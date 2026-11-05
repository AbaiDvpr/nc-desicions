from pydantic import BaseModel, Field, field_validator, model_validator

from app.schemas import (
    AssetIn,
    CompanyIn,
    CostIn,
    FinModelData,
    InvestmentIn,
    LoanIn,
    MarketIn,
    Num,
    ProductIn,
    RevenueIn,
    StaffIn,
    Int,
    Text,
)

Month = Field(pattern=r"^\d{4}-\d{2}$")
CAPEX_KEYS = ("smr", "overhaul", "ready_premises", "equipment")
OPERATING_KEYS = ("raw", "advertising", "wages", "rental", "other")
UNITS = {
    1: "шт",
    2: "кг",
    3: "тонн",
    4: "усл",
    5: "м2",
    6: "м3",
    7: "л",
    8: "м.п.",
    9: "км",
    10: "чел.",
    11: "ед.",
}
SCHEDULES = {
    1: "пятидневная рабочая неделя (8 часов)",
    2: "шестидневная рабочая неделя (8 часов)",
    3: "ежедневная рабочая неделя (8 часов)",
}
TIME_PARAMS = {1: "по годам", 3: "помесячно"}
FINANCING = {
    1: "долевое финансирование (инвестор)",
    2: "долговое (кредитное) финансирование",
    3: "грантовое финансирование",
}
CAPEX_LABELS = {
    "smr": "Перечень видов работ СМР, капитального ремонта",
    "overhaul": "Перечень материалов при СМР, капитальном ремонте",
    "ready_premises": "Помещения, здания, сооружения",
    "equipment": "Приобретение оборудования, техники, мебели",
}


class VolumeSaleIn(BaseModel):
    name: str = Field(min_length=1, max_length=255)
    count: Num
    units: int = Field(default=1, ge=1, le=11)
    seasonal: bool = False
    months: list[int] = Field(default_factory=list, max_length=12)
    price: Num

    @field_validator("name")
    @classmethod
    def strip_name(cls, value: str) -> str:
        value = value.strip()
        if not value:
            raise ValueError("Укажите наименование продукции")
        return value

    @field_validator("months")
    @classmethod
    def month_numbers(cls, value: list[int]) -> list[int]:
        if any(month < 1 or month > 12 for month in value):
            raise ValueError("Месяц сезонности должен быть от 1 до 12")
        return value


class AssetRow(BaseModel):
    name: str = Field(default="", max_length=255)
    count: Num = 0
    price_one: Num = 0
    price: Num = 0
    year: Int = 0
    life_years: Int = 0


class VariableCostIn(BaseModel):
    product: str = Field(default="", max_length=255)
    cost_type: str = Field(default="", max_length=255)
    price: Num = 0


class ExpenseRow(BaseModel):
    name: str = Field(default="", max_length=255)
    value: Num = 0
    invest: bool = False
    period: int = Field(default=1, ge=0, le=12)
    type_expense: int = Field(default=0, ge=0, le=2)


class SalaryRow(BaseModel):
    invest: bool = False
    name: str = Field(default="", max_length=255)
    count: Num = 0
    salary: Num = 0
    percent: Num = 0
    industrial: int = Field(default=0, ge=0, le=1)
    services: list[Text] = Field(default_factory=list, max_length=30)


class Amounts(BaseModel):
    smr: Num = 0
    overhaul: Num = 0
    ready_premises: Num = 0
    equipment: Num = 0
    raw: Num = 0
    advertising: Num = 0
    wages: Num = 0
    rental: Num = 0
    other: Num = 0


class ProjectFormIn(BaseModel):
    name: str = Field(min_length=1, max_length=500)
    legal_form: int = Field(ge=1, le=2)
    nds: int = Field(ge=0, le=1)
    tax_regime: int = Field(ge=1, le=4)
    currency: str = Field(pattern=r"^(kzt|usd|eur)$")
    time_param: int = Field(ge=1, le=3)
    schedule: int = Field(ge=1, le=3)
    planning_from: str = Month
    planning_to: str = Month

    financing: int = Field(ge=1, le=3)
    investor_term: Int = 0
    investor_dividend: Num = 0
    loan_term: Int = 0
    loan_grace: Int = 0
    loan_rate: Num = 0
    loan_annuity: bool = False
    wc_term: Int = 0
    wc_grace: Int = 0
    wc_rate: Num = 0

    sales_start: str = Month
    planned_volume: str = Month
    volume_sales: list[VolumeSaleIn] = Field(min_length=1, max_length=30)
    planned_capacity: dict[str, Num] = Field(default_factory=dict)
    other_income: Num = 0
    equipment: list[AssetRow] = Field(default_factory=list, max_length=30)
    premises: list[AssetRow] = Field(default_factory=list, max_length=30)
    smr: list[AssetRow] = Field(default_factory=list, max_length=30)
    overhaul: list[AssetRow] = Field(default_factory=list, max_length=30)

    cost_mode: str = Field(pattern=r"^(unit|monthly)$")
    variable_costs: list[VariableCostIn] = Field(default_factory=list, max_length=30)
    variable_other: list[ExpenseRow] = Field(default_factory=list, max_length=30)
    salary: list[SalaryRow] = Field(default_factory=list, max_length=30)
    admin_expenses: list[ExpenseRow] = Field(default_factory=list, max_length=40)
    marketing_expenses: list[ExpenseRow] = Field(default_factory=list, max_length=30)

    own_invest: int = Field(ge=0, le=1)
    own: Amounts = Field(default_factory=Amounts)
    attracted: Amounts = Field(default_factory=Amounts)
    months_raw: Int = 1
    months_wages: Int = 1
    months_advertising: Int = 1
    months_rental: Int = 1
    months_other: Int = 1

    @field_validator("name")
    @classmethod
    def strip_name(cls, value: str) -> str:
        value = value.strip()
        if not value:
            raise ValueError("Укажите название проекта")
        return value

    @field_validator("tax_regime")
    @classmethod
    def tax_choice(cls, value: int) -> int:
        if value not in (1, 4):
            raise ValueError("Выберите форму налогообложения")
        return value

    @field_validator("time_param")
    @classmethod
    def time_choice(cls, value: int) -> int:
        if value not in (1, 3):
            raise ValueError("Выберите временные параметры расчёта")
        return value

    @model_validator(mode="after")
    def check_form(self):
        if self.planning_to < self.planning_from:
            raise ValueError("Дата окончания проекта раньше даты начала")
        if not (self.planning_from <= self.sales_start <= self.planning_to):
            raise ValueError("Дата начала продаж должна входить в период планирования")
        if not (self.planning_from <= self.planned_volume <= self.planning_to):
            raise ValueError("Срок выхода на плановый объём должен входить в период планирования")
        if any(row.count <= 0 for row in self.volume_sales):
            raise ValueError("Укажите планируемое количество продаж")
        if self.financing == 1:
            if not 1 <= self.investor_term <= 100:
                raise ValueError("Срок участия инвестора — от 1 до 100 лет")
            if self.investor_dividend > 100:
                raise ValueError("Дивиденды инвестора не больше 100%")
        if self.financing == 2:
            if not 1 <= self.loan_term <= 250:
                raise ValueError("Срок займа на инвестиционные цели — от 1 до 250 месяцев")
            if self.loan_grace > 36:
                raise ValueError("Льготный период не больше 36 месяцев")
            if self.wc_term and not 24 <= self.wc_term <= 36:
                raise ValueError("Срок займа на оборотные средства — от 24 до 36 месяцев")
            if self.wc_grace > 36:
                raise ValueError("Льготный период по оборотным средствам не больше 36 месяцев")
            if self.wc_rate > 100 or self.loan_rate > 100:
                raise ValueError("Ставка по кредиту не больше 100%")
        for label, rows in (
            ("оборудования", self.equipment),
            ("недвижимости", self.premises),
            ("СМР", self.smr),
            ("материалов", self.overhaul),
        ):
            for row in rows:
                if not row.name.strip():
                    continue
                if row.count <= 0 or row.year < 1900 or row.life_years <= 0:
                    raise ValueError(f"Для {label} укажите количество, год приобретения и срок службы")
        targets = {
            "smr": sum(row.price for row in self.smr if row.name.strip()),
            "overhaul": sum(row.price for row in self.overhaul if row.name.strip()),
            "ready_premises": sum(row.price for row in self.premises if row.name.strip()),
            "equipment": sum(row.price for row in self.equipment if row.name.strip()),
        }
        for key, target in targets.items():
            own = getattr(self.own, key) if self.own_invest else 0
            distributed = own + getattr(self.attracted, key)
            if target and round(distributed) != round(target):
                raise ValueError(
                    f"Распределите сумму «{CAPEX_LABELS[key]}»: собственные и привлекаемые вместе должны равняться {round(target)}"
                )
        return self


def months_between(start: str, end: str) -> int:
    if not start or not end or len(start) < 7 or len(end) < 7:
        return 36
    year_1, month_1 = int(start[:4]), int(start[5:7])
    year_2, month_2 = int(end[:4]), int(end[5:7])
    span = (year_2 - year_1) * 12 + (month_2 - month_1) + 1
    return min(240, max(1, span))


def present(data: dict) -> tuple[str, int]:
    company = data.get("company")
    if isinstance(company, dict) and "horizon_months" in company:
        return company.get("industry") or "", int(company.get("horizon_months") or 36)
    forms = {1: "ИП", 2: "ТОО"}
    return forms.get(data.get("legal_form"), ""), months_between(
        data.get("planning_from") or "", data.get("planning_to") or ""
    )


def _period(value: int) -> str:
    if value == 0:
        return "once"
    if value == 12:
        return "yearly"
    return "monthly"


def _assets(rows: list[AssetRow], category: str) -> list[AssetIn]:
    result = []
    for row in rows:
        if not row.name.strip():
            continue
        result.append(
            AssetIn(
                name=row.name.strip(),
                category=category,
                cost=row.price or row.price_one * row.count,
                quantity=row.count or 1,
                purchase_date=f"{row.year:04d}-01" if row.year >= 1900 else "",
                life_months=row.life_years * 12 if row.life_years else 60,
            )
        )
    return result


def _costs(rows: list[ExpenseRow], category: str) -> list[CostIn]:
    return [
        CostIn(name=row.name.strip(), category=category, value=row.value, period=_period(row.period))
        for row in rows
        if row.name.strip() and row.value
    ]


def to_fin_model(form: ProjectFormIn) -> FinModelData:
    cogs: dict[str, float] = {}
    if form.cost_mode == "unit":
        for row in form.variable_costs:
            cogs[row.product] = cogs.get(row.product, 0) + row.price
    products = [
        ProductIn(
            name=row.name,
            unit=UNITS.get(row.units, "шт"),
            price=row.price,
            volume=row.count,
            start_date=form.sales_start,
            cogs_unit=cogs.get(row.name, 0),
        )
        for row in form.volume_sales
    ]
    staff = [
        StaffIn(
            position=(row.name.strip() + (" (ПП)" if row.industrial else ""))[:255],
            count=row.count,
            salary=row.salary,
            bonus_type="pct" if row.percent else "none",
            bonus_value=row.percent,
        )
        for row in form.salary
        if row.name.strip()
    ]
    costs = _costs(
        [row for row in form.admin_expenses if row.type_expense == 2],
        "Аренда",
    )
    costs += _costs(
        [row for row in form.admin_expenses if row.type_expense != 2],
        "Прочее",
    )
    costs += _costs(form.marketing_expenses, "Маркетинг")
    if form.cost_mode == "monthly":
        costs += _costs(form.variable_other, "Переменные расходы")
    assets = (
        _assets(form.equipment, "Оборудование")
        + _assets(form.premises, "Здания и помещения")
        + _assets(form.smr, "СМР")
        + _assets(form.overhaul, "Материалы")
    )
    capacity = ", ".join(f"{year}: {value:g}%" for year, value in form.planned_capacity.items() if value)
    description = (
        f"График работы: {SCHEDULES[form.schedule]}. "
        f"Временные параметры расчёта: {TIME_PARAMS[form.time_param]}. "
        f"Продажи с {form.sales_start}, выход на 100% план: {form.planned_volume}."
    )
    if capacity:
        description += f" Проектная мощность: {capacity}."
    attracted = sum(getattr(form.attracted, key) for key in (*CAPEX_KEYS, *OPERATING_KEYS))
    own = sum(getattr(form.own, key) for key in (*CAPEX_KEYS, *OPERATING_KEYS)) if form.own_invest else 0
    loans: list[LoanIn] = []
    if form.financing == 2:
        capex_amount = sum(getattr(form.attracted, key) for key in CAPEX_KEYS)
        operating_amount = sum(getattr(form.attracted, key) for key in OPERATING_KEYS)
        if form.wc_term and operating_amount:
            main_amount = capex_amount
            loans.append(
                LoanIn(
                    name="Займ на пополнение оборотных средств",
                    amount=operating_amount,
                    issue_date=form.planning_from,
                    rate=form.wc_rate,
                    term_months=form.wc_term,
                    grace_months=form.wc_grace,
                    method="differentiated",
                )
            )
        else:
            main_amount = capex_amount + operating_amount
        if main_amount:
            loans.append(
                LoanIn(
                    name="Займ на инвестиционные цели",
                    amount=main_amount,
                    issue_date=form.planning_from,
                    rate=form.loan_rate,
                    term_months=form.loan_term or 36,
                    grace_months=form.loan_grace,
                    method="annuity" if form.loan_annuity else "differentiated",
                )
            )
    return FinModelData(
        company=CompanyIn(
            name=form.name,
            legal_form="ИП" if form.legal_form == 1 else "ТОО",
            currency={"kzt": "KZT", "usd": "USD", "eur": "EUR"}[form.currency],
            start_date=form.planning_from,
            horizon_months=months_between(form.planning_from, form.planning_to),
            tax_regime="general" if form.tax_regime == 1 else "simplified",
            vat_payer=bool(form.nds),
            description=description,
        ),
        market=MarketIn(funding_goal=FINANCING[form.financing]),
        revenue=RevenueIn(products=products, other_income_amount=form.other_income),
        expenses={"staff": staff, "costs": costs, "assets": assets},
        investment=InvestmentIn(
            equity=own,
            investor_amount=attracted if form.financing == 1 else 0,
            investor_date=form.planning_from if form.financing == 1 else "",
            investor_share=form.investor_dividend if form.financing == 1 else 0,
            grant_amount=attracted if form.financing == 3 else 0,
            loans=loans,
        ),
    )
