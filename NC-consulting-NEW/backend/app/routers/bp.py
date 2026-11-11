from fastapi import APIRouter, Depends, HTTPException, status
from pydantic import BaseModel, Field, field_validator
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.bp_templates import (
    COVERS,
    demo_facts,
    provider_for_model,
    request_body,
    setting_payload,
    template_payload,
)
from app.database import get_db
from app.bp_jobs import job_payload
from app.models import BpJob, BpSetting, BpTemplate, FinModel, User
from app.security import get_current_admin, get_current_user

client = APIRouter(prefix="/api/bp", tags=["bp"])
admin = APIRouter(prefix="/api/admin/bp", tags=["admin-bp"])


class SectionIn(BaseModel):
    key: str = Field(min_length=1, max_length=40, pattern=r"^[a-zA-Z0-9_-]+$")
    title: str = Field(min_length=1, max_length=200)
    source: str
    prompt: str = Field(default="", max_length=4000)
    sample: str = Field(default="", max_length=6000)

    @field_validator("source")
    @classmethod
    def source_ok(cls, value: str) -> str:
        if value not in ("ai", "facts"):
            raise ValueError("Раздел либо пишет модель, либо берётся из финмодели")
        return value


class TemplateIn(BaseModel):
    name: str = Field(min_length=1, max_length=120)
    description: str = Field(default="", max_length=400)
    cover: str
    accent: str = Field(pattern=r"^#[0-9A-Fa-f]{6}$")
    sections: list[SectionIn] = Field(min_length=1, max_length=16)

    @field_validator("cover")
    @classmethod
    def cover_ok(cls, value: str) -> str:
        if value not in COVERS:
            raise ValueError("Неизвестная обложка")
        return value

    @field_validator("sections")
    @classmethod
    def keys_ok(cls, value: list[SectionIn]) -> list[SectionIn]:
        keys = [item.key for item in value]
        if len(keys) != len(set(keys)):
            raise ValueError("Ключи разделов должны быть разными")
        return value


class SettingIn(BaseModel):
    provider: str = Field(min_length=1, max_length=40)
    model: str = Field(min_length=1, max_length=80)


def _setting(db: Session) -> BpSetting:
    row = db.get(BpSetting, 1)
    if row is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Модель для бизнес-плана не настроена")
    return row


def _templates(db: Session) -> list[BpTemplate]:
    return list(db.scalars(select(BpTemplate).order_by(BpTemplate.sort_order, BpTemplate.id)))


def _template(db: Session, template_id: int) -> BpTemplate:
    row = db.get(BpTemplate, template_id)
    if row is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Шаблон не найден")
    return row


@client.get("")
def bp_catalog(_: User = Depends(get_current_user), db: Session = Depends(get_db)):
    return {
        "templates": [
            {
                "id": item.id,
                "slug": item.slug,
                "name": item.name,
                "description": item.description,
                "cover": item.cover,
                "accent": item.accent,
                "sections": [
                    {
                        "key": section.get("key"),
                        "title": section.get("title"),
                        "source": section.get("source"),
                        "sample": section.get("sample") or "",
                    }
                    for section in (item.sections or [])
                ],
            }
            for item in _templates(db)
        ],
    }


@admin.get("/jobs")
def admin_jobs(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    rows = db.scalars(select(BpJob).order_by(BpJob.id.desc()).limit(40)).all()
    result = []
    for job in rows:
        user = db.get(User, job.user_id)
        project = db.get(FinModel, job.fin_model_id)
        result.append(
            {
                **job_payload(db, job),
                "client": user.full_name if user else "",
                "email": user.email if user else "",
                "project": project.name if project else "",
            }
        )
    return result


@admin.get("")
def admin_bp(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    row = _setting(db)
    return {
        **setting_payload(row),
        "templates": [template_payload(item) for item in _templates(db)],
        "demo": demo_facts(),
    }


@admin.put("/settings")
def update_settings(data: SettingIn, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    if provider_for_model(data.model) != data.provider:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Эта модель не относится к выбранной языковой модели",
        )
    row = _setting(db)
    row.provider = data.provider
    row.model = data.model
    db.commit()
    db.refresh(row)
    return setting_payload(row)


@admin.put("/templates/{template_id}")
def update_template(
    template_id: int,
    data: TemplateIn,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    row = _template(db, template_id)
    row.name = data.name.strip()
    row.description = data.description.strip()
    row.cover = data.cover
    row.accent = data.accent
    row.sections = [item.model_dump() for item in data.sections]
    db.commit()
    db.refresh(row)
    return template_payload(row)


@admin.post("/request")
def preview_request(
    data: TemplateIn,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    """Тело одного запроса, которым весь текст бизнес-плана уйдёт в выбранную модель."""
    row = _setting(db)
    return request_body(data.model_dump(), demo_facts(), row.model)
