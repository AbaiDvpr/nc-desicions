from datetime import datetime, timedelta, timezone

import jwt
from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.config import settings
from app.models import Invoice, LedgerEntry, RefillRequest, Tariff, User
from app.tariffs import assign_tariff
from app.ioka import PAID, IokaError, create_order, order_status
from app.telegram import notify_payment, notify_refill
from app.wooppay import DONE, WooppayError, create_invoice, login, operation_status


def _token(invoice_id: int, user_id: int) -> str:
    payload = {
        "invoice_id": invoice_id,
        "user_id": user_id,
        "purpose": "wooppay",
        "exp": datetime.now(timezone.utc) + timedelta(days=14),
    }
    return jwt.encode(payload, settings.jwt_secret, algorithm=settings.jwt_algorithm)


def read_token(token: str) -> tuple[int, int]:
    try:
        data = jwt.decode(token, settings.jwt_secret, algorithms=[settings.jwt_algorithm])
    except jwt.PyJWTError as exc:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Счёт не найден") from exc
    if data.get("purpose") != "wooppay":
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Счёт не найден")
    return int(data["invoice_id"]), int(data["user_id"])


def settle_invoice(db: Session, invoice: Invoice) -> Invoice:
    locked = db.scalar(select(Invoice).where(Invoice.id == invoice.id).with_for_update())
    if locked is None or locked.paid_at is not None:
        return locked or invoice

    user = db.get(User, locked.user_id)
    if user is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Пользователь не найден")

    user.balance += locked.amount
    note = locked.description.strip() or "Пополнение счёта"
    db.add(LedgerEntry(user_id=user.id, amount=locked.amount, comment=note))

    if locked.tariff_id:
        tariff = db.get(Tariff, locked.tariff_id)
        if tariff is not None:
            assign_tariff(db, user, tariff, charge=True)

    locked.paid_at = datetime.now(timezone.utc)
    db.commit()
    db.refresh(locked)
    notify_payment(user, locked.amount, locked.service, locked.provider)
    return locked


def start_wooppay(
    db: Session,
    user: User,
    *,
    amount: int,
    email: str,
    phone: str,
    description: str,
    service: str,
    tariff_id: int | None,
) -> str:
    if amount <= 0:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Сумма должна быть больше нуля")

    invoice = Invoice(
        user_id=user.id,
        tariff_id=tariff_id,
        amount=amount,
        email=email,
        phone=phone.strip(),
        service=service[:200],
        description=(description or service)[:500],
        provider="wooppay",
    )
    db.add(invoice)
    db.flush()

    token = _token(invoice.id, user.id)
    back_url = f"{settings.frontend_url.rstrip('/')}/cabinet/payment/return?invoice={invoice.id}"
    request_url = f"{settings.api_public_url.rstrip('/')}/api/payments/wooppay/{token}"
    death = (datetime.now(timezone.utc) + timedelta(days=7)).strftime("%Y-%m-%d %H:%M:%S")

    try:
        session = login()
        operation_id, url = create_invoice(
            session=session,
            reference_id=str(invoice.id),
            back_url=back_url,
            request_url=request_url,
            amount=amount,
            death_date=death,
            description=invoice.description or service,
            email=email,
            phone=phone.strip(),
        )
    except WooppayError as exc:
        db.rollback()
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=str(exc)) from exc

    invoice.operation_id = operation_id
    db.commit()
    return url


def start_ioka(
    db: Session,
    user: User,
    *,
    amount: int,
    email: str,
    phone: str,
    description: str,
    service: str,
) -> str:
    if amount <= 0:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Сумма должна быть больше нуля")

    invoice = Invoice(
        user_id=user.id,
        tariff_id=None,
        amount=amount,
        email=email,
        phone=phone.strip(),
        service=service[:200],
        description=(description or service)[:500],
        provider="ioka",
    )
    db.add(invoice)
    db.flush()
    back_url = f"{settings.frontend_url.rstrip('/')}/cabinet/payment/return?invoice={invoice.id}"
    try:
        order_id, url = create_order(
            amount=amount,
            description=invoice.description or service,
            external_id=str(invoice.id),
            back_url=back_url,
        )
    except IokaError as exc:
        db.rollback()
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=str(exc)) from exc

    invoice.external_id = order_id
    db.commit()
    return url


def sync_invoice(db: Session, invoice: Invoice) -> Invoice:
    if invoice.paid_at is not None:
        return invoice
    if invoice.provider == "ioka":
        if not invoice.external_id:
            return invoice
        if order_status(invoice.external_id) in PAID:
            return settle_invoice(db, invoice)
        return invoice
    if not invoice.operation_id:
        return invoice
    try:
        session = login()
    except WooppayError:
        return invoice
    if operation_status(session, invoice.operation_id) == DONE:
        return settle_invoice(db, invoice)
    return invoice


def create_refill(db: Session, user: User, *, full_name: str, phone: str, amount: int, comment: str) -> RefillRequest:
    request = RefillRequest(
        user_id=user.id,
        full_name=full_name.strip(),
        phone=phone.strip(),
        amount=amount,
        comment=comment.strip(),
    )
    db.add(request)
    db.commit()
    db.refresh(request)
    notify_refill(user, request.full_name, request.phone, request.amount, request.comment)
    return request
