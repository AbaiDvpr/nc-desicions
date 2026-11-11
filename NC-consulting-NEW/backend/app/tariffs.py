import calendar
from datetime import datetime, timezone
from zoneinfo import ZoneInfo

from fastapi import HTTPException, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.database import SessionLocal
from app.models import Company, FinModel, LedgerEntry, Tariff, User, UserTariff

ALMATY = ZoneInfo("Asia/Almaty")
MONTHS = (
    "",
    "январь",
    "февраль",
    "март",
    "апрель",
    "май",
    "июнь",
    "июль",
    "август",
    "сентябрь",
    "октябрь",
    "ноябрь",
    "декабрь",
)

PLANS = [
    {
        "slug": "basic",
        "name": "Базовый",
        "period": "month",
        "price": 0,
        "models_limit": 5,
        "edits_limit": 2,
        "companies_limit": 1,
        "plans_limit": 1,
        "is_default": True,
        "sort_order": 1,
        "features": [
            "Финансовых моделей: 5",
            "Правок в модели: 2",
            "Компаний в управленческом учёте: 1",
            "Планов по компании: 1",
        ],
    },
    {
        "slug": "start",
        "name": "Старт",
        "period": "month",
        "price": 9900,
        "models_limit": 3,
        "edits_limit": 3,
        "companies_limit": 1,
        "plans_limit": 1,
        "is_default": False,
        "sort_order": 2,
        "features": [
            "Финансовых моделей: 3",
            "Правок в модели: 3",
            "Компаний в управленческом учёте: 1",
            "Планов по компании: 1",
        ],
    },
    {
        "slug": "business",
        "name": "Бизнес",
        "period": "month",
        "price": 19900,
        "models_limit": 10,
        "edits_limit": 5,
        "companies_limit": 3,
        "plans_limit": 3,
        "is_default": False,
        "sort_order": 3,
        "features": [
            "Финансовых моделей: 10",
            "Правок в модели: 5",
            "Компаний в управленческом учёте: 3",
            "Планов по компании: 3",
        ],
    },
    {
        "slug": "pro",
        "name": "Про",
        "period": "month",
        "price": 39900,
        "models_limit": 30,
        "edits_limit": 10,
        "companies_limit": 10,
        "plans_limit": 10,
        "is_default": False,
        "sort_order": 4,
        "features": [
            "Финансовых моделей: 30",
            "Правок в модели: 10",
            "Компаний в управленческом учёте: 10",
            "Планов по компании: 10",
        ],
    },
    {
        "slug": "business-year",
        "name": "Бизнес",
        "period": "year",
        "price": 119400,
        "models_limit": 10,
        "edits_limit": 5,
        "companies_limit": 3,
        "plans_limit": 3,
        "is_default": False,
        "sort_order": 5,
        "features": [
            "Финансовых моделей: 10",
            "Правок в модели: 5",
            "Компаний в управленческом учёте: 3",
            "Планов по компании: 3",
        ],
    },
    {
        "slug": "pro-year",
        "name": "Про",
        "period": "year",
        "price": 239400,
        "models_limit": 30,
        "edits_limit": 10,
        "companies_limit": 10,
        "plans_limit": 10,
        "is_default": False,
        "sort_order": 6,
        "features": [
            "Финансовых моделей: 30",
            "Правок в модели: 10",
            "Компаний в управленческом учёте: 10",
            "Планов по компании: 10",
        ],
    },
]


def _shift(moment: datetime, months: int) -> datetime:
    month_index = moment.month - 1 + months
    year = moment.year + month_index // 12
    month = month_index % 12 + 1
    day = min(moment.day, calendar.monthrange(year, month)[1])
    return moment.replace(year=year, month=month, day=day)


def _aware(moment: datetime) -> datetime:
    if moment.tzinfo is None:
        return moment.replace(tzinfo=timezone.utc)
    return moment


def period_bounds(sub: UserTariff, now: datetime | None = None) -> tuple[datetime, datetime, str]:
    """Окно, в котором тратится лимит. Месячный тариф — текущий месяц, годовой — срок подписки."""
    now = _aware(now or datetime.now(timezone.utc))
    started = _aware(sub.started_at)
    local = now.astimezone(ALMATY)
    if sub.period == "year":
        end = _aware(sub.expires_at) if sub.expires_at is not None else _shift(started, 12)
        label = f"{started.astimezone(ALMATY).strftime('%d.%m.%Y')}–{end.astimezone(ALMATY).strftime('%d.%m.%Y')}"
        return started, end, label
    month_start = datetime(local.year, local.month, 1, tzinfo=ALMATY)
    if local.month == 12:
        month_end = datetime(local.year + 1, 1, 1, tzinfo=ALMATY)
    else:
        month_end = datetime(local.year, local.month + 1, 1, tzinfo=ALMATY)
    start = max(started, month_start)
    return start, month_end, f"{MONTHS[local.month]} {local.year}"


def models_used(db: Session, user_id: int, start: datetime | None = None, end: datetime | None = None) -> int:
    query = select(func.count()).select_from(FinModel).where(
        FinModel.user_id == user_id,
        FinModel.status != "draft",
        FinModel.is_deleted.is_(False),
    )
    if start is not None:
        query = query.where(FinModel.created_at >= start)
    if end is not None:
        query = query.where(FinModel.created_at < end)
    return db.scalar(query) or 0


def quota(db: Session, user: User, now: datetime | None = None) -> dict:
    """Сколько моделей можно создать в текущем периоде: лимит тарифа плюс добавка администратора."""
    now = _aware(now or datetime.now(timezone.utc))
    sub = current_subscription(db, user)
    empty = {
        "subscription": sub,
        "used": 0,
        "left": 0,
        "extra": 0,
        "allowance": 0,
        "label": "",
        "blocked": sub is None or subscription_expired(sub),
    }
    if sub is None or subscription_expired(sub):
        return empty
    start, end, label = period_bounds(sub, now)
    if now < start:
        empty["label"] = label
        empty["blocked"] = True
        empty["extra"] = sub.models_extra or 0
        empty["allowance"] = sub.models_limit + empty["extra"]
        empty["left"] = empty["allowance"]
        return empty
    used = models_used(db, user.id, start, end)
    extra = sub.models_extra or 0
    allowance = sub.models_limit + extra
    return {
        "subscription": sub,
        "used": used,
        "left": max(allowance - used, 0),
        "extra": extra,
        "allowance": allowance,
        "label": label,
        "blocked": False,
    }


def current_subscription(db: Session, user: User) -> UserTariff | None:
    if not user.current_tariff_id:
        return None
    return db.get(UserTariff, user.current_tariff_id)


def subscription_expired(sub: UserTariff | None) -> bool:
    if sub is None or sub.expires_at is None:
        return False
    return sub.expires_at < datetime.now(timezone.utc)


def ensure_can_create_company(db: Session, user: User) -> None:
    sub = current_subscription(db, user)
    if sub is None or subscription_expired(sub):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="По вашему тарифу сейчас нельзя создать компанию",
        )
    tariff = db.get(Tariff, sub.tariff_id)
    limit = tariff.companies_limit if tariff else 1
    used = db.scalar(select(func.count()).select_from(Company).where(Company.user_id == user.id)) or 0
    if used >= limit:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="По вашему тарифу вы исчерпали количество компаний",
        )


def ensure_can_create_model(db: Session, user: User) -> None:
    state = quota(db, user)
    if state["blocked"]:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="По вашему тарифу сейчас нельзя создать финансовую модель",
        )
    if state["used"] >= state["allowance"]:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="По вашему тарифу вы исчерпали возможность рассчитывать финансовые модели за этот период",
        )


def assign_default(db: Session, user: User) -> UserTariff:
    basic = db.scalar(select(Tariff).where(Tariff.slug == "basic"))
    if basic is None:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail="Тариф по умолчанию не найден")
    return assign_tariff(db, user, basic, charge=False)


def assign_tariff(
    db: Session,
    user: User,
    tariff: Tariff,
    *,
    charge: bool,
    started_at: datetime | None = None,
    models_extra: int = 0,
) -> UserTariff:
    now = _aware(started_at or datetime.now(timezone.utc))
    expires = None
    if not tariff.is_default:
        expires = _shift(now, 12 if tariff.period == "year" else 1)

    sub = UserTariff(
        user_id=user.id,
        tariff_id=tariff.id,
        name=tariff.name,
        period=tariff.period,
        price=tariff.price,
        models_limit=tariff.models_limit,
        models_extra=max(0, int(models_extra)),
        edits_limit=tariff.edits_limit,
        features=list(tariff.features),
        started_at=now,
        expires_at=expires,
    )
    db.add(sub)
    db.flush()
    user.current_tariff_id = sub.id

    if charge and tariff.price:
        if user.balance < tariff.price:
            raise HTTPException(
                status_code=status.HTTP_402_PAYMENT_REQUIRED,
                detail="Недостаточно средств на балансе",
            )
        user.balance -= tariff.price
        db.add(
            LedgerEntry(
                user_id=user.id,
                amount=-tariff.price,
                comment=f"Списание в счёт оплаты тарифного плана {tariff.name}",
            )
        )
    db.add(user)
    return sub


def backfill_tariffs() -> None:
    db = SessionLocal()
    try:
        existing = {row.slug for row in db.scalars(select(Tariff)).all()}
        for plan in PLANS:
            if plan["slug"] not in existing:
                db.add(Tariff(**plan))
        db.flush()

        basic = db.scalar(select(Tariff).where(Tariff.slug == "basic"))
        users = db.scalars(select(User).where(User.current_tariff_id.is_(None))).all()
        for user in users:
            assign_tariff(db, user, basic, charge=False)
        db.commit()
    finally:
        db.close()
