"""ФЭМ версия 3 — вид книги «Радуга здоровья», расчёт по логике v3, формулы в ячейках.

Жирные ячейки — ввод. Инфляция: 12,9% и 12,7% сверху, ставки 12% и CAPEX 11% в таблице.
ОПиУ, ДДС и анализ ссылаются на предыдущие листы.
"""

from __future__ import annotations

from io import BytesIO

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter
from openpyxl.workbook.defined_name import DefinedName
from openpyxl.worksheet.worksheet import Worksheet

from app.fem_v2 import KEY_LABELS, build_report
from app.models import User

FONT_NAME = "Century Gothic"
PURPLE = "FF641AEA"
MONEY = '#,##0;(#,##0);"–"'
PCT = "0.0%"
FACTOR = "0.000"
THIN = Border(
    left=Side(style="thin", color="B7B7B7"),
    right=Side(style="thin", color="B7B7B7"),
    top=Side(style="thin", color="B7B7B7"),
    bottom=Side(style="thin", color="B7B7B7"),
)
YELLOW = PatternFill("solid", fgColor="FFF4CC")
TOTAL = PatternFill("solid", fgColor="F3EEFF")
WHITE = PatternFill("solid", fgColor="FFFFFF")

PRE = "Предпосылки"
FIN = "Структура финансирования"
REV = "План доходов"
PAY = "ФОТ"
ADM = "Адм расходы"
DEP = "Амортизация"
LOAN = "График платежей"
PNL = "ОПиУ"
CF = "ДДС"
AN = "Анализ эффект."

# Ссылки на предпосылки. Год i лежит в колонке E + i.
SALES_Y, SALES_M = f"'{PRE}'!$C$11", f"'{PRE}'!$C$12"
PLAN_Y, PLAN_M = f"'{PRE}'!$C$13", f"'{PRE}'!$C$14"
END_Y, END_M = f"'{PRE}'!$C$15", f"'{PRE}'!$C$16"
FULL_Y = f"'{PRE}'!$C$17"
CIT = f"'{PRE}'!$C$53"
VAT = f"'{PRE}'!$C$54"
GENERAL = f"'{PRE}'!$C$55"
PROP = f"'{PRE}'!$C$56"
DIV_RATE = f"'{PRE}'!$C$57"
DIV_YEARS = f"'{PRE}'!$C$58"
SOC_SIMPLE = f"'{PRE}'!$C$59"


def build_fem_v3_excel(user: User, form: dict) -> bytes:
    result = build_report(form)
    book = _Book(user, form, result)
    book.write()
    buffer = BytesIO()
    book.wb.save(buffer)
    return buffer.getvalue()


class _Book:
    def __init__(self, user: User, form: dict, result):
        self.user = user
        self.form = form
        self.result = result
        self.years = result.years[:12]
        self.n = len(self.years)
        self.wb = Workbook()
        self.wb.calculation.calcMode = "auto"
        self.wb.calculation.fullCalcOnLoad = True
        self.ws: dict[str, Worksheet] = {}

    def write(self) -> None:
        order = [PRE, FIN, REV, PAY, ADM, DEP, LOAN, PNL, CF, AN]
        self.ws[PRE] = self.wb.active
        self.ws[PRE].title = PRE
        for name in order[1:]:
            self.ws[name] = self.wb.create_sheet(name)
        for name in order:
            sheet = self.ws[name]
            sheet.sheet_properties.tabColor = PURPLE
            sheet.sheet_view.showGridLines = True
            sheet.sheet_view.zoomScale = 100
            sheet.page_setup.orientation = "landscape"
            sheet.page_setup.paperSize = sheet.PAPERSIZE_A4
            sheet.sheet_format.defaultColWidth = 12.63
            sheet.sheet_format.defaultRowHeight = 15
        count = max(len(self.result.funding_rows), 1)
        self.fin_last = 3 + count
        self.fin_total = self.fin_last + 1
        self.fin_share = self.fin_total + 1
        self._premises()
        self._funding()
        self._revenue()
        self._payroll()
        self._admin()
        self._depreciation()
        self._loans()
        self._pnl()
        self._cash()
        self._analysis()
        for sheet in self.ws.values():
            sheet.column_dimensions["A"].width = 42
            sheet.column_dimensions["B"].width = 16
            sheet.sheet_format.defaultColWidth = 12.63
            for index in range(2, 16):
                letter = get_column_letter(index + 1)
                sheet.column_dimensions[letter].width = 12.63

    def _premises(self) -> None:
        ws = self.ws[PRE]
        data = self.result.assumptions
        sales, full, start, end = data["sales"], data["full"], data["start"], data["end"]
        _title(ws, "Предпосылки")
        inputs = [
            (11, "Год начала продаж", sales[0], "0"),
            (12, "Месяц начала продаж", sales[1], "0"),
            (13, "Год начала плана", start[0], "0"),
            (14, "Месяц начала плана", start[1], "0"),
            (15, "Год конца плана", end[0], "0"),
            (16, "Месяц конца плана", end[1], "0"),
            (17, "Год полной загрузки", full[0], "0"),
        ]
        for row, label, value, fmt in inputs:
            _text(ws, row, 1, label, align="left")
            _input(ws, row, 3, value, fmt)
        _text(ws, 18, 1, "Ставка дисконтирования", bold=True, align="left")
        _text(ws, 19, 1, "для прогнозируемого периода", align="left")
        _text(ws, 20, 1, "Стоимость собственного капитала", align="left")
        _input(ws, 20, 3, data["re"], PCT)
        _text(ws, 21, 1, "Доля собственного капитала", align="left")
        _formula(ws, 21, 3, f"=IF('{FIN}'!D{self._share_row()}+'{FIN}'!E{self._share_row()}<=0,0,'{FIN}'!E{self._share_row()})", PCT, bold=True, fill=TOTAL)
        _text(ws, 22, 1, "Стоимость заемного капитала (до налогов)", align="left")
        _input(ws, 22, 3, data["rd"], PCT)
        _text(ws, 23, 1, "Налоговый щит T", align="left")
        _input(ws, 23, 3, data["tax_shield"], PCT)
        _text(ws, 24, 1, "Стоимость заемного капитала (после налогов)", align="left")
        _formula(ws, 24, 3, "=C22*(1-C23)", PCT, bold=True, fill=TOTAL)
        _text(ws, 25, 1, "Доля заемного капитала", align="left")
        _formula(ws, 25, 3, f"=IF('{FIN}'!D{self._share_row()}+'{FIN}'!E{self._share_row()}<=0,0,'{FIN}'!D{self._share_row()})", PCT, bold=True, fill=TOTAL)
        _text(ws, 26, 1, "Средневзвешенная стоимость капитала (WACC)", align="left")
        _formula(ws, 26, 3, "=C20*C21+C24*C25", PCT, bold=True, fill=TOTAL)
        for row, label, value, fmt in (
            (53, "Ставка КПН / ИПН", data["cit_rate"], PCT),
            (54, "НДС", data["vat"], PCT),
            (55, "Общий режим, 1 = да", 1 if data["general"] else 0, "0"),
            (56, "Налог на имущество", data["property_tax"], PCT),
            (57, "Дивиденды, доля прибыли прошлого года", data["dividend_rate"], PCT),
            (58, "Лет выплаты дивидендов", data["investor_years"], "0"),
            (59, "Соцналог на упрощёнке", 0, PCT),
        ):
            _text(ws, row, 1, label, align="left")
            _input(ws, row, 3, value, fmt)
        _text(ws, 6, 1, "Год", bold=True, align="left")
        _text(ws, 7, 1, "Показатели", bold=True, align="left")
        _text(ws, 7, 2, "Обоснование", bold=True)
        _text(ws, 7, 3, "Текущее значение", bold=True)
        _text(ws, 8, 1, "Ставка дисконтирования", align="left")
        _formula(ws, 8, 3, "=C26", PCT, bold=True, fill=TOTAL)
        for index, year in enumerate(self.years):
            col = 5 + index
            letter = get_column_letter(col)
            prev = get_column_letter(col - 1)
            if index == 0:
                _input(ws, 6, col, year, "0")
                _formula(ws, 8, col, "=1", FACTOR, fill=TOTAL)
            else:
                _formula(ws, 6, col, f"={prev}6+1", "0", bold=True, fill=TOTAL)
                _formula(ws, 8, col, f"={prev}8/(1+$C$8)", FACTOR, fill=TOTAL)
            _text(ws, 7, col, f"{index + 1} год", bold=True)
        _text(ws, 9, 1, "Коэффициент инфляции", align="left")
        _text(ws, 9, 2, "Прогноз социально-экономического развития Республики Казахстан", align="left")
        ws.row_dimensions[9].height = 32
        _input(ws, 9, 3, 0.129, PCT)
        _text(ws, 10, 1, "Индекс роста цен на продукцию", align="left")
        _input(ws, 10, 3, 0.127, PCT)
        for index in range(self.n):
            col = 5 + index
            letter = get_column_letter(col)
            prev = get_column_letter(col - 1)
            for row, rate in ((9, "$C$9"), (10, "$C$10")):
                if index == 0:
                    _formula(ws, row, col, "=1", FACTOR)
                else:
                    _formula(ws, row, col, f"={prev}{row}*(1+{rate})", FACTOR)
        _text(ws, 28, 1, "Ставки инфляции", bold=True, align="left")
        _text(ws, 29, 1, "на каждый календарный год", align="left")
        _text(ws, 30, 1, "Вид инфляции", bold=True, align="left")
        rate_rows = [
            (31, "CAPEX"),
            (32, "Цены на услуги"),
            (33, "Переменные затраты"),
            (34, "Постоянные затраты"),
            (35, "Заработные платы"),
        ]
        for row, label in rate_rows:
            _text(ws, row, 1, label, align="left")
            _text(ws, row, 2, "%")
            for index in range(self.n):
                col = 5 + index
                letter = get_column_letter(col)
                prev = get_column_letter(col - 1)
                if index == 0:
                    _input(ws, row, col, 0, PCT)
                elif row == 31 and index == 1:
                    _input(ws, row, col, 0, PCT)
                elif row == 31 and index == 2:
                    _input(ws, row, col, 0.11, PCT)
                elif index == 1:
                    _input(ws, row, col, 0.12, PCT)
                else:
                    _formula(ws, row, col, f"={prev}{row}", PCT)
        _text(ws, 37, 1, "Индекс цен за период", bold=True, align="left")
        period_rows = [(39, 31), (40, 32), (41, 33), (42, 34), (43, 35)]
        for row, rate_row in period_rows:
            _formula(ws, row, 1, f"=A{rate_row}", align="left")
            _text(ws, row, 2, "%")
            for index in range(self.n):
                letter = get_column_letter(5 + index)
                if index == 0:
                    _formula(ws, row, 5 + index, "=1", FACTOR)
                else:
                    _formula(ws, row, 5 + index, f"=1+{letter}{rate_row}", FACTOR)
        _text(ws, 45, 1, "Накопленный индекс цен", bold=True, align="left")
        k_labels = [(47, 39), (48, 40), (49, 41), (50, 42), (51, 43)]
        for row, period_row in k_labels:
            _formula(ws, row, 1, f"=A{period_row}", align="left")
            _text(ws, row, 2, "%")
            for index in range(self.n):
                col = 5 + index
                letter = get_column_letter(col)
                prev = get_column_letter(col - 1)
                if index == 0:
                    _formula(ws, row, col, "=1", FACTOR)
                else:
                    _formula(ws, row, col, f"={prev}{row}*{letter}{period_row}", FACTOR)
        _text(ws, 61, 1, "Месяцев работы", align="left")
        _text(ws, 62, 1, "Загрузка мощности", align="left")
        _text(ws, 63, 1, "Ставка ОПВР", align="left")
        _text(ws, 64, 1, "Месяцев износа в год ввода", align="left")
        capacity = self.form.get("planned_capacity") or {}
        entered = {int(year): float(value or 0) for year, value in capacity.items() if str(year).isdigit()}
        use_entered = any(entered.get(year, 0) > 0 for year in self.years)
        for index, year in enumerate(self.years):
            col = 5 + index
            letter = get_column_letter(col)
            year_cell = f"{letter}$6"
            _formula(
                ws, 61, col,
                f"=MAX(0,MIN({END_Y}*12+{END_M},{year_cell}*12+12)-MAX({SALES_Y}*12+{SALES_M},{year_cell}*12+1)+1)",
                "0", fill=TOTAL,
            )
            if use_entered:
                _input(ws, 62, col, max(0.0, entered.get(year, 0)) / 100, PCT)
            else:
                _formula(
                    ws, 62, col,
                    f"=IF({year_cell}<{SALES_Y},0,IF({year_cell}>={FULL_Y},1,({year_cell}-{SALES_Y}+1)/({FULL_Y}-{SALES_Y}+1)))",
                    PCT, fill=TOTAL,
                )
            _formula(
                ws, 63, col,
                f"=IF({year_cell}>=2028,0.05,IF({year_cell}>=2027,0.045,IF({year_cell}>=2026,0.035,IF({year_cell}>=2025,0.025,0.015))))",
                PCT, fill=TOTAL,
            )
            _formula(ws, 64, col, f"=12-IF({year_cell}={PLAN_Y},{PLAN_M},1)+1", "0")
        for month in range(1, 13):
            _formula(ws, 70, 2 + month, str(month) if False else month, "0", bold=True)
            ws.cell(70, 2 + month, month)
            _paint(ws.cell(70, 2 + month), "0", bold=True, fill=TOTAL)
        _text(ws, 70, 1, "Календарный месяц", align="left")
        _text(ws, 71, 1, "Месяц входит в работу, 1 = да", align="left")
        for index, year in enumerate(self.years):
            row = 72 + index
            _formula(ws, row, 1, f"={get_column_letter(5 + index)}6", "0", align="left")
            for month in range(1, 13):
                _formula(
                    ws, row, 2 + month,
                    f'=IF(AND($A{row}*12+{month}>={SALES_Y}*12+{SALES_M},$A{row}*12+{month}<={END_Y}*12+{END_M}),1,0)',
                    "0", fill=TOTAL,
                )
        self.flag_row = 72

    def _share_row(self) -> int:
        return getattr(self, "fin_share", 8)

    def _funding(self) -> None:
        ws = self.ws[FIN]
        _title(ws, "Структура финансирования")
        _note(ws, "Стоимость строки — сумма заемных, собственных и гранта. Год — год приобретения из анкеты, без месяца.")
        headers = ["№", "Наименование", "Итоговая стоимость, KZT", "Заемные средства", "Собственные средства", "Грант", "Год", "CAPEX, 1 = да", "Срок, лет"]
        for column, label in enumerate(headers, start=1):
            _text(ws, 3, column, label, bold=True)
        source = self.result.assumptions["financing"]
        rows = self.result.funding_rows
        self.fin_first = 4
        for index, row in enumerate(rows):
            line = 4 + index
            own = row["own"]
            external = row["external"]
            if source == "Кредит":
                debt, equity, grant = external, own, 0.0
            elif source == "Грант":
                debt, equity, grant = 0.0, own, external
            else:
                debt, equity, grant = 0.0, own + external, 0.0
            _input(ws, line, 1, index + 1, "0")
            _input(ws, line, 2, row["name"], align="left")
            _formula(ws, line, 3, f"=D{line}+E{line}+F{line}", MONEY, bold=True, fill=TOTAL)
            _input(ws, line, 4, debt, MONEY)
            _input(ws, line, 5, equity, MONEY)
            _input(ws, line, 6, grant, MONEY)
            _input(ws, line, 7, row["year"], "0")
            _input(ws, line, 8, 0 if row.get("working") else 1, "0")
            _input(ws, line, 9, row["life"] or 0, "0")
        last = self.fin_last
        if not rows:
            for column in range(1, 10):
                _input(ws, 4, column, "" if column == 2 else 0, align="left" if column == 2 else "center")
        total = self.fin_total
        _text(ws, total, 2, "ВСЕГО:", bold=True, align="right")
        for column, letter in ((3, "C"), (4, "D"), (5, "E"), (6, "F")):
            _formula(ws, total, column, f"=SUM({letter}{self.fin_first}:{letter}{last})", MONEY, bold=True, fill=TOTAL)
        share = self.fin_share
        _text(ws, share, 2, "Доля D и E", align="right")
        _formula(ws, share, 4, f"=IF(D{total}+E{total}<=0,0,D{total}/(D{total}+E{total}))", PCT, bold=True, fill=TOTAL)
        _formula(ws, share, 5, f"=IF(D{total}+E{total}<=0,0,E{total}/(D{total}+E{total}))", PCT, bold=True, fill=TOTAL)
        _text(ws, share + 2, 2, f"Источник привлечённых: {source}. Грант в WACC не входит.", align="left")
        raw = KEY_LABELS["raw"]
        _text(ws, share + 3, 2, "Запас сырья на старте", align="left")
        _formula(ws, share + 3, 3, f'=SUMIF(B{self.fin_first}:B{last},"{raw}",C{self.fin_first}:C{last})', MONEY, fill=TOTAL)
        self.raw_cell = f"'{FIN}'!C{share + 3}"

    def _revenue(self) -> None:
        ws = self.ws[REV]
        _title(ws, "План доходов")
        _text(ws, 2, 1, "Показатель", bold=True, align="left")
        _text(ws, 2, 2, "ЕИ", bold=True)
        for index in range(self.n):
            _formula(ws, 2, 3 + index, f"='{PRE}'!{get_column_letter(5 + index)}6&\" год\"", bold=True)
        _text(ws, 3, 1, "Месяцев работы в году", align="left")
        _text(ws, 3, 2, "мс.")
        _text(ws, 4, 1, "Загрузка мощности", align="left")
        _text(ws, 4, 2, "%")
        for index in range(self.n):
            src = get_column_letter(5 + index)
            _formula(ws, 3, 3 + index, f"='{PRE}'!{src}61", "0")
            _formula(ws, 4, 3 + index, f"='{PRE}'!{src}62", PCT)
        products = self.result.revenue_rows
        row = 6
        self.product_revenue_rows = []
        self.product_cogs_rows = []
        for product in products:
            _text(ws, row, 1, product["name"], bold=True, align="left")
            row += 1
            _text(ws, row, 1, "Мощность в месяц", align="left")
            _input(ws, row, 2, product["monthly"], MONEY)
            for month, weight in enumerate(product["season"]):
                _input(ws, row, 20 + month, weight, "0.00")
            volume_row = row
            row += 1
            _text(ws, row, 1, "Цена", align="left")
            _input(ws, row, 2, product["price"], MONEY)
            for index in range(self.n):
                _formula(ws, row, 3 + index, f"=$B{row}*'{PRE}'!{get_column_letter(5 + index)}48", MONEY)
            price_row = row
            row += 1
            _text(ws, row, 1, "Себестоимость единицы", align="left")
            _input(ws, row, 2, product["unit_cost"], MONEY)
            for index in range(self.n):
                _formula(ws, row, 3 + index, f"=$B{row}*'{PRE}'!{get_column_letter(5 + index)}49", MONEY)
            cost_row = row
            row += 1
            _text(ws, row, 1, "Выручка потока", align="left")
            for index in range(self.n):
                letter = get_column_letter(3 + index)
                flags = f"'{PRE}'!C{self.flag_row + index}:N{self.flag_row + index}"
                season = f"$T{volume_row}:$AE{volume_row}"
                _formula(
                    ws, row, 3 + index,
                    f"=$B{volume_row}*{letter}$4*{letter}{price_row}*SUMPRODUCT({flags},{season})",
                    MONEY, fill=TOTAL,
                )
            self.product_revenue_rows.append(row)
            row += 1
            _text(ws, row, 1, "Себестоимость потока", align="left")
            for index in range(self.n):
                letter = get_column_letter(3 + index)
                flags = f"'{PRE}'!C{self.flag_row + index}:N{self.flag_row + index}"
                season = f"$T{volume_row}:$AE{volume_row}"
                _formula(
                    ws, row, 3 + index,
                    f"=$B{volume_row}*{letter}$4*{letter}{cost_row}*SUMPRODUCT({flags},{season})",
                    MONEY, fill=TOTAL,
                )
            self.product_cogs_rows.append(row)
            row += 1
        _text(ws, row, 1, "Прочие доходы в месяц", align="left")
        _input(ws, row, 2, float(self.form.get("other_income") or 0), MONEY)
        income_row = row
        for index in range(self.n):
            flags = f"'{PRE}'!C{self.flag_row + index}:N{self.flag_row + index}"
            _formula(ws, row, 3 + index, f"=$B{income_row}*SUM({flags})*'{PRE}'!{get_column_letter(5 + index)}48", MONEY)
        self.other_income_row = income_row
        row += 1
        variable = [item for item in (self.form.get("variable_other") or []) if str(item.get("name") or "").strip() and int(float(item.get("period") or 1)) > 0]
        self.variable_rows = []
        for item in variable:
            _text(ws, row, 1, str(item.get("name")).strip(), align="left")
            _input(ws, row, 2, float(item.get("value") or 0), MONEY)
            _input(ws, row, 16, int(float(item.get("period") or 1)), "0")
            for index in range(self.n):
                flags = f"'{PRE}'!C{self.flag_row + index}:N{self.flag_row + index}"
                _formula(
                    ws, row, 3 + index,
                    f"=$B{row}*'{PRE}'!{get_column_letter(5 + index)}49*({_month_sum(self.flag_row + index, f'$P{row}', 'sales')})",
                    MONEY, fill=TOTAL,
                )
            self.variable_rows.append(row)
            row += 1
        self.rev_total = row
        _text(ws, row, 1, "Итого выручка", bold=True, align="left")
        _text(ws, row, 2, "тенге", bold=True)
        parts = self.product_revenue_rows + [income_row]
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            _formula(ws, row, 3 + index, "=" + "+".join(f"{letter}{item}" for item in parts), MONEY, bold=True, fill=TOTAL)
        row += 1
        self.cogs_total = row
        _text(ws, row, 1, "Итого себестоимость", bold=True, align="left")
        cogs_parts = self.product_cogs_rows + self.variable_rows
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            expr = "=" + "+".join(f"{letter}{item}" for item in cogs_parts) if cogs_parts else "=0"
            _formula(ws, row, 3 + index, expr, MONEY, bold=True, fill=TOTAL)

    def _payroll(self) -> None:
        ws = self.ws[PAY]
        _title(ws, "ФОТ")
        _text(ws, 3, 1, "Наименование должности", bold=True, align="left")
        _text(ws, 3, 2, "Оклад в месяц", bold=True)
        _text(ws, 3, 3, "Премия, %", bold=True)
        staff = self.result.payroll_rows or []
        self.pay_first = 4
        for index, person in enumerate(staff):
            line = 4 + index
            _input(ws, line, 1, person["name"], align="left")
            _input(ws, line, 2, person["salary"], MONEY)
            _input(ws, line, 3, person["bonus"] * 100, "0.00")
            for year_index in range(self.n):
                count_col = 4 + year_index * 2
                fot_col = count_col + 1
                if year_index == 0:
                    _input(ws, line, count_col, 0 if person["invest"] else person["count"], "0.00")
                else:
                    _formula(ws, line, count_col, f"={get_column_letter(count_col - 2)}{line}", "0.00", fill=TOTAL)
                k = f"'{PRE}'!{get_column_letter(5 + year_index)}51"
                flags = f"'{PRE}'!C{self.flag_row + year_index}:N{self.flag_row + year_index}"
                _formula(
                    ws, line, fot_col,
                    f"={get_column_letter(2)}{line}*(1+C{line}/100)*{get_column_letter(count_col)}{line}*SUM({flags})*{k}",
                    MONEY, fill=TOTAL,
                )
        self.pay_last = 3 + max(len(staff), 1)
        if not staff:
            for column in range(1, 4):
                _input(ws, 4, column, 0 if column > 1 else "", MONEY if column == 2 else "0", align="left" if column == 1 else "center")
        header_row = 2
        for year_index in range(self.n):
            _formula(ws, header_row, 4 + year_index * 2, f"='{PRE}'!{get_column_letter(5 + year_index)}6&\" год\"", bold=True)
            _text(ws, 3, 4 + year_index * 2, "Количество", bold=True)
            _text(ws, 3, 5 + year_index * 2, "ФОТ", bold=True)
        total = self.pay_last + 1
        self.fot_row = total
        _text(ws, total, 1, "ФОТ", bold=True, align="left")
        for year_index in range(self.n):
            fot_col = 5 + year_index * 2
            letter = get_column_letter(fot_col)
            _formula(ws, total, fot_col, f"=SUM({letter}{self.pay_first}:{letter}{self.pay_last})", MONEY, bold=True, fill=TOTAL)
        rates = [(5, 0.05), (6, 0.90), (7, 0.03), (8, 0.095)]
        _text(ws, 4, 28, "СО", align="left")
        _text(ws, 5, 28, "База СО", align="left")
        _text(ws, 6, 28, "ООСМС", align="left")
        _text(ws, 7, 28, "Соцналог, общий режим", align="left")
        for offset, value in enumerate(rates):
            _input(ws, 4 + offset, 29, value[1], PCT)
        self.tax_rows = {}
        specs = [
            (total + 1, "Социальные отчисления", f"={{fot}}*$AC$4*$AC$5"),
            (total + 2, "ОПВР", None),
            (total + 3, "ООСМС", f"={{fot}}*$AC$7"),
            (total + 4, "Социальный налог", None),
        ]
        # rates were written to column 29 = AC. Rows 4-7. Good.
        for row, label, _template in (
            (total + 1, "Социальные отчисления", None),
            (total + 2, "ОПВР", None),
            (total + 3, "ООСМС", None),
            (total + 4, "Социальный налог", None),
        ):
            _text(ws, row, 1, label, align="left")
        self.soc_row = total + 1
        self.opvr_row = total + 2
        self.med_row = total + 3
        self.soc_tax_row = total + 4
        for year_index in range(self.n):
            letter = get_column_letter(5 + year_index * 2)
            opvr = f"'{PRE}'!{get_column_letter(5 + year_index)}63"
            _formula(ws, self.soc_row, 5 + year_index * 2, f"={letter}{total}*$AC$4*$AC$5", MONEY, fill=TOTAL)
            _formula(ws, self.opvr_row, 5 + year_index * 2, f"={letter}{total}*{opvr}", MONEY, fill=TOTAL)
            _formula(ws, self.med_row, 5 + year_index * 2, f"={letter}{total}*$AC$6", MONEY, fill=TOTAL)
            _formula(
                ws, self.soc_tax_row, 5 + year_index * 2,
                f"=IF({GENERAL}=1,MAX(0,{letter}{total}*$AC$7-{letter}{self.soc_row}),{letter}{total}*{SOC_SIMPLE})",
                MONEY, fill=TOTAL,
            )
        del rates

    def _admin(self) -> None:
        ws = self.ws[ADM]
        _title(ws, "Административные расходы")
        _text(ws, 2, 1, "Наименование", bold=True, align="left")
        _text(ws, 2, 2, "ЕИ", bold=True)
        for index in range(self.n):
            _formula(ws, 2, 3 + index, f"='{PRE}'!{get_column_letter(5 + index)}6", "0", bold=True)
        month_col = 3 + self.n
        _text(ws, 2, month_col, "В месяц", bold=True)
        _text(ws, 2, month_col + 1, "Период, мес.", bold=True)
        rows = self.result.admin_rows
        self.adm_first = 3
        for index, item in enumerate(rows):
            line = 3 + index
            _input(ws, line, 1, item["name"], align="left")
            _text(ws, line, 2, "тенге")
            _input(ws, line, month_col, 0 if item["invest"] else item["value"], MONEY)
            _input(ws, line, month_col + 1, item["period"], "0")
            for year_index in range(self.n):
                k = f"'{PRE}'!{get_column_letter(5 + year_index)}50"
                flags = f"'{PRE}'!C{self.flag_row + year_index}:N{self.flag_row + year_index}"
                period = f"{get_column_letter(month_col + 1)}{line}"
                amount = f"{get_column_letter(month_col)}{line}"
                _formula(
                    ws, line, 3 + year_index,
                    f'=IF({period}<=0,IF({get_column_letter(3 + year_index)}$2={SALES_Y},{amount}*{k}*INDEX({flags},{SALES_M}),0),{amount}*{k}*({_month_sum(self.flag_row + year_index, period, "admin")}))',
                    MONEY, fill=TOTAL,
                )
        self.adm_last = 2 + max(len(rows), 1)
        prop = self.adm_last + 1
        self.property_row = prop
        _text(ws, prop, 1, "Налог на имущество", align="left")
        _text(ws, prop, 2, "тенге")
        for index in range(self.n):
            dep = get_column_letter(3 + index)
            _formula(ws, prop, 3 + index, f"=('{DEP}'!{dep}8+'{DEP}'!{dep}11)/2*{PROP}", MONEY, fill=TOTAL)
        total = prop + 1
        self.adm_total = total
        _text(ws, total, 1, "ИТОГО", bold=True, align="left")
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            _formula(ws, total, 3 + index, f"=SUM({letter}{self.adm_first}:{letter}{prop})", MONEY, bold=True, fill=TOTAL)

    def _depreciation(self) -> None:
        ws = self.ws[DEP]
        _title(ws, "Расчет амортизационных отчислений")
        _note(ws, "В год ввода норма / 12 × месяцы этого года внутри плана. Дальше полная норма. Замена — в следующем году после полного износа, по индексу CAPEX.")
        _text(ws, 5, 1, "Год", bold=True, align="left")
        for index in range(self.n):
            _formula(ws, 5, 3 + index, f"='{PRE}'!{get_column_letter(5 + index)}6", "0", bold=True)
        assets = []
        for offset, row in enumerate(self.result.funding_rows):
            if row["depreciable"] and row["life"] > 0 and not row.get("working"):
                assets.append((4 + offset, row))
        self.dep_assets = assets
        start = 14
        dep_rows, open_rows, close_rows, repl_rows = [], [], [], []
        for index, (fin_row, _row) in enumerate(assets):
            origin = start + index * 8
            _formula(ws, origin, 1, f"='{FIN}'!B{fin_row}", align="left", bold=True)
            _text(ws, origin + 1, 1, "Ставка амортизации", align="left")
            _formula(ws, origin + 1, 2, f"=IF('{FIN}'!I{fin_row}=0,0,1/'{FIN}'!I{fin_row})", PCT, fill=TOTAL)
            _text(ws, origin + 2, 1, "Первоначальная стоимость", align="left")
            _formula(ws, origin + 2, 2, f"='{FIN}'!C{fin_row}", MONEY, fill=TOTAL)
            _formula(ws, origin + 2, 3, f"='{FIN}'!G{fin_row}", "0", fill=TOTAL)
            _text(ws, origin + 3, 1, "Балансовая стоимость на начало", align="left")
            _text(ws, origin + 4, 1, "Сумма износа в периоде", align="left")
            _text(ws, origin + 5, 1, "Балансовая стоимость на конец", align="left")
            _text(ws, origin + 6, 1, "База нормы", align="left")
            _text(ws, origin + 7, 1, "Замена, CAPEX", align="left")
            for year_index in range(self.n):
                col = 3 + year_index
                letter = get_column_letter(col)
                prev = get_column_letter(col - 1)
                year_ref = f"{letter}$5"
                born = f"$C{origin + 2}"
                cost = f"$B{origin + 2}"
                life = f"'{FIN}'!I{fin_row}"
                k = f"'{PRE}'!{get_column_letter(5 + year_index)}47"
                intro = f"'{PRE}'!{get_column_letter(5 + year_index)}64"
                if year_index == 0:
                    _formula(ws, origin + 7, col, "=0", MONEY, fill=TOTAL)
                    _formula(ws, origin + 3, col, f"=IF(OR({life}=0,{year_ref}<{born}),0,IF({year_ref}={born},{cost},0))", MONEY, fill=TOTAL)
                    _formula(ws, origin + 6, col, f"=IF({year_ref}={born},{cost},0)", MONEY, fill=TOTAL)
                else:
                    _formula(
                        ws, origin + 7, col,
                        f"=IF(AND({life}>0,{prev}{origin + 3}>0,{prev}{origin + 5}<=1),{cost}*{k},0)",
                        MONEY, fill=TOTAL,
                    )
                    _formula(
                        ws, origin + 3, col,
                        f"=IF(OR({life}=0,{year_ref}<{born}),0,IF({year_ref}={born},{cost},{prev}{origin + 5}+{letter}{origin + 7}))",
                        MONEY, fill=TOTAL,
                    )
                    _formula(
                        ws, origin + 6, col,
                        f"=IF({year_ref}<{born},0,IF({letter}{origin + 7}>0,{cost}*{k},IF({year_ref}={born},{cost},{prev}{origin + 6})))",
                        MONEY, fill=TOTAL,
                    )
                _formula(
                    ws, origin + 4, col,
                    f"=IF(OR({life}=0,{year_ref}<{born}),0,MIN({letter}{origin + 3},{letter}{origin + 6}/{life}/12*IF({year_ref}={born},{intro},12)))",
                    MONEY, fill=TOTAL,
                )
                _formula(ws, origin + 5, col, f"=MAX(0,{letter}{origin + 3}-{letter}{origin + 4})", MONEY, fill=TOTAL)
            open_rows.append(origin + 3)
            dep_rows.append(origin + 4)
            close_rows.append(origin + 5)
            repl_rows.append(origin + 7)
        _text(ws, 8, 1, "Балансовая стоимость на начало, итого", bold=True, align="left")
        _text(ws, 9, 1, "Износ за год, итого", bold=True, align="left")
        _text(ws, 11, 1, "Балансовая стоимость на конец, итого", bold=True, align="left")
        _text(ws, 12, 1, "Замена CAPEX, итого", bold=True, align="left")
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            _formula(ws, 8, 3 + index, _sum(letter, open_rows), MONEY, bold=True, fill=TOTAL)
            _formula(ws, 9, 3 + index, _sum(letter, dep_rows), MONEY, bold=True, fill=TOTAL)
            _formula(ws, 11, 3 + index, _sum(letter, close_rows), MONEY, bold=True, fill=TOTAL)
            _formula(ws, 12, 3 + index, _sum(letter, repl_rows), MONEY, bold=True, fill=TOTAL)

    def _loans(self) -> None:
        ws = self.ws[LOAN]
        _title(ws, "График платежей")
        _note(ws, "Платёж k — через k месяцев после выдачи. Льгота: тело 0, проценты со всей суммы. Последний платёж закрывает остаток. Сумма берётся из заемных средств.")
        _text(ws, 2, 2, "№ платежа", bold=True)
        _text(ws, 2, 3, "Дата", bold=True)
        _text(ws, 2, 4, "Остаток на начало", bold=True)
        _text(ws, 2, 5, "Проценты", bold=True)
        _text(ws, 2, 6, "Основной долг", bold=True)
        _text(ws, 2, 7, "Платёж", bold=True)
        _text(ws, 2, 8, "Сальдо", bold=True)
        _text(ws, 2, 14, "Условия", bold=True)
        labels = [
            (3, "Сумма", f"='{FIN}'!D{self.fin_total}", MONEY),
            (4, "Ставка банка", self.result.assumptions["rd"], PCT),
            (6, "Ставка для клиента", "=P4", PCT),
            (7, "Срок субсидирования, лет", 0, "0"),
            (8, "Срок, лет", f"=IF(P9=0,0,P9/12)", "0.00"),
            (9, "Кол-во платежей, мес.", int(self.form.get("loan_term") or 0), "0"),
            (10, "Дата выдачи", f"=DATE({PLAN_Y},{PLAN_M},1)", "DD.MM.YYYY"),
            (18, "Льгота, мес.", int(self.form.get("loan_grace") or 0), "0"),
            (19, "Аннуитет, 1 = да", 1 if self.form.get("loan_annuity") else 0, "0"),
        ]
        for row, label, value, fmt in labels:
            _text(ws, row, 14, label, align="left")
            if isinstance(value, str) and value.startswith("="):
                _formula(ws, row, 16, value, fmt, fill=TOTAL)
            elif row in (4, 7, 9, 18, 19):
                _input(ws, row, 16, value, fmt)
            else:
                _formula(ws, row, 16, value, fmt, fill=TOTAL)
        _formula(ws, 3, 16, f"='{FIN}'!D{self.fin_total}", MONEY, bold=True, fill=TOTAL)
        term = int(self.form.get("loan_term") or 0)
        count = max(term, 1)
        count = min(count, 180)
        _formula(ws, 17, 16, '=IF(OR(P9<=P18,P3<=0),0,PMT(P4/12,P9-P18,-P3))', MONEY, fill=TOTAL)
        _text(ws, 17, 14, "Аннуитетный платёж", align="left")
        for step in range(1, count + 1):
            row = 3 + step
            prev = row - 1
            _formula(ws, row, 2, f"={step}" if step == 1 else f"=B{prev}+1", "0")
            _formula(ws, row, 3, f"=EDATE($P$10,{step})", "DD.MM.YYYY")
            if step == 1:
                _formula(ws, row, 4, "=P3", MONEY, fill=TOTAL)
            else:
                _formula(ws, row, 4, f"=H{prev}", MONEY, fill=TOTAL)
            _formula(ws, row, 5, f"=IF(OR(B{row}>$P$9,D{row}<=0),0,D{row}*$P$4/12)", MONEY, fill=TOTAL)
            _formula(
                ws, row, 6,
                f'=IF(OR(B{row}>$P$9,D{row}<=0),0,IF(B{row}=$P$9,D{row},IF(B{row}<=$P$18,0,IF($P$19=1,MIN(D{row},MAX(0,$P$17-E{row})),MIN(D{row},$P$3/MAX($P$9-$P$18,1))))))',
                MONEY, fill=TOTAL,
            )
            _formula(ws, row, 7, f"=E{row}+F{row}", MONEY, fill=TOTAL)
            _formula(ws, row, 8, f"=MAX(0,D{row}-F{row})", MONEY, fill=TOTAL)
        self.loan_last = 3 + count

    def _pnl(self) -> None:
        ws = self.ws[PNL]
        _title(ws, "Отчет о доходах и расходах по проекту")
        _text(ws, 4, 1, "Наименование", bold=True, align="left")
        for index in range(self.n):
            if index == 0:
                _formula(ws, 4, 2, f"='{PRE}'!E6", "0", bold=True)
            else:
                prev = get_column_letter(1 + index)
                _formula(ws, 4, 2 + index, f"={prev}4+1", "0", bold=True)
        rows = [
            (6, "Выручка итого", "rev"),
            (7, "Себестоимость", "cogs"),
            (8, "Валовая прибыль", "gross"),
            (9, "ФОТ", "pay"),
            (10, "Налоги ФОТ", "tax"),
            (11, "Административные расходы", "adm"),
            (12, "Амортизация", "dep"),
            (13, "Выплаты по вознаграждениям", "interest"),
            (14, "Прибыль до выплаты налога", "ebt"),
            (15, "КПН", "cit"),
            (16, "Чистая прибыль", "net"),
            (17, "Рентабельность продаж", "margin"),
            (18, "EBITDA", "ebitda"),
            (20, "ср/мес выручка", "avg_rev"),
            (21, "ср/мес ЧП", "avg_net"),
        ]
        for row, label, _key in rows:
            _text(ws, row, 1, label, bold=row in (6, 8, 14, 16, 18), align="left")
        _text(ws, 23, 1, "Перенос убытка на начало", align="left")
        _text(ws, 24, 1, "Использовано убытка", align="left")
        for index in range(self.n):
            col = 2 + index
            letter = get_column_letter(col)
            prev = get_column_letter(col - 1)
            rev = f"'{REV}'!{get_column_letter(3 + index)}{self.rev_total}"
            cogs = f"'{REV}'!{get_column_letter(3 + index)}{self.cogs_total}"
            fot = f"'{PAY}'!{get_column_letter(5 + index * 2)}{self.fot_row}"
            tax = "+".join(
                f"'{PAY}'!{get_column_letter(5 + index * 2)}{item}"
                for item in (self.soc_row, self.opvr_row, self.med_row, self.soc_tax_row)
            )
            adm = f"'{ADM}'!{get_column_letter(3 + index)}{self.adm_total}"
            dep = f"'{DEP}'!{get_column_letter(3 + index)}9"
            _formula(ws, 6, col, f"={rev}", MONEY, bold=True, fill=TOTAL)
            _formula(ws, 7, col, f"={cogs}", MONEY, fill=TOTAL)
            _formula(ws, 8, col, f"={letter}6-{letter}7", MONEY, bold=True, fill=TOTAL)
            _formula(ws, 9, col, f"={fot}", MONEY, fill=TOTAL)
            _formula(ws, 10, col, f"={tax}", MONEY, fill=TOTAL)
            _formula(ws, 11, col, f"={adm}", MONEY, fill=TOTAL)
            _formula(ws, 12, col, f"={dep}", MONEY, fill=TOTAL)
            _formula(
                ws, 13, col,
                f'=SUMPRODUCT((YEAR(\'{LOAN}\'!$C$4:$C${self.loan_last})={letter}$4)*(\'{LOAN}\'!$E$4:$E${self.loan_last}))',
                MONEY, fill=TOTAL,
            )
            _formula(ws, 14, col, f"={letter}8-SUM({letter}9:{letter}13)", MONEY, bold=True, fill=TOTAL)
            if index == 0:
                _formula(ws, 23, col, "=0", MONEY, fill=TOTAL)
            else:
                _formula(ws, 23, col, f"=MAX(0,-{prev}14)+{prev}23-{prev}24", MONEY, fill=TOTAL)
            _formula(ws, 24, col, f"=IF({GENERAL}=1,MIN(MAX({letter}14,0),{letter}23),0)", MONEY, fill=TOTAL)
            _formula(
                ws, 15, col,
                f"=IF({GENERAL}=1,MAX(0,{letter}14-{letter}24)*{CIT},MAX(0,{letter}6)*{CIT})",
                MONEY, fill=TOTAL,
            )
            _formula(ws, 16, col, f"={letter}14-{letter}15", MONEY, bold=True, fill=TOTAL)
            _formula(ws, 17, col, f'=IF({letter}6<=0,0,{letter}16/{letter}6)', PCT, fill=TOTAL)
            _formula(ws, 18, col, f"={letter}14+{letter}12+{letter}13", MONEY, bold=True, fill=TOTAL)
            _formula(ws, 20, col, f"={letter}6/12", MONEY, fill=TOTAL)
            _formula(ws, 21, col, f"={letter}16/12", MONEY, fill=TOTAL)

    def _cash(self) -> None:
        ws = self.ws[CF]
        _title(ws, "Прогнозный отчет о движении денежных средств")
        for index in range(self.n):
            _formula(ws, 4, 2 + index, f"='{PNL}'!{get_column_letter(2 + index)}4", "0", bold=True)
        lines = [
            (5, "Движение денежных средств от операционной деятельности"),
            (6, "Поступление от реализации"),
            (7, "Себестоимость, оплата"),
            (8, "ФОТ"),
            (9, "Налоги ФОТ"),
            (10, "Административные расходы"),
            (11, "Выплата вознаграждений"),
            (12, "КПН"),
            (13, "Прирост оборотного капитала"),
            (14, "Итого движение денег по операционной деятельности"),
            (15, "Движение денежных средств от инвестиционной деятельности"),
            (16, "CAPEX, включая замену"),
            (17, "Итого движение денег по инвестиционной деятельности"),
            (18, "Движение денежных средств от финансовой деятельности"),
            (19, "Получение финансирования"),
            (20, "Выплата основного долга"),
            (21, "Дивиденды"),
            (22, "Итого движение денег по финансовой деятельности"),
            (23, "ИТОГО поступление-выбытие"),
            (24, "Денежные средства на начало периода"),
            (25, "Денежные средства на конец периода"),
        ]
        for row, label in lines:
            _text(ws, row, 1, label, bold=row in (14, 17, 22, 23, 25), align="left")
        for index in range(self.n):
            col = 2 + index
            letter = get_column_letter(col)
            prev = get_column_letter(col - 1)
            year = f"{letter}$4"
            capex_new = (
                f"SUMPRODUCT(('{FIN}'!$G${self.fin_first}:$G${self.fin_last}={year})"
                f"*('{FIN}'!$H${self.fin_first}:$H${self.fin_last}=1)"
                f"*('{FIN}'!$C${self.fin_first}:$C${self.fin_last}))"
            )
            repl = f"'{DEP}'!{get_column_letter(3 + index)}12"
            _formula(ws, 6, col, f"='{PNL}'!{letter}6*(1+{VAT})", MONEY, fill=TOTAL)
            _formula(ws, 7, col, f"='{PNL}'!{letter}7*(1+{VAT})", MONEY, fill=TOTAL)
            _formula(ws, 8, col, f"='{PNL}'!{letter}9", MONEY, fill=TOTAL)
            _formula(ws, 9, col, f"='{PNL}'!{letter}10", MONEY, fill=TOTAL)
            prop = f"'{ADM}'!{get_column_letter(3 + index)}{self.property_row}"
            _formula(ws, 10, col, f"=('{PNL}'!{letter}11-{prop})*(1+{VAT})+{prop}", MONEY, fill=TOTAL)
            _formula(ws, 11, col, f"='{PNL}'!{letter}13", MONEY, fill=TOTAL)
            _formula(ws, 12, col, f"='{PNL}'!{letter}15", MONEY, fill=TOTAL)
            _formula(
                ws, 13, col,
                f'=IF(AND({year}={SALES_Y},{SALES_M}=12),{self.raw_cell},IF(AND({year}={SALES_Y}+1,{SALES_M}=12),-{self.raw_cell},0))',
                MONEY, fill=TOTAL,
            )
            _formula(ws, 14, col, f"={letter}6-{letter}7-{letter}8-{letter}9-{letter}10-{letter}11-{letter}12-{letter}13", MONEY, bold=True, fill=TOTAL)
            _formula(ws, 16, col, f"={capex_new}+{repl}", MONEY, fill=TOTAL)
            _formula(ws, 17, col, f"=-{letter}16", MONEY, bold=True, fill=TOTAL)
            if index == 0:
                _formula(ws, 19, col, f"='{FIN}'!D{self.fin_total}+'{FIN}'!E{self.fin_total}+'{FIN}'!F{self.fin_total}", MONEY, fill=TOTAL)
            else:
                _formula(ws, 19, col, "=0", MONEY, fill=TOTAL)
            _formula(
                ws, 20, col,
                f"=SUMPRODUCT((YEAR('{LOAN}'!$C$4:$C${self.loan_last})={year})*('{LOAN}'!$F$4:$F${self.loan_last}))",
                MONEY, fill=TOTAL,
            )
            if index == 0:
                _formula(ws, 21, col, "=0", MONEY, fill=TOTAL)
            else:
                _formula(
                    ws, 21, col,
                    f'=IF(AND({year}-{get_column_letter(2)}4<={DIV_YEARS},\'{PNL}\'!{prev}16>0),\'{PNL}\'!{prev}16*{DIV_RATE},0)',
                    MONEY, fill=TOTAL,
                )
            _formula(ws, 22, col, f"={letter}19-{letter}20-{letter}21", MONEY, bold=True, fill=TOTAL)
            _formula(ws, 23, col, f"={letter}14+{letter}17+{letter}22", MONEY, bold=True, fill=TOTAL)
            if index == 0:
                _input(ws, 24, col, 0, MONEY)
            else:
                _formula(ws, 24, col, f"={prev}25", MONEY, fill=TOTAL)
            _formula(ws, 25, col, f"={letter}23+{letter}24", MONEY, bold=True, fill=TOTAL)

    def _analysis(self) -> None:
        ws = self.ws[AN]
        _title(ws, "Расчет эффективности проекта")
        _formula(ws, 3, 1, f"='{PRE}'!C26", PCT, bold=True, fill=TOTAL)
        for index in range(self.n):
            _formula(ws, 3, 2 + index, f"='{PNL}'!{get_column_letter(2 + index)}4&\" год\"", bold=True)
            letter = get_column_letter(2 + index)
            prev = get_column_letter(1 + index)
            if index == 0:
                _formula(ws, 4, 2, "=1", FACTOR, fill=TOTAL)
            else:
                _formula(ws, 4, 2 + index, f"={prev}4/(1+$A$3)", FACTOR, fill=TOTAL)
            _formula(ws, 5, 2 + index, f"='{CF}'!{letter}14", MONEY, fill=TOTAL)
            _formula(ws, 6, 2 + index, f"='{PNL}'!{letter}13*(1-'{PRE}'!C23)", MONEY, fill=TOTAL)
            _formula(ws, 7, 2 + index, f"='{CF}'!{letter}17", MONEY, fill=TOTAL)
            _formula(ws, 8, 2 + index, f"={letter}5+{letter}6+{letter}7", MONEY, bold=True, fill=TOTAL)
            if index == 0:
                _formula(ws, 9, 2, f"={letter}8", MONEY, fill=TOTAL)
                _formula(ws, 11, 2, f"={letter}10", MONEY, fill=TOTAL)
            else:
                _formula(ws, 9, 2 + index, f"={prev}9+{letter}8", MONEY, fill=TOTAL)
                _formula(ws, 11, 2 + index, f"={prev}11+{letter}10", MONEY, fill=TOTAL)
            _formula(ws, 10, 2 + index, f"={letter}8*{letter}4", MONEY, fill=TOTAL)
            _formula(
                ws, 12, 2 + index,
                f'=IF(\'{PNL}\'!{letter}13+\'{CF}\'!{letter}20<=1,NA(),(\'{CF}\'!{letter}14+\'{PNL}\'!{letter}13)/(\'{PNL}\'!{letter}13+\'{CF}\'!{letter}20))',
                "0.00", fill=TOTAL,
            )
        for row, label in (
            (4, "Коэффициент дисконтирования"),
            (5, "Движение денег от операционной деятельности"),
            (6, "Проценты × (1−T), возврат в FCF"),
            (7, "Инвестиционный поток"),
            (8, "Free Cash Flow"),
            (9, "Free Cash Flow нарастающим итогом"),
            (10, "Дисконтированный денежный поток"),
            (11, "NPV нарастающим итогом"),
            (12, "DSCR"),
        ):
            _text(ws, row, 1, label, bold=row in (8, 11), align="left")
        last = get_column_letter(1 + self.n)
        _text(ws, 14, 1, "NPV проекта", bold=True, align="left")
        _formula(ws, 14, 2, f"={last}11", MONEY, bold=True, fill=TOTAL)
        _text(ws, 15, 1, "IRR", bold=True, align="left")
        _formula(ws, 15, 2, f'=IFERROR(IRR(B8:{last}8),"не считается")', PCT, bold=True, fill=TOTAL)
        _text(ws, 16, 1, "MIRR", bold=True, align="left")
        _formula(ws, 16, 2, f'=IFERROR(MIRR(B8:{last}8,$A$3,$A$3),"не считается")', PCT, bold=True, fill=TOTAL)
        _text(ws, 17, 1, "IC, сумма CAPEX", align="left")
        _formula(ws, 17, 2, f"=SUM('{CF}'!B16:{last}16)", MONEY, fill=TOTAL)
        _text(ws, 18, 1, "P = NPV / IC", align="left")
        _formula(ws, 18, 2, '=IF(B17=0,"не считается",B14/B17)', PCT, fill=TOTAL)
        _text(ws, 19, 1, "PI = P + 1", align="left")
        _formula(ws, 19, 2, '=IF(ISNUMBER(B18),B18+1,"не считается")', "0.00", fill=TOTAL)
        _text(ws, 20, 1, "Простой срок, лет", align="left")
        _formula(ws, 20, 2, "=" + _payback(9, 8, self.n), "0.00", fill=TOTAL)
        _text(ws, 21, 1, "Дисконтированный срок, лет", align="left")
        _formula(ws, 21, 2, "=" + _payback(11, 10, self.n), "0.00", fill=TOTAL)
        _text(ws, 22, 1, "ARR", align="left")
        if self.n >= 2:
            _formula(ws, 22, 2, f'=IF(B17=0,"не считается",AVERAGE(C8:{last}8)/B17)', PCT, fill=TOTAL)
        else:
            _formula(ws, 22, 2, '="не считается"', fill=TOTAL)
        _text(ws, 23, 1, "Минимальный DSCR", align="left")
        _formula(ws, 23, 2, f'=IFERROR(AGGREGATE(5,6,B12:{last}12),"не считается")', "0.00", fill=TOTAL)
        _text(ws, 24, 1, "Остаток на конец, минимум по годам", align="left")
        _formula(ws, 24, 2, f"=MIN('{CF}'!B25:{last}25)", MONEY, fill=TOTAL)
        _text(ws, 26, 1, "Сценарии ниже — отдельные полные прогоны при нулевой инфляции анкеты. Ставки на листе «Предпосылки» на них не влияют.", align="left")
        _text(ws, 27, 1, "Сценарий", bold=True, align="left")
        _text(ws, 27, 2, "NPV", bold=True)
        _text(ws, 27, 3, "IRR", bold=True)
        for index, row in enumerate(self.result.scenarios):
            line = 28 + index
            _text(ws, line, 1, row["name"], align="left")
            _formula(ws, line, 2, row["npv"], MONEY)
            if row["irr"] is None:
                _text(ws, line, 3, "не считается")
            else:
                _formula(ws, line, 3, row["irr"], PCT)
        line = 30 + len(self.result.scenarios)
        _text(ws, line, 1, "Помесячная проверка кассы — отдельный прогон при нулевой инфляции анкеты. Ставки на листе «Предпосылки» на неё не влияют.", align="left")
        line += 1
        _text(ws, line, 1, "Минимальный остаток внутри года", align="left")
        _formula(ws, line, 2, self.result.metrics["min_cash"], MONEY)
        gap = self.result.metrics.get("first_gap")
        line += 1
        _text(ws, line, 1, "Первый кассовый разрыв", align="left")
        if gap:
            _text(ws, line, 2, f"{int(gap['month']):02d}.{gap['year']}")
            _formula(ws, line, 3, gap["closing_cash"], MONEY)
        else:
            _text(ws, line, 2, "нет")


def _payback(cum_row: int, flow_row: int, count: int) -> str:
    first = f"B{flow_row}"
    expr = f'IF({first}>=0,0,'
    for index in range(1, count):
        prev = f"{get_column_letter(1 + index)}{cum_row}"
        current = f"{get_column_letter(2 + index)}{cum_row}"
        amount = f"{get_column_letter(2 + index)}{flow_row}"
        expr += f'IF(AND({prev}<0,{current}>=0),IF({amount}=0,"не считается",{index}+ABS({prev})/{amount}),'
    expr += '"не окупается в горизонте прогноза"' + ")" * count
    return expr


def _month_sum(flag_row: int, period_cell: str, mode: str) -> str:
    parts = []
    for month in range(1, 13):
        flag = f"'{PRE}'!{get_column_letter(2 + month)}{flag_row}"
        if mode == "admin":
            cond = f"MOD({month}-1,{period_cell})=0"
        else:
            cond = f"MOD({month}-{SALES_M},{period_cell})=0"
        parts.append(f"IF({cond},{flag},0)")
    return "+".join(parts)


def _sum(letter: str, rows: list[int]) -> str:
    if not rows:
        return "=0"
    return "=" + "+".join(f"{letter}{row}" for row in rows)


def _title(ws: Worksheet, text: str) -> None:
    ws["A1"] = text
    ws["A1"].font = Font(name=FONT_NAME, size=10, bold=True, color="000000")


def _note(ws: Worksheet, text: str) -> None:
    ws["A2"] = text
    ws["A2"].font = Font(name=FONT_NAME, size=10, italic=True, color="666666")
    ws["A2"].alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)
    ws.row_dimensions[2].height = 15


def _paint(cell, fmt: str | None = None, bold: bool = False, fill: PatternFill | None = None, align: str = "center") -> None:
    cell.font = Font(name=FONT_NAME, size=10, bold=bold, color="222222")
    cell.border = THIN
    cell.alignment = Alignment(horizontal=align, vertical="center", wrap_text=True)
    if fmt:
        cell.number_format = fmt
    if fill is not None:
        cell.fill = fill


def _text(ws: Worksheet, row: int, column: int, value, bold: bool = False, align: str = "center") -> None:
    cell = ws.cell(row, column, value)
    _paint(cell, bold=bold, align=align)


def _input(ws: Worksheet, row: int, column: int, value, fmt: str | None = None, align: str = "center") -> None:
    cell = ws.cell(row, column, value)
    _paint(cell, fmt, bold=True, align=align)


def _formula(ws: Worksheet, row: int, column: int, value, fmt: str | None = None, bold: bool = False, fill: PatternFill | None = None, align: str = "center") -> None:
    cell = ws.cell(row, column, value)
    _paint(cell, fmt, bold=bold, fill=fill, align=align)
