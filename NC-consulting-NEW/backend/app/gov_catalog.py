"""Каталог госпрограмм в БД. Парсер Damu — только по кнопке «Обновить» в админке."""

from __future__ import annotations

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.gov_programs import PAGE, list_programs as fetch_damu_live, parse_programs, _download, _enrich
from app.models import GovProgram

# Для программ субсидии Damu без явной ставки предпринимателя — клиент платит 12,6%.
DEFAULT_SUBSIDY_CLIENT_RATE = 12.6


def program_payload(row: GovProgram) -> dict:
    rate = row.client_rate
    return {
        "id": row.id,
        "slug": row.slug,
        "title": row.title,
        "kind": row.kind,
        "kind_label": row.kind_label,
        "url": row.url,
        # rate — то, что подставляется в «Ставка для клиента» при выборе программы.
        "rate": float(rate) if rate is not None else None,
        "client_rate": float(rate) if rate is not None else None,
        "amount": row.amount or "",
        "detail": row.detail or "",
        "purpose": row.purpose or "",
        "term_min_months": row.term_min_months,
        "term_max_months": row.term_max_months,
        "term_kind": row.term_kind or "",
        "logic": row.logic or "",
        "allows_investment": bool(row.allows_investment),
        "allows_wc": bool(row.allows_wc),
        "grace_max_months": row.grace_max_months,
        "is_active": bool(row.is_active),
        "sort_order": int(row.sort_order or 0),
        "source": row.source or "manual",
    }


def list_catalog(db: Session, *, active_only: bool = True) -> dict:
    query = select(GovProgram).order_by(GovProgram.sort_order, GovProgram.title, GovProgram.id)
    if active_only:
        query = query.where(GovProgram.is_active.is_(True))
    rows = list(db.scalars(query))
    return {
        "source": PAGE,
        "programs": [program_payload(row) for row in rows],
        "stale": False,
        "from_db": True,
    }


def _resolve_client_rate(parsed: dict) -> float | None:
    if parsed.get("rate") is not None:
        return float(parsed["rate"])
    if parsed.get("kind") == "subsidy":
        return DEFAULT_SUBSIDY_CLIENT_RATE
    return None


def upsert_from_damu(db: Session, parsed: list[dict]) -> dict:
    """Слить карточки Damu в БД. Ручные правки client_rate/logic не затираем, если уже заданы админом."""
    existing = {row.slug: row for row in db.scalars(select(GovProgram)).all()}
    created = 0
    updated = 0
    for index, item in enumerate(parsed):
        slug = item.get("slug") or ""
        if not slug:
            continue
        row = existing.get(slug)
        client_rate = _resolve_client_rate(item)
        if row is None:
            row = GovProgram(
                slug=slug,
                title=item.get("title") or slug,
                kind=item.get("kind") or "subsidy",
                kind_label=item.get("kind_label") or "Программа",
                url=item.get("url") or "",
                client_rate=client_rate,
                amount=(item.get("amount") or "")[:120],
                detail=(item.get("detail") or "")[:300],
                purpose=(item.get("purpose") or "")[:240],
                term_min_months=item.get("term_min_months"),
                term_max_months=item.get("term_max_months"),
                term_kind=item.get("term_kind") or "",
                logic=(item.get("logic") or "")[:800],
                allows_investment=bool(item.get("allows_investment", True)),
                allows_wc=bool(item.get("allows_wc", True)),
                grace_max_months=item.get("grace_max_months"),
                is_active=True,
                sort_order=index,
                source="damu",
            )
            db.add(row)
            created += 1
            continue
        # Обновить поля с сайта, но сохранить client_rate если админ уже выставил.
        row.title = item.get("title") or row.title
        row.kind = item.get("kind") or row.kind
        row.kind_label = item.get("kind_label") or row.kind_label
        row.url = item.get("url") or row.url
        row.amount = (item.get("amount") or "")[:120]
        row.detail = (item.get("detail") or "")[:300]
        row.purpose = (item.get("purpose") or "")[:240]
        row.term_min_months = item.get("term_min_months")
        row.term_max_months = item.get("term_max_months")
        row.term_kind = item.get("term_kind") or row.term_kind
        if item.get("logic"):
            row.logic = str(item["logic"])[:800]
        row.allows_investment = bool(item.get("allows_investment", True))
        row.allows_wc = bool(item.get("allows_wc", True))
        row.grace_max_months = item.get("grace_max_months")
        row.sort_order = index
        row.source = "damu" if row.source != "manual" else row.source
        if row.client_rate is None and client_rate is not None:
            row.client_rate = client_rate
        # Для субсидий без ставки — держим 12,6 по умолчанию Damu.
        if row.client_rate is None and row.kind == "subsidy":
            row.client_rate = DEFAULT_SUBSIDY_CLIENT_RATE
        updated += 1
    # Гарантия для Іскер аймақ: ставка клиента 12,6, если ещё пусто или ошибочно не задана.
    isker = existing.get("isker_aymak") or db.scalars(select(GovProgram).where(GovProgram.slug == "isker_aymak")).first()
    if isker is not None and (isker.client_rate is None or float(isker.client_rate) <= 0):
        isker.client_rate = DEFAULT_SUBSIDY_CLIENT_RATE
    db.commit()
    return {"created": created, "updated": updated, "total": created + updated}


def refresh_from_damu(db: Session) -> dict:
    """Скачать свежий список с damu.kz и слить в БД."""
    # Живой парсер (с обогащением страниц) — может занять время.
    live = fetch_damu_live()
    programs = live.get("programs") or []
    if not programs:
        try:
            programs = parse_programs(_download())
            from concurrent.futures import ThreadPoolExecutor

            with ThreadPoolExecutor(max_workers=6) as pool:
                programs = list(pool.map(_enrich, programs))
        except Exception as exc:
            return {"ok": False, "error": str(exc), "created": 0, "updated": 0, "total": 0}
    stats = upsert_from_damu(db, programs)
    return {"ok": True, "error": "", **stats, "count": len(list_catalog(db, active_only=False)["programs"])}


def ensure_gov_catalog(db: Session) -> None:
    """Если каталог пуст — базовая «Іскер аймақ» с 12,6%. Полный список — кнопка «Обновить с Damu» в админке."""
    count = db.scalars(select(GovProgram.id).limit(1)).first()
    if count is None:
        db.add(
            GovProgram(
                slug="isker_aymak",
                title="Іскер аймақ",
                kind="subsidy",
                kind_label="Субсидия",
                url="https://damu.kz/ru/programmi/subsidy/isker_aymak",
                client_rate=DEFAULT_SUBSIDY_CLIENT_RATE,
                amount="200 млн теңге",
                detail="40% от номинальной ставки вознаграждения",
                purpose="Инвестиции; ПОС",
                term_max_months=36,
                term_kind="subsidy",
                allows_investment=True,
                allows_wc=True,
                source="manual",
                logic=(
                    "«Іскер аймақ» снижает банковскую ставку. Ставка для клиента по умолчанию 12,6%. "
                    "Срок субсидии до 36 мес. Субсидия Damu = ставка банка − ставка клиента. "
                    "Полный каталог подтягивается кнопкой «Обновить с Damu» в админке."
                ),
            )
        )
        db.commit()
    isker = db.scalars(select(GovProgram).where(GovProgram.slug == "isker_aymak")).first()
    if isker is not None and (isker.client_rate is None or float(isker.client_rate) <= 0):
        isker.client_rate = DEFAULT_SUBSIDY_CLIENT_RATE
    for row in db.scalars(select(GovProgram).where(GovProgram.kind == "subsidy", GovProgram.client_rate.is_(None))).all():
        row.client_rate = DEFAULT_SUBSIDY_CLIENT_RATE
    db.commit()
