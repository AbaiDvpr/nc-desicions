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

    return {
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
    }


def _number(value):
    if isinstance(value, (int, float)):
        return float(value)
    return None
