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


def _month_or_blank(value: str, message: str) -> str:
    value = (value or "").strip()
    if not value:
        return ""
    value = value[:7]
    if len(value) != 7 or value[4] != "-" or not (value[:4].isdigit() and value[5:].isdigit()):
        raise ValueError(message)
    month = int(value[5:])
    if month < 1 or month > 12:
        raise ValueError(message)
    return value
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
    4: "инвестор и кредит",
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
    sales_start: str = ""
    own_capacity: bool = False
    planned_capacity: dict[str, Num] = Field(default_factory=dict)

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

    @field_validator("sales_start")
    @classmethod
    def product_sales_start(cls, value: str) -> str:
        return _month_or_blank(value, "Дата начала продаж продукта должна быть месяцем")


class AssetRow(BaseModel):
    name: str = Field(default="", max_length=255)
    count: Num = 0
    price_one: Num = 0
    price: Num = 0
    year: Int = 0
    life_years: Int = 0
    pay_month: str = ""
    commission_month: str = ""
    already_owned: bool = False
    book_value: Num = 0

    @field_validator("pay_month", "commission_month")
    @classmethod
    def asset_month(cls, value: str) -> str:
        return _month_or_blank(value, "Дата оплаты или ввода актива должна быть месяцем")


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
    # Калькулятор земельного налога: площадь × ставка в выбранных единицах → value (плата за период).
    land_tax: bool = False
    land_unit: str = Field(default="m2", max_length=8)  # m2 | ha
    land_area: Num = 0
    land_rate: Num = 0

    @field_validator("land_unit")
    @classmethod
    def land_unit_ok(cls, value: str) -> str:
        text = str(value or "m2").strip().lower()
        if text in ("ha", "га", "hectare", "hectares"):
            return "ha"
        return "m2"


class SalaryRow(BaseModel):
    invest: bool = False
    name: str = Field(default="", max_length=255)
    count: Num = 0
    salary: Num = 0
    percent: Num = 0
    industrial: int = Field(default=0, ge=0, le=1)
    opv_exempt: bool = False
    so_exempt: bool = False
    opvr_exempt: bool = False
    osms_exempt: bool = False
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


class GovProgramIn(BaseModel):
    slug: str = Field(default="", max_length=80)
    title: str = Field(default="", max_length=240)
    kind: str = Field(default="", max_length=20)
    kind_label: str = Field(default="", max_length=40)
    url: str = Field(default="", max_length=300)
    rate: float | None = None
    client_rate: float | None = None
    amount: str = Field(default="", max_length=120)
    detail: str = Field(default="", max_length=300)
    purpose: str = Field(default="", max_length=240)
    term_min_months: int | None = None
    term_max_months: int | None = None
    term_kind: str = Field(default="", max_length=20)
    logic: str = Field(default="", max_length=800)
    allows_investment: bool = True
    allows_wc: bool = True
    grace_max_months: int | None = None


class ProjectFormIn(BaseModel):
    name: str = Field(min_length=1, max_length=500)
    legal_form: int = Field(ge=1, le=2)
    nds: int = Field(ge=0, le=1)
    tax_regime: int = Field(ge=1, le=4)
    # Ставки в процентах (4 = 4%). Пустые → дефолты справочника / константы FEM.
    tax_simplified_rate: Num = 0
    tax_cit_rate: Num = 0
    vat_rate: Num = 0
    vosms_rate: Num = 0
    social_tax_rate: Num = 0
    currency: str = Field(pattern=r"^(kzt|usd|eur)$")
    time_param: int = Field(ge=1, le=3)
    schedule: int = Field(ge=1, le=3)
    planning_from: str = Month
    planning_to: str = Month

    financing: int = Field(ge=1, le=4)
    investor_term: Int = 0
    investor_dividend: Num = 0
    risk_premium: Num = 0
    loan_term: Int = 0
    loan_grace: Int = 0
    loan_rate: Num = 0
    loan_bank_rate: Num = 0
    loan_subsidy_rate: Num = 0
    loan_subsidy_term: Int = 0
    loan_nbk_plus_4: bool = False
    loan_annuity: bool = False
    gov_program: GovProgramIn | None = None
    # Гарантия Damu как дополнение к субсидии/кредиту (не меняет ставку).
    gov_guarantee: GovProgramIn | None = None
    wc_term: Int = 0
    wc_grace: Int = 0
    wc_rate: Num = 0

    sales_start: str = Month
    planned_volume: str = Month
    volume_sales: list[VolumeSaleIn] = Field(min_length=1, max_length=100)
    planned_capacity: dict[str, Num] = Field(default_factory=dict)
    other_income: Num = 0
    other_income_start: str = ""
    other_income_period: int = Field(default=1, ge=1, le=3)
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
    credit: Amounts = Field(default_factory=Amounts)
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

    @field_validator("other_income_start")
    @classmethod
    def other_income_month(cls, value: str) -> str:
        return _month_or_blank(value, "Дата начала прочих доходов должна быть месяцем")

    @model_validator(mode="after")
    def check_form(self):
        if self.planning_to < self.planning_from:
            raise ValueError("Дата окончания проекта раньше даты начала")
        if not (self.planning_from <= self.sales_start <= self.planning_to):
            raise ValueError("Дата начала продаж должна входить в период планирования")
        if not (self.planning_from <= self.planned_volume <= self.planning_to):
            raise ValueError("Дата «Выход на 100%» должна входить в период планирования")
        if self.planned_volume < self.sales_start:
            raise ValueError("Дата «Выход на 100%» не может быть раньше начала продаж")
        if any(row.count <= 0 for row in self.volume_sales):
            raise ValueError("Укажите планируемое количество продаж")
        for row in self.volume_sales:
            if row.sales_start and not (self.planning_from <= row.sales_start <= self.planning_to):
                raise ValueError("Дата начала продаж продукта должна входить в период планирования")
            if any(value < 0 for value in row.planned_capacity.values()):
                raise ValueError("Загрузка не может быть отрицательной")
        if any(value < 0 for value in self.planned_capacity.values()):
            raise ValueError("Загрузка не может быть отрицательной")
        if self.other_income_start and not (self.planning_from <= self.other_income_start <= self.planning_to):
            raise ValueError("Дата начала прочих доходов должна входить в период планирования")
        if self.financing in (1, 4):
            if not 1 <= self.investor_term <= 100:
                raise ValueError("Срок участия инвестора — от 1 до 100 лет")
            if self.investor_dividend > 100:
                raise ValueError("Дивиденды инвестора не больше 100%")
            if self.risk_premium > 100:
                raise ValueError("Премия за риск проекта не больше 100%")
        if self.financing in (2, 4):
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
            if self.loan_bank_rate > 100 or self.loan_subsidy_rate > 100:
                raise ValueError("Банковская и субсидируемая ставки не больше 100%")
            if self.loan_subsidy_term and self.loan_subsidy_term > self.loan_term:
                raise ValueError("Срок субсидии не больше срока кредита")
            if self.loan_bank_rate and self.loan_rate and self.loan_bank_rate + 0.001 < self.loan_rate:
                raise ValueError("Ставка для клиента не больше банковской")
            if self.loan_bank_rate and self.loan_rate:
                # Как в Радуге: субсидия Damu = банк − ставка клиента.
                self.loan_subsidy_rate = round(float(self.loan_bank_rate) - float(self.loan_rate), 3)
            if self.loan_bank_rate and self.loan_subsidy_rate and self.loan_bank_rate + 0.001 < self.loan_subsidy_rate:
                raise ValueError("Субсидируемая ставка не больше банковской")
            program = self.gov_program
            if program is not None and program.slug:
                raw_title = (program.title or "госпрограмме").strip()
                title = raw_title if raw_title.startswith("«") else f"«{raw_title}»"
                if not program.allows_investment:
                    raise ValueError(f"{title} не покрывает кредит на помещение и оборудование. Работайте без госпрограммы.")
                if not program.allows_wc and (self.wc_term or self.wc_grace or self.wc_rate):
                    raise ValueError(f"{title} не даёт заём на оборотные средства. Работайте без госпрограммы.")
                if not self.loan_bank_rate and not self.loan_nbk_plus_4:
                    raise ValueError(f"Для {title} укажите ставку банка или включите «НБ РК + 4%».")
                if program.grace_max_months and self.loan_grace > program.grace_max_months:
                    raise ValueError(f"Каникулы больше {program.grace_max_months} мес. — это предел программы {title}.")
                if program.allows_investment and program.rate is not None and abs(float(self.loan_rate) - float(program.rate)) > 0.001:
                    raise ValueError(f"Ставка для клиента должна быть {program.rate:g}% — так в программе {title}")
                # Срок субсидии по умолчанию = срок кредита, не больше максимума программы.
                if not self.loan_subsidy_term and self.loan_term:
                    cap = int(program.term_max_months or 0)
                    self.loan_subsidy_term = min(int(self.loan_term), cap) if cap else int(self.loan_term)
                if program.term_kind == "subsidy":
                    if not self.loan_subsidy_term:
                        raise ValueError(f"Укажите срок субсидии по программе {title}")
                    if program.term_max_months and self.loan_subsidy_term > program.term_max_months:
                        raise ValueError(
                            f"Срок субсидии {self.loan_subsidy_term} мес. больше максимума {title}: {program.term_max_months} мес."
                        )
                if self.loan_subsidy_term and self.loan_term and self.loan_subsidy_term > self.loan_term:
                    self.loan_subsidy_term = int(self.loan_term)
                if program.term_kind == "loan":
                    low = program.term_min_months or 0
                    high = program.term_max_months or 0
                    if low and self.loan_term < low:
                        raise ValueError(f"Срок {self.loan_term} мес. меньше минимума программы {title}: {low} мес.")
                    if high and self.loan_term > high:
                        raise ValueError(f"Срок {self.loan_term} мес. больше максимума программы {title}: {high} мес.")
                if program.term_kind == "subsidy" and program.rate is not None and self.wc_term:
                    if abs(float(self.wc_rate) - float(program.rate)) > 0.001:
                        raise ValueError(f"Ставка по оборотным средствам должна быть {program.rate:g}% — так в программе {title}")
        if self.financing == 4:
            for key in (*CAPEX_KEYS, *OPERATING_KEYS):
                external = getattr(self.attracted, key)
                loan = getattr(self.credit, key)
                if round(loan) > round(external):
                    raise ValueError("Кредит больше привлекаемой суммы по одной из статей")
        for label, rows in (
            ("оборудования", self.equipment),
            ("недвижимости", self.premises),
            ("СМР", self.smr),
            ("материалов", self.overhaul),
        ):
            for row in rows:
                if not row.name.strip():
                    continue
                title = row.name.strip()
                missing = []
                if row.count <= 0:
                    missing.append("количество")
                if row.year < 1900:
                    missing.append("год приобретения")
                if row.life_years <= 0:
                    missing.append("срок службы")
                if missing:
                    raise ValueError(f"Для «{title}» ({label}) укажите: {', '.join(missing)}")
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
    facts = card_facts(data)
    return facts["industry"], facts["horizon_months"]


def _shift_month(start: str, offset: int) -> str:
    if not start or len(start) < 7:
        return ""
    year, month = int(start[:4]), int(start[5:7])
    index = year * 12 + (month - 1) + offset
    return f"{index // 12:04d}-{index % 12 + 1:02d}"


def card_facts(data: dict) -> dict:
    forms = {1: "ИП", 2: "ТОО", "1": "ИП", "2": "ТОО"}
    financing_names = {1: "Инвестор", 2: "Кредит", 3: "Грант", 4: "Инвестор и кредит"}
    company = data.get("company")
    if isinstance(company, dict) and "horizon_months" in company:
        start = str(company.get("start_date") or "")[:7]
        horizon = int(company.get("horizon_months") or 36)
        goal = str((data.get("market") or {}).get("funding_goal") or "")
        short = {
            "долевое финансирование (инвестор)": "Инвестор",
            "долговое (кредитное) финансирование": "Кредит",
            "грантовое финансирование": "Грант",
            "инвестор и кредит": "Инвестор и кредит",
        }
        legal = str(company.get("legal_form") or "")
        return {
            "industry": company.get("industry") or legal,
            "horizon_months": horizon,
            "legal_form": legal,
            "planning_from": start,
            "planning_to": _shift_month(start, horizon - 1) if start else "",
            "financing": short.get(goal, goal),
            "gov_program": "",
        }
    legal_raw = data.get("legal_form")
    try:
        financing_key = int(data.get("financing"))
    except (TypeError, ValueError):
        financing_key = None
    start = str(data.get("planning_from") or "")[:7]
    end = str(data.get("planning_to") or "")[:7]
    legal = forms.get(legal_raw, forms.get(str(legal_raw), ""))
    titles = []
    if financing_key in (2, 4):
        for key in ("gov_program", "gov_guarantee"):
            item = data.get(key) or {}
            if isinstance(item, dict):
                title = str(item.get("title") or "").strip()
                if title:
                    titles.append(title)
    return {
        "industry": legal,
        "horizon_months": months_between(start, end) if start and end else 0,
        "legal_form": legal,
        "planning_from": start,
        "planning_to": end,
        "financing": financing_names.get(financing_key, ""),
        "gov_program": " + ".join(titles),
    }


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


def _loans(form: ProjectFormIn, capex_amount: float, operating_amount: float) -> list[LoanIn]:
    loans: list[LoanIn] = []
    if capex_amount <= 0 and operating_amount <= 0:
        return loans
    program = form.gov_program
    allow_wc = not (program is not None and program.slug and program.allows_wc is False)
    rate = float(form.loan_rate)
    term = int(form.loan_term or 36)
    grace = int(form.loan_grace or 0)
    if program is not None and program.slug:
        if program.rate is not None:
            rate = float(program.rate)
        if program.term_kind == "loan":
            low = int(program.term_min_months or 0)
            high = int(program.term_max_months or 0)
            if high and term > high:
                term = high
            if low and term < low:
                term = low
        if program.grace_max_months:
            grace = min(grace, int(program.grace_max_months))
    wc_amount = operating_amount if allow_wc and form.wc_term and operating_amount else 0.0
    if wc_amount:
        wc_rate = float(form.wc_rate)
        if program is not None and program.slug and program.term_kind == "subsidy" and program.rate is not None:
            wc_rate = float(program.rate)
        loans.append(
            LoanIn(
                name="Займ на пополнение оборотных средств",
                amount=wc_amount,
                issue_date=form.planning_from,
                rate=wc_rate,
                term_months=form.wc_term,
                grace_months=form.wc_grace,
                method="differentiated",
            )
        )
        main_amount = capex_amount
    elif allow_wc:
        main_amount = capex_amount + operating_amount
    else:
        # Без оборотки по программе в кредит идёт только CAPEX.
        main_amount = capex_amount
    if main_amount:
        title = "Займ на инвестиционные цели"
        if program is not None and program.slug and program.title:
            title = f"Займ на инвестиционные цели ({program.title})"
        loans.append(
            LoanIn(
                name=title,
                amount=main_amount,
                issue_date=form.planning_from,
                rate=rate,
                term_months=term,
                grace_months=grace,
                method="annuity" if form.loan_annuity else "differentiated",
            )
        )
    return loans


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
    credit = sum(getattr(form.credit, key) for key in (*CAPEX_KEYS, *OPERATING_KEYS)) if form.financing == 4 else 0
    investor_amount = attracted if form.financing == 1 else max(0, attracted - credit) if form.financing == 4 else 0
    if form.financing == 2:
        loan_capex = sum(getattr(form.attracted, key) for key in CAPEX_KEYS)
        loan_operating = sum(getattr(form.attracted, key) for key in OPERATING_KEYS)
    elif form.financing == 4:
        loan_capex = sum(getattr(form.credit, key) for key in CAPEX_KEYS)
        loan_operating = sum(getattr(form.credit, key) for key in OPERATING_KEYS)
    else:
        loan_capex = 0
        loan_operating = 0
    loans = _loans(form, loan_capex, loan_operating)
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
            investor_amount=investor_amount,
            investor_date=form.planning_from if form.financing in (1, 4) else "",
            investor_share=form.investor_dividend if form.financing in (1, 4) else 0,
            grant_amount=attracted if form.financing == 3 else 0,
            loans=loans,
        ),
    )
