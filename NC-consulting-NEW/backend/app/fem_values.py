"""Читает уже посчитанные годовые отчёты и показатели из файла финансовой модели.

Формулы модели не переписываются: файл считается тем же Calc, которым открывают скачанный Excel.
"""

from __future__ import annotations

import hashlib
import json
from io import BytesIO

from openpyxl import load_workbook

from app.fem_v5_book import build_fem_v5_excel
from app.xlsx_calc import XlsxCalcError, recalculate_xlsx

PNL = "ОПиУ"
CASH = "ДДС"
EFFECT = "Анализ эффект."
_CACHE: dict[str, dict] = {}

PNL_LINES = (
    (6, "Выручка"),
    (8, "Себестоимость"),
    (11, "Валовая прибыль"),
    (18, "Прибыль до налога"),
    (22, "Налог"),
    (23, "Чистая прибыль"),
    (25, "EBITDA"),
)
CASH_LINES = (
    (15, "Опер. поток"),
    (20, "Инвестиции"),
    (28, "Финансирование"),
    (31, "Деньги на конец"),
)


def read_statements(form: dict) -> dict | None:
    key = hashlib.sha256(
        json.dumps(form or {}, ensure_ascii=False, sort_keys=True, default=str).encode()
    ).hexdigest()
    cached = _CACHE.get(key)
    if cached is not None:
        return cached
    try:
        snapshot = _calculate(form or {})
    except Exception:
        return None
    if snapshot:
        _CACHE[key] = snapshot
    return snapshot


def _calculate(form: dict) -> dict | None:
    payload = build_fem_v5_excel(None, form)
    try:
        calculated = recalculate_xlsx(payload)
    except (XlsxCalcError, OSError):
        return None
    workbook = load_workbook(BytesIO(calculated), data_only=True)
    if PNL not in workbook.sheetnames or CASH not in workbook.sheetnames or EFFECT not in workbook.sheetnames:
        return None
    pnl = workbook[PNL]
    cash = workbook[CASH]
    effect = workbook[EFFECT]
    years: list[int] = []
    column = 2
    while isinstance(pnl.cell(4, column).value, (int, float)):
        years.append(int(pnl.cell(4, column).value))
        column += 1
    if not years:
        return None

    def lines(sheet, spec) -> list[dict]:
        rows = []
        for row, label in spec:
            values = []
            for offset in range(len(years)):
                raw = sheet.cell(row, 2 + offset).value
                values.append(float(raw) if isinstance(raw, (int, float)) else 0.0)
            rows.append({"label": label, "values": values})
        return rows

    snapshot = {
        "years": years,
        "pnl": lines(pnl, PNL_LINES),
        "cash": lines(cash, CASH_LINES),
        "npv": _number(effect["B5"].value),
        "irr": effect["B6"].value,
        "pi": _number(effect["B7"].value),
        "payback": effect["B8"].value,
        "discounted": effect["B9"].value,
        "dscr": effect["B10"].value,
        "deficit": _number(effect["B11"].value),
        "mirr": effect["B12"].value,
        "wacc": effect["B13"].value,
        "investor": {
            "npv": effect["C5"].value,
            "irr": effect["C6"].value,
            "pi": effect["C7"].value,
            "payback": effect["C8"].value,
            "discounted": effect["C9"].value,
            "rate": effect["B14"].value,
            "share": effect["B15"].value,
            "term": effect["B16"].value,
            "paid_in": effect["B19"].value,
            "dividends": effect["B20"].value,
            "unpaid": effect["B21"].value,
            "exit": effect["B22"].value,
        },
    }
    readers = (
        ("pnl_rows", lambda: _statement(pnl, years, 6, 25)),
        ("cash_rows", lambda: _statement(cash, years, 5, 33)),
        ("income", lambda: _income(workbook, years)),
        ("admin", lambda: _admin(workbook, years)),
        ("payroll", lambda: _payroll(workbook, years)),
        ("schedules", lambda: _schedules(workbook)),
        ("capex", lambda: _capex(workbook)),
        ("funding", lambda: _funding(workbook)),
    )
    for key, read in readers:
        try:
            snapshot[key] = read()
        except Exception:
            snapshot[key] = None
    return snapshot


def _number(value):
    if isinstance(value, (int, float)):
        return float(value)
    return None


def _text(value) -> str:
    return str(value).strip() if value is not None else ""


def _ym_text(value) -> str:
    if hasattr(value, "year") and hasattr(value, "month"):
        return f"{value.year:04d}-{value.month:02d}"
    return _text(value)[:7]


def _year_columns(sheet, row: int, years: list[int], first: int) -> dict[int, int]:
    """Колонка каждого года по строке заголовка: «2027», «2027 год» или число."""
    found: dict[int, int] = {}
    for column in range(first, sheet.max_column + 1):
        raw = sheet.cell(row, column).value
        text = _text(raw).split(" ")[0]
        if isinstance(raw, (int, float)):
            text = str(int(raw))
        if text.isdigit() and int(text) in years and int(text) not in found:
            found[int(text)] = column
    return found


def _values(sheet, row: int, columns: dict[int, int], years: list[int]) -> list[float]:
    return [_number(sheet.cell(row, columns[year]).value) or 0.0 if year in columns else 0.0 for year in years]


def _statement(sheet, years: list[int], first: int, last: int) -> list[dict]:
    """Все строки отчёта по годам. Строка без чисел — заголовок блока."""
    rows = []
    for row in range(first, last + 1):
        label = _text(sheet.cell(row, 1).value)
        if not label:
            continue
        raw = [sheet.cell(row, 2 + offset).value for offset in range(len(years))]
        if all(item is None for item in raw):
            rows.append({"label": label, "values": None})
            continue
        rows.append({"label": label, "values": [_number(item) or 0.0 for item in raw]})
    return rows


def _income(workbook, years: list[int]) -> dict | None:
    if "План доходов" not in workbook.sheetnames:
        return None
    sheet = workbook["План доходов"]
    columns = _year_columns(sheet, 1, years, 3)
    products = []
    months = None
    load = None
    current = None
    for row in range(3, sheet.max_row + 1):
        label = _text(sheet.cell(row, 1).value)
        unit = _text(sheet.cell(row, 2).value)
        if not label:
            continue
        if label.startswith("Месяцев продаж в году"):
            months = _values(sheet, row, columns, years)
        elif label.startswith("Средняя загрузка"):
            load = _values(sheet, row, columns, years)
        elif label.startswith("Итого выручка"):
            return {"products": products, "months": months, "load": load, "total": _values(sheet, row, columns, years)}
        elif row > 11 and not unit and all(sheet.cell(row, column).value is None for column in columns.values()):
            current = {"name": label, "unit": "", "count": [], "price": [], "revenue": []}
            products.append(current)
        elif current is not None and label.startswith("Количество за год"):
            current["unit"] = unit
            current["count"] = _values(sheet, row, columns, years)
        elif current is not None and label.startswith("Цена"):
            current["price"] = _values(sheet, row, columns, years)
        elif current is not None and label == "Выручка":
            current["revenue"] = _values(sheet, row, columns, years)
    return {"products": products, "months": months, "load": load, "total": None}


def _admin(workbook, years: list[int]) -> list[dict] | None:
    if "Адм расходы" not in workbook.sheetnames:
        return None
    sheet = workbook["Адм расходы"]
    columns = _year_columns(sheet, 2, years, 3)
    rows = []
    group = ""
    for row in range(3, sheet.max_row + 1):
        label = _text(sheet.cell(row, 1).value)
        if not label or label.startswith("в т.ч."):
            continue
        if not _text(sheet.cell(row, 2).value):
            group = label
            continue
        rows.append({"label": label, "group": group, "values": _values(sheet, row, columns, years), "total": label.upper() == "ИТОГО"})
    return rows


def _payroll(workbook, years: list[int]) -> dict | None:
    if "ФОТ" not in workbook.sheetnames:
        return None
    sheet = workbook["ФОТ"]
    columns = _year_columns(sheet, 2, years, 4)
    people = []
    totals = []
    group = ""
    for row in range(4, 32):
        label = _text(sheet.cell(row, 1).value)
        if not label:
            continue
        salary = sheet.cell(row, 3).value
        if isinstance(salary, (int, float)):
            people.append(
                {
                    "name": label,
                    "group": group,
                    "salary": float(salary),
                    "count": [_number(sheet.cell(row, columns[year]).value) or 0.0 if year in columns else 0.0 for year in years],
                    "fund": [_number(sheet.cell(row, columns[year] + 1).value) or 0.0 if year in columns else 0.0 for year in years],
                }
            )
            continue
        funds = [sheet.cell(row, columns[year] + 1).value if year in columns else None for year in years]
        if all(item is None for item in funds):
            if row < 12:
                group = label
            continue
        if label in ("ФОТ", "Социальные отчисления", "ОПВР", "ООСМС", "Социальный налог", "ФОТ с отчислениями работодателя"):
            totals.append({"label": label, "values": [_number(item) or 0.0 for item in funds]})
    return {"people": people, "totals": totals}


def _schedules(workbook) -> list[dict] | None:
    if "График платежей" not in workbook.sheetnames:
        return None
    sheet = workbook["График платежей"]
    blocks = []
    current = None
    for row in range(1, sheet.max_row + 1):
        first = _text(sheet.cell(row, 1).value)
        second = sheet.cell(row, 2).value
        if first == "№" and _text(second) == "Дата":
            title = _text(sheet.cell(row - 1, 1).value)
            current = {"title": title, "rows": [], "total": None, "terms": {}}
            blocks.append(current)
            continue
        if current is None:
            continue
        term = _text(sheet.cell(row, 10).value)
        if term:
            value = sheet.cell(row, 12).value
            current["terms"][term] = _ym_text(value) if hasattr(value, "year") else value
        if first == "Итого":
            current["total"] = [_number(sheet.cell(row, column).value) or 0.0 for column in range(3, 9)]
            current = None
            continue
        if first.isdigit() or isinstance(sheet.cell(row, 1).value, (int, float)):
            current["rows"].append(
                {
                    "n": int(float(sheet.cell(row, 1).value)),
                    "date": _ym_text(second),
                    "payment": _number(sheet.cell(row, 3).value) or 0.0,
                    "principal": _number(sheet.cell(row, 4).value) or 0.0,
                    "interest": _number(sheet.cell(row, 5).value) or 0.0,
                    "balance": _number(sheet.cell(row, 6).value) or 0.0,
                    "bank_interest": _number(sheet.cell(row, 7).value) or 0.0,
                    "subsidy": _number(sheet.cell(row, 8).value) or 0.0,
                }
            )
        elif first == "Выдача":
            current["issued"] = _ym_text(second)
            current["amount"] = _number(sheet.cell(row, 6).value) or 0.0
    return [block for block in blocks if block["rows"]]


def _capex(workbook) -> list[dict] | None:
    if "Реестр CAPEX" not in workbook.sheetnames:
        return None
    sheet = workbook["Реестр CAPEX"]
    rows = []
    for row in range(3, sheet.max_row + 1):
        name = _text(sheet.cell(row, 1).value)
        if not name or name == "Итого":
            break
        rows.append(
            {
                "name": name,
                "category": _text(sheet.cell(row, 2).value),
                "amount": _number(sheet.cell(row, 8).value) or _number(sheet.cell(row, 3).value) or 0.0,
                "life": _number(sheet.cell(row, 5).value),
                "paid": _ym_text(sheet.cell(row, 9).value),
                "started": _ym_text(sheet.cell(row, 10).value),
            }
        )
    return rows


def _funding(workbook) -> list[dict] | None:
    if "Структура финансирования" not in workbook.sheetnames:
        return None
    sheet = workbook["Структура финансирования"]
    rows = []
    for row in range(4, sheet.max_row + 1):
        name = _text(sheet.cell(row, 2).value)
        if not name:
            continue
        values = [_number(sheet.cell(row, column).value) or 0.0 for column in range(3, 8)]
        if name.startswith("ВСЕГО"):
            rows.append({"name": "Итого", "total": values[0], "credit": values[1], "investor": values[2], "own": values[3], "grant": values[4], "sum": True})
            break
        if not values[0]:
            continue
        rows.append({"name": name, "total": values[0], "credit": values[1], "investor": values[2], "own": values[3], "grant": values[4], "sum": False})
    return rows
