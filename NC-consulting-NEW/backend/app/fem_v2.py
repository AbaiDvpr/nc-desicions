"""ФЭМ версия 2 — расчёт по «Логика расчёта ФЭМ, v3» от 4 ноября 2026.

Книга Excel: жёлтые ячейки — анкета, остальные числа — формулы.
ОПиУ, ДДС и анализ берут готовые строки предыдущих листов.
Горизонт — годы анкеты. Кредит и первый год считаются помесячно
и сворачиваются в годы на листе «Помесячный расчёт».
"""

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime

from app.models import User

# Ставки справочника. Жёлтые ячейки в файле — те же числа, их можно сверить.
CIT_SIMPLIFIED = 0.04
CIT_GENERAL = 0.20
VAT_RATE = 0.16
SOCIAL_CONTRIB = 0.05
SOCIAL_BASE = 0.90
OOSMS = 0.03
SOCIAL_TAX_GENERAL = 0.06
SOCIAL_TAX_SIMPLIFIED = 0.0
PROPERTY_TAX = 0.005
NBK_BASE = 0.1525
DSCR_MIN = 1.2
SIMPLIFIED_LIMIT = 600_000_000
TYPICAL_MARGIN = 0.40
REINVEST_FOLLOWS_WACC = True

CAPEX_KEYS = ("smr", "overhaul", "ready_premises", "equipment")
OPERATING_KEYS = ("raw", "advertising", "wages", "rental", "other")
KEY_LABELS = {
    "smr": "Работы СМР и капитальный ремонт",
    "overhaul": "Материалы при СМР и ремонте",
    "ready_premises": "Помещения, здания, сооружения",
    "equipment": "Оборудование, техника, мебель",
    "raw": "Запас сырья и товаров на старте",
    "advertising": "Реклама на инвестпериод",
    "wages": "Зарплата на инвестпериод",
    "rental": "Аренда на инвестпериод",
    "other": "Прочие расходы на инвестпериод",
}
ASSET_LISTS = (
    ("equipment", "equipment", True),
    ("premises", "ready_premises", True),
    ("smr", "smr", True),
    ("overhaul", "overhaul", True),
)


def _num(value) -> float:
    try:
        return float(value or 0)
    except (TypeError, ValueError):
        return 0.0


def _month(value: str) -> tuple[int, int] | None:
    text = str(value or "")
    if len(text) < 7 or text[4] != "-":
        return None
    year, month = int(text[:4]), int(text[5:7])
    if month < 1 or month > 12:
        return None
    return year, month


def _add_months(year: int, month: int, shift: int) -> tuple[int, int]:
    index = year * 12 + (month - 1) + shift
    return index // 12, index % 12 + 1


def _months_between(start: tuple[int, int], end: tuple[int, int]) -> list[tuple[int, int]]:
    year, month = start
    items = []
    while (year, month) <= end and len(items) < 240:
        items.append((year, month))
        month += 1
        if month == 13:
            month = 1
            year += 1
    return items


def _opvr(year: int) -> float:
    if year >= 2028:
        return 0.05
    if year >= 2027:
        return 0.045
    if year >= 2026:
        return 0.035
    if year >= 2025:
        return 0.025
    return 0.015


def pmt(rate: float, nper: int, pv: float) -> float:
    if nper <= 0 or pv == 0:
        return 0.0
    if abs(rate) < 1e-12:
        return pv / nper
    growth = (1 + rate) ** nper
    return pv * rate * growth / (growth - 1)


def indexes(years: list[int], growth: float) -> tuple[dict[int, float], dict[int, float]]:
    """I(1) = 1, дальше I(t) = 1 + r(t). K(1) = 1, K(t) = K(t-1) × I(t)."""
    period: dict[int, float] = {}
    cumulative: dict[int, float] = {}
    previous = 1.0
    for index, year in enumerate(years):
        period[year] = 1.0 if index == 0 else 1.0 + growth
        cumulative[year] = 1.0 if index == 0 else previous * period[year]
        previous = cumulative[year]
    return period, cumulative


def wacc(cost_of_equity: float, cost_of_debt: float, equity: float, debt: float, tax: float) -> float:
    base = equity + debt
    if base <= 0:
        return cost_of_equity
    return cost_of_equity * equity / base + cost_of_debt * (1 - tax) * debt / base


def irr(cashflows: list[float]) -> float | None:
    if len(cashflows) < 2:
        return None
    if not any(item > 1 for item in cashflows) or not any(item < -1 for item in cashflows):
        return None
    low, high = -0.9, 10.0
    def npv(rate: float) -> float:
        return sum(cf / (1 + rate) ** t for t, cf in enumerate(cashflows))
    if npv(low) * npv(high) > 0:
        return None
    for _ in range(80):
        mid = (low + high) / 2
        if npv(mid) > 0:
            low = mid
        else:
            high = mid
    return (low + high) / 2


def mirr(cashflows: list[float], finance: float, reinvest: float) -> float | None:
    count = len(cashflows)
    if count < 2:
        return None
    negative = 0.0
    positive = 0.0
    for index, amount in enumerate(cashflows):
        if amount < 0:
            negative += amount / (1 + finance) ** index
        elif amount > 0:
            positive += amount * (1 + reinvest) ** (count - 1 - index)
    if negative >= -1 or positive <= 1:
        return None
    return (positive / -negative) ** (1 / (count - 1)) - 1


def payback(cashflows: list[float]) -> float | None:
    cumulative = 0.0
    for index, amount in enumerate(cashflows):
        previous = cumulative
        cumulative += amount
        if previous < 0 <= cumulative:
            if amount <= 0:
                return None
            return index + abs(previous) / amount
        if index == 0 and cumulative >= 0 and amount > 0:
            return 0.0
    return None


def loan_schedule(
    principal: float,
    annual_rate: float,
    term_months: int,
    grace: int,
    annuity: bool,
    start: tuple[int, int],
    subsidy_on: bool,
    client_rate: float,
    subsidy_months: int,
) -> list[dict]:
    """Платёж k — через k месяцев после выдачи. Льгота: проценты со всей суммы, тело 0."""
    if principal <= 0 or term_months <= 0:
        return []
    rate = annual_rate / 12
    grace = max(0, min(grace, term_months))
    paying = term_months - grace
    payment = pmt(rate, paying, principal) if annuity else 0.0
    balance = principal
    rows = []
    year, month = start
    for step in range(1, term_months + 1):
        year, month = _add_months(year, month, 1) if step > 1 else _add_months(*start, 1)
        if step == 1:
            year, month = _add_months(*start, 1)
        bank_interest = balance * rate
        if step <= grace or paying <= 0:
            principal_pay = 0.0
        elif step == term_months:
            principal_pay = balance
        elif annuity:
            principal_pay = min(balance, max(0.0, payment - bank_interest))
        else:
            principal_pay = min(balance, principal / paying)
        if subsidy_on and annual_rate > 0 and step <= subsidy_months:
            client_interest = bank_interest * client_rate / annual_rate
        else:
            client_interest = bank_interest
        balance = max(0.0, balance - principal_pay)
        rows.append(
            {
                "year": year,
                "month": month,
                "opening": balance + principal_pay,
                "bank_interest": bank_interest,
                "client_interest": client_interest,
                "subsidy": bank_interest - client_interest,
                "principal": principal_pay,
                "payment": principal_pay + client_interest,
                "closing": balance,
            }
        )
    if rows:
        rows[-1]["principal"] += rows[-1]["closing"]
        rows[-1]["payment"] += rows[-1]["closing"]
        rows[-1]["closing"] = 0.0
    return rows


@dataclass
class CalcOptions:
    revenue_factor: float = 1.0
    rate_add: float = 0.0
    subsidy_on: bool = False
    launch_shift: int = 0
    growth: float = 0.0
    stock_days: float = 0.0
    receivable_days: float = 0.0
    payable_days: float = 0.0


@dataclass
class FemResult:
    name: str
    currency: str
    years: list[int]
    assumptions: dict
    funding_rows: list[dict]
    loans: list[dict]
    revenue_rows: list[dict]
    seasonality: list[float]
    payroll_rows: list[dict]
    admin_rows: list[dict]
    assets: list[dict]
    pnl: dict[str, list[float]]
    cash: dict[str, list[float]]
    monthly: list[dict]
    metrics: dict
    checks: list[dict] = field(default_factory=list)
    scenarios: list[dict] = field(default_factory=list)


def _load_by_year(years: list[int], capacity: dict, sales: tuple[int, int], full: tuple[int, int]) -> dict[int, float]:
    entered = {int(year): _num(value) for year, value in (capacity or {}).items() if str(year).isdigit()}
    if years and any(entered.get(year, 0) > 0 for year in years):
        return {year: max(0.0, entered.get(year, 0)) / 100 for year in years}
    span = max(1, full[0] - sales[0] + 1)
    loads = {}
    for year in years:
        if year < sales[0]:
            loads[year] = 0.0
        elif year >= full[0]:
            loads[year] = 1.0
        else:
            loads[year] = (year - sales[0] + 1) / span
    return loads


def _season(months: list, seasonal: bool) -> list[float]:
    chosen = sorted({int(item) for item in (months or []) if 1 <= int(item) <= 12})
    if not seasonal or not chosen:
        return [1.0] * 12
    weight = 12 / len(chosen)
    return [weight if index + 1 in chosen else 0.0 for index in range(12)]


def calculate(form: dict, options: CalcOptions | None = None) -> FemResult:
    options = options or CalcOptions()
    start = _month(form.get("planning_from")) or (datetime.now().year, 1)
    end = _month(form.get("planning_to")) or _add_months(*start, 35)
    if end < start:
        end = start
    sales = _month(form.get("sales_start")) or start
    full = _month(form.get("planned_volume")) or end
    sales = _add_months(*sales, options.launch_shift)
    full = _add_months(*full, options.launch_shift)
    calendar = _months_between(start, end)
    years = []
    for year, _month_number in calendar:
        if year not in years:
            years.append(year)
    years = years[:20]

    general = int(form.get("tax_regime") or 4) == 1
    vat_payer = general and int(form.get("nds") or 0) == 1
    vat = VAT_RATE if vat_payer else 0.0
    tax_shield = CIT_GENERAL if general else 0.0
    cit_rate = CIT_GENERAL if general else CIT_SIMPLIFIED
    financing = int(form.get("financing") or 1)
    own_on = int(form.get("own_invest") or 0) == 1
    own = form.get("own") or {}
    attracted = form.get("attracted") or {}
    time_param = int(form.get("time_param") or 3)

    _, price_k = indexes(years, options.growth)
    _, cost_k = indexes(years, options.growth)
    _, fixed_k = indexes(years, options.growth)
    _, wage_k = indexes(years, options.growth)
    _, capex_k = indexes(years, options.growth)

    def own_of(key: str) -> float:
        return _num(own.get(key)) if own_on else 0.0

    def ext_of(key: str) -> float:
        return _num(attracted.get(key))

    funding_rows: list[dict] = []
    assets: list[dict] = []
    for list_name, key, depreciable in ASSET_LISTS:
        rows = [row for row in (form.get(list_name) or []) if str(row.get("name") or "").strip()]
        listed = sum(_num(row.get("price")) or _num(row.get("price_one")) * _num(row.get("count")) for row in rows)
        for row in rows:
            cost = _num(row.get("price")) or _num(row.get("price_one")) * _num(row.get("count"))
            if cost <= 0:
                continue
            share = cost / listed if listed else 0
            year = int(_num(row.get("year"))) or start[0]
            # В анкете только год. Для помесячной кассы берётся первый месяц этого года внутри плана.
            in_year = [item for item in calendar if item[0] == year]
            if in_year:
                moment = in_year[0]
            elif (year, 1) < start:
                moment = start
            else:
                moment = (year, 1)
            life = _num(row.get("life_years"))
            item = {
                "name": str(row["name"]).strip(),
                "cost": cost,
                "year": moment[0],
                "month": moment[1],
                "own": own_of(key) * share,
                "external": ext_of(key) * share,
                "depreciable": depreciable and life > 0,
                "life": life,
                "key": key,
            }
            assets.append(item)
            funding_rows.append(item)
    for key in OPERATING_KEYS:
        amount_own = own_of(key)
        amount_ext = ext_of(key)
        if amount_own + amount_ext <= 0:
            continue
        funding_rows.append(
            {
                "name": KEY_LABELS[key],
                "cost": amount_own + amount_ext,
                "year": sales[0],
                "month": sales[1],
                "own": amount_own,
                "external": amount_ext,
                "depreciable": False,
                "life": 0,
                "key": key,
                "working": True,
            }
        )

    project_cost = sum(row["cost"] for row in funding_rows)
    own_total = sum(row["own"] for row in funding_rows)
    external_total = sum(row["external"] for row in funding_rows)
    if financing == 2:
        debt_total = external_total
        investor_total = 0.0
        grant_total = 0.0
    elif financing == 3:
        debt_total = 0.0
        investor_total = 0.0
        grant_total = external_total
    elif financing == 4:
        credit_map = form.get("credit") or {}
        debt_total = sum(min(_num(credit_map.get(key)), ext_of(key)) for key in (*CAPEX_KEYS, *OPERATING_KEYS))
        investor_total = max(0.0, external_total - debt_total)
        grant_total = 0.0
    else:
        debt_total = 0.0
        investor_total = external_total
        grant_total = 0.0
    equity_total = own_total + investor_total

    if financing == 4:
        credit_map = form.get("credit") or {}
        capex_debt = sum(min(_num(credit_map.get(key)), ext_of(key)) for key in CAPEX_KEYS)
        operating_debt = sum(min(_num(credit_map.get(key)), ext_of(key)) for key in OPERATING_KEYS)
    elif financing == 2:
        capex_debt = sum(ext_of(key) for key in CAPEX_KEYS)
        operating_debt = sum(ext_of(key) for key in OPERATING_KEYS)
    else:
        capex_debt = 0.0
        operating_debt = 0.0
    bank_rate = max(0.0, _num(form.get("loan_rate")) / 100 + options.rate_add)
    client_rate = bank_rate
    subsidy_months = 0
    loans = []
    if financing in (2, 4) and capex_debt > 0:
        loans.append(
            {
                "name": "Займ на инвестиционные цели",
                "principal": capex_debt,
                "rate": bank_rate,
                "term": int(_num(form.get("loan_term")) or 36),
                "grace": int(_num(form.get("loan_grace"))),
                "annuity": bool(form.get("loan_annuity")),
                "start": start,
            }
        )
    if financing in (2, 4) and operating_debt > 0 and int(_num(form.get("wc_term"))):
        loans.append(
            {
                "name": "Займ на пополнение оборотных средств",
                "principal": operating_debt,
                "rate": max(0.0, _num(form.get("wc_rate")) / 100 + options.rate_add),
                "term": int(_num(form.get("wc_term"))),
                "grace": int(_num(form.get("wc_grace"))),
                "annuity": False,
                "start": start,
            }
        )
    elif financing in (2, 4) and operating_debt > 0 and loans:
        loans[0]["principal"] += operating_debt
    for loan in loans:
        loan["rows"] = loan_schedule(
            loan["principal"],
            loan["rate"],
            loan["term"],
            loan["grace"],
            loan["annuity"],
            loan["start"],
            options.subsidy_on,
            client_rate,
            subsidy_months,
        )
        loan["closing"] = loan["rows"][-1]["closing"] if loan["rows"] else loan["principal"]

    debt_rate = 0.0
    if debt_total > 0 and loans:
        debt_rate = sum(loan["principal"] * loan["rate"] for loan in loans) / sum(loan["principal"] for loan in loans)
    discount = wacc(NBK_BASE, debt_rate, equity_total, debt_total, tax_shield)

    loads = _load_by_year(years, form.get("planned_capacity"), sales, full)
    products = []
    for row in form.get("volume_sales") or []:
        name = str(row.get("name") or "").strip()
        if not name:
            continue
        monthly_full = _num(row.get("count"))
        if time_param == 1:
            monthly_full /= 12
        products.append(
            {
                "name": name,
                "monthly": monthly_full * options.revenue_factor,
                "price": _num(row.get("price")),
                "season": _season(row.get("months"), bool(row.get("seasonal"))),
                "unit_cost": 0.0,
            }
        )
    if form.get("cost_mode") == "unit":
        for cost in form.get("variable_costs") or []:
            title = str(cost.get("product") or "").strip()
            amount = _num(cost.get("price"))
            matched = [item for item in products if item["name"] == title]
            targets = matched or products
            if not targets:
                continue
            share = amount / len(targets)
            for item in targets:
                item["unit_cost"] += share
    other_monthly = [row for row in (form.get("variable_other") or []) if str(row.get("name") or "").strip()]
    other_income = _num(form.get("other_income")) * options.revenue_factor

    staff = []
    for row in form.get("salary") or []:
        title = str(row.get("name") or "").strip()
        if not title:
            continue
        staff.append(
            {
                "name": title,
                "count": _num(row.get("count")),
                "salary": _num(row.get("salary")),
                "bonus": _num(row.get("percent")) / 100,
                "invest": bool(row.get("invest")),
            }
        )
    admin = []
    for source in ("admin_expenses", "marketing_expenses"):
        for row in form.get(source) or []:
            title = str(row.get("name") or "").strip()
            if not title or _num(row.get("value")) == 0:
                continue
            admin.append(
                {
                    "name": title,
                    "value": _num(row.get("value")),
                    "period": int(_num(row.get("period")) or 1),
                    "invest": bool(row.get("invest")),
                }
            )

    raw_stock = own_of("raw") + ext_of("raw")
    dividend_rate = _num(form.get("investor_dividend")) / 100 if financing in (1, 4) else 0.0
    investor_years = int(_num(form.get("investor_term"))) if financing in (1, 4) else 0

    month_rows = []
    for year, month in calendar:
        if year not in price_k:
            continue
        operating = sales <= (year, month) <= end
        season_month = month
        load = loads.get(year, 0.0)
        revenue = 0.0
        cogs = 0.0
        volume = 0.0
        if operating:
            for product in products:
                qty = product["monthly"] * load * product["season"][season_month - 1]
                volume += qty
                revenue += qty * product["price"] * price_k[year]
                cogs += qty * product["unit_cost"] * cost_k[year]
            revenue += other_income * price_k[year]
            for item in other_monthly:
                period = item.get("period") or 1
                if period <= 0:
                    continue
                if period == 1 or (month - sales[1]) % period == 0:
                    cogs += _num(item.get("value")) * cost_k[year]
        payroll = 0.0
        if operating:
            for person in staff:
                gross = person["salary"] * person["count"] * (1 + person["bonus"]) * wage_k[year]
                if person["invest"]:
                    continue
                payroll += gross
        payroll_tax = _payroll_tax(payroll, year, general)
        admin_cost = 0.0
        if operating:
            for item in admin:
                if item["invest"]:
                    continue
                period = item["period"]
                amount = item["value"] * fixed_k[year]
                if period <= 0:
                    if (year, month) == sales:
                        admin_cost += amount
                elif period == 1 or (month - 1) % period == 0:
                    admin_cost += amount
        capex = 0.0
        for asset in assets:
            if (asset["year"], asset["month"]) == (year, month):
                capex += asset["cost"]
        interest = 0.0
        principal = 0.0
        for loan in loans:
            for pay in loan["rows"]:
                if (pay["year"], pay["month"]) == (year, month):
                    interest += pay["client_interest"] if options.subsidy_on else pay["bank_interest"]
                    principal += pay["principal"]
        month_rows.append(
            {
                "year": year,
                "month": month,
                "operating": operating,
                "load": load,
                "revenue": revenue,
                "cogs": cogs,
                "volume": volume,
                "payroll": payroll,
                "payroll_tax": payroll_tax,
                "admin": admin_cost,
                "capex": capex,
                "interest": interest,
                "principal": principal,
            }
        )

    depreciation = _depreciation(assets, years, calendar, capex_k)
    for row in month_rows:
        row["depreciation"] = depreciation["monthly"].get((row["year"], row["month"]), 0.0)
        row["capex"] += depreciation["replacements"].get((row["year"], row["month"]), 0.0)
        row["property"] = 0.0

    property_tax = {}
    for year in years:
        opening = depreciation["opening"].get(year, 0.0)
        closing = depreciation["closing"].get(year, 0.0)
        property_tax[year] = (opening + closing) / 2 * PROPERTY_TAX
    for year in years:
        months_of_year = [row for row in month_rows if row["year"] == year]
        if months_of_year:
            months_of_year[-1]["property"] = property_tax[year]

    by_year = {year: _blank_year() for year in years}
    for row in month_rows:
        bucket = by_year[row["year"]]
        for key in ("revenue", "cogs", "payroll", "payroll_tax", "admin", "depreciation", "interest", "principal", "capex"):
            bucket[key] += row[key]
        bucket["admin"] += row["property"]
        if row["operating"]:
            bucket["months"] += 1
    for year in years:
        by_year[year]["property"] = property_tax.get(year, 0.0)

    loss_carry = 0.0
    pnl = {key: [] for key in (
        "revenue", "cogs", "gross", "payroll", "payroll_tax", "admin", "depreciation",
        "interest", "ebt", "cit", "net", "margin", "ebitda", "avg_revenue", "avg_net",
    )}
    net_by_year = {}
    for year in years:
        bucket = by_year[year]
        gross = bucket["revenue"] - bucket["cogs"]
        ebt = gross - bucket["payroll"] - bucket["payroll_tax"] - bucket["admin"] - bucket["depreciation"] - bucket["interest"]
        if general:
            taxable = ebt
            if taxable > 0 and loss_carry > 0:
                used = min(loss_carry, taxable)
                taxable -= used
                loss_carry -= used
            if ebt < 0:
                loss_carry += -ebt
            cit = max(0.0, taxable) * cit_rate
        else:
            cit = max(0.0, bucket["revenue"]) * cit_rate
        net = ebt - cit
        net_by_year[year] = net
        ebitda = ebt + bucket["depreciation"] + bucket["interest"]
        months_open = bucket["months"] or 0
        pnl["revenue"].append(bucket["revenue"])
        pnl["cogs"].append(bucket["cogs"])
        pnl["gross"].append(gross)
        pnl["payroll"].append(bucket["payroll"])
        pnl["payroll_tax"].append(bucket["payroll_tax"])
        pnl["admin"].append(bucket["admin"])
        pnl["depreciation"].append(bucket["depreciation"])
        pnl["interest"].append(bucket["interest"])
        pnl["ebt"].append(ebt)
        pnl["cit"].append(cit)
        pnl["net"].append(net)
        pnl["margin"].append(net / bucket["revenue"] if bucket["revenue"] else 0.0)
        pnl["ebitda"].append(ebitda)
        pnl["avg_revenue"].append(bucket["revenue"] / 12)
        pnl["avg_net"].append(net / 12)
        bucket["cit"] = cit
        bucket["net"] = net
        bucket["months_open"] = months_open

    tax_left = {year: by_year[year]["cit"] for year in years}
    dividend_left = {year: 0.0 for year in years}
    for index, year in enumerate(years):
        if index == 0 or dividend_rate <= 0:
            continue
        if investor_years and index > investor_years:
            continue
        previous = net_by_year[years[index - 1]]
        if previous > 0:
            dividend_left[year] = previous * dividend_rate

    prev_wc = 0.0
    capex_vat_refund = 0.0
    first_gap = None
    cash_open = 0.0
    full_load_at = None
    for row in month_rows:
        if full_load_at is None and row["load"] >= 0.999 and row["operating"]:
            full_load_at = (row["year"], row["month"])
        stock = row["cogs"] / 30 * options.stock_days if row["cogs"] else 0.0
        if (row["year"], row["month"]) == sales:
            stock += raw_stock
        receivable = row["revenue"] / 30 * options.receivable_days if row["revenue"] else 0.0
        payable = row["cogs"] / 30 * options.payable_days if row["cogs"] else 0.0
        working = stock + receivable - payable
        delta_wc = working - prev_wc
        prev_wc = working
        output_vat = row["revenue"] * vat
        input_vat = (row["cogs"] + row["admin"]) * vat
        net_vat = output_vat - input_vat
        cit = 0.0
        if tax_left[row["year"]] and (row["month"] == 12 or (row["year"], row["month"]) == calendar[-1]):
            cit = tax_left[row["year"]]
            tax_left[row["year"]] = 0.0
        dividend = 0.0
        if dividend_left[row["year"]] and row["month"] == calendar[0][1] and row["year"] != years[0]:
            dividend = dividend_left[row["year"]]
            dividend_left[row["year"]] = 0.0
        elif dividend_left[row["year"]] and row["month"] == 1:
            dividend = dividend_left[row["year"]]
            dividend_left[row["year"]] = 0.0
        receipts = row["revenue"] * (1 + vat)
        paid_costs = (row["cogs"] + row["admin"]) * (1 + vat) + row["payroll"] + row["payroll_tax"] + row["property"]
        operating_flow = receipts - paid_costs - row["interest"] - cit - net_vat - delta_wc
        capex_gross = row["capex"] * (1 + vat)
        investing = -capex_gross + capex_vat_refund
        capex_vat_refund = row["capex"] * vat
        draw = 0.0
        equity_in = 0.0
        investor_in = 0.0
        grant_in = 0.0
        if (row["year"], row["month"]) == start:
            equity_in = own_total
            investor_in = investor_total
            grant_in = grant_total
            draw = debt_total
        financing_flow = equity_in + investor_in + grant_in + draw - row["principal"] - dividend
        closing = cash_open + operating_flow + investing + financing_flow
        row.update(
            {
                "receipts": receipts,
                "vat": net_vat,
                "cit": cit,
                "dividend": dividend,
                "delta_wc": delta_wc,
                "working": working,
                "operating_flow": operating_flow,
                "investing": investing,
                "financing_flow": financing_flow,
                "opening_cash": cash_open,
                "closing_cash": closing,
                "equity_in": equity_in,
                "investor_in": investor_in,
                "grant_in": grant_in,
                "draw": draw,
            }
        )
        if closing < -1 and first_gap is None:
            first_gap = row
        cash_open = closing

    cash_lines = {key: [] for key in (
        "receipts", "cogs", "payroll", "payroll_tax", "admin", "interest", "cit", "vat",
        "delta_wc", "operating", "capex", "investing", "equity", "investor", "grant", "draw",
        "principal", "dividend", "financing", "closing",
    )}
    for year in years:
        chosen = [row for row in month_rows if row["year"] == year]
        cash_lines["receipts"].append(sum(row["receipts"] for row in chosen))
        cash_lines["cogs"].append(sum(row["cogs"] * (1 + vat) for row in chosen))
        cash_lines["payroll"].append(sum(row["payroll"] for row in chosen))
        cash_lines["payroll_tax"].append(sum(row["payroll_tax"] for row in chosen))
        cash_lines["admin"].append(sum(row["admin"] * (1 + vat) + row["property"] for row in chosen))
        cash_lines["interest"].append(sum(row["interest"] for row in chosen))
        cash_lines["cit"].append(sum(row["cit"] for row in chosen))
        cash_lines["vat"].append(sum(row["vat"] for row in chosen))
        cash_lines["delta_wc"].append(sum(row["delta_wc"] for row in chosen))
        cash_lines["operating"].append(sum(row["operating_flow"] for row in chosen))
        cash_lines["capex"].append(sum(row["capex"] for row in chosen))
        cash_lines["investing"].append(sum(row["investing"] for row in chosen))
        cash_lines["equity"].append(sum(row["equity_in"] for row in chosen))
        cash_lines["investor"].append(sum(row["investor_in"] for row in chosen))
        cash_lines["grant"].append(sum(row["grant_in"] for row in chosen))
        cash_lines["draw"].append(sum(row["draw"] for row in chosen))
        cash_lines["principal"].append(sum(row["principal"] for row in chosen))
        cash_lines["dividend"].append(sum(row["dividend"] for row in chosen))
        cash_lines["financing"].append(sum(row["financing_flow"] for row in chosen))
        cash_lines["closing"].append(chosen[-1]["closing_cash"] if chosen else 0.0)

    fcf = []
    for index in range(len(years)):
        fcf.append(
            cash_lines["operating"][index]
            + cash_lines["interest"][index] * (1 - tax_shield)
            + cash_lines["investing"][index]
        )
    factors = []
    factor = 1.0
    for _ in years:
        factors.append(factor)
        factor /= 1 + discount if discount > -0.99 else 1
    dcf = [amount * weight for amount, weight in zip(fcf, factors)]
    invested = sum(cash_lines["capex"])
    cumulative_fcf = []
    running = 0.0
    for amount in fcf:
        running += amount
        cumulative_fcf.append(running)
    dscr_values = []
    for index in range(len(years)):
        debt_service = cash_lines["interest"][index] + cash_lines["principal"][index]
        if debt_service <= 1:
            dscr_values.append(None)
        else:
            dscr_values.append((cash_lines["operating"][index] + cash_lines["interest"][index]) / debt_service)
    paid = [item for item in dscr_values if item is not None]
    simple = payback(fcf)
    discounted = payback(dcf)
    rate = irr(fcf)
    modified = mirr(fcf, discount, discount)
    profitability = (sum(dcf) / invested) if invested else None
    later = fcf[1:]
    average_return = (sum(later) / len(later) / invested) if later and invested else None

    m1 = by_year[years[0]]["months"] if years else 0
    assumptions = {
        "regime": "Общеустановленный" if general else "Упрощённая декларация",
        "general": general,
        "vat": vat,
        "cit_rate": cit_rate,
        "tax_shield": tax_shield,
        "m1": m1,
        "sales": sales,
        "full": full,
        "start": start,
        "end": end,
        "loads": loads,
        "growth": options.growth,
        "price_k": price_k,
        "cost_k": cost_k,
        "fixed_k": fixed_k,
        "wage_k": wage_k,
        "capex_k": capex_k,
        "re": NBK_BASE,
        "rd": debt_rate,
        "equity": equity_total,
        "debt": debt_total,
        "wacc": discount,
        "subsidy_on": options.subsidy_on,
        "dividend_rate": dividend_rate,
        "investor_years": investor_years,
        "project": project_cost,
        "own": own_total,
        "investor": investor_total,
        "grant": grant_total,
        "financing": {1: "Инвестор", 2: "Кредит", 3: "Грант"}.get(financing, "Инвестор"),
        "opening_cash": 0.0,
        "stock_days": options.stock_days,
        "receivable_days": options.receivable_days,
        "payable_days": options.payable_days,
        "property_tax": PROPERTY_TAX,
        "full_load": full_load_at,
        "time_param": "помесячно" if time_param == 3 else "по годам",
    }
    metrics = {
        "fcf": fcf,
        "dcf": dcf,
        "factors": factors,
        "wacc": discount,
        "npv": sum(dcf),
        "irr": rate,
        "mirr": modified,
        "pi": None if profitability is None else profitability + 1,
        "p": profitability,
        "pb": simple,
        "dpb": discounted,
        "arr": average_return,
        "ic": invested,
        "dscr": dscr_values,
        "min_dscr": min(paid) if paid else None,
        "min_cash": min((row["closing_cash"] for row in month_rows), default=0.0),
        "first_gap": first_gap,
        "cumulative": cumulative_fcf,
    }
    seasonality = products[0]["season"] if products else [1.0] * 12
    result = FemResult(
        name=str(form.get("name") or "Финмодель"),
        currency={"kzt": "тенге", "usd": "доллары США", "eur": "евро"}.get(form.get("currency"), "тенге"),
        years=years,
        assumptions=assumptions,
        funding_rows=funding_rows,
        loans=loans,
        revenue_rows=products,
        seasonality=seasonality,
        payroll_rows=staff,
        admin_rows=admin,
        assets=assets,
        pnl=pnl,
        cash=cash_lines,
        monthly=month_rows,
        metrics=metrics,
    )
    result.checks = _checks(result, form)
    return result


def _blank_year() -> dict:
    return {key: 0.0 for key in (
        "revenue", "cogs", "payroll", "payroll_tax", "admin", "depreciation", "interest",
        "principal", "capex", "months", "property", "cit", "net",
    )}


def _payroll_tax(payroll: float, year: int, general: bool) -> float:
    if payroll <= 0:
        return 0.0
    social = payroll * SOCIAL_BASE * SOCIAL_CONTRIB
    opvr = payroll * _opvr(year)
    medical = payroll * OOSMS
    if general:
        # НК с 2026: СН 6% без вычета СО. До 2026 — прежняя схема max(0; ставка − СО).
        if year >= 2026:
            social_tax = payroll * SOCIAL_TAX_GENERAL
        else:
            legacy = 0.11 if year >= 2025 else 0.095
            social_tax = max(0.0, payroll * legacy - social)
    else:
        social_tax = payroll * SOCIAL_TAX_SIMPLIFIED
    return social + opvr + medical + social_tax


def _depreciation(assets: list[dict], years: list[int], calendar: list[tuple[int, int]], capex_k: dict[int, float]) -> dict:
    opening = {year: 0.0 for year in years}
    closing = {year: 0.0 for year in years}
    annual = {year: 0.0 for year in years}
    monthly = {moment: 0.0 for moment in calendar}
    replacements = {moment: 0.0 for moment in calendar}
    for asset in assets:
        if not asset["depreciable"] or asset["life"] <= 0:
            continue
        original = asset["cost"]
        cost = original
        remaining = 0.0
        alive = False
        born = (asset["year"], asset["month"])
        for year in years:
            if not alive and year == born[0]:
                alive = True
                remaining = cost
            elif not alive and year > born[0]:
                alive = True
                remaining = cost
            opening[year] += remaining if alive and year >= born[0] else 0.0
            if year < born[0] or not alive:
                dep = 0.0
            else:
                months = 12 - born[1] + 1 if year == born[0] else 12
                dep = min(remaining, cost / asset["life"] / 12 * months)
            remaining = max(0.0, remaining - dep)
            annual[year] += dep
            moments = [moment for moment in calendar if moment[0] == year]
            if moments and dep:
                share = dep / len(moments)
                for moment in moments:
                    monthly[moment] += share
            if alive and remaining <= 1 and year < years[-1]:
                next_year = years[years.index(year) + 1]
                replacement = (next_year, 1)
                if replacement in replacements:
                    renewed = original * capex_k.get(next_year, 1.0)
                    replacements[replacement] += renewed
                    cost = renewed
                    remaining = 0.0
                    born = replacement
                    alive = False
            closing[year] += remaining if year >= asset["year"] else 0.0
    return {"opening": opening, "closing": closing, "annual": annual, "monthly": monthly, "replacements": replacements}


def _checks(result: FemResult, form: dict) -> list[dict]:
    data = result.assumptions
    metrics = result.metrics
    checks = []
    if metrics["first_gap"]:
        gap = metrics["first_gap"]
        checks.append(_check(
            "Кассовый разрыв",
            "критично",
            f"Первый минус: {gap['month']:02d}.{gap['year']}, остаток {gap['closing_cash']:,.0f}.",
            "Увеличьте собственные средства, добавьте льготный период, сдвиньте CAPEX или пополните оборотные кредитом.",
        ))
    else:
        checks.append(_check("Кассовый разрыв", "ок", "Остаток денег не уходит в минус ни в одном месяце.", ""))
    if metrics["min_dscr"] is None:
        checks.append(_check("Покрытие долга", "информация", "Выплат тела и процентов нет — DSCR не считается.", ""))
    elif metrics["min_dscr"] < DSCR_MIN:
        checks.append(_check(
            "Покрытие долга",
            "критично",
            f"Минимальный DSCR {metrics['min_dscr']:.2f} ниже порога {DSCR_MIN:.2f}. Смотрите неполный первый год.",
            "Снизьте ставку, удлините срок или добавьте льготный период.",
        ))
    else:
        checks.append(_check("Покрытие долга", "ок", f"Минимальный DSCR {metrics['min_dscr']:.2f} не ниже {DSCR_MIN:.2f}.", ""))
    broken = [loan["name"] for loan in result.loans if abs(loan.get("closing", 0)) > 1]
    if broken:
        checks.append(_check("Погашение кредита", "критично", "Остаток долга на конец срока не ноль: " + ", ".join(broken), "Проверьте срок, льготу и тип платежа."))
    elif result.loans:
        checks.append(_check("Погашение кредита", "ок", "По каждому кредиту остаток на конец срока равен нулю.", ""))
    else:
        checks.append(_check("Погашение кредита", "информация", "Кредитов в анкете нет.", ""))
    sources = data["own"] + data["investor"] + data["grant"] + data["debt"]
    if abs(sources - data["project"]) > 1:
        checks.append(_check(
            "Баланс источников",
            "критично",
            f"Источники {sources:,.0f} не сходятся с суммой проекта {data['project']:,.0f}.",
            "Собственные, инвестор, грант, кредиты и лизинг вместе должны равняться сумме проекта.",
        ))
    else:
        checks.append(_check("Баланс источников", "ок", "Источники финансирования равны сумме проекта.", ""))
    checks.append(_check(
        "Условия программы",
        "информация",
        "Программа господдержки не выбрана. Субсидия выключена, модель идёт по рыночной ставке.",
        "Льгота не применяется молча.",
    ))
    if not data["general"]:
        peak = max(result.pnl["revenue"] or [0])
        if peak > SIMPLIFIED_LIMIT:
            checks.append(_check(
                "Лимит упрощёнки",
                "предупреждение",
                f"Выручка года {peak:,.0f} выше лимита {SIMPLIFIED_LIMIT:,.0f}.",
                "Перейти на общеустановленный режим.",
            ))
        else:
            checks.append(_check("Лимит упрощёнки", "ок", "Выручка года не выше лимита упрощёнки в справочнике.", ""))
    margin = max(result.pnl["margin"] or [0])
    if margin > TYPICAL_MARGIN:
        checks.append(_check(
            "Рентабельность",
            "предупреждение",
            f"Рентабельность продаж доходит до {margin:.0%}, это выше типичных {TYPICAL_MARGIN:.0%}.",
            "Проверьте цену, загрузку и себестоимость.",
        ))
    else:
        checks.append(_check("Рентабельность", "ок", "Рентабельность продаж не выше типичного порога справочника.", ""))
    checks.append(_check(
        "Рост выручки",
        "информация",
        "Оборот прошлого года в анкете не задан: сравнение с действующим бизнесом не выполняется.",
        "",
    ))
    if metrics["pb"] is None:
        checks.append(_check("Окупаемость", "информация", "Не окупается в горизонте прогноза.", ""))
    else:
        checks.append(_check("Окупаемость", "ок", _span(metrics["pb"]) + " от даты запуска.", ""))
    if data["debt"] > 0 and data["re"] < data["rd"]:
        checks.append(_check(
            "Стоимость капитала",
            "предупреждение",
            "Стоимость собственного капитала ниже ставки по кредиту — NPV может быть завышен.",
            "Расчёт не блокируется.",
        ))
    return checks


def _check(name: str, level: str, text: str, hint: str) -> dict:
    return {"name": name, "level": level, "text": text, "hint": hint}


def _span(years: float) -> str:
    whole = int(years)
    months = int(round((years - whole) * 12))
    if months == 12:
        whole += 1
        months = 0
    return f"{whole} г. {months} мес."


def build_report(form: dict) -> FemResult:
    base = calculate(form)
    scenarios = [
        ("Базовый", CalcOptions()),
        ("Без субсидии", CalcOptions(subsidy_on=False)),
        ("Выручка −10%", CalcOptions(revenue_factor=0.9)),
        ("Выручка −20%", CalcOptions(revenue_factor=0.8)),
        ("Ставка кредита +2 п.п.", CalcOptions(rate_add=0.02)),
        ("Запуск позже на 3 месяца", CalcOptions(launch_shift=3)),
    ]
    rows = []
    for title, options in scenarios:
        model = base if title == "Базовый" else calculate(form, options)
        rows.append(
            {
                "name": title,
                "npv": model.metrics["npv"],
                "irr": model.metrics["irr"],
                "min_dscr": model.metrics["min_dscr"],
                "min_cash": model.metrics["min_cash"],
                "note": "Совпадает с базовым: субсидия и так выключена." if title == "Без субсидии" and not base.assumptions["subsidy_on"] else "",
            }
        )
    base.scenarios = rows
    return base


def build_fem_v2_excel(user: User, form: dict) -> bytes:
    result = build_report(form)
    from app.fem_v2_book import render

    return render(user, form, result)


