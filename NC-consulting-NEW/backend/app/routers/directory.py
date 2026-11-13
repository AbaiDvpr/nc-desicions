from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.bp_profile import clean_company, company_defaults
from app.database import get_db
from app.security import get_current_user
from app.models import Company, CompanyRequisite, Counterparty, User
from app.schemas import (
    CabinetCompanyIn,
    CabinetCompanyOut,
    CounterpartyIn,
    CounterpartyOut,
    RequisiteIn,
    RequisiteOut,
)
from app.tariffs import ensure_can_create_company

router = APIRouter(prefix="/api", tags=["directory"])

BANKS = {
    "Halyk Bank": "HSBKKZKX",
    "Kaspi Bank": "CASPKZKA",
    "Bank CenterCredit": "KCJBKZKX",
    "ForteBank": "IRTYKZKA",
    "Jusan Bank": "TSESKZKA",
    "Freedom Bank": "KSNVKZKA",
    "Eurasian Bank": "EURIKZKA",
    "Bereke Bank": "BRKEKZKA",
}


def _company_or_404(db: Session, user: User, company_id: int) -> Company:
    company = db.scalar(select(Company).where(Company.id == company_id, Company.user_id == user.id))
    if company is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Компания не найдена")
    return company


def _requisite_or_404(db: Session, user: User, company_id: int, requisite_id: int) -> CompanyRequisite:
    company = _company_or_404(db, user, company_id)
    row = db.scalar(
        select(CompanyRequisite).where(
            CompanyRequisite.id == requisite_id,
            CompanyRequisite.company_id == company.id,
        )
    )
    if row is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Реквизит не найден")
    return row


def _requisite_data(body: RequisiteIn) -> dict:
    data = body.model_dump()
    bank = data["bank_name"].strip()
    if bank:
        bik = BANKS.get(bank)
        if bik is None:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Выберите банк из списка")
        data["bik"] = bik
    else:
        data["bik"] = ""
    data["bank_name"] = bank
    bin_value = data["bin"].strip()
    if bin_value and (len(bin_value) != 12 or not bin_value.isdigit()):
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="БИН или ИИН — 12 цифр")
    data["bin"] = bin_value
    return data


def _party_or_404(db: Session, user: User, party_id: int) -> Counterparty:
    party = db.scalar(
        select(Counterparty).where(Counterparty.id == party_id, Counterparty.user_id == user.id)
    )
    if party is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Контрагент не найден")
    return party


@router.get("/banks")
def banks(_: User = Depends(get_current_user)) -> list[dict]:
    return [{"name": name, "bik": bik} for name, bik in BANKS.items()]


@router.get("/companies", response_model=list[CabinetCompanyOut])
def list_companies(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    rows = db.scalars(
        select(Company).where(Company.user_id == user.id).order_by(Company.created_at.desc())
    ).all()
    return rows


@router.post("/companies", response_model=CabinetCompanyOut, status_code=status.HTTP_201_CREATED)
def create_company(
    body: CabinetCompanyIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    ensure_can_create_company(db, user)
    company = Company(user_id=user.id, **body.model_dump())
    db.add(company)
    db.commit()
    db.refresh(company)
    return company


@router.get("/companies/{company_id}", response_model=CabinetCompanyOut)
def get_company(company_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    return _company_or_404(db, user, company_id)


@router.put("/companies/{company_id}", response_model=CabinetCompanyOut)
def update_company(
    company_id: int,
    body: CabinetCompanyIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    company = _company_or_404(db, user, company_id)
    for key, value in body.model_dump().items():
        setattr(company, key, value)
    db.commit()
    db.refresh(company)
    return company


@router.get("/companies/{company_id}/bp-profile")
def get_company_bp(company_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    company = _company_or_404(db, user, company_id)
    return {"data": clean_company(company.bp_data), "defaults": company_defaults(db, company)}


@router.put("/companies/{company_id}/bp-profile")
def put_company_bp(
    company_id: int,
    body: dict,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    company = _company_or_404(db, user, company_id)
    company.bp_data = clean_company(body)
    db.commit()
    return {"data": company.bp_data, "defaults": company_defaults(db, company)}


@router.get("/companies/{company_id}/requisites", response_model=list[RequisiteOut])
def list_requisites(company_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    company = _company_or_404(db, user, company_id)
    return db.scalars(
        select(CompanyRequisite)
        .where(CompanyRequisite.company_id == company.id)
        .order_by(CompanyRequisite.id)
    ).all()


@router.post(
    "/companies/{company_id}/requisites",
    response_model=RequisiteOut,
    status_code=status.HTTP_201_CREATED,
)
def create_requisite(
    company_id: int,
    body: RequisiteIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    company = _company_or_404(db, user, company_id)
    used = db.scalar(
        select(func.count()).select_from(CompanyRequisite).where(CompanyRequisite.company_id == company.id)
    ) or 0
    if used >= 5:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Можно сохранить не больше 5 реквизитов")
    row = CompanyRequisite(company_id=company.id, **_requisite_data(body))
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.put("/companies/{company_id}/requisites/{requisite_id}", response_model=RequisiteOut)
def update_requisite(
    company_id: int,
    requisite_id: int,
    body: RequisiteIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    row = _requisite_or_404(db, user, company_id, requisite_id)
    for key, value in _requisite_data(body).items():
        setattr(row, key, value)
    db.commit()
    db.refresh(row)
    return row


@router.delete("/companies/{company_id}/requisites/{requisite_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_requisite(
    company_id: int,
    requisite_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    row = _requisite_or_404(db, user, company_id, requisite_id)
    db.delete(row)
    db.commit()


@router.get("/counterparties", response_model=list[CounterpartyOut])
def list_counterparties(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    rows = db.scalars(
        select(Counterparty)
        .where(Counterparty.user_id == user.id)
        .order_by(Counterparty.created_at.desc())
    ).all()
    return rows


@router.get("/counterparties/{party_id}", response_model=CounterpartyOut)
def get_counterparty(party_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    return _party_or_404(db, user, party_id)


@router.post("/counterparties", response_model=CounterpartyOut, status_code=status.HTTP_201_CREATED)
def create_counterparty(
    body: CounterpartyIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    data = _party_data(body)
    party = Counterparty(user_id=user.id, **data)
    db.add(party)
    db.commit()
    db.refresh(party)
    return party


@router.put("/counterparties/{party_id}", response_model=CounterpartyOut)
def update_counterparty(
    party_id: int,
    body: CounterpartyIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    party = _party_or_404(db, user, party_id)
    for key, value in _party_data(body).items():
        setattr(party, key, value)
    db.commit()
    db.refresh(party)
    return party


@router.delete("/counterparties/{party_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_counterparty(
    party_id: int,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    party = _party_or_404(db, user, party_id)
    db.delete(party)
    db.commit()


def _party_data(body: CounterpartyIn) -> dict:
    bik = BANKS.get(body.bank_name)
    if bik is None:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Выберите банк из списка")
    data = body.model_dump()
    data["bik"] = bik
    data["bin"] = body.bin.strip()
    return data
