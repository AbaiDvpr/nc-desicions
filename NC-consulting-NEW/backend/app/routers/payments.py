from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.database import get_db
from app.models import Invoice, Tariff, User
from app.payments import create_refill, read_token, settle_invoice, start_ioka, start_wooppay, sync_invoice
from app.schemas import InvoiceStatusOut, PayUrlOut, RefillIn, TariffBuyIn, TopupIn
from app.security import get_current_user

router = APIRouter(prefix="/api/payments", tags=["payments"])


def _status(invoice: Invoice) -> InvoiceStatusOut:
    return InvoiceStatusOut(
        id=invoice.id,
        paid=invoice.paid_at is not None,
        amount=invoice.amount,
        service=invoice.service,
    )


def _owned(db: Session, user: User, invoice_id: int) -> Invoice:
    invoice = db.get(Invoice, invoice_id)
    if invoice is None or invoice.user_id != user.id:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Счёт не найден")
    return invoice


@router.post("/tariffs/{tariff_id}/wooppay", response_model=PayUrlOut)
def pay_tariff_wooppay(
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
    if tariff.price <= 0:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Этот тариф оплачивается без Wooppay")
    url = start_wooppay(
        db,
        user,
        amount=tariff.price,
        email=str(data.email),
        phone=data.phone,
        description=data.comment or f"Тариф {tariff.name}",
        service=tariff.name,
        tariff_id=tariff.id,
    )
    return PayUrlOut(url=url)


@router.post("/topup/wooppay", response_model=PayUrlOut)
def topup_wooppay(
    data: TopupIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    url = start_wooppay(
        db,
        user,
        amount=data.amount,
        email=str(data.email),
        phone=data.phone,
        description="Пополнение счёта",
        service="Пополнение счёта",
        tariff_id=None,
    )
    return PayUrlOut(url=url)


@router.post("/topup/ioka", response_model=PayUrlOut)
def topup_ioka(
    data: TopupIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    url = start_ioka(
        db,
        user,
        amount=data.amount,
        email=str(data.email),
        phone=data.phone,
        description="Пополнение счёта",
        service="Пополнение счёта",
    )
    return PayUrlOut(url=url)


@router.post("/topup/request", status_code=status.HTTP_201_CREATED)
def topup_request(
    data: RefillIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    create_refill(
        db,
        user,
        full_name=data.full_name,
        phone=data.phone,
        amount=data.amount,
        comment=data.comment,
    )
    return {"success": "Заявка отправлена. Счёт на Kaspi выставят на указанный номер."}


@router.get("/invoices/{invoice_id}", response_model=InvoiceStatusOut)
def invoice_status(
    invoice_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    return _status(_owned(db, user, invoice_id))


@router.post("/invoices/{invoice_id}/sync", response_model=InvoiceStatusOut)
def invoice_sync(
    invoice_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    invoice = sync_invoice(db, _owned(db, user, invoice_id))
    return _status(invoice)


@router.get("/wooppay/{token}")
def wooppay_callback(token: str, db: Session = Depends(get_db)):
    invoice_id, user_id = read_token(token)
    invoice = db.get(Invoice, invoice_id)
    if invoice is None or invoice.user_id != user_id:
        return {"data": 0}
    if invoice.paid_at is None:
        settle_invoice(db, invoice)
    return {"data": 1}
