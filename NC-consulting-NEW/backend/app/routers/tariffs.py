from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.database import get_db
from app.models import LedgerEntry, Tariff, User, UserTariff
from app.schemas import TariffBuyIn, TariffCurrentOut, TariffOut
from app.security import get_current_user
from app.tariffs import assign_tariff, quota, subscription_expired

router = APIRouter(prefix="/api/tariffs", tags=["tariffs"])


def _tariff_out(tariff: Tariff) -> TariffOut:
    return TariffOut.model_validate(tariff)


@router.get("", response_model=list[TariffOut])
def list_tariffs(
    period: str = "month",
    db: Session = Depends(get_db),
    _: User = Depends(get_current_user),
):
    if period not in {"month", "year"}:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Неизвестный период")
    rows = db.scalars(select(Tariff).where(Tariff.period == period).order_by(Tariff.sort_order)).all()
    return [_tariff_out(row) for row in rows]


@router.get("/current", response_model=TariffCurrentOut)
def current_tariff(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    sub = db.get(UserTariff, user.current_tariff_id) if user.current_tariff_id else None
    state = quota(db, user)
    used = state["used"]
    now = datetime.now(timezone.utc)
    left = 0 if state["blocked"] else state["left"]
    days_left = None
    progress = 100
    if sub is not None and sub.expires_at is not None:
        total = (sub.expires_at - sub.started_at).total_seconds() or 1
        passed = (now - sub.started_at).total_seconds()
        progress = max(0, min(100, round(100 - passed / total * 100)))
        days_left = max(0, round((sub.expires_at - now).total_seconds() / 86400))

    history = db.scalars(
        select(LedgerEntry).where(LedgerEntry.user_id == user.id).order_by(LedgerEntry.id.desc())
    ).all()
    return TariffCurrentOut(
        balance=user.balance,
        models_used=used,
        models_left=left,
        models_extra=state["extra"],
        models_allowance=state["allowance"],
        period_label=state["label"],
        expired=subscription_expired(sub),
        days_left=days_left,
        expire_progress=progress,
        subscription=None
        if sub is None
        else {
            "name": sub.name,
            "period": sub.period,
            "price": sub.price,
            "models_limit": sub.models_limit,
            "edits_limit": sub.edits_limit,
            "features": sub.features,
            "started_at": sub.started_at,
            "expires_at": sub.expires_at,
            "everlasting": sub.expires_at is None,
        },
        payments=[
            {"id": row.id, "amount": row.amount, "comment": row.comment, "created_at": row.created_at}
            for row in history
        ],
    )


@router.get("/{tariff_id}", response_model=TariffOut)
def get_tariff(tariff_id: int, db: Session = Depends(get_db), _: User = Depends(get_current_user)):
    tariff = db.get(Tariff, tariff_id)
    if tariff is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Тариф не найден")
    return _tariff_out(tariff)


@router.post("/{tariff_id}/buy", response_model=TariffOut)
def buy_tariff(
    tariff_id: int,
    data: TariffBuyIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    tariff = db.get(Tariff, tariff_id)
    if tariff is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Тариф не найден")
    if not data.confirm:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Подтвердите оплату")

    sub = db.get(UserTariff, user.current_tariff_id) if user.current_tariff_id else None
    if sub and sub.tariff_id == tariff.id and not subscription_expired(sub) and tariff.is_default:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Этот тариф уже подключён")

    if tariff.price and user.balance < tariff.price:
        raise HTTPException(
            status_code=status.HTTP_402_PAYMENT_REQUIRED,
            detail="Недостаточно средств на балансе. Пополните счёт или оплатите через Wooppay",
        )

    user.phone = data.phone.strip()
    assign_tariff(db, user, tariff, charge=not tariff.is_default)
    db.commit()
    return _tariff_out(tariff)
