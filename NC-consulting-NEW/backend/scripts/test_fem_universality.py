#!/usr/bin/env python3
"""Универсальность генератора ФЭМ v5: разные типы бизнеса, без переделки Excel.

Считает каждую модель через LibreOffice и проверяет сценарийно:
магазин / производство / IT / без кредита / с инвестором / кредит+субсидия.
"""

from __future__ import annotations

import json
import subprocess
import sys
import tempfile
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
    _staff_industrial,
    build_fem_v5_excel,
)
from app.models import FinModel  # noqa: E402
from app.routers.fin_models import _ready_form  # noqa: E402

CASES = (
    {
        "id": 7,
        "slug": "shop",
        "title": "Магазин / e-commerce",
        "expect": {"financing": 1, "has_credit": False, "has_investor": True},
    },
    {
        "id": 9,
        "slug": "manufacturing",
        "title": "Производство",
        "expect": {"financing": 2, "has_credit": True, "has_investor": False},
    },
    {
        "id": 10,
        "slug": "it_mixed",
        "title": "IT / сервис с инвестором и кредитом",
        "expect": {"financing": 4, "has_credit": True, "has_investor": True},
    },
    {
        "id": 12,
        "slug": "no_credit",
        "title": "Без кредита (инвестор + свои)",
        "expect": {"financing": 1, "has_credit": False, "has_investor": True},
    },
    {
        "id": 16,
        "slug": "credit_subsidy",
        "title": "Кредит с субсидией (медцентр)",
        "expect": {"financing": 2, "has_credit": True, "has_investor": False, "dual": True},
    },
    {
        "id": 11,
        "slug": "grant",
        "title": "Грант без кредита",
        "expect": {"financing": 3, "has_credit": False, "has_investor": False},
    },
)


def _near(a, b, tol=1.0) -> bool:
    try:
        return abs(float(a) - float(b)) <= tol
    except (TypeError, ValueError):
        return False


def _calc(payload: bytes, tag: str) -> Path:
    with tempfile.TemporaryDirectory(prefix=f"fem-uni-{tag}-") as folder:
        folder = Path(folder)
        src = folder / "in"
        out = folder / "out"
        src.mkdir()
        out.mkdir()
        source = src / "model.xlsx"
        source.write_bytes(payload)
        completed = subprocess.run(
            [
                "soffice",
                "--headless",
                "--norestore",
                "--nolockcheck",
                "--nologo",
                f"-env:UserInstallation=file://{folder}/profile",
                "--convert-to",
                "xlsx",
                "--outdir",
                str(out),
                str(source),
            ],
            capture_output=True,
            text=True,
            timeout=300,
        )
        calculated = out / "model.xlsx"
        if completed.returncode != 0 or not calculated.exists():
            raise RuntimeError(completed.stderr[-1500:] or "LibreOffice failed")
        target = Path(f"/tmp/fem_uni_{tag}_calc.xlsx")
        target.write_bytes(calculated.read_bytes())
        return target


def _fin_totals(wb) -> dict:
    fin = wb[FIN]
    # ищем строку ВСЕГО
    for row in range(1, 40):
        if str(fin.cell(row, 2).value or "").strip().upper().startswith("ВСЕГО"):
            return {
                "invest": float(fin.cell(row, 3).value or 0),
                "debt": float(fin.cell(row, 4).value or 0),
                "investor": float(fin.cell(row, 5).value or 0),
                "own": float(fin.cell(row, 6).value or 0),
                "grant": float(fin.cell(row, 7).value or 0),
                "row": row,
            }
    raise RuntimeError("FIN total row not found")


def _schedule_ok(wb, dual: bool, expect_credit: bool) -> list[dict]:
    checks = []
    sched = wb[SCHED]
    openings = [r for r in range(1, min(80, sched.max_row or 1)) if sched.cell(r, 1).value == "Выдача"]
    if not openings:
        return [{"check": "no schedule rows", "ok": not expect_credit}]
    for opening in openings:
        drawn = float(sched.cell(opening, 6).value or 0)
        next_label = str(sched.cell(opening + 1, 1).value or "")
        # Срок 0 / долг 0: строка «Платежей нет…», Итого нет — это норма для сценария без кредита.
        if next_label.startswith("Платежей нет") or drawn <= 1:
            checks.append(
                {
                    "check": "empty loan ok",
                    "ok": drawn <= 1,
                    "opening": opening,
                    "drawn": drawn,
                    "note": next_label[:80],
                }
            )
            continue
        total = None
        for r in range(opening + 1, opening + 400):
            if sched.cell(r, 1).value == "Итого":
                total = r
                break
        if total is None:
            checks.append({"check": f"total after opening {opening}", "ok": False, "drawn": drawn})
            continue
        principal = float(sched.cell(total, 4).value or 0)
        balance = float(sched.cell(total, 6).value or 0)
        ok = _near(principal, drawn, 1) and abs(balance) < 1
        checks.append(
            {
                "check": "loan closes",
                "ok": ok,
                "opening": opening,
                "drawn": drawn,
                "principal": principal,
                "balance": balance,
            }
        )
        for offset in range(1, 6):
            label = sched.cell(total + offset, 1).value
            text = str(sched.cell(total + offset, 3).value or "")
            if not label:
                break
            good = any(
                bit in text
                for bit in (
                    "по сроку",
                    "равно сумме кредита",
                    "ноль",
                    "равны телу и процентам",
                    "отрицательного нет",
                )
            )
            checks.append({"check": f"sheet:{label}", "ok": good, "value": text})
        if dual and drawn > 0:
            has_block = any(
                sched.cell(r, 10).value == "Условия транша" for r in range(1, opening + 5)
            )
            checks.append({"check": "dual conditions block", "ok": has_block})
    return checks


def _fcff_excludes_funding(wb) -> dict:
    cf = wb[CF]
    effect = wb[EFFECT]
    header = None
    for r in range(1, 80):
        if cf.cell(r, 1).value == "Дата" and cf.cell(r, 15).value:
            header = r
            break
    if header is None:
        return {"ok": False, "reason": "CF header not found"}
    # первый месяц
    r = header + 1
    fund = float(cf.cell(r, 15).value or 0)
    capex = float(cf.cell(r, 13).value or 0)
    fcff = float(effect.cell(24, 2).value or 0)
    # независимо: без финансирования и без тела/процентов (щит 0 или проценты ещё 0)
    receipts = float(cf.cell(r, 4).value or 0)
    pay_sup = float(cf.cell(r, 6).value or 0)
    var = float(cf.cell(r, 7).value or 0)
    fot = float(cf.cell(r, 8).value or 0)
    fot_tax = float(cf.cell(r, 9).value or 0)
    adm = float(cf.cell(r, 10).value or 0)
    tax = float(cf.cell(r, 12).value or 0)
    indep = receipts - pay_sup - var - fot - fot_tax - adm - tax - capex
    return {
        "ok": _near(fcff, indep, 1) and (fund == 0 or abs(fcff + fund) > 1 or capex > 0),
        "funding": fund,
        "fcff": fcff,
        "indep": indep,
        "capex": capex,
        # главное: FCFF не равен funding и не включает его как приток
        "funding_not_in_fcff": not _near(fcff, fund, 1),
    }


def _investor_gate(wb, has_investor: bool) -> dict:
    effect = wb[EFFECT]
    val = effect.cell(5, 3).value
    if has_investor:
        ok = isinstance(val, (int, float)) or (isinstance(val, str) and val not in ("Не применяется", "не применяется"))
        # при инвесторе NPV может быть текстом только если нет смены знака — допускаем число или осмысленный текст
        if isinstance(val, str) and val == "Не применяется":
            ok = False
        return {"ok": ok, "investor_npv": val, "mode": "with_investor"}
    return {"ok": str(val) == "Не применяется", "investor_npv": val, "mode": "no_investor"}


def _staff_split(form: dict) -> dict:
    people = [item for item in (form.get("salary") or []) if str(item.get("name") or "").strip()]
    prod = [p for p in people if _staff_industrial(p)]
    admin = [p for p in people if not _staff_industrial(p)]
    return {"total": len(people), "production": len(prod), "admin": len(admin)}


def run_case(case: dict) -> dict:
    db = SessionLocal()
    model = db.get(FinModel, case["id"])
    form = _ready_form(model)
    expect = case["expect"]
    financing = int(form.get("financing") or 0)
    payload = build_fem_v5_excel(None, form)
    out_xlsx = Path(f"/var/www/html/nc-desicions/NC-fem-uni-{case['slug']}.xlsx")
    out_xlsx.write_bytes(payload)
    wb = load_workbook(_calc(payload, case["slug"]), data_only=True)

    result = {
        "id": case["id"],
        "slug": case["slug"],
        "title": case["title"],
        "name": model.name,
        "financing": financing,
        "file": str(out_xlsx),
        "checks": [],
        "metrics": {},
    }

    def add(name: str, ok: bool, **extra):
        extra.pop("ok", None)
        extra.pop("check", None)
        result["checks"].append({"check": name, "ok": bool(ok), **extra})

    add("build+calc", True, bytes=len(payload))
    add("sheets", all(s in wb.sheetnames for s in (SHEET, FIN, PNL, CF, EFFECT, SCHED)))
    add("financing_code", financing == expect["financing"], got=financing, expected=expect["financing"])

    totals = _fin_totals(wb)
    result["metrics"]["financing"] = totals
    sources = totals["debt"] + totals["investor"] + totals["own"] + totals["grant"]
    add("sources=invest", _near(sources, totals["invest"], 1), sources=sources, invest=totals["invest"])
    add("credit_flag", (totals["debt"] > 1) == expect["has_credit"], debt=totals["debt"])
    add("investor_flag", (totals["investor"] > 1) == expect["has_investor"], investor=totals["investor"])

    # кредит
    dual = bool(expect.get("dual"))
    for item in _schedule_ok(wb, dual=dual, expect_credit=expect["has_credit"]):
        add(item["check"], item["ok"], **{k: v for k, v in item.items() if k not in ("check", "ok")})

    if expect["has_credit"] and totals["debt"] > 1:
        loan_checks = [c for c in result["checks"] if c["check"] == "loan closes"]
        add("has_closing_loan", any(c["ok"] for c in loan_checks), found=len(loan_checks))
    else:
        add("no_credit_schedule_ok", True)

    inv = _investor_gate(wb, expect["has_investor"])
    add(
        "investor_metrics",
        inv["ok"],
        investor_npv=inv.get("investor_npv"),
        mode=inv.get("mode"),
    )

    fcff = _fcff_excludes_funding(wb)
    add(
        "fcff_vs_indep",
        _near(fcff.get("fcff"), fcff.get("indep"), 1),
        fcff=fcff.get("fcff"),
        indep=fcff.get("indep"),
        funding=fcff.get("funding"),
        capex=fcff.get("capex"),
    )
    add(
        "fcff_excludes_funding",
        bool(fcff.get("funding_not_in_fcff")) or float(fcff.get("funding") or 0) == 0,
        funding=fcff.get("funding"),
        fcff=fcff.get("fcff"),
    )

    effect = wb[EFFECT]
    npv = effect.cell(5, 2).value
    irr = effect.cell(6, 2).value
    dscr = effect.cell(10, 2).value
    deficit = effect.cell(11, 2).value
    result["metrics"].update(
        {
            "npv": npv if isinstance(npv, (int, float)) else str(npv),
            "irr": irr if isinstance(irr, (int, float)) else str(irr),
            "dscr_min": dscr if isinstance(dscr, (int, float)) else str(dscr),
            "deficit": float(deficit) if isinstance(deficit, (int, float)) else None,
            "wacc": wb[SHEET].cell(25, 3).value,
            "staff": _staff_split(form),
        }
    )
    add("project_npv_number", isinstance(npv, (int, float)), value=npv)

    # CAPEX: если есть активы — даты оплаты не все «формула года→январь» без явных месяцев не требуем;
    # проверяем что лист существует и итого к оплате >= 0
    cap = wb[CAPEX]
    if CAPEX in wb.sheetnames:
        # строка итого
        total_pay = None
        for r in range(2, 40):
            if str(cap.cell(r, 1).value or "") == "Итого":
                total_pay = float(cap.cell(r, 13).value or 0)
                break
        add("capex_total_nonneg", total_pay is not None and total_pay >= -1, to_pay=total_pay)

    result["passed"] = sum(1 for c in result["checks"] if c["ok"])
    result["failed"] = sum(1 for c in result["checks"] if not c["ok"])
    result["ok"] = result["failed"] == 0
    return result


def main() -> int:
    report = {"cases": [], "summary": {}}
    for case in CASES:
        try:
            item = run_case(case)
        except Exception as exc:  # noqa: BLE001
            item = {
                "id": case["id"],
                "slug": case["slug"],
                "title": case["title"],
                "ok": False,
                "error": f"{type(exc).__name__}: {exc}",
                "passed": 0,
                "failed": 1,
                "checks": [{"check": "exception", "ok": False, "error": str(exc)}],
            }
        report["cases"].append(item)
        status = "PASS" if item.get("ok") else "FAIL"
        print(f"{status} [{case['slug']}] {case['title']} — {item.get('name', '')} "
              f"passed={item.get('passed')} failed={item.get('failed')}")
        for c in item.get("checks") or []:
            if not c.get("ok"):
                print("   FAIL", c)

    ok_n = sum(1 for c in report["cases"] if c.get("ok"))
    report["summary"] = {
        "cases": len(report["cases"]),
        "passed_cases": ok_n,
        "failed_cases": len(report["cases"]) - ok_n,
        "all_ok": ok_n == len(report["cases"]),
        "verdict": "универсальность подтверждена" if ok_n == len(report["cases"]) else "есть провалы по сценариям",
    }
    out = Path("/var/www/html/nc-desicions/NC-fem-universality-report.json")
    out.write_text(json.dumps(report, ensure_ascii=False, indent=2, default=str), encoding="utf-8")
    print(json.dumps(report["summary"], ensure_ascii=False))
    print("Report:", out)
    return 0 if report["summary"]["all_ok"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
