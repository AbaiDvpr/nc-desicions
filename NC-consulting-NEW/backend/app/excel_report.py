from collections.abc import Callable
from datetime import date, datetime
from io import BytesIO
from math import ceil
from zoneinfo import ZoneInfo

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter
from openpyxl.worksheet.datavalidation import DataValidation
from openpyxl.worksheet.worksheet import Worksheet

from app.currencies import in_phrase
from app.models import User
from app.schemas import AssetIn, FinModelData

LOCAL_TZ = ZoneInfo("Asia/Almaty")

S_ASSUMPTIONS = "Предпосылки"
S_FUNDING = "Структура финансирования"
S_REVENUE = "План доходов"
S_PAYROLL = "ФОТ"
S_ADMIN = "Адм расходы"
S_DEPRECIATION = "Амортизация"
S_LOAN = "График платежей"
S_PNL = "ОПиУ"
S_CASHFLOW = "ДДС"
S_ANALYSIS = "Анализ эффект."

MONTH_NAMES = ["янв", "фев", "мар", "апр", "май", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"]

SEASONALITY = {
    "none": [1.0] * 12,
    "summer": [0.7, 0.7, 0.8, 0.9, 1.1, 1.3, 1.4, 1.4, 1.1, 0.9, 0.8, 0.9],
    "winter": [1.4, 1.3, 1.1, 0.9, 0.8, 0.7, 0.7, 0.7, 0.8, 1.0, 1.2, 1.4],
    "year_end": [0.8, 0.8, 0.9, 0.9, 0.9, 0.9, 0.9, 0.9, 1.0, 1.1, 1.3, 1.6],
    "spring_autumn": [0.8, 0.8, 1.1, 1.2, 1.2, 0.9, 0.8, 0.8, 1.1, 1.2, 1.2, 0.9],
}
SEASONALITY_LABELS = {
    "none": "Нет", "summer": "Летний пик", "winter": "Зимний пик",
    "year_end": "Пик в конце года", "spring_autumn": "Весна и осень",
}
TAX_REGIMES = {
    "general": "Общеустановленный режим",
    "simplified": "Упрощённая декларация",
    "retail": "Розничный налог",
}
AUTO_TAX_RATES = {"general": (20, 0), "simplified": (0, 4), "retail": (0, 4)}
AUTO_RISK_PREMIUM = 7
LOAN_METHODS = {
    "annuity": "Аннуитетный",
    "differentiated": "Дифференцированный",
    "bullet": "Индивидуальный (погашение в конце срока)",
}
COST_KINDS = {"fixed": "Постоянный", "per_unit": "На единицу продаж", "pct_revenue": "% от выручки"}
PERIODS = {"once": "Разово", "monthly": "Ежемесячно", "yearly": "Ежегодно", "quarterly": "Ежеквартально"}
BONUS_TYPES = {"none": "Нет", "amount": "Сумма в мес.", "pct": "% от оклада"}
SCENARIOS = ["Базовый", "Оптимистичный", "Пессимистичный"]

MONEY = "#,##0;[Red]-#,##0"
PERCENT = "0.0%"
NUMBER = "#,##0"
DECIMAL = "0.00"
YEARS_FMT = "0.0"

TITLE_FONT = Font(size=16, bold=True, color="1A2233")
SUBTITLE_FONT = Font(size=10, color="6B7489")
HEADER_FONT = Font(bold=True, color="FFFFFF")
INDEX_FONT = Font(size=8, color="9AA3B5")
BOLD = Font(bold=True)
MUTED = Font(italic=True, color="8A93A6")
HEADER_FILL = PatternFill("solid", fgColor="2F5BEA")
ANNUAL_FILL = PatternFill("solid", fgColor="1D3FB8")
TOTAL_FILL = PatternFill("solid", fgColor="EAF0FF")
SECTION_FILL = PatternFill("solid", fgColor="F4F6FB")
INPUT_FILL = PatternFill("solid", fgColor="FFF6D6")
THIN = Side(style="thin", color="E3E7EF")
BORDER = Border(left=THIN, right=THIN, top=THIN, bottom=THIN)
WRAP = Alignment(wrap_text=True, vertical="top")

FIRST = 3  # first month column on every monthly sheet


def col(index: int) -> str:
    return get_column_letter(index)


def ref(sheet: str, cell: str) -> str:
    return f"'{sheet}'!{cell}"


def pct(value: float) -> float:
    return value / 100


def title(ws: Worksheet, text: str, subtitle: str) -> None:
    ws["A1"] = text
    ws["A1"].font = TITLE_FONT
    ws["A2"] = subtitle
    ws["A2"].font = SUBTITLE_FONT


def header(ws: Worksheet, row: int, labels: list[str], first_col: int = 1) -> None:
    for c, label in enumerate(labels, start=first_col):
        cell = ws.cell(row=row, column=c, value=label)
        cell.font = HEADER_FONT
        cell.fill = HEADER_FILL
        cell.border = BORDER
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)


def style_cells(ws: Worksheet, row: int, first_col: int, last_col: int, fmt: str | None = None,
                *, bold: bool = False, fill: PatternFill | None = None) -> None:
    for c in range(first_col, last_col + 1):
        cell = ws.cell(row=row, column=c)
        cell.border = BORDER
        if fmt:
            cell.number_format = fmt
        if bold:
            cell.font = BOLD
        if fill:
            cell.fill = fill


def mark_input(ws: Worksheet, *cells: str) -> None:
    for address in cells:
        ws[address].fill = INPUT_FILL


def widths(ws: Worksheet, sizes: dict[str, float]) -> None:
    for letter, size in sizes.items():
        ws.column_dimensions[letter].width = size


def parse_month(value: str) -> tuple[int, int] | None:
    if not value:
        return None
    year, month = value.split("-")
    return int(year), int(month)


class _ModelWriter:
    def __init__(self, user: User, data: FinModelData):
        self.user = user
        self.data = data
        self.months = data.company.horizon_months
        self.n_years = ceil(self.months / 12)
        start = parse_month(data.company.start_date)
        if start is None:
            today = date.today()
            start = (today.year + today.month // 12, today.month % 12 + 1)
        self.start_year, self.start_month = start
        self.now = datetime.now(LOCAL_TZ).strftime("%d.%m.%Y %H:%M")
        currency = in_phrase(data.company.currency)
        self.subtitle = (
            f"{data.company.name} · NC-Desicion · сгенерировано NC-AI · {self.now} · суммы в {currency}"
        )
        self.wb = Workbook()
        self.p: dict[str, str] = {}

        assets = [a for a in data.expenses.assets]
        if data.investment.capex_mode == "manual" and data.investment.capex_manual > 0:
            assets.append(AssetIn(name="Капитальные вложения (без детализации)", category="Другое",
                                  cost=data.investment.capex_manual, quantity=1, life_months=60))
        self.assets = [
            a.model_copy(update={"name": a.name or f"Основное средство {i}"})
            for i, a in enumerate(assets, start=1)
        ]
        self.products = [
            p.model_copy(update={"name": p.name or f"Продукт / услуга {i}"})
            for i, p in enumerate(data.revenue.products, start=1)
        ]
        self.staff = [
            s.model_copy(update={"position": s.position or f"Должность {i}"})
            for i, s in enumerate(data.expenses.staff, start=1)
        ]
        self.costs = [
            c.model_copy(update={"name": c.name or f"Статья расходов {i}"})
            for i, c in enumerate(data.expenses.costs, start=1)
        ]
        self.loans = [
            loan.model_copy(update={"name": loan.name or f"Кредит {i}"})
            for i, loan in enumerate(data.investment.loans, start=1)
        ]

    # Time helpers

    def idx(self, value: str, default: int = 1) -> int:
        parsed = parse_month(value)
        if parsed is None:
            return default
        year, month = parsed
        return max(1, (year - self.start_year) * 12 + (month - self.start_month) + 1)

    def calendar_month(self, m: int) -> int:
        return (self.start_month - 1 + m - 1) % 12

    def month_label(self, m: int) -> str:
        total = self.start_month - 1 + m - 1
        return f"{MONTH_NAMES[total % 12]} {self.start_year + total // 12}"

    def mc(self, m: int) -> str:
        return col(FIRST + m - 1)

    @property
    def total_col(self) -> int:
        return FIRST + self.months

    def year_cols(self, y: int) -> tuple[str, str]:
        start = (y - 1) * 12 + 1
        end = min(y * 12, self.months)
        return self.mc(start), self.mc(end)

    def months_range(self, row: int, until: int | None = None) -> str:
        return f"{self.mc(1)}{row}:{self.mc(until or self.months)}{row}"

    # Grid helpers

    def grid_header(self, ws: Worksheet, row: int, lead: list[str], annual: bool = False) -> None:
        """Index row (month numbers) followed by the month header row."""
        ws.cell(row=row, column=1, value="№ месяца").font = INDEX_FONT
        for m in range(1, self.months + 1):
            cell = ws.cell(row=row, column=FIRST + m - 1, value=m)
            cell.font = INDEX_FONT
            cell.alignment = Alignment(horizontal="center")
        labels = lead + [self.month_label(m) for m in range(1, self.months + 1)] + ["Итого"]
        header(ws, row + 1, labels)
        if annual:
            header(ws, row + 1, [f"Год {y}" for y in range(1, self.n_years + 1)], self.total_col + 1)
            for c in range(self.total_col + 1, self.total_col + 1 + self.n_years):
                ws.cell(row=row + 1, column=c).fill = ANNUAL_FILL

    def grid_row(
        self,
        ws: Worksheet,
        row: int,
        label: str,
        unit: str,
        value: Callable[[int, str], object],
        fmt: str = MONEY,
        *,
        total: str | None = "sum",
        annual: str | None = None,
        bold: bool = False,
        inputs: bool = False,
    ) -> int:
        ws.cell(row=row, column=1, value=label)
        ws.cell(row=row, column=2, value=unit)
        for m in range(1, self.months + 1):
            ws.cell(row=row, column=FIRST + m - 1, value=value(m, self.mc(m)))
            if inputs:
                ws.cell(row=row, column=FIRST + m - 1).fill = INPUT_FILL
        if total == "sum":
            ws.cell(row=row, column=self.total_col, value=f"=SUM({self.months_range(row)})")
        elif total == "first":
            ws.cell(row=row, column=self.total_col, value=f"={self.mc(1)}{row}")
        elif total == "last":
            ws.cell(row=row, column=self.total_col, value=f"={self.mc(self.months)}{row}")
        last_col = self.total_col
        if annual:
            for y in range(1, self.n_years + 1):
                first_m, last_m = self.year_cols(y)
                formula = {
                    "sum": f"=SUM({first_m}{row}:{last_m}{row})",
                    "first": f"={first_m}{row}",
                    "last": f"={last_m}{row}",
                }[annual]
                ws.cell(row=row, column=self.total_col + y, value=formula)
            last_col = self.total_col + self.n_years
        style_cells(ws, row, 1, last_col, bold=bold, fill=TOTAL_FILL if bold else None)
        style_cells(ws, row, FIRST, last_col, fmt)
        if inputs:
            for m in range(1, self.months + 1):
                ws.cell(row=row, column=FIRST + m - 1).fill = INPUT_FILL
        return row

    def sum_rows(self, rows: list[int], m_letter: str) -> str:
        return "=" + "+".join(f"{m_letter}{r}" for r in rows) if rows else "=0"

    def grid_widths(self, ws: Worksheet, label_width: float = 40, annual: bool = False) -> None:
        ws.column_dimensions["A"].width = label_width
        ws.column_dimensions["B"].width = 8
        last = self.total_col + (self.n_years if annual else 0)
        for c in range(FIRST, last + 1):
            ws.column_dimensions[col(c)].width = 13
        ws.column_dimensions[col(self.total_col)].width = 16

    def section(self, ws: Worksheet, row: int, text: str, annual: bool = False) -> None:
        last = self.total_col + (self.n_years if annual else 0)
        style_cells(ws, row, 1, last, fill=SECTION_FILL)
        ws.cell(row=row, column=1, value=text).font = BOLD

    def params_table(self, ws: Worksheet, row: int, labels: list[str],
                     rows: list[list[tuple[object, str, bool]]]) -> list[int]:
        """Header + rows of (value, number_format, is_input) cells. Returns row numbers."""
        header(ws, row, labels)
        ws.row_dimensions[row].height = 45
        out = []
        for offset, cells in enumerate(rows, start=1):
            r = row + offset
            for c, (value, fmt, is_input) in enumerate(cells, start=1):
                cell = ws.cell(row=r, column=c, value=value)
                cell.border = BORDER
                if fmt:
                    cell.number_format = fmt
                if is_input:
                    cell.fill = INPUT_FILL
            out.append(r)
        return out

    def growth(self, rate_ref: str, m: int) -> str:
        """Step-wise annual indexation factor for month ``m``."""
        return f"(1+{rate_ref})^INT(({m}-1)/12)"

    # Build

    def build(self) -> bytes:
        wb = self.wb
        wb.active.title = S_ASSUMPTIONS
        for name in (S_FUNDING, S_REVENUE, S_PAYROLL, S_ADMIN, S_DEPRECIATION,
                     S_LOAN, S_PNL, S_CASHFLOW, S_ANALYSIS):
            wb.create_sheet(name)

        self.sheet_assumptions()
        self.sheet_revenue()
        self.sheet_payroll()
        self.sheet_admin()
        self.sheet_depreciation()
        self.sheet_loans()
        self.sheet_funding()
        self.sheet_pnl()
        self.sheet_cashflow()
        self.sheet_analysis()

        buffer = BytesIO()
        wb.save(buffer)
        return buffer.getvalue()

    # 1. Предпосылки

    def sheet_assumptions(self) -> None:
        ws = self.wb[S_ASSUMPTIONS]
        c, market, inv = self.data.company, self.data.market, self.data.investment
        title(ws, "1. Предпосылки", self.subtitle)

        def text_block(row: int, heading: str, items: list[tuple[str, str]]) -> int:
            header(ws, row, [heading, "Значение"])
            ws.merge_cells(start_row=row, start_column=2, end_row=row, end_column=4)
            row += 1
            for label, value in items:
                ws.cell(row=row, column=1, value=label).font = BOLD
                ws.cell(row=row, column=2, value=value or "—").alignment = WRAP
                ws.merge_cells(start_row=row, start_column=2, end_row=row, end_column=4)
                style_cells(ws, row, 1, 4)
                length = len(str(value or ""))
                if length > 70:
                    ws.row_dimensions[row].height = min(15 * (length // 70 + 1), 300)
                row += 1
            return row + 1

        location = ", ".join(x for x in (c.country, c.city) if x)
        row = text_block(4, "Основная информация", [
            ("Компания / проект", c.name),
            ("Организационно-правовая форма", c.legal_form),
            ("Отрасль", c.industry),
            ("Вид деятельности", c.activity),
            ("Регион реализации", location),
            ("Стадия бизнеса", c.stage),
            ("Описание проекта", c.description),
            ("Целевая аудитория", c.target_audience),
            ("Конкурентные преимущества", c.advantages),
        ])

        def names(items) -> str:
            return "; ".join(f"{i.name} — {i.note}" if i.note else i.name for i in items if i.name)

        row = text_block(row, "Рынок и бизнес-план", [
            ("Проблема, которую решает бизнес", market.problem),
            ("Размер и особенности рынка", "Неизвестно" if market.market_unknown else market.market_size),
            ("Основные конкуренты", names(market.competitors)),
            ("Поставщики и партнёры", names(market.suppliers)),
            ("Каналы продаж", ", ".join(market.channels)),
            ("Маркетинговая стратегия", market.marketing),
            ("Основные риски", market.risks),
            ("Цель финансирования", market.funding_goal),
        ])

        if c.tax_mode == "auto":
            cit, turnover = AUTO_TAX_RATES[c.tax_regime]
            tax_comment = "Ориентировочно по налоговому режиму — проверьте"
        else:
            cit, turnover = c.cit_rate, c.turnover_tax_rate
            tax_comment = "Задано вручную"
        if c.discount_mode == "auto":
            discount = round((1 + pct(c.inflation)) * (1 + pct(AUTO_RISK_PREMIUM)) - 1, 4)
            discount_comment = f"Рассчитано: инфляция + премия за риск {AUTO_RISK_PREMIUM}%"
        else:
            discount = pct(c.discount_rate)
            discount_comment = "Задано вручную"

        header(ws, row, ["Параметры финансовой модели", "Значение", "Комментарий"])
        ws.merge_cells(start_row=row, start_column=3, end_row=row, end_column=4)
        row += 1
        params = [
            (None, "Валюта модели", c.currency, "@", "", False),
            (None, "Дата начала проекта", self.month_label(1), "@", "", False),
            (None, "Горизонт прогноза, мес.", self.months, NUMBER, "", False),
            (None, "Шаг расчёта", "Месяц", "@", "", False),
            (None, "Налоговый режим", TAX_REGIMES[c.tax_regime], "@", "", False),
            (None, "Плательщик НДС", "Да" if c.vat_payer else "Нет", "@",
             "Цены в модели указаны без НДС", False),
            ("tax_profit", "Ставка КПН (налог на прибыль)", pct(cit), PERCENT, tax_comment, True),
            ("tax_turnover", "Налог с дохода (спецрежим)", pct(turnover), PERCENT, tax_comment, True),
            ("employer_tax", "Налоги работодателя по умолчанию", pct(c.employer_tax_rate), PERCENT,
             "Соц. налог, отчисления, ОСМС, ОПВР", True),
            ("inflation", "Годовая инфляция", pct(c.inflation), PERCENT,
             "Индексация себестоимости", True),
            (None, "Ожидаемый рост бизнеса в год", pct(c.business_growth), PERCENT,
             "Ориентир для роста продаж", False),
            ("discount", "Ставка дисконтирования", discount, PERCENT, discount_comment, True),
            ("opening_cash", "Начальный остаток денег", c.opening_cash, MONEY, "", True),
            ("investor_target", "Целевая доходность инвестора", pct(inv.investor_target_return),
             PERCENT, "", True),
            (None, "Период выхода инвестора",
             self.month_label(self.idx(inv.investor_exit_date)) if inv.investor_exit_date else "—",
             "@", "", False),
        ]
        for key, label, value, fmt, comment, editable in params:
            ws.cell(row=row, column=1, value=label).font = BOLD
            ws.cell(row=row, column=2, value=value)
            ws.cell(row=row, column=3, value=comment).font = MUTED
            ws.merge_cells(start_row=row, start_column=3, end_row=row, end_column=4)
            style_cells(ws, row, 1, 4)
            ws.cell(row=row, column=2).number_format = fmt
            if key:
                self.p[key] = ref(S_ASSUMPTIONS, f"$B${row}")
            if editable:
                mark_input(ws, f"B{row}")
            row += 1

        row += 1
        header(ws, row, ["Сценарии", "Изменение продаж", "Изменение расходов"])
        scenario_values = [(0, 0), (pct(inv.scenario_opt_sales), pct(inv.scenario_opt_costs)),
                           (pct(inv.scenario_pes_sales), pct(inv.scenario_pes_costs))]
        first_sc = row + 1
        for offset, (name, (sales, costs)) in enumerate(zip(SCENARIOS, scenario_values), start=1):
            r = row + offset
            ws.cell(row=r, column=1, value=name).font = BOLD
            ws.cell(row=r, column=2, value=sales)
            ws.cell(row=r, column=3, value=costs)
            style_cells(ws, r, 1, 3)
            style_cells(ws, r, 2, 3, PERCENT)
            if offset > 1:
                mark_input(ws, f"B{r}", f"C{r}")
        last_sc = row + len(SCENARIOS)
        row = last_sc + 1

        ws.cell(row=row, column=1, value="Активный сценарий").font = BOLD
        ws.cell(row=row, column=2, value=SCENARIOS[0])
        ws.cell(row=row, column=3, value="Выберите из списка — модель пересчитается").font = MUTED
        style_cells(ws, row, 1, 3)
        mark_input(ws, f"B{row}")
        validation = DataValidation(type="list", formula1=f'"{",".join(SCENARIOS)}"', allow_blank=False)
        ws.add_data_validation(validation)
        validation.add(f"B{row}")
        active = f"$B${row}"
        self.p["scenario"] = ref(S_ASSUMPTIONS, active)

        lookup = f"MATCH({active},$A${first_sc}:$A${last_sc},0)"
        for key, label, column in (("sales_factor", "Коэффициент продаж", "B"),
                                   ("cost_factor", "Коэффициент расходов", "C")):
            row += 1
            ws.cell(row=row, column=1, value=label).font = BOLD
            ws.cell(row=row, column=2,
                    value=f"=1+IFERROR(INDEX(${column}${first_sc}:${column}${last_sc},{lookup}),0)")
            style_cells(ws, row, 1, 3)
            ws.cell(row=row, column=2).number_format = DECIMAL
            self.p[key] = ref(S_ASSUMPTIONS, f"$B${row}")

        row += 2
        ws.cell(row=row, column=1, value="Жёлтые ячейки — вводимые данные").fill = INPUT_FILL
        ws.cell(row=row + 1, column=1, value="Остальные значения рассчитываются формулами").font = MUTED
        widths(ws, {"A": 40, "B": 30, "C": 26, "D": 26})

    # 3. План доходов

    def sheet_revenue(self) -> None:
        ws = self.wb[S_REVENUE]
        rev = self.data.revenue
        title(ws, "3. План доходов", self.subtitle)
        sf, cf, infl = self.p["sales_factor"], self.p["cost_factor"], self.p["inflation"]

        param_rows = self.params_table(
            ws, 4,
            ["Продукт / услуга", "Тип", "Ед.", "Цена, ₸", "Объём в месяц", "Начало продаж",
             "Рост продаж в год", "Индексация цены в год", "Себестоимость ед., ₸", "Скидка",
             "Предоплата", "Отсрочка, дней", "Сезонность"],
            [[
                (p.name, None, False), ("Товар" if p.kind == "goods" else "Услуга", None, False),
                (p.unit, None, False), (p.price, MONEY, True), (p.volume, NUMBER, True),
                (self.month_label(self.idx(p.start_date)), None, False),
                (pct(p.growth), PERCENT, True), (pct(p.price_indexation), PERCENT, True),
                (p.cogs_unit, MONEY, True), (pct(p.discount_pct), PERCENT, True),
                (pct(p.prepayment_pct), PERCENT, True), (p.deferral_days, NUMBER, False),
                (SEASONALITY_LABELS[p.seasonality], None, False),
            ] for p in self.products],
        )
        row = (param_rows[-1] if param_rows else 4) + 2

        extra = [
            ("subs_start", "Подписка: абонентов на старте", rev.subscribers_start if rev.subscription_enabled else 0, NUMBER),
            ("subs_fee", "Подписка: плата в месяц, ₸", rev.subscription_fee if rev.subscription_enabled else 0, MONEY),
            ("churn", "Отток клиентов в месяц", pct(rev.churn_pct), PERCENT),
            ("client_growth", "Рост клиентской базы в месяц", pct(rev.client_growth_pct), PERCENT),
            ("other_income", f"Прочие доходы ({PERIODS[rev.other_income_period].lower()}), ₸",
             rev.other_income_amount, MONEY),
            ("one_time", f"Разовый доход ({self.month_label(self.idx(rev.one_time_date))}), ₸",
             rev.one_time_amount, MONEY),
        ]
        header(ws, row, ["Дополнительные доходы", "", "Значение"])
        ep: dict[str, str] = {}
        for key, label, value, fmt in extra:
            row += 1
            ws.cell(row=row, column=1, value=label)
            ws.cell(row=row, column=3, value=value).number_format = fmt
            style_cells(ws, row, 1, 3)
            mark_input(ws, f"C{row}")
            ep[key] = f"$C${row}"

        row += 2
        self.grid_header(ws, row, ["Показатель", "Ед."])
        row += 2
        revenue_rows, cogs_rows, unit_rows, receipt_rows = [], [], [], []

        for product, pr in zip(self.products, param_rows):
            self.section(ws, row, product.name)
            row += 1
            start = self.idx(product.start_date)
            pattern = SEASONALITY[product.seasonality]
            coef_row = self.grid_row(ws, row, "Сезонный коэффициент", "",
                                     lambda m, L, pattern=pattern: pattern[self.calendar_month(m)], DECIMAL,
                                     total=None, inputs=True)
            vol_row = self.grid_row(
                ws, row + 1, "Объём продаж", product.unit,
                lambda m, L: 0 if m < start
                else f"=$E${pr}*(1+$G${pr})^(({m}-{start})/12)*{L}{coef_row}*{sf}",
                NUMBER,
            )
            price_row = self.grid_row(
                ws, row + 2, "Цена с учётом скидки", "₸",
                lambda m, L: f"=$D${pr}*{self.growth(f'$H${pr}', m)}*(1-$J${pr})", MONEY, total=None,
            )
            rev_row = self.grid_row(ws, row + 3, "Выручка", "₸",
                                    lambda m, L: f"={L}{vol_row}*{L}{price_row}")
            cogs_row = self.grid_row(
                ws, row + 4, "Себестоимость", "₸",
                lambda m, L: f"={L}{vol_row}*$I${pr}*{self.growth(infl, m)}*{cf}",
            )
            shift = round(product.deferral_days / 30)

            def receipts(m: int, L: str, rev_row=rev_row, pr=pr, shift=shift) -> str:
                deferred = f"+{self.mc(m - shift)}{rev_row}*(1-$K${pr})" if m - shift >= 1 else ""
                return f"={L}{rev_row}*$K${pr}{deferred}"

            receipt_row = self.grid_row(ws, row + 5, "Поступления от покупателей", "₸", receipts)
            revenue_rows.append(rev_row)
            cogs_rows.append(cogs_row)
            unit_rows.append(vol_row)
            receipt_rows.append(receipt_row)
            row += 7

        self.section(ws, row, "Дополнительные доходы")
        row += 1
        subs_row = self.grid_row(
            ws, row, "Подписка: абонентов", "чел.",
            lambda m, L: f"={ep['subs_start']}" if m == 1
            else f"={self.mc(m - 1)}{row}*(1+{ep['client_growth']}-{ep['churn']})",
            NUMBER, total=None,
        )
        subs_rev = self.grid_row(ws, row + 1, "Подписка: выручка", "₸",
                                 lambda m, L: f"={L}{subs_row}*{ep['subs_fee']}*{sf}")
        step = {"monthly": 1, "quarterly": 3, "yearly": 12}[rev.other_income_period]
        other_rev = self.grid_row(ws, row + 2, "Прочие доходы", "₸",
                                  lambda m, L: f"={ep['other_income']}" if (m - 1) % step == 0 else 0)
        one_time_month = self.idx(rev.one_time_date)
        one_time = self.grid_row(ws, row + 3, "Разовые доходы", "₸",
                                 lambda m, L: f"={ep['one_time']}" if m == one_time_month else 0)
        extra_rows = [subs_rev, other_rev, one_time]
        row += 5

        self.section(ws, row, "Итого")
        row += 1
        self.rev_total = self.grid_row(ws, row, "Итого выручка", "₸",
                                       lambda m, L: self.sum_rows(revenue_rows + extra_rows, L), bold=True)
        self.cogs_total = self.grid_row(ws, row + 1, "Итого себестоимость", "₸",
                                        lambda m, L: self.sum_rows(cogs_rows, L), bold=True)
        self.units_total = self.grid_row(ws, row + 2, "Итого продано единиц", "ед.",
                                         lambda m, L: self.sum_rows(unit_rows, L), NUMBER)
        self.receipts_total = self.grid_row(
            ws, row + 3, "Итого поступления от покупателей", "₸",
            lambda m, L: self.sum_rows(receipt_rows + extra_rows, L), bold=True,
        )
        self.grid_widths(ws, 30)
        widths(ws, {"B": 10, "C": 14, "D": 14, "E": 14, "F": 14, "G": 13, "H": 14,
                    "I": 15, "J": 13, "K": 13, "L": 13, "M": 16})

    # 4. ФОТ

    def sheet_payroll(self) -> None:
        ws = self.wb[S_PAYROLL]
        title(ws, "4. ФОТ", self.subtitle)
        param_rows = self.params_table(
            ws, 4,
            ["Должность", "Кол-во", "Оклад, ₸/мес", "Выход на работу", "Индексация в год",
             "Бонус", "Размер бонуса", "Налоги работодателя", "Рост штата, чел./год"],
            [[
                (s.position, None, False), (s.count, NUMBER, True), (s.salary, MONEY, True),
                (self.month_label(self.idx(s.start_date)), None, False),
                (pct(s.indexation), PERCENT, True), (BONUS_TYPES[s.bonus_type], None, False),
                (pct(s.bonus_value) if s.bonus_type == "pct" else s.bonus_value,
                 PERCENT if s.bonus_type == "pct" else MONEY, s.bonus_type != "none"),
                (pct(s.tax_rate) if s.tax_mode == "manual" else f"={self.p['employer_tax']}",
                 PERCENT, s.tax_mode == "manual"),
                (s.growth_per_year, NUMBER, True),
            ] for s in self.staff],
        )
        row = (param_rows[-1] if param_rows else 4) + 2
        self.grid_header(ws, row, ["Показатель", "Ед."])
        row += 2
        heads, pay_rows, tax_rows = [], [], []
        for person, pr in zip(self.staff, param_rows):
            self.section(ws, row, person.position)
            start = self.idx(person.start_date)
            heads.append(self.grid_row(
                ws, row + 1, "Численность", "чел.",
                lambda m, L: 0 if m < start else f"=$B${pr}+$I${pr}*INT(({m}-{start})/12)",
                NUMBER, total=None,
            ))
            head_row = heads[-1]

            def payroll(m: int, L: str, pr=pr, head_row=head_row, bonus=person.bonus_type) -> str:
                salary = f"$C${pr}*{self.growth(f'$E${pr}', m)}"
                if bonus == "pct":
                    salary = f"{salary}*(1+$G${pr})"
                elif bonus == "amount":
                    salary = f"({salary}+$G${pr})"
                return f"={L}{head_row}*{salary}"

            pay_rows.append(self.grid_row(ws, row + 2, "ФОТ", "₸", payroll))
            tax_rows.append(self.grid_row(ws, row + 3, "Налоги работодателя", "₸",
                                          lambda m, L, r=row + 2, pr=pr: f"={L}{r}*$H${pr}"))
            row += 5

        self.section(ws, row, "Итого")
        self.headcount_total = self.grid_row(ws, row + 1, "Итого численность", "чел.",
                                             lambda m, L: self.sum_rows(heads, L), NUMBER, total=None)
        pay_total = self.grid_row(ws, row + 2, "Итого ФОТ", "₸", lambda m, L: self.sum_rows(pay_rows, L))
        tax_total = self.grid_row(ws, row + 3, "Итого налоги работодателя", "₸",
                                  lambda m, L: self.sum_rows(tax_rows, L))
        self.fot_total = self.grid_row(ws, row + 4, "Итого ФОТ с налогами", "₸",
                                       lambda m, L: f"={L}{pay_total}+{L}{tax_total}", bold=True)
        self.grid_widths(ws, 32)
        widths(ws, {"B": 10, "C": 15, "D": 14, "E": 13, "F": 13, "G": 14, "H": 14, "I": 14})

    # 5. Адм расходы

    def sheet_admin(self) -> None:
        ws = self.wb[S_ADMIN]
        title(ws, "5. Административные и операционные расходы", self.subtitle)
        cf = self.p["cost_factor"]
        param_rows = self.params_table(
            ws, 4,
            ["Статья", "Категория", "Тип расхода", "Размер", "Периодичность", "Начало", "Окончание",
             "Индексация в год", "Отсрочка оплаты, дней"],
            [[
                (c.name, None, False), (c.category, None, False), (COST_KINDS[c.kind], None, False),
                (pct(c.value) if c.kind == "pct_revenue" else c.value,
                 PERCENT if c.kind == "pct_revenue" else MONEY, True),
                (PERIODS[c.period], None, False),
                (self.month_label(self.idx(c.start_date)), None, False),
                (self.month_label(self.idx(c.end_date)) if c.end_date else "до конца", None, False),
                (pct(c.indexation), PERCENT, True), (c.deferral_days, NUMBER, False),
            ] for c in self.costs],
        )
        row = (param_rows[-1] if param_rows else 4) + 2
        self.grid_header(ws, row, ["Статья", "Ед."])
        row += 2
        cost_rows: list[tuple[int, int]] = []
        units = lambda L: ref(S_REVENUE, f"{L}{self.units_total}")  # noqa: E731
        revenue = lambda L: ref(S_REVENUE, f"{L}{self.rev_total}")  # noqa: E731
        for cost, pr in zip(self.costs, param_rows):
            start = self.idx(cost.start_date)
            end = self.idx(cost.end_date, self.months) if cost.end_date else self.months

            def active(m: int, start=start, end=end, period=cost.period) -> bool:
                if m < start or m > end:
                    return False
                return {"once": m == start, "monthly": True, "yearly": (m - start) % 12 == 0}[period]

            def amount(m: int, L: str, pr=pr, kind=cost.kind, active=active) -> object:
                if not active(m):
                    return 0
                base = f"$D${pr}*{self.growth(f'$H${pr}', m)}"
                if kind == "per_unit":
                    return f"={base}*{units(L)}*{cf}"
                if kind == "pct_revenue":
                    return f"=$D${pr}*{revenue(L)}*{cf}"
                return f"={base}*{cf}"

            r = self.grid_row(ws, row, cost.name, "₸", amount)
            cost_rows.append((r, round(cost.deferral_days / 30)))
            row += 1

        row += 1
        self.adm_total = self.grid_row(ws, row, "Итого расходы (начисление)", "₸",
                                       lambda m, L: self.sum_rows([r for r, _ in cost_rows], L), bold=True)

        def paid(m: int, L: str) -> str:
            parts = [f"{self.mc(m - shift)}{r}" for r, shift in cost_rows if m - shift >= 1]
            return "=" + "+".join(parts) if parts else 0

        self.adm_paid = self.grid_row(ws, row + 1, "Итого оплата расходов (с учётом отсрочки)", "₸", paid)
        self.grid_widths(ws, 36)
        widths(ws, {"B": 14, "C": 16, "D": 14, "E": 14, "F": 12, "G": 12, "H": 13, "I": 14})

    # 6. Амортизация

    def sheet_depreciation(self) -> None:
        ws = self.wb[S_DEPRECIATION]
        title(ws, "6. Основные средства и амортизация", f"Линейный метод · {self.subtitle}")
        param_rows = self.params_table(
            ws, 4,
            ["Актив", "Категория", "Стоимость ед., ₸", "Кол-во", "Месяц покупки",
             "Срок использования, мес.", "Метод", "Ликвидационная стоимость", "Доп. капзатраты, ₸",
             "Месяц доп. затрат"],
            [[
                (a.name, None, False), (a.category, None, False), (a.cost, MONEY, True),
                (a.quantity, NUMBER, True), (self.month_label(self.idx(a.purchase_date)), None, False),
                (a.life_months, NUMBER, True), ("Линейный" if a.method == "linear" else "Иной (расчёт линейный)", None, False),
                (a.salvage, MONEY, True), (a.extra_capex, MONEY, True),
                (self.month_label(self.idx(a.extra_capex_date)) if a.extra_capex else "—", None, False),
            ] for a in self.assets],
        )
        self.asset_param_rows = param_rows
        row = (param_rows[-1] if param_rows else 4) + 2
        self.grid_header(ws, row, ["Показатель", "Ед."])
        row += 2
        capex_rows, dep_rows = [], []
        for asset, pr in zip(self.assets, param_rows):
            self.section(ws, row, asset.name)
            bought = self.idx(asset.purchase_date)
            extra_m = self.idx(asset.extra_capex_date) if asset.extra_capex else None

            def capex(m: int, L: str, pr=pr, bought=bought, extra_m=extra_m) -> object:
                parts = []
                if m == bought:
                    parts.append(f"$C${pr}*$D${pr}")
                if m == extra_m:
                    parts.append(f"$I${pr}")
                return "=" + "+".join(parts) if parts else 0

            def dep(m: int, L: str, pr=pr, bought=bought, extra_m=extra_m) -> str:
                life = f"$F${pr}"
                main = (f"IF(AND({m}>{bought},{m}<={bought}+{life}),"
                        f"MAX(0,$C${pr}*$D${pr}-$H${pr})/{life},0)")
                if extra_m:
                    main += f"+IF(AND({m}>{extra_m},{m}<={extra_m}+{life}),$I${pr}/{life},0)"
                return f"=IF({life}<=0,0,{main})"

            capex_rows.append(self.grid_row(ws, row + 1, "Приобретение", "₸", capex))
            dep_rows.append(self.grid_row(ws, row + 2, "Амортизация", "₸", dep))
            row += 4

        self.section(ws, row, "Итого")
        self.capex_total = self.grid_row(ws, row + 1, "Итого капитальные затраты", "₸",
                                         lambda m, L: self.sum_rows(capex_rows, L), bold=True)
        self.dep_total = self.grid_row(ws, row + 2, "Итого амортизация", "₸",
                                       lambda m, L: self.sum_rows(dep_rows, L), bold=True)
        cap, dp = self.capex_total, self.dep_total
        self.grid_row(
            ws, row + 3, "Остаточная стоимость на конец месяца", "₸",
            lambda m, L: f"=SUM(${self.mc(1)}{cap}:{L}{cap})-SUM(${self.mc(1)}{dp}:{L}{dp})",
            total="last",
        )
        self.grid_widths(ws, 36)
        widths(ws, {"B": 14, "C": 15, "D": 10, "E": 13, "F": 14, "G": 16, "H": 15, "I": 15, "J": 13})

    # 7. График платежей

    def sheet_loans(self) -> None:
        ws = self.wb[S_LOAN]
        title(ws, "7. График платежей по кредитам", self.subtitle)
        row = 4
        self.loan_amount_cells: list[str] = []
        blocks: list[dict[str, int]] = []

        if not self.loans:
            ws.cell(row=row, column=1, value="Кредиты и займы не привлекаются").font = MUTED
            row += 2

        for loan in self.loans:
            ws.cell(row=row, column=1, value=f"Кредит: {loan.name}").font = Font(bold=True, size=12)
            row += 1
            issue = self.idx(loan.issue_date)
            period = 3 if loan.frequency == "quarterly" else 1
            first_pay = self.idx(loan.first_payment_date, issue + period) if loan.first_payment_date else issue + period
            end = issue + loan.term_months
            pay_months = [k for k in range(first_pay, end + 1) if (k - first_pay) % period == 0]
            if loan.term_months and end not in pay_months:
                pay_months.append(end)
            principal_months = [k for k in pay_months if k > issue + loan.grace_months]
            if loan.method == "bullet":
                principal_months = [end] if loan.term_months else []
            n = len(principal_months)

            params = [
                ("amount", "Сумма кредита, ₸", loan.amount, MONEY, True),
                ("rate", "Годовая процентная ставка", pct(loan.rate), PERCENT, True),
                (None, "Дата выдачи", self.month_label(issue), "@", False),
                (None, "Срок кредита, мес.", loan.term_months, NUMBER, False),
                (None, "Тип погашения", LOAN_METHODS[loan.method], "@", False),
                (None, "Льготный период, мес.", loan.grace_months, NUMBER, False),
                (None, "Периодичность погашения", PERIODS[loan.frequency], "@", False),
                (None, "Дата первого платежа", self.month_label(first_pay), "@", False),
                ("commission", "Комиссия" + (", % от суммы" if loan.commission_type == "pct" else ", ₸"),
                 pct(loan.commission_value) if loan.commission_type == "pct" else loan.commission_value,
                 PERCENT if loan.commission_type == "pct" else MONEY, True),
            ]
            cells: dict[str, str] = {}
            for key, label, value, fmt, editable in params:
                ws.cell(row=row, column=1, value=label).font = BOLD
                ws.cell(row=row, column=2, value=value)
                style_cells(ws, row, 1, 2)
                ws.cell(row=row, column=2).number_format = fmt
                if editable:
                    mark_input(ws, f"B{row}")
                if key:
                    cells[key] = f"$B${row}"
                row += 1
            amount, rate = cells["amount"], cells["rate"]
            commission = (f"{amount}*{cells['commission']}" if loan.commission_type == "pct"
                          else cells["commission"])
            self.loan_amount_cells.append(ref(S_LOAN, amount))

            row += 1
            self.grid_header(ws, row, ["Показатель", "Ед."])
            row += 2
            r = {key: row + i for i, key in enumerate(
                ["received", "open", "accrued", "principal", "interest_paid", "commission", "close"])}
            rp = f"{rate}*{period}/12"
            last_principal = principal_months[-1] if principal_months else None

            def principal(m: int, L: str) -> object:
                if m not in principal_months:
                    return 0
                if m == last_principal:
                    return f"={L}{r['open']}"
                if loan.method == "annuity":
                    return f"=MIN({L}{r['open']},PMT({rp},{n},-{amount})-{L}{r['open']}*{rp})"
                return f"=MIN({L}{r['open']},{amount}/{n})"

            def interest_paid(m: int, L: str) -> object:
                if m not in pay_months:
                    return 0
                previous = max([k for k in pay_months if k < m], default=issue)
                return f"=SUM({self.mc(previous + 1)}{r['accrued']}:{L}{r['accrued']})"

            self.grid_row(ws, r["received"], "Получение кредита", "₸",
                          lambda m, L: f"={amount}" if m == issue else 0)
            self.grid_row(ws, r["open"], "Остаток долга на начало", "₸",
                          lambda m, L: 0 if m == 1 else f"={self.mc(m - 1)}{r['close']}", total="first")
            self.grid_row(ws, r["accrued"], "Начислено вознаграждение", "₸",
                          lambda m, L: f"={L}{r['open']}*{rate}/12")
            self.grid_row(ws, r["principal"], "Погашение основного долга", "₸", principal)
            self.grid_row(ws, r["interest_paid"], "Выплата вознаграждения", "₸", interest_paid)
            self.grid_row(ws, r["commission"], "Комиссия", "₸",
                          lambda m, L: f"={commission}" if m == issue else 0)
            self.grid_row(ws, r["close"], "Остаток долга на конец", "₸",
                          lambda m, L: f"={L}{r['open']}+{L}{r['received']}-{L}{r['principal']}",
                          total="last", bold=True)
            blocks.append(r)
            row += 9

        ws.cell(row=row, column=1, value="Итого по всем кредитам").font = Font(bold=True, size=12)
        row += 1
        self.grid_header(ws, row, ["Показатель", "Ед."])
        row += 2
        self.loan_totals: dict[str, int] = {}
        labels = [("received", "Получение кредитов"), ("accrued", "Начислено вознаграждение"),
                  ("principal", "Погашение основного долга"), ("interest_paid", "Выплата вознаграждения"),
                  ("commission", "Комиссии"), ("close", "Остаток долга на конец")]
        for offset, (key, label) in enumerate(labels):
            rows = [b[key] for b in blocks]
            self.loan_totals[key] = self.grid_row(
                ws, row + offset, label, "₸", lambda m, L, rows=rows: self.sum_rows(rows, L),
                total="last" if key == "close" else "sum", bold=True,
            )
        self.grid_widths(ws, 38)

    # 2. Структура финансирования

    def sheet_funding(self) -> None:
        ws = self.wb[S_FUNDING]
        inv = self.data.investment
        title(ws, "2. Структура финансирования", self.subtitle)

        def line(row: int, label: str, value: object, fmt: str = MONEY, *, note: str = "",
                 editable: bool = False, bold: bool = False) -> int:
            ws.cell(row=row, column=1, value=label)
            ws.cell(row=row, column=2, value=value)
            ws.cell(row=row, column=3, value=note).font = MUTED
            style_cells(ws, row, 1, 3, bold=bold, fill=TOTAL_FILL if bold else None)
            ws.cell(row=row, column=2).number_format = fmt
            if editable:
                mark_input(ws, f"B{row}")
            return row

        header(ws, 4, ["Потребность в финансировании", "Сумма, ₸", "Комментарий"])
        dep = self.asset_param_rows
        if dep:
            capex_formula = "=" + "+".join(
                f"{ref(S_DEPRECIATION, f'C{r}')}*{ref(S_DEPRECIATION, f'D{r}')}" for r in dep)
        else:
            capex_formula = 0
        capex_row = line(5, "Первоначальные капитальные вложения", capex_formula,
                         note="По активам (лист «Амортизация»)")
        row = 6
        launch_rows = []
        for item in inv.launch_costs:
            launch_rows.append(line(row, f"Запуск: {item.name or 'статья'}", item.amount, editable=True))
            row += 1
        self.launch_total = line(
            row, "Итого запуск и подготовка",
            f"=SUM(B{launch_rows[0]}:B{launch_rows[-1]})" if launch_rows else 0,
            note="Расход первого месяца в ОПиУ",
        )
        row += 1
        if inv.wc_mode == "auto":
            fot = ref(S_PAYROLL, f"{self.mc(1)}{self.fot_total}:{self.mc(min(3, self.months))}{self.fot_total}")
            adm = ref(S_ADMIN, f"{self.mc(1)}{self.adm_total}:{self.mc(min(3, self.months))}{self.adm_total}")
            self.wc_row = line(row, "Первоначальный оборотный капитал", f"=SUM({fot})+SUM({adm})",
                               note="Рассчитано: 3 месяца ФОТ и расходов")
        else:
            self.wc_row = line(row, "Первоначальный оборотный капитал", inv.working_capital, editable=True)
        row += 1
        if inv.contingency_type == "pct":
            ws.cell(row=row, column=4, value=pct(inv.contingency_value)).number_format = PERCENT
            mark_input(ws, f"D{row}")
            reserve = line(row, "Резерв на непредвиденные расходы", f"=B{capex_row}*D{row}",
                           note="% от капвложений →")
        else:
            reserve = line(row, "Резерв на непредвиденные расходы", inv.contingency_value, editable=True)
        row += 1
        existing = line(row, "Существующие вложения (уже вложено)", inv.existing_investments, editable=True)
        row += 1
        self.need_row = line(
            row, "Итого потребность в финансировании",
            f"=B{capex_row}+B{self.launch_total}+B{self.wc_row}+B{reserve}-B{existing}", bold=True,
        )
        self.capex_initial_row = capex_row

        row += 2
        header(ws, row, ["Источники финансирования", "Сумма, ₸", "Дата / условия", "Доля"])
        row += 1
        first_src = row
        self.equity_row = line(row, "Собственные средства", inv.equity, editable=True,
                               note=self.month_label(1))
        self.investor_row = line(row + 1, "Взносы инвестора", inv.investor_amount, editable=True,
                                 note=self.month_label(self.idx(inv.investor_date)))
        self.grant_row = line(row + 2, "Гранты и субсидии", inv.grant_amount, editable=True,
                              note=inv.grant_terms[:80])
        row += 3
        self.contribution_rows = []
        for contribution in inv.contributions:
            self.contribution_rows.append(
                (line(row, "Дополнительный взнос", contribution.amount, editable=True,
                      note=self.month_label(self.idx(contribution.date))),
                 self.idx(contribution.date)))
            row += 1
        for loan, cell in zip(self.loans, self.loan_amount_cells):
            line(row, f"Кредит: {loan.name}", f"={cell}", note=self.month_label(self.idx(loan.issue_date)))
            row += 1
        last_src = row - 1
        total_src = line(row, "Итого источники", f"=SUM(B{first_src}:B{last_src})", bold=True)
        for r in range(first_src, row + 1):
            ws.cell(row=r, column=4, value=f"=IF($B${total_src}=0,0,B{r}/$B${total_src})")
            ws.cell(row=r, column=4).number_format = PERCENT
            ws.cell(row=r, column=4).border = BORDER
        line(row + 1, "Профицит (+) / дефицит (−) финансирования", f"=B{total_src}-B{self.need_row}",
             bold=True)
        line(row + 2, "Доля инвестора в бизнесе", pct(inv.investor_share), PERCENT, editable=True)
        widths(ws, {"A": 44, "B": 18, "C": 30, "D": 12})

    # 8. ОПиУ

    def sheet_pnl(self) -> None:
        ws = self.wb[S_PNL]
        title(ws, "8. Отчёт о прибылях и убытках", self.subtitle)
        self.grid_header(ws, 4, ["Показатель", "Ед."], annual=True)
        pl: dict[str, int] = {}
        self.pl = pl
        keys = ["rev", "cogs", "gross", "fot", "adm", "launch", "ebitda", "dep", "ebit", "interest",
                "commission", "ebt", "tax_turnover", "tax_profit", "net", "margin"]
        for i, key in enumerate(keys):
            pl[key] = 6 + i
        launch = ref(S_FUNDING, f"$B${self.launch_total}")
        first_ebt, first_tax = f"${self.mc(1)}${pl['ebt']}", f"${self.mc(1)}${pl['tax_profit']}"

        def tax_profit(m: int, L: str) -> str:
            due = f"{self.p['tax_profit']}*MAX(0,SUM({first_ebt}:{L}{pl['ebt']}))"
            paid = f"-SUM({first_tax}:{self.mc(m - 1)}{pl['tax_profit']})" if m > 1 else "0"
            return f"=-MAX(0,{due}-({paid}))"

        lines = [
            ("rev", "Выручка", lambda m, L: f"={ref(S_REVENUE, f'{L}{self.rev_total}')}", True),
            ("cogs", "Себестоимость", lambda m, L: f"=-{ref(S_REVENUE, f'{L}{self.cogs_total}')}", False),
            ("gross", "Валовая прибыль", lambda m, L: f"={L}{pl['rev']}+{L}{pl['cogs']}", True),
            ("fot", "ФОТ с налогами", lambda m, L: f"=-{ref(S_PAYROLL, f'{L}{self.fot_total}')}", False),
            ("adm", "Административные и операционные расходы",
             lambda m, L: f"=-{ref(S_ADMIN, f'{L}{self.adm_total}')}", False),
            ("launch", "Расходы на запуск", lambda m, L: f"=-{launch}" if m == 1 else 0, False),
            ("ebitda", "EBITDA",
             lambda m, L: f"={L}{pl['gross']}+{L}{pl['fot']}+{L}{pl['adm']}+{L}{pl['launch']}", True),
            ("dep", "Амортизация", lambda m, L: f"=-{ref(S_DEPRECIATION, f'{L}{self.dep_total}')}", False),
            ("ebit", "Операционная прибыль (EBIT)", lambda m, L: f"={L}{pl['ebitda']}+{L}{pl['dep']}", True),
            ("interest", "Вознаграждение по кредитам",
             lambda m, L: f"=-{ref(S_LOAN, f'{L}{self.loan_totals['accrued']}')}", False),
            ("commission", "Комиссии по кредитам",
             lambda m, L: f"=-{ref(S_LOAN, f'{L}{self.loan_totals['commission']}')}", False),
            ("ebt", "Прибыль до налогообложения",
             lambda m, L: f"={L}{pl['ebit']}+{L}{pl['interest']}+{L}{pl['commission']}", True),
            ("tax_turnover", "Налог с дохода (спецрежим)",
             lambda m, L: f"=-{L}{pl['rev']}*{self.p['tax_turnover']}", False),
            ("tax_profit", "КПН", tax_profit, False),
            ("net", "Чистая прибыль",
             lambda m, L: f"={L}{pl['ebt']}+{L}{pl['tax_turnover']}+{L}{pl['tax_profit']}", True),
        ]
        for key, label, formula, bold in lines:
            self.grid_row(ws, pl[key], label, "₸", formula, annual="sum", bold=bold)

        margin = pl["margin"]
        self.grid_row(ws, margin, "Рентабельность по чистой прибыли", "%",
                      lambda m, L: f"=IF({L}{pl['rev']}=0,0,{L}{pl['net']}/{L}{pl['rev']})", PERCENT,
                      total=None, annual=None)
        total_letter = col(self.total_col)
        ws[f"{total_letter}{margin}"] = (
            f"=IF({total_letter}{pl['rev']}=0,0,{total_letter}{pl['net']}/{total_letter}{pl['rev']})")
        for y in range(1, self.n_years + 1):
            L = col(self.total_col + y)
            ws[f"{L}{margin}"] = f"=IF({L}{pl['rev']}=0,0,{L}{pl['net']}/{L}{pl['rev']})"
        style_cells(ws, margin, FIRST, self.total_col + self.n_years, PERCENT)
        self.grid_widths(ws, 40, annual=True)
        ws.freeze_panes = f"{col(FIRST)}6"

    # 9. ДДС

    def sheet_cashflow(self) -> None:
        ws = self.wb[S_CASHFLOW]
        inv = self.data.investment
        title(ws, "9. Движение денежных средств", self.subtitle)
        self.grid_header(ws, 4, ["Показатель", "Ед."], annual=True)
        pl = self.pl
        cf: dict[str, int] = {}
        self.cf = cf
        funding = lambda r: ref(S_FUNDING, f"$B${r}")  # noqa: E731
        investor_month = self.idx(inv.investor_date)

        def at(month: int, formula: str) -> Callable[[int, str], object]:
            return lambda m, L: formula if m == month else 0

        def contributions(m: int, L: str) -> object:
            parts = [funding(r) for r, month in self.contribution_rows if month == m]
            return "=" + "+".join(parts) if parts else 0

        layout = [
            ("section", "Операционная деятельность"),
            ("op_receipts", "Поступления от покупателей и прочие доходы",
             lambda m, L: f"={ref(S_REVENUE, f'{L}{self.receipts_total}')}", False),
            ("op_cogs", "Оплата себестоимости", lambda m, L: f"={ref(S_PNL, f'{L}{pl['cogs']}')}", False),
            ("op_fot", "Выплата ФОТ и налогов работодателя",
             lambda m, L: f"={ref(S_PNL, f'{L}{pl['fot']}')}", False),
            ("op_adm", "Оплата административных и операционных расходов",
             lambda m, L: f"=-{ref(S_ADMIN, f'{L}{self.adm_paid}')}", False),
            ("op_launch", "Расходы на запуск", lambda m, L: f"={ref(S_PNL, f'{L}{pl['launch']}')}", False),
            ("op_tax", "Уплата налогов",
             lambda m, L: f"={ref(S_PNL, f'{L}{pl['tax_turnover']}')}+{ref(S_PNL, f'{L}{pl['tax_profit']}')}",
             False),
            ("op", "Чистый поток от операционной деятельности",
             lambda m, L: f"=SUM({L}{cf['op_receipts']}:{L}{cf['op_tax']})", True),
            ("section", "Инвестиционная деятельность"),
            ("inv_capex", "Приобретение основных средств",
             lambda m, L: f"=-{ref(S_DEPRECIATION, f'{L}{self.capex_total}')}", False),
            ("inv", "Чистый поток от инвестиционной деятельности",
             lambda m, L: f"={L}{cf['inv_capex']}", True),
            ("section", "Финансовая деятельность"),
            ("fin_equity", "Собственные средства", at(1, f"={funding(self.equity_row)}"), False),
            ("fin_investor", "Взносы инвестора", at(investor_month, f"={funding(self.investor_row)}"), False),
            ("fin_grant", "Гранты и субсидии", at(1, f"={funding(self.grant_row)}"), False),
            ("fin_contrib", "Дополнительные взносы", contributions, False),
            ("fin_loans", "Получение кредитов",
             lambda m, L: f"={ref(S_LOAN, f'{L}{self.loan_totals['received']}')}", False),
            ("fin_commission", "Комиссии по кредитам",
             lambda m, L: f"=-{ref(S_LOAN, f'{L}{self.loan_totals['commission']}')}", False),
            ("fin_principal", "Погашение основного долга",
             lambda m, L: f"=-{ref(S_LOAN, f'{L}{self.loan_totals['principal']}')}", False),
            ("fin_interest", "Выплата вознаграждения",
             lambda m, L: f"=-{ref(S_LOAN, f'{L}{self.loan_totals['interest_paid']}')}", False),
            ("fin", "Чистый поток от финансовой деятельности",
             lambda m, L: f"=SUM({L}{cf['fin_equity']}:{L}{cf['fin_interest']})", True),
            ("net", "Чистый денежный поток",
             lambda m, L: f"={L}{cf['op']}+{L}{cf['inv']}+{L}{cf['fin']}", True),
            ("open", "Остаток на начало месяца",
             lambda m, L: f"={self.p['opening_cash']}" if m == 1 else f"={self.mc(m - 1)}{cf['close']}",
             False),
            ("close", "Остаток на конец месяца", lambda m, L: f"={L}{cf['open']}+{L}{cf['net']}", True),
        ]
        row = 6
        for item in layout:
            cf[item[0] if item[0] != "section" else f"section:{item[1]}"] = row
            row += 1
        for item in layout:
            if item[0] == "section":
                self.section(ws, cf[f"section:{item[1]}"], item[1], annual=True)
                continue
            key, label, formula, bold = item
            mode = {"open": "first", "close": "last"}.get(key, "sum")
            self.grid_row(ws, cf[key], label, "₸", formula, total=mode, annual=mode, bold=bold)
        self.grid_widths(ws, 46, annual=True)
        ws.freeze_panes = f"{col(FIRST)}6"

    # 10. Анализ эффективности

    def sheet_analysis(self) -> None:
        ws = self.wb[S_ANALYSIS]
        title(ws, "10. Анализ эффективности проекта", self.subtitle)
        self.grid_header(ws, 4, ["Показатель", "Ед."])
        cf, rate = self.cf, self.p["discount"]
        an = {key: 6 + i for i, key in enumerate(["fcf", "cum", "factor", "dcf", "dcum", "pb", "dpb"])}

        def payback(flow: str, cum: str) -> Callable[[int, str], str]:
            def formula(m: int, L: str) -> str:
                if m == 1:
                    return f'=IF(AND({L}{an[flow]}>0,{L}{an[cum]}>=0),1,"")'
                prev = self.mc(m - 1)
                return (f'=IF(AND({prev}{an[cum]}<0,{L}{an[cum]}>=0),'
                        f'{m - 1}+(-{prev}{an[cum]})/{L}{an[flow]},"")')
            return formula

        dcf_cell = lambda key, L: ref(S_CASHFLOW, f"{L}{cf[key]}")  # noqa: E731
        self.grid_row(ws, an["fcf"], "Денежный поток проекта (FCF)", "₸",
                      lambda m, L: f"={dcf_cell('op', L)}+{dcf_cell('inv', L)}", bold=True)
        self.grid_row(ws, an["cum"], "Накопленный денежный поток", "₸",
                      lambda m, L: f"={L}{an['fcf']}" if m == 1
                      else f"={self.mc(m - 1)}{an['cum']}+{L}{an['fcf']}", total="last")
        self.grid_row(ws, an["factor"], "Коэффициент дисконтирования", "",
                      lambda m, L: f"=1/(1+{rate})^({m}/12)", "0.0000", total=None)
        self.grid_row(ws, an["dcf"], "Дисконтированный денежный поток", "₸",
                      lambda m, L: f"={L}{an['fcf']}*{L}{an['factor']}", bold=True)
        self.grid_row(ws, an["dcum"], "Накопленный дисконтированный поток", "₸",
                      lambda m, L: f"={L}{an['dcf']}" if m == 1
                      else f"={self.mc(m - 1)}{an['dcum']}+{L}{an['dcf']}", total="last")
        self.grid_row(ws, an["pb"], "Расчёт простой окупаемости, мес.", "", payback("fcf", "cum"),
                      "0.0", total=None)
        self.grid_row(ws, an["dpb"], "Расчёт дисконтированной окупаемости, мес.", "",
                      payback("dcf", "dcum"), "0.0", total=None)
        for key in ("pb", "dpb"):
            ws.cell(row=an[key], column=1).font = MUTED

        need = ref(S_FUNDING, f"$B${self.need_row}")
        row = an["dpb"] + 2
        header(ws, row, ["Показатели эффективности", "", "Значение"])
        ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=2)
        irr_row = row + 3
        metrics = [
            ("Активный сценарий", f"={self.p['scenario']}", "@"),
            ("Ставка дисконтирования", f"={rate}", PERCENT),
            ("Внутренняя норма доходности (IRR), годовых",
             f'=IFERROR((1+IRR({self.months_range(an["fcf"])}))^12-1,"—")', PERCENT),
            ("Чистая приведённая стоимость (NPV), ₸", f"=SUM({self.months_range(an['dcf'])})", MONEY),
            ("Индекс доходности (PI)", f'=IF({need}<=0,"—",1+C{irr_row + 1}/{need})', DECIMAL),
            ("Простой срок окупаемости, лет",
             f'=IF(COUNT({self.months_range(an["pb"])})=0,"—",MIN({self.months_range(an["pb"])})/12)',
             YEARS_FMT),
            ("Дисконтированный срок окупаемости, лет",
             f'=IF(COUNT({self.months_range(an["dpb"])})=0,"—",MIN({self.months_range(an["dpb"])})/12)',
             YEARS_FMT),
            ("Целевая доходность инвестора", f"={self.p['investor_target']}", PERCENT),
            ("IRR выше целевой доходности?",
             f'=IF(ISNUMBER(C{irr_row}),IF(C{irr_row}>={self.p["investor_target"]},"Да","Нет"),"—")', "@"),
        ]
        for offset, (label, formula, fmt) in enumerate(metrics, start=1):
            r = row + offset
            ws.cell(row=r, column=1, value=label).font = BOLD
            ws.merge_cells(start_row=r, start_column=1, end_row=r, end_column=2)
            cell = ws.cell(row=r, column=3, value=formula)
            cell.alignment = Alignment(horizontal="right")
            style_cells(ws, r, 1, 3)
            cell.number_format = fmt
        self.grid_widths(ws, 44)
        ws.column_dimensions[col(FIRST)].width = 16
        ws.freeze_panes = f"{col(FIRST)}6"


def build_fin_model_excel(user: User, data: FinModelData) -> bytes:
    return _ModelWriter(user, data).build()
