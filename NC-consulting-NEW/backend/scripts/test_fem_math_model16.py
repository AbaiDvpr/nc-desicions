#!/usr/bin/env python3
"""Численные проверки ФЭМ v5 на модели 16 (кредитный медцентр).

Считает Excel через LibreOffice Calc и сверяет кредит, CAPEX, ОПиУ, ДДС, FCFF, WACC, инвестора.
"""

from __future__ import annotations

import json
import subprocess
import sys
import tempfile
from collections import defaultdict
from pathlib import Path

from openpyxl import load_workbook

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from app.database import SessionLocal  # noqa: E402
from app.fem_v5_book import (  # noqa: E402
    CAPEX,
    CF,
    EFFECT,
    FIN,
    PNL,
    SCHED,
    SHEET,
    _debt_rate,
    build_fem_v5_excel,
)
from app.models import FinModel  # noqa: E402
from app.routers.fin_models import _ready_form  # noqa: E402


def _calc(payload: bytes) -> Path:
    with tempfile.TemporaryDirectory(prefix="fem-math-") as folder:
        folder = Path(folder)
        src_dir = folder / "in"
        out_dir = folder / "out"
        profile = folder / "profile"
        src_dir.mkdir()
        out_dir.mkdir()
        source = src_dir / "model.xlsx"
        source.write_bytes(payload)
        completed = subprocess.run(
            [
                "soffice",
                "--headless",
                "--norestore",
                "--nolockcheck",
                "--nologo",
                f"-env:UserInstallation=file://{profile}",
                "--convert-to",
                "xlsx",
                "--outdir",
                str(out_dir),
                str(source),
            ],
            capture_output=True,
            text=True,
            timeout=300,
        )
        calculated = out_dir / "model.xlsx"
        if completed.returncode != 0 or not calculated.exists():
            raise RuntimeError(completed.stderr[-2000:] or "LibreOffice convert failed")
        target = Path("/tmp/fem_v5_model16_math_calc.xlsx")
        target.write_bytes(calculated.read_bytes())
        return target


def _near(a, b, tol=0.05) -> bool:
    try:
        return abs(float(a) - float(b)) <= tol
    except (TypeError, ValueError):
        return False


def main() -> int:
    form = _ready_form(SessionLocal().get(FinModel, 16))
    payload = build_fem_v5_excel(None, form)
    raw_path = Path("/var/www/html/nc-desicions/NC-fem-v5-16.xlsx")
    raw_path.write_bytes(payload)
    stamped = Path("/var/www/html/nc-desicions/NC-fem-v5-16-math-tested.xlsx")
    stamped.write_bytes(payload)

    wb = load_workbook(_calc(payload), data_only=True)
    report: dict = {"ok": [], "fail": [], "files": {"raw": str(raw_path), "stamped": str(stamped)}}

    def ok(msg: str, **extra):
        report["ok"].append({"check": msg, **extra})

    def fail(msg: str, **extra):
        report["fail"].append({"check": msg, **extra})

    # --- WACC ---
    expected_debt = (0.126 * 36 + 0.2025 * 48) / 84
    debt = _debt_rate(form)
    if _near(debt, expected_debt, 1e-9):
        ok("WACC debt rate time-weighted", value=debt, expected=expected_debt)
    else:
        fail("WACC debt rate", value=debt, expected=expected_debt)
    wacc = wb[SHEET].cell(25, 3).value
    if isinstance(wacc, (int, float)) and wacc > 0.1:
        ok("WACC C25 calculated", value=wacc)
    else:
        fail("WACC C25", value=wacc)

    # --- CAPEX dates ---
    cap = wb[CAPEX]
    expected_capex = {
        3: ("2026-11", "2026-11", 131_000_000),
        4: ("2027-01", "2027-01", 2_000_000),
        5: ("2027-01", "2027-03", 13_000_000),
    }
    for row, (pay, commission, amount) in expected_capex.items():
        p = cap.cell(row, 9).value
        c = cap.cell(row, 10).value
        to_pay = cap.cell(row, 13).value
        pay_ok = p and p.strftime("%Y-%m") == pay
        com_ok = c and c.strftime("%Y-%m") == commission
        amt_ok = _near(to_pay, amount, 1)
        if pay_ok and com_ok and amt_ok:
            ok(f"CAPEX row {row} dates/amount", name=cap.cell(row, 1).value, pay=pay, commission=commission)
        else:
            fail(
                f"CAPEX row {row}",
                name=cap.cell(row, 1).value,
                pay=str(p),
                commission=str(c),
                to_pay=to_pay,
                expected=(pay, commission, amount),
            )

    # --- Credit 84 months ---
    sched = wb[SCHED]
    opening = next(r for r in range(1, 40) if sched.cell(r, 1).value == "Выдача")
    first = opening + 1
    last = first + 83
    total = last + 1
    principal = float(sched.cell(total, 4).value or 0)
    client_int = float(sched.cell(total, 5).value or 0)
    bank_int = float(sched.cell(total, 7).value or 0)
    subsidy = float(sched.cell(total, 8).value or 0)
    balance = float(sched.cell(total, 6).value or 0)
    drawn = float(sched.cell(opening, 6).value or 0)

    if _near(drawn, 80_000_000, 1) and _near(principal, 80_000_000, 1) and abs(balance) < 1:
        ok("Credit principal closes", drawn=drawn, principal=principal, balance=balance)
    else:
        fail("Credit principal", drawn=drawn, principal=principal, balance=balance)

    if _near(bank_int - client_int, subsidy, 1):
        ok("Subsidy = bank% - client%", bank=bank_int, client=client_int, subsidy=subsidy)
    else:
        fail("Subsidy identity", bank=bank_int, client=client_int, subsidy=subsidy)

    row_errs = 0
    rate_errs = 0
    for n in range(84):
        r = first + n
        pay = float(sched.cell(r, 3).value or 0)
        od = float(sched.cell(r, 4).value or 0)
        cl = float(sched.cell(r, 5).value or 0)
        bk = float(sched.cell(r, 7).value or 0)
        sub = float(sched.cell(r, 8).value or 0)
        if abs(pay - (od + cl)) > 0.05:
            row_errs += 1
        if n < 36:
            if abs(sub - (bk - cl)) > 0.1:
                row_errs += 1
            prev = float(sched.cell(r - 1, 6).value or 0)
            if prev and abs(cl / prev * 12 - 0.126) > 1e-6:
                rate_errs += 1
        else:
            if abs(sub) > 0.05 or abs(cl - bk) > 0.05:
                row_errs += 1
            prev = float(sched.cell(r - 1, 6).value or 0)
            if prev and abs(cl / prev * 12 - 0.2025) > 1e-6:
                rate_errs += 1

    if row_errs == 0:
        ok("Credit 84 months payment identities")
    else:
        fail("Credit row identities", errors=row_errs)
    if rate_errs == 0:
        ok("Client rate 12.6% then 20.25%")
    else:
        fail("Client rate switch", errors=rate_errs)

    for offset, label in enumerate(("Строки", "Тело", "Остаток", "Взносы", "Сальдо"), start=1):
        text = str(sched.cell(total + offset, 3).value or "")
        good = text in ("по сроку", "равно сумме кредита", "ноль", "равны телу и процентам", "отрицательного нет")
        (ok if good else fail)(f"Schedule check {label}", value=text)

    # grace panel
    grace_interest = next(
        (sched.cell(r, 12).value for r in range(1, 40) if sched.cell(r, 10).value == "Начислено % во время льготного периода"),
        None,
    )
    if _near(grace_interest, 8_100_000, 1):
        ok("Grace interest 8.1M", value=grace_interest)
    else:
        fail("Grace interest", value=grace_interest)

    # --- PNL vs schedule interest ---
    by_year: dict[int, float] = defaultdict(float)
    for n in range(84):
        r = first + n
        d = sched.cell(r, 2).value
        if d:
            by_year[d.year] += float(sched.cell(r, 5).value or 0)
    years = []
    col = 2
    while isinstance(wb[PNL].cell(4, col).value, (int, float)):
        years.append(int(wb[PNL].cell(4, col).value))
        col += 1
    pnl_errs = 0
    for i, year in enumerate(years):
        pnl_v = float(wb[PNL].cell(17, 2 + i).value or 0)
        if abs(pnl_v - by_year.get(year, 0.0)) > 0.05:
            pnl_errs += 1
            fail("PNL interest vs schedule", year=year, pnl=pnl_v, schedule=by_year.get(year, 0.0))
    if pnl_errs == 0:
        ok("PNL interest = schedule client% by year", years=years)

    # --- DDS vs schedule ---
    cf = wb[CF]
    header = next(r for r in range(1, 50) if cf.cell(r, 1).value == "Дата" and cf.cell(r, 11).value)
    dds_errs = 0
    for n in range(84):
        sr = first + n
        sdate = sched.cell(sr, 2).value
        # pay #1 is one month after issue; CF starts at issue month
        cr = header + 1 + (n + 1)
        if cf.cell(cr, 1).value != sdate:
            # search nearby
            found = None
            for rr in range(header + 1, header + 1 + 130):
                if cf.cell(rr, 1).value == sdate:
                    found = rr
                    break
            cr = found
        if cr is None:
            dds_errs += 1
            continue
        if not _near(cf.cell(cr, 11).value, sched.cell(sr, 5).value, 0.05):
            dds_errs += 1
        if not _near(cf.cell(cr, 14).value, sched.cell(sr, 4).value, 0.05):
            dds_errs += 1
    if dds_errs == 0:
        ok("DDS interest/principal = schedule for 84 months")
    else:
        fail("DDS vs schedule", errors=dds_errs)

    # CAPEX cash timing
    capex_by_month = {}
    for i in range(0, 120):
        r = header + 1 + i
        val = float(cf.cell(r, 13).value or 0)
        if val > 0:
            capex_by_month[cf.cell(r, 1).value.strftime("%Y-%m")] = val
    if _near(capex_by_month.get("2026-11", 0), 131_000_000, 1) and _near(capex_by_month.get("2027-01", 0), 15_000_000, 1):
        ok("DDS CAPEX cash dates", months=capex_by_month)
    else:
        fail("DDS CAPEX cash dates", months=capex_by_month)

    # Funding only in first month; FCFF excludes it
    fund0 = float(cf.cell(header + 1, 15).value or 0)
    fcff0 = float(wb[EFFECT].cell(24, 2).value or 0)
    if _near(fund0, 146_000_000, 1) and _near(fcff0, -131_000_000, 1):
        ok("FCFF excludes loan/own funding", funding=fund0, fcff0=fcff0)
    else:
        fail("FCFF vs funding", funding=fund0, fcff0=fcff0)

    # Investor metrics: summary C5:C12; side block B19:B22
    inv_ok = True
    for row, col in (
        (5, 3),
        (6, 3),
        (7, 3),
        (8, 3),
        (9, 3),
        (12, 3),
        (19, 2),
        (20, 2),
        (21, 2),
        (22, 2),
    ):
        val = wb[EFFECT].cell(row, col).value
        if str(val) != "Не применяется":
            inv_ok = False
            fail("Investor metric not gated", row=row, col=col, value=val)
    if inv_ok:
        ok("Investor metrics = Не применяется")

    # Project metrics exist
    npv = wb[EFFECT].cell(5, 2).value
    irr = wb[EFFECT].cell(6, 2).value
    if isinstance(npv, (int, float)) and isinstance(irr, (int, float)):
        ok("Project NPV/IRR present", npv=npv, irr=irr)
    else:
        fail("Project NPV/IRR", npv=npv, irr=irr)

    fin = wb[FIN]
    if _near(fin.cell(8, 4).value, 80_000_000, 1) and _near(fin.cell(8, 5).value, 0, 1) and _near(fin.cell(8, 6).value, 66_000_000, 1):
        ok("Financing structure 80/0/66")
    else:
        fail("Financing structure", debt=fin.cell(8, 4).value, investor=fin.cell(8, 5).value, own=fin.cell(8, 6).value)

    report["summary"] = {
        "passed": len(report["ok"]),
        "failed": len(report["fail"]),
        "score_hint": "9/10+" if not report["fail"] else "needs fixes",
    }
    out = Path("/var/www/html/nc-desicions/NC-fem-v5-16-math-report.json")
    out.write_text(json.dumps(report, ensure_ascii=False, indent=2, default=str), encoding="utf-8")
    print(json.dumps(report["summary"], ensure_ascii=False))
    for item in report["fail"]:
        print("FAIL", item)
    for item in report["ok"]:
        print("OK", item["check"])
    print("Excel:", raw_path)
    print("Report:", out)
    return 1 if report["fail"] else 0


if __name__ == "__main__":
    raise SystemExit(main())
