from fastapi import APIRouter, Depends, HTTPException, status
from pydantic import BaseModel, Field
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.database import get_db
from app.fem_rates import DEFAULT_RATES, ensure_fem_rates, rates_payload
from app.fem_v5_book import national_bank_base_rate
from app.gov_catalog import (
    DEFAULT_SUBSIDY_CLIENT_RATE,
    ensure_gov_catalog,
    list_catalog,
    program_payload,
    refresh_from_damu,
)
from app.models import GovProgram, User
from app.security import get_current_admin, get_current_user

router = APIRouter(prefix="/api/gov-programs", tags=["gov-programs"])
admin = APIRouter(prefix="/api/admin/gov-programs", tags=["admin-gov-programs"])


class GovProgramUpdate(BaseModel):
    title: str = Field(default="", max_length=240)
    kind: str = Field(default="subsidy", max_length=20)
    kind_label: str = Field(default="Субсидия", max_length=40)
    url: str = Field(default="", max_length=300)
    client_rate: float | None = Field(default=None, ge=0, le=100)
    amount: str = Field(default="", max_length=120)
    detail: str = Field(default="", max_length=300)
    purpose: str = Field(default="", max_length=240)
    term_min_months: int | None = None
    term_max_months: int | None = None
    term_kind: str = Field(default="", max_length=20)
    logic: str = Field(default="", max_length=800)
    allows_investment: bool = True
    allows_wc: bool = True
    grace_max_months: int | None = None
    is_active: bool = True
    sort_order: int = 0


class GovProgramCreate(GovProgramUpdate):
    slug: str = Field(min_length=1, max_length=80)


@router.get("")
def gov_programs(_: User = Depends(get_current_user), db: Session = Depends(get_db)):
    """Каталог из БД (не парсит Damu на каждый запрос)."""
    ensure_gov_catalog(db)
    return list_catalog(db, active_only=True)


@router.get("/base-rate")
def nbk_base_rate(_: User = Depends(get_current_user), db: Session = Depends(get_db)):
    """Базовая ставка НБРК и банковский номинал «НБ РК + N%»."""
    loaded = national_bank_base_rate()
    if loaded is None:
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail="Базовая ставка НБРК сейчас недоступна")
    rate, as_of = loaded
    percent = round(rate * 100, 2)
    spread = float(rates_payload(ensure_fem_rates(db)).get("nbk_bank_spread") or DEFAULT_RATES["nbk_bank_spread"])
    bank = round(percent + spread, 2)
    label = f"НБ РК + {spread:g}%"
    return {
        "rate": percent,
        "as_of": as_of,
        "spread": spread,
        "bank_plus_4": bank,
        "bank_plus_spread": bank,
        "label": label,
    }


@admin.get("")
def admin_list(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    ensure_gov_catalog(db)
    payload = list_catalog(db, active_only=False)
    payload["default_subsidy_client_rate"] = DEFAULT_SUBSIDY_CLIENT_RATE
    return payload


@admin.post("/refresh")
def admin_refresh(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    result = refresh_from_damu(db)
    if not result.get("ok"):
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=result.get("error") or "Не удалось скачать каталог Damu",
        )
    return {**result, **list_catalog(db, active_only=False)}


@admin.post("")
def admin_create(data: GovProgramCreate, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    slug = data.slug.strip().lower().replace(" ", "_")
    if db.scalars(select(GovProgram).where(GovProgram.slug == slug)).first():
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Такой slug уже есть")
    rate = data.client_rate
    if rate is None and data.kind == "subsidy":
        rate = DEFAULT_SUBSIDY_CLIENT_RATE
    row = GovProgram(
        slug=slug,
        title=data.title.strip() or slug,
        kind=data.kind,
        kind_label=data.kind_label,
        url=data.url,
        client_rate=rate,
        amount=data.amount,
        detail=data.detail,
        purpose=data.purpose,
        term_min_months=data.term_min_months,
        term_max_months=data.term_max_months,
        term_kind=data.term_kind or ("subsidy" if data.kind == "subsidy" else ""),
        logic=data.logic,
        allows_investment=data.allows_investment,
        allows_wc=data.allows_wc,
        grace_max_months=data.grace_max_months,
        is_active=data.is_active,
        sort_order=data.sort_order,
        source="manual",
    )
    db.add(row)
    db.commit()
    db.refresh(row)
    return program_payload(row)


@admin.put("/{program_id}")
def admin_update(
    program_id: int,
    data: GovProgramUpdate,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    row = db.get(GovProgram, program_id)
    if row is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Программа не найдена")
    row.title = data.title.strip() or row.title
    row.kind = data.kind
    row.kind_label = data.kind_label
    row.url = data.url
    row.client_rate = data.client_rate
    if row.client_rate is None and row.kind == "subsidy":
        row.client_rate = DEFAULT_SUBSIDY_CLIENT_RATE
    row.amount = data.amount
    row.detail = data.detail
    row.purpose = data.purpose
    row.term_min_months = data.term_min_months
    row.term_max_months = data.term_max_months
    row.term_kind = data.term_kind
    row.logic = data.logic
    row.allows_investment = data.allows_investment
    row.allows_wc = data.allows_wc
    row.grace_max_months = data.grace_max_months
    row.is_active = data.is_active
    row.sort_order = data.sort_order
    row.source = "manual"
    db.commit()
    db.refresh(row)
    return program_payload(row)
