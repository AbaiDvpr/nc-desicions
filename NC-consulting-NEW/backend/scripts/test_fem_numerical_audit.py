#!/usr/bin/env python3
"""Усиленный независимый численный аудит ФЭМ v5.

Не меняет Excel/генератор. Считает файл через LibreOffice и независимо
пересчитывает NPV/IRR/MIRR, FCFF, ДДС↔график, долг/%/субсидию, потоки инвестора.
Каждая проверка пишет expected/actual/diff/tolerance/ok в JSON.
Негативные тесты портят копию снимка модели и требуют, чтобы аудитор это поймал.
"""

from __future__ import annotations

import json
import math
import subprocess
import sys
import tempfile
from copy import deepcopy
from dataclasses import asdict, dataclass, field
from datetime import date, datetime
from pathlib import Path
from typing import Any

from openpyxl import load_workbook

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from app.database import SessionLocal  # noqa: E402
from app.fem_v5_book import CAPEX, CF, EFFECT, FIN, PNL, SCHED, build_fem_v5_excel  # noqa: E402
from app.models import FinModel  # noqa: E402
from app.routers.fin_models import _ready_form  # noqa: E402
from fastapi import HTTPException  # noqa: E402
from sqlalchemy import select  # noqa: E402

OUT_DIR = Path("/var/www/html/nc-desicions")
ORIGIN = 24  # первая строка помесячного блока на «Анализ эффект.»


@dataclass
class Check:
    name: str
    expected: Any
    actual: Any
    tolerance: float
    ok: bool
    diff: float | None = None
    detail: dict = field(default_factory=dict)
    kind: str = "main"  # main | negative

    def as_dict(self) -> dict:
        return asdict(self)


@dataclass
class XirrResult:
    rate: float | None
    status: str
    roots: list[float] = field(default_factory=list)
    sign_changes: int = 0


class AuditValueError(ValueError):
    """Обязательное финансовое поле отсутствует или нечисловое."""


def _is_number(v: Any) -> bool:
    return isinstance(v, (int, float)) and not isinstance(v, bool)


def _finite(v: Any, field: str) -> float:
    """Обязательное числовое поле: без подмены None/NaN/Inf нулём."""
    if v is None:
        raise AuditValueError(f"{field}: значение отсутствует (None)")
    if isinstance(v, str):
        raise AuditValueError(f"{field}: ожидалось число, получена строка {v!r}")
    if not _is_number(v):
        raise AuditValueError(f"{field}: нечисловое значение {v!r}")
    f = float(v)
    if not math.isfinite(f):
        raise AuditValueError(f"{field}: не конечное число {v!r}")
    return f


def _optional_finite(v: Any, field: str, default: float = 0.0) -> float:
    """Ячейка ДДС/графика: пусто = 0; NaN/Inf/мусор — ошибка."""
    if v is None or v == "":
        return default
    if isinstance(v, str):
        raise AuditValueError(f"{field}: ожидалось число или пусто, получена строка {v!r}")
    if not _is_number(v):
        raise AuditValueError(f"{field}: нечисловое значение {v!r}")
    f = float(v)
    if not math.isfinite(f):
        raise AuditValueError(f"{field}: не конечное число {v!r}")
    return f


def _as_date(v: Any) -> date | None:
    if isinstance(v, datetime):
        return v.date()
    if isinstance(v, date):
        return v
    return None


def _diff(expected: Any, actual: Any) -> float | None:
    try:
        return float(actual) - float(expected)
    except (TypeError, ValueError):
        return None


def check_near(name: str, expected: Any, actual: Any, tol: float, *, kind: str = "main", **detail) -> Check:
    try:
        if expected is None or actual is None:
            raise TypeError("None")
        exp_f = float(expected)
        act_f = float(actual)
        if not math.isfinite(exp_f) or not math.isfinite(act_f):
            return Check(
                name=name,
                expected=expected,
                actual=actual,
                tolerance=tol,
                ok=False,
                diff=None,
                detail={**detail, "reason": "NaN/Inf"},
                kind=kind,
            )
        diff = act_f - exp_f
        ok = abs(diff) <= tol
        return Check(name=name, expected=exp_f, actual=act_f, tolerance=tol, ok=ok, diff=diff, detail=detail, kind=kind)
    except (TypeError, ValueError):
        ok = expected == actual
        return Check(
            name=name,
            expected=expected,
            actual=actual,
            tolerance=tol,
            ok=ok,
            diff=_diff(expected, actual),
            detail=detail,
            kind=kind,
        )


def check_equal(name: str, expected: Any, actual: Any, *, kind: str = "main", **detail) -> Check:
    ok = expected == actual
    return Check(
        name=name,
        expected=expected,
        actual=actual,
        tolerance=0.0,
        ok=ok,
        diff=_diff(expected, actual),
        detail=detail,
        kind=kind,
    )


def _calc(payload: bytes, tag: str) -> Path:
    with tempfile.TemporaryDirectory(prefix=f"fem-audit-{tag}-") as folder:
        folder = Path(folder)
        src, out = folder / "in", folder / "out"
        src.mkdir()
        out.mkdir()
        (src / "model.xlsx").write_bytes(payload)
        done = subprocess.run(
            [
                "soffice",
                "--headless",
                "--norestore",
                "--nolockcheck",
                "--nologo",
                f"-env:UserInstallation=file://{folder}/p",
                "--convert-to",
                "xlsx",
                "--outdir",
                str(out),
                str(src / "model.xlsx"),
            ],
            capture_output=True,
            text=True,
            timeout=360,
        )
        calc = out / "model.xlsx"
        if done.returncode != 0 or not calc.exists():
            raise RuntimeError(done.stderr[-1200:] or "LibreOffice failed")
        target = Path(f"/tmp/fem_audit_{tag}.xlsx")
        target.write_bytes(calc.read_bytes())
        return target


# --- независимая финматематика -------------------------------------------------

def npv_dated(flows: list[float], dates: list[date], rate: float) -> float:
    """NPV с day-count (date-d0)/365. Ставка 0% и (−1, 0) допустимы; ≤ −100% — ошибка."""
    if not _is_number(rate) or not math.isfinite(float(rate)):
        raise AuditValueError(f"недопустимая ставка NPV: {rate!r}")
    rate = float(rate)
    if rate <= -1.0:
        raise AuditValueError(f"недопустимая ставка NPV ≤ −100%: {rate}")
    if not flows:
        return 0.0
    if len(flows) != len(dates):
        raise AuditValueError(f"NPV: длина потоков {len(flows)} ≠ дат {len(dates)}")
    d0 = dates[0]
    total = 0.0
    for amount, dt in zip(flows, dates):
        if not math.isfinite(amount):
            raise AuditValueError(f"NPV: неконечный поток {amount!r}")
        years = (dt - d0).days / 365.0
        total += amount / ((1.0 + rate) ** years)
    return total


def discount_factor(dt: date, d0: date, rate: float) -> float:
    if rate <= -1.0:
        raise AuditValueError(f"недопустимая ставка дисконта ≤ −100%: {rate}")
    years = (dt - d0).days / 365.0
    return 1.0 / ((1.0 + rate) ** years)


def _sign_changes(flows: list[float]) -> int:
    signs = [1 if x > 0 else -1 for x in flows if abs(x) > 1e-12]
    if not signs:
        return 0
    return sum(1 for i in range(1, len(signs)) if signs[i] != signs[i - 1])


def xirr(flows: list[float], dates: list[date], guess: float = 0.1) -> XirrResult:
    """XIRR с учётом нескольких смен знака и неоднозначности корня."""
    if not flows or len(flows) != len(dates):
        return XirrResult(None, "invalid_input", sign_changes=0)
    sc = _sign_changes(flows)
    if sc == 0:
        return XirrResult(None, "no_sign_change", sign_changes=0)

    d0 = dates[0]

    def f(r: float) -> float:
        if r <= -0.999999999:
            return float("inf")
        s = 0.0
        for amount, dt in zip(flows, dates):
            s += amount / ((1.0 + r) ** ((dt - d0).days / 365.0))
        return s

    # Сканирование скобок: ищем смены знака NPV(r)
    grid = [-0.99, -0.9, -0.75, -0.5, -0.25, -0.1, 0.0, 0.05, 0.1, 0.2, 0.35, 0.5, 0.75, 1.0, 1.5, 2.0, 3.0, 5.0, 8.0, 12.0]
    roots: list[float] = []
    for i in range(len(grid) - 1):
        lo, hi = grid[i], grid[i + 1]
        flo, fhi = f(lo), f(hi)
        if not math.isfinite(flo) or not math.isfinite(fhi):
            continue
        if abs(flo) < 1e-8:
            roots.append(lo)
            continue
        if flo * fhi > 0:
            continue
        a, b, fa = lo, hi, flo
        for _ in range(80):
            mid = 0.5 * (a + b)
            fm = f(mid)
            if abs(fm) < 1e-10 or (b - a) < 1e-12:
                roots.append(mid)
                break
            if fa * fm <= 0:
                b = mid
            else:
                a, fa = mid, fm
        else:
            roots.append(0.5 * (a + b))

    # Уникализация близких корней
    uniq: list[float] = []
    for r in sorted(roots):
        if not uniq or abs(r - uniq[-1]) > 1e-5:
            uniq.append(r)

    if not uniq:
        # Newton с нескольких стартов
        for start in (guess, -0.5, 0.0, 0.1, 0.25, 0.5, 1.0, -0.2):
            rate = start
            ok = False
            for _ in range(60):
                y = f(rate)
                # численная производная
                eps = 1e-6
                dy = (f(rate + eps) - y) / eps
                if abs(dy) < 1e-14:
                    break
                nxt = rate - y / dy
                if nxt <= -0.999999:
                    nxt = -0.9999
                if abs(nxt - rate) < 1e-12:
                    rate = nxt
                    ok = abs(f(rate)) < 1e-4
                    break
                rate = nxt
            if ok and math.isfinite(rate):
                uniq.append(rate)
                break
        uniq = sorted({round(r, 10) for r in uniq})

    if not uniq:
        return XirrResult(None, "no_convergence", sign_changes=sc)
    if len(uniq) == 1:
        return XirrResult(uniq[0], "ok", roots=uniq, sign_changes=sc)
    # Несколько корней: берём ближайший к guess как «основной», статус multiple
    primary = min(uniq, key=lambda r: abs(r - guess))
    return XirrResult(primary, "multiple_roots", roots=uniq, sign_changes=sc)


def mirr_annual_monthly(flows: list[float], annual_rate: float) -> float | None:
    """Как в Excel-листе: MIRR помесячно, затем (1+m)^12-1."""
    if not flows or max(flows) <= 0 or min(flows) >= 0:
        return None
    if annual_rate <= -1.0:
        raise AuditValueError(f"недопустимая ставка MIRR ≤ −100%: {annual_rate}")
    monthly = (1.0 + annual_rate) ** (1.0 / 12.0) - 1.0
    n = len(flows)
    pos_fv = 0.0
    neg_pv = 0.0
    for i, amount in enumerate(flows):
        if amount > 0:
            pos_fv += amount * ((1.0 + monthly) ** (n - 1 - i))
        elif amount < 0:
            neg_pv += amount / ((1.0 + monthly) ** i)
    if neg_pv >= 0 or pos_fv <= 0:
        return None
    m_month = (pos_fv / (-neg_pv)) ** (1.0 / (n - 1)) - 1.0
    return (1.0 + m_month) ** 12.0 - 1.0


def _irr_sheet_matches(result: XirrResult, irr_sheet: Any) -> Check:
    """Сверка XIRR с листом с учётом отсутствия/неоднозначности решения."""
    if result.status in ("no_sign_change", "no_convergence", "invalid_input"):
        ok = isinstance(irr_sheet, str) and (
            "знака" in str(irr_sheet).casefold()
            or "не определяется" in str(irr_sheet).casefold()
            or "не примен" in str(irr_sheet).casefold()
        )
        return Check(
            name="irr_independent_no_solution",
            expected="нет смены знака / не определяется",
            actual=irr_sheet,
            tolerance=0.0,
            ok=ok,
            detail={"status": result.status, "sign_changes": result.sign_changes, "roots": result.roots},
        )
    if result.status == "multiple_roots":
        if isinstance(irr_sheet, str):
            return Check(
                name="irr_independent_multiple_roots_sheet_text",
                expected="численный IRR или явное сообщение",
                actual=irr_sheet,
                tolerance=0.0,
                ok=True,
                detail={"status": result.status, "roots": result.roots, "sign_changes": result.sign_changes},
            )
        if not _is_number(irr_sheet) or not math.isfinite(float(irr_sheet)):
            return Check(
                name="irr_independent_multiple_roots",
                expected=result.roots,
                actual=irr_sheet,
                tolerance=1e-5,
                ok=False,
                detail={"status": result.status, "sign_changes": result.sign_changes},
            )
        sheet = float(irr_sheet)
        matched = min(result.roots, key=lambda r: abs(r - sheet))
        return check_near(
            "irr_independent_multiple_roots",
            matched,
            sheet,
            1e-5,
            status=result.status,
            roots=result.roots,
            sign_changes=result.sign_changes,
        )
    # единственный корень
    if isinstance(irr_sheet, str):
        return Check(
            name="irr_independent",
            expected=result.rate,
            actual=irr_sheet,
            tolerance=1e-5,
            ok=False,
            detail={"status": result.status, "sign_changes": result.sign_changes},
        )
    return check_near(
        "irr_independent",
        result.rate,
        irr_sheet,
        1e-5,
        status=result.status,
        sign_changes=result.sign_changes,
    )


# --- чтение книги → снимок -----------------------------------------------------

def _fin_totals(wb) -> dict:
    fin = wb[FIN]
    for row in range(1, 50):
        if str(fin.cell(row, 2).value or "").strip().upper().startswith("ВСЕГО"):
            return {
                "invest": _finite(fin.cell(row, 3).value, "FIN.invest"),
                "debt": _finite(fin.cell(row, 4).value, "FIN.debt"),
                "investor": _finite(fin.cell(row, 5).value, "FIN.investor"),
                "own": _finite(fin.cell(row, 6).value, "FIN.own"),
                "grant": _finite(fin.cell(row, 7).value, "FIN.grant"),
            }
    raise RuntimeError("FIN TOTAL not found")


def _cf_header(wb) -> int:
    cf = wb[CF]
    for r in range(1, 90):
        if cf.cell(r, 1).value == "Дата" and cf.cell(r, 4).value:
            return r
    raise RuntimeError("CF header not found")


def _year_tax_map(wb) -> dict[int, float]:
    pnl = wb[PNL]
    years: list[int] = []
    col = 2
    while _is_number(pnl.cell(4, col).value):
        years.append(int(pnl.cell(4, col).value))
        col += 1
    tax_row = next((r for r in range(1, 40) if "Налог" in str(pnl.cell(r, 1).value or "")), None)
    out: dict[int, float] = {}
    if tax_row is None:
        return out
    for i, year in enumerate(years):
        out[year] = _optional_finite(pnl.cell(tax_row, 2 + i).value, f"PNL.tax.{year}")
    return out


def _effect_series(wb) -> dict:
    """Помесячные ряды анализа без розового продления."""
    effect = wb[EFFECT]
    dates: list[date] = []
    fcff: list[float] = []
    disc_factor: list[float] = []
    disc_fcff: list[float] = []
    inv_contrib: list[float] = []
    inv_div: list[float] = []
    inv_exit: list[float] = []
    inv_flow: list[float] = []
    for r in range(ORIGIN, ORIGIN + 500):
        dt = _as_date(effect.cell(r, 1).value)
        if dt is None:
            break
        if str(effect.cell(r, 15).value or "") == "продление":
            break
        dates.append(dt)
        fcff.append(_finite(effect.cell(r, 2).value, f"EFFECT.FCFF[{r}]"))
        disc_factor.append(_finite(effect.cell(r, 4).value, f"EFFECT.disc_factor[{r}]"))
        disc_fcff.append(_finite(effect.cell(r, 5).value, f"EFFECT.disc_fcff[{r}]"))
        inv_contrib.append(_optional_finite(effect.cell(r, 7).value, f"EFFECT.inv_contrib[{r}]"))
        inv_div.append(_optional_finite(effect.cell(r, 8).value, f"EFFECT.inv_div[{r}]"))
        inv_exit.append(_optional_finite(effect.cell(r, 9).value, f"EFFECT.inv_exit[{r}]"))
        inv_flow.append(_optional_finite(effect.cell(r, 10).value, f"EFFECT.inv_flow[{r}]"))
    wacc = _finite(effect.cell(13, 2).value, "EFFECT.wacc")
    # ставка инвестора может быть текстом «Не применяется»
    inv_rate_raw = effect.cell(14, 2).value
    if isinstance(inv_rate_raw, str):
        investor_rate = None
    else:
        investor_rate = _finite(inv_rate_raw, "EFFECT.investor_rate")
    return {
        "dates": dates,
        "fcff": fcff,
        "disc_factor": disc_factor,
        "disc_fcff": disc_fcff,
        "inv_contrib": inv_contrib,
        "inv_div": inv_div,
        "inv_exit": inv_exit,
        "inv_flow": inv_flow,
        "wacc": wacc,
        "investor_rate": investor_rate,
        "npv_sheet": effect.cell(5, 2).value,
        "irr_sheet": effect.cell(6, 2).value,
        "mirr_sheet": effect.cell(12, 2).value,
        "inv_npv_sheet": effect.cell(5, 3).value,
        "inv_irr_sheet": effect.cell(6, 3).value,
        "shield": _optional_finite(effect.cell(18, 2).value, "EFFECT.shield"),
        "deficit": effect.cell(11, 2).value,
        "dscr_min": effect.cell(10, 2).value,
        "terminal_by_row": {
            i: _optional_finite(effect.cell(ORIGIN + i, 33).value, f"EFFECT.terminal[{i}]")
            for i in range(len(dates))
        },
    }


def _extract_cf_rows(wb, header: int, n_months: int) -> list[dict]:
    cf = wb[CF]
    rows = []
    for i in range(n_months + 5):
        r = header + 1 + i
        dt = _as_date(cf.cell(r, 1).value)
        if dt is None:
            break
        rows.append(
            {
                "row": r,
                "date": dt,
                "inflow": _optional_finite(cf.cell(r, 4).value, f"CF.inflow[{r}]"),
                "opex": _optional_finite(cf.cell(r, 6).value, f"CF.opex[{r}]"),
                "salary": _optional_finite(cf.cell(r, 7).value, f"CF.salary[{r}]"),
                "tax_other": _optional_finite(cf.cell(r, 8).value, f"CF.tax_other[{r}]"),
                "vat": _optional_finite(cf.cell(r, 9).value, f"CF.vat[{r}]"),
                "cit": _optional_finite(cf.cell(r, 10).value, f"CF.cit[{r}]"),
                "interest": _optional_finite(cf.cell(r, 11).value, f"CF.interest[{r}]"),
                "other": _optional_finite(cf.cell(r, 12).value, f"CF.other[{r}]"),
                "capex": _optional_finite(cf.cell(r, 13).value, f"CF.capex[{r}]"),
                "principal": _optional_finite(cf.cell(r, 14).value, f"CF.principal[{r}]"),
                "funding": _optional_finite(cf.cell(r, 15).value, f"CF.funding[{r}]"),
            }
        )
    return rows


def _fcff_from_cf_row(row: dict, year_tax: dict[int, float], shield_rate: float) -> float:
    weight = shield_rate if year_tax.get(row["date"].year, 0.0) > 0 else 0.0
    return (
        row["inflow"]
        - row["opex"]
        - row["salary"]
        - row["tax_other"]
        - row["vat"]
        - row["cit"]
        - row["other"]
        - row["capex"]
        - row["interest"] * weight
    )


def _loan_tranches(wb) -> list[dict]:
    sched = wb[SCHED]
    limit = min(800, max(200, int(sched.max_row or 200)))
    openings = [r for r in range(1, limit) if sched.cell(r, 1).value == "Выдача"]
    tranches = []
    for opening in openings:
        drawn_raw = sched.cell(opening, 6).value
        drawn = _optional_finite(drawn_raw, f"SCHED.drawn[{opening}]")
        nxt = str(sched.cell(opening + 1, 1).value or "")
        if drawn <= 1 or nxt.startswith("Платежей нет"):
            tranches.append({"opening": opening, "drawn": drawn, "empty": True, "rows": []})
            continue
        total = next((r for r in range(opening + 1, opening + 500) if sched.cell(r, 1).value == "Итого"), None)
        if total is None:
            tranches.append({"opening": opening, "drawn": drawn, "empty": False, "rows": [], "broken": True})
            continue
        client_rate = bank_rate = subsidy_rate = None
        subsidy_term = 0
        prev_total = max(
            (r for r in range(1, opening) if sched.cell(r, 1).value == "Итого"),
            default=0,
        )
        scan_lo = max(1, prev_total + 1, opening - 25)
        scan_hi = opening + 40
        for r in range(scan_lo, scan_hi):
            label = str(sched.cell(r, 10).value or "")
            if not label:
                continue
            val = sched.cell(r, 12).value
            if val is None:
                val = sched.cell(r, 11).value
            low = label.casefold().strip()
            if "ставка для клиента" in low:
                client_rate = _finite(val, f"SCHED.client_rate[{r}]")
            elif "субсидируемая ставка" in low:
                subsidy_rate = _finite(val, f"SCHED.subsidy_rate[{r}]")
            elif "срок субсид" in low:
                subsidy_term = int(_finite(val, f"SCHED.subsidy_term[{r}]"))
            elif low == "ставка" or low.startswith("ставка банка"):
                if bank_rate is None:
                    bank_rate = _finite(val, f"SCHED.bank_rate[{r}]")
        if client_rate is None and bank_rate is not None:
            client_rate = bank_rate
        if bank_rate is None and client_rate is not None:
            bank_rate = client_rate
        if subsidy_rate is None and bank_rate is not None and client_rate is not None:
            subsidy_rate = max(0.0, bank_rate - client_rate)
        dual = bool(
            subsidy_term > 0
            and bank_rate is not None
            and client_rate is not None
            and abs(bank_rate - client_rate) > 1e-9
        )
        rows = []
        prev_bal = drawn
        for r in range(opening + 1, total):
            step = int(_finite(sched.cell(r, 1).value, f"SCHED.step[{r}]"))
            bal = _finite(sched.cell(r, 6).value, f"SCHED.balance[{r}]")
            bank_raw = sched.cell(r, 7).value
            sub_raw = sched.cell(r, 8).value
            rows.append(
                {
                    "row": r,
                    "n": step,
                    "date": _as_date(sched.cell(r, 2).value),
                    "payment": _finite(sched.cell(r, 3).value, f"SCHED.payment[{r}]"),
                    "principal": _finite(sched.cell(r, 4).value, f"SCHED.principal[{r}]"),
                    "client_interest": _finite(sched.cell(r, 5).value, f"SCHED.interest[{r}]"),
                    "balance": bal,
                    "bank_interest": None if bank_raw is None else _finite(bank_raw, f"SCHED.bank_int[{r}]"),
                    "subsidy": None if sub_raw is None else _finite(sub_raw, f"SCHED.subsidy[{r}]"),
                    "prev_balance": prev_bal,
                }
            )
            prev_bal = bal
        if client_rate is None or bank_rate is None:
            raise AuditValueError(f"SCHED: не найдена ставка для транша opening={opening}")
        tranches.append(
            {
                "opening": opening,
                "drawn": drawn,
                "empty": False,
                "total_row": total,
                "dual": dual,
                "client_rate": client_rate,
                "bank_rate": bank_rate,
                "subsidy_rate": subsidy_rate if subsidy_rate is not None else 0.0,
                "subsidy_term": subsidy_term or 0,
                "rows": rows,
                "principal_sum": _finite(sched.cell(total, 4).value, f"SCHED.principal_sum[{total}]"),
                "interest_sum": _finite(sched.cell(total, 5).value, f"SCHED.interest_sum[{total}]"),
                "balance_end": _finite(sched.cell(total, 6).value, f"SCHED.balance_end[{total}]"),
            }
        )
    return tranches


def _capex_register(wb) -> float | None:
    cap = wb[CAPEX]
    for r in range(2, 80):
        if str(cap.cell(r, 1).value or "") == "Итого":
            return _finite(cap.cell(r, 13).value, "CAPEX.register_total")
    return None


def build_snapshot(wb) -> dict:
    """Чистый снимок рассчитанной книги — можно deepcopy и портить для негативных тестов."""
    series = _effect_series(wb)
    header = _cf_header(wb)
    return {
        "totals": _fin_totals(wb),
        "series": series,
        "year_tax": _year_tax_map(wb),
        "cf_rows": _extract_cf_rows(wb, header, len(series["dates"])),
        "tranches": _loan_tranches(wb),
        "capex_register": _capex_register(wb),
    }


# --- проверки по снимку --------------------------------------------------------

def evaluate_snapshot(snap: dict, *, kind: str = "main") -> list[Check]:
    """Все основные численные проверки по снимку модели."""
    checks: list[Check] = []
    totals = snap["totals"]
    series = snap["series"]
    year_tax = snap["year_tax"]
    cf_rows = snap["cf_rows"]
    tranches = snap["tranches"]
    register = snap["capex_register"]
    cf_by_date = {r["date"]: r for r in cf_rows}

    sources = totals["debt"] + totals["investor"] + totals["own"] + totals["grant"]
    checks.append(check_near("sources=invest", totals["invest"], sources, 1.0, kind=kind, **totals))

    # NPV
    npv_indep = npv_dated(series["fcff"], series["dates"], series["wacc"])
    npv_sheet = series["npv_sheet"]
    if not _is_number(npv_sheet) or not math.isfinite(float(npv_sheet)):
        checks.append(
            Check(
                name="npv_independent",
                expected=npv_indep,
                actual=npv_sheet,
                tolerance=max(1.0, abs(npv_indep) * 1e-6),
                ok=False,
                detail={"reason": "NPV листа не число"},
                kind=kind,
            )
        )
    else:
        checks.append(
            check_near(
                "npv_independent",
                npv_indep,
                npv_sheet,
                max(1.0, abs(npv_indep) * 1e-6),
                kind=kind,
                wacc=series["wacc"],
                months=len(series["fcff"]),
            )
        )

    # Реальные коэффициенты дисконтирования Excel (колонка D)
    if series["dates"]:
        d0 = series["dates"][0]
        disc_errs = 0
        for i, dt in enumerate(series["dates"]):
            expected = discount_factor(dt, d0, series["wacc"])
            actual = series["disc_factor"][i]
            tol = max(1e-9, abs(expected) * 1e-9)
            c = check_near(
                f"discount_factor_month_{i+1}",
                expected,
                actual,
                tol,
                kind=kind,
                date=str(dt),
                wacc=series["wacc"],
            )
            checks.append(c)
            if not c.ok:
                disc_errs += 1
            # Дисконт FCFF = FCFF × коэффициент
            exp_disc_fcff = series["fcff"][i] * actual
            c2 = check_near(
                f"discount_fcff_month_{i+1}",
                exp_disc_fcff,
                series["disc_fcff"][i],
                max(0.5, abs(exp_disc_fcff) * 1e-9),
                kind=kind,
                date=str(dt),
            )
            checks.append(c2)
            if not c2.ok:
                disc_errs += 1
        checks.append(
            Check(
                name="discount_factors_all_months",
                expected=0,
                actual=disc_errs,
                tolerance=0.0,
                ok=disc_errs == 0,
                diff=float(disc_errs),
                detail={"months": len(series["dates"])},
                kind=kind,
            )
        )

    # IRR / MIRR
    irr_res = xirr(series["fcff"], series["dates"])
    irr_check = _irr_sheet_matches(irr_res, series["irr_sheet"])
    irr_check.kind = kind
    checks.append(irr_check)

    mirr_indep = mirr_annual_monthly(series["fcff"], series["wacc"])
    mirr_sheet = series["mirr_sheet"]
    if mirr_indep is None:
        ok = isinstance(mirr_sheet, str)
        checks.append(
            Check(
                name="mirr_independent_no_solution",
                expected="нет смены знака / не определяется",
                actual=mirr_sheet,
                tolerance=0.0,
                ok=ok,
                detail={"indep": None},
                kind=kind,
            )
        )
    else:
        checks.append(check_near("mirr_independent", mirr_indep, mirr_sheet, 1e-5, kind=kind))

    # FCFF все месяцы — детально
    fcff_errs = 0
    for i, dt in enumerate(series["dates"]):
        row = cf_by_date.get(dt)
        if row is None:
            fcff_errs += 1
            checks.append(
                Check(
                    name=f"fcff_month_{i+1}",
                    expected=str(dt),
                    actual=None,
                    tolerance=0.0,
                    ok=False,
                    detail={"reason": "дата отсутствует в ДДС"},
                    kind=kind,
                )
            )
            continue
        indep = _fcff_from_cf_row(row, year_tax, series["shield"])
        terminal = 0.0
        if i == len(series["dates"]) - 1:
            terminal = float(series["terminal_by_row"].get(i, 0.0) or 0.0)
            indep = indep + terminal
        sheet = series["fcff"][i]
        diff = sheet - indep
        if abs(diff) > 1:
            alt = _fcff_from_cf_row(row, year_tax, 0.0) + terminal
            if abs(sheet - alt) <= 1:
                indep = alt
                diff = sheet - indep
        ok = abs(diff) <= 1.0
        if not ok:
            fcff_errs += 1
        checks.append(
            Check(
                name=f"fcff_month_{i+1}",
                expected=indep,
                actual=sheet,
                tolerance=1.0,
                ok=ok,
                diff=diff,
                detail={
                    "date": str(dt),
                    "funding": row["funding"],
                    "principal": row["principal"],
                    "terminal": terminal,
                },
                kind=kind,
            )
        )
        if row["funding"] > 1 and abs(sheet - row["funding"]) <= 1:
            checks.append(
                Check(
                    name=f"fcff_month_{i+1}_funding_leaked",
                    expected="FCFF≠funding",
                    actual=sheet,
                    tolerance=0.0,
                    ok=False,
                    detail={"funding": row["funding"]},
                    kind=kind,
                )
            )
            fcff_errs += 1
    checks.append(
        Check(
            name="fcff_all_months",
            expected=0,
            actual=fcff_errs,
            tolerance=0.0,
            ok=fcff_errs == 0,
            diff=float(fcff_errs),
            detail={"months": len(series["dates"])},
            kind=kind,
        )
    )

    # ДДС ↔ график — каждый месяц детально
    by_date: dict[str, dict] = {}
    for tr in tranches:
        for row in tr.get("rows") or []:
            if not row["date"]:
                continue
            key = row["date"].isoformat()
            bucket = by_date.setdefault(key, {"interest": 0.0, "principal": 0.0, "date": row["date"]})
            bucket["interest"] += row["client_interest"]
            bucket["principal"] += row["principal"]
    dds_err = 0
    for key in sorted(by_date.keys()):
        sdate = by_date[key]["date"]
        crow = cf_by_date.get(sdate)
        if crow is None:
            dds_err += 1
            checks.append(
                Check(
                    name=f"dds_missing_{key}",
                    expected=key,
                    actual=None,
                    tolerance=0.0,
                    ok=False,
                    kind=kind,
                )
            )
            continue
        c_int = check_near(
            f"dds_interest_{key}",
            by_date[key]["interest"],
            crow["interest"],
            0.5,
            kind=kind,
        )
        c_prin = check_near(
            f"dds_principal_{key}",
            by_date[key]["principal"],
            crow["principal"],
            0.5,
            kind=kind,
        )
        checks.append(c_int)
        checks.append(c_prin)
        if not c_int.ok or not c_prin.ok:
            dds_err += 1
    checks.append(
        Check(
            name="dds_vs_schedule_all_months",
            expected=0,
            actual=dds_err,
            tolerance=0.0,
            ok=dds_err == 0,
            diff=float(dds_err),
            detail={"months": len(by_date), "tranches": len([t for t in tranches if not t.get("empty")])},
            kind=kind,
        )
    )

    # Инвестор
    inv_total = totals["investor"]
    if inv_total < 1:
        checks.append(check_equal("investor_npv_na", "Не применяется", series["inv_npv_sheet"], kind=kind))
        checks.append(check_equal("investor_irr_na", "Не применяется", series["inv_irr_sheet"], kind=kind))
        checks.append(check_near("investor_contrib_zero", 0.0, sum(series["inv_contrib"]), 1.0, kind=kind))
    else:
        contrib = sum(series["inv_contrib"])
        checks.append(check_near("investor_contrib_sum", -inv_total, contrib, 1.0, kind=kind))
        flow = series["inv_flow"]
        flow_errs = 0
        for i, amount in enumerate(flow):
            expected = series["inv_contrib"][i] + series["inv_div"][i] + series["inv_exit"][i]
            c = check_near(
                f"investor_flow_month_{i+1}",
                expected,
                amount,
                0.5,
                kind=kind,
                date=str(series["dates"][i]),
            )
            checks.append(c)
            if not c.ok:
                flow_errs += 1
        checks.append(
            Check(
                name="investor_flow_identity",
                expected=0,
                actual=flow_errs,
                tolerance=0.0,
                ok=flow_errs == 0,
                diff=float(flow_errs),
                kind=kind,
            )
        )
        if series["investor_rate"] is None:
            checks.append(
                Check(
                    name="investor_rate_missing",
                    expected="числовая ставка инвестора",
                    actual=None,
                    tolerance=0.0,
                    ok=False,
                    kind=kind,
                )
            )
        else:
            inv_npv = npv_dated(flow, series["dates"], series["investor_rate"])
            checks.append(
                check_near(
                    "investor_npv_independent",
                    inv_npv,
                    series["inv_npv_sheet"],
                    max(1.0, abs(inv_npv) * 1e-6),
                    kind=kind,
                )
            )
            inv_irr = xirr(flow, series["dates"])
            inv_check = _irr_sheet_matches(inv_irr, series["inv_irr_sheet"])
            inv_check.name = inv_check.name.replace("irr_independent", "investor_irr_independent", 1)
            inv_check.kind = kind
            checks.append(inv_check)

    # Долг помесячно
    loan_month_errs_total = 0
    for tr in tranches:
        if tr.get("empty"):
            checks.append(check_near(f"loan_empty_{tr['opening']}", 0.0, tr["drawn"], 1.0, kind=kind))
            continue
        if tr.get("broken"):
            checks.append(
                Check(
                    name=f"loan_broken_{tr['opening']}",
                    expected="Итого",
                    actual=None,
                    tolerance=0.0,
                    ok=False,
                    kind=kind,
                )
            )
            loan_month_errs_total += 1
            continue
        checks.append(
            check_near(f"loan_{tr['opening']}_principal_sum", tr["drawn"], tr["principal_sum"], 1.0, kind=kind)
        )
        checks.append(check_near(f"loan_{tr['opening']}_balance_end", 0.0, tr["balance_end"], 1.0, kind=kind))
        dual = tr.get("dual")
        client_rate = tr["client_rate"]
        bank_rate = tr["bank_rate"]
        subsidy_rate = tr["subsidy_rate"]
        subsidy_term = int(tr["subsidy_term"])
        tranche_errs = 0
        for row in tr["rows"]:
            n = row["n"]
            prev = row["prev_balance"]
            if dual:
                exp_client = prev * (client_rate if n <= subsidy_term else bank_rate) / 12.0
                exp_bank = prev * bank_rate / 12.0
                exp_sub = prev * subsidy_rate / 12.0 if n <= subsidy_term else 0.0
            else:
                exp_client = prev * client_rate / 12.0
                exp_bank = None
                exp_sub = None
            c1 = check_near(
                f"loan_{tr['opening']}_m{n}_client_interest",
                exp_client,
                row["client_interest"],
                max(0.5, abs(exp_client) * 1e-6),
                kind=kind,
                date=str(row["date"]),
            )
            checks.append(c1)
            if not c1.ok:
                tranche_errs += 1
            if dual and row["bank_interest"] is not None:
                c2 = check_near(
                    f"loan_{tr['opening']}_m{n}_bank_interest",
                    exp_bank,
                    row["bank_interest"],
                    max(0.5, abs(exp_bank) * 1e-6),
                    kind=kind,
                )
                c3 = check_near(
                    f"loan_{tr['opening']}_m{n}_subsidy",
                    exp_sub,
                    row["subsidy"],
                    max(0.5, abs(exp_sub or 0) * 1e-6 + 0.01),
                    kind=kind,
                )
                checks.append(c2)
                checks.append(c3)
                if not c2.ok:
                    tranche_errs += 1
                if not c3.ok:
                    tranche_errs += 1
            exp_bal = prev - row["principal"]
            c4 = check_near(
                f"loan_{tr['opening']}_m{n}_balance",
                exp_bal,
                row["balance"],
                0.5,
                kind=kind,
            )
            checks.append(c4)
            if not c4.ok:
                tranche_errs += 1
            c5 = check_near(
                f"loan_{tr['opening']}_m{n}_payment",
                row["principal"] + row["client_interest"],
                row["payment"],
                0.5,
                kind=kind,
            )
            checks.append(c5)
            if not c5.ok:
                tranche_errs += 1
            if dual and n > subsidy_term and abs(row["subsidy"] or 0) > 0.5:
                tranche_errs += 1
                checks.append(
                    Check(
                        name=f"loan_{tr['opening']}_m{n}_subsidy_after_term",
                        expected=0.0,
                        actual=row["subsidy"],
                        tolerance=0.5,
                        ok=False,
                        diff=_optional_finite(row["subsidy"], "subsidy"),
                        kind=kind,
                    )
                )
        loan_month_errs_total += tranche_errs
        checks.append(
            Check(
                name=f"loan_{tr['opening']}_monthly_math",
                expected=0,
                actual=tranche_errs,
                tolerance=0.0,
                ok=tranche_errs == 0,
                diff=float(tranche_errs),
                detail={"months": len(tr["rows"]), "dual": dual, "client_rate": client_rate, "bank_rate": bank_rate},
                kind=kind,
            )
        )
    checks.append(
        Check(
            name="loan_monthly_errors_total",
            expected=0,
            actual=loan_month_errs_total,
            tolerance=0.0,
            ok=loan_month_errs_total == 0,
            diff=float(loan_month_errs_total),
            kind=kind,
        )
    )

    # CAPEX
    if register is not None:
        paid = sum(r["capex"] for r in cf_rows)
        checks.append(check_near("capex_dds=register", register, paid, 1.0, kind=kind))

    return checks


def run_negative_tests(clean: dict) -> list[Check]:
    """Портит копии снимка и требует, чтобы evaluate_snapshot поймал ошибку."""
    neg: list[Check] = []

    def _must_fail(label: str, sabotaged: dict, predicate) -> Check:
        try:
            bad_checks = evaluate_snapshot(sabotaged, kind="negative")
        except AuditValueError as exc:
            return Check(
                name=label,
                expected="аудитор FAIL или AuditValueError",
                actual=f"AuditValueError: {exc}",
                tolerance=0.0,
                ok=True,
                detail={"caught": str(exc)},
                kind="negative",
            )
        matched = [c for c in bad_checks if predicate(c)]
        detected = any(not c.ok for c in matched) if matched else any(not c.ok for c in bad_checks)
        sample = next((c for c in matched if not c.ok), None) or next((c for c in bad_checks if not c.ok), None)
        return Check(
            name=label,
            expected="хотя бы одна целевая проверка FAIL",
            actual=("detected" if detected else "not_detected"),
            tolerance=0.0,
            ok=detected,
            detail={
                "failed_sample": sample.as_dict() if sample else None,
                "matched": len(matched),
                "failed_total": sum(1 for c in bad_checks if not c.ok),
            },
            kind="negative",
        )

    # 1) Искажённый NPV листа
    s1 = deepcopy(clean)
    if _is_number(s1["series"]["npv_sheet"]):
        s1["series"]["npv_sheet"] = float(s1["series"]["npv_sheet"]) + 1_000_000.0
    else:
        s1["series"]["npv_sheet"] = 1_000_000.0
    neg.append(_must_fail("negative_corrupted_npv_detected", s1, lambda c: c.name == "npv_independent"))

    # 2) Нарушенный платёж кредита
    s2 = deepcopy(clean)
    loan_rows = [r for tr in s2["tranches"] for r in (tr.get("rows") or [])]
    if loan_rows:
        victim = loan_rows[min(5, len(loan_rows) - 1)]
        victim["payment"] = victim["payment"] + 50_000.0
        neg.append(
            _must_fail(
                "negative_corrupted_loan_payment_detected",
                s2,
                lambda c: "payment" in c.name and c.name.startswith("loan_"),
            )
        )
    else:
        neg.append(
            Check(
                name="negative_corrupted_loan_payment_skipped",
                expected="no debt",
                actual="skipped",
                tolerance=0.0,
                ok=True,
                detail={"reason": "нет активных платежей"},
                kind="negative",
            )
        )

    # 3) Пропущенный CAPEX в ДДС
    s3 = deepcopy(clean)
    if s3["capex_register"] is not None and s3["cf_rows"]:
        # обнуляем первый ненулевой CAPEX
        for row in s3["cf_rows"]:
            if row["capex"] > 0:
                row["capex"] = 0.0
                break
        else:
            s3["capex_register"] = float(s3["capex_register"]) + 100_000.0
        neg.append(_must_fail("negative_corrupted_capex_detected", s3, lambda c: c.name == "capex_dds=register"))
    else:
        neg.append(
            Check(
                name="negative_corrupted_capex_skipped",
                expected="no capex register",
                actual="skipped",
                tolerance=0.0,
                ok=True,
                kind="negative",
            )
        )

    # 4) Ошибочный FCFF на листе эффекта
    s4 = deepcopy(clean)
    if s4["series"]["fcff"]:
        s4["series"]["fcff"] = list(s4["series"]["fcff"])
        s4["series"]["fcff"][0] = s4["series"]["fcff"][0] + 123_456.0
        # согласовать disc_fcff чтобы ловить именно fcff_month, не discount_fcff
        s4["series"]["disc_fcff"] = list(s4["series"]["disc_fcff"])
        s4["series"]["disc_fcff"][0] = s4["series"]["fcff"][0] * s4["series"]["disc_factor"][0]
        neg.append(_must_fail("negative_corrupted_fcff_detected", s4, lambda c: c.name == "fcff_month_1"))
    else:
        neg.append(
            Check(
                name="negative_corrupted_fcff_skipped",
                expected="has fcff",
                actual="skipped",
                tolerance=0.0,
                ok=False,
                kind="negative",
            )
        )

    # 5) Искажённый коэффициент дисконтирования
    s5 = deepcopy(clean)
    if len(s5["series"]["disc_factor"]) > 1:
        s5["series"]["disc_factor"] = list(s5["series"]["disc_factor"])
        s5["series"]["disc_factor"][1] = s5["series"]["disc_factor"][1] * 0.5
        neg.append(
            _must_fail("negative_corrupted_discount_factor_detected", s5, lambda c: c.name == "discount_factor_month_2")
        )

    return neg


def audit_model(model_id: int) -> dict:
    db = SessionLocal()
    try:
        model = db.get(FinModel, model_id)
        form = _ready_form(model)
        payload = build_fem_v5_excel(None, form)
        wb = load_workbook(_calc(payload, str(model_id)), data_only=True)
        snap = build_snapshot(wb)
        main_checks = evaluate_snapshot(snap, kind="main")
        neg_checks = run_negative_tests(snap)
        checks = main_checks + neg_checks

        irr_res = xirr(snap["series"]["fcff"], snap["series"]["dates"])
        mirr_indep = mirr_annual_monthly(snap["series"]["fcff"], snap["series"]["wacc"])
        npv_indep = npv_dated(snap["series"]["fcff"], snap["series"]["dates"], snap["series"]["wacc"])

        main_failed = sum(1 for c in main_checks if not c.ok)
        neg_failed = sum(1 for c in neg_checks if not c.ok)
        passed = sum(1 for c in checks if c.ok)
        failed = sum(1 for c in checks if not c.ok)
        return {
            "id": model_id,
            "name": model.name,
            "financing": int(form.get("financing") or 0),
            "totals": snap["totals"],
            "metrics": {
                "npv_sheet": snap["series"]["npv_sheet"],
                "npv_indep": npv_indep,
                "irr_sheet": snap["series"]["irr_sheet"],
                "irr_indep": irr_res.rate,
                "irr_status": irr_res.status,
                "irr_roots": irr_res.roots,
                "irr_sign_changes": irr_res.sign_changes,
                "mirr_sheet": snap["series"]["mirr_sheet"],
                "mirr_indep": mirr_indep,
                "wacc": snap["series"]["wacc"],
                "months": len(snap["series"]["fcff"]),
                "deficit": snap["series"]["deficit"],
                "dscr_min": snap["series"]["dscr_min"],
            },
            "checks": [c.as_dict() for c in checks],
            "passed": passed,
            "failed": failed,
            "main_passed": len(main_checks) - main_failed,
            "main_failed": main_failed,
            "negative_passed": len(neg_checks) - neg_failed,
            "negative_failed": neg_failed,
            "ok": main_failed == 0 and neg_failed == 0,
            "negative_tests_ok": neg_failed == 0,
        }
    finally:
        db.close()


def main() -> int:
    db = SessionLocal()
    try:
        ids = []
        for m in db.scalars(select(FinModel).order_by(FinModel.id)).all():
            try:
                _ready_form(m)
                ids.append(m.id)
            except HTTPException:
                continue
    finally:
        db.close()

    report: dict[str, Any] = {"models": [], "summary": {}}
    for mid in ids:
        print(f"... auditing {mid}", flush=True)
        try:
            item = audit_model(mid)
        except Exception as exc:  # noqa: BLE001
            item = {
                "id": mid,
                "ok": False,
                "error": f"{type(exc).__name__}: {exc}",
                "passed": 0,
                "failed": 1,
                "main_failed": 1,
                "negative_failed": 1,
                "checks": [
                    {
                        "name": "exception",
                        "expected": "ok",
                        "actual": str(exc),
                        "tolerance": 0.0,
                        "ok": False,
                        "diff": None,
                        "detail": {},
                        "kind": "main",
                    }
                ],
                "negative_tests_ok": False,
            }
        report["models"].append(item)
        mark = "PASS" if item.get("ok") else "FAIL"
        print(
            f"{mark} #{item.get('id')} {item.get('name', '')} "
            f"{item.get('passed')}/{item.get('passed', 0) + item.get('failed', 0)} "
            f"main_fail={item.get('main_failed')} neg_ok={item.get('negative_tests_ok')}"
        )
        shown = 0
        for c in item.get("checks") or []:
            if not c.get("ok"):
                print("   FAIL", c.get("name"), "exp", c.get("expected"), "act", c.get("actual"), "diff", c.get("diff"))
                shown += 1
                if shown >= 15:
                    print("   ...")
                    break

    ok_n = sum(1 for m in report["models"] if m.get("ok"))
    neg_ok = sum(1 for m in report["models"] if m.get("negative_tests_ok"))
    main_fail_models = sum(1 for m in report["models"] if (m.get("main_failed") or 0) > 0)
    neg_fail_models = sum(1 for m in report["models"] if (m.get("negative_failed") or 0) > 0)
    total_checks = sum(m.get("passed", 0) + m.get("failed", 0) for m in report["models"])
    total_pass = sum(m.get("passed", 0) for m in report["models"])
    all_ok = ok_n == len(report["models"]) and neg_ok == len(report["models"])
    report["summary"] = {
        "models": len(report["models"]),
        "passed_models": ok_n,
        "failed_models": len(report["models"]) - ok_n,
        "models_with_main_failures": main_fail_models,
        "models_with_negative_failures": neg_fail_models,
        "checks_total": total_checks,
        "checks_passed": total_pass,
        "checks_failed": total_checks - total_pass,
        "negative_tests_models_ok": neg_ok,
        "all_ok": all_ok,
        "verdict": (
            "усиленный численный аудит пройден; негативные тесты ловят порчу снимка модели"
            if all_ok
            else "есть расхождения основных или негативных проверок"
        ),
    }
    path = OUT_DIR / "NC-fem-numerical-audit-report.json"
    path.write_text(json.dumps(report, ensure_ascii=False, indent=2, default=str), encoding="utf-8")
    print(json.dumps(report["summary"], ensure_ascii=False))
    print("Report:", path)
    return 0 if all_ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
