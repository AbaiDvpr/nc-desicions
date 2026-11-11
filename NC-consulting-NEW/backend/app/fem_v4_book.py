"""ФЭМ версии 4 — логика отчёта старого кабинета nc-dd-2023, но числа связаны формулами.

Жёлтые ячейки — анкета и коэффициенты старого отчёта (инфляция 8,7%, рост зарплаты 5%,
дисконт 14%, налоги и амортизация 1 / срок службы). Остальные числа ссылаются на лист,
откуда берутся: объём × цена, ОПиУ из доходов и расходов, ДДС из ОПиУ.
"""

from __future__ import annotations

from datetime import date
from io import BytesIO

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter
from openpyxl.worksheet.worksheet import Worksheet

from app.models import User

SRC = "Исходные"
INC = "Доходы"
COST = "Себестоимость"
PAY = "ФОТ"
EXP = "Расходы"
INV = "Инвестиции"
FIN = "Финансирование"
PNL = "ОПиУ"
CF = "ДДС"
BAL = "Баланс"
D_DATE = 400
D_YEAR = 401
D_INFL = 402
D_WAGE = 403
D_LOAD = 404
D_MON = 405
D_SALE = 406
D_DISC = 407
WRITEOFF_MONTHS = 390
TAX_BASE_ADJ = 391
EXIT_SHARE = 392
STOCK_WORDS = ("короб", "пакет", "плен", "плён", "этикет", "упаков", "сырь", "материал")

FIRST = 4
MONEY = '#,##0;(#,##0);"–"'
PCT = "0.00%"
DATE = "MMM YYYY"
THIN = Side(style="thin", color="D5DBE6")
BORDER = Border(left=THIN, right=THIN, top=THIN, bottom=THIN)
HEADER_FILL = PatternFill("solid", fgColor="1D3FB8")
HEADER_FONT = Font(bold=True, color="FFFFFF", name="Calibri", size=10)
BOLD = Font(bold=True, name="Calibri", size=10)
TEXT = Font(name="Calibri", size=10)
MUTED = Font(italic=True, color="5C677A", name="Calibri", size=10)
INPUT = PatternFill("solid", fgColor="FFF6D6")
SECTION = PatternFill("solid", fgColor="EAF0FF")
YEAR_FILL = PatternFill("solid", fgColor="F4F7FF")
TOTAL_FILL = PatternFill("solid", fgColor="E5F6EC")
WHITE = PatternFill("solid", fgColor="FFFFFF")

ROUND_UNITS = {1, 4, 10, 11}
TAX_RATES = {
    1: {1: 0.10, 2: 0.01, 3: 0.01, 4: 0.04},
    2: {1: 0.20, 2: 0.01, 3: 0.01, 4: 0.04},
}
FIN_ROWS = (
    ("smr", "СМР"),
    ("overhaul", "Капитальный ремонт"),
    ("ready_premises", "Помещения, здания, сооружения"),
    ("equipment", "Оборудование, техника, мебель"),
    ("raw", "Сырьё и товары"),
    ("advertising", "Реклама"),
    ("wages", "Заработная плата"),
    ("rental", "Аренда"),
    ("other", "Прочие расходы"),
)


def _num(value) -> float:
    try:
        return float(value or 0)
    except (TypeError, ValueError):
        return 0.0


def _flag(value) -> int:
    return 1 if value in (True, 1, "1", "true", "True") else 0


def _span(start: date, end: date) -> int:
    return (end.year - start.year) * 12 + (end.month - start.month)


def _depreciate(name: str) -> int:
    text = name.lower().replace("ё", "е")
    words = tuple(word.replace("ё", "е") for word in STOCK_WORDS)
    return 0 if any(word in text for word in words) else 1


def _month(value) -> date:
    text = str(value or "")[:7]
    year, month = text.split("-")
    return date(int(year), int(month), 1)


def _months(start: date, end: date) -> list[date]:
    cursor = date(start.year, start.month, 1)
    stop = date(end.year, end.month, 1)
    rows = []
    while cursor <= stop and len(rows) < 240:
        rows.append(cursor)
        year = cursor.year + (1 if cursor.month == 12 else 0)
        month = 1 if cursor.month == 12 else cursor.month + 1
        cursor = date(year, month, 1)
    return rows


def _slots(start: date, end: date) -> list[dict]:
    slots: list[dict] = []
    grouped: dict[int, list[int]] = {}
    months = _months(start, end)
    for index, current in enumerate(months):
        slots.append({"kind": "month", "date": current})
        grouped.setdefault(current.year, []).append(len(slots) - 1)
        if current.month == 12 or index == len(months) - 1:
            indexes = grouped[current.year]
            slots.append({"kind": "year", "year": current.year, "m0": indexes[0], "m1": indexes[-1]})
    return slots


def _col(index: int) -> str:
    return get_column_letter(FIRST + index)


def _ref(sheet: str, index: int, row: int) -> str:
    return f"'{sheet}'!{_col(index)}{row}"


def _cell(sheet: str, column: str, row: int) -> str:
    return f"'{sheet}'!${column}${row}"


class LegacyBook:
    def __init__(self, user: User, form: dict):
        self.user = user
        self.form = form
        self.start = _month(form.get("planning_from"))
        self.end = _month(form.get("planning_to"))
        self.slots = _slots(self.start, self.end)
        self.book = Workbook()
        self.book.calculation.calcMode = "auto"
        self.book.calculation.fullCalcOnLoad = True
        self.ws: dict[str, Worksheet] = {}
        self.product_rows: list[int] = []
        self.product_revenue: dict[str, int] = {}
        self.volume_row: dict[str, int] = {}
        self.revenue_rows: list[int] = []
        self.total_revenue = 0
        self.other_income_row = 0
        self.unit_cost_col = "R"
        self.cost_rows: list[int] = []
        self.cogs_total = 0
        self.staff_rows: list[int] = []
        self.fot_total = 0
        self.tax_total = 0
        self.expense_rows: list[int] = []
        self.expense_total = 0
        self.asset_rows: list[int] = []
        self.capex_total = 0
        self.dep_total = 0
        self.book_end = 0
        self.own_total = 0
        self.attracted_total = 0
        self.interest_row = 0
        self.principal_row = 0
        self.loan_close = 0
        self.raw_row = 0
        self.fa_cost_row = 0
        self.stock_cost_row = 0
        self.writeoff_row = 0
        self.goods_end = 0
        self.supplies_end = 0
        self.equity_row = 0
        self.pnl: dict[str, int] = {}

    def build(self) -> bytes:
        names = [SRC, INC, COST, PAY, EXP, INV, FIN, PNL, CF, BAL]
        self.ws[SRC] = self.book.active
        self.ws[SRC].title = SRC
        for name in names[1:]:
            self.ws[name] = self.book.create_sheet(name)
        self._sources()
        self._calendar()
        self._income()
        self._costs()
        self._payroll()
        self._expenses()
        self._investments()
        self._financing()
        self._loan()
        self._pnl()
        self._cash()
        self._balance()
        self._analysis()
        for sheet in self.ws.values():
            sheet.sheet_view.showGridLines = False
            sheet.page_setup.orientation = "landscape"
            sheet.page_setup.fitToPage = True
            sheet.page_setup.fitToWidth = 1
            sheet.page_setup.fitToHeight = 0
            sheet.sheet_properties.pageSetUpPr.fitToPage = True
            sheet.freeze_panes = "D3"
            sheet.column_dimensions["A"].width = 52
            sheet.column_dimensions["B"].width = 18
            sheet.column_dimensions["C"].width = 16
            for index in range(len(self.slots)):
                sheet.column_dimensions[_col(index)].width = 13
        self.ws[SRC].column_dimensions["A"].width = 62
        self.ws[SRC].column_dimensions["C"].width = 78
        buffer = BytesIO()
        self.book.save(buffer)
        return buffer.getvalue()

    def _input(self, sheet: str, row: int, column: int, value, fmt=None):
        cell = self.ws[sheet].cell(row, column, value)
        cell.fill = INPUT
        cell.font = TEXT
        cell.border = BORDER
        cell.alignment = Alignment(horizontal="left", vertical="center")
        if fmt:
            cell.number_format = fmt
        return cell

    def _label(self, sheet: str, row: int, text: str, bold=False, section=False):
        cell = self.ws[sheet].cell(row, 1, text)
        cell.font = BOLD if bold or section else TEXT
        if section:
            cell.fill = SECTION
        return cell

    def _paint(self, sheet: str, row: int, label: str, formula, year="sum", fmt=MONEY, bold=False, fill=None):
        self._label(sheet, row, label, bold=bold, section=fill is SECTION)
        ws = self.ws[sheet]
        for index, slot in enumerate(self.slots):
            cell = ws.cell(row, FIRST + index)
            if slot["kind"] == "year":
                if year == "sum":
                    cell.value = f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
                elif year == "last":
                    cell.value = f"={_col(slot['m1'])}{row}"
                elif year == "blank":
                    cell.value = None
                elif callable(year):
                    cell.value = year(index, slot)
                else:
                    cell.value = formula(index, slot)
            else:
                cell.value = formula(index, slot)
            cell.font = BOLD if bold else TEXT
            cell.border = BORDER
            cell.number_format = fmt
            cell.fill = fill or (YEAR_FILL if slot["kind"] == "year" else WHITE)
            if bold and fill is None:
                cell.fill = TOTAL_FILL if slot["kind"] == "month" else YEAR_FILL
        return row

    def _link(self, sheet: str, row: int, label: str, source: str, source_row: int, factor: str | None = None, bold=False):
        def formula(index, _slot):
            base = _ref(source, index, source_row)
            return f"={base}*({factor})" if factor else f"={base}"

        return self._paint(sheet, row, label, formula, year=formula, bold=bold)

    def _sum(self, sheet: str, row: int, label: str, rows: list[int], bold=True):
        def formula(index, _slot):
            if not rows:
                return "=0"
            return "=" + "+".join(f"{_col(index)}{item}" for item in rows)

        return self._paint(sheet, row, label, formula, year=formula, bold=bold)

    def _header(self, sheet: str, title: str, note: str):
        ws = self.ws[sheet]
        ws.cell(1, 1, title).font = Font(bold=True, name="Calibri", size=14, color="1D3FB8")
        ws.cell(2, 1, note).font = MUTED
        ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=3)
        ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=3)
        for index, slot in enumerate(self.slots):
            cell = ws.cell(1, FIRST + index, f"={_ref(SRC, index, D_DATE)}")
            cell.font = HEADER_FONT
            cell.fill = HEADER_FILL
            cell.alignment = Alignment(horizontal="center")
            cell.number_format = DATE if slot["kind"] == "month" else "@"

    def _sources(self):
        form = self.form
        legal = int(form.get("legal_form") or 1)
        regime = int(form.get("tax_regime") or 4)
        tax = TAX_RATES.get(legal, TAX_RATES[1]).get(regime, 0.03)
        ws = self.ws[SRC]
        self._header(SRC, "Исходные данные", "Жёлтые ячейки можно менять. МРП 3 063 и пороги налогов взяты из старого кабинета, это не ставки 2027 года. Строка 370 объясняет каждый блок.")
        rows = [
            (4, "Коэффициент инфляции (старый кабинет, 8,7%)", 0.087, PCT),
            (5, "Коэффициент роста заработной платы (старый кабинет, 5%)", 0.05, PCT),
            (6, "Ставка дисконтирования (старый кабинет, 14%)", 0.14, PCT),
            (7, "Ставка налога на прибыль (из анкеты)", tax, PCT),
            (8, "Ставка НДС (старый кабинет, 12%)", 0.12, PCT),
            (9, "Плательщик НДС (1 — да)", _flag(form.get("nds")), "0"),
            (10, "Амортизация оборудования, если срок не задан", 0.01, PCT),
            (11, "Амортизация недвижимости, если срок не задан", 0.002, PCT),
            (12, "Объём в месяц начала продаж, если год без загрузки", 0.5, PCT),
            (13, "МРП, ₸ (старый кабинет, ориентир 2023)", 3063, MONEY),
            (14, "МРП в вычете ИПН (старый кабинет, 14)", 14, "0"),
            (15, "Начало планирования", self.start, "DD.MM.YYYY"),
            (16, "Начало продаж", _month(form.get("sales_start")), "DD.MM.YYYY"),
            (17, "Выход на 100%", _month(form.get("planned_volume")), "DD.MM.YYYY"),
            (18, "Прочий доход в месяц", _num(form.get("other_income")), MONEY),
            (19, "Финансирование: 1 инвестор, 2 кредит, 3 грант", int(form.get("financing") or 1), "0"),
            (20, "Ставка кредита, % годовых", _num(form.get("loan_rate")), "0.00"),
            (21, "Срок кредита, мес.", _num(form.get("loan_term")), "0"),
            (22, "Льготный период, мес.", _num(form.get("loan_grace")), "0"),
            (23, "Аннуитет (1 — да)", _flag(form.get("loan_annuity")), "0"),
            (24, "Дивиденды инвестору, %", _num(form.get("investor_dividend")), "0.00"),
            (25, "Срок участия инвестора, лет (0 — весь горизонт)", _num(form.get("investor_term")), "0"),
        ]
        for row, label, value, fmt in rows:
            self._label(SRC, row, label)
            self._input(SRC, row, 2, value, fmt)
        self._label(SRC, 26, "Дивиденды начисляются до")
        cell = ws.cell(26, 2, f'=IF(B25<=0,DATE(9999,12,31),EDATE(B15,B25*12))')
        cell.font = TEXT
        cell.border = BORDER
        cell.number_format = "DD.MM.YYYY"
        too = legal == 2
        rates = [
            (28, "ОПВ", 0.1, PCT),
            (29, "ИПН", 0.1, PCT),
            (30, "ВОСМС", 0.02 if too else 0, PCT),
            (31, "Соц. отчисления до порогового года (старый кабинет)", 0.035, PCT),
            (32, "Соц. отчисления с порогового года (старый кабинет)", 0.05, PCT),
            (33, "Пороговый год соц. отчислений (старый кабинет)", 2025, "0"),
            (34, "ОСМС до порогового года (старый кабинет)", 0.02, PCT),
            (35, "ОСМС с порогового года (старый кабинет)", 0.03, PCT),
            (36, "Пороговый год ОСМС (старый кабинет)", 2023, "0"),
            (37, "Соц. налог для формулы ИП", 0.13 if not too else 0, PCT),
            (38, "Коэффициент пенсии в формуле ИП", 0.9 if not too else 0, "0.00"),
            (39, "Соц. налог до порогового года (старый кабинет)", 0.095 if too else 0, PCT),
            (40, "Соц. налог с порогового года (старый кабинет)", 0.11 if too else 0, PCT),
            (41, "Пороговый год соц. налога (старый кабинет)", 2025, "0"),
        ]
        self._label(SRC, 27, "Ставки налогов с фонда оплаты", bold=True, section=True)
        for row, label, value, fmt in rates:
            self._label(SRC, row, label)
            self._input(SRC, row, 2, value, fmt)
        self._label(SRC, 42, "Численность для вычета МРП")
        head = ws.cell(42, 2, "=0")
        head.font = TEXT
        head.border = BORDER
        head.number_format = "0"
        self.legal_form = legal
        ws.cell(4, 20, "Год").font = BOLD
        ws.cell(4, 21, "Загрузка года").font = BOLD
        capacity = form.get("planned_capacity") or {}
        years = []
        for key, value in capacity.items():
            try:
                year = int(str(key)[:4])
                percent = float(value or 0)
            except (TypeError, ValueError):
                continue
            years.append((year, percent / 100 if percent > 1 else percent))
        for offset, (year, percent) in enumerate(sorted(years)):
            self._input(SRC, 5 + offset, 20, year, "0")
            self._input(SRC, 5 + offset, 21, percent, PCT)

        self._label(SRC, 44, "Продукция", bold=True, section=True)
        for column, text in enumerate(["Название", "Ед.", "Количество", "Цена", "Округлять", *[str(month) for month in range(1, 13)], "Себестоимость ед."], start=1):
            cell = ws.cell(45, column, text)
            cell.font = BOLD
            cell.fill = SECTION
        for offset, product in enumerate(form.get("volume_sales") or []):
            row = 46 + offset
            self.product_rows.append(row)
            months = set(product.get("months") or [])
            seasonal = bool(product.get("seasonal")) and bool(months)
            self._input(SRC, row, 1, product.get("name") or "")
            self._input(SRC, row, 2, int(product.get("units") or 1), "0")
            self._input(SRC, row, 3, _num(product.get("count")), MONEY)
            self._input(SRC, row, 4, _num(product.get("price")), MONEY)
            self._input(SRC, row, 5, 1 if int(product.get("units") or 1) in ROUND_UNITS else 0, "0")
            for month in range(1, 13):
                self._input(SRC, row, 5 + month, 0 if seasonal and month not in months else 1, "0")
            cost = ws.cell(row, 18, f'=SUMIF($A$90:$A$130,A{row},$B$90:$B$130)')
            cost.font = TEXT
            cost.border = BORDER
            cost.number_format = MONEY
        self._label(SRC, 89, "Себестоимость на единицу", bold=True, section=True)
        ws.cell(89, 2, "Сумма").font = BOLD
        cost_at = 90
        if form.get("cost_mode") == "unit":
            for item in form.get("variable_costs") or []:
                if not str(item.get("product") or "").strip():
                    continue
                self._input(SRC, cost_at, 1, item.get("product"))
                self._input(SRC, cost_at, 2, _num(item.get("price")), MONEY)
                cost_at += 1
        self.cost_mode = form.get("cost_mode") or "unit"

        self._label(SRC, 132, "Сотрудники", bold=True, section=True)
        for column, text in enumerate(["Должность", "Количество", "Оклад", "% от дохода", "Производственный", "Только запуск", "Услуги", "Старт", "Месяцев"], start=1):
            cell = ws.cell(133, column, text)
            cell.font = BOLD
            cell.fill = SECTION
        sales = _month(form.get("sales_start"))
        for person in form.get("salary") or []:
            if not str(person.get("name") or "").strip():
                continue
            row = 134 + len(self.staff_rows)
            self.staff_rows.append(row)
            launch = _flag(person.get("invest")) == 1
            start = self.start if launch else sales
            months = max(1, _span(self.start, sales)) if launch else max(1, _span(sales, self.end) + 1)
            self._input(SRC, row, 1, person.get("name"))
            self._input(SRC, row, 2, _num(person.get("count")), "0.00")
            self._input(SRC, row, 3, _num(person.get("salary")), MONEY)
            self._input(SRC, row, 4, _num(person.get("percent")), "0.00")
            self._input(SRC, row, 5, _flag(person.get("industrial")), "0")
            self._input(SRC, row, 6, 1 if launch else 0, "0")
            services = [str(item) for item in (person.get("services") or []) if str(item).strip()]
            self._input(SRC, row, 7, ", ".join(services))
            self._input(SRC, row, 8, start, "DD.MM.YYYY")
            self._input(SRC, row, 9, months, "0")
            person["_services"] = services
        self.people = [item for item in (form.get("salary") or []) if str(item.get("name") or "").strip()]
        if self.staff_rows:
            head.value = f"=SUM(B{self.staff_rows[0]}:B{self.staff_rows[-1]})"

        self._label(SRC, 170, "Расходы", bold=True, section=True)
        for column, text in enumerate(["Название", "Сумма", "Период", "Только запуск", "Умножать на объём"], start=1):
            cell = ws.cell(171, column, text)
            cell.font = BOLD
            cell.fill = SECTION
        expense_at = 172
        groups = []
        if self.cost_mode != "unit":
            groups.append((form.get("variable_other") or [], 1))
        groups.append((form.get("admin_expenses") or [], 0))
        groups.append((form.get("marketing_expenses") or [], 0))
        for rows, scale in groups:
            for item in rows:
                if not str(item.get("name") or "").strip() or _num(item.get("value")) == 0:
                    continue
                self.expense_rows.append(expense_at)
                self._input(SRC, expense_at, 1, item.get("name"))
                self._input(SRC, expense_at, 2, _num(item.get("value")), MONEY)
                self._input(SRC, expense_at, 3, int(item.get("period") or 0), "0")
                self._input(SRC, expense_at, 4, _flag(item.get("invest")), "0")
                self._input(SRC, expense_at, 5, scale, "0")
                expense_at += 1
        self._label(SRC, WRITEOFF_MONTHS, "Списание запасов материалов, месяцев")
        self._input(SRC, WRITEOFF_MONTHS, 2, 12, "0")
        self._label(SRC, TAX_BASE_ADJ, "Корректировка налоговой базы, доля от прибыли")
        self._input(SRC, TAX_BASE_ADJ, 2, 0, PCT)
        self._label(SRC, EXIT_SHARE, "Доля инвестора при выходе")
        self._input(SRC, EXIT_SHARE, 2, 0, PCT)
        legend = [
            (370, "Откуда взяты жёлтые ставки"),
            (371, "МРП 3 063 ₸ и 14 МРП в вычете ИПН — константы старого кабинета, файл config/projects.php, ориентир 2023 года. Для 2027 года их нужно заменить на действующий МРП."),
            (372, "НДС 12% — та же константа старого кабинета. В модели она применяется, только если в анкете проект отмечен плательщиком НДС."),
            (373, "Ставка налога на прибыль берётся из анкеты: форма и режим. Для ИП на упрощёнке это 3%. Налоговая база в модели — бухгалтерская прибыль до налога, умноженная на (1 + корректировка в строке 391). Ноль в строке 391 оставляет базу равной бухгалтерской прибыли."),
            (374, "Пороги соцотчислений, ОСМС и соцналога скопированы из того же config/projects.php: соцотчисления 3,5% до 2025 и 5% с 2025, ОСМС 2% до 2023 и 3% с 2023, соцналог ТОО 9,5% до 2025 и 11% с 2025. Формула смотрит на год месяца и берёт ставку после порога."),
            (375, "Инфляция 8,7%, рост зарплаты 5% и ставка дисконтирования 14% — тоже предпосылки старого кабинета, не прогноз на 2027 год."),
            (376, "Загрузка по годам стоит в колонках T и U. Если год заполнен, объём продаж этого года берёт эту долю. Пустая таблица включает линейный выход на объём между датами в B16 и B17."),
            (377, "Сотрудник с признаком «Только запуск» получает оклад со старта проекта и только указанное число месяцев. Для запуска подставлен период до начала продаж. Даты в колонках H и I можно поменять."),
            (378, "Упаковка (коробки, пакеты, плёнка, этикетки) ставится в запасы и списывается равными частями за число месяцев из строки 390. На амортизацию она не попадает."),
            (379, "Доля при выходе в строке 392 по умолчанию 0: IRR инвестора считает его взнос и дивиденды. Если указать долю, в месяц выхода к потоку добавится эта доля от капитала."),
        ]
        for row, text in legend:
            cell = ws.cell(row, 1, text)
            cell.font = BOLD if row == 370 else MUTED
            cell.alignment = Alignment(wrap_text=True, vertical="center")
            ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=3)
            ws.row_dimensions[row].height = 32

    def _calendar(self):
        ws = self.ws[SRC]
        labels = [
            (D_DATE, "Дата / итог года"),
            (D_YEAR, "Индекс года"),
            (D_INFL, "Инфляция"),
            (D_WAGE, "Рост заработной платы"),
            (D_LOAD, "Загрузка"),
            (D_MON, "Номер месяца"),
            (D_SALE, "Номер месяца продаж"),
            (D_DISC, "Дисконт месяца: 1/(1+ставка)^((месяц+1)/12)"),
        ]
        for row, text in labels:
            self._label(SRC, row, text, bold=row == D_DATE)
        previous_month = None
        previous_sales = None
        ramp = (
            f'IF({{col}}{D_DATE}<{_cell(SRC, "B", 16)},1,'
            f'IF({{col}}{D_DATE}>={_cell(SRC, "B", 17)},1,'
            f'MIN(1,{_cell(SRC, "B", 12)}+DATEDIF({_cell(SRC, "B", 16)},{{col}}{D_DATE},"M")*'
            f'IF(DATEDIF({_cell(SRC, "B", 16)},{_cell(SRC, "B", 17)},"M")=0,0,'
            f'(1-{_cell(SRC, "B", 12)})/DATEDIF({_cell(SRC, "B", 16)},{_cell(SRC, "B", 17)},"M")))))'
        )
        for index, slot in enumerate(self.slots):
            column = FIRST + index
            letter = _col(index)
            if slot["kind"] == "year":
                ws.cell(D_DATE, column, f"Итог {slot['year']}").font = BOLD
                ws.cell(D_DATE, column).fill = YEAR_FILL
                for row in (D_YEAR, D_INFL, D_WAGE, D_LOAD, D_MON, D_SALE, D_DISC):
                    cell = ws.cell(row, column, None)
                    cell.fill = YEAR_FILL
                    cell.border = BORDER
            else:
                cell = self._input(SRC, D_DATE, column, slot["date"], DATE)
                cell.alignment = Alignment(horizontal="center")
                ws.cell(D_YEAR, column, f"=YEAR({letter}{D_DATE})-YEAR({_cell(SRC, 'B', 15)})")
                ws.cell(D_INFL, column, f"=(1+{_cell(SRC, 'B', 4)})^{letter}{D_YEAR}")
                ws.cell(D_WAGE, column, f"=(1+{_cell(SRC, 'B', 5)})^{letter}{D_YEAR}")
                fallback = ramp.format(col=letter)
                ws.cell(D_LOAD, column, f'=IFERROR(VLOOKUP(YEAR({letter}{D_DATE}),$T$5:$U$16,2,FALSE),{fallback})')
                if previous_month is None:
                    ws.cell(D_MON, column, "=0")
                else:
                    ws.cell(D_MON, column, f"={previous_month}{D_MON}+1")
                previous_month = letter
                if previous_sales is None:
                    ws.cell(D_SALE, column, f'=IF({letter}{D_DATE}<{_cell(SRC, "B", 16)},"",0)')
                else:
                    ws.cell(D_SALE, column, f'=IF({letter}{D_DATE}<{_cell(SRC, "B", 16)},"",{previous_sales}{D_SALE}+1)')
                previous_sales = letter
                ws.cell(D_DISC, column, f"=1/(1+{_cell(SRC, 'B', 6)})^(({letter}{D_MON}+1)/12)")
                for row in (D_YEAR, D_INFL, D_WAGE, D_LOAD, D_MON, D_SALE, D_DISC):
                    ws.cell(row, column).font = TEXT
                    ws.cell(row, column).border = BORDER
                    if row in (D_INFL, D_WAGE, D_LOAD, D_DISC):
                        ws.cell(row, column).number_format = "0.0000"

    def _income(self):
        self._header(INC, "Доходы", "Объём = количество × загрузка года из колонок T:U. Если год не задан, берётся выход на объём. Выручка = объём × цена × инфляция.")
        row = 4
        for product_row in self.product_rows:
            name = self.ws[SRC].cell(product_row, 1).value
            self.volume_row[name] = row

            def volume(index, slot, product_row=product_row, row=row):
                if slot["kind"] == "year":
                    return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
                season = f"INDEX('{SRC}'!F{product_row}:Q{product_row},MONTH({_ref(SRC, index, D_DATE)}))"
                count = f"'{SRC}'!C{product_row}"
                capacity = _ref(SRC, index, D_LOAD)
                started = f"{_ref(SRC, index, D_DATE)}>='{SRC}'!$B$16"
                return (
                    f'=IF(NOT({started}),0,IF({season}=0,0,'
                    f'IF(\'{SRC}\'!E{product_row}=1,ROUNDUP({count}*{capacity},0),{count}*{capacity})))'
                )

            self._paint(INC, row, f"Объём: {name}", volume, year=volume, fmt="#,##0.00")
            price_row = row + 1

            def price(index, slot, product_row=product_row, price_row=price_row):
                if slot["kind"] == "year":
                    return f"={_col(slot['m1'])}{price_row}"
                return f'=IF({_ref(SRC, index, D_DATE)}<\'{SRC}\'!$B$16,0,\'{SRC}\'!D{product_row}*{_ref(SRC, index, D_INFL)})'

            self._paint(INC, price_row, f"Цена: {name}", price, year=price)
            revenue_row = row + 2

            def revenue(index, _slot, row=row, price_row=price_row):
                return f"={_col(index)}{row}*{_col(index)}{price_row}"

            self._paint(INC, revenue_row, f"Выручка: {name}", revenue, year="sum", bold=True)
            self.product_revenue[name] = revenue_row
            self.revenue_rows.append(revenue_row)
            row += 4

        def other(index, slot):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
            return f'=IF({_ref(SRC, index, D_DATE)}<\'{SRC}\'!$B$16,0,\'{SRC}\'!$B$18*{_ref(SRC, index, D_INFL)})'

        self.other_income_row = row
        self._paint(INC, row, "Прочий доход", other, year=other)
        self.revenue_rows.append(row)
        self.total_revenue = row + 1
        self._sum(INC, self.total_revenue, "Итого выручка", self.revenue_rows)

    def _costs(self):
        self._header(COST, "Себестоимость", "На единицу: объём с листа «Доходы» × себестоимость × инфляция. Помесячная сумма считается в расходах с коэффициентом объёма.")
        row = 4
        if self.cost_mode == "unit":
            for product_row in self.product_rows:
                name = self.ws[SRC].cell(product_row, 1).value
                volume = self.volume_row[name]

                def cogs(index, slot, product_row=product_row, volume=volume, row=row):
                    if slot["kind"] == "year":
                        return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
                    return f"={_ref(INC, index, volume)}*'{SRC}'!R{product_row}*{_ref(SRC, index, D_INFL)}"

                self._paint(COST, row, f"Себестоимость: {name}", cogs, year=cogs)
                self.cost_rows.append(row)
                row += 1
        self.cogs_total = row
        self._sum(COST, row, "Итого себестоимость", self.cost_rows)

    def _payroll(self):
        self._header(PAY, "Фонд оплаты труда", "Оклад начисляется только между датой старта и концом срока в исходных. Производственная численность умножается на загрузку года.")
        row = 4
        pay_rows = []
        for person, source in zip(self.people, self.staff_rows):
            services = person.get("_services") or []

            def salary(index, slot, source=source, services=services, row=row):
                if slot["kind"] == "year":
                    return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
                persons = f'IF(\'{SRC}\'!E{source}=1,ROUNDUP(\'{SRC}\'!B{source}*{_ref(SRC, index, D_LOAD)},0),\'{SRC}\'!B{source})'
                bonus_base = self._bonus_base(index, services)
                bonus = f"'{SRC}'!D{source}/100*({bonus_base})"
                pay = f"'{SRC}'!C{source}*{_ref(SRC, index, D_WAGE)}*({persons})+{bonus}"
                started = f'{_ref(SRC, index, D_DATE)}>=\'{SRC}\'!H{source}'
                active = f'{_ref(SRC, index, D_DATE)}<EDATE(\'{SRC}\'!H{source},\'{SRC}\'!I{source})'
                return f'=IF(AND({started},{active}),{pay},0)'

            self._paint(PAY, row, self.ws[SRC].cell(source, 1).value or "Сотрудник", salary, year=salary)
            pay_rows.append(row)
            row += 1
        self.fot_total = row
        self._sum(PAY, row, "Итого фонд оплаты", pay_rows)
        row += 2
        self._label(PAY, row, "Налоги с фонда оплаты", bold=True, section=True)
        tax_rows = []
        row += 1
        fot = self.fot_total
        if self.legal_form == 2:
            specs = [
                ("ОПВ", lambda index, prior: f"{_cell(SRC, 'B', 28)}*{_col(index)}{fot}"),
                ("ВОСМС", lambda index, prior: f"{_cell(SRC, 'B', 30)}*{_col(index)}{fot}"),
                ("ИПН", lambda index, prior: f"MAX(0,{_col(index)}{fot}-{_col(index)}{prior[0]}-{_col(index)}{prior[1]}-{_cell(SRC, 'B', 13)}*{_cell(SRC, 'B', 14)}*{_cell(SRC, 'B', 42)})*{_cell(SRC, 'B', 29)}"),
                ("Соц. отчисления", lambda index, prior: f"({_col(index)}{fot}-{_col(index)}{prior[0]})*IF(YEAR({_ref(SRC, index, D_DATE)})>={_cell(SRC, 'B', 33)},{_cell(SRC, 'B', 32)},{_cell(SRC, 'B', 31)})"),
                ("Соц. налог", lambda index, prior: f"({_col(index)}{fot}-{_col(index)}{prior[0]}-{_col(index)}{prior[1]})*IF(YEAR({_ref(SRC, index, D_DATE)})>={_cell(SRC, 'B', 41)},{_cell(SRC, 'B', 40)},{_cell(SRC, 'B', 39)})-{_col(index)}{prior[3]}"),
                ("ОСМС", lambda index, prior: f"{_col(index)}{fot}*IF(YEAR({_ref(SRC, index, D_DATE)})>={_cell(SRC, 'B', 36)},{_cell(SRC, 'B', 35)},{_cell(SRC, 'B', 34)})"),
            ]
        else:
            specs = [
                ("ОПВ", lambda index, prior: f"{_cell(SRC, 'B', 28)}*{_col(index)}{fot}"),
                ("ИПН", lambda index, prior: f"({_col(index)}{fot}-{_col(index)}{prior[0]})*{_cell(SRC, 'B', 29)}"),
                ("Соц. отчисления", lambda index, prior: f"{_cell(SRC, 'B', 37)}*{_cell(SRC, 'B', 38)}*{_col(index)}{fot}-IF(YEAR({_ref(SRC, index, D_DATE)})>={_cell(SRC, 'B', 33)},{_cell(SRC, 'B', 32)},{_cell(SRC, 'B', 31)})*{_cell(SRC, 'B', 38)}*{_col(index)}{fot}"),
                ("ОСМС", lambda index, prior: f"{_col(index)}{fot}*IF(YEAR({_ref(SRC, index, D_DATE)})>={_cell(SRC, 'B', 36)},{_cell(SRC, 'B', 35)},{_cell(SRC, 'B', 34)})"),
            ]
        for title, body in specs:
            current = row

            def tax(index, slot, body=body, tax_rows=list(tax_rows), current=current):
                if slot["kind"] == "year":
                    return f"=SUM({_col(slot['m0'])}{current}:{_col(slot['m1'])}{current})"
                return f'=IF({_col(index)}{fot}<=0,0,{body(index, tax_rows)})'

            self._paint(PAY, row, title, tax, year=tax)
            tax_rows.append(row)
            row += 1
        self.tax_total = row
        self._sum(PAY, row, "Итого налоги с фонда оплаты", tax_rows)

    def _bonus_base(self, index: int, services: list[str]) -> str:
        rows = [self.product_revenue[name] for name in services if name in self.product_revenue]
        if not rows:
            rows = list(self.product_revenue.values()) or [self.total_revenue]
        return "+".join(_ref(INC, index, item) for item in rows)

    def _expenses(self):
        self._header(EXP, "Расходы", "Сумма из исходных × инфляция. Период 0 — один раз. Расход запуска действует только до начала продаж, остальные — с начала продаж.")
        row = 4
        painted = []
        for source in self.expense_rows:
            def amount(index, slot, source=source, row=row):
                if slot["kind"] == "year":
                    return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
                ordinal = f'IF(\'{SRC}\'!D{source}=1,{_ref(SRC, index, D_MON)},{_ref(SRC, index, D_SALE)})'
                scale = f'IF(\'{SRC}\'!E{source}=1,IF({_ref(SRC, index, D_LOAD)}=0,1,{_ref(SRC, index, D_LOAD)}),1)'
                base = f"'{SRC}'!B{source}*{_ref(SRC, index, D_INFL)}*{scale}"
                period = f"'{SRC}'!C{source}"
                charge = f'IF({period}=0,IF({ordinal}=0,{base},0),IF(MOD({ordinal},{period})=0,{base},0))'
                before = f'{_ref(SRC, index, D_DATE)}<\'{SRC}\'!$B$16'
                return f'=IF(\'{SRC}\'!D{source}=1,IF({before},{charge},0),IF({before},0,{charge}))'

            self._paint(EXP, row, self.ws[SRC].cell(source, 1).value or "Расход", amount, year=amount)
            painted.append(row)
            row += 1
        self.expense_total = row
        self._sum(EXP, row, "Итого расходы", painted)

    def _investments(self):
        self._header(INV, "Инвестиции и амортизация", "Стоимость = количество × цена. Упаковка и материалы стоят в запасах и не амортизируются. Амортизация останавливается, когда срок в годах заканчивается.")
        ws = self.ws[INV]
        for column, text in enumerate(["Наименование", "Количество", "Цена", "Срок, лет", "Стоимость", "Ставка в месяц", "Амортизировать"], start=1):
            cell = ws.cell(4, column, text)
            cell.font = BOLD
            cell.fill = SECTION
        groups = (
            ("equipment", self.form.get("equipment") or [], 10),
            ("premises", self.form.get("premises") or [], 11),
            ("smr", self.form.get("smr") or [], 11),
            ("overhaul", self.form.get("overhaul") or [], 11),
        )
        row = 5
        for _key, assets, rate_row in groups:
            for asset in assets:
                name = str(asset.get("name") or "").strip()
                if not name:
                    continue
                self.asset_rows.append(row)
                self._input(INV, row, 1, name)
                self._input(INV, row, 2, _num(asset.get("count")) or 1, "0.00")
                price_one = _num(asset.get("price_one"))
                price = _num(asset.get("price"))
                self._input(INV, row, 3, price_one or price, MONEY)
                self._input(INV, row, 4, _num(asset.get("life_years")), "0")
                total = ws.cell(row, 5, f"=IF(C{row}=0,0,B{row}*C{row})")
                total.font = TEXT
                total.border = BORDER
                total.number_format = MONEY
                rate = ws.cell(row, 6, f"=IF(D{row}>0,1/D{row}/12,{_cell(SRC, 'B', rate_row)})")
                rate.font = TEXT
                rate.border = BORDER
                rate.number_format = PCT
                self._input(INV, row, 7, _depreciate(name) if _key == "overhaul" else 1, "0")
                row += 1
        self.capex_total = row
        self._label(INV, row, "Итого инвестиции", bold=True)
        total = ws.cell(row, 5, f"=SUM(E5:E{row - 1})" if self.asset_rows else "=0")
        total.font = BOLD
        total.fill = TOTAL_FILL
        total.border = BORDER
        total.number_format = MONEY
        self.fa_cost_row = row + 1
        self.stock_cost_row = row + 2
        self._label(INV, self.fa_cost_row, "Амортизируемые активы")
        fa = ws.cell(self.fa_cost_row, 5, f'=SUMIF(G5:G{row - 1},1,E5:E{row - 1})' if self.asset_rows else "=0")
        fa.font = TEXT
        fa.border = BORDER
        fa.number_format = MONEY
        self._label(INV, self.stock_cost_row, "Запасы материалов, без амортизации")
        stock = ws.cell(self.stock_cost_row, 5, f'=SUMIF(G5:G{row - 1},0,E5:E{row - 1})' if self.asset_rows else "=0")
        stock.font = TEXT
        stock.border = BORDER
        stock.number_format = MONEY
        dep_row = row + 4

        def depreciation(index, slot, dep_row=dep_row):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{dep_row}:{_col(slot['m1'])}{dep_row})"
            if not self.asset_rows:
                return "=0"
            month_no = _ref(SRC, index, D_MON)
            parts = [
                f'IF(OR($G{item}=0,{month_no}>=IF($D{item}>0,$D{item}*12,9999)),0,$E{item}*$F{item})'
                for item in self.asset_rows
            ]
            return "=" + "+".join(parts)

        self.dep_total = dep_row
        self._paint(INV, dep_row, "Амортизация за месяц", depreciation, year=depreciation, bold=True)
        acc_row = dep_row + 1
        previous = None

        def accumulated(index, slot, acc_row=acc_row, dep_row=dep_row):
            nonlocal previous
            if slot["kind"] == "year":
                return f"={_col(slot['m1'])}{acc_row}"
            if previous is None:
                previous = _col(index)
                return f"={_col(index)}{dep_row}"
            formula = f"={previous}{acc_row}+{_col(index)}{dep_row}"
            previous = _col(index)
            return formula

        self._paint(INV, acc_row, "Накопленная амортизация", accumulated, year=accumulated)
        book_row = dep_row + 2

        def book(index, slot, book_row=book_row, acc_row=acc_row):
            if slot["kind"] == "year":
                return f"={_col(slot['m1'])}{book_row}"
            return f"=$E${self.fa_cost_row}-{_col(index)}{acc_row}"

        self.book_end = book_row
        self._paint(INV, book_row, "Балансовая стоимость на конец", book, year=book, bold=True)
        write_row = book_row + 2

        def writeoff(index, slot, write_row=write_row):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{write_row}:{_col(slot['m1'])}{write_row})"
            months = _cell(SRC, "B", WRITEOFF_MONTHS)
            stock_cell = f"$E${self.stock_cost_row}"
            month_no = _ref(SRC, index, D_MON)
            return f'=IF(OR({months}<=0,{month_no}>={months}),0,{stock_cell}/{months})'

        self.writeoff_row = write_row
        self._paint(INV, write_row, "Списание запасов материалов", writeoff, year=writeoff)

    def _financing(self):
        self._header(FIN, "Структура финансирования", "Свои и привлекаемые — из шага 4. Итог и доля считаются формулой.")
        ws = self.ws[FIN]
        for column, text in enumerate(["Статья", "Свои", "Привлекаемые", "Итого", "Доля"], start=1):
            cell = ws.cell(4, column, text)
            cell.font = BOLD
            cell.fill = SECTION
        own = self.form.get("own") or {}
        attracted = self.form.get("attracted") or {}
        use_own = _flag(self.form.get("own_invest")) == 1
        for offset, (key, label) in enumerate(FIN_ROWS):
            row = 5 + offset
            if key == "raw":
                self.raw_row = row
            self._label(FIN, row, label)
            self._input(FIN, row, 2, _num(own.get(key)) if use_own else 0, MONEY)
            self._input(FIN, row, 3, _num(attracted.get(key)), MONEY)
            total = ws.cell(row, 4, f"=B{row}+C{row}")
            total.font = TEXT
            total.border = BORDER
            total.number_format = MONEY
        last = 4 + len(FIN_ROWS)
        self._label(FIN, last + 1, "Итого", bold=True)
        for column, letter in ((2, "B"), (3, "C"), (4, "D")):
            cell = ws.cell(last + 1, column, f"=SUM({letter}5:{letter}{last})")
            cell.font = BOLD
            cell.fill = TOTAL_FILL
            cell.border = BORDER
            cell.number_format = MONEY
        self.own_total = last + 1
        self.attracted_total = last + 1
        for offset in range(len(FIN_ROWS)):
            row = 5 + offset
            share = ws.cell(row, 5, f"=IF($D${last + 1}=0,0,D{row}/$D${last + 1})")
            share.font = TEXT
            share.border = BORDER
            share.number_format = PCT

    def _loan(self):
        principal = f"IF('{SRC}'!$B$19=2,'{FIN}'!C{self.attracted_total},0)"
        rate = f"'{SRC}'!$B$20/100/12"
        term = f"'{SRC}'!$B$21"
        grace = f"'{SRC}'!$B$22"
        annuity = f"'{SRC}'!$B$23"
        row_open, row_interest, row_principal, row_close = 18, 19, 20, 21
        self.interest_row = row_interest
        self.principal_row = row_principal
        self.loan_close = row_close
        self._label(FIN, 17, "График кредита", bold=True, section=True)
        previous = None
        for index, slot in enumerate(self.slots):
            column = _col(index)
            if slot["kind"] == "year":
                self.ws[FIN].cell(row_open, FIRST + index, f"={_col(slot['m0'])}{row_open}")
                self.ws[FIN].cell(row_interest, FIRST + index, f"=SUM({_col(slot['m0'])}{row_interest}:{_col(slot['m1'])}{row_interest})")
                self.ws[FIN].cell(row_principal, FIRST + index, f"=SUM({_col(slot['m0'])}{row_principal}:{_col(slot['m1'])}{row_principal})")
                self.ws[FIN].cell(row_close, FIRST + index, f"={_col(slot['m1'])}{row_close}")
            else:
                opened = f"{principal}" if previous is None else f"{previous}{row_close}"
                self.ws[FIN].cell(row_open, FIRST + index, f"={opened}")
                self.ws[FIN].cell(row_interest, FIRST + index, f"={column}{row_open}*({rate})")
                payment = f"IF({term}=0,0,PMT({rate},{term},-{principal}))"
                annuity_body = f"MIN({column}{row_open},MAX(0,{payment}-{column}{row_interest}))"
                equal_body = f"IF({term}=0,0,MIN({column}{row_open},{principal}/{term}))"
                body = f'IF({_ref(SRC, index, D_MON)}<{grace},0,IF({_ref(SRC, index, D_MON)}>={grace}+{term},0,IF({annuity}=1,{annuity_body},{equal_body})))'
                self.ws[FIN].cell(row_principal, FIRST + index, f"=IF({principal}=0,0,{body})")
                self.ws[FIN].cell(row_close, FIRST + index, f"=MAX(0,{column}{row_open}-{column}{row_principal})")
                previous = column
            for line in (row_open, row_interest, row_principal, row_close):
                cell = self.ws[FIN].cell(line, FIRST + index)
                cell.font = TEXT
                cell.border = BORDER
                cell.number_format = MONEY
                cell.fill = YEAR_FILL if slot["kind"] == "year" else WHITE
        self._label(FIN, row_open, "Долг на начало")
        self._label(FIN, row_interest, "Проценты")
        self._label(FIN, row_principal, "Погашение тела")
        self._label(FIN, row_close, "Долг на конец", bold=True)

    def _pnl(self):
        self._header(PNL, "ОПиУ", "Строки ссылаются на листы-источники. Налог = ставка × бухгалтерская прибыль × (1 + корректировка из исходных).")
        nds = f"IF('{SRC}'!$B$9=1,1-'{SRC}'!$B$8,1)"
        row = 4
        self.pnl["revenue"] = self._link(PNL, row, "Выручка", INC, self.total_revenue, bold=True)
        row += 1
        self.pnl["cogs"] = self._link(PNL, row, "Себестоимость", COST, self.cogs_total, nds)
        row += 1
        self.pnl["margin"] = self._binary(PNL, row, "Маржинальный доход", self.pnl["revenue"], self.pnl["cogs"], "-", bold=True)
        row += 1
        self.pnl["margin_pct"] = self._ratio(PNL, row, "Маржа", self.pnl["margin"], self.pnl["revenue"])
        row += 2
        self.pnl["fot"] = self._link(PNL, row, "Фонд оплаты труда", PAY, self.fot_total)
        row += 1
        self.pnl["fot_tax"] = self._link(PNL, row, "Налоги с фонда оплаты", PAY, self.tax_total)
        row += 1
        self.pnl["opex"] = self._link(PNL, row, "Расходы", EXP, self.expense_total, nds)
        row += 1
        self.pnl["writeoff"] = self._link(PNL, row, "Списание запасов материалов", INV, self.writeoff_row)
        row += 1
        self.pnl["ebitda"] = row
        parts = [self.pnl["margin"], self.pnl["fot"], self.pnl["fot_tax"], self.pnl["opex"], self.pnl["writeoff"]]

        def ebitda(index, _slot, parts=parts):
            column = _col(index)
            return f"={column}{parts[0]}-{column}{parts[1]}-{column}{parts[2]}-{column}{parts[3]}-{column}{parts[4]}"

        self._paint(PNL, row, "EBITDA", ebitda, year=ebitda, bold=True)
        row += 2
        self.pnl["dep"] = self._link(PNL, row, "Амортизация", INV, self.dep_total)
        row += 1
        self.pnl["ebit"] = self._binary(PNL, row, "Прибыль до процентов", self.pnl["ebitda"], self.pnl["dep"], "-", bold=True)
        row += 1
        self.pnl["interest"] = self._link(PNL, row, "Проценты по кредиту", FIN, self.interest_row)
        row += 1
        self.pnl["ebt"] = self._binary(PNL, row, "Прибыль до налога", self.pnl["ebit"], self.pnl["interest"], "-", bold=True)
        row += 1

        def tax(index, _slot):
            return f"=MAX(0,{_col(index)}{self.pnl['ebt']}*(1+'{SRC}'!$B${TAX_BASE_ADJ}))*'{SRC}'!$B$7"

        self.pnl["tax"] = row
        self._paint(PNL, row, "Налог на прибыль", tax, year=tax)
        row += 1
        self.pnl["profit"] = self._binary(PNL, row, "Чистая прибыль", self.pnl["ebt"], self.pnl["tax"], "-", bold=True)
        row += 2

        def dividend(index, slot):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
            return (
                f'=IF(OR(\'{SRC}\'!$B$19<>1,{_ref(SRC, index, D_DATE)}>=\'{SRC}\'!$B$26),0,'
                f'MAX(0,{_col(index)}{self.pnl["profit"]})*\'{SRC}\'!$B$24/100)'
            )

        self.pnl["dividend"] = row
        self._paint(PNL, row, "Дивиденды инвестору", dividend, year=dividend)

    def _binary(self, sheet: str, row: int, label: str, left: int, right: int, sign: str, bold=False):
        def formula(index, _slot):
            return f"={_col(index)}{left}{sign}{_col(index)}{right}"

        self._paint(sheet, row, label, formula, year=formula, bold=bold)
        return row

    def _ratio(self, sheet: str, row: int, label: str, left: int, right: int):
        def formula(index, _slot):
            return f"=IF({_col(index)}{right}=0,0,{_col(index)}{left}/{_col(index)}{right})"

        self._paint(sheet, row, label, formula, year=formula, fmt=PCT)
        return row

    def _cash(self):
        self._header(CF, "ДДС", "Закупка товаров равна себестоимости и пополняет склад. Первоначальные запасы и амортизируемые активы оплачиваются в первом месяце. Списание упаковки в деньги не входит: она уже куплена.")
        row = 4
        self._link(CF, row, "Поступления от продаж", PNL, self.pnl["revenue"])
        inflow = row
        row += 1
        self._link(CF, row, "Закупка товаров на пополнение", COST, self.cogs_total)
        replenish = row
        row += 1
        self._link(CF, row, "Фонд оплаты труда", PNL, self.pnl["fot"])
        row += 1
        self._link(CF, row, "Налоги с фонда оплаты", PNL, self.pnl["fot_tax"])
        row += 1
        self._link(CF, row, "Расходы", PNL, self.pnl["opex"])
        row += 1
        self._link(CF, row, "Налог на прибыль", PNL, self.pnl["tax"])
        row += 1
        self._link(CF, row, "Проценты по кредиту", PNL, self.pnl["interest"])
        cash_cost_rows = list(range(replenish, row + 1))
        row += 1

        def outflow(index, _slot, cash_cost_rows=cash_cost_rows):
            return "=" + "+".join(f"{_col(index)}{item}" for item in cash_cost_rows)

        self._paint(CF, row, "Операционные выплаты", outflow, year=outflow)
        operating_out = row
        row += 1
        self._binary(CF, row, "Операционный поток", inflow, operating_out, "-", bold=True)
        operating = row
        row += 2

        def once(index, slot, formula):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
            if index == 0:
                return formula
            return "=0"

        fa_row = row
        self._paint(CF, row, "Покупка амортизируемых активов", lambda index, slot, row=row: once(index, slot, f"='{INV}'!E{self.fa_cost_row}"), year=lambda index, slot, row=row: once(index, slot, f"='{INV}'!E{self.fa_cost_row}"))
        row += 1
        goods_buy = row
        self._paint(CF, row, "Первоначальный запас товаров", lambda index, slot, row=row: once(index, slot, f"='{FIN}'!D{self.raw_row}"), year=lambda index, slot, row=row: once(index, slot, f"='{FIN}'!D{self.raw_row}"))
        row += 1
        supplies_buy = row
        self._paint(CF, row, "Первоначальный запас материалов", lambda index, slot, row=row: once(index, slot, f"='{INV}'!E{self.stock_cost_row}"), year=lambda index, slot, row=row: once(index, slot, f"='{INV}'!E{self.stock_cost_row}"))
        row += 1

        def investing_sum(index, _slot, rows=(fa_row, goods_buy, supplies_buy)):
            return "=" + "+".join(f"{_col(index)}{item}" for item in rows)

        self._paint(CF, row, "Инвестиционный поток", investing_sum, year=investing_sum, bold=True)
        investing = row
        row += 2

        def raised(index, slot):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
            if index == 0:
                return f"='{FIN}'!B{self.own_total}+'{FIN}'!C{self.attracted_total}"
            return "=0"

        self._paint(CF, row, "Свои и привлекаемые средства", raised, year=raised)
        financing_in = row
        row += 1
        self._link(CF, row, "Погашение кредита", FIN, self.principal_row)
        financing_principal = row
        row += 1
        self._link(CF, row, "Дивиденды", PNL, self.pnl["dividend"])
        dividends = row
        row += 1

        def financing(index, _slot):
            column = _col(index)
            return f"={column}{financing_in}-{column}{financing_principal}-{column}{dividends}"

        self._paint(CF, row, "Финансовый поток", financing, year=financing, bold=True)
        financing_row = row
        row += 2

        def net(index, _slot):
            column = _col(index)
            return f"={column}{operating}-{column}{investing}+{column}{financing_row}"

        self._paint(CF, row, "Чистый денежный поток", net, year=net, bold=True)
        net_row = row
        row += 1
        self._running(CF, row, net_row, "Деньги на конец месяца")
        self.cash_end = row
        self.operating_row = operating
        self.investing_row = investing
        row += 2
        goods_row = row
        previous = None

        def goods(index, slot, goods_row=goods_row):
            nonlocal previous
            if slot["kind"] == "year":
                return f"={_col(slot['m1'])}{goods_row}"
            bought = f"{_col(index)}{replenish}"
            sold = f"'{COST}'!{_col(index)}{self.cogs_total}"
            if previous is None:
                previous = _col(index)
                return f"={_col(index)}{goods_buy}+{bought}-{sold}"
            formula = f"={previous}{goods_row}+{bought}-{sold}"
            previous = _col(index)
            return formula

        self._paint(CF, goods_row, "Запас товаров на конец", goods, year=goods)
        self.goods_end = goods_row
        supplies_row = goods_row + 1
        previous_supplies = None

        def supplies(index, slot, supplies_row=supplies_row):
            nonlocal previous_supplies
            if slot["kind"] == "year":
                return f"={_col(slot['m1'])}{supplies_row}"
            written = f"'{INV}'!{_col(index)}{self.writeoff_row}"
            if previous_supplies is None:
                previous_supplies = _col(index)
                return f"={_col(index)}{supplies_buy}-{written}"
            formula = f"={previous_supplies}{supplies_row}-{written}"
            previous_supplies = _col(index)
            return formula

        self._paint(CF, supplies_row, "Запас материалов на конец", supplies, year=supplies)
        self.supplies_end = supplies_row

    def _running(self, sheet: str, row: int, source: int, label: str):
        self._label(sheet, row, label, bold=True)
        previous = None
        for index, slot in enumerate(self.slots):
            if slot["kind"] == "year":
                value = f"={_col(slot['m1'])}{row}"
            elif previous is None:
                value = f"={_col(index)}{source}"
                previous = _col(index)
            else:
                value = f"={previous}{row}+{_col(index)}{source}"
                previous = _col(index)
            cell = self.ws[sheet].cell(row, FIRST + index, value)
            cell.font = BOLD
            cell.fill = TOTAL_FILL if slot["kind"] == "month" else YEAR_FILL
            cell.border = BORDER
            cell.number_format = MONEY

    def _balance(self):
        self._header(BAL, "Баланс", "Деньги, запасы и основные средства — из ДДС и инвестиций. Ниже на этом листе NPV, IRR проекта и IRR инвестора.")
        row = 4
        self._link(BAL, row, "Деньги", CF, self.cash_end)
        cash = row
        row += 1
        self._link(BAL, row, "Запас товаров", CF, self.goods_end)
        goods = row
        row += 1
        self._link(BAL, row, "Запас материалов", CF, self.supplies_end)
        supplies = row
        row += 1
        self._link(BAL, row, "Основные средства", INV, self.book_end)
        assets_fixed = row
        row += 1

        def assets(index, _slot, rows=(cash, goods, supplies, assets_fixed)):
            return "=" + "+".join(f"{_col(index)}{item}" for item in rows)

        self._paint(BAL, row, "Активы", assets, year=assets, bold=True)
        assets_row = row
        row += 2
        profit = self.pnl["profit"]
        dividend = self.pnl["dividend"]
        retained_source = row

        def earned(index, slot):
            if slot["kind"] == "year":
                return f"=SUM({_col(slot['m0'])}{row}:{_col(slot['m1'])}{row})"
            return f"='{PNL}'!{_col(index)}{profit}-'{PNL}'!{_col(index)}{dividend}"

        self._paint(BAL, row, "Прибыль месяца после дивидендов", earned, year=earned)
        row += 1
        self._running(BAL, row, retained_source, "Нераспределённая прибыль")
        retained = row
        row += 1

        def equity(index, _slot, retained=retained):
            own = f"'{FIN}'!B{self.own_total}"
            attracted = f"'{FIN}'!C{self.attracted_total}"
            base = f'IF(\'{SRC}\'!$B$19=2,{own},{own}+{attracted})'
            return f"={base}+{_col(index)}{retained}"

        self._paint(BAL, row, "Капитал", equity, year=equity, bold=True)
        equity_row = row
        self.equity_row = equity_row
        row += 1

        def debt(index, _slot):
            return f'=IF(\'{SRC}\'!$B$19=2,{_ref(FIN, index, self.loan_close)},0)'

        self._paint(BAL, row, "Кредит", debt, year=debt)
        debt_row = row
        row += 1
        self._binary(BAL, row, "Капитал и обязательства", equity_row, debt_row, "+", bold=True)
        liabilities = row
        row += 2
        self._binary(BAL, row, "Расхождение баланса", assets_row, liabilities, "-")

    def _analysis(self):
        ws = self.ws[BAL]
        self._label(BAL, 24, "Эффективность", bold=True, section=True)
        fcf_row = 25

        def fcf(index, _slot):
            return f"='{CF}'!{_col(index)}{self.operating_row}-'{CF}'!{_col(index)}{self.investing_row}"

        self._paint(BAL, fcf_row, "Свободный денежный поток", fcf, year=fcf, bold=True)
        self._running(BAL, 26, fcf_row, "Свободный поток нарастающим итогом")
        disc_row = 27

        def discounted(index, slot):
            if slot["kind"] == "year":
                return None
            return f"={_col(index)}{fcf_row}*{_ref(SRC, index, D_DISC)}"

        self._paint(BAL, disc_row, "Поток × дисконт месяца", discounted, year="blank")
        month_slots = [index for index, slot in enumerate(self.slots) if slot["kind"] == "month"]
        self._label(BAL, 29, "NPV по месяцам", bold=True)
        parts = "+".join(f"{_col(index)}{disc_row}" for index in month_slots) or "0"
        npv = ws.cell(29, 2, f"={parts}")
        npv.font = BOLD
        npv.fill = TOTAL_FILL
        npv.border = BORDER
        npv.number_format = MONEY
        self._label(BAL, 30, "IRR проекта, помесячно", bold=True)
        irr_row = 31

        def project_irr(index, slot):
            if slot["kind"] == "year":
                return None
            return f"={_col(index)}{fcf_row}"

        self._paint(BAL, irr_row, "Месяцы для IRR проекта", project_irr, year="blank", fmt="0.00")
        last = _col(len(self.slots) - 1)
        irr = ws.cell(30, 2, f'=IFERROR(IRR(D{irr_row}:{last}{irr_row}),"—")')
        irr.font = BOLD
        irr.fill = TOTAL_FILL
        irr.border = BORDER
        irr.number_format = PCT
        investor_row = 33
        last_month = month_slots[-1] if month_slots else 0

        def investor(index, slot, investor_row=investor_row):
            if slot["kind"] == "year":
                return None
            date = _ref(SRC, index, D_DATE)
            exit_date = _cell(SRC, "B", 26)
            share = _cell(SRC, "B", EXIT_SHARE)
            dividend = f"'{PNL}'!{_col(index)}{self.pnl['dividend']}"
            equity = f"{_col(index)}{self.equity_row}"
            contribution = f"-'{FIN}'!C{self.attracted_total}" if index == month_slots[0] else "0"
            if index == month_slots[0]:
                hit = f"{date}>={exit_date}"
            else:
                previous = _ref(SRC, index - 1 if self.slots[index - 1]["kind"] == "month" else index - 2, D_DATE)
                hit = f"AND({date}>={exit_date},{previous}<{exit_date})"
            if index == last_month:
                hit = f"OR({hit},{exit_date}>{date})"
            return f"={contribution}+{dividend}+IF({hit},{share}*{equity},0)"

        self._paint(BAL, investor_row, "Поток инвестора", investor, year="blank")
        self._label(BAL, 34, "IRR инвестора, помесячно", bold=True)
        investor_irr = ws.cell(34, 2, f'=IFERROR(IRR(D{investor_row}:{last}{investor_row}),"—")')
        investor_irr.font = BOLD
        investor_irr.fill = TOTAL_FILL
        investor_irr.border = BORDER
        investor_irr.number_format = PCT
        self._label(BAL, 35, "Индекс прибыльности", bold=True)
        first = _col(month_slots[0]) if month_slots else "D"
        invested = f"'{CF}'!{first}{self.investing_row}"
        pi = ws.cell(35, 2, f'=IF({invested}=0,"—",(B29+{invested})/{invested})')
        pi.font = BOLD
        pi.fill = TOTAL_FILL
        pi.border = BORDER
        pi.number_format = "0.00"
        self._label(BAL, 36, "Ставка дисконтирования", bold=True)
        rate = ws.cell(36, 2, f"={_cell(SRC, 'B', 6)}")
        rate.font = TEXT
        rate.border = BORDER
        rate.number_format = PCT
        note = ws.cell(38, 1, "NPV дисконтирует каждый месяц по (1 + ставка)^((номер месяца + 1)/12). IRR инвестора: минус привлечённые средства, плюс дивиденды и доля капитала в месяц выхода. Доля по умолчанию 0.")
        note.font = MUTED
        note.alignment = Alignment(wrap_text=True)
        ws.merge_cells(start_row=38, start_column=1, end_row=38, end_column=3)
        ws.row_dimensions[38].height = 36


def build_fem_v4_excel(user: User, form: dict) -> bytes:
    return LegacyBook(user, form).build()
