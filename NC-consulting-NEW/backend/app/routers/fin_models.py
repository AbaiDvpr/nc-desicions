import json

from fastapi import APIRouter, Depends, HTTPException, Query, Response, status
from pydantic import BaseModel, Field
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.audit_export import build_audit_payload
from app.bp_jobs import (
    AttemptLimit,
    NothingToRetry,
    RetryBusy,
    _jobs,
    _latest,
    client_job_payload,
    bp_cards,
    launch,
    launch_review,
    render_job,
    render_job_pdf,
    retry_failed,
    start_job,
    start_review,
)
from app.database import get_db
from app.excel_report import build_fin_model_excel
from app.fem_v2 import build_fem_v2_excel
from app.fem_v3_book import build_fem_v3_excel
from app.fem_v4_book import build_fem_v4_excel
from app.fem_v5_book import build_fem_v5_excel
from app.models import BpJob, BpTemplate, FinModel, User
from app.project_form import ZERO_COGS_MESSAGE, ProjectFormIn, card_facts, to_fin_model, zero_cogs_state
from app.schemas import DraftIn, DraftOut, FinModelData, FinModelDetail, FinModelOut, FinModelPage
from app.security import get_current_user
from app.tariffs import ensure_can_create_model
from app.xlsx_calc import XlsxCalcError, recalculate_xlsx

router = APIRouter(prefix="/api/fin-models", tags=["fin-models"])

DOCX_MEDIA_TYPE = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
XLSX_MEDIA_TYPE = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
JSON_MEDIA_TYPE = "application/json; charset=utf-8"


def _draft_body(model: FinModel) -> dict | None:
    data = model.data or {}
    if model.status == "draft" and isinstance(data, dict) and data.get("_draft"):
        return data
    return None


def _to_out(
    model: FinModel,
    bp_status: str = "",
    bp_count: int = 0,
    bp_complete: int = 0,
    bp_started_at=None,
    bp_finished_at=None,
) -> FinModelOut:
    draft = _draft_body(model)
    source = draft.get("form") if draft else (model.data or {})
    facts = card_facts(source if isinstance(source, dict) else {})
    return FinModelOut(
        id=model.id,
        name=model.name,
        industry=facts["industry"],
        horizon_months=facts["horizon_months"],
        created_at=model.created_at,
        status="draft" if draft else "ready",
        completed_steps=int(draft.get("completed_steps") or 0) if draft else 0,
        legal_form=facts["legal_form"],
        planning_from=facts["planning_from"],
        planning_to=facts["planning_to"],
        financing=facts["financing"],
        gov_program=facts.get("gov_program") or "",
        bp_status=bp_status,
        bp_count=bp_count,
        bp_complete=bp_complete,
        bp_started_at=bp_started_at,
        bp_finished_at=bp_finished_at,
    )


def _as_fin(model: FinModel) -> FinModelData:
    if _draft_body(model):
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Черновик ещё не готов к выгрузке")
    data = model.data or {}
    if "volume_sales" in data:
        return to_fin_model(ProjectFormIn.model_validate(data))
    return FinModelData.model_validate(data)


def _draft_name(form: dict) -> str:
    name = str(form.get("name") or "").strip()
    return name[:500] or "Черновик"


def _write_draft(model: FinModel, body: DraftIn) -> None:
    if body.reached_step < body.completed_steps:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Некорректный черновик")
    model.name = _draft_name(body.form)
    model.status = "draft"
    model.data = {
        "_draft": True,
        "form": body.form,
        "completed_steps": body.completed_steps,
        "reached_step": body.reached_step,
    }


def _get_owned(db: Session, user: User, model_id: int) -> FinModel:
    model = db.get(FinModel, model_id)
    if model is None or model.user_id != user.id:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Модель не найдена")
    return model


def _get_active(db: Session, user: User, model_id: int) -> FinModel:
    model = _get_owned(db, user, model_id)
    if model.is_deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Модель в архиве")
    return model


def _attachment(content: bytes, media_type: str, filename: str) -> Response:
    return Response(
        content=content,
        media_type=media_type,
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


PAGE_SIZE = 10


def _name_pattern(value: str) -> str:
    escaped = value.replace("\\", "\\\\").replace("%", "\\%").replace("_", "\\_")
    return f"%{escaped}%"


@router.get("", response_model=FinModelPage)
def list_fin_models(
    archived: bool = False,
    page: int = Query(1, ge=1),
    q: str = Query("", max_length=200),
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    filters = [FinModel.user_id == user.id, FinModel.is_deleted.is_(archived)]
    text = q.strip()
    if text:
        filters.append(FinModel.name.ilike(_name_pattern(text), escape="\\"))
    total = db.scalar(select(func.count()).select_from(FinModel).where(*filters)) or 0
    pages = max(1, (total + PAGE_SIZE - 1) // PAGE_SIZE) if total else 1
    current = min(page, pages)
    models = list(
        db.scalars(
            select(FinModel)
            .where(*filters)
            .order_by(FinModel.created_at.desc(), FinModel.id.desc())
            .offset((current - 1) * PAGE_SIZE)
            .limit(PAGE_SIZE)
        )
    )
    cards = bp_cards(db, user.id, [model.id for model in models])
    return FinModelPage(
        items=[_to_out(model, **cards.get(model.id, {})) for model in models],
        total=total,
        page=current,
        pages=pages,
        limit=PAGE_SIZE,
    )


@router.post("/{model_id}/archive", response_model=FinModelOut)
def archive_fin_model(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_owned(db, user, model_id)
    model.is_deleted = True
    db.commit()
    db.refresh(model)
    return _to_out(model)


@router.post("/{model_id}/restore", response_model=FinModelOut)
def restore_fin_model(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_owned(db, user, model_id)
    if model.is_deleted and model.status != "draft":
        ensure_can_create_model(db, user)
    model.is_deleted = False
    db.commit()
    db.refresh(model)
    return _to_out(model)


@router.post("/drafts", response_model=FinModelOut, status_code=status.HTTP_201_CREATED)
def create_draft(
    body: DraftIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = FinModel(user_id=user.id, name="Черновик", status="draft", data={})
    _write_draft(model, body)
    db.add(model)
    db.commit()
    db.refresh(model)
    return _to_out(model)


@router.get("/drafts/{draft_id}", response_model=DraftOut)
def get_draft(
    draft_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, draft_id)
    draft = _draft_body(model)
    if draft is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Черновик не найден")
    return DraftOut(
        id=model.id,
        form=draft.get("form") or {},
        completed_steps=int(draft.get("completed_steps") or 1),
        reached_step=int(draft.get("reached_step") or 1),
    )


@router.put("/drafts/{draft_id}", response_model=FinModelOut)
def update_draft(
    draft_id: int,
    body: DraftIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, draft_id)
    if _draft_body(model) is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Черновик не найден")
    _write_draft(model, body)
    db.commit()
    db.refresh(model)
    return _to_out(model)


def _ready_payload(data: ProjectFormIn) -> dict:
    payload = data.model_dump()
    cogs = zero_cogs_state(payload)
    if cogs["zero"] and not cogs["confirmed"]:
        raise HTTPException(status_code=422, detail=ZERO_COGS_MESSAGE)
    if not cogs["zero"]:
        payload["zero_cogs_confirmed"] = False
    return payload


@router.post("", response_model=FinModelOut, status_code=status.HTTP_201_CREATED)
def create_fin_model(
    data: ProjectFormIn,
    draft_id: int | None = None,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    ensure_can_create_model(db, user)
    payload = _ready_payload(data)
    name = data.name.strip()
    if draft_id is not None:
        model = _get_active(db, user, draft_id)
        if _draft_body(model) is None:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Черновик не найден")
        model.name = name
        model.status = "ready"
        model.data = payload
    else:
        model = FinModel(user_id=user.id, name=name, status="ready", data=payload)
        db.add(model)
    db.commit()
    db.refresh(model)
    return _to_out(model)


def _ready_form(model: FinModel) -> dict:
    if _draft_body(model) or model.status != "ready":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Модель не найдена")
    data = model.data or {}
    if "volume_sales" not in data:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Эту модель нельзя открыть для редактирования",
        )
    return data


@router.get("/{model_id}", response_model=FinModelDetail)
def get_fin_model(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    return FinModelDetail(id=model.id, name=model.name, form=_ready_form(model))


@router.put("/{model_id}", response_model=FinModelOut)
def update_fin_model(
    model_id: int,
    data: ProjectFormIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    _ready_form(model)
    payload = _ready_payload(data)
    model.name = data.name.strip()
    model.status = "ready"
    model.data = payload
    db.commit()
    db.refresh(model)
    return _to_out(model)


@router.post("/{model_id}/business-plan")
def start_business_plan(
    model_id: int,
    template_id: int | None = None,
    force: bool = False,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    _ready_form(model)
    try:
        job, fresh = start_job(db, user, model, template_id, force)
    except LookupError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc)) from exc
    except AttemptLimit as exc:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(exc)) from exc
    if fresh:
        launch(job.id)
    return client_job_payload(db, job)


@router.post("/{model_id}/business-plan/{job_id}/retry")
def retry_business_plan(
    model_id: int,
    job_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    _ready_form(model)
    try:
        job, fresh = retry_failed(db, user, model, job_id)
    except LookupError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc)) from exc
    except NothingToRetry as exc:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(exc)) from exc
    except RetryBusy as exc:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(exc)) from exc
    if fresh:
        launch(job.id)
    return client_job_payload(db, job)


class TaskReviewIn(BaseModel):
    answer: str = Field(default="", max_length=20000)


@router.post("/{model_id}/business-plan/{job_id}/tasks/{task_id}/review")
def review_business_plan_task(
    model_id: int,
    job_id: int,
    task_id: int,
    data: TaskReviewIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    _ready_form(model)
    try:
        job, fresh = start_review(db, user, model, job_id, task_id, data.answer)
    except LookupError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc)) from exc
    except NothingToRetry as exc:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(exc)) from exc
    except RetryBusy as exc:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(exc)) from exc
    if fresh:
        launch_review(task_id)
    return client_job_payload(db, job)


@router.get("/{model_id}/business-plan")
def get_business_plan(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    job = _latest(db, user.id, model.id)
    if job is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Бизнес-план ещё не отправляли")
    return client_job_payload(db, job)


def _report_file(model_id: int, template_id: int | None, job_id: int | None, user: User, db: Session):
    model = _get_active(db, user, model_id)
    if job_id:
        job = db.get(BpJob, job_id)
        if job is None or job.user_id != user.id or job.fin_model_id != model.id:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Такого варианта нет")
    else:
        job = _latest(db, user.id, model.id)
    if job is None or job.status in ("queued", "running"):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Файл появится, когда бизнес-план будет готов.",
        )
    template = db.get(BpTemplate, template_id) if template_id else None
    if template_id and template is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Шаблон не найден")
    slug = template.slug if template else "plan"
    number = next(
        (index for index, item in enumerate(_jobs(db, user.id, model.id), start=1) if item.id == job.id),
        1,
    )
    form = _ready_form(model)
    return model, job, form, template, slug, number


@router.get("/{model_id}/report.docx")
def download_report(
    model_id: int,
    template_id: int | None = None,
    job_id: int | None = None,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model, job, form, template, slug, number = _report_file(model_id, template_id, job_id, user, db)
    return _attachment(
        render_job(db, job, form, template),
        DOCX_MEDIA_TYPE,
        f"NC-bp-{model.id}-{slug}-v{number}.docx",
    )


@router.get("/{model_id}/report.pdf")
def download_report_pdf(
    model_id: int,
    template_id: int | None = None,
    job_id: int | None = None,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model, job, form, template, slug, number = _report_file(model_id, template_id, job_id, user, db)
    return _attachment(
        render_job_pdf(db, job, form, template),
        "application/pdf",
        f"NC-bp-{model.id}-{slug}-v{number}.pdf",
    )


@router.get("/{model_id}/fem-v2.xlsx")
def download_fem_v2(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    return _attachment(
        build_fem_v2_excel(user, _ready_form(model)),
        XLSX_MEDIA_TYPE,
        f"NC-fem-v2-{model.id}.xlsx",
    )


@router.get("/{model_id}/fem-v3.xlsx")
def download_fem_v3(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    return _attachment(
        build_fem_v3_excel(user, _ready_form(model)),
        XLSX_MEDIA_TYPE,
        f"NC-fem-v3-{model.id}.xlsx",
    )


@router.get("/{model_id}/fem-v4.xlsx")
def download_fem_v4(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    return _attachment(
        build_fem_v4_excel(user, _ready_form(model)),
        XLSX_MEDIA_TYPE,
        f"NC-fem-v4-{model.id}.xlsx",
    )


@router.get("/{model_id}/fem-v5.xlsx")
def download_fem_v5(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    raw = build_fem_v5_excel(user, _ready_form(model))
    try:
        content = recalculate_xlsx(raw)
    except XlsxCalcError as exc:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Не удалось пересчитать Excel перед скачиванием",
        ) from exc
    return _attachment(content, XLSX_MEDIA_TYPE, f"NC-fem-v5-{model.id}.xlsx")


@router.get("/{model_id}/audit.json")
def download_audit_json(
    model_id: int,
    steps: str = Query("1,2,3,4", max_length=40),
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """JSON анкеты с пояснениями для ИИ-аудита. Шаги 1–4."""
    model = _get_active(db, user, model_id)
    form = _ready_form(model)
    allowed = {1, 2, 3, 4}
    wanted: list[int] = []
    for part in str(steps or "1,2,3,4").split(","):
        part = part.strip()
        if not part:
            continue
        try:
            number = int(part)
        except ValueError as exc:
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Неверный параметр steps") from exc
        if number not in allowed:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Доступны шаги 1–4. Укажите steps=1,2,3,4",
            )
        if number not in wanted:
            wanted.append(number)
    if not wanted:
        wanted = [1, 2, 3, 4]
    payload = build_audit_payload(model.id, model.name, form, steps=wanted)
    body = json.dumps(payload, ensure_ascii=False, indent=2).encode("utf-8")
    label = "-".join(str(n) for n in wanted)
    return _attachment(body, JSON_MEDIA_TYPE, f"NC-audit-steps{label}-{model.id}.json")


@router.get("/{model_id}/model.xlsx")
def download_excel(
    model_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    model = _get_active(db, user, model_id)
    return _attachment(
        build_fin_model_excel(user, _as_fin(model)), XLSX_MEDIA_TYPE, f"NC-fin-model-{model.id}.xlsx"
    )
