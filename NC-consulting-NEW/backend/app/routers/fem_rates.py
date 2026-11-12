from fastapi import APIRouter, Depends, HTTPException, status
from pydantic import BaseModel, Field
from sqlalchemy.orm import Session

from app.database import get_db
from app.fem_rates import (
    DEFAULT_MZP_SCHEDULE,
    DEFAULT_RATES,
    ensure_fem_rates,
    format_mzp_schedule,
    parse_mzp_schedule,
    rates_payload,
)
from app.models import User
from app.security import get_current_admin, get_current_user

client = APIRouter(prefix="/api/fem-rates", tags=["fem-rates"])
admin = APIRouter(prefix="/api/admin/fem-rates", tags=["admin-fem-rates"])


class MzpRowIn(BaseModel):
    year: int = Field(ge=2000, le=2100)
    amount: float = Field(ge=0, le=10_000_000)


class FemRatesIn(BaseModel):
    simplified_ip_rate: float = Field(ge=0, le=100)
    simplified_too_rate: float = Field(ge=0, le=100)
    cit_ip_rate: float = Field(ge=0, le=100)
    cit_too_rate: float = Field(ge=0, le=100)
    vat_rate: float = Field(ge=0, le=100)
    opv_rate: float = Field(ge=0, le=100)
    vosms_rate: float = Field(ge=0, le=100)
    ipn_rate: float = Field(ge=0, le=100)
    ipn_rate_high: float = Field(ge=0, le=100)
    so_rate: float = Field(ge=0, le=100)
    opvr_rate: float = Field(ge=0, le=100)
    opvr_rate_2027: float = Field(default=4.5, ge=0, le=100)
    opvr_rate_2028: float = Field(default=5.0, ge=0, le=100)
    osms_rate: float = Field(ge=0, le=100)
    social_tax_rate: float = Field(ge=0, le=100)
    nbk_bank_spread: float = Field(ge=0, le=50)
    mrp: float = Field(default=4325.0, gt=0, le=1_000_000)
    vat_threshold_mrp: float = Field(default=10000.0, ge=0, le=100_000_000)
    simplified_limit_mrp: float = Field(default=600000.0, ge=0, le=100_000_000)
    mzp_rows: list[MzpRowIn] | None = None
    mzp_schedule: str | None = None


def _schedule_from_payload(data: FemRatesIn) -> str:
    if data.mzp_rows is not None:
        schedule = {int(row.year): float(row.amount) for row in data.mzp_rows if float(row.amount) >= 0}
        if not schedule:
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Укажите хотя бы один год МЗП")
        return format_mzp_schedule(schedule)
    if data.mzp_schedule is not None and data.mzp_schedule.strip():
        parsed = parse_mzp_schedule(data.mzp_schedule)
        if not parsed:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Неверный формат МЗП. Пример: 2025:85000,2026:85000",
            )
        return format_mzp_schedule(parsed)
    return DEFAULT_MZP_SCHEDULE


@client.get("")
def get_fem_rates(_: User = Depends(get_current_user), db: Session = Depends(get_db)):
    """Дефолты для новой анкеты. Клиент может заменить значения в своей модели."""
    return rates_payload(ensure_fem_rates(db))


@admin.get("")
def admin_get_fem_rates(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    return rates_payload(ensure_fem_rates(db))


@admin.put("")
def admin_put_fem_rates(data: FemRatesIn, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    row = ensure_fem_rates(db)
    for key in DEFAULT_RATES:
        setattr(row, key, float(getattr(data, key)))
    # Старое поле — зеркало упрощёнки ИП.
    row.simplified_rate = float(data.simplified_ip_rate)
    row.mzp_schedule = _schedule_from_payload(data)
    db.commit()
    db.refresh(row)
    return rates_payload(row)
