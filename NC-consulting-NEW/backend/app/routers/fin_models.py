from fastapi import APIRouter, Depends, HTTPException, Response, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.database import get_db
from app.excel_report import build_fin_model_excel
from app.models import FinModel, User
from app.project_form import ProjectFormIn, present, to_fin_model
from app.reports import build_fin_model_report
from app.schemas import FinModelData, FinModelOut
from app.security import get_current_user
from app.tariffs import ensure_can_create_model

router = APIRouter(prefix="/api/fin-models", tags=["fin-models"])

DOCX_MEDIA_TYPE = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
XLSX_MEDIA_TYPE = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"


def _to_out(model: FinModel) -> FinModelOut:
    industry, horizon = present(model.data or {})
    return FinModelOut(
        id=model.id,
        name=model.name,
        industry=industry,
        horizon_months=horizon,
        created_at=model.created_at,
    )


def _as_fin(model: FinModel) -> FinModelData:
    data = model.data or {}
    if "volume_sales" in data:
        return to_fin_model(ProjectFormIn.model_validate(data))
    return FinModelData.model_validate(data)


def _get_owned(db: Session, user: User, model_id: int) -> FinModel:
    model = db.get(FinModel, model_id)
    if model is None or model.user_id != user.id:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Модель не найдена")
    return model


def _attachment(content: bytes, media_type: str, filename: str) -> Response:
    return Response(
        content=content,
        media_type=media_type,
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


@router.get("", response_model=list[FinModelOut])
def list_fin_models(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    models = db.scalars(
        select(FinModel).where(FinModel.user_id == user.id).order_by(FinModel.created_at.desc())
    )
    return [_to_out(m) for m in models]


@router.post("", response_model=FinModelOut, status_code=status.HTTP_201_CREATED)
def create_fin_model(
    data: ProjectFormIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    ensure_can_create_model(db, user)
    model = FinModel(user_id=user.id, name=data.name.strip(), data=data.model_dump())
    db.add(model)
    db.commit()
    db.refresh(model)
    return _to_out(model)


@router.get("/{model_id}/report.docx")
def download_report(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_owned(db, user, model_id)
    return _attachment(
        build_fin_model_report(user, _as_fin(model)), DOCX_MEDIA_TYPE, f"NC-fin-model-{model.id}.docx"
    )


@router.get("/{model_id}/model.xlsx")
def download_excel(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_owned(db, user, model_id)
    return _attachment(
        build_fin_model_excel(user, _as_fin(model)), XLSX_MEDIA_TYPE, f"NC-fin-model-{model.id}.xlsx"
    )
