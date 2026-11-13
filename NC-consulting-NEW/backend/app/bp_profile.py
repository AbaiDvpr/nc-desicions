"""Сведения о компании для банковского бизнес-плана (#006). В анкете финмодели их нет.

Постоянные сведения хранятся в карточке компании и подходят к любому проекту.
У финмодели остаются только выбранная компания и то, что относится к этому проекту.
"""

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import BpProfile, Company, CompanyRequisite, FinModel

TEXT_FIELDS = (
    ("company_name", "Полное наименование", 200),
    ("bin", "БИН / ИИН", 20),
    ("registered", "Дата первичной регистрации", 40),
    ("registration_doc", "Учредительные документы, перерегистрация", 600),
    ("address", "Юридический и фактический адрес", 300),
    ("project_address", "Адрес объекта проекта", 300),
    ("owners", "Собственники и доли", 600),
    ("director", "Первый руководитель", 200),
    ("activity", "Основной вид деятельности (ОКЭД)", 300),
    ("about", "Описание деятельности компании", 3000),
    ("experience", "Опыт в подобных проектах", 3000),
    ("staff_now", "Численность сейчас", 300),
    ("reputation", "Деловая репутация", 2000),
    ("credit_history", "Кредитная история", 2000),
    ("licenses", "Лицензии и разрешения", 1500),
    ("contracts", "Контракты и оферты", 1500),
    ("suppliers", "Поставщики", 1500),
)
LISTS = {
    "managers": (("name", 120), ("position", 120), ("education", 300), ("experience", 300)),
    "turnover": (("period", 80), ("amount", 40)),
    "collateral": (("name", 300), ("owner", 160), ("value", 40)),
    "competitors": (("name", 160), ("format", 160), ("strong", 300), ("weak", 300)),
}
LIST_LIMIT = 20
LABELS = dict((key, label) for key, label, _limit in TEXT_FIELDS)

PROJECT_KEYS = frozenset({"project_address", "contracts", "collateral"})
COMPANY_KEYS = frozenset(key for key, _label, _limit in TEXT_FIELDS if key not in PROJECT_KEYS) | (
    frozenset(LISTS) - PROJECT_KEYS
)
LEGAL_FORMS = {"1": "ИП", "2": "ТОО"}


def _clip(value, limit: int) -> str:
    text = str(value or "").replace("\r\n", "\n").strip()
    text = "".join(ch if ch in "\n\t" or ord(ch) >= 32 else " " for ch in text)
    return text[:limit]


def clean(data: dict | None, keys: frozenset | None = None) -> dict:
    data = data or {}
    result = {
        key: _clip(data.get(key), limit) for key, _label, limit in TEXT_FIELDS if keys is None or key in keys
    }
    for key, columns in LISTS.items():
        if keys is not None and key not in keys:
            continue
        rows = []
        for row in (data.get(key) or [])[:LIST_LIMIT]:
            if not isinstance(row, dict):
                continue
            item = {name: _clip(row.get(name), limit) for name, limit in columns}
            if any(item.values()):
                rows.append(item)
        result[key] = rows
    return result


def clean_company(data: dict | None) -> dict:
    return clean(data, COMPANY_KEYS)


def company_defaults(db: Session, company: Company) -> dict:
    """Что уже известно из профиля и реквизитов компании: подставляется, если поле не заполнено."""
    requisite = db.scalar(
        select(CompanyRequisite).where(CompanyRequisite.company_id == company.id).order_by(CompanyRequisite.id)
    )
    name = company.name.strip()
    form = LEGAL_FORMS.get(company.legal_form, "")
    if form and not any(mark in name for mark in "«\""):
        name = f"{form} «{name}»"
    defaults = {"company_name": name, "about": (company.description or "").strip()}
    if requisite is not None:
        if requisite.name.strip():
            defaults["company_name"] = requisite.name.strip()
        legal, actual = requisite.legal_address.strip(), requisite.actual_address.strip()
        if legal and actual and legal != actual:
            defaults["address"] = f"юридический: {legal}; фактический: {actual}"
        else:
            defaults["address"] = legal or actual
        defaults["bin"] = requisite.bin.strip()
        defaults["director"] = requisite.director.strip()
    return clean_company(defaults)


def company_profile(db: Session, company: Company) -> dict:
    data = clean_company(company.bp_data)
    for key, value in company_defaults(db, company).items():
        if not data.get(key):
            data[key] = value
    return data


def _companies(db: Session, user_id: int) -> list[Company]:
    return list(db.scalars(select(Company).where(Company.user_id == user_id).order_by(Company.created_at.desc())))


def _project(db: Session, model_id: int) -> tuple[BpProfile | None, dict]:
    row = db.scalar(select(BpProfile).where(BpProfile.fin_model_id == model_id))
    return row, dict(row.data) if row else {}


def _company_for(db: Session, user_id: int, stored: dict) -> Company | None:
    """Выбранная компания, а если выбора не было и компания одна — она."""
    companies = _companies(db, user_id)
    chosen = stored.get("company_id")
    if chosen:
        return next((company for company in companies if company.id == chosen), None)
    if chosen is None and len(companies) == 1:
        return companies[0]
    return None


def get_profile(db: Session, model_id: int) -> dict:
    model = db.get(FinModel, model_id)
    _row, stored = _project(db, model_id)
    profile = clean(stored)
    company = _company_for(db, model.user_id, stored) if model else None
    if company is not None:
        for key, value in company_profile(db, company).items():
            if value:
                profile[key] = value
    return profile


def profile_state(db: Session, user_id: int, model_id: int) -> dict:
    _row, stored = _project(db, model_id)
    company = _company_for(db, user_id, stored)
    return {
        "company_id": company.id if company else None,
        "companies": [{"id": row.id, "name": row.name} for row in _companies(db, user_id)],
        "company": company_profile(db, company) if company else clean_company(None),
        "project": clean(stored, PROJECT_KEYS),
    }


def save_profile(db: Session, user_id: int, model_id: int, data: dict) -> dict:
    row, _stored = _project(db, model_id)
    stored = clean(data.get("project"), PROJECT_KEYS)
    chosen = data.get("company_id")
    if chosen is None or chosen == 0:
        stored["company_id"] = 0 if chosen == 0 else None
    else:
        owned = db.scalar(select(Company.id).where(Company.id == int(chosen), Company.user_id == user_id))
        stored["company_id"] = owned
    if row is None:
        db.add(BpProfile(user_id=user_id, fin_model_id=model_id, data=stored))
    else:
        row.data = stored
    db.commit()
    return profile_state(db, user_id, model_id)


def profile_case(profile: dict | None) -> str:
    """Строки для кейса модели. Пустые поля не попадают."""
    profile = clean(profile)
    lines = []
    for key, label, _limit in TEXT_FIELDS:
        if profile[key]:
            lines.append(f"{label}: {profile[key]}")
    for row in profile["managers"]:
        lines.append(
            "Менеджмент: "
            + ", ".join(part for part in (row["name"], row["position"], row["education"], row["experience"]) if part)
        )
    for row in profile["turnover"]:
        lines.append(f"Фактический оборот: {row['period']} — {row['amount']}")
    for row in profile["collateral"]:
        lines.append("Залог: " + ", ".join(part for part in (row["name"], row["owner"], row["value"]) if part))
    for row in profile["competitors"]:
        lines.append("Конкурент: " + ", ".join(part for part in (row["name"], row["format"], row["strong"], row["weak"]) if part))
    if not lines:
        return ""
    return "Сведения о компании от клиента:\n" + "\n".join(f"- {line}" for line in lines)
