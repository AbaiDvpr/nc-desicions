"""Полный бизнес-план из данных модели: до 20 разделов, без выдуманных цифр."""

from datetime import date
from io import BytesIO
import re

from PIL import Image, ImageDraw, ImageFont
from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor
from fpdf import FPDF
from fpdf.enums import XPos, YPos

from app.currencies import MONEY

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
MONTHS_GEN = (
    "",
    "января",
    "февраля",
    "марта",
    "апреля",
    "мая",
    "июня",
    "июля",
    "августа",
    "сентября",
    "октября",
    "ноября",
    "декабря",
)
LEGAL = {1: "ИП", 2: "ТОО"}
FINANCING = {1: "Инвестор", 2: "Кредит", 3: "Грант", 4: "Инвестор и кредит"}
TAX = {1: "общеустановленный режим", 4: "упрощённый режим"}
UNITS = {1: "шт", 2: "кг", 3: "тонн", 4: "усл", 5: "м²", 6: "м³", 7: "л", 8: "м.п.", 9: "км", 10: "чел.", 11: "ед."}
INVEST = (
    ("smr", "Работы СМР и капитальный ремонт", "capex"),
    ("overhaul", "Материалы СМР и капитального ремонта", "capex"),
    ("ready_premises", "Помещения, здания, сооружения", "capex"),
    ("equipment", "Оборудование, техника, мебель", "capex"),
    ("raw", "Товарный запас / сырьё и товары", "wc"),
    ("advertising", "Реклама на запуск", "wc"),
    ("wages", "Заработная плата на запуск", "wc"),
    ("rental", "Аренда на запуск", "wc"),
    ("other", "Прочие расходы на запуск", "wc"),
)
ASSET_GROUPS = (
    ("premises", "Помещения, здания, сооружения"),
    ("equipment", "Оборудование, техника, мебель"),
    ("smr", "Работы СМР и капитальный ремонт"),
    ("overhaul", "Материалы СМР и капитального ремонта"),
    ("inventory", "Товарный запас / оборотный капитал"),
)
MISSING = "В анкете этого нет."
SANS = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
SANS_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
SERIF = "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf"
SERIF_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf"
MOSAIC_HEAD = "DAEEF3"


def _num(value) -> float:
    try:
        return float(value or 0)
    except (TypeError, ValueError):
        return 0.0


def _sign(form: dict) -> str:
    return MONEY.get(str(form.get("currency") or "kzt").strip().lower(), MONEY["kzt"])[1]


def _money(form: dict, value: float) -> str:
    amount = int(round(value))
    sign = _sign(form)
    text = f"{abs(amount):,}".replace(",", " ")
    return f"−{text} {sign}" if amount < 0 else f"{text} {sign}"


def _gov_program(form: dict) -> dict | None:
    if int(form.get("financing") or 0) not in (2, 4):
        return None
    program = form.get("gov_program") or {}
    if not isinstance(program, dict) or not str(program.get("slug") or program.get("title") or "").strip():
        return None
    return program


def _gov_span(form: dict) -> str:
    program = _gov_program(form)
    if not program:
        return ""
    low = program.get("term_min_months")
    high = program.get("term_max_months")
    if low and high:
        return f"{int(low)}–{int(high)} мес."
    if high:
        return f"до {int(high)} мес."
    if low:
        return f"от {int(low)} мес."
    return ""


def _gov_title(form: dict) -> str:
    if int(form.get("financing") or 0) not in (2, 4):
        return ""
    titles = []
    for key in ("gov_program", "gov_guarantee"):
        item = form.get(key) or {}
        if isinstance(item, dict):
            title = str(item.get("title") or "").strip()
            if title:
                titles.append(title)
    return " + ".join(titles)


def _gov_grace(form: dict) -> str:
    program = _gov_program(form)
    if not program:
        return ""
    cap = program.get("grace_max_months")
    if cap:
        return f"до {int(cap)} мес."
    return "каталог не указывает"


def _gov_wc(form: dict) -> str:
    program = _gov_program(form)
    if not program:
        return ""
    if program.get("allows_wc") is False:
        return "отсутствует"
    return "можно"


def _payment_logic(facts: dict) -> str:
    monthly = _pct(_num(facts.get("loan_rate")) / 12)
    grace = int(facts.get("loan_grace") or 0)
    pause = f" Первые {grace} мес. платятся только проценты, тело не гасится." if grace else ""
    if facts.get("loan_annuity"):
        return (
            f"Аннуитет. Каждый месяц проценты — {monthly} от остатка долга. "
            f"После льготы взнос одинаковый: сначала проценты месяца, остальное гасит тело. "
            f"Доля процентов во взносе к концу срока меньше.{pause}"
        )
    return (
        f"Дифференцированный график. Каждый месяц проценты — {monthly} от остатка долга. "
        f"Тело после льготы гасится равными долями, поэтому взнос каждый месяц меньше.{pause}"
    )


def _pct(value) -> str:
    text = f"{_num(value):.10g}".replace(".", ",")
    return f"{text}%"


def _rate(value) -> str:
    if isinstance(value, str) and value.strip():
        return value.strip()
    if not isinstance(value, (int, float)):
        return "—"
    return f"{float(value) * 100:.1f}".replace(".", ",") + "%"


def _years(value) -> str:
    if isinstance(value, str) and value.strip():
        return value.strip()
    if not isinstance(value, (int, float)):
        return "—"
    return f"{float(value):.1f}".replace(".", ",") + " года"


def _plain_number(value) -> str:
    if isinstance(value, str) and value.strip():
        return value.strip()
    if not isinstance(value, (int, float)):
        return "—"
    return f"{float(value):.2f}".replace(".", ",")


def _month(value: str) -> str:
    text = str(value or "")
    if len(text) < 7 or not text[:4].isdigit() or not text[5:7].isdigit():
        return "не указано"
    month = int(text[5:7])
    if not 1 <= month <= 12:
        return "не указано"
    return f"{MONTHS[month]} {text[:4]}"


def _ym(value) -> tuple[int, int] | None:
    text = str(value or "")
    if len(text) < 7 or not text[:4].isdigit() or not text[5:7].isdigit():
        return None
    month = int(text[5:7])
    if not 1 <= month <= 12:
        return None
    return int(text[:4]), month


def _load_percent(value: float) -> float:
    """Как в файле Фэм: число больше 1 — это проценты, иначе доля."""
    return value if value > 1 else value * 100


def _selling_months(start: str, end: str, sales: str, year: int, season: set[int] | None = None) -> int:
    bounds = (_ym(start), _ym(end), _ym(sales))
    if any(item is None for item in bounds):
        return 0
    (start_year, start_month), (end_year, end_month), (sales_year, sales_month) = bounds
    left = max((year, 1), (start_year, start_month), (sales_year, sales_month))
    right = min((year, 12), (end_year, end_month))
    if left > right:
        return 0
    count = 0
    cursor_year, cursor_month = left
    while (cursor_year, cursor_month) <= right:
        if season is None or cursor_month in season:
            count += 1
        cursor_month += 1
        if cursor_month == 13:
            cursor_year += 1
            cursor_month = 1
    return count


def _span(start: str, end: str) -> int | None:
    if not start or not end or len(start) < 7 or len(end) < 7:
        return None
    if not (start[:4].isdigit() and start[5:7].isdigit() and end[:4].isdigit() and end[5:7].isdigit()):
        return None
    return (int(end[:4]) - int(start[:4])) * 12 + (int(end[5:7]) - int(start[5:7])) + 1


def _block(kind: str, *payload) -> tuple:
    return (kind, *payload)


def assemble(
    form: dict,
    template=None,
    answers: dict | None = None,
    results: dict | None = None,
    images: list[dict] | None = None,
    profile: dict | None = None,
) -> dict:
    form = form or {}
    sign = _sign(form)
    monthly = int(form.get("time_param") or 3) != 1
    products = []
    for row in form.get("volume_sales") or []:
        name = str(row.get("name") or "").strip()
        if not name:
            continue
        count = _num(row.get("count"))
        volume = count if monthly else count / 12
        price = _num(row.get("price"))
        season_months = {int(item) for item in (row.get("months") or []) if 1 <= int(item) <= 12}
        products.append(
            {
                "name": name,
                "unit": UNITS.get(int(row.get("units") or 1), "шт"),
                "volume": volume,
                "price": price,
                "revenue": volume * price,
                "season": season_months if row.get("seasonal") and season_months else None,
                "sales_start": str(row.get("sales_start") or ""),
            }
        )
    unit_cost: dict[str, float] = {}
    for row in form.get("variable_costs") or []:
        name = str(row.get("product") or "").strip()
        if name:
            unit_cost[name] = unit_cost.get(name, 0) + _num(row.get("price"))
    for item in products:
        item["unit_cost"] = unit_cost.get(item["name"], 0)
        item["cogs"] = item["unit_cost"] * item["volume"]
        item["margin"] = item["revenue"] - item["cogs"]
    revenue = sum(item["revenue"] for item in products)
    cogs = sum(item["cogs"] for item in products)
    staff = []
    for row in form.get("salary") or []:
        name = str(row.get("name") or "").strip()
        if not name:
            continue
        count = _num(row.get("count"))
        salary = _num(row.get("salary"))
        staff.append(
            {
                "name": name,
                "count": count,
                "salary": salary,
                "fund": count * salary,
                "percent": _num(row.get("percent")),
                "services": [str(item).strip() for item in (row.get("services") or []) if str(item).strip()],
            }
        )
    payroll = sum(item["fund"] for item in staff)
    rent_rows, admin_rows, marketing_rows = [], [], []
    for row in form.get("admin_expenses") or []:
        value = _num(row.get("value"))
        land = (
            bool(row.get("land_tax"))
            or _num(row.get("land_area")) > 0
            or _num(row.get("land_rate")) > 0
            or "земельн" in str(row.get("name") or "").lower()
        )
        if value <= 0 and land:
            value = _num(row.get("land_area")) * _num(row.get("land_rate"))
        if value <= 0:
            continue
        raw = str(row.get("name") or "").strip()
        if land:
            name = raw if len(raw) >= 3 else "Земельный налог"
        else:
            name = raw if len(raw) >= 2 else ("Аренда" if int(row.get("type_expense") or 0) == 2 else "Прочий расход")
        item = {"name": name, "value": value}
        (rent_rows if int(row.get("type_expense") or 0) == 2 else admin_rows).append(item)
    for row in form.get("marketing_expenses") or []:
        value = _num(row.get("value"))
        if value <= 0:
            continue
        raw = str(row.get("name") or "").strip()
        name = raw if len(raw) >= 2 else "Реклама"
        marketing_rows.append({"name": name, "value": value})
    rent = sum(item["value"] for item in rent_rows)
    admin = sum(item["value"] for item in admin_rows)
    marketing = sum(item["value"] for item in marketing_rows)
    fixed = payroll + rent + admin + marketing
    result = revenue - cogs - fixed
    revenue_by_name = {item["name"]: item["revenue"] for item in products}
    bonus = 0.0
    bonus_lines = []
    for person in staff:
        if not person["percent"]:
            continue
        chosen = [name for name in person["services"] if name in revenue_by_name]
        if chosen:
            base = sum(revenue_by_name[name] for name in chosen)
            scope = "позиции " + ", ".join(chosen)
        else:
            base = revenue
            scope = "вся выручка месяца, список позиций не задан"
        part = person["percent"] / 100 * base
        bonus += part
        bonus_lines.append(f"{person['name']}: {person['percent']:g}% от базы «{scope}»")
    result_paid = result - bonus
    other = _num(form.get("other_income"))
    other_period = {1: "каждый месяц", 2: "раз в год", 3: "один раз"}.get(int(form.get("other_income_period") or 1), "каждый месяц")
    own_on = int(form.get("own_invest") or 0) == 1
    own = form.get("own") or {}
    attracted = form.get("attracted") or {}
    credit = form.get("credit") or {}
    lines = []
    for key, label, kind in INVEST:
        own_value = _num(own.get(key)) if own_on else 0
        attracted_value = _num(attracted.get(key))
        credit_value = _num(credit.get(key))
        if own_value or attracted_value or credit_value:
            lines.append(
                {
                    "key": key,
                    "label": label,
                    "kind": kind,
                    "own": own_value,
                    "attracted": attracted_value,
                    "credit": credit_value,
                    "total": own_value + attracted_value,
                }
            )
    investment = sum(item["total"] for item in lines)
    own_total = sum(item["own"] for item in lines)
    attracted_total = sum(item["attracted"] for item in lines)
    credit_total = sum(item["credit"] for item in lines)
    financing = int(form.get("financing") or 0)
    assets = []
    for key, label in ASSET_GROUPS:
        for row in form.get(key) or []:
            name = str(row.get("name") or "").strip()
            if not name:
                continue
            assets.append(
                {
                    "name": name,
                    "group": label,
                    "count": _num(row.get("count")),
                    "amount": _num(row.get("price")),
                    "life": _num(row.get("life_years")),
                }
            )
    capacity = []
    for year, value in sorted((form.get("planned_capacity") or {}).items()):
        if str(year).isdigit():
            capacity.append((str(year), _num(value)))
    start = str(form.get("planning_from") or "")
    end = str(form.get("planning_to") or "")
    span = _span(start, end)
    investor_years = int(_num(form.get("investor_term")))
    checks = []
    if products and result_paid < 0:
        checks.append(
            "При полной загрузке проект по введённым ежемесячным расходам убыточен до налогов и амортизации: "
            f"{_money(form, result_paid)} в месяц. Выручка {_money(form, revenue)}, закупочная себестоимость "
            f"{_money(form, cogs)}, постоянные расходы вместе с окладом {_money(form, fixed)}."
        )
    if financing in (1, 4):
        checks.append(
            f"Срок участия инвестора — {investor_years} лет. Это не срок возврата вложений. "
            f"Дивиденды { _num(form.get('investor_dividend')):g}% — доля распределяемой прибыли, а не график выплаты тела инвестиций."
        )
        if span and investor_years * 12 > span:
            checks.append(
                f"Горизонт модели — {span} мес.: {_month(start)} — {_month(end)}. "
                "Прогноз короче срока участия инвестора, поэтому возврат за весь этот срок в модели не виден."
            )
    if form.get("planned_volume") and form.get("planned_volume") == form.get("planning_to"):
        checks.append(
            f"Выход на полный объём совпадает с последним месяцем модели ({_month(form.get('planned_volume'))}). "
            "После выхода на 100% в прогнозе нет отдельного периода."
        )
    if any(value > 100 for _year, value in capacity):
        checks.append("В отдельные годы загрузка выше 100%. Это возможно, только если плановый объём можно превысить.")
    full_point = _ym(form.get("planned_volume"))
    entered_load = {year: _load_percent(value) for year, value in capacity}
    if full_point and entered_load.get(str(full_point[0]), 100) < 100:
        year_load = entered_load[str(full_point[0])]
        checks.append(
            f"Полный объём указан на {_month(form.get('planned_volume'))}, а загрузка {full_point[0]} года — {year_load:g}%. "
            "Вписанный процент года стоит на каждый месяц этого года. "
            f"Поэтому {_month(form.get('planned_volume'))} идёт с загрузкой {year_load:g}%, а не 100%."
        )
    half = (revenue - cogs - bonus) * 0.5 - fixed
    if products and result_paid >= 0:
        checks.append(
            f"Месяц полной загрузки до налогов и амортизации {_money(form, result_paid)}. Это не убыток. "
            f"При загрузке 50%, если себестоимость падает вместе с объёмом, а постоянные расходы остаются, "
            f"результат около {_money(form, half)}. Прочие доходы в обе цифры не входят."
        )
    year_rows = []
    sales_point = str(form.get("sales_start") or start)
    for year, percent in entered_load.items():
        operating = _selling_months(start, end, sales_point, int(year))
        if not operating:
            continue
        year_revenue = 0.0
        year_cogs = 0.0
        year_by_name: dict[str, float] = {}
        for item in products:
            product_sales = item["sales_start"] or sales_point
            months = _selling_months(start, end, product_sales, int(year), item["season"])
            item_revenue = item["revenue"] * percent / 100 * months
            year_revenue += item_revenue
            year_cogs += item["cogs"] * percent / 100 * months
            year_by_name[item["name"]] = item_revenue
        year_bonus = 0.0
        for person in staff:
            if not person["percent"]:
                continue
            chosen = [name for name in person["services"] if name in year_by_name]
            base = sum(year_by_name[name] for name in chosen) if chosen else year_revenue
            year_bonus += person["percent"] / 100 * base
        year_fixed = fixed * operating
        year_rows.append(
            {
                "year": year,
                "load": percent,
                "months": operating,
                "revenue": year_revenue,
                "cogs": year_cogs,
                "fixed": year_fixed,
                "bonus": year_bonus,
                "result": year_revenue - year_cogs - year_fixed - year_bonus,
            }
        )
    production = _production_notes(form, products)
    checks.extend(production)
    if int(form.get("tax_regime") or 0) == 4:
        checks.append(
            "Налоговый режим в анкете — упрощённый. Этот документ не проверяет, допустим ли он при обороте проекта."
        )
    volume_note = (
        "Объём в анкете задан в месяц."
        if monthly
        else "Объём в анкете задан за год. В таблице месяц — это двенадцатая часть, чтобы сверить расходы, которые идут каждый месяц."
    )
    company = str((profile or {}).get("company_name") or "").strip()
    facts = {
        "name": str(form.get("name") or "Проект"),
        "company": company,
        "legal": LEGAL.get(int(form.get("legal_form") or 0), "не указано"),
        "financing_code": financing,
        "financing": FINANCING.get(financing, "не указано"),
        "tax": TAX.get(int(form.get("tax_regime") or 0), "не указано"),
        "nds": "да" if form.get("nds") else "нет",
        "currency": MONEY.get(str(form.get("currency") or "kzt").strip().lower(), MONEY["kzt"])[0],
        "sign": sign,
        "start": _month(start),
        "end": _month(end),
        "span": span,
        "sales_start": _month(form.get("sales_start")),
        "full_volume": _month(form.get("planned_volume")),
        "investor_years": investor_years,
        "dividend": _num(form.get("investor_dividend")),
        "risk": _num(form.get("risk_premium")),
        "loan_term": int(_num(form.get("loan_term"))),
        "loan_rate": _num(form.get("loan_rate")),
        "loan_bank_rate": _num(form.get("loan_bank_rate")),
        "loan_subsidy_rate": _num(form.get("loan_subsidy_rate")),
        "loan_subsidy_term": int(_num(form.get("loan_subsidy_term"))),
        "loan_nbk_plus_4": bool(form.get("loan_nbk_plus_4")),
        "loan_grace": int(_num(form.get("loan_grace"))),
        "loan_annuity": bool(form.get("loan_annuity")),
        "gov_program": _gov_title(form),
        "gov_span": _gov_span(form),
        "gov_grace": _gov_grace(form),
        "gov_wc": _gov_wc(form),
        "products": products,
        "revenue": revenue,
        "cogs": cogs,
        "gross": revenue - cogs,
        "payroll": payroll,
        "rent": rent,
        "admin": admin,
        "marketing": marketing,
        "fixed": fixed,
        "result": result,
        "bonus": bonus,
        "bonus_lines": bonus_lines,
        "result_paid": result_paid,
        "half_result": half,
        "year_rows": year_rows,
        "other": other,
        "other_period": other_period,
        "staff": staff,
        "rent_rows": rent_rows,
        "admin_rows": admin_rows,
        "marketing_rows": marketing_rows,
        "lines": lines,
        "investment": investment,
        "own_total": own_total,
        "attracted_total": attracted_total,
        "credit_total": credit_total,
        "assets": assets,
        "capacity": capacity,
        "volume_note": volume_note,
        "checks": checks,
        "wc_months": {
            "raw": int(_num(form.get("months_raw"))),
            "advertising": int(_num(form.get("months_advertising"))),
            "wages": int(_num(form.get("months_wages"))),
            "rental": int(_num(form.get("months_rental"))),
            "other": int(_num(form.get("months_other"))),
        },
    }
    cover = (getattr(template, "cover", None) or "classic") if template else "classic"
    accent = str((getattr(template, "accent", None) or "#1e3a5f") if template else "#1e3a5f").lstrip("#").upper()
    if len(accent) != 6:
        accent = "1E3A5F"
    if cover == "mosaic":
        from app.bp_mosaic_sections import mosaic_sections

        sections = mosaic_sections(facts, form, answers or {}, results, profile, images or [])
    else:
        sections = _sections(facts, form, answers or {}, template, results, images or [])
    return {
        "facts": facts,
        "sections": sections,
        "cover": cover if cover in ("classic", "investor", "brief", "book", "mosaic", "growth") else "classic",
        "accent": accent,
        "style_name": getattr(template, "name", "") if template else "",
        "prepared": _today(),
    }


def _hospitality_notes(products: list[dict]) -> list[str]:
    rooms = [item for item in products if "номер" in item["name"].lower()]
    if not rooms:
        return []
    names = ", ".join(item["name"] for item in rooms)
    units = ", ".join(sorted({item["unit"] for item in rooms}))
    seasons = []
    for item in rooms:
        if not item["season"]:
            seasons.append(f"{item['name']} без сезона")
            continue
        months = ", ".join(MONTHS[month] for month in sorted(item["season"]))
        seasons.append(f"{item['name']}: {months}")
    return [
        (
            f"Позиции «{names}» заданы в единицах «{units}». Это не номеро-ночь, поэтому загрузку номерного фонда, ADR и RevPAR документ не считает. "
            "Сезон: " + "; ".join(seasons) + "."
        )
    ]


def _production_notes(form: dict, products: list[dict]) -> list[str]:
    cows = 0.0
    for key, _label in ASSET_GROUPS:
        for row in form.get(key) or []:
            name = str(row.get("name") or "").lower()
            if re.search(r"коров(?!ник)", name):
                cows += _num(row.get("count"))
    milk = next((item for item in products if item["unit"] == "л" and "молок" in item["name"].lower()), None)
    if not cows or milk is None or not milk["volume"]:
        return []
    per_day = milk["volume"] / cows / 30
    return [
        (
            f"Продажа «{milk['name']}» при 100% — {milk['volume']:g} л в месяц на {cows:g} голов. "
            f"Это около {per_day:.0f} л на голову в день при 30-дневном месяце, если это молоко надоено этими головами и не ушло в переработку."
        )
    ]


def _today() -> str:
    current = date.today()
    return f"{current.day} {MONTHS_GEN[current.month]} {current.year}"


def _prose(answers: dict, key: str) -> list[tuple]:
    text = str((answers or {}).get(key) or "").strip()
    if not text:
        return []
    return _markdown_blocks(text)


def _markdown_blocks(text: str) -> list[tuple]:
    """Знаки ## и ** из ответа модели становятся заголовком и жирным, в файл не попадают."""
    blocks = []
    paragraph: list[str] = []

    def flush() -> None:
        if not paragraph:
            return
        spans = _inline(" ".join(paragraph))
        paragraph.clear()
        if spans:
            blocks.append(("rich", spans))

    for raw in text.splitlines():
        line = raw.strip()
        if not line:
            flush()
            continue
        if len(line) >= 3 and not line.strip("-*_# "):
            flush()
            continue
        heading = re.match(r"^#{1,6}\s*(.+)$", line)
        wrapped = re.fullmatch(r"\*\*(.+?)\*\*", line)
        if heading or wrapped:
            flush()
            title = _inline_plain(heading.group(1) if heading else wrapped.group(1))
            if title:
                blocks.append(_block("h", title))
            continue
        bullet = re.match(r"^[-*]\s+(.+)$", line)
        if bullet:
            flush()
            spans = _inline("• " + bullet.group(1))
            if spans:
                blocks.append(("rich", spans))
            continue
        paragraph.append(line)
    flush()
    return blocks


def _inline(text: str) -> list[tuple[str, bool]]:
    spans: list[tuple[str, bool]] = []
    bold = False
    for piece in re.split(r"(\*\*\*|___|\*\*|__)", text):
        if piece in ("**", "__", "***", "___"):
            bold = not bold
            continue
        piece = piece.replace("`", "")
        piece = re.sub(r"\*([^*\n]+)\*", r"\1", piece).replace("*", "")
        if piece:
            spans.append((piece, bold))
    return spans


def _inline_plain(text: str) -> str:
    return "".join(part for part, _bold in _inline(text)).strip()


def readable_answer(text: str) -> str:
    """Текст раздела без решёток и звёздочек, как его видит человек."""
    lines: list[str] = []
    for raw in str(text or "").splitlines():
        line = raw.strip()
        if not line:
            if lines and lines[-1] != "":
                lines.append("")
            continue
        if len(line) >= 3 and not line.strip("-*_# "):
            continue
        heading = re.match(r"^#{1,6}\s*(.+)$", line)
        wrapped = re.fullmatch(r"\*\*(.+?)\*\*", line)
        bullet = re.match(r"^[-*]\s+(.+)$", line)
        source = heading.group(1) if heading else wrapped.group(1) if wrapped else bullet.group(1) if bullet else line
        plain = _inline_plain(source)
        if not plain:
            continue
        lines.append(("• " + plain) if bullet else plain)
    return "\n".join(lines).strip()


def _profile(template) -> str:
    slug = str(getattr(template, "slug", "") or "")
    if slug == "bank":
        return "bank"
    if slug == "investor":
        return "investor"
    return "universal"


def _season_text(item: dict) -> str:
    if not item.get("season"):
        return "круглый год"
    return ", ".join(MONTHS[month] for month in sorted(item["season"]))


def _activity_text(facts: dict) -> str:
    names = " ".join(item["name"].lower() for item in facts["products"])
    units = {item["unit"] for item in facts["products"]}
    if "номер" in names:
        return "Это гостиница: ниже процесс размещения и сопутствующих услуг, а не заводской цикл."
    if "л" in units or "коров" in names:
        return "Это сельское хозяйство: ниже мощности и выпуск, которые заданы в модели."
    if facts["cogs"] and units <= {"шт"}:
        return "Это торговля: ниже закупка и продажа. Отдельной технологии в модели нет."
    if any(token in names for token in ("услуг", "консульта", "обучен")):
        return "Это услуги: ниже процесс оказания услуг по помещениям и штату из модели."
    return "Ниже операционная деятельность по помещениям, оборудованию и штату из модели."


def _year_table(form: dict, years: list, rows: list[dict]) -> tuple[list[str], list[list[str]]]:
    header = ["Год", *[row["label"] for row in rows]]
    body = []
    for index, year in enumerate(years):
        body.append([str(year), *[_money(form, row["values"][index]) for row in rows]])
    return header, body


def _result_blocks(form: dict, results: dict, facts: dict) -> list:
    money = lambda value: _money(form, value)
    rows = [
        ["NPV", money(results["npv"]) if isinstance(results.get("npv"), (int, float)) else "—"],
        ["IRR", _rate(results.get("irr"))],
        ["PI", _plain_number(results.get("pi"))],
        ["Простой срок окупаемости", _years(results.get("payback"))],
        ["Дисконтированный срок окупаемости", _years(results.get("discounted"))],
    ]
    if facts["financing_code"] in (2, 4):
        rows.append(["Минимальный DSCR", _plain_number(results.get("dscr"))])
    if isinstance(results.get("deficit"), (int, float)):
        rows.append(["Дефицит ликвидности", money(results["deficit"])])
    return [
        _block("h", "Инвестиционные показатели"),
        _block("table", ["Показатель", "Значение"], rows),
        _block("p", "Годовые отчёты о прибыли и движении денег — в приложениях."),
    ]


def _sections(
    facts: dict,
    form: dict,
    answers: dict | None = None,
    template=None,
    results: dict | None = None,
    images: list[dict] | None = None,
) -> list[tuple]:
    money = lambda value: _money(form, value)
    profile = _profile(template)
    answers = answers or {}
    share = lambda part: "—" if not facts["investment"] else f"{part / facts['investment'] * 100:.1f}%".replace(".", ",")
    attracted_name = {1: "Инвестор", 2: "Кредит", 3: "Грант", 4: "Привлечённые, всего"}.get(facts["financing_code"], "Привлечённые")
    product_rows = [
        [
            item["name"],
            f"{item['volume']:g} {item['unit']}",
            money(item["price"]),
            money(item["revenue"]),
            money(item["unit_cost"]),
            money(item["cogs"]),
            money(item["margin"]),
        ]
        for item in facts["products"]
    ]
    staff_rows = [
        [item["name"], f"{item['count']:g}", money(item["salary"]), money(item["fund"]), f"{item['percent']:g}%" if item["percent"] else "—"]
        for item in facts["staff"]
    ]
    invest_rows = [
        [item["label"], money(item["own"]), money(item["attracted"]), money(item["total"])]
        for item in facts["lines"]
    ]
    asset_rows = [
        [item["name"], item["group"], f"{item['count']:g}", money(item["amount"]), f"{item['life']:g}"]
        for item in facts["assets"]
    ]
    expense_rows = (
        [[item["name"], "Аренда", money(item["value"])] for item in facts["rent_rows"]]
        + [[item["name"], "Административные", money(item["value"])] for item in facts["admin_rows"]]
        + [[item["name"], "Маркетинг", money(item["value"])] for item in facts["marketing_rows"]]
    )
    capacity_rows = [[year, f"{value:g}%"] for year, value in facts["capacity"]]
    revenue_total = facts["revenue"] or 0
    mix_rows = [
        [
            item["name"],
            money(item["revenue"]),
            "—" if not revenue_total else f"{item['revenue'] / revenue_total * 100:.1f}%".replace(".", ","),
            _season_text(item),
        ]
        for item in facts["products"]
    ]
    bridge = [
        ["Выручка при 100% загрузке", money(facts["revenue"])],
        ["Закупочная себестоимость", money(facts["cogs"])],
        ["Валовая прибыль", money(facts["gross"])],
        ["Фонд оплаты труда, оклад", money(facts["payroll"])],
        ["Аренда", money(facts["rent"])],
        ["Административные расходы", money(facts["admin"])],
        ["Маркетинг", money(facts["marketing"])],
        ["Постоянные расходы вместе с окладом", money(facts["fixed"])],
        ["Сверка до налогов, без переменной зарплаты", money(facts["result"])],
    ]
    if facts["bonus"]:
        bridge.append(["Переменная часть зарплаты", money(facts["bonus"])])
        bridge.append(["Сверка после переменной части", money(facts["result_paid"])])
    year_table = [
        [
            item["year"],
            f"{item['load']:g}%",
            str(item["months"]),
            money(item["revenue"]),
            money(item["cogs"]),
            money(item["fixed"]),
            money(item["result"]),
        ]
        for item in facts["year_rows"]
    ]
    source_rows = [
        ["Собственные средства", money(facts["own_total"]), share(facts["own_total"])],
        [attracted_name, money(facts["attracted_total"]), share(facts["attracted_total"])],
    ]
    if facts["financing_code"] == 4:
        investor_part = facts["attracted_total"] - facts["credit_total"]
        source_rows.append(["из них инвестор", money(investor_part), share(investor_part)])
        source_rows.append(["из них кредит", money(facts["credit_total"]), share(facts["credit_total"])])
    source_rows.append(["Инвестиционная потребность", money(facts["investment"]), "100%"])
    milestones = [
        ["Начало модели", facts["start"]],
        ["Старт продаж", facts["sales_start"]],
        ["Полный объём", facts["full_volume"]],
        ["Окончание модели", facts["end"]],
    ]
    if facts["financing_code"] in (1, 4):
        milestones.append(["Срок участия инвестора", f"{facts['investor_years']} лет с начала проекта"])
    wc = facts["wc_months"]
    wc_rows = [
        ["Сырьё и товары", f"{wc['raw']} мес."],
        ["Реклама", f"{wc['advertising']} мес."],
        ["Заработная плата", f"{wc['wages']} мес."],
        ["Аренда", f"{wc['rental']} мес."],
        ["Прочие", f"{wc['other']} мес."],
    ]
    kpi = [
        ["Инвестиционная потребность", money(facts["investment"])],
        ["Собственные средства", money(facts["own_total"])],
        [attracted_name, money(facts["attracted_total"])],
        ["Выручка при 100% загрузке, в месяц", money(facts["revenue"])],
        ["Прибыль месяца до налога", money(facts["result_paid"])],
        ["Та же прибыль при загрузке 50%", money(facts["half_result"])],
        ["Горизонт модели", f"{facts['start']} — {facts['end']}"],
    ]
    if facts["financing_code"] in (1, 4):
        kpi.append(["Срок участия инвестора", f"{facts['investor_years']} лет"])
        kpi.append(["Доля распределяемой прибыли", _pct(facts["dividend"])])
    headcount = sum(item["count"] for item in facts["staff"])
    names = ", ".join(item["name"] for item in facts["products"]) or "позиции не заданы"
    if facts["result_paid"] < 0:
        conclusion = (
            f"При 100% загрузке всех позиций сразу результат до налогов {money(facts['result_paid'])} в месяц."
        )
    else:
        conclusion = (
            f"При 100% загрузке всех позиций сразу результат до налогов {money(facts['result_paid'])} в месяц."
        )
    profile_line = {
        "bank": "Формат — банковский: кредит, платёжеспособность и обеспечение.",
        "investor": "Формат — инвесторский: доходность, доля распределяемой прибыли и риск.",
    }.get(profile, "Формат — универсальный.")
    purchase_rows = []
    for row in form.get("variable_costs") or []:
        product = str(row.get("product") or "").strip()
        kind = str(row.get("cost_type") or "").strip()
        if product or kind:
            purchase_rows.append([product or "—", kind or "—", money(_num(row.get("price"))), "за единицу"])
    for row in form.get("variable_other") or []:
        name = str(row.get("name") or "").strip()
        if name:
            purchase_rows.append([name, "прочая закупка", money(_num(row.get("value"))), "сумма в анкете"])
    has_purchases = bool(purchase_rows) or facts["cogs"] or any(item["key"] == "raw" and item["total"] for item in facts["lines"])
    show_pledge = profile == "bank" or facts["financing_code"] in (2, 4)
    load_checks = [text for text in facts["checks"] if "идёт с загрузкой" in text or "загрузка выше 100%" in text]
    production = [text for text in facts["checks"] if "голов" in text or "двойной учёт" in text]
    tax_checks = [text for text in facts["checks"] if "упрощён" in text]
    horizon_checks = [text for text in facts["checks"] if "Прогноз короче" in text or "совпадает с последним месяцем" in text]
    risk_rows = [
        ["Продажи", "Прибыль месяца до налога и та же прибыль при загрузке 50%"],
        ["Закупочная цена", "Цена единицы взята из заявки"],
        ["Финансирование", _capital_risk(facts)],
    ]
    if any(item.get("season") for item in facts["products"]):
        risk_rows.append(["Сезон", "Год складывается только из месяцев, когда позиция продаётся"])
    if facts["bonus"]:
        risk_rows.append(["Переменная зарплата", "Процент от выручки входит в сверку отдельно от оклада"])
    if facts["financing_code"] in (1, 4):
        risk_rows.append(["Дивиденды", f"Доля распределяемой прибыли {facts['dividend']:g}%"])
    title_rows = [
        ["Проект", facts["name"]],
        ["Организационно-правовая форма", facts["legal"]],
        ["Стоимость проекта", money(facts["investment"])],
        ["Привлекаемое финансирование", f"{money(facts['attracted_total'])}, {facts['financing']}"],
        ["Срок модели", f"{facts['start']} — {facts['end']}"],
        ["Дата подготовки", _today()],
        ["Конфиденциальность", "Конфиденциально"],
    ]
    pieces = []

    def add(key: str, title: str, blocks: list) -> None:
        pieces.append((key, title, blocks))

    add("title", "Титульный лист", [
        _block("p", f"Бизнес-план проекта «{facts['name']}»."),
        _block("table", ["Поле", "Значение"], title_rows),
    ])
    amount_title = "Сумма кредита" if facts["financing_code"] in (2, 4) else "Привлекаемая сумма"
    terms = [
        _block("p", profile_line),
        _block("p", f"{amount_title}: {money(facts['attracted_total'])}. Источник: {facts['financing']}."),
        _block("p", f"Собственные средства: {money(facts['own_total'])}. Стоимость проекта: {money(facts['investment'])}."),
        _block("h", "Структура финансирования"),
        _block("table", ["Статья", "Собственные", "Привлечённые", "Всего"], invest_rows + [["Итого", money(facts["own_total"]), money(facts["attracted_total"]), money(facts["investment"])]]),
        _block("p", f"Период реализации: {facts['start']} — {facts['end']}. Старт продаж — {facts['sales_start']}. Полный объём — {facts['full_volume']}."),
        _block("table", ["Точка", "Срок"], milestones),
    ]
    if facts["financing_code"] in (2, 4) and facts["loan_term"]:
        method = "аннуитет" if facts["loan_annuity"] else "дифференцированные платежи"
        credit_rows = [
            ["Госпрограмма", facts["gov_program"] or "нет"],
            ["Порядок платежей", method],
            ["Срок", f"{facts['loan_term']} мес."],
            ["Льготный период", f"{facts['loan_grace']} мес."],
            ["Ставка для клиента", _pct(facts["loan_rate"])],
            ["Сумма", money(facts["credit_total"])],
        ]
        if facts.get("loan_bank_rate"):
            bank_label = "Ставка банка (НБ РК + 4%)" if facts.get("loan_nbk_plus_4") else "Ставка банка"
            credit_rows.append([bank_label, _pct(facts["loan_bank_rate"])])
        if facts.get("loan_subsidy_rate"):
            credit_rows.append(["Субсидируемая ставка Damu", _pct(facts["loan_subsidy_rate"])])
        if facts.get("loan_subsidy_term"):
            credit_rows.append(["Срок субсидии", f"{facts['loan_subsidy_term']} мес."])
        if facts.get("gov_span"):
            credit_rows.append(["Срок по каталогу", facts["gov_span"]])
        if facts.get("gov_grace"):
            credit_rows.append(["Каникулы по программе", facts["gov_grace"]])
        if facts.get("gov_wc"):
            credit_rows.append(["Оборотные средства по программе", facts["gov_wc"]])
        terms.append(_block("h", "Условия кредита"))
        terms.append(_block("table", ["Условие", "Значение"], credit_rows))
        terms.append(_block("p", _payment_logic(facts)))
        wc_term = int(_num(form.get("wc_term")))
        if facts.get("gov_wc") == "отсутствует":
            terms.append(_block("p", "Заём на оборотные средства по выбранной госпрограмме отсутствует."))
        elif wc_term:
            terms.append(_block("p", f"Заём на оборотные средства: срок {wc_term} мес., льготный период {int(_num(form.get('wc_grace')))} мес., ставка {_pct(form.get('wc_rate'))}."))
        terms.append(_block("p", f"Источник погашения — выручка от: {names}."))
    elif facts["financing_code"] in (1, 4):
        terms.append(_block("p", f"Привлечённые средства — капитал инвестора. Доля распределяемой прибыли {_pct(facts['dividend'])}. Срок участия {facts['investor_years']} лет."))
    add("terms", "Общие исходные данные и условия", terms)
    add("company", "Информация о хозяйствующем субъекте", [
        _block("p", f"Форма {facts['legal']}. Налоговый режим: {facts['tax']}. НДС: {facts['nds']}. Валюта {facts['currency']} ({facts['sign']})."),
        _block("p", f"Деятельность: {names}."),
        _block("p", f"Численность по штатному расписанию — {headcount:g}."),
        _block("table", ["Должность", "Числ.", "Оклад", "Фонд", "%"], staff_rows or [["—", "—", "—", "—", "—"]]),
        _block("p", f"Фонд окладов {money(facts['payroll'])} в месяц. Процент от выручки в оклад не входит."),
        *[_block("p", line) for line in facts["bonus_lines"]],
        *([_block("p", f"Переменная часть при полной загрузке всех позиций сразу — {money(facts['bonus'])} в месяц.")] if facts["bonus"] else []),
    ])
    add("risks", "Анализ рисков", [
        _block("table", ["Риск", "Основание"], risk_rows),
        _block("p", _sales_risk(facts, money)),
        _block("p", _capital_risk(facts)),
        _block("p", _dividend_risk(facts, money)),
    ])
    offer = [
        _block("h", "Продуктовая линейка"),
        _block("p", facts["volume_note"] + " Объём в таблице — при загрузке 100%."),
        _block("table", ["Позиция", "Объём", "Цена", "Выручка", "За ед.", "Закуп", "Маржа"], product_rows or [["—", "—", "—", "—", "—", "—", "—"]]),
        _block("table", ["Позиция", "Выручка при 100%", "Доля", "Сезон"], mix_rows or [["—", "—", "—", "—"]]),
        _block("h", "Маркетинговые расходы"),
        _block("table", ["Статья", "В месяц"], [[item["name"], money(item["value"])] for item in facts["marketing_rows"]] or [["—", "—"]]),
    ]
    if facts["marketing"]:
        offer.append(_block("p", f"Маркетинговые расходы вместе: {money(facts['marketing'])} в месяц."))
    add("offer", "Реализация продукции (оказание услуг)", offer)
    sales_blocks = [
        _block("p", f"Старт продаж — {facts['sales_start']}. Полный объём — {facts['full_volume']}. {facts['volume_note']}"),
        _block("table", ["Год", "Загрузка"], capacity_rows or [["—", "—"]]),
        *[_block("p", text) for text in load_checks],
    ]
    if facts["other"]:
        sales_blocks.append(_block("p", f"Прочие доходы {money(facts['other'])}, {facts['other_period']}, входят в годовую выручку."))
    sales_blocks.append(_block("p", "Годовая прибыль и движение денег — в приложениях. Окупаемость — в оценке вложений."))
    add("sales", "Прогнозы продаж и планируемый график продаж", sales_blocks)
    cost_blocks = [
        _block("p", _activity_text(facts)),
        _block("h", "Помещения и оборудование"),
        _block("table", ["Наименование", "Группа", "Количество", "Сумма", "Срок службы, лет"], asset_rows or [["—", "—", "—", "—", "—"]]),
        *[_block("warn", text) for text in production],
        _block("h", "Постоянные расходы в месяц"),
        _block("table", ["Статья", "Группа", "В месяц"], expense_rows or [["—", "—", "—"]]),
    ]
    if has_purchases:
        cost_blocks.extend([
            _block("h", "Закупки"),
            _block("table", ["Позиция", "Вид", "Сумма", "Как задано"], purchase_rows or [["—", "—", "—", "—"]]),
            _block("p", f"Себестоимость месяца при 100% загрузке — {money(facts['cogs'])}. Запас сырья — {wc['raw']} мес."),
        ])
    add("costs", "План расходов", cost_blocks)
    invest_blocks = [
        _block("p", _financing_text(facts)),
        _block("h", "Оборотный запас"),
        _block("table", ["Статья", "Запас"], wc_rows),
        *[_block("p", text) for text in horizon_checks + tax_checks],
    ]
    if results:
        invest_blocks.extend(_result_blocks(form, results, facts))
    else:
        invest_blocks.append(_block("p", f"При полной загрузке всех позиций сразу прибыль месяца до налога — {money(facts['result_paid'])}."))
    if show_pledge and facts["financing_code"] not in (2, 4):
        invest_blocks.append(_block("p", "Кредит не выбран, обеспечение займа не требуется."))
    add("invest", "Оценка вложений инвестиций", invest_blocks)
    add("close", "Выводы", [
        _block("table", ["Показатель", "Значение"], kpi),
    ])
    if results:
        profit = [row for row in results["pnl"] if row["label"] in ("Выручка", "Себестоимость", "Чистая прибыль", "EBITDA")]
        tax_rows = [row for row in results["pnl"] if row["label"] in ("Валовая прибыль", "Прибыль до налога", "Налог")]
        add("appendices", "Приложения", [
            _block("h", "Приложение 1. Отчёт о прибылях и убытках, тенге"),
            _block("table", *_year_table(form, results["years"], profit)),
            _block("table", *_year_table(form, results["years"], tax_rows)),
            _block("h", "Приложение 2. Движение денежных средств, тенге"),
            _block("table", *_year_table(form, results["years"], results["cash"])),
        ])
    attach = {
        "terms": ["use"],
        "risks": ["risks"],
        "offer": ["product", "market", "competitors", "marketing"],
        "costs": ["operations"],
        "close": ["summary"],
    }
    images = images or []
    if any(item["section"] == "appendices" for item in images) and not any(key == "appendices" for key, _t, _b in pieces):
        add("appendices", "Приложения", [])
    numbered = []
    for index, (key, title, blocks) in enumerate(pieces, start=1):
        extra = []
        for item in attach.get(key, []):
            extra.extend(_prose(answers, item))
        pictures = [_block("image", item["data"], item["ratio"], item["caption"]) for item in images if item["section"] == key]
        numbered.append((index, title, [*blocks, *extra, *pictures]))
    contents = (
        0,
        "Содержание",
        [
            _block("p", "Оглавление собрано по разделам этого файла."),
            _block("table", ["№", "Раздел"], [[f"{number:02d}", title] for number, title, _blocks in numbered]),
        ],
    )
    return [numbered[0], contents, *numbered[1:]]


def project_case(form: dict) -> str:
    """Полный кейс для модели: все поля проекта, без догадок."""
    form = form or {}
    facts = assemble(form)["facts"]
    money = lambda value: _money(form, value)
    schedule = {1: "пятидневная неделя", 2: "шестидневная неделя", 3: "ежедневно"}.get(int(form.get("schedule") or 0), "не указан")
    step = "объём задан в месяц" if int(form.get("time_param") or 3) != 1 else "объём задан за год"
    lines = [
        "Это полный кейс проекта. Пиши только по этим полям. Чего здесь нет — не упоминай и не называй отсутствующим.",
        f"Проект: {facts['name']}",
        f"Форма: {facts['legal']}",
        f"Налог: {facts['tax']}, НДС: {facts['nds']}",
        f"Валюта: {facts['currency']} ({facts['sign']})",
        f"График работы: {schedule}",
        f"Шаг объёма: {step}",
        f"Период: {facts['start']} — {facts['end']}" + (f", {facts['span']} мес." if facts["span"] else ""),
        f"Старт продаж: {facts['sales_start']}",
        f"Полный объём: {facts['full_volume']}",
        f"Финансирование: {facts['financing']}",
    ]
    if facts["capacity"]:
        lines.append("Загрузка по годам, %: " + "; ".join(f"{year} — {value:g}" for year, value in facts["capacity"]))
    products = []
    for item in facts["products"]:
        products.append(
            f"{item['name']}: {item['volume']:g} {item['unit']} по {money(item['price'])}, "
            f"выручка {money(item['revenue'])}, себестоимость единицы {money(item['unit_cost'])}, "
            f"себестоимость {money(item['cogs'])}, маржа {money(item['margin'])}"
        )
    lines.extend(_case_lines("Продукция при 100% загрузке", products))
    for row in form.get("volume_sales") or []:
        name = str(row.get("name") or "").strip()
        if not name:
            continue
        extra = []
        if row.get("sales_start"):
            extra.append(f"старт {_month(row.get('sales_start'))}")
        if row.get("seasonal"):
            extra.append("сезонная")
        if row.get("months"):
            extra.append("месяцы " + ", ".join(str(item) for item in row.get("months")))
        if extra:
            lines.append(f"- {name}, уточнение: " + ", ".join(extra))
    staff = [
        f"{item['name']}: {item['count']:g} × {money(item['salary'])}"
        + (f", {item['percent']:g}% от выручки" if item["percent"] else "")
        for item in facts["staff"]
    ]
    lines.extend(_case_lines("Команда, оклад в месяц", staff))
    expenses = (
        [f"{item['name']} (аренда): {money(item['value'])}" for item in facts["rent_rows"]]
        + [f"{item['name']} (административные): {money(item['value'])}" for item in facts["admin_rows"]]
        + [f"{item['name']} (маркетинг): {money(item['value'])}" for item in facts["marketing_rows"]]
    )
    lines.extend(_case_lines("Постоянные расходы в месяц", expenses))
    assets = [
        f"{item['name']} ({item['group']}): {item['count']:g} шт, {money(item['amount'])}, срок службы {item['life']:g} лет"
        for item in facts["assets"]
    ]
    lines.extend(_case_lines("Активы", assets))
    invest = [
        f"{item['label']}: собственные {money(item['own'])}, привлечённые {money(item['attracted'])}, всего {money(item['total'])}"
        for item in facts["lines"]
    ]
    lines.extend(_case_lines("Инвестиции по статьям", invest))
    lines.append(f"Инвестиционная потребность: {money(facts['investment'])}")
    lines.append(f"Собственные средства: {money(facts['own_total'])}")
    lines.append(f"Привлечённые средства: {money(facts['attracted_total'])}")
    if facts["credit_total"]:
        lines.append(f"Из привлечённых кредит: {money(facts['credit_total'])}")
    wc = facts["wc_months"]
    lines.append(
        "Запас оборотных средств, месяцев: "
        f"сырьё {wc['raw']}, реклама {wc['advertising']}, зарплата {wc['wages']}, аренда {wc['rental']}, прочие {wc['other']}"
    )
    lines.append(
        f"Прочие доходы: {money(facts['other'])}, {facts['other_period']}" if facts["other"] else "Прочие доходы в сверку не входят."
    )
    lines.append(_financing_text(facts))
    lines.append(
        "Прибыль одного месяца при 100% загрузке, до налога и амортизации: "
        f"выручка {money(facts['revenue'])}, себестоимость {money(facts['cogs'])}, "
        f"валовая прибыль {money(facts['gross'])}, оклад {money(facts['payroll'])}, "
        f"аренда {money(facts['rent'])}, административные {money(facts['admin'])}, "
        f"маркетинг {money(facts['marketing'])}, переменная зарплата {money(facts['bonus'])}, "
        f"сверка после неё {money(facts['result_paid'])}"
    )
    if facts["checks"]:
        lines.append("Проверки, их нельзя сглаживать:")
        lines.extend(f"- {item}" for item in facts["checks"])
    lines.append("Не перечисляй поля, которых в кейсе нет. Не пиши, что данных не хватает.")
    return "\n".join(lines)


def _case_lines(title: str, rows: list[str]) -> list[str]:
    if not rows:
        return [f"{title}: нет"]
    return [f"{title}:", *[f"- {row}" for row in rows]]


def _capital_risk(facts: dict) -> str:
    if facts["financing_code"] == 1:
        return "Источник привлечённых средств — инвестор. Это капитал, не банковский заём. Кредита в заявке нет."
    if facts["financing_code"] == 3:
        return "Привлечённая сумма в этой заявке — грант, не кредит."
    if facts["financing_code"] == 4:
        return "В заявке есть и инвестор, и кредит. Капитал инвестора и заём — разные источники, их нельзя называть одним займом."
    return "Источник привлечённых средств — кредит. Это заём, не доля инвестора."


def _sales_risk(facts: dict, money) -> str:
    if facts["result"] < 0:
        return (
            "Риск недостижения продаж. Уже месяц полной загрузки даёт результат до налогов и амортизации "
            + money(facts["result_paid"])
            + ". Если загрузка будет ниже, разрыв шире: постоянные расходы при этом не падают."
        )
    return (
        "Риск недостижения продаж. Месяц полной загрузки даёт результат до налогов и амортизации "
        + money(facts["result_paid"])
        + ". Это не убыток. При загрузке 50%, если себестоимость падает вместе с объёмом, а постоянные расходы остаются, результат "
        + money(facts["half_result"])
        + ". Налоги и амортизация в обе цифры не входят."
    )


def _dividend_risk(facts: dict, money) -> str:
    if facts["financing_code"] not in (1, 4):
        return "Инвестор в выбранном способе финансирования не используется, поэтому дивидендный риск к этой заявке не относится."
    if facts["result_paid"] < 0:
        return (
            "Финансовый риск. При результате месяца "
            + money(facts["result_paid"])
            + " дивиденды не из чего платить, даже если доля распределения задана. "
            + f"Доля распределяемой прибыли {_pct(facts['dividend'])}."
        )
    return f"Доля распределяемой прибыли {_pct(facts['dividend'])}. Срок участия инвестора задан в исходных данных."


def _financing_text(facts: dict) -> str:
    if facts["financing_code"] in (1, 4):
        text = (
            f"Инвестор участвует {facts['investor_years']} лет. Это привлечённый капитал, не банковский заём. "
            f"Доля распределяемой прибыли {_pct(facts['dividend'])}. Премия за риск проекта {_pct(facts['risk'])}."
        )
    else:
        text = "Инвестор в выбранном способе финансирования не используется."
    if facts["financing_code"] in (2, 4) and facts["loan_term"]:
        method = "аннуитет" if facts["loan_annuity"] else "дифференцированные платежи"
        gov_bits = []
        if facts.get("gov_program"):
            gov_bits.append(f"госпрограмма «{str(facts['gov_program']).strip('«»')}»")
            if facts.get("gov_span"):
                gov_bits.append(f"срок по каталогу {str(facts['gov_span']).rstrip('.')}")
            if facts.get("gov_grace"):
                gov_bits.append(f"каникулы по программе {str(facts['gov_grace']).rstrip('.')}")
            if facts.get("gov_wc") == "отсутствует":
                gov_bits.append("заём на оборотные средства по программе отсутствует")
        else:
            gov_bits.append("без госпрограммы")
        rate_bits = [f"ставка для клиента {_pct(facts['loan_rate'])}"]
        if facts.get("loan_bank_rate"):
            bank_note = "НБ РК + 4%" if facts.get("loan_nbk_plus_4") else "номинал банка"
            rate_bits.append(f"ставка банка {_pct(facts['loan_bank_rate'])} ({bank_note})")
        if facts.get("loan_subsidy_rate"):
            rate_bits.append(f"субсидия Damu {_pct(facts['loan_subsidy_rate'])}")
        if facts.get("loan_subsidy_term"):
            rate_bits.append(f"срок субсидии {facts['loan_subsidy_term']} мес.")
        text += (
            f" Кредит: {method}, срок {facts['loan_term']} мес., льготный период {facts['loan_grace']} мес., "
            f"{', '.join(rate_bits)}, {', '.join(gov_bits)}. "
            f"Каждый месяц проценты клиента — {_pct(_num(facts['loan_rate']) / 12)} от остатка долга."
        )
    if facts["financing_code"] == 3:
        text += " Привлечённая сумма в этой модели — грант."
    return text


BOOK = (10, 33, 64)
BOOK_LINE = (186, 206, 224)


def _sign_money(sign: str, value: float) -> str:
    amount = int(round(value))
    text = f"{abs(amount):,}".replace(",", " ")
    return f"−{text} {sign}" if amount < 0 else f"{text} {sign}"


def _short_money(value: float) -> str:
    amount = abs(value)
    if amount >= 1_000_000_000:
        return f"{value / 1_000_000_000:.1f} млрд".replace(".", ",")
    if amount >= 1_000_000:
        return f"{value / 1_000_000:.1f} млн".replace(".", ",")
    return f"{int(round(value)):,}".replace(",", " ")


def _book_year(plan: dict) -> str:
    match = re.search(r"\d{4}", str(plan["facts"].get("start") or ""))
    if match:
        return match.group(0)
    match = re.search(r"\d{4}", str(plan.get("prepared") or ""))
    return match.group(0) if match else ""


def _wrap_name(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont, width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words or [text]:
        trial = f"{current} {word}".strip()
        if draw.textlength(trial, font=font) <= width:
            current = trial
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines[:3] or [text]


def _book_cover_png(plan: dict) -> bytes:
    width, height = 1240, 1754
    image = Image.new("RGB", (width, height), BOOK)
    draw = ImageDraw.Draw(image)
    brand = ImageFont.truetype(SANS, 30)
    title = ImageFont.truetype(SANS_BOLD, 108)
    sub = ImageFont.truetype(SANS, 34)
    draw.text((96, 150), "NC-", font=brand, fill=(255, 255, 255))
    draw.text((96, 192), "CONSULTING", font=brand, fill=(255, 255, 255))
    draw.line((96, 258, 560, 258), fill=(255, 255, 255), width=2)
    draw.text((96, 360), "Бизнес-", font=title, fill=(255, 255, 255))
    draw.text((96, 480), "план", font=title, fill=(255, 255, 255))
    y = 680
    for line in _wrap_name(draw, str(plan["facts"]["name"]), sub, 980):
        draw.text((96, y), line, font=sub, fill=(214, 224, 236))
        y += 48
    year = _book_year(plan)
    if year:
        draw.text((96, y + 24), year, font=sub, fill=(255, 255, 255))
    draw.ellipse((1088, 148, 1160, 220), outline=BOOK_LINE, width=3)
    draw.ellipse((1108, 168, 1140, 200), outline=BOOK_LINE, width=2)
    for index, radius in enumerate((980, 860, 740, 620)):
        box = (-120, height - radius - 80 + index * 18, radius, height + 220)
        draw.arc(box, 200, 340, fill=BOOK_LINE, width=3)
    buffer = BytesIO()
    image.save(buffer, "PNG")
    return buffer.getvalue()


def _book_bars_png(plan: dict) -> bytes | None:
    rows = plan["facts"].get("year_rows") or []
    if not rows:
        return None
    width, height = 1400, 560
    image = Image.new("RGB", (width, height), (255, 255, 255))
    draw = ImageDraw.Draw(image)
    title = ImageFont.truetype(SANS_BOLD, 28)
    label = ImageFont.truetype(SANS, 22)
    draw.text((24, 16), "Выручка по годам, до налогов и амортизации", font=title, fill=BOOK)
    peak = max(item["revenue"] for item in rows) or 1
    left, right, top, bottom = 70, 40, 80, 70
    plot_w = width - left - right
    plot_h = height - top - bottom
    gap = 28
    bar_w = (plot_w - gap * (len(rows) + 1)) / len(rows)
    for index, item in enumerate(rows):
        bar_h = plot_h * item["revenue"] / peak
        x = left + gap + index * (bar_w + gap)
        y = top + plot_h - bar_h
        draw.rectangle((x, y, x + bar_w, top + plot_h), fill=(30, 58, 110))
        value = _short_money(item["revenue"])
        draw.text((x, max(top, y - 30)), value, font=label, fill=BOOK)
        draw.text((x, top + plot_h + 12), str(item["year"]), font=label, fill=(70, 80, 96))
    draw.line((left, top + plot_h, width - right, top + plot_h), fill=(210, 216, 224), width=2)
    buffer = BytesIO()
    image.save(buffer, "PNG")
    return buffer.getvalue()


def _book_mix_png(plan: dict) -> bytes | None:
    facts = plan["facts"]
    total = facts.get("investment") or 0
    if total <= 0:
        return None
    width, height = 1400, 280
    image = Image.new("RGB", (width, height), (255, 255, 255))
    draw = ImageDraw.Draw(image)
    title = ImageFont.truetype(SANS_BOLD, 28)
    label = ImageFont.truetype(SANS, 22)
    draw.text((24, 12), "Структура финансирования", font=title, fill=BOOK)
    parts = (
        ("Собственные", facts.get("own_total") or 0, (30, 58, 110)),
        ("Привлечённые", facts.get("attracted_total") or 0, (70, 110, 150)),
    )
    y = 70
    for name, value, color in parts:
        share = value / total
        draw.text((24, y), f"{name}  {_sign_money(facts['sign'], value)}", font=label, fill=(40, 48, 60))
        y += 34
        draw.rectangle((24, y, 24 + 1100 * share, y + 28), fill=color)
        y += 52
    buffer = BytesIO()
    image.save(buffer, "PNG")
    return buffer.getvalue()


def _book_kpis(plan: dict) -> list[tuple[str, str]]:
    facts = plan["facts"]
    sign = facts["sign"]
    rows = [
        ("Инвестиции", _sign_money(sign, facts["investment"])),
        ("Выручка в месяц", _sign_money(sign, facts["revenue"])),
        ("Результат в месяц", _sign_money(sign, facts["result"])),
        ("При загрузке 50%", _sign_money(sign, facts["half_result"])),
    ]
    if facts["investment"]:
        share = facts["attracted_total"] / facts["investment"] * 100
        rows[3] = ("Доля привлечённых", f"{share:.1f}%".replace(".", ","))
    return rows


GROWTH_NAVY = (16, 40, 72)
GROWTH_NAVY_DARK = (10, 26, 48)
GROWTH_GOLD = (184, 148, 74)
GROWTH_GOLD_LIGHT = (214, 186, 122)
GROWTH_GOLD_DEEP = (150, 116, 52)
GROWTH_INK = (16, 32, 56)
GROWTH_MUTED = (96, 108, 124)
GROWTH_TITLE_PT = 44


def _pt(size: float) -> int:
    """Пункт в пиксели обложки: лист A4 рисуется в 150 точках на дюйм."""
    return max(1, round(size * 150 / 72))


def _growth_date(plan: dict) -> str:
    text = str(plan.get("prepared") or "")
    parts = text.split()
    if len(parts) == 3 and parts[0].isdigit() and parts[2].isdigit():
        month = next((index for index, name in enumerate(MONTHS_GEN) if name == parts[1]), 0)
        if month:
            return f"{int(parts[0]):02d}.{month:02d}.{parts[2]}"
    current = date.today()
    return f"{current.day:02d}.{current.month:02d}.{current.year}"


def _quad(start: tuple, control: tuple, end: tuple, steps: int = 36) -> list[tuple]:
    points = []
    for step in range(steps + 1):
        t = step / steps
        points.append(
            (
                (1 - t) ** 2 * start[0] + 2 * (1 - t) * t * control[0] + t ** 2 * end[0],
                (1 - t) ** 2 * start[1] + 2 * (1 - t) * t * control[1] + t ** 2 * end[1],
            )
        )
    return points


def _arrow_head(tip: tuple, prev: tuple, length: float = 34, spread: float = 16) -> list[tuple]:
    dx, dy = tip[0] - prev[0], tip[1] - prev[1]
    dist = (dx * dx + dy * dy) ** 0.5 or 1
    ux, uy = dx / dist, dy / dist
    px, py = -uy * spread, ux * spread
    base = (tip[0] - ux * length, tip[1] - uy * length)
    return [tip, (base[0] + px, base[1] + py), (base[0] - px, base[1] - py)]


def _growth_cover_png(plan: dict) -> bytes:
    width, height = 1240, 1754
    image = Image.new("RGB", (width, height), (255, 255, 255))
    draw = ImageDraw.Draw(image)
    # Верхний угол: тёмная полоса и основной треугольник с одной и той же диагональю.
    draw.polygon([(860, 0), (width, 0), (width, 420)], fill=GROWTH_NAVY)
    draw.polygon([(1088, 0), (width, 0), (width, 168)], fill=GROWTH_NAVY_DARK)
    draw.line((860, 0, width, 420), fill=GROWTH_GOLD, width=4)
    # Нижний угол: та же диагональ у заливки и у золотой линии.
    draw.polygon([(0, height), (0, 1548), (330, height)], fill=GROWTH_NAVY)
    draw.polygon([(0, height), (0, 1648), (170, height)], fill=GROWTH_NAVY_DARK)
    draw.line((0, 1548, 330, height), fill=GROWTH_GOLD, width=4)

    city = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    city_draw = ImageDraw.Draw(city)
    skyline_base = 1320
    for left, top, right in (
        (760, 980, 820),
        (830, 860, 910),
        (920, 740, 1020),
        (1030, 900, 1110),
        (1120, 800, 1210),
    ):
        city_draw.rectangle((left, top, right, skyline_base), fill=(198, 214, 228, 150))
    image.paste(Image.alpha_composite(image.convert("RGBA"), city).convert("RGB"))
    draw = ImageDraw.Draw(image)

    bars = (110, 190, 290, 400)
    base = 1390
    left0, bar_w, gap, depth = 820, 72, 28, 14
    for index, bar_h in enumerate(bars):
        left = left0 + index * (bar_w + gap)
        top = base - bar_h
        draw.polygon(
            [(left, top), (left + depth, top - depth), (left + bar_w + depth, top - depth), (left + bar_w, top)],
            fill=GROWTH_GOLD_LIGHT,
        )
        draw.polygon(
            [
                (left + bar_w, top),
                (left + bar_w + depth, top - depth),
                (left + bar_w + depth, base - depth),
                (left + bar_w, base),
            ],
            fill=GROWTH_GOLD_DEEP,
        )
        draw.rectangle((left, top, left + bar_w, base), fill=GROWTH_GOLD)
    draw.line((left0, base, left0 + 4 * bar_w + 3 * gap + depth, base), fill=(226, 230, 234), width=2)
    arrow = _quad((760, 1320), (1000, 1080), (1210, 900))
    draw.line(arrow[:-3], fill=GROWTH_GOLD, width=6)
    draw.polygon(_arrow_head(arrow[-1], arrow[-6]), fill=GROWTH_GOLD)

    label = ImageFont.truetype(SANS_BOLD, _pt(13))
    name_font = ImageFont.truetype(SANS, _pt(12))
    title = ImageFont.truetype(SERIF_BOLD, _pt(GROWTH_TITLE_PT))
    project_font = ImageFont.truetype(SERIF, _pt(22))
    meta_label = ImageFont.truetype(SANS_BOLD, _pt(12))
    meta_value = ImageFont.truetype(SANS, _pt(12))
    foot = ImageFont.truetype(SANS, _pt(11))
    facts = plan["facts"]
    company = str(facts.get("company") or "").strip() or "—"
    draw.line((78, 86, 78, 168), fill=GROWTH_GOLD, width=3)
    draw.text((98, 84), "Компания", font=label, fill=GROWTH_INK)
    y = 118
    for line in _wrap_name(draw, company, name_font, 520):
        draw.text((98, y), line, font=name_font, fill=GROWTH_MUTED)
        y += _pt(15)
    draw.text((78, 250), "Бизнес-план", font=title, fill=GROWTH_INK)
    title_box = draw.textbbox((78, 250), "Бизнес-план", font=title)
    draw.rectangle((78, title_box[3] + 16, 250, title_box[3] + 22), fill=GROWTH_GOLD)
    y = title_box[3] + 40
    for line in _wrap_name(draw, str(facts.get("name") or "Проект"), project_font, 620):
        draw.text((78, y), line, font=project_font, fill=GROWTH_INK)
        y += _pt(28)
    y += 28
    for caption, value, icon in (
        ("Компания:", company, "building"),
        ("Дата:", _growth_date(plan), "date"),
    ):
        _growth_icon(draw, icon, 86, y, GROWTH_GOLD)
        draw.line((138, y + 4, 138, y + 52), fill=GROWTH_GOLD, width=2)
        draw.text((156, y), caption, font=meta_label, fill=GROWTH_INK)
        draw.text((156, y + 24), value, font=meta_value, fill=GROWTH_MUTED)
        y += 78

    footer = "Бизнес-план  •  Конфиденциально"
    footer_w = draw.textlength(footer, font=foot)
    footer_x = (width - footer_w) / 2
    footer_y = height - 78
    draw.line((160, footer_y + 8, footer_x - 16, footer_y + 8), fill=GROWTH_GOLD, width=2)
    draw.line((footer_x + footer_w + 16, footer_y + 8, width - 160, footer_y + 8), fill=GROWTH_GOLD, width=2)
    draw.text((footer_x, footer_y), footer, font=foot, fill=GROWTH_MUTED)
    buffer = BytesIO()
    image.save(buffer, "PNG")
    return buffer.getvalue()


def _growth_icon(draw: ImageDraw.ImageDraw, kind: str, x: int, y: int, color: tuple) -> None:
    if kind == "building":
        draw.polygon([(x + 4, y + 18), (x + 22, y + 4), (x + 40, y + 18)], outline=color)
        draw.rectangle((x + 8, y + 18, x + 36, y + 46), outline=color, width=2)
        draw.rectangle((x + 18, y + 30, x + 26, y + 46), outline=color, width=2)
        return
    draw.rounded_rectangle((x + 6, y + 10, x + 40, y + 46), radius=4, outline=color, width=2)
    draw.line((x + 6, y + 22, x + 40, y + 22), fill=color, width=2)
    draw.line((x + 16, y + 6, x + 16, y + 16), fill=color, width=2)
    draw.line((x + 30, y + 6, x + 30, y + 16), fill=color, width=2)


def _growth_cover_docx(doc: Document, plan: dict) -> None:
    paragraph = doc.add_paragraph()
    _tight(paragraph)
    run = paragraph.add_run()
    run.add_picture(BytesIO(_growth_cover_png(plan)), width=Cm(21), height=Cm(29.7))


def render_docx(plan: dict) -> bytes:
    if plan["cover"] == "mosaic":
        from app.bp_mosaic import render_mosaic_docx

        return render_mosaic_docx(plan)
    cover = plan["cover"]
    accent = plan["accent"]
    font = "Times New Roman" if cover == "classic" else "Calibri"
    doc = Document()
    normal = doc.styles["Normal"]
    normal.font.name = font
    normal.font.size = Pt(11)
    for style_name, size in (("Heading 1", 16), ("Heading 2", 13)):
        style = doc.styles[style_name]
        style.font.name = font
        style.font.size = Pt(size)
        style.font.color.rgb = _rgb(accent)
        style.font.bold = True
    section = doc.sections[0]
    if cover == "book":
        for margin in ("top_margin", "bottom_margin", "left_margin", "right_margin"):
            setattr(section, margin, Cm(0))
        _book_cover_docx(doc, plan)
        content = doc.add_section(WD_SECTION.NEW_PAGE)
        content.top_margin = Cm(1.6)
        content.bottom_margin = Cm(1.6)
        content.left_margin = Cm(1.7)
        content.right_margin = Cm(1.7)
        _book_metrics_docx(doc, plan, font, accent)
    elif cover == "growth":
        for margin in ("top_margin", "bottom_margin", "left_margin", "right_margin"):
            setattr(section, margin, Cm(0))
        _growth_cover_docx(doc, plan)
        content = doc.add_section(WD_SECTION.NEW_PAGE)
        content.top_margin = Cm(1.6)
        content.bottom_margin = Cm(1.6)
        content.left_margin = Cm(1.7)
        content.right_margin = Cm(1.7)
    else:
        section.top_margin = Cm(1.6)
        section.bottom_margin = Cm(1.6)
        section.left_margin = Cm(1.7)
        section.right_margin = Cm(1.7)
        _cover(doc, plan, font, accent)
    for number, title, blocks in plan["sections"]:
        heading = doc.add_heading(title if number == 0 else f"{number:02d}    {title}", level=1)
        heading.paragraph_format.space_before = Pt(16)
        heading.paragraph_format.space_after = Pt(6)
        for block in blocks:
            _docx_block(doc, block, font, accent)
    buffer = BytesIO()
    doc.save(buffer)
    return buffer.getvalue()


def _tight(paragraph) -> None:
    paragraph.paragraph_format.space_before = Pt(0)
    paragraph.paragraph_format.space_after = Pt(0)
    paragraph.paragraph_format.line_spacing = 1


def _book_cover_docx(doc: Document, plan: dict) -> None:
    paragraph = doc.add_paragraph()
    _tight(paragraph)
    run = paragraph.add_run()
    run.add_picture(BytesIO(_book_cover_png(plan)), width=Cm(21), height=Cm(29.7))


def _book_metrics_docx(doc: Document, plan: dict, font: str, accent: str) -> None:
    heading = doc.add_heading("Ключевые показатели", level=1)
    heading.paragraph_format.space_before = Pt(0)
    table = doc.add_table(2, 2)
    table.autofit = False
    kpis = _book_kpis(plan)
    for index, (label, value) in enumerate(kpis):
        cell = table.cell(index // 2, index % 2)
        cell.text = ""
        name = cell.paragraphs[0]
        name_run = name.add_run(label)
        name_run.font.name = font
        name_run.font.size = Pt(11)
        name_run.font.color.rgb = _rgb("5C6778")
        value_paragraph = cell.add_paragraph()
        value_run = value_paragraph.add_run(value)
        value_run.bold = True
        value_run.font.name = font
        value_run.font.size = Pt(16)
        value_run.font.color.rgb = _rgb(accent)
    _lock_table(table, [8.8, 8.8])
    doc.add_paragraph()
    bars = _book_bars_png(plan)
    if bars:
        doc.add_picture(BytesIO(bars), width=Cm(16.5))
    mix = _book_mix_png(plan)
    if mix:
        doc.add_picture(BytesIO(mix), width=Cm(16.5))
    note = doc.add_paragraph("График собран из годовой сверки заявки.")
    note.runs[0].font.size = Pt(10)
    note.runs[0].font.color.rgb = _rgb("6D665C")
    doc.add_page_break()


def _cover(doc: Document, plan: dict, font: str, accent: str) -> None:
    facts = plan["facts"]
    if plan["cover"] == "investor":
        _shade(_run_paragraph(doc, " ", font, 8, "FFFFFF", after=0), accent)
        for text, size, bold in (
            ("NC-Decision", 11, True),
            ("БИЗНЕС-ПЛАН", 12, False),
            (facts["name"], 26, True),
            (facts["financing"], 16, True),
            (f"{facts['legal']}  ·  {facts['start']} — {facts['end']}", 12, False),
            (f"Стоимость проекта {_sign_money(facts['sign'], facts['investment'])}. Привлечено {_sign_money(facts['sign'], facts['attracted_total'])}.", 12, False),
            ("Конфиденциально", 11, False),
        ):
            paragraph = _run_paragraph(doc, text, font, size, "FFFFFF", bold, after=0)
            _shade(paragraph, accent)
        _shade(_run_paragraph(doc, " ", font, 8, "FFFFFF", after=8), accent)
        return
    if plan["cover"] == "brief":
        bar = _run_paragraph(doc, plan["style_name"] or "Бизнес-план", font, 11, accent, True, after=4)
        _edge(bar, "top", accent, "24")
        _run_paragraph(doc, facts["name"], font, 26, "1C1915", True, before=8, after=4)
        _run_paragraph(doc, f"{facts['legal']}  ·  {facts['financing']}  ·  {facts['start']} — {facts['end']}", font, 12, "134E4A", after=4)
        _run_paragraph(doc, f"Стоимость проекта {_sign_money(facts['sign'], facts['investment'])}. Привлечено {_sign_money(facts['sign'], facts['attracted_total'])}.", font, 12, "134E4A", after=4)
        _run_paragraph(doc, "Конфиденциально", font, 11, "6D665C", after=8)
        return
    for text, size, bold, color in (
        ("NC-Decision", 11, True, accent),
        ("БИЗНЕС-ПЛАН", 12, False, accent),
        (facts["name"], 28, True, accent),
        (f"{facts['legal']}  ·  {facts['financing']}  ·  {facts['start']} — {facts['end']}", 12, False, "6D665C"),
        (f"Стоимость проекта {_sign_money(facts['sign'], facts['investment'])}. Привлечено {_sign_money(facts['sign'], facts['attracted_total'])}.", 12, False, "6D665C"),
        ("Конфиденциально", 11, False, "6D665C"),
    ):
        paragraph = _run_paragraph(doc, text, font, size, color, bold, after=3)
        _edge(paragraph, "left", accent, "24")


def _body_paragraph(paragraph, look: dict | None):
    if look and look.get("justify"):
        paragraph.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        paragraph.paragraph_format.first_line_indent = Cm(1.25)
        paragraph.paragraph_format.line_spacing = 1.15
    return paragraph


def _docx_block(doc: Document, block: tuple, font: str, accent: str, look: dict | None = None) -> None:
    kind = block[0]
    size = (look or {}).get("size", 11)
    if kind == "p":
        _body_paragraph(_run_paragraph(doc, block[1], font, size, "1C1915", after=6), look)
    elif kind == "rich":
        paragraph = _body_paragraph(doc.add_paragraph(), look)
        paragraph.paragraph_format.space_before = Pt(0)
        paragraph.paragraph_format.space_after = Pt(6)
        for text, bold in block[1]:
            _paint(paragraph.add_run(_plain(text)), font, size, "1C1915", bold)
    elif kind == "warn":
        _body_paragraph(_run_paragraph(doc, block[1], font, size, "9D2222", True, after=6), look)
    elif kind == "miss":
        _body_paragraph(_run_paragraph(doc, block[1], font, size, "6D665C", after=6), look)
    elif kind == "h":
        paragraph = doc.add_heading(block[1], level=2)
        paragraph.paragraph_format.space_before = Pt(10)
        paragraph.paragraph_format.space_after = Pt(4)
    elif kind == "image":
        data, ratio, caption = block[1], block[2], block[3]
        width = min(16.0, 18.0 / ratio)
        paragraph = doc.add_paragraph()
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        paragraph.paragraph_format.space_before = Pt(6)
        paragraph.paragraph_format.space_after = Pt(2 if caption else 8)
        paragraph.add_run().add_picture(BytesIO(data), width=Cm(width))
        if caption:
            note = _run_paragraph(doc, caption, font, 10, "6D665C", after=8)
            note.alignment = WD_ALIGN_PARAGRAPH.CENTER
    elif kind == "table":
        headers, rows = block[1], block[2]
        look = look or {}
        cell_size = look.get("cell", 8)
        table = doc.add_table(rows=1 + len(rows), cols=len(headers))
        table.style = "Table Grid"
        usable = look.get("usable", 17.6)
        shares = block[3] if len(block) > 3 and block[3] and len(block[3]) == len(headers) else None
        _lock_table(table, [usable * share for share in shares] if shares else _col_widths(usable, len(headers)))
        for index, header in enumerate(headers):
            _cell(table.rows[0].cells[index], header, font, cell_size, True, look.get("head_fill", accent), look.get("head_color", "FFFFFF"))
        tr_pr = table.rows[0]._tr.get_or_add_trPr()
        repeat = OxmlElement("w:tblHeader")
        tr_pr.append(repeat)
        for r_index, row in enumerate(rows, start=1):
            fill = "F7F8FB" if r_index % 2 == 0 and look.get("zebra", True) else "FFFFFF"
            padded = list(row) + [""] * (len(headers) - len(row))
            for c_index, value in enumerate(padded[: len(headers)]):
                _cell(table.rows[r_index].cells[c_index], value, font, cell_size, False, fill, "1C1915")
        doc.add_paragraph()


def _dxa(parent, tag: str, value: int) -> None:
    node = parent.find(qn(tag))
    if node is None:
        node = OxmlElement(tag)
        parent.append(node)
    node.set(qn("w:w"), str(value))
    node.set(qn("w:type"), "dxa")


def _lock_table(table, widths: list[float]) -> None:
    table.autofit = False
    tbl_pr = table._tbl.tblPr
    layout = tbl_pr.find(qn("w:tblLayout"))
    if layout is None:
        layout = OxmlElement("w:tblLayout")
        tbl_pr.append(layout)
    layout.set(qn("w:type"), "fixed")
    _dxa(tbl_pr, "w:tblW", int(sum(widths) * 567))
    grid = table._tbl.find(qn("w:tblGrid"))
    if grid is None:
        grid = OxmlElement("w:tblGrid")
        tbl_pr.addnext(grid)
    for child in list(grid):
        grid.remove(child)
    for width in widths:
        column = OxmlElement("w:gridCol")
        column.set(qn("w:w"), str(int(width * 567)))
        grid.append(column)
    for row in table.rows:
        for cell, width in zip(row.cells, widths):
            _dxa(cell._tc.get_or_add_tcPr(), "w:tcW", int(width * 567))


def _cell(cell, text: str, font: str, size: int, bold: bool, fill: str, color: str) -> None:
    cell.text = ""
    paragraph = cell.paragraphs[0]
    paragraph.paragraph_format.space_before = Pt(0)
    paragraph.paragraph_format.space_after = Pt(0)
    _paint(paragraph.add_run(_plain(text)), font, size, color, bold)
    shade = OxmlElement("w:shd")
    shade.set(qn("w:val"), "clear")
    shade.set(qn("w:color"), "auto")
    shade.set(qn("w:fill"), fill)
    cell._tc.get_or_add_tcPr().append(shade)


def _run_paragraph(doc, text: str, font: str, size: int, color: str, bold: bool = False, before: int = 0, after: int = 6):
    paragraph = doc.add_paragraph()
    paragraph.paragraph_format.space_before = Pt(before)
    paragraph.paragraph_format.space_after = Pt(after)
    _paint(paragraph.add_run(_plain(text)), font, size, color, bold)
    return paragraph


def _paint(run, name: str, size: int, color: str | None, bold: bool) -> None:
    run.bold = bold
    run.font.name = name
    run.font.size = Pt(size)
    if color:
        run.font.color.rgb = _rgb(color)
    fonts = run._element.get_or_add_rPr().get_or_add_rFonts()
    fonts.set(qn("w:ascii"), name)
    fonts.set(qn("w:hAnsi"), name)
    fonts.set(qn("w:cs"), name)


def _shade(paragraph, fill: str) -> None:
    shade = OxmlElement("w:shd")
    shade.set(qn("w:val"), "clear")
    shade.set(qn("w:color"), "auto")
    shade.set(qn("w:fill"), fill)
    paragraph._p.get_or_add_pPr().append(shade)


def _edge(paragraph, edge: str, color: str, size: str) -> None:
    borders = OxmlElement("w:pBdr")
    line = OxmlElement(f"w:{edge}")
    line.set(qn("w:val"), "single")
    line.set(qn("w:sz"), size)
    line.set(qn("w:space"), "4")
    line.set(qn("w:color"), color)
    borders.append(line)
    paragraph._p.get_or_add_pPr().append(borders)


def _rgb(value: str) -> RGBColor:
    return RGBColor(int(value[0:2], 16), int(value[2:4], 16), int(value[4:6], 16))


def _plain(value) -> str:
    text = str("" if value is None else value)
    return "".join(ch if ch in "\n\t" or ord(ch) >= 32 else " " for ch in text)


class _Pdf(FPDF):
    def __init__(self, plan: dict):
        super().__init__(format="A4", unit="mm")
        self.plan = plan
        serif = plan["cover"] in ("classic", "mosaic")
        self.add_font("Body", "", SERIF if serif else SANS)
        self.add_font("Body", "B", SERIF_BOLD if serif else SANS_BOLD)
        self.set_auto_page_break(True, 18)
        self.accent = _pdf_color(plan["accent"])
        mosaic = plan["cover"] == "mosaic"
        self.head_fill = _pdf_color(MOSAIC_HEAD) if mosaic else self.accent
        self.head_text = (0, 0, 0) if mosaic else (255, 255, 255)
        self.grid = (0, 0, 0) if mosaic else (220, 224, 232)
        self.zebra = not mosaic
        self.alias_nb_pages()

    def header(self):
        if self.plan.get("cover") != "book" or self.page_no() == 1:
            return
        self.set_xy(16, 8)
        self.set_font("Body", "", 9)
        self.set_text_color(*BOOK)
        self.cell(80, 5, "NC-CONSULTING")
        self.set_xy(16, 8)
        self.cell(self.epw, 5, f"{self.page_no():02d}", align="R")
        self.set_draw_color(*BOOK)
        self.set_line_width(0.3)
        self.line(16, 15, 194, 15)

    def footer(self):
        if self.plan.get("cover") in ("book", "mosaic", "growth") and self.page_no() == 1:
            return
        if self.plan.get("cover") == "mosaic":
            self.set_y(-14)
            self.set_font("Body", "", 10)
            self.set_text_color(0, 0, 0)
            self.cell(0, 6, str(self.page_no()), align="C")
            return
        self.set_y(-12)
        self.set_font("Body", "", 8)
        self.set_text_color(109, 102, 92)
        label = "NC-CONSULTING" if self.plan.get("cover") == "book" else self.plan["facts"]["name"]
        self.cell(0, 6, f"{label}  ·  {self.page_no()} / {{nb}}", align="C")


def _pdf_write(pdf: _Pdf, height: float, text: str, size: int = 10, bold: bool = False, color: tuple = (28, 25, 21)) -> None:
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Body", "B" if bold else "", size)
    pdf.set_text_color(*color)
    pdf.multi_cell(pdf.epw, height, _plain(text), new_x=XPos.LMARGIN, new_y=YPos.NEXT)


def render_pdf(plan: dict) -> bytes:
    if plan["cover"] == "mosaic":
        from app.bp_mosaic import render_mosaic_pdf

        return render_mosaic_pdf(plan)
    pdf = _Pdf(plan)
    book = plan["cover"] == "book"
    pdf.set_margins(16 if book else 14, 20 if book else 14, 16 if book else 14)
    pdf.add_page()
    if book:
        _pdf_book_cover(pdf, plan)
        pdf.add_page()
        _pdf_book_metrics(pdf, plan)
    elif plan["cover"] == "growth":
        _pdf_growth_cover(pdf, plan)
        pdf.add_page()
    else:
        _pdf_cover(pdf, plan)
    for number, title, blocks in plan["sections"]:
        pdf.ln(4)
        _pdf_write(pdf, 7, title if number == 0 else f"{number:02d}    {title}", 14, True, pdf.accent)
        pdf.ln(1)
        for block in blocks:
            _pdf_block(pdf, block)
    return bytes(pdf.output())


def _pdf_place(pdf: _Pdf, png: bytes, ratio: float) -> None:
    height = pdf.epw * ratio
    if pdf.get_y() + height > pdf.page_break_trigger:
        pdf.add_page()
    y = pdf.get_y()
    pdf.image(BytesIO(png), x=pdf.l_margin, y=y, w=pdf.epw, h=height)
    pdf.set_xy(pdf.l_margin, y + height + 3)


def _pdf_picture(pdf: _Pdf, data: bytes, ratio: float, caption: str) -> None:
    width = min(pdf.epw, 180 / ratio, 160)
    height = width * ratio
    room = height + (8 if caption else 3)
    if pdf.get_y() + room > pdf.page_break_trigger:
        pdf.add_page()
    y = pdf.get_y() + 2
    pdf.image(BytesIO(data), x=pdf.l_margin + (pdf.epw - width) / 2, y=y, w=width, h=height)
    pdf.set_xy(pdf.l_margin, y + height + 2)
    if caption:
        pdf.set_font("Body", "", 9)
        pdf.set_text_color(109, 102, 92)
        pdf.multi_cell(pdf.epw, 4.5, _plain(caption), align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(2)


def _pdf_growth_cover(pdf: _Pdf, plan: dict) -> None:
    pdf.set_auto_page_break(False)
    pdf.image(BytesIO(_growth_cover_png(plan)), x=0, y=0, w=210, h=297)
    pdf.set_auto_page_break(True, 18)


def _pdf_book_cover(pdf: _Pdf, plan: dict) -> None:
    pdf.set_auto_page_break(False)
    pdf.image(BytesIO(_book_cover_png(plan)), x=0, y=0, w=210, h=297)
    pdf.set_auto_page_break(True, 18)


def _pdf_book_metrics(pdf: _Pdf, plan: dict) -> None:
    _pdf_write(pdf, 8, "Ключевые показатели", 18, True, BOOK)
    pdf.ln(2)
    kpis = _book_kpis(plan)
    box_w = (pdf.epw - 4) / 2
    box_h = 22
    start_y = pdf.get_y()
    for index, (label, value) in enumerate(kpis):
        column = index % 2
        row = index // 2
        x = pdf.l_margin + column * (box_w + 4)
        y = start_y + row * (box_h + 4)
        pdf.set_fill_color(244, 247, 251)
        pdf.set_draw_color(220, 226, 234)
        pdf.rect(x, y, box_w, box_h, "FD")
        pdf.set_xy(x + 3, y + 3)
        pdf.set_font("Body", "", 9)
        pdf.set_text_color(92, 103, 120)
        pdf.cell(box_w - 6, 5, _plain(label))
        pdf.set_xy(x + 3, y + 10)
        pdf.set_font("Body", "B", 12)
        pdf.set_text_color(*BOOK)
        pdf.cell(box_w - 6, 7, _plain(value))
    pdf.set_xy(pdf.l_margin, start_y + 2 * (box_h + 4))
    bars = _book_bars_png(plan)
    if bars:
        _pdf_place(pdf, bars, 560 / 1400)
    mix = _book_mix_png(plan)
    if mix:
        _pdf_place(pdf, mix, 280 / 1400)
    _pdf_write(pdf, 5, "График собран из годовой сверки заявки.", 9, False, (109, 102, 92))
    pdf.add_page()


def _pdf_cover(pdf: _Pdf, plan: dict) -> None:
    facts = plan["facts"]
    accent = pdf.accent
    if plan["cover"] == "investor":
        pdf.set_font("Body", "B", 22)
        name_lines = pdf.multi_cell(pdf.epw, 9, _plain(facts["name"]), dry_run=True, output="LINES", new_x=XPos.LMARGIN, new_y=YPos.NEXT) or [""]
        block_h = 54 + 9 * len(name_lines)
        pdf.set_fill_color(*accent)
        pdf.rect(0, 0, 210, block_h, "F")
        pdf.set_xy(14, 14)
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Body", "B", 11)
        pdf.cell(0, 6, "NC-Decision")
        pdf.ln(7)
        pdf.set_font("Body", "", 12)
        pdf.cell(0, 6, "БИЗНЕС-ПЛАН")
        pdf.ln(8)
        _pdf_write(pdf, 9, facts["name"], 22, True, (255, 255, 255))
        _pdf_write(pdf, 8, facts["financing"], 14, True, (255, 255, 255))
        pdf.set_xy(14, block_h + 6)
    else:
        pdf.set_text_color(*accent)
        pdf.set_font("Body", "B", 11)
        pdf.cell(0, 6, "NC-Decision" if plan["cover"] == "classic" else (plan["style_name"] or "БИЗНЕС-ПЛАН"))
        pdf.ln(8)
        _pdf_write(pdf, 9, facts["name"], 22, True, accent)
        pdf.ln(1)
    gray = (80, 74, 66)
    _pdf_write(pdf, 6, f"{facts['legal']}  ·  {facts['financing']}", 11, False, gray)
    _pdf_write(pdf, 6, f"{facts['start']} — {facts['end']}", 11, False, gray)
    _pdf_write(pdf, 6, f"Стоимость проекта {_sign_money(facts['sign'], facts['investment'])}. Привлечено {_sign_money(facts['sign'], facts['attracted_total'])}.", 11, False, gray)
    _pdf_write(pdf, 6, f"Документ от {plan['prepared']}. Конфиденциально.", 11, False, gray)
    pdf.ln(2)


def _pdf_rich(pdf: _Pdf, spans: list[tuple[str, bool]], size: int = 10, color: tuple = (28, 25, 21)) -> None:
    pdf.set_x(pdf.l_margin)
    pdf.set_text_color(*color)
    for text, bold in spans:
        pdf.set_font("Body", "B" if bold else "", size)
        pdf.write(5, _plain(text))
    pdf.ln(6)


def _pdf_block(pdf: _Pdf, block: tuple) -> None:
    kind = block[0]
    if kind == "rich":
        _pdf_rich(pdf, block[1])
        return
    if kind in ("p", "warn", "miss"):
        color = (157, 34, 34) if kind == "warn" else ((109, 102, 92) if kind == "miss" else (28, 25, 21))
        _pdf_write(pdf, 5, block[1], 10, kind == "warn", color)
        pdf.ln(1)
        return
    if kind == "h":
        pdf.ln(1)
        _pdf_write(pdf, 6, block[1], 12, True, pdf.accent)
        return
    if kind == "image":
        _pdf_picture(pdf, block[1], block[2], block[3])
        return
    headers, rows = block[1], block[2]
    _pdf_table(pdf, headers, rows, block[3] if len(block) > 3 else None)


def _col_widths(total: float, count: int) -> list[float]:
    if count <= 1:
        return [total]
    if count == 2:
        return [total * 0.46, total * 0.54]
    if count == 7:
        name = total * 0.2
        rest = (total - name) / 6
        return [name, *([rest] * 6)]
    first = total * 0.36
    rest = (total - first) / (count - 1)
    return [first, *([rest] * (count - 1))]


def _pdf_table(pdf: _Pdf, headers: list[str], rows: list[list[str]], shares: list[float] | None = None) -> None:
    if shares and len(shares) == len(headers):
        widths = [pdf.epw * share for share in shares]
    else:
        widths = _col_widths(pdf.epw, len(headers))
    header_cells = [_plain(item) for item in headers]

    def paint_header() -> None:
        _pdf_row(pdf, header_cells, widths, bold=True, fill=pdf.head_fill, color=pdf.head_text)

    paint_header()
    for index, row in enumerate(rows):
        cells = [_plain(value) for value in row]
        if len(cells) < len(headers):
            cells.extend([""] * (len(headers) - len(cells)))
        height, _wrapped = _pdf_measure(pdf, cells[: len(headers)], widths, bold=False)
        if pdf.get_y() + height > pdf.page_break_trigger:
            pdf.add_page()
            paint_header()
        fill = (247, 248, 251) if index % 2 and pdf.zebra else (255, 255, 255)
        _pdf_row(pdf, cells[: len(headers)], widths, fill=fill, color=(28, 25, 21))
    pdf.ln(2)


def _wrap_lines(pdf: _Pdf, text: str, width: float) -> list[str]:
    usable = max(width - 1.8, 4)
    lines = pdf.multi_cell(usable, 3.6, text or "—", dry_run=True, output="LINES", new_x=XPos.LMARGIN, new_y=YPos.NEXT) or ["—"]
    safe: list[str] = []
    for line in lines:
        if pdf.get_string_width(line) <= usable:
            safe.append(line)
            continue
        chunk = ""
        for char in line:
            if chunk and pdf.get_string_width(chunk + char) > usable:
                safe.append(chunk)
                chunk = char
            else:
                chunk += char
        if chunk:
            safe.append(chunk)
    return safe or ["—"]


def _pdf_measure(pdf: _Pdf, cells: list[str], widths: list[float], bold: bool) -> tuple[float, list[list[str]]]:
    pdf.set_font("Body", "B" if bold else "", 8)
    wrapped = [_wrap_lines(pdf, text, width) for text, width in zip(cells, widths)]
    height = max(7, max(len(lines) for lines in wrapped) * 3.6 + 1.6)
    return height, wrapped


def _pdf_row(pdf: _Pdf, cells: list[str], widths: list[float], bold: bool = False, fill: tuple = (255, 255, 255), color: tuple = (28, 25, 21)) -> None:
    height, wrapped = _pdf_measure(pdf, cells, widths, bold)
    if pdf.get_y() + height > pdf.page_break_trigger:
        pdf.add_page()
    pdf.set_font("Body", "B" if bold else "", 8)
    pdf.set_text_color(*color)
    pdf.set_fill_color(*fill)
    pdf.set_draw_color(*pdf.grid)
    pdf.set_auto_page_break(False)
    x0, y0 = pdf.l_margin, pdf.get_y()
    x = x0
    for lines, width in zip(wrapped, widths):
        pdf.rect(x, y0, width, height, "FD")
        for index, line in enumerate(lines):
            pdf.set_xy(x + 0.8, y0 + 0.8 + index * 3.6)
            pdf.cell(max(width - 1.6, 2), 3.6, line)
        x += width
    pdf.set_auto_page_break(True, 18)
    pdf.set_xy(x0, y0 + height)


def _pdf_color(value: str) -> tuple[int, int, int]:
    return (int(value[0:2], 16), int(value[2:4], 16), int(value[4:6], 16))
