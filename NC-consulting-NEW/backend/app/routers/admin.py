from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, Response, status
from pydantic import BaseModel, ConfigDict, EmailStr, Field
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.database import get_db
from app.models import AdminTask, Company, FinModel, LedgerEntry, RefillRequest, Tariff, User, UserTariff
from app.security import clear_admin_cookie, get_current_admin, set_admin_cookie, verify_password
from app.tariffs import ALMATY, assign_tariff, quota

router = APIRouter(prefix="/api/admin", tags=["admin"])


class LoginIn(BaseModel):
    email: EmailStr
    password: str


class AdminOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    email: str
    full_name: str


class UserRow(BaseModel):
    id: int
    email: str
    full_name: str
    company: str | None
    phone: str | None
    balance: int
    tariff_name: str | None
    models_count: int
    companies_count: int
    created_at: datetime


class LedgerRow(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    amount: int
    comment: str
    created_at: datetime


class UserQuota(BaseModel):
    tariff_id: int | None
    name: str | None
    period: str | None
    models_limit: int
    models_extra: int
    allowance: int
    used: int
    left: int
    started_at: datetime | None
    expires_at: datetime | None
    period_label: str


class UserDetail(UserRow):
    ledger: list[LedgerRow]
    quota: UserQuota
    model_dates: list[datetime]


class TariffAssignIn(BaseModel):
    tariff_id: int
    started_on: str = Field(pattern=r"^\d{4}-\d{2}-\d{2}$")
    models_extra: int = Field(ge=0, le=10_000)


class BalanceIn(BaseModel):
    amount: int = Field(ge=1, le=5_000_000)
    direction: str = Field(pattern="^(credit|debit)$")
    comment: str = Field(min_length=2, max_length=200)


class ModelRow(BaseModel):
    id: int
    name: str
    user_email: str
    user_name: str
    created_at: datetime


class CompanyRow(BaseModel):
    id: int
    name: str
    legal_form: str
    user_email: str
    user_name: str
    created_at: datetime


class TariffRow(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    name: str
    period: str
    price: int
    models_limit: int
    edits_limit: int
    companies_limit: int
    plans_limit: int
    is_default: bool


class TariffIn(BaseModel):
    price: int = Field(ge=0, le=5_000_000)
    models_limit: int = Field(ge=0, le=10_000)
    edits_limit: int = Field(ge=0, le=10_000)
    companies_limit: int = Field(ge=0, le=10_000)
    plans_limit: int = Field(ge=0, le=10_000)


class RefillRow(BaseModel):
    id: int
    user_id: int
    user_email: str
    full_name: str
    phone: str
    amount: int
    comment: str
    is_paid: bool
    created_at: datetime


def _tariff_name(db: Session, user: User) -> str | None:
    if not user.current_tariff_id:
        return None
    sub = db.get(UserTariff, user.current_tariff_id)
    return sub.name if sub else None


def _quota_out(db: Session, user: User) -> UserQuota:
    state = quota(db, user)
    sub = state["subscription"]
    return UserQuota(
        tariff_id=sub.tariff_id if sub else None,
        name=sub.name if sub else None,
        period=sub.period if sub else None,
        models_limit=sub.models_limit if sub else 0,
        models_extra=state["extra"],
        allowance=state["allowance"],
        used=state["used"],
        left=0 if state["blocked"] else state["left"],
        started_at=sub.started_at if sub else None,
        expires_at=sub.expires_at if sub else None,
        period_label=state["label"],
    )


def _model_dates(db: Session, user: User) -> list[datetime]:
    return list(
        db.scalars(
            select(FinModel.created_at)
            .where(FinModel.user_id == user.id, FinModel.status != "draft", FinModel.is_deleted.is_(False))
            .order_by(FinModel.created_at)
        ).all()
    )


def _user_row(db: Session, user: User) -> UserRow:
    models_count = db.scalar(select(func.count()).select_from(FinModel).where(FinModel.user_id == user.id)) or 0
    companies_count = db.scalar(select(func.count()).select_from(Company).where(Company.user_id == user.id)) or 0
    return UserRow(
        id=user.id,
        email=user.email,
        full_name=user.full_name,
        company=user.company,
        phone=user.phone,
        balance=user.balance,
        tariff_name=_tariff_name(db, user),
        models_count=models_count,
        companies_count=companies_count,
        created_at=user.created_at,
    )


@router.post("/login", response_model=AdminOut)
def login(data: LoginIn, response: Response, db: Session = Depends(get_db)):
    user = db.scalar(select(User).where(func.lower(User.email) == data.email.lower()))
    if user is None or not verify_password(data.password, user.hashed_password):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Неверный email или пароль")
    if not user.is_admin:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Нет доступа в админку")
    set_admin_cookie(response, user.id)
    return user


@router.post("/logout", status_code=status.HTTP_204_NO_CONTENT)
def logout(response: Response):
    clear_admin_cookie(response)


@router.get("/me", response_model=AdminOut)
def me(admin: User = Depends(get_current_admin)):
    return admin


@router.get("/stats")
def stats(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    def count(model):
        return db.scalar(select(func.count()).select_from(model)) or 0

    clients = db.scalar(select(func.count()).select_from(User).where(User.is_admin.is_(False))) or 0
    open_refills = db.scalar(
        select(func.count()).select_from(RefillRequest).where(RefillRequest.is_paid.is_(False))
    ) or 0
    return {
        "users": clients,
        "models": count(FinModel),
        "companies": count(Company),
        "open_refills": open_refills,
    }


@router.get("/users", response_model=list[UserRow])
def users(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    rows = db.scalars(select(User).order_by(User.created_at.desc())).all()
    return [_user_row(db, user) for user in rows if not user.is_admin]


@router.get("/users/{user_id}", response_model=UserDetail)
def user_detail(user_id: int, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    user = db.get(User, user_id)
    if user is None or user.is_admin:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Пользователь не найден")
    ledger = db.scalars(
        select(LedgerEntry).where(LedgerEntry.user_id == user.id).order_by(LedgerEntry.created_at.desc()).limit(12)
    ).all()
    row = _user_row(db, user)
    return UserDetail(**row.model_dump(), ledger=ledger, quota=_quota_out(db, user), model_dates=_model_dates(db, user))


@router.post("/users/{user_id}/balance", response_model=UserDetail)
def adjust_balance(
    user_id: int,
    body: BalanceIn,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    user = db.get(User, user_id)
    if user is None or user.is_admin:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Пользователь не найден")
    signed = body.amount if body.direction == "credit" else -body.amount
    if user.balance + signed < 0:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="На балансе недостаточно средств")
    user.balance += signed
    db.add(LedgerEntry(user_id=user.id, amount=signed, comment=body.comment.strip()))
    db.commit()
    db.refresh(user)
    return user_detail(user_id, _, db)


@router.put("/users/{user_id}/tariff", response_model=UserDetail)
def assign_user_tariff(
    user_id: int,
    body: TariffAssignIn,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    user = db.get(User, user_id)
    if user is None or user.is_admin:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Пользователь не найден")
    tariff = db.get(Tariff, body.tariff_id)
    if tariff is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Тариф не найден")
    year, month, day = (int(part) for part in body.started_on.split("-"))
    try:
        started = datetime(year, month, day, tzinfo=ALMATY)
    except ValueError as error:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Некорректная дата периода") from error
    assign_tariff(db, user, tariff, charge=False, started_at=started, models_extra=body.models_extra)
    db.commit()
    return user_detail(user_id, _, db)


@router.get("/models", response_model=list[ModelRow])
def models(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    rows = db.execute(
        select(FinModel, User).join(User, User.id == FinModel.user_id).order_by(FinModel.created_at.desc())
    ).all()
    return [
        ModelRow(id=model.id, name=model.name, user_email=user.email, user_name=user.full_name, created_at=model.created_at)
        for model, user in rows
    ]


@router.get("/companies", response_model=list[CompanyRow])
def companies(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    rows = db.execute(
        select(Company, User).join(User, User.id == Company.user_id).order_by(Company.created_at.desc())
    ).all()
    return [
        CompanyRow(
            id=company.id,
            name=company.name,
            legal_form=company.legal_form,
            user_email=user.email,
            user_name=user.full_name,
            created_at=company.created_at,
        )
        for company, user in rows
    ]


@router.get("/tariffs", response_model=list[TariffRow])
def tariffs(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    return db.scalars(select(Tariff).order_by(Tariff.sort_order, Tariff.id)).all()


@router.put("/tariffs/{tariff_id}", response_model=TariffRow)
def update_tariff(
    tariff_id: int,
    body: TariffIn,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    tariff = db.get(Tariff, tariff_id)
    if tariff is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Тариф не найден")
    if tariff.is_default and body.price != 0:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Бесплатный тариф остаётся с ценой 0")
    for key, value in body.model_dump().items():
        setattr(tariff, key, value)
    db.commit()
    db.refresh(tariff)
    return tariff


@router.get("/refills", response_model=list[RefillRow])
def refills(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    rows = db.execute(
        select(RefillRequest, User)
        .join(User, User.id == RefillRequest.user_id)
        .order_by(RefillRequest.created_at.desc())
    ).all()
    return [
        RefillRow(
            id=refill.id,
            user_id=user.id,
            user_email=user.email,
            full_name=refill.full_name,
            phone=refill.phone,
            amount=refill.amount,
            comment=refill.comment,
            is_paid=refill.is_paid,
            created_at=refill.created_at,
        )
        for refill, user in rows
    ]


@router.post("/refills/{refill_id}/pay", response_model=RefillRow)
def pay_refill(refill_id: int, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    refill = db.scalar(select(RefillRequest).where(RefillRequest.id == refill_id).with_for_update())
    if refill is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Заявка не найдена")
    user = db.get(User, refill.user_id)
    if user is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Пользователь не найден")
    if not refill.is_paid:
        user.balance += refill.amount
        db.add(LedgerEntry(user_id=user.id, amount=refill.amount, comment="Пополнение по заявке Kaspi"))
        refill.is_paid = True
        db.commit()
        db.refresh(refill)
    return RefillRow(
        id=refill.id,
        user_id=user.id,
        user_email=user.email,
        full_name=refill.full_name,
        phone=refill.phone,
        amount=refill.amount,
        comment=refill.comment,
        is_paid=refill.is_paid,
        created_at=refill.created_at,
    )


TASK_COLUMNS = ("todo", "doing", "done")


class TaskOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    title: str
    note: str
    column: str
    position: int
    created_at: datetime


class TaskIn(BaseModel):
    title: str = Field(min_length=1, max_length=200)
    note: str = Field(default="", max_length=2000)
    column: str = "todo"


class TaskPatch(BaseModel):
    title: str | None = Field(default=None, min_length=1, max_length=200)
    note: str | None = Field(default=None, max_length=2000)


class TaskMove(BaseModel):
    column: str
    before_id: int | None = None


def _tasks(db: Session) -> list[AdminTask]:
    return list(db.scalars(select(AdminTask).order_by(AdminTask.position, AdminTask.id)))


def _column_rows(db: Session, column: str) -> list[AdminTask]:
    return [row for row in _tasks(db) if row.column == column]


def _reindex(rows: list[AdminTask]) -> None:
    for index, row in enumerate(rows):
        row.position = index


@router.get("/tasks", response_model=list[TaskOut])
def list_tasks(_: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    return _tasks(db)


@router.post("/tasks", response_model=TaskOut, status_code=status.HTTP_201_CREATED)
def create_task(body: TaskIn, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    column = body.column if body.column in TASK_COLUMNS else "todo"
    task = AdminTask(
        title=body.title.strip(),
        note=body.note.strip(),
        column=column,
        position=len(_column_rows(db, column)),
    )
    db.add(task)
    db.commit()
    db.refresh(task)
    return task


@router.patch("/tasks/{task_id}", response_model=TaskOut)
def update_task(
    task_id: int,
    body: TaskPatch,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    task = db.get(AdminTask, task_id)
    if task is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Задача не найдена")
    if body.title is not None:
        task.title = body.title.strip()
    if body.note is not None:
        task.note = body.note.strip()
    db.commit()
    db.refresh(task)
    return task


@router.put("/tasks/{task_id}/move", response_model=list[TaskOut])
def move_task(
    task_id: int,
    body: TaskMove,
    _: User = Depends(get_current_admin),
    db: Session = Depends(get_db),
):
    if body.column not in TASK_COLUMNS:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Неизвестная колонка")
    task = db.get(AdminTask, task_id)
    if task is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Задача не найдена")
    grouped = {column: _column_rows(db, column) for column in TASK_COLUMNS}
    grouped[task.column] = [row for row in grouped[task.column] if row.id != task.id]
    dest = grouped[body.column]
    index = len(dest)
    if body.before_id is not None and body.before_id != task.id:
        found = next((at for at, row in enumerate(dest) if row.id == body.before_id), None)
        if found is not None:
            index = found
    dest.insert(index, task)
    task.column = body.column
    for rows in grouped.values():
        _reindex(rows)
    db.commit()
    return _tasks(db)


@router.delete("/tasks/{task_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_task(task_id: int, _: User = Depends(get_current_admin), db: Session = Depends(get_db)):
    task = db.get(AdminTask, task_id)
    if task is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Задача не найдена")
    column = task.column
    db.delete(task)
    db.flush()
    _reindex(_column_rows(db, column))
    db.commit()
