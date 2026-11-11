"""ФЭМ версии 5. Листы как в «ФЭМ Радуга здоровья (----new).xlsx».

Инфляция — годовая инфляция из API НБРК. Если API молчит, берётся FEM_INFLATION. Рост зарплаты 5%.
Ставка дисконтирования — WACC: базовая ставка НБРК, ставка кредита из анкеты и налог.
Курсы — с того же адреса Нацбанка, что в старом кабинете, на сегодняшний день по Алматы.
"""

from __future__ import annotations

import json
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from datetime import date, datetime, timedelta
from io import BytesIO
from zoneinfo import ZoneInfo

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter

from app.config import Settings
from app.models import User
from app.project_form import UNITS

SHEET = "Предпосылки"
FIN = "Структура финансирования"
REV = "План доходов"
MONTHS = "Месяцы"
COST = "Себестоимость"
PAY = "ФОТ"
ADM = "Адм расходы"
AMORT = "Амортизация"
CAPEX = "Реестр CAPEX"
SCHED = "График платежей"
PNL = "ОПиУ"
CF = "ДДС"
EFFECT = "Анализ эффект."
QTY = '#,##0.00'
NBK_RATES_URL = "https://nationalbank.kz/rss/get_rates.cfm"
NBK_TZ = ZoneInfo("Asia/Almaty")
NBK_CODES = ("RUB", "EUR", "USD")
YEAR_COLUMN = 5
FONT_NAME = "Century Gothic"
KEYS = (
    "smr",
    "overhaul",
    "ready_premises",
    "equipment",
    "raw",
    "advertising",
    "wages",
    "rental",
    "other",
)
# Ставки НК РК с 01.01.2026: КПН 10%/20%, СНР упрощёнка 4% (маслихат ±50% не моделируется).
TAX_RATES = {
    1: {1: 0.10, 2: 0.01, 3: 0.01, 4: 0.04},
    2: {1: 0.20, 2: 0.01, 3: 0.01, 4: 0.04},
}
VAT_RATE_2026 = 0.16
VOSMS_RATE = 0.02
SOCIAL_TAX_2026 = 0.06

PCT = '0.0%;[Red]\\-0.0%'
PCT_FINE = '0.00%;[Red]\\-0.00%'
PCT_WHOLE = '0%;[Red]\\-0%'
# Деньги и цены из анкеты могут быть с тиынами (82,50 ₸/кг) — как precision:3 в старом кабинете.
MONEY = '#,##0.00'

PURPLE = PatternFill("solid", fgColor="EAE1FC")
GREEN = PatternFill("solid", fgColor="D8E4BC")
BLUE = PatternFill("solid", fgColor="DAEEF3")
GRAY = PatternFill("solid", fgColor="D9D9D9")
PINK = PatternFill("solid", fgColor="F4BEB0")
BLACK = Side(style="thin", color="000000")
GREY_SIDE = Side(style="thin", color="808080")
BOX = Border(left=BLACK, right=BLACK, top=BLACK, bottom=BLACK)
GREY_BOX = Border(left=GREY_SIDE, right=GREY_SIDE, top=GREY_SIDE, bottom=GREY_SIDE)

TEXT = Font(name=FONT_NAME, size=10, color="000000")
SECTION = Font(name=FONT_NAME, size=10, bold=True, color="44546A")
HEADER = Font(name=FONT_NAME, size=10, color="000000")


def _num(value) -> float:
    try:
        return float(value or 0)
    except (TypeError, ValueError):
        return 0.0


def _is_land_expense(item: dict) -> bool:
    name = str(item.get("name") or "").strip().lower()
    return (
        bool(item.get("land_tax"))
        or _num(item.get("land_area")) > 0
        or _num(item.get("land_rate")) > 0
        or "земельн" in name
    )


def _expense_title(item: dict, fallback: str = "Расход") -> str:
    """Обрывки вроде «н» в Excel не пишем — для земли подставляем нормальное имя."""
    name = str(item.get("name") or "").strip()
    if _is_land_expense(item):
        return name if len(name) >= 3 else "Земельный налог"
    return name if len(name) >= 2 else fallback


def _expense_amount(item: dict) -> float:
    value = _num(item.get("value"))
    if value > 0:
        return value
    if _is_land_expense(item):
        return _num(item.get("land_area")) * _num(item.get("land_rate"))
    return 0.0


def _rate(value) -> float:
    number = _num(value)
    return number / 100 if number > 1 else number


def _interest_shield(legal: int, regime: int) -> float:
    """Щит только у налога на прибыль. Упрощёнка и спецрежимы считаются с дохода."""
    if int(regime) != 1:
        return 0.0
    return TAX_RATES.get(int(legal) if int(legal) in TAX_RATES else 1, TAX_RATES[1]).get(1, 0.0)


def _year(value) -> int:
    return int(str(value or "2027")[:4])


def _planning_years(form: dict) -> int:
    """Сколько календарных лет от начала до конца плана. Конец не короче срока инвестора и кредита."""
    start = _year(form.get("planning_from"))
    end = _horizon(form)[0].year
    if end < start:
        end = start
    return end - start + 1


def _year_month(value) -> tuple[int, int]:
    year, month = str(value or "2027-01")[:7].split("-")
    return int(year), int(month)


def _selling_months(form: dict, year: int, season: set[int] | None = None) -> int:
    """Месяцы продаж внутри года: окно плана от начала продаж и, если задан, сезон."""
    sales_year, sales_month = _year_month(form.get("sales_start") or form.get("planning_from"))
    end_year, end_month = _horizon(form)[0].year, _horizon(form)[0].month
    plan_year, plan_month = _year_month(form.get("planning_from"))
    if (sales_year, sales_month) < (plan_year, plan_month):
        sales_year, sales_month = plan_year, plan_month
    if (end_year, end_month) < (sales_year, sales_month):
        end_year, end_month = sales_year, sales_month
    if year < sales_year or year > end_year:
        return 0
    first = sales_month if year == sales_year else 1
    last = end_month if year == end_year else 12
    count = 0
    for month in range(first, last + 1):
        if season and month not in season:
            continue
        count += 1
    return count


def _capacity(form: dict, years: list[int]) -> list[float]:
    """Доля загрузки. Пустые годы доводятся до 100%. Если не заполнено ничего, рост от старта продаж до года полного объёма."""
    raw = form.get("planned_capacity") or {}
    known: dict[int, float] = {}
    for year in years:
        if str(year) not in raw and year not in raw:
            continue
        number = _num(raw.get(str(year), raw.get(year)))
        known[year] = number / 100 if number > 1 else number
    if not any(value > 0 for value in known.values()):
        sales_year = _year_month(form.get("sales_start") or form.get("planning_from"))[0]
        full_year = _year_month(form.get("planned_volume") or form.get("planning_to"))[0]
        span = max(1, full_year - sales_year + 1)
        loads = []
        for year in years:
            if year < sales_year:
                loads.append(0.0)
            elif year >= full_year:
                loads.append(1.0)
            else:
                loads.append((year - sales_year + 1) / span)
        return loads
    loads = []
    for index, year in enumerate(years):
        if year in known:
            loads.append(known[year])
            continue
        previous = next((item for item in range(index - 1, -1, -1) if years[item] in known), None)
        following = next((item for item in range(index + 1, len(years)) if years[item] in known), None)
        if previous is None and following is None:
            loads.append(1.0)
        elif previous is None:
            loads.append(known[years[following]] * (index + 1) / (following + 1))
        elif following is None:
            start = known[years[previous]]
            steps = len(years) - 1 - previous
            loads.append(start + (1 - start) * (index - previous) / steps if steps else start)
        else:
            left = known[years[previous]]
            right = known[years[following]]
            loads.append(left + (right - left) * (index - previous) / (following - previous))
    return loads


def _entered_capacity(form: dict, years: list[int]) -> list[float | None]:
    """Только то, что клиент сам вписал. Нулевая анкета остаётся пустой, и загрузку считает формула."""
    raw = form.get("planned_capacity") or {}
    parsed: list[float | None] = []
    for year in years:
        if str(year) not in raw and year not in raw:
            parsed.append(None)
            continue
        number = _num(raw.get(str(year), raw.get(year)))
        if abs(number) > 1:
            number = number / 100
        parsed.append(number)
    saved = [value for value in parsed if value is not None]
    if len(saved) == len(years) and all(value == 0 for value in saved):
        return [None] * len(years)
    return parsed


def _months_in_year(form: dict, year: int) -> int:
    """Месяцы плана внутри года: с месяца старта до декабря, полный год или до месяца окончания."""
    start_year, start_month = _year_month(form.get("planning_from"))
    end_year, end_month = _year_month(form.get("planning_to"))
    if (end_year, end_month) < (start_year, start_month):
        end_year, end_month = start_year, start_month
    if year < start_year or year > end_year:
        return 0
    first = start_month if year == start_year else 1
    last = end_month if year == end_year else 12
    return last - first + 1


def _form_date(value) -> date:
    year, month = _year_month(value)
    return date(year, month, 1)


def _shift_month(year: int, month: int, delta: int) -> tuple[int, int]:
    index = year * 12 + (month - 1) + delta
    return index // 12, index % 12 + 1


def _horizon(form: dict) -> tuple[date, str]:
    """Конец плана = max(дата анкеты, начало + срок инвестора, начало + срок кредита)."""
    start = _form_date(form.get("planning_from"))
    asked = _form_date(form.get("planning_to"))
    if (asked.year, asked.month) < (start.year, start.month):
        asked = start
    end = asked
    reasons: list[str] = []
    financing = int(form.get("financing") or 1)
    if financing in (1, 4):
        years = int(_num(form.get("investor_term")))
        if years > 0:
            year, month = _shift_month(start.year, start.month, years * 12)
            point = date(year, month, 1)
            if (point.year, point.month) > (end.year, end.month):
                end = point
                reasons.append(f"срок инвестора {years} лет, выход {point.strftime('%m.%Y')}")
    if financing in (2, 4):
        _, loan_term, _ = _loan_terms(form)
        wc_term = int(_num(form.get("wc_term"))) if _gov_blocks_wc(form) else 0
        months = max(loan_term, wc_term)
        if months > 0:
            year, month = _shift_month(start.year, start.month, months)
            point = date(year, month, 1)
            if (point.year, point.month) > (end.year, end.month):
                end = point
                reasons.append(f"срок кредита {months} мес., последний платёж {point.strftime('%m.%Y')}")
    if not reasons:
        return end, "Как в анкете."
    asked_text = asked.strftime("%m.%Y")
    return end, "Горизонт продлён до " + end.strftime("%m.%Y") + f": в анкете конец {asked_text}, " + "; ".join(reasons) + ". Иначе выход или последний платёж не попадает в расчёт."


def _months_formula(year_cell: str, start_cell: str, end_cell: str, season: set[int] | None = None) -> str:
    """Сколько месяцев года попадает в окно от start_cell до end_cell."""
    if season:
        parts = []
        for month in range(1, 13):
            if month not in season:
                continue
            parts.append(
                f"IF(AND(DATE({year_cell},{month},1)>=DATE(YEAR({start_cell}),MONTH({start_cell}),1),"
                f"DATE({year_cell},{month},1)<=DATE(YEAR({end_cell}),MONTH({end_cell}),1)),1,0)"
            )
        return "=" + "+".join(parts) if parts else "=0"
    return (
        f"=IF(OR({year_cell}<YEAR({start_cell}),{year_cell}>YEAR({end_cell})),0,"
        f"MAX(0,IF({year_cell}=YEAR({end_cell}),MONTH({end_cell}),12)"
        f"-IF({year_cell}=YEAR({start_cell}),MONTH({start_cell}),1)+1))"
    )


def _load_formula(year_cell: str, sales_cell: str, full_cell: str, entered_cell: str) -> str:
    """Пустая загрузка из анкеты: рост от года начала продаж до 100% в году выхода на объём."""
    span = f"(YEAR({full_cell})-YEAR({sales_cell})+1)"
    ramp = (
        f"IF({year_cell}<YEAR({sales_cell}),0,IF({year_cell}>=YEAR({full_cell}),1,"
        f"({year_cell}-YEAR({sales_cell})+1)/{span}))"
    )
    return f'=IF({entered_cell}="",{ramp},{entered_cell})'


def _price_index(offset: int) -> str:
    """Накопленный индекс цен этого года / база C47. Столбец D строки 47 — первый год."""
    column = get_column_letter(4 + offset)
    return f"'{SHEET}'!{column}47/'{SHEET}'!$C$47"


def _cost_index(offset: int) -> str:
    """Накопленный индекс переменных затрат этого года / база C48."""
    column = get_column_letter(4 + offset)
    return f"'{SHEET}'!{column}48/'{SHEET}'!$C$48"


def _wage_index(offset: int) -> str:
    """Накопленный индекс зарплат этого года / база C50. Столбец D — первый год."""
    column = get_column_letter(4 + offset)
    return f"'{SHEET}'!{column}50/'{SHEET}'!$C$50"


def _fixed_index(offset: int) -> str:
    """Накопленный индекс постоянных затрат этого года / база C49. Столбец D — первый год."""
    column = get_column_letter(4 + offset)
    return f"'{SHEET}'!{column}49/'{SHEET}'!$C$49"


_CAPEX_GROUPS = (
    ("premises", "Помещения"),
    ("equipment", "Оборудование"),
    ("smr", "СМР"),
    ("overhaul", "Материалы"),
)


def _staff_industrial(row: dict) -> bool:
    """Производственный персонал — только флаг industrial из анкеты. Без отраслевых эвристик по названию."""
    return int(_num(row.get("industrial"))) == 1


def _capex_items(form: dict) -> list[dict]:
    """Капитальные активы анкеты. Оборотные запасы и операционные расходы сюда не входят."""
    items = []
    for key, title in _CAPEX_GROUPS:
        for raw in form.get(key) or []:
            price = _num(raw.get("price")) or _num(raw.get("price_one")) * _num(raw.get("count"))
            name = str(raw.get("name") or "").strip()
            if not name and price <= 0:
                continue
            year = int(_num(raw.get("year"))) or _year(form.get("planning_from"))
            life = max(int(_num(raw.get("life_years"))), 0)
            pay_month = str(raw.get("pay_month") or "").strip()[:7]
            commission_month = str(raw.get("commission_month") or "").strip()[:7]
            already = bool(raw.get("already_owned"))
            book = _num(raw.get("book_value"))
            if already and book <= 0:
                book = price
            # Если месяц не указан — не выдумываем январь: месяц старта плана в году покупки.
            if not pay_month and year:
                try:
                    plan_month = int(str(form.get("planning_from") or "2027-01")[5:7])
                except ValueError:
                    plan_month = 1
                plan_month = min(12, max(1, plan_month))
                pay_month = f"{year}-{plan_month:02d}"
            if not commission_month:
                commission_month = pay_month
            items.append(
                {
                    "name": name or title,
                    "category": title,
                    "price": price,
                    "year": year,
                    "life": life,
                    "pay_month": pay_month,
                    "commission_month": commission_month,
                    "already_owned": already,
                    "book_value": book,
                }
            )
    return items


def _month_date_literal(value: str) -> str | None:
    """DATE(y,m,1) из YYYY-MM или None."""
    text = str(value or "").strip()[:7]
    if len(text) != 7 or text[4] != "-":
        return None
    try:
        year = int(text[:4])
        month = int(text[5:])
    except ValueError:
        return None
    if month < 1 or month > 12:
        return None
    return f"DATE({year},{month},1)"


def _capex_date(year_cell: str, plan_start: str, plan_end: str) -> str:
    """Fallback только если месяц оплаты не задан в анкете.
    Не подставляет 1 января: берёт месяц начала плана в году покупки (или границы плана)."""
    return (
        f"IF({year_cell}<YEAR({plan_start}),{plan_start},"
        f"IF({year_cell}>YEAR({plan_end}),DATE(YEAR({plan_end}),MONTH({plan_end}),1),"
        f"IF({year_cell}=YEAR({plan_start}),{plan_start},"
        f"DATE({year_cell},MONTH({plan_start}),1))))"
    )


def _clamp_month(raw: str, plan_start: str, plan_end: str) -> str:
    """Явная дата месяца внутри горизонта плана."""
    return (
        f"IF({raw}<{plan_start},{plan_start},IF({raw}>{plan_end},"
        f"DATE(YEAR({plan_end}),MONTH({plan_end}),1),{raw}))"
    )


def _tax_rate(legal: int, regime: int, form: dict | None = None) -> float:
    """Ставка режима: из анкеты клиента, иначе справочник TAX_RATES."""
    if form:
        from app.fem_rates import resolve_tax_rate

        return resolve_tax_rate(form)
    table = TAX_RATES.get(int(legal) if int(legal) in TAX_RATES else 1, TAX_RATES[1])
    if int(regime) in table:
        return float(table[int(regime)])
    return float(table.get(1, 0.0))


def _form_pct(form: dict, key: str, fallback_fraction: float) -> float:
    """Процент из анкеты (4 или 0.04) → доля. Пусто → fallback."""
    from app.fem_rates import form_rate

    return form_rate(form, key, fallback_fraction * 100.0)


def _capex_purchase_index(year_cell: str, years: int) -> str:
    """Индекс строки 46 только для года покупки. Год раньше плана и год вне горизонта остаются 1."""
    year_from = get_column_letter(YEAR_COLUMN)
    year_to = get_column_letter(YEAR_COLUMN + years - 1)
    index_from = get_column_letter(4)
    index_to = get_column_letter(3 + years)
    year_range = f"'{SHEET}'!${year_from}$6:${year_to}$6"
    index_range = f"'{SHEET}'!${index_from}$46:${index_to}$46"
    return (
        f"IF({year_cell}<'{SHEET}'!{year_from}6,1,"
        f"IF(COUNTIF({year_range},{year_cell})=0,1,"
        f"SUMIF({year_range},{year_cell},{index_range})/'{SHEET}'!$C$46))"
    )


def _service_months(year_cell: str, first: int, last: int, commission_cell: str) -> str:
    """Месяцы года внутри плана, начиная с даты ввода. Конечный месяц плана входит."""
    dates = f"'{MONTHS}'!$A${first}:$A${last}"
    inside = f"'{MONTHS}'!$D${first}:$D${last}"
    return f"SUMPRODUCT((YEAR({dates})={year_cell})*({inside}=1)*({dates}>={commission_cell}))"


def _scheduled_payments(year_cell: str, first: int, last: int, anchor: str, period_cell: str, from_sales: bool, sales: str) -> str:
    """Число платежей года. Период 0 — один раз в первый месяц окна, иначе каждые N месяцев от этой даты.

    from_sales=False — все месяцы плана от якоря (флаг invest в Радуге: и до продаж, и после).
    from_sales=True — только с даты начала продаж.
    """
    dates = f"'{MONTHS}'!$A${first}:$A${last}"
    years = f"'{MONTHS}'!$B${first}:$B${last}"
    inside = f"'{MONTHS}'!$D${first}:$D${last}"
    elapsed = f"((YEAR({dates})-YEAR({anchor}))*12+MONTH({dates})-MONTH({anchor}))"
    sales_date = f"DATE(YEAR({sales}),MONTH({sales}),1)"
    gate = f"({dates}>={sales_date})" if from_sales else f"({dates}>={anchor})"
    once = f"SUMPRODUCT(({years}={year_cell})*({inside}=1)*{gate}*({elapsed}=0))"
    regular = f"SUMPRODUCT(({years}={year_cell})*({inside}=1)*{gate}*({elapsed}>=0)*(MOD({elapsed},{period_cell})=0))"
    return f"IF({period_cell}<=0,{once},{regular})"


def _expense_payments(year_cell: str, first: int, last: int, plan: str, sales: str, period_cell: str, flag_cell: str) -> str:
    """Как invest в Радуге: 1 — с начала проекта на весь горизонт (месяцы года считаются), 0 — только с начала продаж."""
    whole = _scheduled_payments(year_cell, first, last, plan, period_cell, False, sales)
    after = _scheduled_payments(year_cell, first, last, sales, period_cell, True, sales)
    return f"IF({flag_cell}=1,{whole},{after})"


def _paid_months(year_cell: str, first: int, last: int, sales_cell: str, from_plan: bool) -> str:
    """Сколько месяцев года человек в штате. invest=true — все месяцы плана; иначе с даты продаж."""
    window = f"('{MONTHS}'!$B${first}:$B${last}={year_cell})*('{MONTHS}'!$D${first}:$D${last}=1)"
    if from_plan:
        return f"SUMPRODUCT({window})"
    sales = f"DATE(YEAR({sales_cell}),MONTH({sales_cell}),1)"
    gate = f"('{MONTHS}'!$A${first}:$A${last}>={sales})"
    return f"SUMPRODUCT({window}*{gate})"


def _iter_months(form: dict) -> list[date]:
    """Месяцы плана, конечный месяц входит целиком. Конец не короче срока инвестора и кредита."""
    start = _form_date(form.get("planning_from"))
    end = _horizon(form)[0]
    if (end.year, end.month) < (start.year, start.month):
        end = start
    months: list[date] = []
    year, month = start.year, start.month
    while (year, month) <= (end.year, end.month) and len(months) < 600:
        months.append(date(year, month, 1))
        month += 1
        if month == 13:
            month = 1
            year += 1
    return months


def _sumif_months(year_cell: str, value_col: int, first: int, last: int) -> str:
    value = get_column_letter(value_col)
    return (
        f"=SUMIF('{MONTHS}'!$B${first}:$B${last},{year_cell},"
        f"'{MONTHS}'!${value}${first}:${value}${last})"
    )


def national_bank_indicators() -> dict | None:
    """Последний срез макропоказателей НБРК: цель, годовая инфляция, базовая ставка, TONIA."""
    request = urllib.request.Request(
        "https://data.nationalbank.kz/api/v1/data/indicators",
        headers={"Accept": "application/json", "Accept-Language": "ru", "User-Agent": "Mozilla/5.0"},
    )
    try:
        with urllib.request.urlopen(request, timeout=15) as response:
            payload = json.loads(response.read().decode("utf-8"))
    except (OSError, urllib.error.URLError, json.JSONDecodeError, UnicodeError):
        return None
    if not isinstance(payload, list) or not payload:
        return None
    latest = max(payload, key=lambda item: str(item.get("date") or ""))
    return latest if isinstance(latest, dict) else None


def inflation_percent() -> tuple[float, str]:
    """Годовая инфляция НБРК в процентах и подпись с датой среза."""
    latest = national_bank_indicators()
    if latest is not None:
        try:
            percent = float(latest["annualInflation"])
            year, month, day = str(latest["date"]).split("-")
        except (KeyError, TypeError, ValueError):
            percent = None
        else:
            text = f"{percent:g}".replace(".", ",")
            return percent, f"Годовая инфляция НБРК на {day}.{month}.{year}, {text}%"
    percent = Settings().fem_inflation
    text = f"{percent:g}".replace(".", ",")
    return percent, f"Годовая инфляция, {text}%. API НБРК не ответил, взято запасное значение"


def national_bank_rates(on_date: date) -> tuple[date, dict[str, float]] | None:
    """Курсы НБРК на дату с rss/get_rates.cfm.

    Если на эту дату Нацбанк ничего не публиковал, берётся ближайший предыдущий день.
    """
    for shift in range(11):
        day = on_date - timedelta(days=shift)
        url = f"{NBK_RATES_URL}?fdate={day.strftime('%d.%m.%Y')}"
        try:
            with urllib.request.urlopen(url, timeout=15) as response:
                payload = response.read()
        except (OSError, urllib.error.URLError):
            return None
        root = ET.fromstring(payload)
        found: dict[str, float] = {}
        for item in root.findall("item"):
            title = (item.findtext("title") or "").strip()
            if title not in NBK_CODES:
                continue
            try:
                found[title] = float(item.findtext("description") or 0)
            except ValueError:
                continue
        if set(NBK_CODES) <= found.keys():
            return day, found
    return None


def national_bank_base_rate() -> tuple[float, str] | None:
    """Последнее решение по базовой ставке из открытого API НБРК."""
    request = urllib.request.Request(
        "https://data.nationalbank.kz/api/v1/data/base-rate",
        headers={"Accept": "application/json", "User-Agent": "Mozilla/5.0"},
    )
    try:
        with urllib.request.urlopen(request, timeout=15) as response:
            payload = json.loads(response.read().decode("utf-8"))
    except (OSError, urllib.error.URLError, json.JSONDecodeError, UnicodeError):
        return None
    if not isinstance(payload, list) or not payload:
        return None
    latest = max(payload, key=lambda item: str(item.get("effectiveDate") or ""))
    try:
        rate = float(latest["rate"]) / 100
        year, month, day = str(latest["effectiveDate"]).split("-")
    except (KeyError, TypeError, ValueError):
        return None
    return rate, f"{day}.{month}.{year}"


_CAPEX_KEYS = ("smr", "overhaul", "ready_premises", "equipment")
_OPERATING_KEYS = ("raw", "advertising", "wages", "rental", "other")


def _gov(form: dict) -> dict | None:
    if int(form.get("financing") or 0) not in (2, 4):
        return None
    program = form.get("gov_program") or {}
    if not isinstance(program, dict):
        return None
    if not str(program.get("slug") or program.get("title") or "").strip():
        return None
    return program


def _gov_blocks_wc(form: dict) -> bool:
    program = _gov(form)
    return not (program and program.get("allows_wc") is False)


def _loan_terms(form: dict) -> tuple[float, int, int]:
    """Ставка, срок и каникулы инвестиций с учётом госпрограммы."""
    rate = _rate(form.get("loan_rate"))
    term = max(0, min(250, int(_num(form.get("loan_term")))))
    grace = max(0, int(_num(form.get("loan_grace"))))
    program = _gov(form)
    if not program:
        return rate, term, min(grace, 36)
    if program.get("rate") is not None:
        rate = _rate(program.get("rate"))
    low = int(program.get("term_min_months") or 0) if program.get("term_kind") == "loan" else 0
    high = int(program.get("term_max_months") or 0) if program.get("term_kind") == "loan" else 0
    if high and term > high:
        term = high
    if low and term and term < low:
        term = low
    cap = int(program.get("grace_max_months") or 0)
    if cap:
        grace = min(grace, cap)
    else:
        grace = min(grace, 36)
    return rate, term, grace


_FIN_LINES = (
    ("smr", "Перечень видов работ СМР, капитального ремонта"),
    ("overhaul", "Перечень материалов при СМР, капитальном ремонте"),
    ("ready_premises", "Помещения, здания, сооружения"),
    ("equipment", "Приобретение оборудования, техники, мебели"),
    ("raw", "Закуп сырья, товаров, расходных материалов"),
    ("advertising", "Расходы на рекламу"),
    ("wages", "Расходы по выплате заработной платы"),
    ("rental", "Расходы по аренде"),
    ("other", "Прочие расходы"),
)
_MONTHS = (
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


def _debt_rate(form: dict) -> float:
    """Стоимость долга для WACC = то, что клиент реально платит по графику:
    срок субсидии > 0 — средневзвешенная (клиент в льготе, банк после);
    срок субсидии 0 при ставке банка — полная ставка банка;
    иначе ставка клиента из анкеты. При оборотных — смесь траншей."""
    loan, term, _ = _loan_terms(form)
    bank = _rate(form.get("loan_bank_rate"))
    subsidy_term = max(0, int(_num(form.get("loan_subsidy_term"))))
    if bank > 0 and term > 0:
        if subsidy_term <= 0:
            loan = bank
        else:
            subsidized = min(subsidy_term, term)
            after = max(0, term - subsidized)
            loan = (loan * subsidized + bank * after) / term
    if not _gov_blocks_wc(form) or int(_num(form.get("wc_term"))) <= 0:
        return loan
    working = _rate(form.get("wc_rate"))
    program = _gov(form)
    if program and program.get("term_kind") == "subsidy" and program.get("rate") is not None:
        working = _rate(program.get("rate"))
    attracted = form.get("attracted") or {}
    capex = sum(_num(attracted.get(key)) for key in _CAPEX_KEYS)
    operating = sum(_num(attracted.get(key)) for key in _OPERATING_KEYS)
    if working <= 0 or operating <= 0:
        return loan
    if loan <= 0 or capex <= 0:
        return working
    return (loan * capex + working * operating) / (capex + operating)


def _line_plan(form: dict, key: str) -> tuple[str, date | None, int | None, str]:
    """Текст срока, дата начала, число месяцев и категория для будущих листов."""
    raw = str(form.get("planning_from") or "")
    parts = raw.split("-")
    try:
        year, month = int(parts[0]), int(parts[1])
        started = date(year, month, 1)
        name = _MONTHS[month]
    except (IndexError, ValueError):
        year, month, started, name = 0, 0, None, ""
    if key in _CAPEX_KEYS:
        return "Даты и суммы оплат — лист «Реестр CAPEX»", None, None, "Капитальные затраты"
    months = int(round(_num(form.get(f"months_{key}"))))
    genitive = (
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
    text = f"{months} мес. с {genitive[month]} {year} года" if name and months > 0 else ""
    category = "Оборотный капитал" if key == "raw" else "Операционные расходы"
    return text, started, months, category


def _column_span(first: int, count: int) -> tuple[str, str]:
    return get_column_letter(first), get_column_letter(first + count - 1)


def _month_load_formula(
    date_cell: str,
    period_cell: str,
    sales_cell: str,
    full_cell: str,
    year_cell: str,
    entered_row: int,
    flag_row: int,
    years: int,
) -> str:
    """Ручной процент года закрывает все его месяцы. Пустой год растёт линейно, без деления на ноль."""
    year_from, year_to = _column_span(YEAR_COLUMN, years)
    value_to = get_column_letter(2 + years)
    year_range = f"'{SHEET}'!${year_from}$6:${year_to}$6"
    flag = f"SUMPRODUCT(({year_range}={year_cell})*('{REV}'!$C${flag_row}:${value_to}${flag_row}))"
    value = f"SUMPRODUCT(({year_range}={year_cell})*('{REV}'!$C${entered_row}:${value_to}${entered_row}))"
    span = f"((YEAR({full_cell})-YEAR({sales_cell}))*12+MONTH({full_cell})-MONTH({sales_cell}))"
    elapsed = f"((YEAR({date_cell})-YEAR({sales_cell}))*12+MONTH({date_cell})-MONTH({sales_cell}))"
    linear = f"IF({span}<=0,1,IF({elapsed}<=0,0,IF({elapsed}>={span},1,{elapsed}/{span})))"
    return (
        f'=IF(OR({period_cell}=0,{date_cell}<DATE(YEAR({sales_cell}),MONTH({sales_cell}),1)),0,'
        f"IF({flag}>0,MAX(0,{value}),{linear}))"
    )


def _month_qty_formula(month_cell: str, load_cell: str, qty_cell: str, monthly: bool, season: set[int]) -> str:
    base = f"'{REV}'!{qty_cell}" if monthly else f"('{REV}'!{qty_cell}/12)"
    if season:
        gate = "OR(" + ",".join(f"{month_cell}={item}" for item in sorted(season)) + ")"
        return f"=IF({gate},{base}*{load_cell},0)"
    return f"={base}*{load_cell}"


def _month_revenue_formula(qty_cell: str, year_cell: str, price_row: int, years: int) -> str:
    value_to = get_column_letter(2 + years)
    year_from, year_to = _column_span(YEAR_COLUMN, years)
    price = (
        f"INDEX('{REV}'!$C${price_row}:${value_to}${price_row},"
        f"MATCH({year_cell},'{SHEET}'!${year_from}$6:${year_to}$6,0))"
    )
    return f"={qty_cell}*{price}"


def _other_month_formula(row: int, amount: str, start: str, period: str, year_cell: str, years: int) -> str:
    year_from, year_to = _column_span(YEAR_COLUMN, years)
    cumulative_to = get_column_letter(3 + years)
    index = (
        f"INDEX('{SHEET}'!$D$47:${cumulative_to}$47,"
        f"MATCH({year_cell},'{SHEET}'!${year_from}$6:${year_to}$6,0))/'{SHEET}'!$C$47"
    )
    indexed = f"IF({period}=3,1,{index})"
    pay = (
        f"IF({period}=2,AND(D{row}=1,A{row}>=DATE(YEAR({start}),MONTH({start}),1),MONTH(A{row})=MONTH({start})),"
        f"IF({period}=3,AND(D{row}=1,YEAR(A{row})=YEAR({start}),MONTH(A{row})=MONTH({start})),"
        f"AND(D{row}=1,A{row}>=DATE(YEAR({start}),MONTH({start}),1))))"
    )
    return f"=IF({pay},{amount}*({indexed}),0)"


def _average_load_formula(year_cell: str, first: int, last: int) -> str:
    dates = f"'{MONTHS}'!$A${first}:$A${last}"
    years = f"'{MONTHS}'!$B${first}:$B${last}"
    load = f"'{MONTHS}'!$E${first}:$E${last}"
    return (
        f'=IF(COUNTIFS({years},{year_cell},{dates},">="&$B$4,{dates},"<="&$B$5)=0,0,'
        f'SUMIFS({load},{years},{year_cell},{dates},">="&$B$4,{dates},"<="&$B$5)/'
        f'COUNTIFS({years},{year_cell},{dates},">="&$B$4,{dates},"<="&$B$5))'
    )


def _capacity_warning(ranges: list[str]) -> str:
    over = "+".join(f'COUNTIF({item},">1")' for item in ranges) or "0"
    negative = "+".join(f'COUNTIF({item},"<0")' for item in ranges) or "0"
    return (
        '=TRIM(IF(' + over + '>0,'
        '"Загрузка выше 100%. Так можно только если мощность расширена: новые сотрудники, оборудование или CAPEX. Сейчас продажи умножаются на этот процент, дополнительный CAPEX не ставится.","")'
        '&IF(' + negative + '>0," Отрицательная загрузка в расчёт не берётся.",""))'
    )


class PremisesBook:
    def __init__(self, user: User, form: dict):
        self.user = user
        self.form = form
        self.years = _planning_years(form)
        self.book = Workbook()
        self.book.calculation.calcMode = "auto"
        self.book.calculation.fullCalcOnLoad = True
        self.ws = self.book.active
        self.ws.title = SHEET

    def build(self) -> bytes:
        self.share_debt = "=0"
        self.share_investor = "=0"
        self.share_own = "=0"
        self.own_link = 0
        self.attracted_link = 0
        self._prepare_capex()
        self._layout()
        self._rates()
        self._financing_sheet()
        self._indicators()
        self._wacc()
        self._inflation()
        self._indexes()
        self._cumulative()
        self._sources()
        self._income_plan()
        self._month_sheet()
        self._cost_sheet()
        self._payroll_sheet()
        self._admin_sheet()
        self._capex_register()
        self._capex_on_premises()
        self._amort_sheet()
        self._payment_sheet()
        self._pnl_sheet()
        self._cash_sheet()
        self._effect_sheet()
        stream = BytesIO()
        self.book.save(stream)
        return stream.getvalue()

    def _layout(self):
        ws = self.ws
        ws.sheet_properties.tabColor = "641AEA"
        ws.sheet_view.showGridLines = True
        ws.page_setup.orientation = "landscape"
        ws.page_setup.fitToPage = True
        ws.page_setup.fitToWidth = 1
        ws.page_setup.fitToHeight = 1
        ws.page_setup.paperSize = ws.PAPERSIZE_A4
        ws.sheet_format.defaultRowHeight = 15
        ws.row_dimensions[7].height = 26
        ws.row_dimensions[9].height = 32
        ws.row_dimensions[22].height = 28
        widths = {
            "A": 46,
            "B": 18,
            "C": 16,
            "D": 12,
        }
        for column in range(YEAR_COLUMN, YEAR_COLUMN + self.years):
            widths[get_column_letter(column)] = 11
        for letter, width in widths.items():
            ws.column_dimensions[letter].width = width
        ws.auto_filter.ref = None

    def _put(self, row: int, column: int, value, *, font=TEXT, fill=None, border=None, fmt=None, align="left", wrap=False):
        cell = self.ws.cell(row, column, value)
        cell.font = font
        if fill is not None:
            cell.fill = fill
        if border is not None:
            cell.border = border
        if fmt:
            cell.number_format = fmt
        cell.alignment = Alignment(horizontal=align, vertical="center", wrap_text=wrap)
        return cell

    def _rates(self):
        self._put(
            2,
            1,
            "Суммы анкеты уже в выбранной валюте. Отдельный пересчёт по курсу не делается.",
            wrap=True,
            font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
        )
        self.ws.merge_cells("A2:C5")
        self.ws.row_dimensions[2].height = 32

    def _indicators(self):
        start = _year(self.form.get("planning_from"))
        self._put(6, 5, start, align="center", font=Font(name=FONT_NAME, size=10, bold=True))
        for offset in range(1, self.years):
            column = YEAR_COLUMN + offset
            previous = get_column_letter(column - 1)
            self._put(6, column, f"={previous}6+1", align="center", font=Font(name=FONT_NAME, size=10, bold=True))

        headers = ((1, "Показатели"), (2, "Обоснование"), (3, "Текущее значение"))
        for column, text in headers:
            self._put(7, column, text, border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=10, bold=True))
        for offset in range(self.years):
            self._put(7, YEAR_COLUMN + offset, f"{offset + 1} год", border=BOX, align="center", font=Font(name=FONT_NAME, size=10, bold=True))

        self._put(8, 1, "Ставка дисконтирования", border=BOX)
        self._put(
            8,
            2,
            "В расчёт не входит. Ставка проекта — ячейка C25, ставка инвестора — базовая плюс премия на листе «Анализ эффект.».",
            border=BOX,
            wrap=True,
            font=Font(name=FONT_NAME, size=8, color="000000"),
        )
        self._put(8, 3, "см. C25", border=BOX, align="center")

        percent, inflation_note = inflation_percent()
        self._put(9, 1, "Коэффициент инфляции", border=BOX, wrap=True)
        self._put(
            9,
            2,
            inflation_note,
            border=BOX,
            wrap=True,
            font=Font(name=FONT_NAME, size=8, color="000000"),
        )
        self._put(9, 3, _rate(percent), border=BOX, fmt=PCT, align="center", fill=BLUE)
        self._chain(9, "E9*(1+$C$9)")

        self._put(10, 1, "Индекс роста цен на продукцию", border=BOX, wrap=True)
        self._put(10, 3, "=C9", border=BOX, fmt=PCT, align="center")
        self._chain(10, "E10*(1+$C$10)")

        self._put(11, 1, "Количество прогнозируемых месяцев в году", border=BOX, wrap=True)
        self._put(
            11,
            2,
            "Конечный месяц входит полностью",
            border=BOX,
            wrap=True,
            font=Font(name=FONT_NAME, size=8, color="44546A"),
        )
        dates = self._source_cells()
        for offset in range(self.years):
            year_cell = f"{get_column_letter(YEAR_COLUMN + offset)}6"
            self._put(
                11,
                YEAR_COLUMN + offset,
                _months_formula(year_cell, dates["plan_start"], dates["plan_end"]),
                border=BOX,
                fmt="0",
                align="center",
            )

        self._put(12, 1, "График работы", border=BOX)
        self._put(
            12,
            2,
            "В выручку и ФОТ не входит: объём задаётся количеством в анкете, а не числом рабочих дней.",
            border=BOX,
            wrap=True,
            font=Font(name=FONT_NAME, size=8, color="000000"),
        )
        self._put(12, 3, "не используется", border=BOX, align="center")
        self._put(13, 1, "Рабочие часы", border=BOX)
        self._put(13, 2, "В расчёт не входят.", border=BOX, wrap=True, font=Font(name=FONT_NAME, size=8, color="000000"))
        self._put(13, 3, "не используется", border=BOX, align="center")

    def _chain(self, row: int, step: str):
        self._put(row, 5, 1, border=BOX, fmt=PCT, align="center")
        for offset in range(1, self.years):
            column = YEAR_COLUMN + offset
            previous = get_column_letter(column - 1)
            formula = step.replace("E8", f"{previous}{row}").replace("E9", f"{previous}{row}").replace("E10", f"{previous}{row}")
            self._put(row, column, f"={formula}", border=BOX, fmt=PCT, align="center")

    def _wacc(self):
        regime = int(self.form.get("tax_regime") or 4)
        shield_note = (
            "Общий режим: щит равен налогу на прибыль"
            if regime == 1
            else "Упрощёнка и спецрежимы: проценты налог не уменьшают"
        )

        self._put(15, 1, "Ставка дисконтирования", font=SECTION)
        self._put(
            16,
            1,
            "Доли WACC — с листа «Структура финансирования», без гранта. При субсидии ставка кредита для WACC — средневзвешенная по сроку: ставка клиента в период субсидии и ставка банка после её окончания. Без субсидии — ставка из анкеты.",
            wrap=True,
            font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
        )
        self.ws.merge_cells("A16:C16")
        self.ws.row_dimensions[16].height = 36
        self.ws.row_dimensions[22].height = 36
        cells = self._source_cells()
        base_cell = cells["base"]
        premium_cell = cells["premium"]
        loan_cell = cells["loan"]
        rows = (
            (17, "Стоимость собственного капитала", f"={base_cell}", PCT_FINE, PURPLE),
            (18, "Доля собственного капитала", self.share_own, PCT_FINE, PURPLE),
            (19, "Стоимость капитала инвестора", f"={base_cell}+{premium_cell}", PCT_FINE, PURPLE),
            (20, "Доля капитала инвестора", self.share_investor, PCT_FINE, PURPLE),
            (21, "Стоимость кредита (до налогов)", f"={loan_cell}", PCT_FINE, PURPLE),
            (22, "Налоговый щит процентов по кредиту", f"=IF({cells['tax_general']}=1,{cells['tax_rate']},0)", PCT, PURPLE),
            (23, "Стоимость кредита (после налогов)", "=C21*(1-C22)", PCT_FINE, PURPLE),
            (24, "Доля кредита", self.share_debt, PCT_FINE, PURPLE),
        )
        for row, label, value, fmt, fill in rows:
            self._put(row, 1, label, border=GREY_BOX, wrap=row == 22)
            self._put(row, 3, value, border=GREY_BOX, fmt=fmt, align="center", fill=fill)
        self._put(22, 2, shield_note, wrap=True, font=Font(name=FONT_NAME, size=8, color="000000"))
        self._put(25, 1, "Средневзвешенная стоимость капитала (WACC)", border=GREY_BOX, fill=GREEN, wrap=True, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(25, 3, "=C17*C18+C19*C20+C23*C24", border=GREY_BOX, fmt=PCT_FINE, align="center", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))

    def _inflation(self):
        self._put(27, 1, "Ставки инфляции", font=SECTION)
        self._put(28, 1, "на каждый календарный год", font=Font(name=FONT_NAME, size=10, italic=True, color="44546A"))
        self.ws.merge_cells("A29:B29")
        self._put(29, 1, "Вид инфляции", fill=GRAY, border=BOX, align="center", font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(29, 2, None, fill=GRAY, border=BOX)
        for offset in range(self.years):
            source = get_column_letter(YEAR_COLUMN + offset)
            self._put(29, 3 + offset, f"={source}6", fill=GRAY, border=BOX, align="center", font=Font(name=FONT_NAME, size=10, bold=True))

        labels = (
            (30, "Годовой рост стоимости новых капитальных вложений"),
            (31, "Цены на услуги"),
            (32, "Переменные затраты"),
            (33, "Постоянные затраты"),
            (34, "Заработные платы"),
        )
        for row, label in labels:
            self.ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=2)
            self._put(row, 1, label, border=BOX, wrap=row == 30)
            self._put(row, 2, None, border=BOX)
            self._put(row, 3, 0, border=GREY_BOX, fmt=PCT, align="center", fill=BLUE)
        self.ws.row_dimensions[30].height = 32
        # Правило методики, не закон цен: у новых капвложений первый и второй год без роста, с третьего — инфляция.
        # Лист «Амортизация» умножает накопленный индекс только на новую закупку этого года,
        # не переоценивает уже купленное и амортизирует эту стоимость. Оплата остаётся на листе финансирования.
        if self.years >= 2:
            self._put(30, 4, 0, border=GREY_BOX, fmt=PCT, align="center", fill=BLUE)
        if self.years >= 3:
            self._put(30, 5, "=$C$9", border=GREY_BOX, fmt=PCT, align="center", fill=BLUE)
        for column in range(6, 3 + self.years):
            previous = get_column_letter(column - 1)
            self._put(30, column, f"={previous}30", border=GREY_BOX, fmt=PCT, align="center")

        for row, source in ((31, "$C$10"), (32, "$C$9"), (33, "$C$9")):
            if self.years >= 2:
                self._put(row, 4, f"={source}", border=GREY_BOX, fmt=PCT, align="center", fill=BLUE)
            for column in range(5, 3 + self.years):
                previous = get_column_letter(column - 1)
                self._put(row, column, f"={previous}{row}", border=GREY_BOX, fmt=PCT, align="center")

        if self.years >= 2:
            self._put(34, 4, 0.05, border=GREY_BOX, fmt=PCT, align="center", fill=BLUE)
        for column in range(5, 3 + self.years):
            previous = get_column_letter(column - 1)
            self._put(34, column, f"={previous}34", border=GREY_BOX, fmt=PCT, align="center")

    def _indexes(self):
        self._put(
            35,
            1,
            "Накопленный коэффициент — только для новой закупки этого года. Уже купленные активы он не переоценивает. Ноль в первые два года — правило методики: закупка при запуске берётся в цене анкеты.",
            wrap=True,
            font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
        )
        self.ws.merge_cells(start_row=35, start_column=1, end_row=35, end_column=2 + self.years)
        self.ws.row_dimensions[35].height = 36
        self._put(36, 1, "Индекс цен за период", font=SECTION)
        for offset in range(self.years):
            source = get_column_letter(3 + offset)
            self._put(37, 3 + offset, f"={source}29", align="center")
        labels = (
            (38, "=A30", None),
            (39, "Цены продаж на услуги", 31),
            (40, "Переменные затраты", 32),
            (41, "Постоянные затраты", 33),
            (42, "Заработные платы", 34),
        )
        for row, label, source in labels:
            self._put(row, 1, label, border=GREY_BOX, wrap=source is None)
            self._put(row, 2, "%", border=GREY_BOX, align="center")
            rate_row = 30 if source is None else source
            for offset in range(self.years):
                column = 3 + offset
                letter = get_column_letter(column)
                self._put(row, column, f"=1+{letter}{rate_row}", border=GREY_BOX, fmt=PCT, align="center")
        self.ws.row_dimensions[38].height = 32

    def _cumulative(self):
        self._put(44, 1, "Накопленный индекс цен", font=SECTION)
        for offset in range(self.years):
            source = get_column_letter(3 + offset)
            self._put(45, 4 + offset, f"={source}37", align="center")
        labels = (
            (46, "=A30", 38),
            (47, "Цены продаж готовой продукции", 39),
            (48, "Переменные затраты", 40),
            (49, "Постоянные затраты", 41),
            (50, "Заработные платы", 42),
        )
        for row, label, source in labels:
            self._put(row, 1, label, border=GREY_BOX, wrap=source == 38)
            self._put(row, 2, "%", border=GREY_BOX, align="center")
            self._put(row, 3, 1, border=GREY_BOX, fmt=PCT, align="center")
            for offset in range(self.years):
                column = 4 + offset
                previous = get_column_letter(column - 1)
                rate = get_column_letter(3 + offset)
                self._put(row, column, f"={previous}{row}*{rate}{source}", border=GREY_BOX, fmt=PCT, align="center")
        self.ws.row_dimensions[46].height = 32

    def _financing_sheet(self):
        """Второй лист, как у Радуги: строки вложений, итог и доли для WACC."""
        ws = self.book.create_sheet(FIN)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "1F4E79"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            widths = {"A": 6, "B": 62, "C": 24, "D": 20, "E": 22, "F": 24, "G": 16, "H": 42, "I": 26, "J": 16, "K": 16}
            for letter, width in widths.items():
                ws.column_dimensions[letter].width = width

            self._put(
                1,
                1,
                "Суммы строк — как в анкете. Прогноз, дата оплаты и дата ввода каждой закупки — на листе «Реестр CAPEX». Добавка индексации CAPEX включается в источник: инвестору, в кредит или в свои средства. Оборотные запасы и операционные расходы в CAPEX не входят. Грант в WACC не входит.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells("A1:K1")
            ws.row_dimensions[1].height = 48

            code = {"kzt": "KZT", "usd": "USD", "eur": "EUR"}.get(str(self.form.get("currency") or "kzt"), "KZT")
            headers = (
                (1, "№"),
                (2, "Наименование"),
                (3, f"Итоговая стоимость, {code}"),
                (4, "Заемные средства"),
                (5, "Деньги инвестора"),
                (6, "Собственные средства"),
                (7, "Грант"),
                (8, "Сроки осуществления"),
                (9, "Категория"),
                (10, "Начало оплаты"),
                (11, "Число месяцев"),
            )
            for column, text in headers:
                self._put(2, column, text, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            ws.row_dimensions[2].height = 32
            self._put(3, 2, "Инвестиции, пополнение оборотных средств", font=Font(name=FONT_NAME, size=10, bold=True))

            own = self.form.get("own") or {}
            attracted = self.form.get("attracted") or {}
            use_own = self.form.get("own_invest") in (True, 1, "1", "true", "True")
            financing = int(self.form.get("financing") or 1)
            credit_amounts = self.form.get("credit") or {}
            block_wc = not _gov_blocks_wc(self.form)
            lines = []
            for key, label in _FIN_LINES:
                own_value = _num(own.get(key)) if use_own else 0.0
                external = _num(attracted.get(key))
                if own_value == 0 and external == 0:
                    continue
                loan_part = min(_num(credit_amounts.get(key)), external) if financing == 4 else 0.0
                debt = external if financing == 2 else loan_part
                investor = (external - loan_part) if financing == 4 else (external if financing == 1 else 0.0)
                if block_wc and key in _OPERATING_KEYS and debt > 0:
                    # Госпрограмма без оборотки: эти суммы не в кредит, закрываем своими.
                    own_value += debt
                    debt = 0.0
                text, started, months, category = _line_plan(self.form, key)
                lines.append(
                    (
                        label,
                        debt,
                        investor,
                        own_value,
                        external if financing == 3 else 0.0,
                        text,
                        category,
                        started,
                        months,
                    )
                )

            start = 4
            for index, (label, debt, investor, own_value, grant, when, category, started, months) in enumerate(lines, start=1):
                row = start + index - 1
                self._put(row, 1, index, border=BOX, align="center")
                self._put(row, 2, label, border=BOX, wrap=True)
                self._put(row, 3, f"=D{row}+E{row}+F{row}+G{row}", border=BOX, fmt=MONEY, align="right")
                self._put(row, 4, debt, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                self._put(row, 5, investor, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                self._put(row, 6, own_value, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                self._put(row, 7, grant, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                self._put(row, 8, when, border=BOX, wrap=True)
                self._put(row, 9, category, border=BOX, wrap=True)
                self._put(row, 10, started, border=BOX, fmt="DD.MM.YYYY", align="center")
                self._put(row, 11, months, border=BOX, fmt="0", align="center")
                ws.row_dimensions[row].height = 30

            index_row = start + len(lines)
            addon = f"MAX(0,'{CAPEX}'!H{self.capex_total_row}-'{CAPEX}'!C{self.capex_total_row})"
            self._put(index_row, 1, len(lines) + 1, border=BOX, align="center")
            self._put(index_row, 2, "Добавка индексации CAPEX", border=BOX, wrap=True)
            self._put(index_row, 3, f"=D{index_row}+E{index_row}+F{index_row}+G{index_row}", border=BOX, fmt=MONEY, align="right")
            self._put(index_row, 4, f"={addon}" if financing == 2 else 0, border=BOX, fmt=MONEY, align="right", fill=BLUE)
            self._put(index_row, 5, f"={addon}" if financing in (1, 4) else 0, border=BOX, fmt=MONEY, align="right", fill=BLUE)
            self._put(index_row, 6, f"={addon}" if financing not in (1, 2, 4) else 0, border=BOX, fmt=MONEY, align="right", fill=BLUE)
            self._put(index_row, 7, 0, border=BOX, fmt=MONEY, align="right", fill=BLUE)
            self._put(index_row, 8, "В месяц начала плана", border=BOX, wrap=True)
            self._put(index_row, 9, "Индексация", border=BOX, wrap=True)
            self._put(index_row, 10, _form_date(self.form.get("planning_from")), border=BOX, fmt="DD.MM.YYYY", align="center")
            self._put(index_row, 11, 1, border=BOX, fmt="0", align="center")
            ws.row_dimensions[index_row].height = 30

            total_row = index_row + 1
            first = start
            last = index_row
            sums = {column: f"=SUM({get_column_letter(column)}{first}:{get_column_letter(column)}{last})" for column in range(3, 8)}
            self._put(total_row, 2, "ВСЕГО:", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(total_row, 1, None, border=BOX, fill=GREEN)
            for column in range(3, 8):
                self._put(
                    total_row,
                    column,
                    sums[column],
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                    fill=GREEN,
                    font=Font(name=FONT_NAME, size=10, bold=True),
                )
            self._put(total_row, 8, None, border=BOX, fill=GREEN)

            share_row = total_row + 1
            self._put(share_row, 2, "Доля в финансировании", border=GREY_BOX, wrap=True)
            self._put(share_row, 1, None, border=GREY_BOX)
            self._put(share_row, 3, None, border=GREY_BOX)
            for column in range(4, 8):
                letter = get_column_letter(column)
                self._put(
                    share_row,
                    column,
                    f"=IF(C{total_row}=0,0,{letter}{total_row}/C{total_row})",
                    border=GREY_BOX,
                    fmt=PCT_FINE,
                    align="center",
                    fill=PURPLE,
                )
            self._put(share_row, 8, None, border=GREY_BOX)
            ws.row_dimensions[share_row].height = 30

            wacc_row = share_row + 1
            priced = f"(C{total_row}-G{total_row})"
            self._put(wacc_row, 2, "Доля в WACC, без гранта", border=GREY_BOX, wrap=True)
            self._put(wacc_row, 1, None, border=GREY_BOX)
            self._put(wacc_row, 3, None, border=GREY_BOX)
            for column in range(4, 7):
                letter = get_column_letter(column)
                self._put(
                    wacc_row,
                    column,
                    f"=IF({priced}=0,0,{letter}{total_row}/{priced})",
                    border=GREY_BOX,
                    fmt=PCT_FINE,
                    align="center",
                    fill=GREEN,
                )
            self._put(wacc_row, 7, "не входит", border=GREY_BOX, align="center", fill=GREEN)
            self._put(wacc_row, 8, None, border=GREY_BOX)
            ws.row_dimensions[wacc_row].height = 30
            self.share_debt = f"='{FIN}'!D{wacc_row}"
            self.share_investor = f"='{FIN}'!E{wacc_row}"
            self.share_own = f"='{FIN}'!F{wacc_row}"
            self.own_link = f"='{FIN}'!F{total_row}"
            self.attracted_link = f"='{FIN}'!D{total_row}+'{FIN}'!E{total_row}+'{FIN}'!G{total_row}"
            self.fin_total_row = total_row
            self.fin_first = start
            self.fin_last = index_row
            self._financing_gap(wacc_row)
        finally:
            self.ws = previous

    def _source_cells(self) -> dict[str, str]:
        """Ячейки анкеты столбцом сразу за последним годом."""
        value = get_column_letter(YEAR_COLUMN + self.years + 2)
        return {
            "own": f"${value}$2",
            "attracted": f"${value}$3",
            "kind": f"${value}$4",
            "schedule": f"${value}$5",
            "base": f"${value}$6",
            "premium": f"${value}$7",
            "loan": f"${value}$8",
            "plan_start": f"${value}$9",
            "plan_end": f"${value}$10",
            "sales_start": f"${value}$11",
            "full_volume": f"${value}$12",
            "tax_general": f"${value}$13",
            "tax_rate": f"${value}$14",
            "tax_adj": f"${value}$15",
            "dividend": f"${value}$16",
            "investor_term": f"${value}$17",
            "legal_too": f"${value}$18",
            "vat_rate": f"${value}$19",
            "vat_on": f"${value}$20",
            "vosms_rate": f"${value}$21",
            "social_tax_rate": f"${value}$22",
        }

    def _sources(self):
        label_column = YEAR_COLUMN + self.years + 1
        value_column = label_column + 1
        schedule = int(self.form.get("schedule") or 1)
        financing = int(self.form.get("financing") or 1)
        financing_names = {1: "Инвестор", 2: "Кредит", 3: "Грант", 4: "Инвестор и кредит"}
        schedule_names = {1: "Пятидневка", 2: "Шестидневка", 3: "Каждый день"}
        loaded_base = national_bank_base_rate()
        base_rate = None if loaded_base is None else loaded_base[0]
        base_label = "Базовая ставка НБРК" if loaded_base is None else f"Базовая ставка НБРК с {loaded_base[1]}"
        rows = (
            (2, "Собственные, ₸", self.own_link, MONEY, "right"),
            (3, "Привлекаемые, ₸", self.attracted_link, MONEY, "right"),
            (4, "Тип: 1 инвестор, 2 кредит, 3 грант, 4 инвестор и кредит", financing if financing in financing_names else 1, "0", "center"),
            (5, "График: 1 пятидневка, 2 шестидневка, 3 каждый день", schedule if schedule in schedule_names else 1, "0", "center"),
            (6, base_label, base_rate, PCT_FINE, "center"),
            (7, "Премия за риск проекта", _rate(self.form.get("risk_premium")), PCT_FINE, "center"),
            (
                8,
                "Ставка кредита для WACC"
                + (
                    " (банк: субсидия не действует)"
                    if _rate(self.form.get("loan_bank_rate")) > 0 and int(_num(self.form.get("loan_subsidy_term"))) <= 0
                    else (
                        " (средняя: субсидия + ставка банка)"
                        if _rate(self.form.get("loan_bank_rate")) > 0 and int(_num(self.form.get("loan_subsidy_term"))) > 0
                        else " из анкеты"
                    )
                ),
                _debt_rate(self.form),
                PCT_FINE,
                "center",
            ),
            (9, "Начало плана", _form_date(self.form.get("planning_from")), "DD.MM.YYYY", "center"),
            (10, "Конец плана", _horizon(self.form)[0], "DD.MM.YYYY", "center"),
            (11, "Начало продаж", _form_date(self.form.get("sales_start") or self.form.get("planning_from")), "DD.MM.YYYY", "center"),
            (12, "Выход на 100%", _form_date(self.form.get("planned_volume") or self.form.get("planning_to")), "DD.MM.YYYY", "center"),
            (13, "Налог: 1 общий режим, 0 налог с выручки", 1 if int(self.form.get("tax_regime") or 4) == 1 else 0, "0", "center"),
            (
                14,
                "Ставка налога из анкеты (можно менять)",
                _tax_rate(int(self.form.get("legal_form") or 1), int(self.form.get("tax_regime") or 4), self.form),
                PCT_FINE,
                "center",
            ),
            (15, "Корректировка налоговой базы", 0, PCT_FINE, "center"),
            (16, "Дивиденды инвестора, доля прибыли прошлого года", _rate(self.form.get("investor_dividend")) if financing in (1, 4) else 0, PCT_FINE, "center"),
            (17, "Срок участия инвестора, лет", int(_num(self.form.get("investor_term"))) if financing in (1, 4) else 0, "0", "center"),
            (18, "ТОО: 1 — да (соцналог на ОУР)", 1 if int(self.form.get("legal_form") or 1) == 2 else 0, "0", "center"),
            (19, "Ставка НДС из анкеты", _form_pct(self.form, "vat_rate", VAT_RATE_2026), PCT_FINE, "center"),
            (
                20,
                "Плательщик НДС (1 — да; только ОУР)",
                1 if int(self.form.get("tax_regime") or 4) == 1 and int(self.form.get("nds") or 0) == 1 else 0,
                "0",
                "center",
            ),
            (21, "Ставка ВОСМС работника из анкеты", _form_pct(self.form, "vosms_rate", VOSMS_RATE), PCT_FINE, "center"),
            (22, "Ставка соцналога (ТОО ОУР) из анкеты", _form_pct(self.form, "social_tax_rate", SOCIAL_TAX_2026), PCT_FINE, "center"),
        )
        self._put(1, label_column, "Из анкеты", font=SECTION)
        for row, label, value, fmt, align in rows:
            self._put(row, label_column, label, border=BOX, wrap=True, font=Font(name=FONT_NAME, size=8, color="000000"))
            self._put(row, value_column, value, border=BOX, fmt=fmt, align=align, fill=None if row in (2, 3) else BLUE)
        name_column = value_column + 1
        self._put(4, name_column, financing_names.get(financing, ""), border=BOX, align="left")
        self._put(5, name_column, schedule_names.get(schedule, ""), border=BOX, align="left")
        self._put(10, name_column, _horizon(self.form)[1], border=BOX, align="left", wrap=True)
        self._put(13, name_column, "Общий" if int(self.form.get("tax_regime") or 4) == 1 else "С выручки", border=BOX, align="left")
        self._put(18, name_column, "ТОО" if int(self.form.get("legal_form") or 1) == 2 else "ИП", border=BOX, align="left")
        self._put(
            20,
            name_column,
            "НДС в ДДС" if int(self.form.get("tax_regime") or 4) == 1 and int(self.form.get("nds") or 0) == 1 else "Без НДС",
            border=BOX,
            align="left",
        )
        program = self.form.get("gov_program") or {}
        guarantee = self.form.get("gov_guarantee") or {}
        program_title = str(program.get("title") or "").strip() if isinstance(program, dict) else ""
        program_slug = str(program.get("slug") or "").strip() if isinstance(program, dict) else ""
        guarantee_title = str(guarantee.get("title") or "").strip() if isinstance(guarantee, dict) else ""
        if financing in (2, 4):
            if program_title or program_slug or guarantee_title:
                labels = [part for part in (program_title or program_slug, guarantee_title) if part]
                bits = [f"Госпрограмма: {' + '.join(labels)}"]
                if isinstance(program, dict) and (program_title or program_slug):
                    low = program.get("term_min_months")
                    high = program.get("term_max_months")
                    if low and high:
                        bits.append(f"каталог {int(low)}–{int(high)} мес")
                    elif high:
                        bits.append(f"каталог до {int(high)} мес")
                    grace_cap = program.get("grace_max_months")
                    if grace_cap:
                        bits.append(f"каникулы до {int(grace_cap)} мес")
                    if program.get("allows_wc") is False:
                        bits.append("оборотные средства отсутствуют")
                    elif program.get("allows_wc") is True:
                        bits.append("оборотные средства можно")
                if guarantee_title:
                    bits.append("гарантия Damu как дополнение")
                self._put(8, name_column, "; ".join(bits), border=BOX, align="left", wrap=True)
            else:
                self._put(8, name_column, "Без госпрограммы", border=BOX, align="left", wrap=True)
        self.ws.row_dimensions[10].height = 48
        self.ws.column_dimensions[get_column_letter(label_column)].width = 36
        self.ws.column_dimensions[get_column_letter(value_column)].width = 16
        self.ws.column_dimensions[get_column_letter(name_column)].width = 42
        self.ws.row_dimensions[4].height = 36
        self.ws.row_dimensions[5].height = 28
        self.ws.row_dimensions[8].height = 48 if financing in (2, 4) else 32
        self.ws.row_dimensions[11].height = 30
        self.ws.row_dimensions[12].height = 32
        self.ws.row_dimensions[13].height = 32
        self.ws.row_dimensions[15].height = 32
        self.ws.row_dimensions[16].height = 32
        self.ws.row_dimensions[17].height = 32

    def _income_plan(self):
        """Лист «План доходов»: годовые итоги. Количество и выручка — сумма листа «Месяцы»."""
        ws = self.book.create_sheet(REV)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "548235"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            start_year = _year(self.form.get("planning_from"))
            years = [start_year + offset for offset in range(self.years)]
            monthly = int(self.form.get("time_param") or 3) != 1
            money_unit = {"kzt": "тенге", "usd": "доллар", "eur": "евро"}.get(str(self.form.get("currency") or "kzt"), "тенге")
            ws.column_dimensions["A"].width = 46
            ws.column_dimensions["B"].width = 16
            for offset in range(self.years):
                ws.column_dimensions[get_column_letter(3 + offset)].width = 16

            self._put(1, 1, "Показатель", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(1, 2, "Ед.", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            for offset in range(self.years):
                source = get_column_letter(YEAR_COLUMN + offset)
                self._put(1, 3 + offset, f"='{SHEET}'!{source}6&\" год\"", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            ws.row_dimensions[1].height = 22

            self._put(
                2,
                1,
                "Синие ячейки — из анкеты. Конечный месяц входит полностью. Количество и выручка года — сумма листа «Месяцы». Пустой год растёт по месяцам от 0% в месяц начала продаж до 100% в месяц выхода на объём. Заполненный год ставит свой процент на каждый месяц и не смешивается с этим ростом. Цена года = цена анкеты × накопленный индекс этого года на строке 47 / база C47. Разовый прочий доход не индексируется.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=2 + self.years)
            ws.row_dimensions[2].height = 78

            dates = self._source_cells()
            self._put(3, 1, "Параметры проекта", font=SECTION)
            self._put(4, 1, "Начало продаж", border=BOX)
            self._put(4, 2, f"='{SHEET}'!{dates['sales_start']}", border=BOX, fmt="DD.MM.YYYY", align="center")
            self._put(5, 1, "Конец плана", border=BOX)
            self._put(5, 2, f"='{SHEET}'!{dates['plan_end']}", border=BOX, fmt="DD.MM.YYYY", align="center")
            self._put(6, 1, "Выход на 100%", border=BOX)
            self._put(6, 2, f"='{SHEET}'!{dates['full_volume']}", border=BOX, fmt="DD.MM.YYYY", align="center")
            months_row = 7
            entered_row = 8
            flag_row = 9
            load_row = 10
            warning_row = 11
            month_count = max(len(_iter_months(self.form)), 1)
            month_first, month_last = 5, 4 + month_count
            last_letter = get_column_letter(2 + self.years)
            self._put(months_row, 1, "Месяцев продаж в году", border=BOX, wrap=True)
            self._put(months_row, 2, "мес.", border=BOX, align="center")
            self._put(entered_row, 1, "Загрузка из анкеты", border=BOX, wrap=True)
            self._put(entered_row, 2, "%", border=BOX, align="center")
            self._put(flag_row, 1, "Год задан в анкете", border=BOX, wrap=True)
            self._put(flag_row, 2, "1/0", border=BOX, align="center")
            self._put(load_row, 1, "Средняя загрузка месяцев", border=BOX, wrap=True)
            self._put(load_row, 2, "%", border=BOX, align="center")
            entered = _entered_capacity(self.form, years)
            capacity_ranges = [f"$C${entered_row}:${last_letter}${entered_row}"]
            for offset, _year_number in enumerate(years):
                column = 3 + offset
                year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                self._put(
                    months_row,
                    column,
                    _months_formula(year_cell, "$B$4", "$B$5"),
                    border=BOX,
                    fmt="0",
                    align="center",
                )
                self._put(entered_row, column, entered[offset], border=BOX, fmt=PCT_FINE, align="center", fill=BLUE)
                self._put(
                    flag_row,
                    column,
                    0 if entered[offset] is None else 1,
                    border=BOX,
                    fmt="0",
                    align="center",
                )
                self._put(
                    load_row,
                    column,
                    _average_load_formula(year_cell, month_first, month_last),
                    border=BOX,
                    fmt=PCT_FINE,
                    align="center",
                )
                ws.column_dimensions[get_column_letter(column)].width = 16
            ws.row_dimensions[months_row].height = 30
            ws.row_dimensions[entered_row].height = 30
            ws.row_dimensions[flag_row].height = 30
            ws.row_dimensions[load_row].height = 32

            products = [row for row in (self.form.get("volume_sales") or []) if str(row.get("name") or "").strip()]
            revenue_rows: list[int] = []
            product_links: list[dict] = []
            row = 13
            for index, product in enumerate(products):
                name = str(product.get("name") or "").strip()
                unit = UNITS.get(int(_num(product.get("units")) or 1), "шт")
                season_months = {int(item) for item in (product.get("months") or []) if 1 <= int(item) <= 12}
                seasonal = bool(product.get("seasonal")) and bool(season_months)
                self._put(row, 1, name, font=SECTION)
                row += 1
                sales_row = row
                self._put(sales_row, 1, "Начало продаж", border=BOX)
                raw_start = str(product.get("sales_start") or "").strip()
                if len(raw_start) >= 7 and raw_start[4] == "-":
                    self._put(sales_row, 2, _form_date(raw_start), border=BOX, fmt="DD.MM.YYYY", align="center", fill=BLUE)
                else:
                    self._put(sales_row, 2, "=$B$4", border=BOX, fmt="DD.MM.YYYY", align="center")
                sales_cell = f"$B${sales_row}"
                row += 1
                use_entered, use_flag = entered_row, flag_row
                if product.get("own_capacity"):
                    own_entered = _entered_capacity({"planned_capacity": product.get("planned_capacity") or {}}, years)
                    self._put(row, 1, "Своя загрузка по годам", border=BOX, wrap=True)
                    self._put(row, 2, "%", border=BOX, align="center")
                    for offset, value in enumerate(own_entered):
                        self._put(row, 3 + offset, value, border=BOX, fmt=PCT_FINE, align="center", fill=BLUE)
                    capacity_ranges.append(f"$C${row}:${last_letter}${row}")
                    use_entered = row
                    row += 1
                    self._put(row, 1, "Год задан у продукта", border=BOX, wrap=True)
                    self._put(row, 2, "1/0", border=BOX, align="center")
                    for offset, value in enumerate(own_entered):
                        self._put(row, 3 + offset, 0 if value is None else 1, border=BOX, fmt="0", align="center")
                    use_flag = row
                    row += 1
                qty_row = row
                qty_label = "Количество в месяц при 100%" if monthly else "Количество за год при 100%"
                self._put(qty_row, 1, qty_label + ", из анкеты", border=BOX, wrap=True)
                self._put(qty_row, 2, unit, border=BOX, align="center")
                self._put(qty_row, 3, _num(product.get("count")), border=BOX, fmt=QTY, align="right", fill=BLUE)
                for offset in range(1, self.years):
                    self._put(qty_row, 3 + offset, f"=$C${qty_row}", border=BOX, fmt=QTY, align="right")
                row += 1
                product_months = row
                self._put(product_months, 1, "Месяцев продаж", border=BOX)
                self._put(product_months, 2, "мес.", border=BOX, align="center")
                season = season_months if seasonal else None
                for offset, _year_number in enumerate(years):
                    year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                    self._put(
                        product_months,
                        3 + offset,
                        _months_formula(year_cell, sales_cell, "$B$5", season),
                        border=BOX,
                        fmt="0",
                        align="center",
                    )
                row += 1
                load_col = 6 + index * 3
                qty_col = load_col + 1
                rev_col = load_col + 2
                annual_row = row
                self._put(annual_row, 1, "Количество за год, сумма месяцев", border=BOX, wrap=True)
                self._put(annual_row, 2, unit, border=BOX, align="center")
                for offset in range(self.years):
                    year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                    self._put(
                        annual_row,
                        3 + offset,
                        _sumif_months(year_cell, qty_col, month_first, month_last),
                        border=BOX,
                        fmt=QTY,
                        align="right",
                    )
                row += 1
                price_row = row
                self._put(price_row, 1, "Цена из анкеты, далее индекс", border=BOX, wrap=True)
                self._put(price_row, 2, money_unit, border=BOX, align="center")
                self._put(price_row, 3, _num(product.get("price")), border=BOX, fmt=MONEY, align="right", fill=BLUE)
                for offset in range(1, self.years):
                    self._put(
                        price_row,
                        3 + offset,
                        f"=$C${price_row}*{_price_index(offset)}",
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
                row += 1
                revenue_row = row
                self._put(revenue_row, 1, "Выручка", border=BOX, font=Font(name=FONT_NAME, size=10, bold=True))
                self._put(revenue_row, 2, money_unit, border=BOX, align="center")
                for offset in range(self.years):
                    year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                    self._put(
                        revenue_row,
                        3 + offset,
                        _sumif_months(year_cell, rev_col, month_first, month_last),
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                        font=Font(name=FONT_NAME, size=10, bold=True),
                    )
                revenue_rows.append(revenue_row)
                product_links.append(
                    {
                        "name": name,
                        "sales": f"'{REV}'!{sales_cell}",
                        "qty": f"$C${qty_row}",
                        "price_row": price_row,
                        "monthly": monthly,
                        "season": season_months if seasonal else set(),
                        "entered_row": use_entered,
                        "flag_row": use_flag,
                        "load_col": load_col,
                        "qty_col": qty_col,
                        "rev_col": rev_col,
                        "annual_row": annual_row,
                        "revenue_row": revenue_row,
                    }
                )
                row += 2

            other_link = None
            other = _num(self.form.get("other_income"))
            if other:
                self._put(row, 1, "Прочие доходы", font=SECTION)
                row += 1
                amount_row = row
                self._put(amount_row, 1, "Сумма из анкеты", border=BOX, wrap=True)
                self._put(amount_row, 2, other, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                row += 1
                start_row = row
                self._put(start_row, 1, "Дата начала", border=BOX)
                raw_other = str(self.form.get("other_income_start") or "").strip()
                if len(raw_other) >= 7 and raw_other[4] == "-":
                    self._put(start_row, 2, _form_date(raw_other), border=BOX, fmt="DD.MM.YYYY", align="center", fill=BLUE)
                else:
                    self._put(start_row, 2, "=$B$4", border=BOX, fmt="DD.MM.YYYY", align="center")
                row += 1
                period_row = row
                period = int(_num(self.form.get("other_income_period")) or 1)
                if period not in (1, 2, 3):
                    period = 1
                self._put(period_row, 1, "Периодичность: 1 месяц, 2 год, 3 разово", border=BOX, wrap=True)
                self._put(period_row, 2, period, border=BOX, fmt="0", align="center", fill=BLUE)
                self._put(
                    period_row,
                    3,
                    f'=IF(B{period_row}=2,"ежегодно",IF(B{period_row}=3,"разово","ежемесячно"))',
                    border=BOX,
                )
                ws.row_dimensions[period_row].height = 30
                row += 1
                other_year = row
                other_col = 6 + len(products) * 3
                self._put(other_year, 1, "За год", border=BOX, font=Font(name=FONT_NAME, size=10, bold=True))
                self._put(other_year, 2, money_unit, border=BOX, align="center")
                for offset in range(self.years):
                    year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                    self._put(
                        other_year,
                        3 + offset,
                        _sumif_months(year_cell, other_col, month_first, month_last),
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                        font=Font(name=FONT_NAME, size=10, bold=True),
                    )
                revenue_rows.append(other_year)
                other_link = {
                    "amount": f"'{REV}'!$B${amount_row}",
                    "start": f"'{REV}'!$B${start_row}",
                    "period": f"'{REV}'!$B${period_row}",
                    "col": other_col,
                }
                row += 2

            self.capacity_ranges_full = [f"'{REV}'!{item}" for item in capacity_ranges]
            self._put(
                warning_row,
                1,
                _capacity_warning(capacity_ranges),
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=warning_row, start_column=1, end_row=warning_row, end_column=2 + self.years)
            ws.row_dimensions[warning_row].height = 32
            self._revenue_links = {
                "first": month_first,
                "last": month_last,
                "entered_row": entered_row,
                "flag_row": flag_row,
                "products": product_links,
                "other": other_link,
                "total_row": row,
            }

            self._put(row, 1, "Итого выручка", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, 2, money_unit, border=BOX, fill=GREEN, align="center")
            for offset in range(self.years):
                letter = get_column_letter(3 + offset)
                total = "=" + "+".join(f"{letter}{item}" for item in revenue_rows) if revenue_rows else 0
                self._put(row, 3 + offset, total, border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
        finally:
            self.ws = previous

    def _month_sheet(self):
        """Помесячная загрузка и выручка. На «Плане доходов» остаются годовые итоги."""
        links = getattr(self, "_revenue_links", None)
        if not links:
            return
        ws = self.book.create_sheet(MONTHS)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "7F8C8D"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            ws.freeze_panes = "A5"
            dates = self._source_cells()
            plan_start = f"'{SHEET}'!{dates['plan_start']}"
            plan_end = f"'{SHEET}'!{dates['plan_end']}"
            full_cell = f"'{REV}'!$B$6"
            self._put(1, 1, "Помесячный расчёт. Годовые итоги стоят на листе «План доходов».", font=SECTION)
            self._put(
                2,
                1,
                "Конечный месяц входит полностью. Если год задан в анкете, каждый его месяц берёт этот процент. Пустой год растёт линейно от 0% в месяц начала продаж до 100% в месяц выхода на объём. Одинаковые даты дают 100% без деления на ноль. Свой график продукта заменяет график проекта.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            last_col = 5
            for product in links["products"]:
                last_col = max(last_col, product["rev_col"])
            if links["other"]:
                last_col = max(last_col, links["other"]["col"])
            ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=min(last_col, 8))
            ws.row_dimensions[2].height = 48
            headers = ((1, "Дата"), (2, "Год"), (3, "Месяц"), (4, "В периоде"), (5, "Загрузка проекта"))
            for column, title in headers:
                self._put(4, column, title, border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            for product in links["products"]:
                self._put(3, product["load_col"], product["name"], font=SECTION)
                ws.merge_cells(start_row=3, start_column=product["load_col"], end_row=3, end_column=product["rev_col"])
                for column, title in ((product["load_col"], "Загрузка"), (product["qty_col"], "Количество"), (product["rev_col"], "Выручка")):
                    self._put(4, column, title, border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            if links["other"]:
                self._put(3, links["other"]["col"], "Прочие доходы", font=SECTION)
                self._put(4, links["other"]["col"], "Сумма", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            ws.column_dimensions["A"].width = 14
            for column in range(2, last_col + 1):
                ws.column_dimensions[get_column_letter(column)].width = 16

            first = links["first"]
            for row in range(first, links["last"] + 1):
                if row == first:
                    date_value = f"=DATE(YEAR({plan_start}),MONTH({plan_start}),1)"
                else:
                    date_value = f"=EDATE(A{row - 1},1)"
                self._put(row, 1, date_value, border=BOX, fmt="DD.MM.YYYY", align="center")
                self._put(row, 2, f"=YEAR(A{row})", border=BOX, fmt="0", align="center")
                self._put(row, 3, f"=MONTH(A{row})", border=BOX, fmt="0", align="center")
                self._put(
                    row,
                    4,
                    f"=IF(AND(A{row}>=DATE(YEAR({plan_start}),MONTH({plan_start}),1),A{row}<=DATE(YEAR({plan_end}),MONTH({plan_end}),1)),1,0)",
                    border=BOX,
                    fmt="0",
                    align="center",
                )
                self._put(
                    row,
                    5,
                    _month_load_formula(
                        f"A{row}",
                        f"D{row}",
                        f"'{REV}'!$B$4",
                        full_cell,
                        f"B{row}",
                        links["entered_row"],
                        links["flag_row"],
                        self.years,
                    ),
                    border=BOX,
                    fmt=PCT_FINE,
                    align="center",
                )
                for product in links["products"]:
                    load_cell = f"{get_column_letter(product['load_col'])}{row}"
                    qty_cell = f"{get_column_letter(product['qty_col'])}{row}"
                    self._put(
                        row,
                        product["load_col"],
                        _month_load_formula(
                            f"A{row}",
                            f"D{row}",
                            product["sales"],
                            full_cell,
                            f"B{row}",
                            product["entered_row"],
                            product["flag_row"],
                            self.years,
                        ),
                        border=BOX,
                        fmt=PCT_FINE,
                        align="center",
                    )
                    self._put(
                        row,
                        product["qty_col"],
                        _month_qty_formula(f"C{row}", load_cell, product["qty"], product["monthly"], product["season"]),
                        border=BOX,
                        fmt=QTY,
                        align="right",
                    )
                    self._put(
                        row,
                        product["rev_col"],
                        _month_revenue_formula(qty_cell, f"B{row}", product["price_row"], self.years),
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
                if links["other"]:
                    self._put(
                        row,
                        links["other"]["col"],
                        _other_month_formula(
                            row,
                            links["other"]["amount"],
                            links["other"]["start"],
                            links["other"]["period"],
                            f"B{row}",
                            self.years,
                        ),
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
        finally:
            self.ws = previous

    def _cost_sheet(self):
        """Отдельный лист: в Радуге себестоимости нет. Количество берётся с плана доходов, индекс — со строки 48."""
        self.goods_cash = []
        self.variable_cash = []
        links = getattr(self, "_revenue_links", None)
        if not links:
            return
        ws = self.book.create_sheet(COST)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "C65911"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            money_unit = {"kzt": "тенге", "usd": "доллар", "eur": "евро"}.get(str(self.form.get("currency") or "kzt"), "тенге")
            ws.column_dimensions["A"].width = 46
            ws.column_dimensions["B"].width = 16
            for offset in range(self.years):
                ws.column_dimensions[get_column_letter(3 + offset)].width = 16

            self._put(1, 1, "Показатель", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(1, 2, "Ед.", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            for offset in range(self.years):
                source = get_column_letter(YEAR_COLUMN + offset)
                self._put(1, 3 + offset, f"='{SHEET}'!{source}6&\" год\"", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))

            self._put(
                2,
                1,
                "На единицу — количество с «Плана доходов» × цена анкеты. По графику — «каждые 2 месяца» значит платёж через один, с начала продаж, конечный месяц входит. Обе статьи складываются. Индекс — переменные затраты, строка 48 / база C48. Первоначальный запас из структуры финансирования сюда не входит.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=2 + self.years)
            ws.row_dimensions[2].height = 48

            total_rows: list[int] = []
            row = 4
            costs = [
                item
                for item in (self.form.get("variable_costs") or [])
                if str(item.get("cost_type") or "").strip() or _num(item.get("price")) or str(item.get("product") or "").strip()
            ]
            if costs:
                self._put(row, 1, "На единицу", font=SECTION)
                row += 1
                used: set[int] = set()
                for product in links["products"]:
                    matched = []
                    for index, item in enumerate(costs):
                        if index in used:
                            continue
                        if str(item.get("product") or "").strip() != product["name"]:
                            continue
                        matched.append(item)
                        used.add(index)
                    if not matched:
                        continue
                    self._put(row, 1, product["name"], font=SECTION)
                    row += 1
                    component_rows = []
                    for item in matched:
                        self._put(row, 1, str(item.get("cost_type") or "Затрата").strip() or "Затрата", border=BOX, wrap=True)
                        self._put(row, 2, money_unit, border=BOX, align="center")
                        self._put(row, 3, _num(item.get("price")), border=BOX, fmt=MONEY, align="right", fill=BLUE)
                        component_rows.append(row)
                        row += 1
                    unit_row = row
                    self._put(unit_row, 1, "Итого на единицу", border=BOX, font=Font(name=FONT_NAME, size=10, bold=True))
                    self._put(unit_row, 2, money_unit, border=BOX, align="center")
                    unit_total = "=" + "+".join(f"C{item}" for item in component_rows) if component_rows else 0
                    self._put(unit_row, 3, unit_total, border=BOX, fmt=MONEY, align="right", font=Font(name=FONT_NAME, size=10, bold=True))
                    row += 1
                    index_row = row
                    self._put(index_row, 1, "На единицу с индексом затрат", border=BOX, wrap=True)
                    self._put(index_row, 2, money_unit, border=BOX, align="center")
                    for offset in range(self.years):
                        self._put(
                            index_row,
                            3 + offset,
                            f"=$C${unit_row}*{_cost_index(offset)}",
                            border=BOX,
                            fmt=MONEY,
                            align="right",
                        )
                    row += 1
                    self.goods_cash.append({"qty_col": product["qty_col"], "index_row": index_row})
                    self._put(row, 1, "Количество за год", border=BOX)
                    self._put(row, 2, "как в плане доходов", border=BOX, align="center")
                    for offset in range(self.years):
                        letter = get_column_letter(3 + offset)
                        self._put(row, 3 + offset, f"='{REV}'!{letter}{product['annual_row']}", border=BOX, fmt=QTY, align="right")
                    qty_row = row
                    row += 1
                    cogs_row = row
                    self._put(cogs_row, 1, "Себестоимость", border=BOX, font=Font(name=FONT_NAME, size=10, bold=True))
                    self._put(cogs_row, 2, money_unit, border=BOX, align="center")
                    for offset in range(self.years):
                        letter = get_column_letter(3 + offset)
                        self._put(
                            cogs_row,
                            3 + offset,
                            f"={letter}{index_row}*{letter}{qty_row}",
                            border=BOX,
                            fmt=MONEY,
                            align="right",
                            font=Font(name=FONT_NAME, size=10, bold=True),
                        )
                    total_rows.append(cogs_row)
                    row += 2
                missed = [item for index, item in enumerate(costs) if index not in used and (str(item.get("cost_type") or "").strip() or _num(item.get("price")))]
                if missed:
                    self._put(row, 1, "Не попали в расчёт: нет такого товара в плане доходов", font=SECTION)
                    row += 1
                    for item in missed:
                        title = str(item.get("cost_type") or "Затрата").strip() or "Затрата"
                        product_name = str(item.get("product") or "").strip()
                        self._put(row, 1, f"{title} ({product_name})" if product_name else title, border=BOX, wrap=True)
                        self._put(row, 2, money_unit, border=BOX, align="center")
                        self._put(row, 3, _num(item.get("price")), border=BOX, fmt=MONEY, align="right", fill=BLUE)
                        row += 1
                    row += 1
            periodic = [
                item
                for item in (self.form.get("variable_other") or [])
                if str(item.get("name") or "").strip() or _num(item.get("value"))
            ]
            if periodic:
                self._put(row, 1, "По графику", font=SECTION)
                row += 1
                first, last = links["first"], links["last"]
                dates = f"'{MONTHS}'!$A${first}:$A${last}"
                years = f"'{MONTHS}'!$B${first}:$B${last}"
                inside = f"'{MONTHS}'!$D${first}:$D${last}"
                start = f"'{REV}'!$B$4"
                for item in periodic:
                    title = str(item.get("name") or "").strip()
                    amount = _num(item.get("value"))
                    self._put(row, 1, title or "Переменный расход", font=SECTION)
                    row += 1
                    amount_row = row
                    self._put(amount_row, 1, "Сумма из анкеты", border=BOX)
                    self._put(amount_row, 2, amount, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                    row += 1
                    period_row = row
                    raw_period = item.get("period")
                    period = 1 if raw_period in (None, "") else int(_num(raw_period))
                    self._put(period_row, 1, "Период, месяцев (0 — разово)", border=BOX, wrap=True)
                    self._put(period_row, 2, period, border=BOX, fmt="0", align="center", fill=BLUE)
                    self.variable_cash.append({"amount_row": amount_row, "period_row": period_row})
                    row += 1
                    cogs_row = row
                    self._put(cogs_row, 1, "За год", border=BOX, font=Font(name=FONT_NAME, size=10, bold=True))
                    self._put(cogs_row, 2, money_unit, border=BOX, align="center")
                    elapsed = f"((YEAR({dates})-YEAR({start}))*12+MONTH({dates})-MONTH({start}))"
                    once = (
                        f"SUMPRODUCT(({years}={{year}})*({inside}=1)*(YEAR({dates})=YEAR({start}))*(MONTH({dates})=MONTH({start})))"
                    )
                    regular = (
                        f"SUMPRODUCT(({years}={{year}})*({inside}=1)*({dates}>=DATE(YEAR({start}),MONTH({start}),1))"
                        f"*(MOD({elapsed},$B${period_row})=0))"
                    )
                    for offset in range(self.years):
                        year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                        payments = f"IF($B${period_row}<=0,{once.format(year=year_cell)},{regular.format(year=year_cell)})"
                        self._put(
                            cogs_row,
                            3 + offset,
                            f"=$B${amount_row}*{_cost_index(offset)}*{payments}",
                            border=BOX,
                            fmt=MONEY,
                            align="right",
                            font=Font(name=FONT_NAME, size=10, bold=True),
                        )
                    total_rows.append(cogs_row)
                    row += 2

            self._put(row, 1, "Итого себестоимость", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, 2, money_unit, border=BOX, fill=GREEN, align="center")
            for offset in range(self.years):
                letter = get_column_letter(3 + offset)
                total = "=" + "+".join(f"{letter}{item}" for item in total_rows) if total_rows else 0
                self._put(row, 3 + offset, total, border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self.cogs_total_row = row
        finally:
            self.ws = previous

    def _payroll_sheet(self):
        """Лист «ФОТ» как в Радуге: оклад, численность по годам и отчисления работодателя."""
        links = getattr(self, "_revenue_links", None)
        if not links:
            return
        ws = self.book.create_sheet(PAY)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "641AEA"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            last_col = 3 + self.years * 2
            ws.column_dimensions["A"].width = 46
            ws.column_dimensions["B"].width = 14
            ws.column_dimensions["C"].width = 18
            for column in range(4, last_col + 1):
                ws.column_dimensions[get_column_letter(column)].width = 16

            self._put(
                1,
                1,
                "Оклад × численность × месяцы работы × индекс зарплат (строка 50 / база C50), рост 5%. Численность следующего года равна предыдущей. Неполный год берёт месяцы с листа «Месяцы», конечный месяц входит. «Только на время запуска» — только до начала продаж. Процент — от выручки тех месяцев, когда сотрудник в штате. СО — ставка года от начисления минус ОПВ работника 10% (не выше 50 МЗП), затем база ограничивается 1–7 МЗП. ОПВР и ООСМС считаются от начисления, не от ОПВ. Ноль в колонке отчисления отключает только его. ФОТ АУП и ФОТ производства складываются один раз.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=last_col + 5)
            ws.row_dimensions[1].height = 64

            self._put(2, 1, "Наименование должности", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(3, 1, None, border=BOX, fill=GRAY)
            ws.merge_cells("A2:A3")
            self._put(2, 2, "% от дохода", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(3, 2, None, border=BOX, fill=GRAY)
            ws.merge_cells("B2:B3")
            self._put(2, 3, "Заработная плата", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(3, 3, None, border=BOX, fill=GRAY)
            ws.merge_cells("C2:C3")
            ws.row_dimensions[2].height = 22
            ws.row_dimensions[3].height = 22
            for offset in range(self.years):
                count_col = 4 + offset * 2
                fot_col = count_col + 1
                source = get_column_letter(YEAR_COLUMN + offset)
                self._put(2, count_col, f"='{SHEET}'!{source}6", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
                self._put(2, fot_col, None, border=BOX, fill=GRAY)
                ws.merge_cells(start_row=2, start_column=count_col, end_row=2, end_column=fot_col)
                self._put(3, count_col, "Количество", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
                self._put(3, fot_col, "ФОТ", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))

            people = [item for item in (self.form.get("salary") or []) if str(item.get("name") or "").strip()]
            groups = (
                ("Административно-управленческий персонал (АУП)", "АУП", [item for item in people if not _staff_industrial(item)]),
                ("Производственный персонал", "ПП", [item for item in people if _staff_industrial(item)]),
            )
            sales = f"'{REV}'!$B$4"
            first, last = links["first"], links["last"]
            flag_col = last_col + 2
            so_col = flag_col + 1
            osms_col = flag_col + 2
            opv_col = flag_col + 3
            for column, title, hint in (
                (flag_col, "ОПВР", "1 — платить"),
                (so_col, "СО", "1 — начислять"),
                (osms_col, "ООСМС", "1 — начислять"),
                (opv_col, "ОПВ работника", "1 — удерживать"),
            ):
                self._put(2, column, title, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
                self._put(3, column, hint, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
                ws.column_dimensions[get_column_letter(column)].width = 14
            row = 4
            roster: list[dict] = []
            for title, group_key, group in groups:
                if not group:
                    continue
                self._put(row, 1, title, font=SECTION)
                row += 1
                for person in group:
                    self._put(row, 1, str(person.get("name") or "").strip(), border=BOX)
                    self._put(row, 2, _num(person.get("percent")), border=BOX, fmt="0", align="center", fill=BLUE)
                    self._put(row, 3, _num(person.get("salary")), border=BOX, fmt=MONEY, align="right", fill=BLUE)
                    self._put(row, flag_col, 0 if person.get("opvr_exempt") else 1, border=BOX, fmt="0", align="center", fill=BLUE)
                    self._put(row, so_col, 0 if person.get("so_exempt") else 1, border=BOX, fmt="0", align="center", fill=BLUE)
                    self._put(row, osms_col, 0 if person.get("osms_exempt") else 1, border=BOX, fmt="0", align="center", fill=BLUE)
                    self._put(row, opv_col, 0 if person.get("opv_exempt") else 1, border=BOX, fmt="0", align="center", fill=BLUE)
                    launch = bool(person.get("invest"))
                    for offset in range(self.years):
                        count_col = 4 + offset * 2
                        if offset == 0:
                            count_value = _num(person.get("count"))
                            count_fill = BLUE
                        else:
                            count_value = f"={get_column_letter(count_col - 2)}{row}"
                            count_fill = None
                        self._put(row, count_col, count_value, border=BOX, fmt="0", align="center", fill=count_fill)
                        self._put(row, count_col + 1, None, border=BOX, fmt=MONEY, align="right")
                    roster.append({"row": row, "launch": launch, "group": group_key, "services": person.get("services") or []})
                    row += 1

            if not roster:
                self._put(row, 1, "Штат в анкете не указан", font=SECTION)
                row += 1
            self._write_payroll_totals(row, roster, links, sales, first, last, flag_col, so_col, osms_col, opv_col)
        finally:
            self.ws = previous

    def _admin_sheet(self):
        """Лист «Адм расходы» как в Радуге: сумма в месяц × платежи года × индекс постоянных затрат."""
        self.admin_rows = []
        self.admin_amount_col = 3 + self.years
        self.admin_period_col = self.admin_amount_col + 1
        self.admin_flag_col = self.admin_period_col + 1
        links = getattr(self, "_revenue_links", None)
        if not links:
            return
        ws = self.book.create_sheet(ADM)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "7030A0"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            month_col = 3 + self.years
            period_col = month_col + 1
            flag_col = period_col + 1
            ws.column_dimensions["A"].width = 46
            ws.column_dimensions["B"].width = 12
            for column in range(3, flag_col + 1):
                ws.column_dimensions[get_column_letter(column)].width = 16

            self._put(
                1,
                1,
                "Логика расчёта: сумма в месяц × число платежей в году × индекс постоянных затрат (стр. 49 / C49). Период 0 — один раз, 1 — каждый месяц, 12 — раз в год. Колонка «Запуск» (= invest в Радуге): 1 — платит с начала проекта и дальше по всем годам (в каждом году — свои месяцы), 0 — только с даты начала продаж. ИТОГО — все строки. Строка «С начала проекта» — только с флагом 1 (уже входит в ИТОГО). ФОТ на этот лист не переносится.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=flag_col)
            ws.row_dimensions[1].height = 48
            self._put(2, 1, "Наименование", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, 2, "Ед.", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            for offset in range(self.years):
                source = get_column_letter(YEAR_COLUMN + offset)
                self._put(2, 3 + offset, f"='{SHEET}'!{source}6", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, month_col, "в месяц", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, period_col, "Период", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, flag_col, "С нач. проекта", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))

            groups = (
                ("Административные", self.form.get("admin_expenses") or []),
                ("Маркетинг", self.form.get("marketing_expenses") or []),
            )
            sales = f"'{REV}'!$B$4"
            plan = f"'{SHEET}'!{self._source_cells()['plan_start']}"
            first, last = links["first"], links["last"]
            money_unit = {"kzt": "тенге", "usd": "доллар", "eur": "евро"}.get(str(self.form.get("currency") or "kzt"), "тенге")
            row = 3
            data_rows: list[int] = []
            for title, source in groups:
                items = [item for item in source if _expense_amount(item) > 0]
                if not items:
                    continue
                self._put(row, 1, title, font=SECTION)
                row += 1
                for item in items:
                    raw_period = item.get("period")
                    period = 1 if raw_period in (None, "") else int(_num(raw_period))
                    name = _expense_title(item)
                    land_area = _num(item.get("land_area"))
                    land_rate = _num(item.get("land_rate"))
                    land_unit = str(item.get("land_unit") or "m2").strip().lower()
                    is_ha = land_unit in ("ha", "га", "hectare", "hectares")
                    is_land = _is_land_expense(item)
                    if is_land and (land_area > 0 or land_rate > 0):
                        unit_label = "га" if is_ha else "м²"
                        area_text = f"{land_area:g}".replace(".", ",")
                        rate_text = f"{land_rate:g}".replace(".", ",")
                        # Режим площади из анкеты: сумма = площадь × ставка в м² или га.
                        name = f"{name} ({area_text} {unit_label} × {rate_text} {money_unit}/{unit_label})"
                        amount_value = f"={land_area}*{land_rate}"
                        amount_unit = money_unit
                    else:
                        amount_value = _expense_amount(item)
                        amount_unit = money_unit
                    self._put(row, 1, name, border=BOX, wrap=True)
                    self._put(row, 2, amount_unit, border=BOX, align="center")
                    amount_cell = f"${get_column_letter(month_col)}${row}"
                    period_cell = f"${get_column_letter(period_col)}${row}"
                    flag_cell = f"${get_column_letter(flag_col)}${row}"
                    for offset in range(self.years):
                        year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                        payments = _expense_payments(year_cell, first, last, plan, sales, period_cell, flag_cell)
                        self._put(
                            row,
                            3 + offset,
                            f"={amount_cell}*{_fixed_index(offset)}*({payments})",
                            border=BOX,
                            fmt=MONEY,
                            align="right",
                        )
                    self._put(row, month_col, amount_value, border=BOX, fmt=MONEY, align="right", fill=BLUE)
                    self._put(row, period_col, 12 if is_land and period <= 0 else period, border=BOX, fmt="0", align="center", fill=BLUE)
                    self._put(row, flag_col, 1 if item.get("invest") else 0, border=BOX, fmt="0", align="center", fill=BLUE)
                    data_rows.append(row)
                    row += 1

            if not data_rows:
                self._put(row, 1, "Расходы в анкете не указаны", font=SECTION)
                row += 1
            flag_letter = get_column_letter(flag_col)
            month_letter = get_column_letter(month_col)

            def sum_rows(rows_list: list[int], letter: str) -> str:
                if not rows_list:
                    return "=0"
                return "=" + "+".join(f"{letter}{item}" for item in rows_list)

            def flagged(rows_list: list[int], letter: str, on: int) -> str:
                if not rows_list:
                    return "=0"
                return "=" + "+".join(f"{letter}{item}*({flag_letter}{item}={on})" for item in rows_list)

            # ИТОГО — все расходы (и с флагом invest, и без). В ОПиУ идёт эта строка.
            self._put(row, 1, "ИТОГО", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, 2, money_unit, border=BOX, fill=GREEN, align="center")
            for offset in range(self.years):
                letter = get_column_letter(3 + offset)
                self._put(row, 3 + offset, sum_rows(data_rows, letter), border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, month_col, sum_rows(data_rows, month_letter), border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, period_col, None, border=BOX, fill=GREEN)
            self._put(row, flag_col, None, border=BOX, fill=GREEN)
            self.admin_total_row = row
            row += 1
            # Подсказка: строки с флагом 1 (уже входят в ИТОГО) — без отдельного вычета в ОПиУ.
            self._put(row, 1, "в т.ч. с начала проекта", border=BOX, wrap=True)
            self._put(row, 2, money_unit, border=BOX, align="center")
            for offset in range(self.years):
                letter = get_column_letter(3 + offset)
                self._put(row, 3 + offset, flagged(data_rows, letter, 1), border=BOX, fmt=MONEY, align="right")
            self._put(row, month_col, flagged(data_rows, month_letter, 1), border=BOX, fmt=MONEY, align="right")
            self._put(row, period_col, None, border=BOX)
            self._put(row, flag_col, None, border=BOX)
            self.admin_launch_row = row
            self.admin_rows = data_rows
        finally:
            self.ws = previous

    def _prepare_capex(self):
        self.capex_items = _capex_items(self.form)
        self.capex_first = 3
        count = len(self.capex_items)
        self.capex_last = 2 + count
        self.capex_total_row = 3 if count == 0 else self.capex_last + 1
        self.fin_total_row = 4
        self.fin_first = None
        self.fin_last = None
        self.fin_deficit_row = None
        self.capex_excess_row = None

    def _financing_gap(self, wacc_row: int):
        """Прогноз CAPEX увеличивает потребность. Добавка индексации уже включена в источник."""
        total = f"'{CAPEX}'!C{self.capex_total_row}"
        forecast = f"'{CAPEX}'!H{self.capex_total_row}"
        if self.fin_first:
            invested = f'SUMIF(I{self.fin_first}:I{self.fin_last},"Капитальные затраты",C{self.fin_first}:C{self.fin_last})'
        else:
            invested = "0"
        row = wacc_row + 2
        self._put(row, 2, "CAPEX по анкете, реестр", border=GREY_BOX, wrap=True)
        self._put(row, 3, f"={total}", border=GREY_BOX, fmt=MONEY, align="right")
        base_row = row
        row += 1
        self._put(row, 2, "CAPEX в инвестиционных затратах", border=GREY_BOX, wrap=True)
        self._put(row, 3, f"={invested}", border=GREY_BOX, fmt=MONEY, align="right")
        invested_row = row
        row += 1
        self._put(row, 2, "CAPEX прогноз", border=GREY_BOX, wrap=True)
        self._put(row, 3, f"={forecast}", border=GREY_BOX, fmt=MONEY, align="right")
        forecast_row = row
        row += 1
        self._put(row, 2, "Добавка индексации", border=GREY_BOX, wrap=True)
        self._put(row, 3, f"=C{forecast_row}-C{base_row}", border=GREY_BOX, fmt=MONEY, align="right")
        row += 1
        self._put(row, 2, "Потребность с индексацией", border=GREY_BOX, wrap=True)
        self._put(row, 3, f"=C{self.fin_total_row}-C{invested_row}+C{forecast_row}", border=GREY_BOX, fmt=MONEY, align="right", fill=GREEN)
        need_row = row
        row += 1
        self._put(row, 2, "Дефицит источников", border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(
            row,
            3,
            f"=MAX(0,C{forecast_row}-C{invested_row}-C{self.fin_last})",
            border=GREY_BOX,
            fmt=MONEY,
            align="right",
            fill=PINK,
            font=Font(name=FONT_NAME, size=10, bold=True),
        )
        self.fin_deficit_row = row
        row += 1
        self._put(
            row,
            2,
            "Добавка индексации включена в источник: инвестору, в кредит или в свои средства. Если дефицит остался, в анкете не хватает денег на сами строки, модель его не размазывает.",
            wrap=True,
            font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
        )
        self.ws.merge_cells(start_row=row, start_column=2, end_row=row, end_column=8)
        self.ws.row_dimensions[row].height = 28

    def _capex_register(self):
        """Единственное место, где из базы и индекса получается прогнозная стоимость CAPEX."""
        ws = self.book.create_sheet(CAPEX)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "1B4F72"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            widths = {"A": 32, "B": 18}
            for column in range(3, 14):
                widths[get_column_letter(column)] = 16
            for letter, width in widths.items():
                ws.column_dimensions[letter].width = width
            self._put(
                1,
                1,
                "Единственный расчёт CAPEX. Прогноз = база × индекс строки 46 за год покупки. Дата оплаты и дата ввода задаются отдельно; если месяц не указан, для оплаты берётся первый месяц года внутри плана (в год старта — месяц начала плана), без подмены на 1 января при явной дате. Дата ввода по умолчанию равна оплате. Уже купленный актив: «К оплате» = 0, амортизация от балансовой стоимости. Срок 0 — не капитализируется. ДДС платит колонку «К оплате» один раз.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells("A1:N1")
            ws.row_dimensions[1].height = 62
            headers = (
                "Наименование",
                "Категория",
                "База",
                "Год",
                "Срок, лет",
                "Капитализируется",
                "Индекс CAPEX",
                "Прогноз",
                "Дата оплаты",
                "Дата ввода",
                "Начало амортизации",
                "Амортизируемая стоимость",
                "К оплате",
                "Уже куплен",
            )
            for column, text in enumerate(headers, start=1):
                self._put(2, column, text, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            ws.row_dimensions[2].height = 32
            dates = self._source_cells()
            plan_start = f"'{SHEET}'!{dates['plan_start']}"
            plan_end = f"'{SHEET}'!{dates['plan_end']}"
            first = self.capex_first
            for offset, item in enumerate(self.capex_items):
                row = first + offset
                year_cell = f"$D{row}"
                owned = 1 if item.get("already_owned") else 0
                book = _num(item.get("book_value"))
                pay_lit = _month_date_literal(item.get("pay_month") or "")
                commission_lit = _month_date_literal(item.get("commission_month") or "")
                year_fallback = _capex_date(year_cell, plan_start, plan_end)
                if pay_lit:
                    pay_formula = _clamp_month(pay_lit, plan_start, plan_end)
                else:
                    pay_formula = year_fallback
                if commission_lit:
                    commission_formula = _clamp_month(commission_lit, plan_start, plan_end)
                else:
                    commission_formula = f"I{row}"
                self._put(row, 1, item["name"], border=BOX, wrap=True)
                self._put(row, 2, item["category"], border=BOX, align="center")
                self._put(row, 3, item["price"], border=BOX, fmt=MONEY, align="right", fill=BLUE)
                self._put(row, 4, item["year"], border=BOX, fmt="0", align="center", fill=BLUE)
                self._put(row, 5, item["life"], border=BOX, fmt="0", align="center", fill=BLUE)
                self._put(row, 6, f"=IF(E{row}>0,1,0)", border=BOX, fmt="0", align="center")
                self._put(row, 7, f"={_capex_purchase_index(year_cell, self.years)}", border=BOX, fmt="0.000", align="center")
                self._put(row, 8, f"=C{row}*G{row}", border=BOX, fmt=MONEY, align="right")
                self._put(row, 9, f"={pay_formula}", border=BOX, fmt="DD.MM.YYYY", align="center", fill=BLUE if pay_lit else None)
                self._put(row, 10, f"={commission_formula}", border=BOX, fmt="DD.MM.YYYY", align="center", fill=BLUE if commission_lit else None)
                self._put(row, 11, f"=J{row}", border=BOX, fmt="DD.MM.YYYY", align="center")
                # Уже купленный: амортизируем балансовую стоимость без повторной индексации покупки.
                if owned:
                    self._put(row, 12, f"=IF(F{row}=1,{book if book > 0 else f'C{row}'},0)", border=BOX, fmt=MONEY, align="right", fill=BLUE)
                    self._put(row, 13, 0, border=BOX, fmt=MONEY, align="right")
                else:
                    self._put(row, 12, f"=IF(F{row}=1,H{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 13, f"=H{row}", border=BOX, fmt=MONEY, align="right")
                self._put(row, 14, owned, border=BOX, fmt="0", align="center", fill=BLUE)
            total = self.capex_total_row
            self._put(total, 1, "Итого", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(total, 2, None, border=BOX, fill=GREEN)
            if self.capex_items:
                last = self.capex_last
                sums = {3: "C", 8: "H", 12: "L", 13: "M"}
                for column in range(3, 15):
                    letter = get_column_letter(column)
                    value = f"=SUM({letter}{first}:{letter}{last})" if column in sums else None
                    fmt = "0.000" if column == 7 else (MONEY if column in sums else None)
                    self._put(total, column, value, border=BOX, fmt=fmt, align="right" if column >= 3 else "center", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            else:
                for column, value in ((3, 0), (8, 0), (12, 0), (13, 0)):
                    self._put(total, column, value, border=BOX, fmt=MONEY, align="right", fill=GREEN)
            ws.column_dimensions["N"].width = 12

            row = total + 2
            self._put(row, 1, "Проверки", font=SECTION)
            row += 1
            base_row = row
            self._put(row, 1, "База реестра", border=BOX)
            self._put(row, 3, f"=C{total}", border=BOX, fmt=MONEY, align="right")
            row += 1
            invested_row = row
            if self.fin_first:
                invested = f"SUMIF('{FIN}'!I{self.fin_first}:I{self.fin_last},\"Капитальные затраты\",'{FIN}'!C{self.fin_first}:C{self.fin_last})"
            else:
                invested = "0"
            self._put(row, 1, "CAPEX в инвестиционных затратах", border=BOX, wrap=True)
            self._put(row, 3, f"={invested}", border=BOX, fmt=MONEY, align="right")
            row += 1
            self._put(row, 1, "База реестра = CAPEX в затратах", border=BOX, wrap=True)
            self._put(row, 3, f'=IF(ABS(C{base_row}-C{invested_row})<1,"сходится","расхождение")', border=BOX, align="left")
            row += 1
            forecast_row = row
            self._put(row, 1, "Прогноз CAPEX", border=BOX)
            self._put(row, 3, f"=H{total}", border=BOX, fmt=MONEY, align="right")
            row += 1
            pay_row = row
            self._put(row, 1, "План оплат для ДДС", border=BOX)
            self._put(row, 3, f"=M{total}", border=BOX, fmt=MONEY, align="right")
            row += 1
            self._put(row, 1, "Платежи CAPEX = план оплат", border=BOX, wrap=True)
            self._put(row, 3, f'=IF(ABS(C{pay_row}-C{forecast_row})<1,"сходится, ДДС платит эту сумму один раз","расхождение")', border=BOX, align="left", wrap=True)
            ws.row_dimensions[row].height = 28
            row += 1
            self._put(row, 1, "Амортизируемая стоимость", border=BOX, wrap=True)
            self._put(row, 3, f"=L{total}", border=BOX, fmt=MONEY, align="right")
            row += 1
            self.capex_excess_row = row
            self._put(row, 1, "Износ сверх амортизируемой стоимости", border=BOX, wrap=True)
            self._put(row, 3, 0, border=BOX, fmt=MONEY, align="right")
            row += 1
            self._put(row, 1, "Износ не выше стоимости", border=BOX)
            self._put(row, 3, f'=IF(C{self.capex_excess_row}<1,"не превышает","превышение")', border=BOX, align="left")
            row += 1
            if self.fin_first:
                operating = (
                    f"SUMIF('{FIN}'!I{self.fin_first}:I{self.fin_last},\"Оборотный капитал\",'{FIN}'!C{self.fin_first}:C{self.fin_last})"
                    f"+SUMIF('{FIN}'!I{self.fin_first}:I{self.fin_last},\"Операционные расходы\",'{FIN}'!C{self.fin_first}:C{self.fin_last})"
                )
            else:
                operating = "0"
            self._put(row, 1, "Оборотные и операционные вне CAPEX", border=BOX, wrap=True)
            self._put(row, 3, f"={operating}", border=BOX, fmt=MONEY, align="right")
            row += 1
            if self.capex_items:
                duplicate = (
                    f'COUNTIF(B{first}:B{self.capex_last},"Оборотный капитал")'
                    f'+COUNTIF(B{first}:B{self.capex_last},"Операционные расходы")'
                )
            else:
                duplicate = "0"
            self._put(row, 1, "Запасы и операционные не в реестре", border=BOX, wrap=True)
            self._put(row, 3, f'=IF({duplicate}=0,"не дублируются","дубль")', border=BOX, align="left")
            row += 1
            deficit_row = row
            self._put(row, 1, "Дефицит источников", border=BOX)
            if self.fin_deficit_row:
                self._put(row, 3, f"='{FIN}'!C{self.fin_deficit_row}", border=BOX, fmt=MONEY, align="right", fill=PINK)
            else:
                self._put(row, 3, 0, border=BOX, fmt=MONEY, align="right", fill=PINK)
            row += 1
            self._put(row, 1, "Покрытие потребности", border=BOX)
            self._put(
                row,
                3,
                f'=IF(C{deficit_row}<1,"покрыто, индексация в источнике","дефицит анкеты остался, индексация уже в источнике")',
                border=BOX,
                align="left",
                wrap=True,
            )
            ws.row_dimensions[row].height = 28
        finally:
            self.ws = previous

    def _capex_on_premises(self):
        """Индекс остаётся на «Предпосылках». Итоги берутся из реестра, второй раз не считаются."""
        previous = self.ws
        self.ws = self.book[SHEET]
        try:
            self._put(
                52,
                1,
                "Реестр CAPEX берёт коэффициент только из строки 46 и только за год покупки.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            self.ws.merge_cells("A52:B52")
            self._put(53, 1, "CAPEX по анкете", border=GREY_BOX)
            self._put(53, 3, f"='{CAPEX}'!C{self.capex_total_row}", border=GREY_BOX, fmt=MONEY, align="right")
            self._put(54, 1, "CAPEX прогноз", border=GREY_BOX)
            self._put(54, 3, f"='{CAPEX}'!H{self.capex_total_row}", border=GREY_BOX, fmt=MONEY, align="right")
        finally:
            self.ws = previous

    def _amort_sheet(self):
        """Лист «Амортизация» как в Радуге. Стоимость и даты берутся из реестра CAPEX."""
        links = getattr(self, "_revenue_links", None)
        if not links:
            return
        ws = self.book.create_sheet(AMORT)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "833C0C"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            pay_col = 3 + self.years
            comm_col = pay_col + 1
            start_col = pay_col + 2
            ws.column_dimensions["A"].width = 48
            ws.column_dimensions["B"].width = 18
            for column in range(3, start_col + 1):
                ws.column_dimensions[get_column_letter(column)].width = 16

            self._put(
                1,
                1,
                "Логика расчёта: прямая линия, ставка = 1 / срок. Износ года = амортизируемая стоимость из реестра CAPEX × ставка × месяцы с начала амортизации / 12 и не больше остатка. Дата оплаты, дата ввода и начало амортизации приходят из реестра и здесь второй раз не считаются. Срок 0 не амортизируется. Оборотные запасы и операционные расходы на этот лист не попадают.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=start_col)
            ws.row_dimensions[1].height = 48
            self._put(2, 1, "Наименование", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, 2, "Значение", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            for offset in range(self.years):
                source = get_column_letter(YEAR_COLUMN + offset)
                self._put(2, 3 + offset, f"='{SHEET}'!{source}6", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, pay_col, "Дата оплаты", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, comm_col, "Дата ввода", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(2, start_col, "Начало амортизации", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))

            grouped: dict[str, list[dict]] = {title: [] for _key, title in _CAPEX_GROUPS}
            for index, item in enumerate(self.capex_items):
                grouped[item["category"]].append({**item, "source": self.capex_first + index})
            first, last = links["first"], links["last"]
            row = 3
            dep_rows: list[int] = []
            excess_parts: list[str] = []
            name_font = Font(name=FONT_NAME, size=10, bold=True, color="0070C0")
            last_letter = get_column_letter(2 + self.years)
            for _key, title in _CAPEX_GROUPS:
                items = grouped[title]
                if not items:
                    continue
                self._put(row, 1, title, font=SECTION)
                row += 1
                for item in items:
                    register_row = item["source"]
                    link = f"'{CAPEX}'"
                    self._put(row, 1, item["name"], border=BOX, fill=PURPLE, wrap=True, font=name_font)
                    self._put(row, 2, None, border=BOX, fill=PURPLE)
                    for offset in range(self.years):
                        year_source = get_column_letter(YEAR_COLUMN + offset)
                        self._put(row, 3 + offset, f"='{SHEET}'!{year_source}6", border=BOX, fmt="0", align="center", fill=PURPLE, font=Font(name=FONT_NAME, size=10, bold=True))
                    for column in (pay_col, comm_col, start_col):
                        self._put(row, column, None, border=BOX, fill=PURPLE)
                    header = row
                    row += 1
                    life_row = row
                    self._put(row, 1, "Срок, лет", border=BOX)
                    self._put(row, 2, f"={link}!E{register_row}", border=BOX, fmt="0", align="center")
                    row += 1
                    year_row = row
                    dep_start = f"${get_column_letter(start_col)}${year_row}"
                    self._put(row, 1, "Год ввода", border=BOX)
                    self._put(row, 2, f"={link}!D{register_row}", border=BOX, fmt="0", align="center")
                    self._put(row, pay_col, f"={link}!I{register_row}", border=BOX, fmt="DD.MM.YYYY", align="center")
                    self._put(row, comm_col, f"={link}!J{register_row}", border=BOX, fmt="DD.MM.YYYY", align="center")
                    self._put(row, start_col, f"={link}!K{register_row}", border=BOX, fmt="DD.MM.YYYY", align="center")
                    row += 1
                    self._put(row, 1, "Стоимость в анкете", border=BOX)
                    self._put(row, 2, f"={link}!C{register_row}", border=BOX, fmt=MONEY, align="right")
                    row += 1
                    rate_row = row
                    rate_cell = f"$B${rate_row}"
                    self._put(row, 1, "Ставка амортизации", border=BOX)
                    self._put(row, 2, f"=IF(B{life_row}=0,0,1/B{life_row})", border=BOX, fmt=PCT_FINE, align="center")
                    row += 1
                    cost_row = row
                    cost_cell = f"$B${cost_row}"
                    self._put(row, 1, "Первоначальная стоимость", border=BOX)
                    self._put(row, 2, f"={link}!L{register_row}", border=BOX, fmt=MONEY, align="right")
                    row += 1
                    open_row = row
                    dep_row = row + 1
                    accum_row = row + 2
                    close_row = row + 3
                    labels = (
                        (open_row, "Балансовая стоимость на начало периода", None),
                        (dep_row, "Сумма износа в периоде", PINK),
                        (accum_row, "Накопленная амортизация", None),
                        (close_row, "Балансовая стоимость на конец периода", None),
                    )
                    for current, label, fill in labels:
                        self._put(current, 1, label, border=BOX, wrap=True, fill=fill)
                        self._put(current, 2, None, border=BOX, fill=fill)
                        if current in (open_row, close_row):
                            ws.row_dimensions[current].height = 26
                    for offset in range(self.years):
                        letter = get_column_letter(3 + offset)
                        year_header = f"{letter}{header}"
                        previous = get_column_letter(2 + offset)
                        born = f"YEAR({dep_start})"
                        if offset == 0:
                            opening = f"=IF({year_header}<{born},0,IF({year_header}={born},{cost_cell},0))"
                        else:
                            opening = f"=IF({year_header}<{born},0,IF({year_header}={born},{cost_cell},{previous}{close_row}))"
                        months = _service_months(year_header, first, last, dep_start)
                        charge = f"MIN({letter}{open_row},{cost_cell}*{rate_cell}*({months})/12)"
                        depreciation = f"=IF(OR({rate_cell}=0,{letter}{open_row}=0),0,{charge})"
                        accumulated = f"={letter}{dep_row}" if offset == 0 else f"={previous}{accum_row}+{letter}{dep_row}"
                        self._put(open_row, 3 + offset, opening, border=BOX, fmt=MONEY, align="right")
                        self._put(dep_row, 3 + offset, depreciation, border=BOX, fmt=MONEY, align="right", fill=PINK)
                        self._put(accum_row, 3 + offset, accumulated, border=BOX, fmt=MONEY, align="right")
                        self._put(close_row, 3 + offset, f"={letter}{open_row}-{letter}{dep_row}", border=BOX, fmt=MONEY, align="right")
                    dep_rows.append(dep_row)
                    excess_parts.append(f"MAX(0,{last_letter}{accum_row}-{cost_cell})")
                    row = close_row + 2

            if not dep_rows:
                self._put(row, 1, "Активы в анкете не указаны", font=SECTION)
                row += 2
            self._put(row, 1, "Первоначальная стоимость, всего", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, 2, f"='{CAPEX}'!L{self.capex_total_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self.amort_cost_row = row
            row += 1
            self._put(row, 1, "ИТОГО сумма износа", border=BOX, fill=GREEN, wrap=True, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(row, 2, None, border=BOX, fill=GREEN)
            for offset in range(self.years):
                letter = get_column_letter(3 + offset)
                total = "=" + "+".join(f"{letter}{item}" for item in dep_rows) if dep_rows else "=0"
                self._put(row, 3 + offset, total, border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self.amort_total_row = row
            row += 1
            self._put(row, 1, "Износ сверх стоимости", border=BOX)
            self._put(row, 2, "=" + "+".join(excess_parts) if excess_parts else 0, border=BOX, fmt=MONEY, align="right")
            self.amort_excess_row = row
            if self.capex_excess_row:
                saved = self.ws
                self.ws = self.book[CAPEX]
                self._put(self.capex_excess_row, 3, f"='{AMORT}'!B{row}", border=BOX, fmt=MONEY, align="right")
                self.ws = saved
        finally:
            self.ws = previous

    def _write_payroll_totals(self, row: int, roster: list[dict], links: dict, sales: str, first: int, last: int, flag_col: int, so_col: int, osms_col: int, opv_col: int):
        """Итоги АУП и производства и помесячные отчисления. Оклад в годовой ячейке не заменяется."""
        self.pay_month_first = None
        self.pay_month_last = None
        month_count = max(last - first + 1, 0)
        start_year = _year(self.form.get("planning_from"))
        count_last = get_column_letter(4 + (self.years - 1) * 2)
        year_from = get_column_letter(YEAR_COLUMN)
        year_to = get_column_letter(YEAR_COLUMN + self.years - 1)
        index_from = get_column_letter(4)
        index_to = get_column_letter(3 + self.years)
        # monthly_first считается после годовых итогов — якорь для SUMIFS в годовых формулах премии.
        # Пока якоря нет, бонус в годовом ФОТ пишется позже второй проходкой не нужен: span задаётся после.
        monthly_anchor_row = row

        def group_rows(key: str) -> list[int]:
            return [person["row"] for person in roster if person["group"] == key]

        def paint(title: str, values: list[str], fill, bold: bool = False) -> int:
            nonlocal row
            current = row
            font = Font(name=FONT_NAME, size=10, bold=True) if bold or fill is GREEN else TEXT
            self._put(current, 1, title, border=BOX, fill=fill, wrap=True, font=font)
            self._put(current, 2, None, border=BOX, fill=fill)
            self._put(current, 3, None, border=BOX, fill=fill)
            for offset, value in enumerate(values):
                fot_col = 5 + offset * 2
                self._put(current, fot_col - 1, None, border=BOX, fill=fill)
                self._put(current, fot_col, value, border=BOX, fmt=MONEY, align="right", fill=fill, font=font)
            row += 1
            return current

        def money_sum(rows_list: list[int], offset: int) -> str:
            letter = get_column_letter(5 + offset * 2)
            return "=" + "+".join(f"{letter}{item}" for item in rows_list) if rows_list else "=0"

        # Работодательские начисления: СО, ОПВР, ООСМС, СН. Удержания ОПВ/ВОСМС — отдельные колонки в ДДС.
        employer_charges = (
            ("Социальные отчисления", 11),
            ("ОПВР", 12),
            ("ООСМС", 13),
            ("Социальный налог", 16),
        )

        self._put(row, 1, "Две строки ниже складываются в общий ФОТ один раз: производство — в себестоимость, АУП — в административные расходы. Оклад — начисление до удержаний работника.", wrap=True, font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"))
        self.ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=3)
        self.ws.row_dimensions[row].height = 28
        row += 1
        # Placeholder span for year FOT bonus until monthly block is laid out.
        # Используем заранее вычисленный диапазон: после годовых строк (≈20) + MZP + заголовок.
        # Точные monthly_first/last пересчитаем и проставим бонус через тот же диапазон формулой SUMIFS — строки месячного блока пишутся сразу после MZP.
        # Сначала резервируем место под итоги, потом считаем monthly_first.
        note_charges_row = row
        # provisional: will set monthly after we know row after employer totals + mzp
        admin_fot = paint("ФОТ АУП", ["=0"] * self.years, GREEN, True)
        prod_fot = paint("ФОТ производственного персонала", ["=0"] * self.years, GREEN, True)
        total_fot = paint("ФОТ", [f"={get_column_letter(5 + offset * 2)}{admin_fot}+{get_column_letter(5 + offset * 2)}{prod_fot}" for offset in range(self.years)], GREEN, True)
        self.fot_admin_row = admin_fot
        self.fot_prod_row = prod_fot
        self.fot_total_row = total_fot

        self._put(
            row,
            1,
            "СО — 5% с 2025 (раньше 3,5%) от начисления минус ОПВ 10%, база 1–7 МЗП. ОПВР — лестница до 5% к 2028, база 1–50 МЗП. ООСМС — 3% с 2023, потолок 40 МЗП с 2026. ВОСМС работника — 2%, база до 20 МЗП (nalogikz 2026). Соцналог юрлица на ОУР: с 2026 — 6% от начисления без вычета СО; до 2026 — max(0; ставка×(начисление−ОПВ−ВОСМС)−СО). ИП на ОУР платит СН в МРП — в этой модели не развёрнут. На упрощёнке СН = 0. Удержания ОПВ и ВОСМС уменьшают «к выплате» в ДДС и перечисляются в «Налоги ФОТ».",
            wrap=True,
            font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
        )
        self.ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=8)
        self.ws.row_dimensions[row].height = 64
        row += 1

        # Временные нули: charge() требует monthly span — заполним после раскладки месяцев.
        split_rows: dict[str, dict[str, int]] = {"АУП": {}, "ПП": {}}
        charge_paint_rows: list[tuple[str, int, int, int]] = []
        for title, column in employer_charges:
            aup = paint(f"{title}, АУП", ["=0"] * self.years, None)
            pp = paint(f"{title}, производственный персонал", ["=0"] * self.years, None)
            paint(title, [f"={get_column_letter(5 + offset * 2)}{aup}+{get_column_letter(5 + offset * 2)}{pp}" for offset in range(self.years)], GREEN, True)
            split_rows["АУП"][title] = aup
            split_rows["ПП"][title] = pp
            charge_paint_rows.append((title, column, aup, pp))

        def employer(fot_row: int, key: str, offset: int) -> str:
            letter = get_column_letter(5 + offset * 2)
            parts = [f"{letter}{fot_row}"] + [f"{letter}{split_rows[key][title]}" for title, _column in employer_charges]
            return "=" + "+".join(parts)

        admin_employer = paint("АУП с отчислениями", [employer(admin_fot, "АУП", offset) for offset in range(self.years)], GREEN, True)
        prod_employer = paint("Производственный персонал с отчислениями", [employer(prod_fot, "ПП", offset) for offset in range(self.years)], GREEN, True)
        employer_row = paint("ФОТ с отчислениями работодателя", [f"={get_column_letter(5 + offset * 2)}{admin_employer}+{get_column_letter(5 + offset * 2)}{prod_employer}" for offset in range(self.years)], GREEN, True)
        self.fot_admin_employer_row = admin_employer
        self.fot_prod_employer_row = prod_employer
        self.fot_employer_row = employer_row

        row += 1
        self._put(row, 1, "МЗП по годам из справочника админки (Ставки FEM). Для года без своей суммы берётся последняя известная. Синюю ячейку в файле можно заменить.", wrap=True, font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"))
        self.ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=4)
        row += 1
        self._put(row, 1, "Год", border=BOX, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(row, 2, "МЗП", border=BOX, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
        row += 1
        mzp_first = row
        for offset in range(self.years):
            wage_row = mzp_first + offset
            self._put(wage_row, 1, f"='{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6", border=BOX, fmt="0", align="center")
            self._put(wage_row, 2, _minimum_wage(start_year + offset), border=BOX, fmt=MONEY, align="right", fill=BLUE)
        mzp_last = mzp_first + self.years - 1
        row = mzp_last + 1

        if not roster or not month_count:
            # Без сотрудников годовой ФОТ остаётся нулевым.
            return

        row += 1
        self._put(row, 1, "Помесячно: начисление (до удержаний), премия, отчисления работодателя и удержания работника. ДДС платит сотруднику «начислено − ОПВ − ВОСМС».", font=SECTION)
        row += 1
        monthly_first = row + 1
        monthly_last = monthly_first + len(roster) * month_count - 1
        span = f"$J${monthly_first}:$J${monthly_last}"
        who = f"$D${monthly_first}:$D${monthly_last}"
        years = f"$B${monthly_first}:$B${monthly_last}"

        def charge(column: int, offset: int, group: str | None) -> str:
            letter = get_column_letter(column)
            year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
            formula = f'SUMIFS(${letter}${monthly_first}:${letter}${monthly_last},{years},{year_cell}'
            if group:
                formula += f',$E${monthly_first}:$E${monthly_last},"{group}"'
            return "=" + formula + ")"

        # Проставляем годовой ФОТ и начисления задним числом (ячейки уже созданы).
        for person in roster:
            staff = person["row"]
            for offset in range(self.years):
                count_col = 4 + offset * 2
                year_cell = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}6"
                months = _paid_months(year_cell, first, last, sales, person["launch"])
                bonus = f"SUMIFS({span},{who},{staff},{years},{year_cell})"
                self._put(
                    staff,
                    count_col + 1,
                    f"=$C{staff}*{get_column_letter(count_col)}{staff}*({months})*{_wage_index(offset)}+{bonus}",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
        for offset in range(self.years):
            fot_col = 5 + offset * 2
            aup_rows = group_rows("АУП")
            pp_rows = group_rows("ПП")
            self._put(admin_fot, fot_col, money_sum(aup_rows, offset), border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(prod_fot, fot_col, money_sum(pp_rows, offset), border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            for _title, column, aup, pp in charge_paint_rows:
                self._put(aup, fot_col, charge(column, offset, "АУП"), border=BOX, fmt=MONEY, align="right")
                self._put(pp, fot_col, charge(column, offset, "ПП"), border=BOX, fmt=MONEY, align="right")

        headers = (
            "Дата", "Год", "Месяц", "Строка", "Группа", "В штате", "Численность",
            "Начислено", "На человека", "Премия", "СО", "ОПВР", "ООСМС", "ВОСМС", "ОПВ", "СН",
        )
        for column, title in enumerate(headers, start=1):
            self._put(row, column, title, border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
        cursor = monthly_first
        legal_too = f"'{SHEET}'!{self._source_cells()['legal_too']}"
        tax_general = f"'{SHEET}'!{self._source_cells()['tax_general']}"
        for person in roster:
            staff = person["row"]
            sign = "<" if person["launch"] else ">="
            for source in range(first, last + 1):
                income = _month_income_ref(person["services"], links, source)
                index = f"INDEX('{SHEET}'!${index_from}$50:${index_to}$50,MATCH(B{cursor},'{SHEET}'!${year_from}$6:${year_to}$6,0))/'{SHEET}'!$C$50"
                wage = f"SUMIF($A${mzp_first}:$A${mzp_last},B{cursor},$B${mzp_first}:$B${mzp_last})"
                worked = f"AND('{MONTHS}'!D{source}=1,'{MONTHS}'!A{source}{sign}DATE(YEAR({sales}),MONTH({sales}),1))"
                self._put(cursor, 1, f"='{MONTHS}'!A{source}", border=BOX, fmt="DD.MM.YYYY", align="center")
                self._put(cursor, 2, f"=YEAR(A{cursor})", border=BOX, fmt="0", align="center")
                self._put(cursor, 3, f"=MONTH(A{cursor})", border=BOX, fmt="0", align="center")
                self._put(cursor, 4, staff, border=BOX, fmt="0", align="center")
                self._put(cursor, 5, person["group"], border=BOX, align="center")
                self._put(cursor, 6, f"=IF({worked},1,0)", border=BOX, fmt="0", align="center")
                self._put(cursor, 7, f"=SUMIF($D$2:${count_last}$2,B{cursor},D{staff}:{count_last}{staff})", border=BOX, fmt="0.00", align="center")
                self._put(cursor, 10, f"=IF(F{cursor}=0,0,$B${staff}/100*({income}))", border=BOX, fmt=MONEY, align="right")
                self._put(cursor, 8, f"=IF(F{cursor}=0,0,$C${staff}*G{cursor}*({index})+J{cursor})", border=BOX, fmt=MONEY, align="right")
                self._put(cursor, 9, f"=IF(OR(F{cursor}=0,G{cursor}=0),0,$C${staff}*({index})+IF(G{cursor}=0,0,$B${staff}/100*({income})/G{cursor}))", border=BOX, fmt=MONEY, align="right")
                so_rate = f"IF(B{cursor}>=2025,0.05,0.035)"
                opvr_rate = f"IF(B{cursor}<=2023,0,IF(B{cursor}=2024,0.015,IF(B{cursor}=2025,0.025,IF(B{cursor}=2026,0.035,IF(B{cursor}=2027,0.045,0.05)))))"
                osms_rate = f"IF(B{cursor}>=2023,0.03,0.02)"
                osms_cap = f"IF(B{cursor}>=2026,40,10)"
                opvr_flag = f"{get_column_letter(flag_col)}{staff}"
                so_flag = f"{get_column_letter(so_col)}{staff}"
                osms_flag = f"{get_column_letter(osms_col)}{staff}"
                opv_flag = f"{get_column_letter(opv_col)}{staff}"
                employee_opv = f"IF({opv_flag}=0,0,0.1*MIN(I{cursor},50*{wage}))"
                vosms_cell = f"'{SHEET}'!{self._source_cells()['vosms_rate']}"
                sn_cell = f"'{SHEET}'!{self._source_cells()['social_tax_rate']}"
                # ВОСМС из анкеты, потолок 20 МЗП (НК 2026).
                employee_vosms = f"IF(F{cursor}=0,0,G{cursor}*({vosms_cell})*MIN(I{cursor},20*{wage}))"
                so_base = f"MIN(MAX(I{cursor}-({employee_opv}),{wage}),7*{wage})"
                self._put(cursor, 11, f"=IF(OR(F{cursor}=0,{so_flag}=0),0,G{cursor}*({so_rate})*({so_base}))", border=BOX, fmt=MONEY, align="right")
                self._put(cursor, 12, f"=IF(OR(F{cursor}=0,{opvr_flag}=0),0,G{cursor}*({opvr_rate})*MIN(MAX(I{cursor},{wage}),50*{wage}))", border=BOX, fmt=MONEY, align="right")
                self._put(cursor, 13, f"=IF(OR(F{cursor}=0,{osms_flag}=0),0,G{cursor}*({osms_rate})*MIN(MAX(I{cursor},{wage}),({osms_cap})*{wage}))", border=BOX, fmt=MONEY, align="right")
                self._put(cursor, 14, f"={employee_vosms}", border=BOX, fmt=MONEY, align="right")
                self._put(cursor, 15, f"=IF(F{cursor}=0,0,G{cursor}*({employee_opv}))", border=BOX, fmt=MONEY, align="right")
                # СН: ТОО на ОУР. С 2026 — ставка из анкеты; раньше — legacy max(0;ставка−СО).
                sn_old = f"MAX(0,IF(B{cursor}>=2025,0.11,0.095)*MAX(0,H{cursor}-O{cursor}-N{cursor})-K{cursor})"
                self._put(
                    cursor,
                    16,
                    f"=IF(OR(F{cursor}=0,{legal_too}=0,{tax_general}=0),0,IF(B{cursor}>=2026,({sn_cell})*H{cursor},{sn_old}))",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
                cursor += 1
        if cursor > monthly_first:
            self.pay_month_first = monthly_first
            self.pay_month_last = cursor - 1
        _ = (monthly_anchor_row, note_charges_row)  # reserved for readability / future layout asserts

    def _payment_sheet(self):
        """Лист «График платежей» как в Радуге: выдача, затем тело, проценты и остаток."""
        ws = self.book.create_sheet(SCHED)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "2E75B6"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            widths = {
                "A": 14,
                "B": 16,
                "C": 18,
                "D": 18,
                "E": 16,
                "F": 18,
                "G": 16,
                "H": 16,
                "I": 3,
                "J": 36,
                "K": 10,
                "L": 18,
                "M": 22,
            }
            for letter, width in widths.items():
                ws.column_dimensions[letter].width = width
            self._put(
                1,
                1,
                "Логика расчёта: выдача в месяц начала плана, первый платёж через месяц. Льгота входит в общий срок: в эти месяцы только проценты, тело гасится в оставшиеся месяцы. Аннуитет 1 — равный взнос после льготы, 0 — тело равными долями. Последний платёж закрывает остаток. Сумма — заёмные средства листа «Структура финансирования». При субсидии справа — блок «Условия транша» и «Льготный период» как в кредитном договоре: ставка банка, субсидия, ставка клиента, даты, дни и начисленные проценты за льготу. После срока субсидии ставка клиента равна ставке банка. Добавка индексации CAPEX сюда не входит. Внизу проверка: число строк равно сроку, тело равно сумме кредита, остаток ноль, взносы равны телу и процентам, сальдо не уходит в минус.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells("A1:M1")
            ws.row_dimensions[1].height = 56
            self.loan_blocks = []
            self.sched_interest_first = None
            self.sched_interest_last = None
            row = 3
            for title, principal, rate, term, grace, annuity, subsidized in self._loan_tranches():
                row = self._write_loan(row, title, principal, rate, term, grace, annuity, subsidized=subsidized) + 2
            self.schedule_end = row
        finally:
            self.ws = previous

    def _loan_tranches(self) -> list[tuple]:
        debt = f"'{FIN}'!D{self.fin_total_row}"
        if self.fin_first:
            operating = (
                f"SUMIF('{FIN}'!I{self.fin_first}:I{self.fin_last},\"Оборотный капитал\",'{FIN}'!D{self.fin_first}:D{self.fin_last})"
                f"+SUMIF('{FIN}'!I{self.fin_first}:I{self.fin_last},\"Операционные расходы\",'{FIN}'!D{self.fin_first}:D{self.fin_last})"
            )
        else:
            operating = "0"
        financing = int(self.form.get("financing") or 1)
        allow_wc = _gov_blocks_wc(self.form)
        wc_term = max(0, min(36, int(_num(self.form.get("wc_term"))))) if allow_wc else 0
        loan_rate, loan_term, loan_grace = _loan_terms(self.form)
        wc_grace = max(0, min(36, int(_num(self.form.get("wc_grace"))))) if allow_wc else 0
        wc_rate = _rate(self.form.get("wc_rate")) if allow_wc else 0.0
        program = _gov(self.form)
        if allow_wc and program and program.get("term_kind") == "subsidy" and program.get("rate") is not None and wc_term:
            wc_rate = _rate(program.get("rate"))
        loan_link = f"='{SHEET}'!{self._source_cells()['loan']}"
        bank_rate = _rate(self.form.get("loan_bank_rate"))
        title = "Займ на инвестиционные цели"
        if program:
            name = str(program.get("title") or "").strip()
            if name:
                title = f"Займ на инвестиционные цели ({name})"
        annuity = 1 if self.form.get("loan_annuity") else 0
        if financing in (2, 4) and wc_term > 0:
            return [
                (title, f"{debt}-({operating})", loan_rate, loan_term, loan_grace, annuity, bank_rate > 0),
                ("Займ на пополнение оборотных средств", operating, wc_rate, wc_term, wc_grace, 0, False),
            ]
        single = title if program else "Займ"
        # При ставке банка условия и график берут числа из анкеты, а не ссылку на смешанный WACC.
        rate_value = loan_rate if bank_rate > 0 else loan_link
        return [(single, debt, rate_value, loan_term, loan_grace, annuity, bank_rate > 0)]

    def _write_loan(
        self,
        row: int,
        title: str,
        principal: str,
        rate: float | str,
        term: int,
        grace: int,
        annuity: int,
        subsidized: bool = False,
    ) -> int:
        """Один транш: условия справа, помесячные строки слева. Возвращает строку итога."""
        issue = f"'{SHEET}'!{self._source_cells()['plan_start']}"
        self._put(row, 1, title, font=SECTION)
        bank_rate = _rate(self.form.get("loan_bank_rate"))
        subsidy_rate = _rate(self.form.get("loan_subsidy_rate"))
        subsidy_term = max(0, int(_num(self.form.get("loan_subsidy_term"))))
        dual = bool(subsidized and bank_rate > 0)
        client_rate = _rate(self.form.get("loan_rate")) if dual else (rate if isinstance(rate, str) else _rate(rate))
        width = 8 if dual else 6
        label_col = 10
        unit_col = 11
        value_col = 12 if dual else 11
        value_letter = "L" if dual else "K"
        note_col = 13
        self.ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=width)
        if dual and subsidy_rate <= 0 and bank_rate > client_rate > 0:
            subsidy_rate = round(bank_rate - client_rate, 6)

        def cond(r: int, label: str, unit: str, value, fmt=None, editable: bool = False, note: str | None = None):
            self._put(r, label_col, label, border=GREY_BOX, wrap=True)
            self._put(r, unit_col, unit or None, border=GREY_BOX, align="center")
            self._put(
                r,
                value_col,
                value,
                border=GREY_BOX,
                fmt=fmt,
                align="center",
                fill=BLUE if editable else None,
            )
            if note:
                self._put(r, note_col, note, font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"))

        def section(r: int, text: str):
            self._put(r, label_col, text, border=GREY_BOX, fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(r, unit_col, None, border=GREY_BOX, fill=GRAY)
            self._put(r, value_col, None, border=GREY_BOX, fill=GRAY)
            self.ws.merge_cells(start_row=r, start_column=label_col, end_row=r, end_column=value_col)

        overpay_row = None
        if dual:
            # Справа — как у Радуги: «Условия транша» и «Льготный период» рядом с графиком слева.
            panel = row + 1
            section(panel, "Условия транша")
            sum_row = panel + 1
            bank_row = panel + 2
            subsidy_row = panel + 3
            client_row = panel + 4
            subsidy_term_row = panel + 5
            term_row = panel + 6
            nper_row = panel + 7
            issue_row = panel + 8
            end_row = panel + 9
            overpay_row = panel + 13
            grace_hdr = panel + 15
            grace_row = panel + 16
            grace_start_row = panel + 17
            grace_end_row = panel + 18
            grace_rate_row = panel + 19
            grace_days_row = panel + 20
            grace_interest_row = panel + 21
            ann_row = panel + 22
            pmt_row = panel + 23
            rate_row = client_row
            nbk_note = "базовая НБ РК + 4%" if self.form.get("loan_nbk_plus_4") else "ставка банка"
            od_text = "Аннуитет (банк)" if annuity else "Равными долями"
            cond(sum_row, "Сумма", "тенге", f"={principal}", MONEY, False)
            cond(bank_row, "Ставка банка (номинал)", "%", bank_rate, PCT_FINE, True, nbk_note)
            # Не «ставка клиента», а доля субсидии в п.п.: банк − клиент (как в анкете «Субсидия Damu»).
            cond(subsidy_row, "Субсидия Damu (банк − клиент), п.п.", "%", subsidy_rate, PCT_FINE, True)
            cond(client_row, "Ставка для клиента (платит проект)", "%", client_rate, PCT_FINE, True)
            cond(subsidy_term_row, "Срок субсидирования", "мес.", subsidy_term, "0", True)
            cond(term_row, "Срок кредитования", "мес.", term, "0", True)
            cond(nper_row, "Кол-во платежей", "мес.", f"=MAX(0,${value_letter}${term_row}-MIN(${value_letter}${grace_row},${value_letter}${term_row}))", "0", False)
            cond(issue_row, "Выдача", "дата", f"={issue}", "DD.MM.YYYY", False)
            cond(end_row, "Конец", "дата", f"=EDATE(${value_letter}${issue_row},${value_letter}${term_row})", "DD.MM.YYYY", False)
            cond(panel + 10, "Вид КЛ", "", "Невозобновляемая КЛ")
            cond(panel + 11, "Погашение ОД", "", od_text)
            cond(panel + 12, "Погашение %", "", "Ежемесячными платежами")
            cond(overpay_row, "Сумма переплаты", "тенге", 0, MONEY, False)
            section(grace_hdr, "Льготный период")
            cond(grace_row, "Льготный период по ОД, кол-во мес", "мес.", grace, "0", True)
            cond(grace_start_row, "Начало", "дата", f"=${value_letter}${issue_row}", "DD.MM.YYYY", False)
            cond(grace_end_row, "Конец", "дата", f"=EDATE(${value_letter}${grace_start_row},MIN(${value_letter}${grace_row},${value_letter}${term_row}))", "DD.MM.YYYY", False)
            cond(grace_rate_row, "Ставка", "%", f"=${value_letter}${bank_row}", PCT_FINE, False)
            cond(grace_days_row, "Кол-во дней", "день", f"=DAYS360(${value_letter}${grace_start_row},${value_letter}${grace_end_row},TRUE)", "0", False)
            cond(
                grace_interest_row,
                "Начислено % во время льготного периода",
                "тенге",
                f"=ROUND(${value_letter}${sum_row}*${value_letter}${bank_row}/360*${value_letter}${grace_days_row},2)",
                MONEY,
                False,
            )
            cond(ann_row, "Аннуитет", "0/1", annuity, "0", True)
            # График слева начинается сразу под заголовком транша — параллельно правому блоку.
            header = panel
            headers = ("№", "Дата", "Платеж клиента", "Основной долг", "% клиента", "Сальдо", "% банка", "Субсидия Damu")
        else:
            bank_row = subsidy_row = subsidy_term_row = None
            conditions = (
                ("Сумма", f"={principal}", MONEY, False),
                ("Ставка", rate, PCT_FINE, not isinstance(rate, str)),
                ("Срок, мес.", term, "0", True),
                ("Льгота, мес.", grace, "0", True),
                ("Аннуитет", annuity, "0", True),
                ("Выдача", f"={issue}", "DD.MM.YYYY", False),
            )
            for offset, (label, value, fmt, editable) in enumerate(conditions):
                self._put(row + offset, label_col, label, border=GREY_BOX, wrap=True)
                self._put(row + offset, value_col, value, border=GREY_BOX, fmt=fmt, align="center", fill=BLUE if editable else None)
            sum_row = row
            rate_row = row + 1
            term_row = row + 2
            grace_row = row + 3
            ann_row = row + 4
            issue_row = row + 5
            pmt_row = row + 6
            client_row = rate_row
            nper_row = None
            header = pmt_row + 2
            headers = ("№", "Дата", "Взнос", "Основной долг", "Проценты", "Сальдо")

        amount = f"${value_letter}${sum_row}"
        rate_cell = f"${value_letter}${rate_row}"
        client_cell = f"${value_letter}${client_row}"
        bank_cell = f"${value_letter}${bank_row}" if bank_row else rate_cell
        subsidy_cell = f"${value_letter}${subsidy_row}" if subsidy_row else "0"
        subsidy_term_cell = f"${value_letter}${subsidy_term_row}" if subsidy_term_row else "0"
        term_cell = f"${value_letter}${term_row}"
        grace_cell = f"MIN(${value_letter}${grace_row},{term_cell})"
        ann_cell = f"${value_letter}${ann_row}"
        nper = f"MAX(0,{term_cell}-{grace_cell})"
        pmt_rate = bank_cell if dual else rate_cell
        self._put(pmt_row, label_col, "Взнос аннуитета банка" if dual else "Взнос аннуитета", border=GREY_BOX, wrap=True)
        if dual:
            self._put(pmt_row, unit_col, "тенге", border=GREY_BOX, align="center")
        self._put(
            pmt_row,
            value_col,
            f'=IF(OR({ann_cell}=0,{amount}<=0,{nper}<=0),0,IF({pmt_rate}=0,{amount}/{nper},{amount}*({pmt_rate}/12)*(1+{pmt_rate}/12)^{nper}/((1+{pmt_rate}/12)^{nper}-1)))',
            border=GREY_BOX,
            fmt=MONEY,
            align="center",
        )
        for column, text in enumerate(headers, start=1):
            self._put(header, column, text, border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
        opening = header + 1
        self._put(opening, 1, "Выдача", border=BOX, align="center")
        self._put(opening, 2, f"=${value_letter}${issue_row}", border=BOX, fmt="DD.MM.YYYY", align="center")
        for column in range(3, width):
            self._put(opening, column, None, border=BOX)
        self._put(opening, 6, f"={amount}", border=BOX, fmt=MONEY, align="right", fill=PURPLE)
        if dual:
            self._put(opening, 7, None, border=BOX)
            self._put(opening, 8, None, border=BOX)
        first = opening + 1
        if term <= 0:
            self._put(first, 1, "Платежей нет: срок не задан или заёмных средств нет.", font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"))
            self.ws.merge_cells(start_row=first, start_column=1, end_row=first, end_column=width)
            self.loan_blocks.append({
                "name": title,
                "principal": f"'{SCHED}'!{value_letter}{sum_row}",
                "interest": "0",
                "closing": f"'{SCHED}'!F{opening}",
                "date_range": f"'{SCHED}'!$B${opening}:$B${opening}",
                "balance_range": f"'{SCHED}'!$F${opening}:$F${opening}",
            })
            return first
        for step in range(term):
            current = first + step
            previous = current - 1
            self._put(current, 1, 1 if step == 0 else f"=A{previous}+1", border=BOX, fmt="0", align="center")
            self._put(current, 2, f"=EDATE(B{previous},1)", border=BOX, fmt="DD.MM.YYYY", align="center")
            if dual:
                # % банка всегда; % клиента — ставка программы в срок субсидии, потом полная банковская (как в Радуге, но в ОПиУ/ДДС идёт колонка клиента).
                self._put(
                    current,
                    7,
                    f"=IF(OR({amount}<=0,A{current}>{term_cell}),0,F{previous}*{bank_cell}/12)",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
                self._put(
                    current,
                    5,
                    f"=IF(OR({amount}<=0,A{current}>{term_cell}),0,IF(A{current}<={subsidy_term_cell},F{previous}*{client_cell}/12,F{previous}*{bank_cell}/12))",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
                self._put(
                    current,
                    8,
                    f"=IF(OR({amount}<=0,A{current}>{term_cell},A{current}>{subsidy_term_cell}),0,F{previous}*{subsidy_cell}/12)",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
                self._put(
                    current,
                    4,
                    f"=IF(OR({amount}<=0,A{current}>{term_cell}),0,IF(A{current}<={grace_cell},IF(A{current}={term_cell},F{previous},0),IF(A{current}={term_cell},F{previous},IF({ann_cell}=1,MIN(F{previous},MAX(0,${value_letter}${pmt_row}-G{current})),MIN(F{previous},{amount}/MAX(1,{nper}))))))",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
            else:
                self._put(current, 5, f"=IF(OR({amount}<=0,A{current}>{term_cell}),0,F{previous}*{rate_cell}/12)", border=BOX, fmt=MONEY, align="right")
                self._put(
                    current,
                    4,
                    f"=IF(OR({amount}<=0,A{current}>{term_cell}),0,IF(A{current}<={grace_cell},IF(A{current}={term_cell},F{previous},0),IF(A{current}={term_cell},F{previous},IF({ann_cell}=1,MIN(F{previous},MAX(0,${value_letter}${pmt_row}-E{current})),MIN(F{previous},{amount}/MAX(1,{nper}))))))",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
            self._put(current, 3, f"=D{current}+E{current}", border=BOX, fmt=MONEY, align="right")
            self._put(current, 6, f"=F{previous}-D{current}", border=BOX, fmt=MONEY, align="right")
        last = first + term - 1
        total = last + 1
        self._put(total, 1, "Итого", border=BOX, fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(total, 2, None, border=BOX, fill=GREEN)
        self._put(total, 3, f"=SUM(C{first}:C{last})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(total, 4, f"=SUM(D{first}:D{last})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(total, 5, f"=SUM(E{first}:E{last})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
        self._put(total, 6, f"=F{last}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
        if dual:
            self._put(total, 7, f"=SUM(G{first}:G{last})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(total, 8, f"=SUM(H{first}:H{last})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=Font(name=FONT_NAME, size=10, bold=True))
            if overpay_row:
                # Как в Радуге: переплата = сумма % банка за весь срок.
                self._put(overpay_row, value_col, f"=G{total}", border=GREY_BOX, fmt=MONEY, align="center")
        checks = (
            ("Строки", f'=IF(COUNTA(A{first}:A{last})={term_cell},"по сроку","не по сроку")'),
            ("Тело", f'=IF(ABS(D{total}-F{opening})<1,"равно сумме кредита","не равно сумме кредита")'),
            ("Остаток", f'=IF(ABS(F{total})<1,"ноль","не ноль")'),
            ("Взносы", f'=IF(ABS(C{total}-(D{total}+E{total}))<1,"равны телу и процентам","не сходятся")'),
            ("Сальдо", f'=IF(MIN(F{first}:F{last})>=-1,"отрицательного нет","есть отрицательное")'),
        )
        for offset, (label, formula) in enumerate(checks, start=1):
            self._put(total + offset, 1, label, border=BOX)
            self._put(total + offset, 2, None, border=BOX)
            self._put(total + offset, 3, formula, border=BOX, align="left")
            for column in range(4, width + 1):
                self._put(total + offset, column, None, border=BOX)
            self.ws.merge_cells(start_row=total + offset, start_column=3, end_row=total + offset, end_column=width)
        if self.sched_interest_first is None:
            self.sched_interest_first = first
        self.sched_interest_last = last
        self.loan_blocks.append({
            "name": title,
            "principal": f"'{SCHED}'!{value_letter}{sum_row}",
            "interest": f"'{SCHED}'!E{total}",
            "closing": f"'{SCHED}'!F{total}",
            "date_range": f"'{SCHED}'!$B${opening}:$B${last}",
            "balance_range": f"'{SCHED}'!$F${opening}:$F${last}",
        })
        return total + len(checks)

    def _schedule_interest_range(self) -> tuple[str, str]:
        """Диапазоны даты и % клиента на графике — без жёсткого E$12 при сдвиге условий."""
        first = getattr(self, "sched_interest_first", None) or 12
        last = getattr(self, "sched_interest_last", None) or 600
        return f"'{SCHED}'!$B${first}:$B${last}", f"'{SCHED}'!$E${first}:$E${last}"

    def _schedule_principal_range(self) -> tuple[str, str]:
        """Дата и тело долга на графике — те же строки, что у процентов."""
        first = getattr(self, "sched_interest_first", None) or 12
        last = getattr(self, "sched_interest_last", None) or 600
        return f"'{SCHED}'!$B${first}:$B${last}", f"'{SCHED}'!$D${first}:$D${last}"


    def _pnl_sheet(self):
        """Лист «ОПиУ» как в Радуге: годовые строки ссылаются на уже посчитанные листы."""
        ws = self.book.create_sheet(PNL)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "641AEA"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            last_year = 1 + self.years
            ws.column_dimensions["A"].width = 46
            for column in range(2, last_year + 1):
                ws.column_dimensions[get_column_letter(column)].width = 16
            self._put(1, 1, "Отчет о доходах и расходах по проекту", font=SECTION)
            ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=last_year)
            self._put(
                2,
                1,
                "Выручка — итог плана доходов. Себестоимость товаров — лист «Себестоимость». ФОТ производства и налоги работодателя на него входят в валовую прибыль, ФОТ АУП остаётся ниже. Административные расходы — итог без запуска. Расходы на запуск — расход периода, в CAPEX не капитализируются. Проценты — колонка вознаграждения клиента на листе графика платежей; тело долга в прибыль не входит. Режим, ставка и корректировка базы — ссылки на «Предпосылки». Упрощёнка: ставка × выручка. Общий режим: ставка × прибыль после переноса убытка и корректировки, если база больше нуля. Ноль в корректировке оставляет базу равной прибыли.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=last_year)
            ws.row_dimensions[2].height = 78

            source_cells = self._source_cells()
            flag_column = last_year + 2
            rate_column = flag_column + 1
            rate_letter = get_column_letter(rate_column)
            general = f"${rate_letter}$3"
            tax_rate = f"${rate_letter}$4"
            tax_adj = f"${rate_letter}$5"
            side = (
                (3, "Общий режим", f"='{SHEET}'!{source_cells['tax_general']}", "0"),
                (4, "Ставка налога", f"='{SHEET}'!{source_cells['tax_rate']}", PCT_FINE),
                (5, "Корректировка базы", f"='{SHEET}'!{source_cells['tax_adj']}", PCT_FINE),
            )
            for row, label, formula, fmt in side:
                self._put(row, flag_column, label, border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
                self._put(row, rate_column, formula, border=GREY_BOX, fmt=fmt, align="center", fill=PURPLE)
            ws.column_dimensions[get_column_letter(flag_column)].width = 24
            ws.column_dimensions[rate_letter].width = 14

            self._put(4, 1, "Наименование", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            first_year = get_column_letter(YEAR_COLUMN)
            self._put(4, 2, f"='{SHEET}'!{first_year}6", border=BOX, fmt="0", align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            for offset in range(1, self.years):
                previous_letter = get_column_letter(1 + offset)
                self._put(4, 2 + offset, f"={previous_letter}4+1", border=BOX, fmt="0", align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))

            revenue_row = 6
            revenue_detail = 7
            cogs_row = 8
            prod_row = 9
            prod_tax_row = 10
            gross_row = 11
            admin_fot_row = 12
            admin_tax_row = 13
            admin_row = 14
            launch_row = 15
            amort_row = 16
            interest_row = 17
            ebt_row = 18
            loss_open_row = 19
            loss_used_row = 20
            loss_close_row = 21
            tax_row = 22
            net_row = 23
            margin_row = 24
            ebitda_row = 25
            avg_revenue_row = 27
            avg_net_row = 28
            links = getattr(self, "_revenue_links", None) or {}
            income_total = links.get("total_row")
            cogs_total = getattr(self, "cogs_total_row", None)
            prod_total = getattr(self, "fot_prod_row", None)
            prod_employer = getattr(self, "fot_prod_employer_row", None)
            admin_fot = getattr(self, "fot_admin_row", None)
            admin_employer = getattr(self, "fot_admin_employer_row", None)
            admin_total = getattr(self, "admin_total_row", None)
            amort_total = getattr(self, "amort_total_row", None)
            dates, interest = self._schedule_interest_range()
            plan_start = f"'{SHEET}'!{source_cells['plan_start']}"
            plan_end = f"'{SHEET}'!{source_cells['plan_end']}"

            def link(sheet: str, row: int | None, source: str) -> str:
                if not row:
                    return "=0"
                return f"='{sheet}'!{source}{row}"

            def payroll_tax(staff_row: int | None, employer_row: int | None, source: str) -> str:
                if not staff_row or not employer_row:
                    return "=0"
                return f"='{PAY}'!{source}{employer_row}-'{PAY}'!{source}{staff_row}"

            labels = (
                (revenue_row, "Выручка итого", GREEN),
                (revenue_detail, "Выручка", None),
                (cogs_row, "Себестоимость", None),
                (prod_row, "ФОТ производства", None),
                (prod_tax_row, "Налоги ФОТ производства", None),
                (gross_row, "Валовая прибыль", GREEN),
                (admin_fot_row, "ФОТ АУП", None),
                (admin_tax_row, "Налоги ФОТ АУП", None),
                (admin_row, "Административные расходы", None),
                (launch_row, "Расходы на запуск (см. ИТОГО адм.)", None),
                (amort_row, "Амортизация", None),
                (interest_row, "Выплаты по вознаграждениям", None),
                (ebt_row, "Прибыль до выплаты налога", GREEN),
                (loss_open_row, "Перенос убытка на начало", None),
                (loss_used_row, "Использовано убытка", None),
                (loss_close_row, "Перенос убытка на конец", None),
                (tax_row, "Налог по применимому режиму", None),
                (net_row, "Чистая прибыль", GREEN),
                (margin_row, "Рентабельность продаж", None),
                (ebitda_row, "EBITDA", GREEN),
                (avg_revenue_row, "ср/мес выручка", None),
                (avg_net_row, "ср/мес ЧП", None),
            )
            for row, label, fill in labels:
                self._put(row, 1, label, border=BOX, fill=fill, wrap=True, font=Font(name=FONT_NAME, size=10, bold=True) if fill else TEXT)
            bold = Font(name=FONT_NAME, size=10, bold=True)
            for offset in range(self.years):
                letter = get_column_letter(2 + offset)
                source = get_column_letter(3 + offset)
                fot_letter = get_column_letter(5 + offset * 2)
                year_cell = f"{letter}$4"
                months = f"'{SHEET}'!{get_column_letter(YEAR_COLUMN + offset)}11"
                self._put(revenue_detail, 2 + offset, link(REV, income_total, source), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(revenue_row, 2 + offset, f"={letter}{revenue_detail}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(cogs_row, 2 + offset, link(COST, cogs_total, source), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(prod_row, 2 + offset, link(PAY, prod_total, fot_letter), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(prod_tax_row, 2 + offset, payroll_tax(prod_total, prod_employer, fot_letter), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(gross_row, 2 + offset, f"={letter}{revenue_row}-{letter}{cogs_row}-{letter}{prod_row}-{letter}{prod_tax_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(admin_fot_row, 2 + offset, link(PAY, admin_fot, fot_letter), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(admin_tax_row, 2 + offset, payroll_tax(admin_fot, admin_employer, fot_letter), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(admin_row, 2 + offset, link(ADM, admin_total, source), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                # invest-строки уже в ИТОГО адм. (как в Радуге) — отдельной строкой не вычитаем повторно.
                self._put(launch_row, 2 + offset, 0, border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(amort_row, 2 + offset, link(AMORT, amort_total, source), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(
                    interest_row,
                    2 + offset,
                    f'=SUMIFS({interest},{dates},">="&MAX(DATE({year_cell},1,1),{plan_start}),{dates},"<="&MIN(DATE({year_cell},12,31),{plan_end}))',
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                    fill=PURPLE,
                )
                self._put(ebt_row, 2 + offset, f"={letter}{gross_row}-SUM({letter}{admin_fot_row}:{letter}{interest_row})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                if offset == 0:
                    opening = "=0"
                else:
                    opening = f"={get_column_letter(1 + offset)}{loss_close_row}"
                self._put(loss_open_row, 2 + offset, opening, border=BOX, fmt=MONEY, align="right")
                self._put(loss_used_row, 2 + offset, f"=IF({general}=1,MIN(MAX({letter}{ebt_row},0),{letter}{loss_open_row}),0)", border=BOX, fmt=MONEY, align="right")
                self._put(loss_close_row, 2 + offset, f"=IF({general}=1,{letter}{loss_open_row}-{letter}{loss_used_row}+MAX(-{letter}{ebt_row},0),0)", border=BOX, fmt=MONEY, align="right")
                self._put(
                    tax_row,
                    2 + offset,
                    f"=IF({general}=1,MAX(0,({letter}{ebt_row}-{letter}{loss_used_row})*(1+{tax_adj}))*{tax_rate},MAX(0,{letter}{revenue_row})*{tax_rate})",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                    fill=PURPLE,
                )
                self._put(net_row, 2 + offset, f"={letter}{ebt_row}-{letter}{tax_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(margin_row, 2 + offset, f'=IF({letter}{revenue_row}>0,{letter}{net_row}/{letter}{revenue_row},0)', border=BOX, fmt=PCT_FINE, align="center")
                self._put(ebitda_row, 2 + offset, f"={letter}{ebt_row}+{letter}{amort_row}+{letter}{interest_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(avg_revenue_row, 2 + offset, f"=IF({months}=0,0,{letter}{revenue_row}/{months})", border=BOX, fmt=MONEY, align="right")
                self._put(avg_net_row, 2 + offset, f"=IF({months}=0,0,{letter}{net_row}/{months})", border=BOX, fmt=MONEY, align="right")
            self.pnl = {
                "revenue": revenue_row,
                "cogs": cogs_row,
                "prod": prod_row,
                "prod_tax": prod_tax_row,
                "gross": gross_row,
                "admin_fot": admin_fot_row,
                "admin_tax": admin_tax_row,
                "admin": admin_row,
                "launch": launch_row,
                "interest": interest_row,
                "tax": tax_row,
                "net": net_row,
                "ebitda": ebitda_row,
            }
        finally:
            self.ws = previous


    def _cash_sheet(self):
        """Лист «ДДС» как в Радуге: операция, инвестиции, финансирование и остаток денег."""
        ws = self.book.create_sheet(CF)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "641AEA"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            last_year = 1 + self.years
            ws.column_dimensions["A"].width = 62
            for column in range(2, last_year + 1):
                ws.column_dimensions[get_column_letter(column)].width = 16
            self._put(2, 1, "Прогнозный отчет о движении денежных средств", font=SECTION)
            ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=last_year)
            self._put(
                1,
                1,
                "Годовые строки — сумма помесячного блока ниже. Поступления в ОПиУ без НДС; если плательщик НДС (ОУР), в ДДС к поступлениям, закупкам, адм. и CAPEX добавляется НДС 16%, а в «Налог» входит нетто НДС (исходящий − входящий) вместе с налогом режима. ФОТ в ДДС — к выплате сотруднику (начисление − ОПВ − ВОСМС); удержания и начисления работодателя (СО, ОПВР, ООСМС, СН) — в «Налоги ФОТ». Амортизация в деньги не входит. Реклама, зарплата и прочие из бюджета финансирования второй раз не списываются. Первоначальный запас покупается один раз. CAPEX — «К оплате» в дату реестра. Налог режима: ОУР в последнем месяце года, упрощёнка полугодиями. Финансирование — в месяц начала плана. Дивиденды — доля ЧП прошлого года в срок участия. Минимальный остаток — худший месяц года.",
                wrap=True,
                font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
            )
            ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=last_year)
            ws.row_dimensions[1].height = 96
            for offset in range(self.years):
                self._put(4, 2 + offset, f"='{PNL}'!{get_column_letter(2 + offset)}4", border=BOX, fmt="0", align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))
            self._put(4, 1, "Наименование", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=10, bold=True))

            pnl = self.pnl
            plan_start = f"'{SHEET}'!{self._source_cells()['plan_start']}"
            plan_end = f"'{SHEET}'!{self._source_cells()['plan_end']}"
            inflow_row = 6
            income_row = 7
            outflow_row = 8
            cogs_row = 9
            fot_row = 10
            fot_tax_row = 11
            admin_row = 12
            interest_row = 13
            tax_row = 14
            operating_row = 15
            capex_row = 17
            stock_row = 18
            preopen_row = 19
            investing_row = 20
            own_row = 22
            investor_row = 23
            grant_row = 24
            draw_row = 25
            principal_row = 26
            dividend_row = 27
            financing_row = 28
            net_row = 29
            open_row = 30
            close_row = 31
            gap_row = 32
            labels = (
                (5, "Движение денежных средств от операционной деятельности", SECTION, None),
                (inflow_row, "Поступление денежных средств", GREEN, True),
                (income_row, "Доход", None, False),
                (outflow_row, "Выбытие денежных средств, всего", GREEN, True),
                (cogs_row, "Оплата поставщикам", None, False),
                (fot_row, "ФОТ", None, False),
                (fot_tax_row, "Налоги ФОТ", None, False),
                (admin_row, "Административные расходы и запуск", None, False),
                (interest_row, "Выплата вознаграждений", None, False),
                (tax_row, "Налог по применимому режиму", None, False),
                (operating_row, "Итого движение денег по операционной деятельности", GREEN, True),
                (16, "Движение денежных средств от инвестиционной деятельности", SECTION, None),
                (capex_row, "Приобретение CAPEX", None, False),
                (stock_row, "Запас на конец года", None, False),
                (preopen_row, "Бюджет рекламы, зарплаты и прочих (не списывается)", None, False),
                (investing_row, "Итого движение денег по инвестиционной деятельности", GREEN, True),
                (21, "Движение денежных средств от финансовой деятельности", SECTION, None),
                (own_row, "Получение собственных средств", None, False),
                (investor_row, "Получение денег инвестора", None, False),
                (grant_row, "Получение гранта", None, False),
                (draw_row, "Получение кредита", None, False),
                (principal_row, "Выплата основного долга", None, False),
                (dividend_row, "Дивиденды инвестору", None, False),
                (financing_row, "Итого движение денег по финансовой деятельности", GREEN, True),
                (net_row, "ИТОГО поступление-выбытие денежных средств", GREEN, True),
                (open_row, "Денежные средства на начало периода", None, False),
                (close_row, "Денежные средства на конец периода", GREEN, True),
                (gap_row, "Минимальный остаток внутри года", PINK, True),
            )
            for row, label, fill, bold in labels:
                font = SECTION if fill is SECTION else (Font(name=FONT_NAME, size=10, bold=True) if bold else TEXT)
                self._put(row, 1, label, border=None if fill is SECTION else BOX, fill=None if fill is SECTION else fill, wrap=True, font=font)
                if fill is not SECTION:
                    ws.row_dimensions[row].height = 30 if row in (preopen_row, operating_row, investing_row, financing_row) else 18

            def source(column: str, year_cell: str) -> str:
                return f"=IF({year_cell}=YEAR({plan_start}),'{FIN}'!{column}{self.fin_total_row},0)"

            links = getattr(self, "_revenue_links", None) or {}
            sources = list(range(int(links.get("first") or 0), int(links.get("last") or -1) + 1))
            header = 35
            first_month = header + 1
            month_rows = [first_month + index for index in range(len(sources))]
            by_year: dict[int, list[int]] = {}
            for index, _month_source in enumerate(sources):
                by_year.setdefault(_iter_months(self.form)[index].year, []).append(first_month + index)
            last_letter = get_column_letter(1 + self.years)
            span_first = header + 1
            span_last = header + max(len(sources), 1)
            if sources:
                cash_hint = (
                    f'=IF(MAX(B33:{last_letter}33)<1,"Помесячный остаток. Кассового разрыва нет.",'
                    f'IFERROR("Деньги заканчиваются "&TEXT(AGGREGATE(15,6,(A{span_first}:A{span_last})/(S{span_first}:S{span_last}<0),1),"MM.YYYY")&". ","")'
                    f'&"К концу плана не хватает "&TEXT(MAX(B33:{last_letter}33),"# ##0")'
                    f'&". Варианты: увеличить взнос инвестора, добавить кредит, снизить постоянные расходы.")'
                )
            else:
                cash_hint = "Помесячный остаток."
            self._put(34, 1, cash_hint, wrap=True, font=SECTION)
            ws.row_dimensions[34].height = 36
            month_headers = (
                "Дата", "Год", "В периоде", "Поступления", "Себестоимость товаров", "Оплата поставщикам",
                "Переменные выплаты", "ФОТ к выплате", "Налоги ФОТ", "Адм. и запуск", "Проценты", "Налог+НДС",
                "CAPEX", "Тело долга", "Финансирование", "Запас на конец", "Движение",
                "Деньги на начало", "Деньги на конец", "Дивиденды",
            )
            for column, title in enumerate(month_headers, start=1):
                self._put(header, column, title, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            self._put(header, 22, "Не выплачено", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            self._put(header, 23, "Выкуп доли", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            self._put(header, 24, "Выкуп не выплачен", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            ws.row_dimensions[header].height = 32
            for column in range(1, 25):
                ws.column_dimensions[get_column_letter(column)].width = max(ws.column_dimensions[get_column_letter(column)].width or 0, 16)
            ws.column_dimensions["A"].width = 62
            stock_cell = "$U$34"
            if self.fin_first:
                stock_formula = (
                    f"=SUMIF('{FIN}'!$I${self.fin_first}:$I${self.fin_last},\"Оборотный капитал\","
                    f"'{FIN}'!$C${self.fin_first}:$C${self.fin_last})"
                )
            else:
                stock_formula = "=0"
            self._put(34, 20, "Первоначальный запас", border=GREY_BOX, font=Font(name=FONT_NAME, size=8))
            self._put(34, 21, stock_formula, border=GREY_BOX, fmt=MONEY, align="right", fill=PURPLE)
            year_from = get_column_letter(YEAR_COLUMN)
            year_to = get_column_letter(YEAR_COLUMN + self.years - 1)
            cost_last = get_column_letter(2 + self.years)
            sales = f"'{REV}'!$B$4"
            year_match = f"'{SHEET}'!${year_from}$6:${year_to}$6"

            index_from = get_column_letter(4)
            index_to = get_column_letter(3 + self.years)

            def cost_index(date: str, row_number: int) -> str:
                return (
                    f"INDEX('{SHEET}'!${index_from}${row_number}:${index_to}${row_number},"
                    f"MATCH(YEAR({date}),{year_match},0))/'{SHEET}'!$C${row_number}"
                )

            def goods(date: str, source: int) -> str:
                parts = []
                for item in getattr(self, "goods_cash", []):
                    qty = f"'{MONTHS}'!{get_column_letter(item['qty_col'])}{source}"
                    price = (
                        f"INDEX('{COST}'!$C${item['index_row']}:{cost_last}${item['index_row']},"
                        f"MATCH(YEAR({date}),{year_match},0))"
                    )
                    parts.append(f"({qty})*({price})")
                return "+".join(parts) if parts else "0"

            def variable(date: str) -> str:
                parts = []
                for item in getattr(self, "variable_cash", []):
                    amount = f"'{COST}'!$B${item['amount_row']}"
                    period = f"'{COST}'!$B${item['period_row']}"
                    elapsed = f"((YEAR({date})-YEAR({sales}))*12+MONTH({date})-MONTH({sales}))"
                    pay = (
                        f"IF({period}<=0,AND(YEAR({date})=YEAR({sales}),MONTH({date})=MONTH({sales})),"
                        f"AND({date}>=DATE(YEAR({sales}),MONTH({sales}),1),MOD({elapsed},{period})=0))"
                    )
                    parts.append(f"{amount}*({cost_index(date, 48)})*({pay})")
                return "+".join(parts) if parts else "0"

            amount_col = get_column_letter(self.admin_amount_col)
            period_col = get_column_letter(self.admin_period_col)
            flag_col = get_column_letter(self.admin_flag_col)
            admin_rows = list(getattr(self, "admin_rows", []))
            # Колонка J — сумма. Каждая строка бюджета считается отдельно правее видимой таблицы:
            # одна общая формула на все строки длиннее лимита Excel в 8192 символа, и файл просит восстановление.
            helper_start = max(24, last_year + 3) + 2

            def admin_item(date: str, item_row: int) -> str:
                amount = f"'{ADM}'!${amount_col}${item_row}"
                period = f"'{ADM}'!${period_col}${item_row}"
                flag = f"'{ADM}'!${flag_col}${item_row}"
                elapsed_plan = f"((YEAR({date})-YEAR({plan_start}))*12+MONTH({date})-MONTH({plan_start}))"
                elapsed_sales = f"((YEAR({date})-YEAR({sales}))*12+MONTH({date})-MONTH({sales}))"
                # flag=1 (invest): с начала проекта на весь срок; flag=0: только с начала продаж.
                from_plan = (
                    f"AND({date}>=DATE(YEAR({plan_start}),MONTH({plan_start}),1),"
                    f"IF({period}<=0,{elapsed_plan}=0,AND({elapsed_plan}>=0,MOD({elapsed_plan},{period})=0)))"
                )
                regular = (
                    f"AND({date}>=DATE(YEAR({sales}),MONTH({sales}),1),"
                    f"IF({period}<=0,{elapsed_sales}=0,AND({elapsed_sales}>=0,MOD({elapsed_sales},{period})=0)))"
                )
                return f"{amount}*({cost_index(date, 49)})*IF({flag}=1,IF({from_plan},1,0),IF({regular},1,0))"

            pay_a = getattr(self, "pay_month_first", None)
            pay_b = getattr(self, "pay_month_last", None)
            bold = Font(name=FONT_NAME, size=10, bold=True)
            funding = f"'{FIN}'!F{self.fin_total_row}+'{FIN}'!E{self.fin_total_row}+'{FIN}'!G{self.fin_total_row}+'{FIN}'!D{self.fin_total_row}"
            vat_on = f"'{SHEET}'!{self._source_cells()['vat_on']}"
            vat_rate = f"'{SHEET}'!{self._source_cells()['vat_rate']}"
            vat_factor = f"(1+IF({vat_on}=1,{vat_rate},0))"
            plan_months = _iter_months(self.form)
            for index, month_source in enumerate(sources):
                row = first_month + index
                date = f"A{row}"
                year_number = plan_months[index].year
                month_number = plan_months[index].month
                last_of_year = index == len(sources) - 1 or plan_months[index + 1].year != year_number
                july_in_year = any(item.year == year_number and item.month == 7 for item in plan_months)
                self._put(row, 1, f"='{MONTHS}'!A{month_source}", border=BOX, fmt="DD.MM.YYYY", align="center")
                self._put(row, 2, f"=YEAR({date})", border=BOX, fmt="0", align="center")
                self._put(row, 3, f"='{MONTHS}'!D{month_source}", border=BOX, fmt="0", align="center")
                rev_cols = [item["rev_col"] for item in links.get("products") or []]
                if links.get("other"):
                    rev_cols.append(links["other"]["col"])
                receipts = "+".join(f"'{MONTHS}'!{get_column_letter(column)}{month_source}" for column in rev_cols) or "0"
                # Нетто-выручка × (1+НДС) при плательщике: в ОПиУ выручка без НДС.
                self._put(row, 4, f"=({receipts})*C{row}*{vat_factor}", border=BOX, fmt=MONEY, align="right")
                self._put(row, 5, f"=({goods(date, month_source)})*C{row}", border=BOX, fmt=MONEY, align="right")
                if index == 0:
                    self._put(row, 6, f"=MAX({stock_cell},E{row})*{vat_factor}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 16, f"=MAX(0,{stock_cell}-E{row})", border=BOX, fmt=MONEY, align="right")
                else:
                    self._put(row, 6, f"=MAX(0,E{row}-P{row - 1})*{vat_factor}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 16, f"=MAX(0,P{row - 1}-E{row})", border=BOX, fmt=MONEY, align="right")
                self._put(row, 7, f"=({variable(date)})*C{row}*{vat_factor}", border=BOX, fmt=MONEY, align="right")
                if pay_a:
                    # К выплате сотруднику = начисление − ОПВ − ВОСМС.
                    accrued = f"SUMIFS('{PAY}'!$H${pay_a}:$H${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                    withhold = (
                        f"SUMIFS('{PAY}'!$N${pay_a}:$N${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                        f"+SUMIFS('{PAY}'!$O${pay_a}:$O${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                    )
                    self._put(row, 8, f"=({accrued})-({withhold})", border=BOX, fmt=MONEY, align="right")
                    # Налоги ФОТ: СО+ОПВР+ООСМС+СН + перечисление удержаний ОПВ+ВОСМС.
                    self._put(
                        row,
                        9,
                        (
                            f"=SUMIFS('{PAY}'!$K${pay_a}:$K${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                            f"+SUMIFS('{PAY}'!$L${pay_a}:$L${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                            f"+SUMIFS('{PAY}'!$M${pay_a}:$M${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                            f"+SUMIFS('{PAY}'!$P${pay_a}:$P${pay_b},'{PAY}'!$A${pay_a}:$A${pay_b},{date})"
                            f"+({withhold})"
                        ),
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
                else:
                    self._put(row, 8, "=0", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 9, "=0", border=BOX, fmt=MONEY, align="right")
                if admin_rows:
                    first_helper = get_column_letter(helper_start)
                    last_helper = get_column_letter(helper_start + len(admin_rows) - 1)
                    self._put(row, 10, f"=SUM({first_helper}{row}:{last_helper}{row})*C{row}*{vat_factor}", border=BOX, fmt=MONEY, align="right")
                    for offset, item_row in enumerate(admin_rows):
                        self._put(row, helper_start + offset, f"={admin_item(date, item_row)}", fmt=MONEY, align="right")
                else:
                    self._put(row, 10, "=0", border=BOX, fmt=MONEY, align="right")
                sched_dates, sched_interest = self._schedule_interest_range()
                self._put(
                    row,
                    11,
                    f"=SUMIFS({sched_interest},{sched_dates},\">=\"&{date},{sched_dates},\"<\"&EDATE({date},1))",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
                year_tax = (
                    f"IFERROR(INDEX('{PNL}'!$B${pnl['tax']}:{get_column_letter(1 + self.years)}${pnl['tax']},"
                    f"MATCH(B{row},'{PNL}'!$B$4:{get_column_letter(1 + self.years)}$4,0)),0)"
                )
                general = f"'{SHEET}'!{self._source_cells()['tax_general']}"
                if month_number == 7 and not last_of_year:
                    regime_tax = f"IF({general}=1,0,{year_tax}/2)"
                elif last_of_year and july_in_year and month_number != 7:
                    regime_tax = f"IF({general}=1,{year_tax},{year_tax}/2)"
                elif last_of_year:
                    regime_tax = f"{year_tax}"
                else:
                    regime_tax = "0"
                # Нетто НДС: исходящий с нетто-выручки − входящий с нетто закупок/адм./CAPEX.
                net_receipts = f"({receipts})*C{row}"
                if self.capex_items:
                    capex_net = (
                        f"SUMIFS('{CAPEX}'!$M${self.capex_first}:$M${self.capex_last},"
                        f"'{CAPEX}'!$I${self.capex_first}:$I${self.capex_last},\">=\"&{date},"
                        f"'{CAPEX}'!$I${self.capex_first}:$I${self.capex_last},\"<\"&EDATE({date},1))"
                    )
                else:
                    capex_net = "0"
                if index == 0:
                    supplier_net = f"MAX({stock_cell},E{row})"
                else:
                    supplier_net = f"MAX(0,E{row}-P{row - 1})"
                var_net = f"({variable(date)})*C{row}"
                if admin_rows:
                    first_helper = get_column_letter(helper_start)
                    last_helper = get_column_letter(helper_start + len(admin_rows) - 1)
                    adm_net = f"SUM({first_helper}{row}:{last_helper}{row})*C{row}"
                else:
                    adm_net = "0"
                vat_net = (
                    f"IF({vat_on}=0,0,({net_receipts})*({vat_rate})"
                    f"-(({supplier_net})+({var_net})+({adm_net})+({capex_net}))*({vat_rate}))"
                )
                self._put(row, 12, f"=({regime_tax})+({vat_net})", border=BOX, fmt=MONEY, align="right")
                self._put(row, 13, f"=({capex_net})*{vat_factor}", border=BOX, fmt=MONEY, align="right")
                sched_dates_p, sched_principal = self._schedule_principal_range()
                self._put(
                    row,
                    14,
                    f"=SUMIFS({sched_principal},{sched_dates_p},\">=\"&{date},{sched_dates_p},\"<\"&EDATE({date},1))",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                )
                self._put(row, 15, f"=IF({date}=DATE(YEAR({plan_start}),MONTH({plan_start}),1),{funding},0)", border=BOX, fmt=MONEY, align="right")
                dividend_rate = f"'{SHEET}'!{self._source_cells()['dividend']}"
                dividend_term = f"'{SHEET}'!{self._source_cells()['investor_term']}"
                net_last = get_column_letter(1 + self.years)
                prior_profit = (
                    f"IFERROR(INDEX('{PNL}'!$B${self.pnl['net']}:{net_last}${self.pnl['net']},"
                    f"MATCH(YEAR({date})-1,'{PNL}'!$B$4:{net_last}$4,0)),0)"
                )
                planned_dividend = (
                    f"IF(OR({dividend_rate}<=0,{dividend_term}<=0,YEAR({date})=YEAR({plan_start}),MONTH({date})<>MONTH({plan_start}),"
                    f"YEAR({date})-YEAR({plan_start})>{dividend_term}),0,{dividend_rate}*MAX(0,{prior_profit}))"
                )
                cash_before_dividend = f"R{row}+D{row}-F{row}-G{row}-H{row}-I{row}-J{row}-K{row}-L{row}-M{row}-N{row}+O{row}"
                self._put(row, 20, f"=MIN({planned_dividend},MAX(0,{cash_before_dividend}))", border=BOX, fmt=MONEY, align="right")
                self._put(row, 22, f"=({planned_dividend})-T{row}", border=BOX, fmt=MONEY, align="right")
                effect_row = 24 + index
                gross = f"'{EFFECT}'!AP{effect_row}"
                exit_when = f"EDATE({plan_start},'{EFFECT}'!$B$16*12)"
                plan_end_ref = f"'{SHEET}'!{self._source_cells()['plan_end']}"
                buyback_gate = f"OR('{EFFECT}'!$I$15<>2,A{row}<>{exit_when},{exit_when}>{plan_end_ref})"
                self._put(row, 23, f"=IF({buyback_gate},0,MIN({gross},MAX(0,{cash_before_dividend}-T{row})))", border=BOX, fmt=MONEY, align="right")
                self._put(row, 24, f"=IF({buyback_gate},0,MAX(0,{gross}-W{row}))", border=BOX, fmt=MONEY, align="right")
                self._put(row, 17, f"=D{row}-F{row}-G{row}-H{row}-I{row}-J{row}-K{row}-L{row}-M{row}-N{row}-T{row}-W{row}+O{row}", border=BOX, fmt=MONEY, align="right")
                self._put(row, 18, "=0" if index == 0 else f"=S{row - 1}", border=BOX, fmt=MONEY, align="right")
                self._put(row, 19, f"=Q{row}+R{row}", border=BOX, fmt=MONEY, align="right")
            for offset in range(len(admin_rows)):
                ws.column_dimensions[get_column_letter(helper_start + offset)].hidden = True
            last_month = month_rows[-1] if month_rows else first_month
            span = f"${first_month}:${last_month}" if month_rows else None
            capex_letters = []
            year_month_rows: list[list[int]] = []
            for offset in range(self.years):
                letter = get_column_letter(2 + offset)
                year_cell = f"{letter}$4"
                year_number = _year(self.form.get("planning_from")) + offset
                rows = by_year.get(year_number) or []
                year_month_rows.append(rows)

                def year_sum(column: str, rows=rows) -> str:
                    if not rows:
                        return "=0"
                    return "=" + "+".join(f"{column}{item}" for item in rows)

                self._put(income_row, 2 + offset, year_sum("D"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(inflow_row, 2 + offset, f"=SUM({letter}{income_row})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(cogs_row, 2 + offset, f"={year_sum('F')[1:]}+{year_sum('G')[1:]}" if rows else "=0", border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(fot_row, 2 + offset, year_sum("H"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(fot_tax_row, 2 + offset, year_sum("I"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(admin_row, 2 + offset, year_sum("J"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(interest_row, 2 + offset, year_sum("K"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(tax_row, 2 + offset, year_sum("L"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(outflow_row, 2 + offset, f"=SUM({letter}{cogs_row}:{letter}{tax_row})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(operating_row, 2 + offset, f"={letter}{inflow_row}-{letter}{outflow_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(capex_row, 2 + offset, year_sum("M"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(stock_row, 2 + offset, f"=P{rows[-1]}" if rows else "=0", border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(preopen_row, 2 + offset, "=0", border=BOX, fmt=MONEY, align="right")
                self._put(investing_row, 2 + offset, f"=-{letter}{capex_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(own_row, 2 + offset, source("F", year_cell), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(investor_row, 2 + offset, source("E", year_cell), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(grant_row, 2 + offset, source("G", year_cell), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(draw_row, 2 + offset, source("D", year_cell), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(principal_row, 2 + offset, year_sum("N"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(dividend_row, 2 + offset, year_sum("T"), border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                buyback_sum = year_sum("W")
                self._put(financing_row, 2 + offset, f"={letter}{own_row}+{letter}{investor_row}+{letter}{grant_row}+{letter}{draw_row}-{letter}{principal_row}-{letter}{dividend_row}-({buyback_sum[1:]})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(net_row, 2 + offset, f"={letter}{operating_row}+{letter}{investing_row}+{letter}{financing_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                opening = "=0" if offset == 0 else f"={get_column_letter(1 + offset)}{close_row}"
                self._put(open_row, 2 + offset, opening, border=BOX, fmt=MONEY, align="right")
                self._put(close_row, 2 + offset, f"={letter}{net_row}+{letter}{open_row}", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                gap = "=" + ",".join(f"S{item}" for item in rows) if rows else "=0"
                self._put(gap_row, 2 + offset, f"=MIN({gap[1:]})" if rows else "=0", border=BOX, fmt=MONEY, align="right", fill=PINK, font=bold)
                capex_letters.append(f"{letter}{capex_row}")
            check_column = last_year + 2
            check_value = check_column + 1
            ws.column_dimensions[get_column_letter(check_column)].width = 28
            ws.column_dimensions[get_column_letter(check_value)].width = 18
            paid = "=" + "+".join(capex_letters)
            register = f"='{CAPEX}'!M{self.capex_total_row}" if self.capex_items else "=0"
            self._put(capex_row, check_column, "Оплаты CAPEX за все годы", border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
            self._put(capex_row, check_value, paid, border=GREY_BOX, fmt=MONEY, align="right")
            self._put(stock_row, check_column, "Реестр, к оплате", border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
            self._put(stock_row, check_value, register, border=GREY_BOX, fmt=MONEY, align="right", fill=PURPLE)
            self._put(
                preopen_row,
                check_value,
                f'=IF(ABS({get_column_letter(check_value)}{capex_row}-{get_column_letter(check_value)}{stock_row})<1,"реестр оплачен один раз","не сходится")',
                border=GREY_BOX,
                align="left",
            )
            deficit_row = 33
            self._put(
                deficit_row,
                1,
                "Дефицит ликвидности",
                border=BOX,
                fill=PINK,
                wrap=True,
                font=Font(name=FONT_NAME, size=10, bold=True),
            )
            for offset in range(self.years):
                letter = get_column_letter(2 + offset)
                self._put(
                    deficit_row,
                    2 + offset,
                    f"=MAX(0,-{letter}{gap_row})",
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                    fill=PINK,
                    font=bold,
                )
            ws.row_dimensions[deficit_row].height = 30
            flow_abs = []
            balance_abs = []
            for offset, rows in enumerate(year_month_rows):
                if not rows:
                    continue
                letter = get_column_letter(2 + offset)
                flow_abs.append(f"ABS({letter}{net_row}-(" + "+".join(f"Q{item}" for item in rows) + "))")
                balance_abs.append(f"ABS({letter}{close_row}-S{rows[-1]})")
            received = "+".join(
                f"{get_column_letter(2 + offset)}{own_row}+{get_column_letter(2 + offset)}{investor_row}+{get_column_letter(2 + offset)}{grant_row}+{get_column_letter(2 + offset)}{draw_row}"
                for offset in range(self.years)
            )
            sources_total = (
                f"'{FIN}'!D{self.fin_total_row}+'{FIN}'!E{self.fin_total_row}+'{FIN}'!F{self.fin_total_row}+'{FIN}'!G{self.fin_total_row}"
            )
            value_letter = get_column_letter(check_value)
            checks = (
                (21, "Годовой поток − сумма месяцев", "=MAX(" + ",".join(flow_abs) + ")" if flow_abs else "=0"),
                (22, "Остаток года − последний месяц", "=MAX(" + ",".join(balance_abs) + ")" if balance_abs else "=0"),
                (23, "CAPEX ДДС − реестр", f"={value_letter}{capex_row}-{value_letter}{stock_row}"),
                (24, "Финансирование − источники", f"=({received})-({sources_total})"),
                (26, "Максимальный кассовый разрыв", f"=MAX(B{deficit_row}:{get_column_letter(1 + self.years)}{deficit_row})"),
            )
            self._put(20, check_column, "Контроль, ожидается 0", border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8, bold=True))
            for row, label, formula in checks:
                self._put(row, check_column, label, border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
                self._put(row, check_value, formula, border=GREY_BOX, fmt=MONEY, align="right", fill=GREEN if row == 26 else None)
                ws.row_dimensions[row].height = 28
            self._put(27, check_column, "Разрыв к инвестору не прибавляется", border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
            self.cf = {
                "operating": operating_row,
                "interest": interest_row,
                "investing": investing_row,
                "capex": capex_row,
                "own": own_row,
                "investor": investor_row,
                "grant": grant_row,
                "draw": draw_row,
                "principal": principal_row,
                "dividend": dividend_row,
                "net": net_row,
                "month_first": first_month,
                "month_last": last_month,
                "close": close_row,
                "gap": gap_row,
                "deficit": deficit_row,
                "deficit_total": (check_value, 26),
            }
        finally:
            self.ws = previous


    def _asset_nbv(self, date_cell: str) -> str:
        """Остаточная стоимость CAPEX на дату: прямая линия от даты ввода, до оплаты ноль."""
        if not self.capex_items:
            return "0"
        parts = []
        for row in range(self.capex_first, self.capex_last + 1):
            cost = f"'{CAPEX}'!H{row}"
            life = f"'{CAPEX}'!E{row}"
            pay = f"'{CAPEX}'!I{row}"
            start = f"'{CAPEX}'!K{row}"
            elapsed = f"((YEAR({date_cell})-YEAR({start}))*12+MONTH({date_cell})-MONTH({start})+1)"
            parts.append(f"IF({date_cell}<{pay},0,IF({life}<=0,{cost},MAX(0,{cost}*(1-MIN(1,{elapsed}/({life}*12))))))")
        return "+".join(parts)

    def _loan_balance(self, date_cell: str) -> str:
        """Сальдо всех траншей в этот месяц. После последнего платежа остаток ноль."""
        parts = []
        for block in getattr(self, "loan_blocks", []):
            dates = block.get("date_range")
            balances = block.get("balance_range")
            if dates and balances:
                parts.append(f"SUMIFS({balances},{dates},{date_cell})")
        return "+".join(parts) if parts else "0"

    def _scenarios(self, origin: int, last: int, first_dds: int):
        """Чувствительность того же помесячного FCFF. Поток, ставка и касса не переписываются."""
        cells = self._source_cells()
        general = f"'{SHEET}'!{cells['tax_general']}"
        sales = f"'{SHEET}'!{cells['sales_start']}"
        titles = (
            (53, "Выручка −10%"),
            (54, "Выручка −20%"),
            (55, "Расходы +10%"),
            (56, "Запуск +3 месяца"),
        )
        for column, title in titles:
            self._put(23, column, title, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            self.ws.column_dimensions[get_column_letter(column)].width = 18
        for column, title in ((57, "Касса −10%"), (58, "Касса −20%"), (59, "Касса расходы +10%"), (60, "Касса запуск +3")):
            self._put(23, column, title, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            self.ws.column_dimensions[get_column_letter(column)].width = 16
        for index in range(last - origin + 1):
            row = origin + index
            dds = first_dds + index
            rev = f"'{CF}'!D{dds}"
            opening_stock = f"MIN('{CF}'!$U$34,'{CF}'!F{dds})" if index == 0 else "0"
            var = f"'{CF}'!F{dds}-({opening_stock})+'{CF}'!G{dds}"
            fixed = f"'{CF}'!H{dds}+'{CF}'!I{dds}+'{CF}'!J{dds}"
            tax = f"'{CF}'!L{dds}"
            window = (
                f"AND(A{row}>=DATE(YEAR({sales}),MONTH({sales}),1),"
                f"A{row}<EDATE(DATE(YEAR({sales}),MONTH({sales}),1),3))"
            )
            flows = (
                (53, f"=B{row}-0.1*({rev}-({var}))+IF({general}=0,0.1*{tax},0)"),
                (54, f"=B{row}-0.2*({rev}-({var}))+IF({general}=0,0.2*{tax},0)"),
                (55, f"=B{row}-0.1*({fixed})"),
                (56, f"=B{row}-IF({window},{rev}-({var}),0)"),
            )
            for column, formula in flows:
                self._put(row, column, formula, border=BOX, fmt=MONEY, align="right")
            for column, cash_column in ((53, 57), (54, 58), (55, 59), (56, 60)):
                letter = get_column_letter(column)
                formula = f"='{CF}'!S{dds}+SUM(${letter}${origin}:{letter}{row})-SUM($B${origin}:B{row})"
                self._put(row, cash_column, formula, border=BOX, fmt=MONEY, align="right")
        dates = f"A{origin}:A{last}"
        discount = f"D{origin}:D{last}"
        self._put(4, 53, "Сценарий", border=BOX, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
        self._put(4, 54, "NPV", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
        self._put(4, 55, "IRR", border=BOX, align="center", fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
        self._put(4, 56, "Макс. кассовый разрыв", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
        self._put(5, 53, "База", border=BOX)
        self._put(5, 54, "=B5", border=BOX, fmt=MONEY, align="right")
        self._put(5, 55, "=B6", border=BOX, fmt=PCT_FINE, align="center")
        self._put(5, 56, "=B11", border=BOX, fmt=MONEY, align="right")
        rows = (
            (6, "Выручка −10%", 53, 57),
            (7, "Выручка −20%", 54, 58),
            (8, "Расходы +10%", 55, 59),
            (9, "Запуск +3 месяца", 56, 60),
        )
        for row, title, flow_column, cash_column in rows:
            flow = f"{get_column_letter(flow_column)}{origin}:{get_column_letter(flow_column)}{last}"
            cash = f"{get_column_letter(cash_column)}{origin}:{get_column_letter(cash_column)}{last}"
            rate = f"XIRR({flow},{dates})"
            irr = (
                f'IF(OR(MAX({flow})<=0,MIN({flow})>=0),"нет смены знака",'
                f'IFERROR(IF({rate}<=-0.99,"не определяется",{rate}),"не определяется"))'
            )
            self._put(row, 53, title, border=BOX, wrap=True)
            self._put(row, 54, f"=SUMPRODUCT({flow},{discount})", border=BOX, fmt=MONEY, align="right")
            self._put(row, 55, f"={irr}", border=BOX, fmt=PCT_FINE, align="center")
            self._put(row, 56, f"=MAX(0,-MIN({cash}))", border=BOX, fmt=MONEY, align="right", fill=PINK)
        self._put(
            10,
            53,
            "Выручка −10% и −20% уменьшают поступления и закупки под продажи. Стартовая закупка запаса не уменьшается. На упрощёнке налог с выручки уменьшается вместе с ней. Касса сценария — остаток этого месяца плюс накопленная разница потока. Расходы +10% — это ФОТ, налоги ФОТ и административные. Запуск +3 месяца убирает выручку и переменные закупки трёх первых месяцев продаж, налог этого сценария не пересчитан.",
            wrap=True,
            font=Font(name=FONT_NAME, size=8, italic=True, color="44546A"),
        )
        self.ws.merge_cells(start_row=10, start_column=53, end_row=10, end_column=56)
        self.ws.row_dimensions[10].height = 48

    def _effect_sheet(self):
        """NPV, IRR, PI и сроки по датам месяцев. Проект и инвестор — разные потоки."""
        ws = self.book.create_sheet(EFFECT)
        previous = self.ws
        self.ws = ws
        try:
            ws.sheet_properties.tabColor = "0B6E4F"
            ws.page_setup.orientation = "landscape"
            ws.page_setup.fitToPage = True
            ws.page_setup.fitToWidth = 1
            ws.page_setup.paperSize = ws.PAPERSIZE_A4
            ws.sheet_format.defaultRowHeight = 18
            ws.column_dimensions["A"].width = 46
            for column in range(2, 23):
                ws.column_dimensions[get_column_letter(column)].width = 16
            bold = Font(name=FONT_NAME, size=10, bold=True)
            small = Font(name=FONT_NAME, size=8, italic=True, color="44546A")
            cf = self.cf
            first_dds = int(cf["month_first"])
            last_dds = int(cf["month_last"])
            count = max(0, last_dds - first_dds + 1)
            origin = 24
            end = origin + count - 1 if count else origin
            cells = self._source_cells()
            plan_start = f"'{SHEET}'!{cells['plan_start']}"
            investor_total = f"'{FIN}'!E{self.fin_total_row}"
            own_total = f"'{FIN}'!F{self.fin_total_row}"
            tax_last = get_column_letter(1 + self.years)
            self._put(
                1,
                1,
                "Проект считается по помесячному FCFF: поступления минус оплата поставщикам, переменные выплаты, ФОТ, налоги ФОТ, административные, налог и CAPEX. Взносы инвестора, собственные, кредит, грант, тело долга и дивиденды в FCFF не входят. Проценты возвращаются полностью, если за год налог на прибыль равен нулю. Если налог есть, возвращается только проценты × (1 − щит): щит берётся со строки предпосылок и на упрощёнке равен нулю. NPV и IRR смотрят на дату каждого месяца. Первый месяц не дисконтируется, дальше степень равна числу дней от первой даты, делённому на 365. IRR — годовая XIRR. Если знак потока не меняется, число IRR не выводится.",
                wrap=True,
                font=small,
            )
            ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=8)
            ws.row_dimensions[1].height = 72
            self._put(
                2,
                1,
                "Инвестор — только его деньги. Взнос в месяц начала плана со знаком минус. Плюс дивиденды, которые ДДС реально выплачивает. Невыплаченный остаток в IRR не входит. Голубые ячейки: I13 режим (1 — до конца прогноза, 2 — до даты выхода), I15 способ (1 — продажа доли покупателю, 2 — выкуп из кассы, 3 — без выхода), I17 оценка (1 — чистые активы, 2 — DCF при нулевом росте). По умолчанию стоит режим 1: если выход позже плана, он в IRR не входит. Режим 2 — упрощённый сценарий, не полный прогноз: после плана нет новой выручки, расходов, налогов и CAPEX, берётся средний FCFF последних 12 месяцев. Дивиденд продления — доля последней известной годовой прибыли и только в пределах сценарной кассы. Продажа из кассы не платится. Выкуп после плана платится из этой кассы, не из ДДС; нехватка показана отдельно. Ставка инвестора — базовая плюс премия.",
                wrap=True,
                font=small,
            )
            ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=8)
            ws.row_dimensions[2].height = 78
            capacity_ranges = getattr(self, "capacity_ranges_full", None) or []
            self._put(3, 1, _capacity_warning(capacity_ranges) if capacity_ranges else '=""', wrap=True, font=Font(name=FONT_NAME, size=8, italic=True, color="9C5700"))
            ws.merge_cells(start_row=3, start_column=1, end_row=3, end_column=8)
            ws.row_dimensions[3].height = 32
            self._put(4, 2, "Проект", border=BOX, align="center", fill=GRAY, font=bold)
            self._put(4, 3, "Инвестор", border=BOX, align="center", fill=GRAY, font=bold)
            self._put(4, 4, "Предупреждение проекта", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            self._put(4, 5, "Предупреждение инвестора", border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            ws.column_dimensions["D"].width = 28
            ws.column_dimensions["E"].width = 28
            ws.column_dimensions["H"].width = 42
            for row, label in (
                (5, "NPV"),
                (6, "IRR, годовая по датам"),
                (7, "Индекс PI"),
                (8, "Простой срок, лет"),
                (9, "Дисконтированный срок, лет"),
            ):
                self._put(row, 1, label, border=BOX, font=bold if row == 5 else TEXT)
            self._put(10, 1, "Минимальный DSCR", border=BOX)
            self._put(11, 1, "Дефицит ликвидности", border=BOX, fill=PINK, font=bold)
            deficit_column, deficit_row = cf["deficit_total"]
            self._put(11, 2, f"='{CF}'!{get_column_letter(deficit_column)}{deficit_row}", border=BOX, fmt=MONEY, align="right", fill=PINK, font=bold)
            self._put(
                11,
                3,
                '=IF(B11<1,"Кассового разрыва нет","Деньги заканчиваются внутри плана. Не хватает "&TEXT(B11,"# ##0")&". Варианты: увеличить взнос инвестора, добавить кредит, снизить постоянные расходы.")',
                border=BOX,
                align="left",
                wrap=True,
                fill=PINK,
            )
            ws.row_dimensions[11].height = 48
            self._put(13, 1, "Ставка проекта, WACC", border=BOX)
            self._put(13, 2, f"='{SHEET}'!C25", border=BOX, fmt=PCT_FINE, align="center", fill=PURPLE, font=bold)
            self._put(14, 1, "Ставка инвестора", border=BOX)
            self._put(14, 2, f"='{SHEET}'!C19", border=BOX, fmt=PCT_FINE, align="center", fill=PURPLE, font=bold)
            self._put(15, 1, "Доля прибыли, которая платится инвестору", border=BOX, wrap=True)
            self._put(15, 2, f"='{SHEET}'!{cells['dividend']}", border=BOX, fmt=PCT_FINE, align="center", fill=PURPLE)
            self._put(16, 1, "Срок участия, лет", border=BOX)
            self._put(16, 2, f"='{SHEET}'!{cells['investor_term']}", border=BOX, fmt="0", align="center", fill=PURPLE)
            self._put(17, 1, "Доля инвестора в собственном капитале", border=BOX, wrap=True)
            self._put(17, 2, f"=IF({investor_total}+{own_total}=0,0,{investor_total}/({investor_total}+{own_total}))", border=BOX, fmt=PCT_FINE, align="center", fill=PURPLE, font=bold)
            self._put(18, 1, "Налоговый щит процентов", border=BOX)
            self._put(18, 2, f"='{SHEET}'!C22", border=BOX, fmt=PCT_FINE, align="center", fill=PURPLE)
            self._put(13, 8, "Режим: 1 конец прогноза, 2 полный выход", border=BOX, wrap=True)
            self._put(13, 9, 1, border=BOX, fmt="0", align="center", fill=BLUE, font=bold)
            self._put(15, 8, "Выход: 1 продажа, 2 выкуп, 3 нет", border=BOX, wrap=True)
            self._put(15, 9, 1, border=BOX, fmt="0", align="center", fill=BLUE, font=bold)
            self._put(17, 8, "Оценка: 1 чистые активы, 2 DCF", border=BOX, wrap=True)
            self._put(17, 9, 1, border=BOX, fmt="0", align="center", fill=BLUE, font=bold)
            self._put(12, 8, "Остаток активов в последнем FCFF: 1 да, 0 нет", border=BOX, wrap=True)
            self._put(12, 9, 1, border=BOX, fmt="0", align="center", fill=BLUE, font=bold)
            ws.row_dimensions[13].height = 30
            ws.row_dimensions[15].height = 30
            ws.row_dimensions[17].height = 30
            for row in (15, 17):
                ws.row_dimensions[row].height = 30
            headers = (
                "Дата", "FCFF проекта", "FCFF нарастающим", "Коэффициент проекта", "Дисконт FCFF", "Дисконт нарастающим",
                "Взнос инвестора", "Дивиденды инвестору", "Выход инвестора", "Поток инвестора", "Поток нарастающим",
                "Коэффициент инвестора", "Дисконт инвестора", "Дисконт инвестора нарастающим",
            )
            for column, title in enumerate(headers, start=1):
                self._put(23, column, title, border=BOX, align="center", wrap=True, fill=GRAY, font=Font(name=FONT_NAME, size=8, bold=True))
            ws.row_dimensions[23].height = 36
            if count:
                term_years = int(_num(self.form.get("investor_term"))) if int(self.form.get("financing") or 1) in (1, 4) else 0
                extra = min(120, max(0, term_years * 12 - (count - 1)))
                forecast_last = end
                last = end + extra
                avg_from = origin if count < 12 else end - 11
                self._put(18, 8, "FCFF продления, в месяц", border=BOX, wrap=True)
                self._put(18, 9, f"=AVERAGE(B{avg_from}:B{forecast_last})", border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                for index in range(count):
                    row = origin + index
                    dds = first_dds + index
                    date = f"A{row}"
                    shield = (
                        f"IF(IFERROR(INDEX('{PNL}'!$B${self.pnl['tax']}:{tax_last}${self.pnl['tax']},"
                        f"MATCH(YEAR({date}),'{PNL}'!$B$4:{tax_last}$4,0)),0)>0,$B$18,0)"
                    )
                    self._put(row, 1, f"='{CF}'!A{dds}", border=BOX, fmt="DD.MM.YYYY", align="center")
                    terminal = f"IF($I$12=1,AG{row},0)" if index == count - 1 else "0"
                    self._put(
                        row,
                        2,
                        f"='{CF}'!D{dds}-'{CF}'!F{dds}-'{CF}'!G{dds}-'{CF}'!H{dds}-'{CF}'!I{dds}-'{CF}'!J{dds}-'{CF}'!L{dds}-'{CF}'!M{dds}-'{CF}'!K{dds}*({shield})+{terminal}",
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
                    self._put(row, 3, f"=B{row}" if index == 0 else f"=C{row - 1}+B{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 4, f"=IF($B$13<=0,1,1/(1+$B$13)^(({date}-$A${origin})/365))", border=BOX, fmt="0.000", align="center")
                    self._put(row, 5, f"=B{row}*D{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 6, f"=E{row}" if index == 0 else f"=F{row - 1}+E{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 7, f"=IF({date}=DATE(YEAR({plan_start}),MONTH({plan_start}),1),-{investor_total},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 8, f"='{CF}'!T{dds}", border=BOX, fmt=MONEY, align="right")
                    exit_date = f"EDATE({plan_start},$B$16*12)"
                    self._put(
                        row,
                        9,
                        f'=IF(OR($B$16<=0,$B$17<=0,$I$15=3),0,IF(AND({date}=({exit_date}),OR($I$13=2,{exit_date}<=$A${forecast_last})),IF($I$15=2,\'{CF}\'!W{dds},AP{row}),0))',
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
                    self._put(row, 10, f"=G{row}+H{row}+I{row}", border=BOX, fmt=MONEY, align="right", fill=GREEN)
                    self._put(row, 11, f"=J{row}" if index == 0 else f"=K{row - 1}+J{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 12, f"=IF($B$14<=0,1,1/(1+$B$14)^(({date}-$A${origin})/365))", border=BOX, fmt="0.000", align="center")
                    self._put(row, 13, f"=J{row}*L{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 14, f"=M{row}" if index == 0 else f"=N{row - 1}+M{row}", border=BOX, fmt=MONEY, align="right")
                for step in range(extra):
                    row = forecast_last + 1 + step
                    previous = row - 1
                    date = f"A{row}"
                    self._put(row, 1, f"=EDATE(A{previous},1)", border=BOX, fmt="DD.MM.YYYY", align="center", fill=PINK)
                    self._put(row, 2, f'=IF($I$13=2,$I$18,0)', border=BOX, fmt=MONEY, align="right", fill=PINK)
                    self._put(row, 3, f"=C{previous}+B{row}", border=BOX, fmt=MONEY, align="right", fill=PINK)
                    self._put(row, 4, f"=IF($B$13<=0,1,1/(1+$B$13)^(({date}-$A${origin})/365))", border=BOX, fmt="0.000", align="center")
                    self._put(row, 5, f"=B{row}*D{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 6, f"=F{previous}+E{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 7, 0, border=BOX, fmt=MONEY, align="right")
                    first_ext = forecast_last + 1
                    prior_div = "0" if row == first_ext else f"SUM(H{first_ext}:H{row - 1})"
                    self._put(row, 44, f"=IF($I$13<>2,0,'{CF}'!S{last_dds}+SUM(B{first_ext}:B{row})-({prior_div}))", border=BOX, fmt=MONEY, align="right", fill=PINK)
                    last_profit = f"'{PNL}'!{tax_last}{self.pnl['net']}"
                    self._put(
                        row,
                        8,
                        f'=IF(OR($I$13<>2,$B$15<=0,$B$16<=0,MONTH({date})<>MONTH({plan_start}),YEAR({date})-YEAR({plan_start})>$B$16),0,MIN($B$15*MAX(0,{last_profit}),MAX(0,AR{row})))',
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                        fill=PINK,
                    )
                    exit_date = f"EDATE({plan_start},$B$16*12)"
                    self._put(
                        row,
                        9,
                        f'=IF(OR($B$16<=0,$B$17<=0,$I$15=3,$I$13<>2),0,IF({date}<>{exit_date},0,IF($I$15=2,MIN(AP{row},MAX(0,AR{row}-H{row})),AP{row})))',
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                        fill=PINK,
                    )
                    self._put(row, 43, f'=IF(OR($I$15<>2,$I$13<>2,{date}<>{exit_date}),0,MAX(0,AP{row}-I{row}))', border=BOX, fmt=MONEY, align="right", fill=PINK)
                    self._put(row, 10, f"=G{row}+H{row}+I{row}", border=BOX, fmt=MONEY, align="right", fill=GREEN)
                    self._put(row, 11, f"=K{previous}+J{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 12, f"=IF($B$14<=0,1,1/(1+$B$14)^(({date}-$A${origin})/365))", border=BOX, fmt="0.000", align="center")
                    self._put(row, 13, f"=J{row}*L{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 14, f"=N{previous}+M{row}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 15, "продление", border=BOX, align="center", fill=PINK, font=Font(name=FONT_NAME, size=8, italic=True))
                    self._put(row, 16, f"=IF(AND(C{row}>=0,C{previous}<0,MIN(C{row}:C${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 17, f"=IF(AND(F{row}>=0,F{previous}<0,MIN(F{row}:F${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 18, f"=IF(AND(K{row}>=0,K{previous}<0,MIN(K{row}:K${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 19, f"=IF(AND(N{row}>=0,N{previous}<0,MIN(N{row}:N${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 20, f"=IF(B{row}>0,E{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 21, f"=IF(B{row}<0,E{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 22, f"=IF(J{row}>0,M{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 23, f"=IF(J{row}<0,M{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 27, f"=IF(B{row}=0,AA{previous},SIGN(B{row}))", border=BOX, fmt="0", align="center")
                    self._put(row, 28, f"=IF(AND(B{row}<>0,AA{previous}<>0,AA{row}<>AA{previous}),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 29, f"=IF(J{row}=0,AC{previous},SIGN(J{row}))", border=BOX, fmt="0", align="center")
                    self._put(row, 30, f"=IF(AND(J{row}<>0,AC{previous}<>0,AC{row}<>AC{previous}),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 32, f"=IF(A{row}<=A{previous},1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 33, "=" + (self._asset_nbv(date) or "0"), border=BOX, fmt=MONEY, align="right")
                    self._put(row, 34, "=" + (self._loan_balance(date) or "0"), border=BOX, fmt=MONEY, align="right")
                    frozen = f"'{CF}'!S{last_dds}+'{CF}'!P{last_dds}"
                    self._put(row, 35, f"={frozen}+AG{row}-AH{row}", border=BOX, fmt=MONEY, align="right")
                    trail_from = max(origin, row - 11)
                    trail_months = row - trail_from + 1
                    self._put(row, 40, f"=IF(OR($B$13<=0,SUM(B{trail_from}:B{row})<=0),{frozen}-AH{row},SUM(B{trail_from}:B{row})*12/{trail_months}/$B$13+{frozen}-AH{row})", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 41, f"=IF($I$17=2,AN{row},AI{row})", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 42, f"=MAX(0,$B$17*AO{row})", border=BOX, fmt=MONEY, align="right")
                project_flow = f"B{origin}:B{last}"
                investor_flow = f"J{origin}:J{last}"
                dates = f"A{origin}:A{last}"

                def annual_irr(flow: str, date_range: str) -> str:
                    rate = f"XIRR({flow},{date_range})"
                    return (
                        f'IF(OR(MAX({flow})<=0,MIN({flow})>=0),"нет смены знака",'
                        f'IFERROR(IF({rate}<=-0.99,"не определяется",{rate}),"не определяется"))'
                    )

                def payback(flag_col: str, cum_col: str, flow_col: str) -> str:
                    flags = f"{flag_col}{origin}:{flag_col}{last}"
                    return (
                        f'=IF(SUM({flags})=0,IF($I$13=2,"Не окупается в упрощённом продлении","Не окупается в периоде прогноза"),'
                        f'(INDEX({dates},MATCH(1,{flags},0))-$A${origin})/365'
                        f'+(EDATE(INDEX({dates},MATCH(1,{flags},0)),1)-INDEX({dates},MATCH(1,{flags},0)))/365'
                        f'*IF(INDEX({flow_col}{origin}:{flow_col}{last},MATCH(1,{flags},0))=0,0,'
                        f'ABS(IF(MATCH(1,{flags},0)=1,0,INDEX({cum_col}{origin}:{cum_col}{last},MATCH(1,{flags},0)-1)))'
                        f'/INDEX({flow_col}{origin}:{flow_col}{last},MATCH(1,{flags},0))))'
                    )

                # Флаги окупаемости стоят справа от блока, чтобы не сдвигать денежные колонки.
                for index in range(count):
                    row = origin + index
                    self._put(row, 16, f"=IF(AND(C{row}>=0,B{row}>0,MIN(C{row}:C${last})>=0),1,0)" if index == 0 else f"=IF(AND(C{row}>=0,C{row - 1}<0,MIN(C{row}:C${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 17, f"=IF(AND(F{row}>=0,E{row}>0,MIN(F{row}:F${last})>=0),1,0)" if index == 0 else f"=IF(AND(F{row}>=0,F{row - 1}<0,MIN(F{row}:F${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 18, f"=IF(AND(K{row}>=0,J{row}>0,MIN(K{row}:K${last})>=0),1,0)" if index == 0 else f"=IF(AND(K{row}>=0,K{row - 1}<0,MIN(K{row}:K${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 19, f"=IF(AND(N{row}>=0,M{row}>0,MIN(N{row}:N${last})>=0),1,0)" if index == 0 else f"=IF(AND(N{row}>=0,N{row - 1}<0,MIN(N{row}:N${last})>=0),1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 20, f"=IF(B{row}>0,E{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 21, f"=IF(B{row}<0,E{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 22, f"=IF(J{row}>0,M{row},0)", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 23, f"=IF(J{row}<0,M{row},0)", border=BOX, fmt=MONEY, align="right")
                    dds = first_dds + index
                    self._put(row, 24, f"='{CF}'!D{dds}-'{CF}'!F{dds}-'{CF}'!G{dds}-'{CF}'!H{dds}-'{CF}'!I{dds}-'{CF}'!J{dds}-'{CF}'!L{dds}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 25, f"='{CF}'!K{dds}+'{CF}'!N{dds}", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 26, f'=IF(Y{row}<1,"",X{row}/Y{row})', border=BOX, fmt="0.00", align="center")
                    if index == 0:
                        self._put(row, 27, f"=IF(B{row}=0,0,SIGN(B{row}))", border=BOX, fmt="0", align="center")
                        self._put(row, 28, "=0", border=BOX, fmt="0", align="center")
                        self._put(row, 29, f"=IF(J{row}=0,0,SIGN(J{row}))", border=BOX, fmt="0", align="center")
                        self._put(row, 30, "=0", border=BOX, fmt="0", align="center")
                        self._put(row, 32, "=0", border=BOX, fmt="0", align="center")
                    else:
                        self._put(row, 27, f"=IF(B{row}=0,AA{row - 1},SIGN(B{row}))", border=BOX, fmt="0", align="center")
                        self._put(row, 28, f"=IF(AND(B{row}<>0,AA{row - 1}<>0,AA{row}<>AA{row - 1}),1,0)", border=BOX, fmt="0", align="center")
                        self._put(row, 29, f"=IF(J{row}=0,AC{row - 1},SIGN(J{row}))", border=BOX, fmt="0", align="center")
                        self._put(row, 30, f"=IF(AND(J{row}<>0,AC{row - 1}<>0,AC{row}<>AC{row - 1}),1,0)", border=BOX, fmt="0", align="center")
                        self._put(row, 32, f"=IF(A{row}<=A{row - 1},1,0)", border=BOX, fmt="0", align="center")
                    self._put(row, 33, "=" + (self._asset_nbv(f"A{row}") or "0"), border=BOX, fmt=MONEY, align="right")
                    self._put(row, 34, "=" + (self._loan_balance(f"A{row}") or "0"), border=BOX, fmt=MONEY, align="right")
                    cash_before = f"'{CF}'!R{dds}+'{CF}'!D{dds}-'{CF}'!F{dds}-'{CF}'!G{dds}-'{CF}'!H{dds}-'{CF}'!I{dds}-'{CF}'!J{dds}-'{CF}'!K{dds}-'{CF}'!L{dds}-'{CF}'!M{dds}-'{CF}'!N{dds}+'{CF}'!O{dds}"
                    self._put(row, 35, f"={cash_before}-'{CF}'!T{dds}+'{CF}'!P{dds}+AG{row}-AH{row}", border=BOX, fmt=MONEY, align="right")
                    span = min(12, index + 1)
                    self._put(row, 40, f"=IF($B$13<=0,AI{row},IF(SUM(B{row - span + 1}:B{row})<=0,'{CF}'!P{dds}+({cash_before}-'{CF}'!T{dds})-AH{row},SUM(B{row - span + 1}:B{row})*12/{span}/$B$13+'{CF}'!P{dds}+({cash_before}-'{CF}'!T{dds})-AH{row}))", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 41, f"=IF($I$17=2,AN{row},AI{row})", border=BOX, fmt=MONEY, align="right")
                    self._put(row, 42, f"=MAX(0,$B$17*AO{row})", border=BOX, fmt=MONEY, align="right")
                    if index == 0:
                        for column in range(36, 40):
                            self._put(row, column, 0, border=BOX, fmt="0", align="center")
                    else:
                        self._put(row, 36, f'=IF(AND(COUNTIF($P${origin}:P{row - 1},1)>=1,C{row}<0),1,0)', border=BOX, fmt="0", align="center")
                        self._put(row, 37, f'=IF(AND(COUNTIF($R${origin}:R{row - 1},1)>=1,K{row}<0),1,0)', border=BOX, fmt="0", align="center")
                        self._put(row, 38, f'=IF(AND(COUNTIF($Q${origin}:Q{row - 1},1)>=1,F{row}<0),1,0)', border=BOX, fmt="0", align="center")
                        self._put(row, 39, f'=IF(AND(COUNTIF($S${origin}:S{row - 1},1)>=1,N{row}<0),1,0)', border=BOX, fmt="0", align="center")
                    shield = (
                        f"IF(IFERROR(INDEX('{PNL}'!$B${self.pnl['tax']}:{tax_last}${self.pnl['tax']},"
                        f"MATCH(YEAR(A{row}),'{PNL}'!$B$4:{tax_last}$4,0)),0)>0,$B$18,0)"
                    )
                    self._put(
                        row,
                        31,
                        f"=ABS(B{row}-('{CF}'!D{dds}-'{CF}'!F{dds}-'{CF}'!G{dds}-'{CF}'!H{dds}-'{CF}'!I{dds}-'{CF}'!J{dds}-'{CF}'!L{dds}-'{CF}'!M{dds}-'{CF}'!K{dds}*({shield})+IF(AND($I$12=1,A{row}=A${forecast_last}),AG{row},0)))",
                        border=BOX,
                        fmt=MONEY,
                        align="right",
                    )
                short_dates = f"A{origin}:A{forecast_last}"
                short_project = f"B{origin}:B{forecast_last}"
                short_investor = f"J{origin}:J{forecast_last}"
                self._put(5, 4, '=IF($I$13=2,"Упрощённый сценарий, не полный прогноз","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(5, 5, '=IF($I$13=2,"Упрощённый сценарий, не полный прогноз","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(5, 2, f"=IF($I$13=2,SUM(E{origin}:E{last}),SUM(E{origin}:E{forecast_last}))", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(
                    5,
                    3,
                    f'=IF(({investor_total})<1,"Не применяется",IF($I$13=2,SUM(M{origin}:M{last}),SUM(M{origin}:M{forecast_last})))',
                    border=BOX,
                    fmt=MONEY,
                    align="right",
                    fill=GREEN,
                    font=bold,
                )
                self._put(6, 2, f"=IF($I$13=2,{annual_irr(project_flow, dates)},{annual_irr(short_project, short_dates)})", border=BOX, fmt=PCT_FINE, align="center", fill=GREEN, font=bold)
                self._put(
                    6,
                    3,
                    f'=IF(({investor_total})<1,"Не применяется",IF($I$13=2,{annual_irr(investor_flow, dates)},{annual_irr(short_investor, short_dates)}))',
                    border=BOX,
                    fmt=PCT_FINE,
                    align="center",
                    fill=GREEN,
                    font=bold,
                )
                self._put(6, 4, f'=IF(SUM(AB{origin}:AB{last})>1,"Неоднозначен: несколько смен знака","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(6, 5, f'=IF(SUM(AD{origin}:AD{last})>1,"Неоднозначен: несколько смен знака","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(7, 2, f'=IF($I$13=2,IF(-SUM(U{origin}:U{last})<1,"нет вложений",SUM(T{origin}:T{last})/-SUM(U{origin}:U{last})),IF(-SUM(U{origin}:U{forecast_last})<1,"нет вложений",SUM(T{origin}:T{forecast_last})/-SUM(U{origin}:U{forecast_last})))', border=BOX, fmt="0.00", align="center")
                self._put(
                    7,
                    3,
                    f'=IF(({investor_total})<1,"Не применяется",IF($I$13=2,IF(-SUM(W{origin}:W{last})<1,"нет вложений",SUM(V{origin}:V{last})/-SUM(W{origin}:W{last})),IF(-SUM(W{origin}:W{forecast_last})<1,"нет вложений",SUM(V{origin}:V{forecast_last})/-SUM(W{origin}:W{forecast_last}))))',
                    border=BOX,
                    fmt="0.00",
                    align="center",
                )
                self._put(7, 4, f'=IF(SUM(AB{origin}:AB{last})>1,"Неоднозначен: несколько смен знака","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(7, 5, f'=IF(SUM(AD{origin}:AD{last})>1,"Неоднозначен: несколько смен знака","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(8, 2, payback("P", "C", "B"), border=BOX, fmt="0.00", align="center")
                self._put(8, 3, f'=IF(({investor_total})<1,"Не применяется",{payback("R", "K", "J")[1:]})', border=BOX, fmt="0.00", align="center")
                self._put(8, 4, f'=IF(OR(AND(NOT(ISNUMBER(B8)),SUMPRODUCT((C{origin + 1}:C{last}>=0)*(C{origin}:C{last - 1}<0))+((C{origin}>=0)*(B{origin}>0))>0),SUMPRODUCT((C{origin + 1}:C{last}>=0)*(C{origin}:C{last - 1}<0))+((C{origin}>=0)*(B{origin}>0))>1),"Был временный плюс","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(8, 5, f'=IF(OR(AND(NOT(ISNUMBER(C8)),SUMPRODUCT((K{origin + 1}:K{last}>=0)*(K{origin}:K{last - 1}<0))+((K{origin}>=0)*(J{origin}>0))>0),SUMPRODUCT((K{origin + 1}:K{last}>=0)*(K{origin}:K{last - 1}<0))+((K{origin}>=0)*(J{origin}>0))>1),"Был временный плюс","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(9, 2, payback("Q", "F", "E"), border=BOX, fmt="0.00", align="center")
                self._put(9, 3, f'=IF(({investor_total})<1,"Не применяется",{payback("S", "N", "M")[1:]})', border=BOX, fmt="0.00", align="center")
                self._put(9, 4, f'=IF(OR(AND(NOT(ISNUMBER(B9)),SUMPRODUCT((F{origin + 1}:F{last}>=0)*(F{origin}:F{last - 1}<0))+((F{origin}>=0)*(E{origin}>0))>0),SUMPRODUCT((F{origin + 1}:F{last}>=0)*(F{origin}:F{last - 1}<0))+((F{origin}>=0)*(E{origin}>0))>1),"Был временный плюс","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))
                self._put(9, 5, f'=IF(OR(AND(NOT(ISNUMBER(C9)),SUMPRODUCT((N{origin + 1}:N{last}>=0)*(N{origin}:N{last - 1}<0))+((N{origin}>=0)*(M{origin}>0))>0),SUMPRODUCT((N{origin + 1}:N{last}>=0)*(N{origin}:N{last - 1}<0))+((N{origin}>=0)*(M{origin}>0))>1),"Был временный плюс","")', border=BOX, align="center", wrap=True, font=Font(name=FONT_NAME, size=8, color="9C5700"))

                def annual_mirr(flow: str, rate_cell: str) -> str:
                    monthly = f"((1+{rate_cell})^(1/12)-1)"
                    return (
                        f'IF(OR(MAX({flow})<=0,MIN({flow})>=0),"нет смены знака",'
                        f'IFERROR((1+MIRR({flow},{monthly},{monthly}))^12-1,"не определяется"))'
                    )

                self._put(12, 1, "MIRR, годовая по месяцам", border=BOX, wrap=True)
                self._put(12, 2, f"=IF($I$13=2,{annual_mirr(project_flow, '$B$13')},{annual_mirr(short_project, '$B$13')})", border=BOX, fmt=PCT_FINE, align="center", fill=GREEN, font=bold)
                self._put(
                    12,
                    3,
                    f'=IF(({investor_total})<1,"Не применяется",IF($I$13=2,{annual_mirr(investor_flow, "$B$14")},{annual_mirr(short_investor, "$B$14")}))',
                    border=BOX,
                    fmt=PCT_FINE,
                    align="center",
                    fill=GREEN,
                    font=bold,
                )
                ws.row_dimensions[12].height = 30
                # DSCR по календарным годам (не по месяцам): летние нули продаж не роняют минимум в −2.
                year_numbers = sorted({month.year for month in _iter_months(self.form)})
                yearly_parts = []
                for year in year_numbers:
                    debt_year = f"SUMPRODUCT((YEAR(A{origin}:A{end})={year})*(Y{origin}:Y{end}))"
                    cfads_year = f"SUMPRODUCT((YEAR(A{origin}:A{end})={year})*(X{origin}:X{end}))"
                    yearly_parts.append(f"IF(({debt_year})<1,1E+99,({cfads_year})/({debt_year}))")
                yearly_min = ",".join(yearly_parts) if yearly_parts else "1E+99"
                self._put(10, 1, "Минимальный DSCR (по годам)", border=BOX, wrap=True)
                self._put(
                    10,
                    2,
                    f'=IF(MIN({yearly_min})>=1E+98,"Не применяется",MIN({yearly_min}))',
                    border=BOX,
                    fmt="0.00",
                    align="center",
                    fill=GREEN,
                    font=bold,
                )
                self._put(10, 3, "CFADS / проценты и тело за год; минимум из годовых", border=BOX, align="left", wrap=True)
                ws.row_dimensions[10].height = 36
                self._put(10, 4, "Средний DSCR (по годам)", border=BOX, wrap=True)
                if year_numbers:
                    sum_expr = "+".join(
                        f"IF((SUMPRODUCT((YEAR(A{origin}:A{end})={year})*(Y{origin}:Y{end})))<1,0,"
                        f"SUMPRODUCT((YEAR(A{origin}:A{end})={year})*(X{origin}:X{end}))/"
                        f"SUMPRODUCT((YEAR(A{origin}:A{end})={year})*(Y{origin}:Y{end})))"
                        for year in year_numbers
                    )
                    cnt_expr = "+".join(
                        f"IF((SUMPRODUCT((YEAR(A{origin}:A{end})={year})*(Y{origin}:Y{end})))<1,0,1)"
                        for year in year_numbers
                    )
                    avg_formula = f'=IF(({cnt_expr})<1,"Не применяется",({sum_expr})/({cnt_expr}))'
                else:
                    avg_formula = '="Не применяется"'
                self._put(
                    10,
                    5,
                    avg_formula,
                    border=BOX,
                    fmt="0.00",
                    align="center",
                    fill=GREEN,
                    font=bold,
                )
                self._put(19, 1, "Вложения инвестора", border=BOX)
                self._put(
                    19,
                    2,
                    f'=IF(({investor_total})<1,"Не применяется",-SUM(G{origin}:G{end}))',
                    border=BOX,
                    align="right",
                    fill=PURPLE,
                    # без MONEY: иначе Calc не показывает текстовый «Не применяется»
                )
                self._put(20, 1, "Дивиденды выплачены", border=BOX)
                self._put(
                    20,
                    2,
                    f'=IF(({investor_total})<1,"Не применяется",SUM(H{origin}:H{end}))',
                    border=BOX,
                    align="right",
                    fill=PURPLE,
                )
                self._put(21, 1, "Дивиденды не выплачены", border=BOX)
                self._put(
                    21,
                    2,
                    f'=IF(({investor_total})<1,"Не применяется",SUM(\'{CF}\'!V{first_dds}:V{last_dds}))',
                    border=BOX,
                    align="right",
                    fill=PINK,
                )
                plan_end = f"'{SHEET}'!{cells['plan_end']}"
                exit_date = f"EDATE({plan_start},$B$16*12)"
                self._put(19, 6, "Чистые активы на конец прогноза", border=BOX, wrap=True)
                self._put(19, 7, f"=AI{end}", border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(20, 6, "Доля на конец прогноза", border=BOX, wrap=True)
                self._put(20, 7, f"=MAX(0,$B$17*G19)", border=BOX, fmt=MONEY, align="right", fill=PURPLE)
                self._put(21, 6, "В потоке на дату выхода", border=BOX, wrap=True)
                self._put(21, 7, f"=SUM(I{origin}:I{last})", border=BOX, fmt=MONEY, align="right", fill=GREEN, font=bold)
                self._put(22, 1, "Выход инвестора", border=BOX)
                self._put(
                    22,
                    2,
                    f'=IF(OR($B$16<=0,({investor_total})<1),"Не применяется",IF($I$15=3,"Выход не моделируется: в потоке только взнос и дивиденды.",IF(AND($I$13=1,{exit_date}>{plan_end}),"Режим «до конца прогноза». Дата выхода позже плана, продажа в NPV и IRR не входит. Справа — стоимость доли на последнюю дату модели. Режим 2 в ячейке I13 считает до даты выхода.","В поток входит выбранная оценка на дату выхода. Это упрощённый сценарий, если дата позже плана. Продажа из кассы не платится. Выкуп после плана ограничен сценарной кассой.")))',
                    border=BOX,
                    align="left",
                    wrap=True,
                )
                ws.row_dimensions[22].height = 48
                ws.merge_cells(start_row=22, start_column=2, end_row=22, end_column=5)
                for line in (19, 20, 21):
                    ws.row_dimensions[line].height = 30
                self._scenarios(origin, forecast_last, first_dds)
                drawn = f"'{FIN}'!D{self.fin_total_row}"
                paid_principal = "+".join(f"'{CF}'!{get_column_letter(2 + offset)}{cf['principal']}" for offset in range(self.years))
                register = f"'{CAPEX}'!M{self.capex_total_row}" if self.capex_items else "0"
                capex_paid = "+".join(f"'{CF}'!M{first_dds + index}" for index in range(count))
                audit = last + 2
                self._put(audit, 1, "Проверки", font=SECTION)
                audit_rows = (
                    (1, "FCFF без финансирования", f'=IF(MAX(AE{origin}:AE{end})<1,"OK","Ошибка")', "Отклонение FCFF от формулы без взносов, кредита, гранта, тела и дивидендов"),
                    (2, "CAPEX в потоке = реестр", f'=IF(ABS(({capex_paid})-({register}))<1,"OK","Ошибка")', "Помесячный CAPEX против колонки «К оплате»"),
                    (3, "Взнос инвестора со знаком минус", f'=IF(ABS(-SUM(G{origin}:G{end})-({investor_total}))<1,"OK","Ошибка")', "Сумма месячных взносов равна колонке инвестора"),
                    (4, "Дивиденды анализа = ДДС", f'=IF(AND(ABS(SUM(H{origin}:H{end})-SUM(\'{CF}\'!T{first_dds}:T{last_dds}))<1,SUM(\'{CF}\'!V{first_dds}:V{last_dds})>=-1),"OK","Ошибка")', "В IRR только выплаченные дивиденды. Невыплаченное кассу не уменьшает"),
                    (5, "Выход по выбранному правилу", (
                        f'=IF(OR($B$16<=0,$I$15=3),IF(ABS(SUM(I{origin}:I{last}))<1,"OK","Ошибка"),IF($I$15=2,IF(AND(ABS(SUM(I{origin}:I{forecast_last})-SUM(\'{CF}\'!W{first_dds}:W{last_dds}))<1,'
                        + (f'ABS(SUM(I{forecast_last + 1}:I{last})-IF({exit_date}>$A${forecast_last},MIN(SUMIF(A{forecast_last + 1}:A{last},{exit_date},AP{forecast_last + 1}:AP{last}),MAX(0,SUMIF(A{forecast_last + 1}:A{last},{exit_date},AR{forecast_last + 1}:AR{last})-SUMIF(A{forecast_last + 1}:A{last},{exit_date},H{forecast_last + 1}:H{last}))),0))<1' if extra else "TRUE")
                        + f'),"OK","Ошибка"),IF(AND($I$13=1,{exit_date}>{plan_end}),IF(ABS(SUM(I{origin}:I{last}))<1,"OK","Ошибка"),IF(ABS(SUM(I{origin}:I{last})-SUMIF(A{origin}:A{last},{exit_date},AP{origin}:AP{last}))<1,"OK","Ошибка"))))'
                    ), "Продажа равна оценке и из кассы не платится. Выкуп внутри плана равен ДДС. Выкуп после плана ограничен сценарной кассой"),
                    (6, "Остаток долга после прогноза", f'=IF({drawn}<1,"OK",IF(({paid_principal})>({drawn})+1,"Ошибка",IF(({drawn})-({paid_principal})>1,"Предупреждение","OK")))', "Непогашенный остаток не обнуляется на границе прогноза"),
                    (7, "Даты по порядку", f'=IF(SUM(AF{origin}:AF{last})=0,"OK","Ошибка")', "Каждая следующая дата позже предыдущей"),
                    (8, "NPV по датам потока", f'=IF(AND(ABS(D{origin}-1)<0.0001,ABS(D{origin + 1}-1/(1+$B$13)^((A{origin + 1}-A{origin})/365))<0.0001),"OK","Ошибка")', "Первый месяц без дисконта, дальше дни от первой даты / 365"),
                    (9, "Профиль потока проекта", f'=IF(SUM(AB{origin}:AB{last})>1,"Предупреждение","OK")', "Больше одной смены знака делает IRR и PI неоднозначными. Текст стоит рядом с IRR"),
                    (10, "Профиль потока инвестора", f'=IF(SUM(AD{origin}:AD{last})>1,"Предупреждение","OK")', "Больше одной смены знака делает показатели инвестора неоднозначными"),
                    (11, "Полнота оценки инвестора", f'=IF($B$16<=0,"OK",IF({exit_date}<={plan_end},"OK",IF(AND($I$13=2,A{last}>={exit_date}),"OK","Предупреждение")))', "Режим 1 не включает выход после плана. Режим 2 включает его, если продление дошло до даты"),
                )
                for offset, label, status, detail in audit_rows:
                    line = audit + 1 + offset
                    self._put(line, 1, label, border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
                    self._put(line, 2, status, border=GREY_BOX, align="center", font=Font(name=FONT_NAME, size=8, bold=True))
                    self._put(line, 3, detail, border=GREY_BOX, wrap=True, font=Font(name=FONT_NAME, size=8))
                    ws.merge_cells(start_row=line, start_column=3, end_row=line, end_column=6)
                    ws.row_dimensions[line].height = 28
            else:
                for row in range(5, 10):
                    self._put(row, 2, "нет потока", border=BOX, align="center")
                    self._put(row, 3, "нет потока", border=BOX, align="center")
        finally:
            self.ws = previous


def _mzp_schedule() -> dict[int, float]:
    """МЗП: сначала справочник админки (БД), иначе FEM_MZP из .env / дефолт."""
    from app.fem_rates import load_mzp_schedule

    return load_mzp_schedule() or {2024: 85000.0}


def _minimum_wage(year: int) -> float:
    """МЗП этого года из параметра генератора. Если года нет в списке — последняя известная сумма не позже него."""
    schedule = _mzp_schedule()
    known = [item for item in schedule if item <= year]
    if known:
        return schedule[max(known)]
    return schedule[min(schedule)]


def _month_income_ref(services: list, links: dict, source: int) -> str:
    """Выручка одного месяца: выбранные товары или вся выручка листа «Месяцы»."""
    by_name = {item["name"]: item["rev_col"] for item in links.get("products") or []}
    chosen = [by_name[str(name).strip()] for name in services if str(name).strip() in by_name]
    columns = chosen or [item["rev_col"] for item in links.get("products") or []]
    if not chosen and links.get("other"):
        columns.append(links["other"]["col"])
    if not columns:
        return "0"
    return "+".join(f"'{MONTHS}'!{get_column_letter(column)}{source}" for column in columns)


def build_fem_v5_excel(user: User, form: dict) -> bytes:
    return PremisesBook(user, form).build()
