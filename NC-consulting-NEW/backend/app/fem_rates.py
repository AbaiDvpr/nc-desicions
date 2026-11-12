"""Справочник ставок FEM: дефолты админа + то, что клиент может поменять в анкете.

Проценты в API и анкете — как у премии за риск: 4 значит 4%.
В Excel уходит доля (0.04).
МЗП — сумма в тенге по годам (не процент).
Упрощёнка и ОУР — отдельно для ИП и ТОО.
"""

from __future__ import annotations

from sqlalchemy.orm import Session

from app.models import FemRateSetting

# Базовые ставки НК РК с 01.01.2026 (nalogikz). Админ может переписать в БД.
DEFAULT_RATES = {
    "simplified_ip_rate": 4.0,
    "simplified_too_rate": 4.0,
    "cit_ip_rate": 10.0,
    "cit_too_rate": 20.0,
    "vat_rate": 16.0,
    "opv_rate": 10.0,
    "vosms_rate": 2.0,
    "ipn_rate": 10.0,
    "ipn_rate_high": 15.0,
    "so_rate": 5.0,
    "opvr_rate": 3.5,
    "opvr_rate_2027": 4.5,
    "opvr_rate_2028": 5.0,
    "osms_rate": 3.0,
    "social_tax_rate": 6.0,
    "nbk_bank_spread": 4.0,
    # Пороги НК РК 2026 в МРП и сам МРП в тенге (не проценты).
    "mrp": 4325.0,
    "vat_threshold_mrp": 10000.0,
    "simplified_limit_mrp": 600000.0,
}

# Старое единое поле — зеркало упрощёнки ИП для совместимости.
LEGACY_RATE_KEYS = ("simplified_rate",)

DEFAULT_MZP_SCHEDULE = "2022:60000,2023:70000,2024:85000,2025:85000,2026:85000"


def parse_mzp_schedule(text: str | None) -> dict[int, float]:
    schedule: dict[int, float] = {}
    for part in str(text or "").split(","):
        piece = part.strip()
        if ":" not in piece:
            continue
        year_text, amount_text = piece.split(":", 1)
        try:
            schedule[int(year_text.strip())] = float(amount_text.strip().replace(" ", "").replace(",", "."))
        except ValueError:
            continue
    return schedule


def format_mzp_schedule(schedule: dict[int, float]) -> str:
    parts = []
    for year in sorted(schedule):
        amount = schedule[year]
        parts.append(f"{year}:{int(amount) if float(amount).is_integer() else amount}")
    return ",".join(parts)


def mzp_rows_payload(schedule: dict[int, float]) -> list[dict]:
    return [{"year": year, "amount": schedule[year]} for year in sorted(schedule)]


def ensure_fem_rates(db: Session) -> FemRateSetting:
    row = db.get(FemRateSetting, 1)
    if row is None:
        row = FemRateSetting(
            id=1,
            **DEFAULT_RATES,
            simplified_rate=DEFAULT_RATES["simplified_ip_rate"],
            mzp_schedule=DEFAULT_MZP_SCHEDULE,
        )
        db.add(row)
        db.commit()
        db.refresh(row)
        return row

    changed = False
    legacy = getattr(row, "simplified_rate", None)
    if getattr(row, "simplified_ip_rate", None) is None:
        row.simplified_ip_rate = float(legacy if legacy is not None else DEFAULT_RATES["simplified_ip_rate"])
        changed = True
    if getattr(row, "simplified_too_rate", None) is None:
        row.simplified_too_rate = float(legacy if legacy is not None else DEFAULT_RATES["simplified_too_rate"])
        changed = True
    if not getattr(row, "mzp_schedule", None):
        row.mzp_schedule = DEFAULT_MZP_SCHEDULE
        changed = True
    for key, default in DEFAULT_RATES.items():
        if getattr(row, key, None) is None:
            setattr(row, key, float(default))
            changed = True
    # Держим legacy-поле в синхроне с ИП.
    if row.simplified_rate is None or float(row.simplified_rate) != float(row.simplified_ip_rate):
        row.simplified_rate = float(row.simplified_ip_rate)
        changed = True
    if changed:
        db.commit()
        db.refresh(row)
    return row


def rates_payload(row: FemRateSetting | None = None) -> dict:
    source = row
    data = dict(DEFAULT_RATES)
    schedule_text = DEFAULT_MZP_SCHEDULE
    if source is not None:
        legacy = getattr(source, "simplified_rate", None)
        for key in DEFAULT_RATES:
            value = getattr(source, key, None)
            if value is not None:
                data[key] = float(value)
            elif key.startswith("simplified_") and legacy is not None:
                data[key] = float(legacy)
        if getattr(source, "mzp_schedule", None):
            schedule_text = source.mzp_schedule
    schedule = parse_mzp_schedule(schedule_text) or parse_mzp_schedule(DEFAULT_MZP_SCHEDULE)
    return {
        **data,
        # Совместимость со старым кабинетом/кодом.
        "simplified_rate": data["simplified_ip_rate"],
        "mzp_schedule": format_mzp_schedule(schedule),
        "mzp_rows": mzp_rows_payload(schedule),
        "label_nbk": f"НБ РК + {data['nbk_bank_spread']:g}%",
        "source_note": "Дефолты админа отдельно для ИП и ТОО. В анкете клиент может заменить ставку для своей модели.",
    }


def load_rates() -> dict:
    """Справочник админки для Excel; при недоступной БД — дефолты."""
    try:
        from app.database import SessionLocal

        with SessionLocal() as db:
            return rates_payload(db.get(FemRateSetting, 1))
    except Exception:
        return rates_payload(None)


def load_mzp_schedule() -> dict[int, float]:
    """МЗП для Excel: сначала БД админки, иначе .env / дефолт."""
    try:
        from app.database import SessionLocal

        with SessionLocal() as db:
            row = db.get(FemRateSetting, 1)
            if row is not None and getattr(row, "mzp_schedule", None):
                parsed = parse_mzp_schedule(row.mzp_schedule)
                if parsed:
                    return parsed
    except Exception:
        pass
    try:
        from app.config import settings

        parsed = parse_mzp_schedule(settings.fem_mzp)
        if parsed:
            return parsed
    except Exception:
        pass
    return parse_mzp_schedule(DEFAULT_MZP_SCHEDULE)


def form_rate(form: dict, key: str, fallback_percent: float) -> float:
    """Доля для Excel: из анкеты (проценты) или fallback.

    Пустое и 0 (после Num→0 для старых черновиков) → дефолт справочника.
    """
    raw = form.get(key)
    if raw is None or raw == "":
        return float(fallback_percent) / 100.0
    try:
        number = float(raw)
    except (TypeError, ValueError):
        return float(fallback_percent) / 100.0
    if number > 1:
        number = number / 100.0
    if number == 0:
        return float(fallback_percent) / 100.0
    return number


def simplified_default(defaults: dict, legal: int) -> float:
    if int(legal) == 2:
        return float(defaults.get("simplified_too_rate") or defaults.get("simplified_rate") or 4.0)
    return float(defaults.get("simplified_ip_rate") or defaults.get("simplified_rate") or 4.0)


def resolve_tax_rate(form: dict, defaults: dict | None = None) -> float:
    """Ставка налога режима из анкеты или справочника (с учётом ИП/ТОО)."""
    defaults = defaults or DEFAULT_RATES
    legal = int(form.get("legal_form") or 1)
    regime = int(form.get("tax_regime") or 4)
    if regime == 4:
        return form_rate(form, "tax_simplified_rate", simplified_default(defaults, legal))
    if legal == 2:
        return form_rate(form, "tax_cit_rate", defaults["cit_too_rate"])
    return form_rate(form, "tax_cit_rate", defaults["cit_ip_rate"])
