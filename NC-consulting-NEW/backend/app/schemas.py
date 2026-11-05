from datetime import datetime
from typing import Annotated, Literal

from pydantic import BaseModel, BeforeValidator, ConfigDict, EmailStr, Field


def _empty_to_zero(value):
    return 0 if value in ("", None) else value


Num = Annotated[float, BeforeValidator(_empty_to_zero), Field(ge=0)]
SignedNum = Annotated[float, BeforeValidator(_empty_to_zero)]
Int = Annotated[int, BeforeValidator(_empty_to_zero), Field(ge=0)]
Text = Annotated[str, Field(max_length=255)]
LongText = Annotated[str, Field(max_length=5000)]
Month = Annotated[str, Field(max_length=7, pattern=r"^(\d{4}-\d{2})?$")]
MAX_ITEMS = 30


def _items():
    return Field(default_factory=list, max_length=MAX_ITEMS)


class RegisterIn(BaseModel):
    email: EmailStr
    full_name: str = Field(min_length=1, max_length=255)
    company: str | None = Field(default=None, max_length=255)
    phone: str | None = Field(default=None, max_length=50)
    password: str = Field(min_length=8, max_length=72)


class LoginIn(BaseModel):
    email: EmailStr
    password: str


class ProfileUpdateIn(BaseModel):
    full_name: str = Field(min_length=1, max_length=255)
    company: str | None = Field(default=None, max_length=255)
    phone: str | None = Field(default=None, max_length=50)


class PasswordChangeIn(BaseModel):
    current_password: str = Field(min_length=1, max_length=72)
    new_password: str = Field(min_length=8, max_length=72)


class TariffOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    name: str
    period: Literal["month", "year"]
    price: int
    models_limit: int
    edits_limit: int
    companies_limit: int
    plans_limit: int
    features: list[str]
    is_default: bool


class PayUrlOut(BaseModel):
    url: str


class InvoiceStatusOut(BaseModel):
    id: int
    paid: bool
    amount: int
    service: str


class TopupIn(BaseModel):
    amount: int = Field(ge=100, le=5_000_000)
    phone: str = Field(min_length=5, max_length=50)
    email: EmailStr


class RefillIn(BaseModel):
    full_name: str = Field(min_length=1, max_length=100)
    phone: str = Field(min_length=5, max_length=50)
    amount: int = Field(ge=100, le=5_000_000)
    comment: str = Field(default="", max_length=200)


class TariffBuyIn(BaseModel):
    email: EmailStr
    phone: str = Field(min_length=5, max_length=50)
    comment: str = Field(default="", max_length=500)
    confirm: bool


class SubscriptionOut(BaseModel):
    name: str
    period: str
    price: int
    models_limit: int
    edits_limit: int
    features: list[str]
    started_at: datetime
    expires_at: datetime | None
    everlasting: bool


class PaymentOut(BaseModel):
    id: int
    amount: int
    comment: str
    created_at: datetime


class TariffCurrentOut(BaseModel):
    balance: int
    models_used: int
    models_left: int
    expired: bool
    days_left: int | None
    expire_progress: int
    subscription: SubscriptionOut | None
    payments: list[PaymentOut]


class UserOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    email: EmailStr
    full_name: str
    company: str | None
    phone: str | None
    avatar_url: str | None = None
    balance: int = 0
    created_at: datetime


# Financial model input. Percent fields are stored in percent units (15 means 15%),
# months are "YYYY-MM" strings; an empty month means "from the project start".


class CompanyIn(BaseModel):
    name: str = Field(min_length=1, max_length=255)
    legal_form: Text = "ТОО"
    industry: Text = ""
    activity: Text = ""
    country: Text = "Казахстан"
    city: Text = ""
    stage: Text = "Стартап"
    description: LongText = ""
    target_audience: LongText = ""
    advantages: LongText = ""

    currency: Literal["KZT", "USD", "EUR"] = "KZT"
    start_date: Month = ""
    horizon_months: int = Field(default=36, ge=1, le=240)
    tax_regime: Literal["general", "simplified", "retail"] = "general"
    tax_mode: Literal["auto", "manual"] = "auto"
    cit_rate: Num = 20
    turnover_tax_rate: Num = 0
    employer_tax_rate: Num = 15
    vat_payer: bool = False
    inflation: Num = 8
    business_growth: SignedNum = 10
    discount_mode: Literal["manual", "auto"] = "manual"
    discount_rate: Num = 15
    opening_cash: Num = 0


class NamedNote(BaseModel):
    name: Text = ""
    note: Text = ""


class MarketIn(BaseModel):
    problem: LongText = ""
    market_size: LongText = ""
    market_unknown: bool = False
    competitors: list[NamedNote] = _items()
    suppliers: list[NamedNote] = _items()
    channels: list[Text] = _items()
    marketing: LongText = ""
    risks: LongText = ""
    funding_goal: LongText = ""


class ProductIn(BaseModel):
    name: Text = ""
    kind: Literal["goods", "service"] = "goods"
    unit: Text = "шт."
    price: Num = 0
    volume: Num = 0
    start_date: Month = ""
    growth: SignedNum = 0
    price_indexation: Num = 0
    seasonality: Literal["none", "summer", "winter", "year_end", "spring_autumn"] = "none"
    cogs_unit: Num = 0
    discount_pct: Num = 0
    prepayment_pct: Num = 100
    deferral_days: Int = 0


class RevenueIn(BaseModel):
    products: list[ProductIn] = _items()

    subscription_enabled: bool = False
    subscribers_start: Num = 0
    subscription_fee: Num = 0
    other_income_amount: Num = 0
    other_income_period: Literal["monthly", "quarterly", "yearly"] = "monthly"
    one_time_amount: Num = 0
    one_time_date: Month = ""

    leads_per_month: Num = 0
    conversion_pct: Num = 0
    churn_pct: Num = 0
    client_growth_pct: SignedNum = 0
    capacity_units: Num = 0
    inventory_amount: Num = 0
    inventory_days: Int = 0


class StaffIn(BaseModel):
    position: Text = ""
    count: Num = 0
    salary: Num = 0
    start_date: Month = ""
    indexation: Num = 0
    bonus_type: Literal["none", "amount", "pct"] = "none"
    bonus_value: Num = 0
    tax_mode: Literal["auto", "manual"] = "auto"
    tax_rate: Num = 0
    growth_per_year: Num = 0


class CostIn(BaseModel):
    name: Text = ""
    category: Text = "Прочее"
    kind: Literal["fixed", "per_unit", "pct_revenue"] = "fixed"
    value: Num = 0
    period: Literal["once", "monthly", "yearly"] = "monthly"
    start_date: Month = ""
    end_date: Month = ""
    indexation: Num = 0
    deferral_days: Int = 0


class AssetIn(BaseModel):
    name: Text = ""
    category: Text = "Оборудование"
    cost: Num = 0
    quantity: Num = 1
    purchase_date: Month = ""
    life_months: Int = 60
    method: Literal["linear", "other"] = "linear"
    salvage: Num = 0
    extra_capex: Num = 0
    extra_capex_date: Month = ""


class ExpensesIn(BaseModel):
    staff: list[StaffIn] = _items()
    costs: list[CostIn] = _items()
    assets: list[AssetIn] = _items()


class AmountItem(BaseModel):
    name: Text = ""
    amount: Num = 0


class Contribution(BaseModel):
    amount: Num = 0
    date: Month = ""


class LoanIn(BaseModel):
    name: Text = ""
    amount: Num = 0
    issue_date: Month = ""
    rate: Num = 0
    term_months: Int = 36
    method: Literal["annuity", "differentiated", "bullet"] = "annuity"
    grace_months: Int = 0
    commission_type: Literal["pct", "amount"] = "pct"
    commission_value: Num = 0
    frequency: Literal["monthly", "quarterly"] = "monthly"
    first_payment_date: Month = ""


class InvestmentIn(BaseModel):
    capex_mode: Literal["assets", "manual"] = "assets"
    capex_manual: Num = 0
    launch_costs: list[AmountItem] = _items()
    wc_mode: Literal["manual", "auto"] = "manual"
    working_capital: Num = 0
    contingency_type: Literal["pct", "amount"] = "pct"
    contingency_value: Num = 0
    existing_investments: Num = 0

    equity: Num = 0
    investor_amount: Num = 0
    investor_date: Month = ""
    investor_share: Num = 0
    grant_amount: Num = 0
    grant_terms: LongText = ""
    contributions: list[Contribution] = _items()

    loans: list[LoanIn] = Field(default_factory=list, max_length=10)

    scenario_opt_sales: SignedNum = 20
    scenario_opt_costs: SignedNum = -5
    scenario_pes_sales: SignedNum = -20
    scenario_pes_costs: SignedNum = 10
    investor_target_return: Num = 0
    investor_exit_date: Month = ""


class FinModelData(BaseModel):
    company: CompanyIn
    market: MarketIn = Field(default_factory=MarketIn)
    revenue: RevenueIn = Field(default_factory=RevenueIn)
    expenses: ExpensesIn = Field(default_factory=ExpensesIn)
    investment: InvestmentIn = Field(default_factory=InvestmentIn)


class FinModelOut(BaseModel):
    id: int
    name: str
    industry: str
    horizon_months: int
    created_at: datetime


class CabinetCompanyIn(BaseModel):
    name: str = Field(min_length=3, max_length=256)
    legal_form: Literal["1", "2"]
    tax_regime: Literal["1", "4"]
    currency: Literal["kzt", "usd", "eur"]
    nds: bool
    description: str = Field(default="", max_length=1000)


class CabinetCompanyOut(CabinetCompanyIn):
    id: int
    created_at: datetime


class RequisiteIn(BaseModel):
    name: str = Field(min_length=2, max_length=255)
    legal_address: str = Field(default="", max_length=255)
    actual_address: str = Field(default="", max_length=255)
    email: str = Field(default="", max_length=255)
    phone: str = Field(default="", max_length=50)
    director: str = Field(default="", max_length=255)
    bin: str = Field(default="", max_length=12)
    iik: str = Field(default="", max_length=34)
    bank_name: str = Field(default="", max_length=100)
    bik: str = Field(default="", max_length=20)


class RequisiteOut(RequisiteIn):
    id: int


class CounterpartyIn(BaseModel):
    name: str = Field(min_length=2, max_length=255)
    legal_address: str = Field(min_length=3, max_length=255)
    actual_address: str = Field(default="", max_length=255)
    email: EmailStr
    phone: str = Field(default="", max_length=50)
    director: str = Field(min_length=3, max_length=255)
    bin: str = Field(min_length=12, max_length=12)
    iik: str = Field(min_length=10, max_length=34)
    bank_name: str = Field(min_length=2, max_length=100)
    bik: str = Field(default="", max_length=20)


class CounterpartyOut(CounterpartyIn):
    id: int
    created_at: datetime
