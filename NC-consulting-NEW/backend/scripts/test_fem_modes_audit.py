#!/usr/bin/env python3
"""Независимый аудит двух режимов оценки ФЭМ v5: новые инвестиции и весь действующий бизнес.

Ожидаемые значения считаются в Python только из входов книги (помесячные колонки ДДС, реестр CAPEX,
суммы «Структуры финансирования», ставки «Предпосылок», налог ОПиУ по годам) и анкеты.
Формулы «Анализа эффект.» не используются. Анкеты — копии: база не меняется.

Запуск из backend: PYTHONPATH=. .venv/bin/python scripts/test_fem_modes_audit.py [путь_отчёта_без_расширения]
"""

from __future__ import annotations

import copy
import json
import sys
from dataclasses import asdict, dataclass, field
from datetime import date, datetime
from io import BytesIO
from pathlib import Path

from openpyxl import load_workbook
from openpyxl.utils import get_column_letter

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from app.database import SessionLocal  # noqa: E402
from app.fem_rates import load_rates  # noqa: E402
from app.fem_v5_book import CAPEX, CF, EFFECT, FIN, NO_INFLOW, NO_OUTFLOW, PAY, PNL, SHEET, PremisesBook  # noqa: E402
from app.models import FinModel  # noqa: E402
from app.project_form import ProjectFormIn  # noqa: E402
from app.xlsx_calc import recalculate_xlsx  # noqa: E402

BAD = ("Ошибка", "расхождение", "не сходится", "повторная оплата", "дубль")
NOT_PAID_BACK = "Не окупается в периоде прогноза"


@dataclass
class Check:
    scenario: str
    name: str
    cell: str
    expected: object
    actual: object
    deviation: float | None
    status: str
    note: str = ""


@dataclass
class Scenario:
    title: str
    form: dict
    mode2: bool = False
    stored: bool = False
    checks: list = field(default_factory=list)


def _num(value) -> float:
    return float(value) if isinstance(value, (int, float)) else 0.0


def _day(value) -> date:
    return value.date() if isinstance(value, datetime) else value


def add_months(day: date, months: int) -> date:
    month = day.month - 1 + months
    return date(day.year + month // 12, month % 12 + 1, 1)


def npv(flows: list[float], dates: list[date], rate: float) -> float:
    if rate <= 0:
        return sum(flows)
    return sum(f / (1 + rate) ** ((d - dates[0]).days / 365) for f, d in zip(flows, dates))


def sign_changes(flows: list[float]) -> int:
    signs = [1 if f > 0 else -1 for f in flows if abs(f) > 1e-9]
    return sum(1 for a, b in zip(signs, signs[1:]) if a != b)


def single_cumulative_change(flows: list[float]) -> bool:
    cum, total = [], 0.0
    for value in flows:
        total += value
        cum.append(total)
    ups = sum(1 for a, b in zip(cum, cum[1:]) if a < 0 <= b)
    downs = sum(1 for a, b in zip(cum, cum[1:]) if a >= 0 > b)
    return cum[0] < 0 and ups == 1 and downs == 0


def sign_note(flows: list[float], irr_cell: bool = True) -> str:
    if sign_changes(flows) <= 1:
        return ""
    if single_cumulative_change(flows):
        return "Корень единственный: накопленный поток меняет знак один раз" if irr_cell else ""
    return "Неоднозначен: несколько смен знака"


def positive_roots(flows: list[float], dates: list[date]) -> int:
    """Перебор NPV(r) на сетке положительных ставок 0,0001%…10 000 000%: число смен знака = число корней."""
    grid = [10 ** (-6 + 13 * i / 4000) for i in range(4001)]
    values = [npv(flows, dates, rate) for rate in grid]
    signs = [1 if v > 0 else -1 for v in values if abs(v) > 1e-6]
    return sum(1 for a, b in zip(signs, signs[1:]) if a != b)


def xirr(flows: list[float], dates: list[date]) -> float | None:
    if min(flows) >= 0 or max(flows) <= 0:
        return None
    low, high = -0.99, 1.0
    while npv(flows, dates, high) > 0 and high < 1e6:
        high *= 2
    f_low = sum(f / (1 + low) ** ((d - dates[0]).days / 365) for f, d in zip(flows, dates))
    f_high = sum(f / (1 + high) ** ((d - dates[0]).days / 365) for f, d in zip(flows, dates))
    if f_low * f_high > 0:
        return None
    for _ in range(300):
        mid = (low + high) / 2
        value = sum(f / (1 + mid) ** ((d - dates[0]).days / 365) for f, d in zip(flows, dates))
        if (value > 0) == (f_low > 0):
            low, f_low = mid, value
        else:
            high = mid
    return (low + high) / 2


def irr_text(irr: float | None, flows: list[float]):
    if irr is not None:
        return irr
    if min(flows) >= 0:
        return NO_OUTFLOW
    if max(flows) <= 0:
        return NO_INFLOW
    return "не определяется"


def payback(flows: list[float], dates: list[date]):
    cum, total = [], 0.0
    for value in flows:
        total += value
        cum.append(total)
    if min(cum) >= 0:
        return NO_OUTFLOW
    for k in range(len(flows)):
        started = (cum[k] >= 0 and flows[k] > 0) if k == 0 else (cum[k] >= 0 and cum[k - 1] < 0)
        if started and min(cum[k:]) >= 0:
            month_days = (add_months(dates[k], 1) - dates[k]).days
            before = 0.0 if k == 0 else abs(cum[k - 1])
            share = 0.0 if flows[k] == 0 else before / flows[k]
            return (dates[k] - dates[0]).days / 365 + month_days / 365 * share
    return NOT_PAID_BACK


def payback_text(value, mode2: bool):
    if value == NOT_PAID_BACK and mode2:
        return "Не окупается в упрощённом продлении"
    return value


def profitability_index(flows: list[float], dates: list[date], rate: float):
    pv = [f / (1 + rate) ** ((d - dates[0]).days / 365) if rate > 0 else f for f, d in zip(flows, dates)]
    negative = -sum(v for v, f in zip(pv, flows) if f < 0)
    if negative < 1:
        return NO_OUTFLOW
    return sum(v for v, f in zip(pv, flows) if f > 0) / negative


def compare(sc: Scenario, name: str, cell: str, expected, actual, tol: float, note: str = ""):
    if isinstance(expected, str) or isinstance(actual, str) or expected is None or actual is None:
        same = expected == actual or (
            isinstance(expected, str) and isinstance(actual, str) and expected != "" and actual.startswith(expected)
        )
        deviation = None
    else:
        deviation = float(actual) - float(expected)
        same = abs(deviation) <= tol * max(1.0, abs(float(expected))) if tol < 1 else abs(deviation) <= tol
    sc.checks.append(Check(sc.title, name, cell, _round(expected), _round(actual), _round(deviation), "PASS" if same else "FAIL", note))


def _round(value):
    if isinstance(value, float):
        return round(value, 6)
    return value


def ref(sheet: str, cell: str) -> str:
    return f"'{sheet}'!{cell}"


def build(form: dict, mode2: bool = False, stored: bool = False):
    # Сохранённые модели скачиваются как есть, без повторной валидации анкеты — так же строим и здесь.
    data = copy.deepcopy(form) if stored else ProjectFormIn.model_validate(form).model_dump()
    book = PremisesBook(None, data)
    raw = book.build()
    if mode2:
        wb = load_workbook(BytesIO(raw))
        wb[EFFECT]["I13"] = 2
        stream = BytesIO()
        wb.save(stream)
        raw = stream.getvalue()
    calculated = recalculate_xlsx(raw)
    return data, book, load_workbook(BytesIO(calculated), data_only=True)


def audit(sc: Scenario):
    data, book, wb = build(sc.form, sc.mode2, sc.stored)
    eff, cf, fin, sheet, cap, pnl = wb[EFFECT], wb[CF], wb[FIN], wb[SHEET], wb[CAPEX], wb[PNL]

    errors = [
        f"{ws.title}!{c.coordinate}={c.value}"
        for ws in wb.worksheets
        for row in ws.iter_rows()
        for c in row
        if isinstance(c.value, str) and (c.value.startswith("#") or c.value in BAD)
    ]
    compare(sc, "Ошибки в ячейках книги", "все листы", 0, len(errors), 0, "; ".join(errors[:5]))

    # Входы
    total = book.fin_total_row
    debt, investor, own, grant = (_num(fin.cell(total, column).value) for column in (4, 5, 6, 7))
    r_own, r_inv, r_debt = (_num(sheet[f"C{row}"].value) for row in (17, 19, 23))
    shield_rate = _num(eff["B18"].value)
    years = [int(_num(pnl.cell(4, column).value)) for column in range(2, 2 + book.years)]
    tax = {year: _num(pnl.cell(book.pnl["tax"], 2 + i).value) for i, year in enumerate(years)}
    owned_items = [item for item in book.capex_items if item["already_owned"]]
    owned_book = sum(item["book_value"] for item in owned_items)
    agreed = data.get("owned_assets_value")
    owned_value = float(agreed) if owned_items and agreed is not None else owned_book
    first, last_cf = book.cf["month_first"], book.cf["month_last"]
    months = list(range(first, last_cf + 1))
    vat_on = _num(sheet[book._source_cells()["vat_on"].replace("$", "")].value) == 1
    vat_rate = _num(sheet[book._source_cells()["vat_rate"].replace("$", "")].value)

    def col(row: int, column: int) -> float:
        return _num(cf.cell(row, column).value)

    dates = [_day(cf.cell(row, 1).value) for row in months]
    stock_end = col(last_cf, 16)

    # НДС по кварталам: уплата во 2-м месяце после квартала, превышение входящего НДС переносится, остаток — в последнем месяце
    share = vat_rate / (1 + vat_rate) if vat_on else 0.0
    monthly_vat = [(col(r, 4) - col(r, 6) - col(r, 7) - col(r, 10) - col(r, 13)) * share for r in months]
    expected_paid = [0.0] * len(months)
    vat_credit_end, quarter_sum = 0.0, 0.0
    for i, day in enumerate(dates):
        quarter_sum += monthly_vat[i]
        if day.month in (3, 6, 9, 12) or i == len(dates) - 1:
            balance = quarter_sum - vat_credit_end
            due, vat_credit_end, quarter_sum = max(0.0, balance), max(0.0, -balance), 0.0
            expected_paid[min(i + 2, len(dates) - 1)] += due

    # FCFF без остатка: D − F − G − H − I − J − L − M − K × щит (щит только в годы с налогом)
    fcff = []
    for row, day in zip(months, dates):
        shield = shield_rate if tax.get(day.year, 0) > 0 else 0.0
        value = col(row, 4) - sum(col(row, c) for c in (6, 7, 8, 9, 10, 12, 13)) - col(row, 11) * shield
        fcff.append(value)

    # Остаток активов по реестру: прямая линия от начала амортизации, до оплаты ноль
    def nbv(day: date, owned: bool) -> float:
        value = 0.0
        for offset, item in enumerate(book.capex_items):
            row = book.capex_first + offset
            if bool(item["already_owned"]) != owned:
                continue
            cost = item["book_value"] if owned else item["price"] * _num(cap.cell(row, 7).value)
            life = _num(cap.cell(row, 5).value)
            pay, start = _day(cap.cell(row, 9).value), _day(cap.cell(row, 11).value)
            if day < pay:
                continue
            if life <= 0:
                value += cost
                continue
            elapsed = (day.year - start.year) * 12 + day.month - start.month + 1
            value += max(0.0, cost * (1 - min(1.0, elapsed / (life * 12))))
        return value

    origin = 24
    n = len(months)
    forecast_last = origin + n - 1
    row = forecast_last + 1
    while eff.cell(row, 1).value is not None:
        row += 1
    last = row - 1
    extra = last - forecast_last
    end_row = last if (sc.mode2 and extra) else forecast_last
    end_day = _day(eff.cell(end_row, 1).value)
    k = 0.0 if owned_book < 1 else owned_value / owned_book
    terminal_new = nbv(end_day, False) + stock_end + vat_credit_end
    terminal_whole = nbv(end_day, False) + k * nbv(end_day, True) + stock_end + vat_credit_end

    # WACC
    new_money = debt + investor + own
    wacc_new = r_own if new_money <= 0 else (r_own * own + r_inv * investor + r_debt * debt) / new_money
    whole_money = new_money + owned_value
    wacc_whole = r_own if whole_money <= 0 else (r_own * (own + owned_value) + r_inv * investor + r_debt * debt) / whole_money
    compare(sc, "WACC новых инвестиций", ref(EFFECT, "B13"), wacc_new, eff["B13"].value, 1e-9, "Только новые деньги без гранта")
    compare(sc, "WACC всего бизнеса", ref(EFFECT, "F13"), wacc_whole, eff["F13"].value, 1e-9, "Новые деньги + вклад имуществом по оценке")
    share = 0.0 if investor + own + owned_value == 0 else investor / (investor + own + owned_value)
    compare(sc, "Доля инвестора (та же оценка)", ref(EFFECT, "B17"), share, eff["B17"].value, 1e-9)
    compare(sc, "Оценка имеющихся активов", ref(EFFECT, "F14"), owned_value, eff["F14"].value, 1)

    # Поток новых инвестиций
    if sc.mode2 and extra:
        avg_from = max(0, n - 12)
        tail = fcff[avg_from:]
        average = sum(tail) / len(tail)
        compare(sc, "FCFF продления без остатка", ref(EFFECT, "I18"), average, eff["I18"].value, 1, "Среднее 12 мес. без разового остатка")
        ext_dates = [_day(eff.cell(r, 1).value) for r in range(forecast_last + 1, last + 1)]
        flows_new = fcff + [average] * extra
        flows_dates = dates + ext_dates
        flows_new[-1] += terminal_new
    else:
        flows_new = list(fcff)
        flows_dates = list(dates)
        flows_new[-1] += terminal_new
    actual_flow = [_num(eff.cell(r, 2).value) for r in range(origin, origin + len(flows_new))]
    worst = max(range(len(flows_new)), key=lambda i: abs(actual_flow[i] - flows_new[i]))
    compare(sc, "FCFF новых инвестиций помесячно (макс. отклонение)", ref(EFFECT, f"B{origin + worst}"), flows_new[worst], actual_flow[worst], 1)
    compare(sc, "Остаток в последнем FCFF (новые активы + запас + НДС к зачёту)", ref(EFFECT, f"AU{end_row}"), terminal_new, eff.cell(end_row, 47).value, 1,
            "Имеющиеся активы не входят")
    rate = wacc_new
    compare(sc, "NPV новых инвестиций", ref(EFFECT, "B5"), npv(flows_new, flows_dates, rate), eff["B5"].value, 1)
    irr = xirr(flows_new, flows_dates)
    expected_irr = irr_text(irr, flows_new)
    compare(sc, "IRR новых инвестиций", ref(EFFECT, "B6"), expected_irr, eff["B6"].value, 1e-4,
            "Несколько смен знака" if sign_changes(flows_new) > 1 else "")
    compare(sc, "Пояснение к IRR новых инвестиций", ref(EFFECT, "D6"), sign_note(flows_new), eff["D6"].value or "", 0,
            f"Смен знака потока: {sign_changes(flows_new)}")
    compare(sc, "Пояснение к PI новых инвестиций", ref(EFFECT, "D7"), sign_note(flows_new, irr_cell=False), eff["D7"].value or "", 0)
    if sign_changes(flows_new) > 1 and single_cumulative_change(flows_new):
        compare(sc, "Положительный корень NPV новых инвестиций один (перебор ставок)", ref(EFFECT, "B6"), 1,
                positive_roots(flows_new, flows_dates), 0, "Независимо от критерия накопленного потока")
    compare(sc, "PI новых инвестиций", ref(EFFECT, "B7"), profitability_index(flows_new, flows_dates, rate), eff["B7"].value, 1e-6)
    compare(sc, "Окупаемость новых инвестиций", ref(EFFECT, "B8"), payback_text(payback(flows_new, flows_dates), sc.mode2), eff["B8"].value, 1e-6)
    discounted = [f / (1 + rate) ** ((d - flows_dates[0]).days / 365) if rate > 0 else f for f, d in zip(flows_new, flows_dates)]
    compare(sc, "Дисконтированная окупаемость новых инвестиций", ref(EFFECT, "B9"), payback_text(payback(discounted, flows_dates), sc.mode2), eff["B9"].value, 1e-6)

    # Поток всего бизнеса
    flows_whole = list(flows_new)
    flows_whole[0] -= owned_value
    flows_whole[-1] += terminal_whole - terminal_new
    compare(sc, "Вложение бизнеса в 1-м месяце", ref(EFFECT, f"BI{origin}"), flows_whole[0], eff.cell(origin, 61).value, 1)
    compare(sc, "NPV всего бизнеса", ref(EFFECT, "F5"), npv(flows_whole, flows_dates, wacc_whole), eff["F5"].value, 1)
    irr_whole = xirr(flows_whole, flows_dates)
    expected_whole = irr_text(irr_whole, flows_whole)
    compare(sc, "IRR всего бизнеса", ref(EFFECT, "F6"), expected_whole, eff["F6"].value, 1e-4)
    compare(sc, "Пояснение к IRR всего бизнеса", ref(EFFECT, "G6"), sign_note(flows_whole), eff["G6"].value or "", 0)
    compare(sc, "PI всего бизнеса", ref(EFFECT, "F7"), profitability_index(flows_whole, flows_dates, wacc_whole), eff["F7"].value, 1e-6)
    compare(sc, "Окупаемость всего бизнеса", ref(EFFECT, "F8"), payback_text(payback(flows_whole, flows_dates), sc.mode2), eff["F8"].value, 1e-6)
    disc_whole = [f / (1 + wacc_whole) ** ((d - flows_dates[0]).days / 365) if wacc_whole > 0 else f for f, d in zip(flows_whole, flows_dates)]
    compare(sc, "Дисконтированная окупаемость всего бизнеса", ref(EFFECT, "F9"), payback_text(payback(disc_whole, flows_dates), sc.mode2), eff["F9"].value, 1e-6)
    if owned_value < 1:
        compare(sc, "Без имеющихся активов режимы совпадают", ref(EFFECT, "F5"), eff["B5"].value, eff["F5"].value, 1)

    # НДС: по анкете на любом налоговом режиме, ОПиУ без НДС
    vat_cell = book._source_cells()["vat_on"].replace("$", "")
    compare(sc, "Плательщик НДС = анкета (любой режим)", ref(SHEET, vat_cell), int(_num(data.get("nds")) == 1), int(vat_on), 0,
            f"Режим {data.get('tax_regime')}")
    if _num(data.get("other_income")) == 0:
        revenue = sum(_num(pnl.cell(book.pnl["revenue"], c).value) for c in range(2, 2 + book.years))
        form_vat = _num(data.get("vat_rate")) / 100 if _num(data.get("nds")) == 1 else 0.0
        compare(sc, "Поступления ДДС = выручка ОПиУ × (1 + НДС)", f"{ref(CF, 'D')}{first}:D{last_cf}",
                revenue * (1 + form_vat), sum(col(r, 4) for r in months), 1)

    headers = {cf.cell(first - 1, c).value: c for c in range(1, cf.max_column + 1)}
    paid_col = headers.get("НДС уплачен в месяце")
    if paid_col:
        actual_paid = [col(r, paid_col) for r in months]
        worst = max(range(len(months)), key=lambda i: abs(actual_paid[i] - expected_paid[i]))
        compare(sc, "НДС уплачен по кварталам с переносом (макс. отклонение)", ref(CF, f"{get_column_letter(paid_col)}{months[worst]}"),
                expected_paid[worst], actual_paid[worst], 1)
        compare(sc, "НДС к зачёту на конец плана", ref(CF, f"{get_column_letter(paid_col - 1)}{last_cf}"),
                vat_credit_end, col(last_cf, paid_col - 1), 1, "Переносится дальше: входит в последний FCFF вместе с запасом")

    # Запасы: оплачены один раз, остаток возвращается один раз
    supplier = sum(col(r, 6) for r in months)
    factor = 1 + (vat_rate if vat_on else 0)
    compare(sc, "Оплаты поставщикам = закупки + остаток запаса", f"{ref(CF, 'F')}{first}:F{last_cf}",
            (sum(col(r, 5) for r in months) + stock_end) * factor, supplier, 1)
    compare(sc, "Остаток запаса не отрицательный", ref(CF, f"P{last_cf}"), max(0.0, stock_end), stock_end, 1)

    # ФОТ: производственный / АУП и ОПВР по годам
    staff = [row for row in data.get("salary") or [] if str(row.get("name") or "").strip() and _num(row.get("salary")) > 0]
    prod = sum(abs(_num(pnl.cell(book.pnl["prod"], c).value)) for c in range(2, 2 + book.years))
    admin = sum(abs(_num(pnl.cell(book.pnl["admin_fot"], c).value)) for c in range(2, 2 + book.years))
    compare(sc, "ФОТ производства есть ⇔ есть производственный персонал", ref(PNL, f"B{book.pnl['prod']}"),
            any(int(row.get("industrial") or 0) == 1 for row in staff), prod >= 1, 0)
    compare(sc, "ФОТ АУП есть ⇔ есть административный персонал", ref(PNL, f"B{book.pnl['admin_fot']}"),
            any(int(row.get("industrial") or 0) == 0 for row in staff), admin >= 1, 0)
    rates = load_rates()
    ladder = {"base": _num(data.get("opvr_rate")) / 100 or 0.035, 2027: _num(rates.get("opvr_rate_2027")) / 100, 2028: _num(rates.get("opvr_rate_2028")) / 100}
    pay = wb[PAY]
    by_year: dict[int, list[float]] = {}
    pay_rows = range(book.pay_month_first, book.pay_month_last + 1) if getattr(book, "pay_month_first", None) else range(0)
    for r in pay_rows:
        accrued, opvr = _num(pay.cell(r, 8).value), _num(pay.cell(r, 12).value)
        day = pay.cell(r, 1).value
        if accrued > 0 and isinstance(day, (date, datetime)):
            by_year.setdefault(_day(day).year, []).append(opvr / accrued)
    exempt = any(row.get("opvr_exempt") for row in staff)
    for year, ratios in sorted(by_year.items())[:4]:
        expected = ladder["base"] if year <= 2026 else ladder[2027] if year == 2027 else ladder[2028]
        if not exempt:
            compare(sc, f"Ставка ОПВР {year}", ref(PAY, "L"), expected, max(ratios), 1e-6, "ОПВР / начисление, база ниже предела")

    # Собственные проверки книги
    statuses = {}
    for r in range(last + 2, eff.max_row + 1):
        label, status = eff.cell(r, 1).value, eff.cell(r, 2).value
        if isinstance(label, str) and isinstance(status, str):
            statuses[label] = (status, r)
    for label, (status, r) in statuses.items():
        if status.startswith("Ошибка"):
            compare(sc, f"Проверка книги: {label}", ref(EFFECT, f"B{r}"), "OK", status, 0)
    sc.statuses = {label: status for label, (status, _) in statuses.items()}
    sc.values = {
        "B5": eff["B5"].value, "B6": eff["B6"].value, "B13": eff["B13"].value,
        "F5": eff["F5"].value, "F6": eff["F6"].value, "F13": eff["F13"].value, "B17": eff["B17"].value,
        "C5": eff["C5"].value, "C6": eff["C6"].value,
    }
    return data, wb


# ---------- Сценарии: копии анкет, нейтральные названия ----------

def neutral_base() -> dict:
    with SessionLocal() as db:
        form = copy.deepcopy(db.get(FinModel, 23).data)
    form["name"] = "Сценарий"
    form["volume_sales"] = [{**form["volume_sales"][0], "name": "Продукт А"}]
    form["variable_costs"] = [{"product": "Продукт А", "cost_type": "материалы", "price": 700}]
    form["salary"] = [
        {**form["salary"][0], "name": "Производственный сотрудник", "industrial": 1},
        {**form["salary"][0], "name": "Администратор", "industrial": 0, "count": 1, "salary": 250000},
    ]
    for key in ("equipment", "premises", "smr", "overhaul", "inventory"):
        form[key] = []
    for key in ("own", "attracted", "credit"):
        form[key] = {k: 0 for k in form["own"]}
    form["owned_assets_value"] = None
    form["zero_cogs_confirmed"] = False
    return form


def asset(name, price, owned=False, book=0, life=7, month="2026-11"):
    return {"name": name, "count": 1, "price_one": price, "price": price, "year": int(month[:4]), "life_years": life,
            "pay_month": month, "commission_month": month, "already_owned": owned, "book_value": book}


def fund(form, key, total, own=0, credit=0):
    form["own"][key], form["attracted"][key], form["credit"][key] = own, total - own, credit


def scenarios() -> list[Scenario]:
    base = neutral_base()
    out = []

    def make(title, **changes):
        form = copy.deepcopy(base)
        form.update(changes)
        return title, form

    title, f = make("01 Новый бизнес: CAPEX, инвестор и свои")
    f["equipment"] = [asset("Оборудование", 3_000_000)]
    fund(f, "equipment", 3_000_000, own=500_000)
    fund(f, "raw", 700_000, own=100_000)
    out.append(Scenario(title, f))

    title, f = make("02 Действующий бизнес: имеющиеся активы, оценка не подтверждена")
    f["premises"] = [asset("Помещение", 0, owned=True, book=20_000_000, life=25)]
    f["equipment"] = [asset("Имеющееся оборудование", 300_000, owned=True, book=300_000, life=5), asset("Новое оборудование", 2_000_000)]
    fund(f, "equipment", 2_000_000, own=200_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("03 Действующий бизнес: согласованная оценка 12 млн", owned_assets_value=12_000_000)
    f["premises"] = [asset("Помещение", 0, owned=True, book=20_000_000, life=25)]
    f["equipment"] = [asset("Новое оборудование", 2_000_000)]
    fund(f, "equipment", 2_000_000, own=200_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("04 Действующий бизнес без новых денег")
    f["equipment"] = [asset("Имеющееся оборудование", 900_000, owned=True, book=600_000, life=3)]
    out.append(Scenario(title, f))

    title, f = make("05 Инвестор: выход внутри плана", investor_term=3)
    f["equipment"] = [asset("Оборудование", 3_000_000)]
    fund(f, "equipment", 3_000_000, own=500_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("06 Кредит", financing=2, loan_term=36, loan_grace=3, loan_rate=18, wc_term=24, wc_rate=20)
    f["equipment"] = [asset("Оборудование", 3_000_000)]
    fund(f, "equipment", 3_000_000, own=300_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("07 Смешанное: инвестор + кредит", financing=4, loan_term=36, loan_grace=0, loan_rate=18)
    f["equipment"] = [asset("Оборудование", 3_000_000)]
    fund(f, "equipment", 3_000_000, own=600_000, credit=1_500_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("08 Грант", financing=3)
    f["equipment"] = [asset("Оборудование", 2_000_000)]
    fund(f, "equipment", 2_000_000, own=200_000)
    out.append(Scenario(title, f))

    title, f = make("09 Несколько продуктов и сезонность")
    f["volume_sales"] = [
        {**base["volume_sales"][0]},
        {"name": "Продукт Б", "count": 400, "price": 1800, "units": 1, "months": [6, 7, 8], "seasonal": True,
         "sales_start": "2027-03", "own_capacity": False, "planned_capacity": {}},
    ]
    f["variable_costs"] = [{"product": "Продукт А", "cost_type": "материалы", "price": 700}, {"product": "Продукт Б", "cost_type": "сырьё", "price": 600}]
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("10 CAPEX с оплатой позже старта")
    f["equipment"] = [asset("Оборудование 1", 1_500_000), asset("Оборудование 2", 2_500_000, month="2027-06", life=10)]
    f["smr"] = [asset("Ремонт", 1_000_000, month="2026-12", life=5)]
    fund(f, "equipment", 4_000_000, own=1_000_000)
    fund(f, "smr", 1_000_000)
    out.append(Scenario(title, f))

    title, f = make("11 Запас на 3 месяца, продажи позже старта", sales_start="2027-03", planned_volume="2027-08", sales_start_load=40)
    f["inventory"] = [{"name": "Сырьё", "count": 1, "price_one": 2_100_000, "price": 2_100_000}]
    fund(f, "raw", 2_100_000, own=100_000)
    out.append(Scenario(title, f))

    title, f = make("12 Убыточный проект")
    f["volume_sales"] = [{**base["volume_sales"][0], "price": 900}]
    f["equipment"] = [asset("Оборудование", 3_000_000)]
    fund(f, "equipment", 3_000_000, own=500_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("13 ТОО, общий режим, НДС, имеющиеся активы", legal_form=2, tax_regime=1, nds=1, tax_cit_rate=20)
    f["equipment"] = [asset("Оборудование", 3_000_000), asset("Имеющаяся техника", 1_000_000, owned=True, book=800_000, life=4)]
    fund(f, "equipment", 3_000_000, own=500_000)
    out.append(Scenario(title, f))

    title, f = make("15 ИП, упрощёнка, плательщик НДС", tax_regime=4, nds=1, vat_rate=16)
    f["equipment"] = [asset("Оборудование", 3_000_000)]
    fund(f, "equipment", 3_000_000, own=500_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f))

    title, f = make("14 Режим 2 (до даты выхода) с имеющимися активами", investor_term=7)
    f["premises"] = [asset("Помещение", 0, owned=True, book=10_000_000, life=25)]
    f["equipment"] = [asset("Оборудование", 2_000_000)]
    fund(f, "equipment", 2_000_000, own=200_000)
    fund(f, "raw", 700_000)
    out.append(Scenario(title, f, mode2=True))

    with SessionLocal() as db:
        for model_id in (23, 19, 7, 9, 10, 12):
            model = db.get(FinModel, model_id)
            if model and isinstance(model.data, dict):
                out.append(Scenario(f"DB #{model_id} {model.data.get('name') or ''}".strip(), copy.deepcopy(model.data), stored=True))
    return out


def invariants(results: list[Scenario]):
    """Остаток имеющихся активов не меняет NPV новых инвестиций (упрощёнка: амортизация налог не меняет)."""
    base = next(sc for sc in results if sc.title.startswith("02"))
    form = copy.deepcopy(base.form)
    for item in form["premises"]:
        item["book_value"] *= 2
    probe = Scenario("02b Те же активы, балансовая ×2", form)
    audit(probe)
    compare(probe, "NPV новых инвестиций не зависит от имеющихся активов", ref(EFFECT, "B5"), base.values["B5"], probe.values["B5"], 1,
            "Упрощёнка: налог с выручки, амортизация имеющихся активов поток не меняет")
    compare(probe, "WACC новых инвестиций не зависит от имеющихся активов", ref(EFFECT, "B13"), base.values["B13"], probe.values["B13"], 1e-9)
    results.append(probe)


def regression() -> list[dict]:
    rows = []
    with SessionLocal() as db:
        models = db.query(FinModel).order_by(FinModel.id).all()
        items = [
            (m.id, copy.deepcopy(m.data))
            for m in models
            if m.status == "ready" and isinstance(m.data, dict) and "volume_sales" in m.data and not m.data.get("draft")
        ]
    for model_id, form in items:
        try:
            _, book, wb = build(form, stored=True)
        except Exception as exc:
            rows.append({"id": model_id, "status": "FAIL", "detail": f"сборка: {type(exc).__name__}: {exc}"})
            continue
        bad = [
            f"{ws.title}!{c.coordinate}={c.value}"
            for ws in wb.worksheets
            for row in ws.iter_rows()
            for c in row
            if isinstance(c.value, str) and (c.value.startswith("#") or c.value in BAD)
        ]
        rows.append({"id": model_id, "status": "PASS" if not bad else "FAIL", "detail": "; ".join(bad[:4]),
                     "npv_new": wb[EFFECT]["B5"].value, "npv_whole": wb[EFFECT]["F5"].value})
    return rows


def _fmt(value) -> str:
    if value is None:
        return "—"
    if isinstance(value, bool):
        return str(value)
    if isinstance(value, (int, float)):
        if value != value:
            return "NaN"
        if abs(value) >= 1000:
            return f"{value:,.0f}".replace(",", " ")
        if value == int(value):
            return str(int(value))
        return f"{value:.6g}"
    return str(value).replace("|", "/").replace("\n", " ")


def markdown(report: dict) -> str:
    s = report["summary"]
    lines = [
        "# Аудит режимов оценки ФЭМ: новые инвестиции и весь бизнес",
        "",
        f"Итог: **{s['pass']} PASS / {s['fail']} FAIL** из {s['checks']} проверок; "
        f"регрессия по готовым моделям: {s['regression_models'] - s['regression_fail']}/{s['regression_models']} без ошибок.",
        "",
        "Ожидаемые значения посчитаны в Python независимо от формул книги: поток из ДДС, XNPV/XIRR, "
        "окупаемость, остаточная стоимость из реестра CAPEX, WACC из сумм финансирования. "
        "Фактические — значения ячеек после пересчёта LibreOffice. Все модели — копии, база не меняется.",
        "",
        "## Методика (лист «Анализ эффективности»)",
        "",
        "| | Новые инвестиции (основной, B5:B9) | Весь бизнес (справочно, F5:F9) |",
        "|---|---|---|",
        "| Поток | B: FCFF из ДДС, имеющиеся активы денег не тратят | BI = B − AU + AV − F14 в первом месяце |",
        "| Начальное вложение | Фактические оплаты CAPEX и запаса | + оценка имеющихся активов F14 (вклад имуществом, не деньги) |",
        "| Остаток в конце | AU: остаточная новых активов (AS) + запас на конец | AV: остаточная всех активов AS + F16·AT + запас |",
        "| Ставка | B13: WACC только новых денег (грант вне базы) | F13: WACC новых денег + вклад имуществом по оценке |",
        "| Доля инвестора | B17 по той же оценке F14 | — |",
        "",
        "F14 — согласованная оценка из анкеты, без неё балансовая с пометкой «не подтверждена» (F15). "
        "Режим 2 (I13=2): остаток ставится на последнюю строку продления, I18 — среднемесячный FCFF без остатка. "
        "НДС (любой налоговый режим): квартал, уплата во 2-м месяце после квартала, превышение входящего НДС переносится; "
        "НДС к зачёту на конец плана входит в последний FCFF вместе с запасом (I14).",
        "",
        "## Сводка по сценариям",
        "",
        "| Сценарий | PASS | FAIL | NPV новых (B5) | IRR новых (B6) | NPV бизнеса (F5) | IRR бизнеса (F6) | WACC новых (B13) | WACC бизнеса (F13) |",
        "|---|---|---|---|---|---|---|---|---|",
    ]
    by_scenario: dict[str, list[dict]] = {}
    for c in report["checks"]:
        by_scenario.setdefault(c["scenario"], []).append(c)
    for sc in report["scenarios"]:
        rows = by_scenario.get(sc["title"], [])
        fail = sum(1 for c in rows if c["status"] == "FAIL")
        v = sc["values"]
        lines.append(
            f"| {sc['title']} | {len(rows) - fail} | {fail} | {_fmt(v.get('B5'))} | {_fmt(v.get('B6'))} | "
            f"{_fmt(v.get('F5'))} | {_fmt(v.get('F6'))} | {_fmt(v.get('B13'))} | {_fmt(v.get('F13'))} |"
        )
    for sc in report["scenarios"]:
        lines += ["", f"## {sc['title']}", "", "| Проверка | Ячейка | Ожидалось | Факт | Отклонение | Статус | Примечание |",
                  "|---|---|---|---|---|---|---|"]
        for c in by_scenario.get(sc["title"], []):
            lines.append(
                f"| {_fmt(c['name'])} | {_fmt(c['cell'])} | {_fmt(c['expected'])} | {_fmt(c['actual'])} | "
                f"{_fmt(c['deviation'])} | {c['status']} | {_fmt(c['note']) if c['note'] else ''} |"
            )
        warnings = {k: v for k, v in sc["statuses"].items() if v != "OK"}
        if warnings:
            lines += ["", "Статусы книги не «OK» (лист «Анализ эффективности», аудит):", ""]
            lines += [f"- {k}: {v}" for k, v in warnings.items()]
    lines += ["", "## Регрессия: все готовые модели", "", "| Модель | Статус | NPV новых (B5) | NPV бизнеса (F5) | Ошибки |", "|---|---|---|---|---|"]
    for r in report["regression"]:
        lines.append(f"| #{r['id']} | {r['status']} | {_fmt(r.get('npv_new'))} | {_fmt(r.get('npv_whole'))} | {_fmt(r['detail']) if r['detail'] else ''} |")
    return "\n".join(lines) + "\n"


def main() -> int:
    target = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT.parents[1] / "NC-fem-modes-audit-report"
    results = scenarios()
    for sc in results:
        try:
            audit(sc)
        except Exception as exc:
            sc.checks.append(Check(sc.title, "Сборка", "-", "OK", f"{type(exc).__name__}: {exc}", None, "FAIL"))
        failed = [c for c in sc.checks if c.status == "FAIL"]
        print(f"{'PASS' if not failed else 'FAIL'} {sc.title}: {len(sc.checks) - len(failed)}/{len(sc.checks)}")
        for c in failed:
            print(f"    FAIL {c.name} {c.cell}: ожидалось {c.expected}, факт {c.actual}, отклонение {c.deviation}")
    invariants(results)
    reg = regression()
    checks = [asdict(c) for sc in results for c in sc.checks]
    summary = {
        "checks": len(checks),
        "pass": sum(1 for c in checks if c["status"] == "PASS"),
        "fail": sum(1 for c in checks if c["status"] == "FAIL"),
        "regression_models": len(reg),
        "regression_fail": sum(1 for r in reg if r["status"] == "FAIL"),
    }
    report = {
        "summary": summary,
        "scenarios": [{"title": sc.title, "values": getattr(sc, "values", {}), "statuses": getattr(sc, "statuses", {})} for sc in results],
        "checks": checks,
        "regression": reg,
    }
    target.with_suffix(".json").write_text(json.dumps(report, ensure_ascii=False, indent=2, default=str), encoding="utf-8")
    target.with_suffix(".md").write_text(markdown(report), encoding="utf-8")
    print(json.dumps(summary, ensure_ascii=False))
    return 0 if summary["fail"] == 0 and summary["regression_fail"] == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
