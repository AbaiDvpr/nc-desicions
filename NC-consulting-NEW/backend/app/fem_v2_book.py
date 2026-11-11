"""Книга ФЭМ версии 2: жёлтые ячейки — анкета, остальные числа — формулы Excel.

ОПиУ, ДДС и анализ ссылаются на предыдущие листы. Помесячная арифметика
лежит на листе «Помесячный расчёт», годовые строки его суммируют.
"""

from __future__ import annotations

from datetime import datetime
from io import BytesIO
from zoneinfo import ZoneInfo

from openpyxl import Workbook
from openpyxl.formatting.rule import CellIsRule
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter
from openpyxl.workbook.defined_name import DefinedName
from openpyxl.worksheet.worksheet import Worksheet

from app.fem_v2 import KEY_LABELS, FemResult
from app.models import User

LOCAL_TZ = ZoneInfo("Asia/Almaty")
MONEY = '#,##0;(#,##0);"–"'
PCT = "0.00%"
HEADER_FONT = Font(bold=True, color="FFFFFF")
BOLD = Font(bold=True)
MUTED = Font(italic=True, color="5C677A")
HEADER_FILL = PatternFill("solid", fgColor="1D3FB8")
SECTION = PatternFill("solid", fgColor="EAF0FF")
INPUT = PatternFill("solid", fgColor="FFF6D6")
OK_FILL = PatternFill("solid", fgColor="E5F6EC")
WARN_FILL = PatternFill("solid", fgColor="FFF4E0")
BAD_FILL = PatternFill("solid", fgColor="FDECEC")
THIN = Side(style="thin", color="E3E7EF")
BORDER = Border(left=THIN, right=THIN, top=THIN, bottom=THIN)

FIN = "Структура финансирования"
PRE = "Предпосылки"
LOAN = "График платежей"
DEP = "Амортизация"
REV = "План доходов"
PAY = "ФОТ"
ADM = "Адм. расходы"
PNL = "ОПиУ"
CF = "ДДС"
AN = "Анализ эффективности"
MON = "Помесячный расчёт"

PRE_YEAR = 32
PRE_LOAD = 35
PRE_K_PRICE = 36
PRE_K_COST = 37
PRE_K_WAGE = 38
PRE_K_CAPEX = 39
PRE_OPVR = 40
YEAR_SPAN = "$C$32:$AZ$32"


def render(user: User, form: dict, result: FemResult) -> bytes:
    book = Workbook()
    book.calculation.calcMode = "auto"
    book.calculation.fullCalcOnLoad = True
    stamp = datetime.now(LOCAL_TZ).strftime("%d.%m.%Y %H:%M")
    subtitle = f"{result.name} · ФЭМ версия 2 · {user.full_name} · {stamp} · суммы в {result.currency}"
    sheets = {
        FIN: book.active,
        PRE: book.create_sheet(PRE),
        LOAN: book.create_sheet(LOAN),
        DEP: book.create_sheet(DEP),
        REV: book.create_sheet(REV),
        PAY: book.create_sheet(PAY),
        ADM: book.create_sheet(ADM),
        PNL: book.create_sheet(PNL),
        CF: book.create_sheet(CF),
        AN: book.create_sheet(AN),
        MON: book.create_sheet(MON),
    }
    sheets[FIN].title = FIN
    ctx = _Context(book, sheets, form, result, subtitle)
    ctx.write()
    buffer = BytesIO()
    book.save(buffer)
    return buffer.getvalue()


class _Context:
    def __init__(self, book: Workbook, sheets: dict[str, Worksheet], form: dict, result: FemResult, subtitle: str):
        self.book = book
        self.sheets = sheets
        self.form = form
        self.result = result
        self.subtitle = subtitle
        self.years = result.years
        self.n = len(self.years)
        self.calendar = [(row["year"], row["month"]) for row in result.monthly]
        self.mon_first = 5
        self.mon_last = 4 + max(1, len(self.calendar))

    def write(self) -> None:
        self._premises()
        self._funding()
        self._revenue_inputs()
        self._payroll_inputs()
        self._admin_inputs()
        self._loans()
        self._depreciation()
        self._monthly()
        self._revenue_years()
        self._payroll_years()
        self._admin_years()
        self._pnl()
        self._cash()
        self._analysis()

    def _name(self, name: str, ref: str) -> None:
        self.book.defined_names.add(DefinedName(name=name, attr_text=ref))

    def _premises(self) -> None:
        ws = self.sheets[PRE]
        data = self.result.assumptions
        _heading(
            ws,
            "2. Предпосылки",
            self.subtitle,
            "Жёлтые ячейки можно менять: индексы, WACC и годовые листы пересчитаются. "
            "Месяц начала продаж и месяца плана — из шага 1, это даты горизонта, не дата покупки.",
        )
        sales, full, start, end = data["sales"], data["full"], data["start"], data["end"]
        rows = [
            (5, "Ставка КПН / ИПН", data["cit_rate"], PCT, "RateCIT", "Упрощёнка — от выручки, общий режим — от прибыли."),
            (6, "Налоговый щит T", data["tax_shield"], PCT, "TaxShield", "0 на упрощёнке. На общем режиме равен ставке КПН."),
            (7, "НДС", data["vat"], PCT, "VatRate", "0, если режим без НДС."),
            (8, "Ставка собственного капитала R_e", data["re"], PCT, "EquityRate", "Базовая ставка НБРК, пока в анкете нет своего поля."),
            (9, "Ставка долга R_d", data["rd"], PCT, "DebtRate", "0, если кредит не выбран."),
            (10, "Годовой рост цен и затрат", data["growth"], PCT, "GrowthRate", "r(t) после первого года. K(1)=1, K(t)=K(t−1)×(1+r)."),
            (11, "Налог на имущество", data["property_tax"], PCT, "PropertyRate", "Средняя балансовая стоимость × ставка."),
            (12, "Дивиденды, доля прибыли", data["dividend_rate"], PCT, "DividendRate", "0, если источник не инвестор."),
            (13, "Лет выплаты дивидендов", data["investor_years"], "0", "DividendYears", "Считаются годы после первого."),
            (14, "Год начала продаж", sales[0], "0", "SalesYear", "Из даты запуска на шаге 1."),
            (15, "Месяц начала продаж", sales[1], "0", "SalesMonth", "Из той же даты. Это не месяц покупки оборудования."),
            (16, "Год начала плана", start[0], "0", "PlanStartYear", "planning_from."),
            (17, "Месяц начала плана", start[1], "0", "PlanStartMonth", "Первый месяц горизонта."),
            (18, "Год конца плана", end[0], "0", "PlanEndYear", "planning_to."),
            (19, "Месяц конца плана", end[1], "0", "PlanEndMonth", "Последний месяц горизонта."),
            (20, "Год полной загрузки", full[0], "0", "FullLoadYear", "До этого года загрузка растёт, если в анкете она не задана."),
            (21, "Общий режим, 1 = да", 1 if data["general"] else 0, "0", "GeneralRegime", "1 — общеустановленный, 0 — упрощёнка."),
            (25, "Дни запаса", data["stock_days"], "0", "StockDays", "В анкете дней нет, старт с нуля."),
            (26, "Отсрочка покупателей, дней", data["receivable_days"], "0", "ReceivableDays", "Дебиторка = выручка / 30 × дни."),
            (27, "Отсрочка поставщиков, дней", data["payable_days"], "0", "PayableDays", "Кредиторка = себестоимость / 30 × дни."),
        ]
        for row, label, value, fmt, name, note in rows:
            _input_row(ws, row, label, value, fmt, note)
            self._name(name, f"'{PRE}'!$B${row}")
        _input_row(ws, 22, "Прочие доходы в месяц", f"='{REV}'!B{self._other_income_row()}", MONEY, "Ссылка на план доходов.")
        self._name("OtherIncome", f"'{PRE}'!$B$22")
        ws["B22"].fill = SECTION
        _input_row(
            ws, 23, "Запас сырья на старте",
            f'=SUMIF(\'{FIN}\'!$A$5:$A$200,"{KEY_LABELS["raw"]}",\'{FIN}\'!$C$5:$C$200)',
            MONEY, "Строка структуры финансирования, если она есть.",
        )
        ws["B23"].fill = SECTION
        self._name("OpeningStock", f"'{PRE}'!$B$23")
        _label(ws, 24, "WACC")
        ws["B24"] = (
            '=IF(EquityFunds+DebtFunds<=0,EquityRate,'
            'EquityRate*EquityFunds/(EquityFunds+DebtFunds)+DebtRate*(1-TaxShield)*DebtFunds/(EquityFunds+DebtFunds))'
        )
        _paint(ws["B24"], PCT, bold=True, fill=SECTION)
        ws["C24"] = "R_e × E/(E+D) + R_d × (1−T) × D/(E+D). Доли — со структуры финансирования."
        _paint(ws["C24"])
        self._name("WaccRate", f"'{PRE}'!$B$24")
        _label(ws, 28, "Месяцев работы в 1-й год, m₁")
        ws["B28"] = f"={get_column_letter(3)}{PRE_LOAD - 1}"
        _paint(ws["B28"], "0", bold=True, fill=SECTION)
        ws["C28"] = "Считает строка «Месяцев работы» первого года."
        _paint(ws["C28"])

        capacity = self.form.get("planned_capacity") or {}
        entered = {int(year): float(value or 0) for year, value in capacity.items() if str(year).isdigit()}
        use_entered = any(entered.get(year, 0) > 0 for year in self.years)
        ws.cell(31, 1, "Годовые ряды. Первый год без индекса. Загрузка — из анкеты, если она заполнена, иначе рост до года полной загрузки.")
        ws.cell(31, 1).font = MUTED
        headers = ["Ряд", "Формула", *[str(year) for year in self.years]]
        _header(ws, PRE_YEAR, ["Ряд", "Откуда"] + [str(year) for year in self.years])
        titles = [
            (33, "Месяцев в году плана", "Пересечение года с датами плана"),
            (34, "Месяцев работы", "Пересечение года с периодом продаж"),
            (35, "Загрузка мощности", "Анкета или (год − старт + 1) / (полный − старт + 1)"),
            (36, "Индекс цен K", "K(1)=1, далее предыдущий × (1+рост)"),
            (37, "Индекс переменных затрат K", "Та же цепочка"),
            (38, "Индекс зарплат и постоянных K", "Та же цепочка"),
            (39, "Индекс CAPEX замены K", "Та же цепочка"),
            (40, "Ставка ОПВР", "4,5% в 2027, 5% с 2028, раньше переходная шкала"),
        ]
        for row, title, note in titles:
            ws.cell(row, 1, title)
            ws.cell(row, 2, note)
            _paint(ws.cell(row, 1), bold=row in (35, 36))
            _paint(ws.cell(row, 2))
        for index in range(self.n):
            col = 3 + index
            letter = get_column_letter(col)
            year_cell = f"{letter}${PRE_YEAR}"
            prev = get_column_letter(col - 1)
            ws.cell(PRE_YEAR, col, self.years[index])
            _paint(ws.cell(PRE_YEAR, col), "0", bold=True, fill=HEADER_FILL)
            ws.cell(PRE_YEAR, col).font = HEADER_FONT
            ws.cell(33, col, (
                f'=MAX(0,MIN(PlanEndYear*12+PlanEndMonth,{year_cell}*12+12)'
                f'-MAX(PlanStartYear*12+PlanStartMonth,{year_cell}*12+1)+1)'
            ))
            ws.cell(34, col, (
                f'=MAX(0,MIN(PlanEndYear*12+PlanEndMonth,{year_cell}*12+12)'
                f'-MAX(SalesYear*12+SalesMonth,{year_cell}*12+1)+1)'
            ))
            if use_entered:
                ws.cell(PRE_LOAD, col, max(0.0, entered.get(self.years[index], 0)) / 100)
                _paint(ws.cell(PRE_LOAD, col), PCT, fill=INPUT)
            else:
                ws.cell(PRE_LOAD, col, (
                    f'=IF({year_cell}<SalesYear,0,IF({year_cell}>=FullLoadYear,1,'
                    f'({year_cell}-SalesYear+1)/(FullLoadYear-SalesYear+1)))'
                ))
                _paint(ws.cell(PRE_LOAD, col), PCT, fill=SECTION)
            if index == 0:
                for row in (PRE_K_PRICE, PRE_K_COST, PRE_K_WAGE, PRE_K_CAPEX):
                    ws.cell(row, col, "=1")
            else:
                for row in (PRE_K_PRICE, PRE_K_COST, PRE_K_WAGE, PRE_K_CAPEX):
                    ws.cell(row, col, f"={prev}{row}*(1+GrowthRate)")
            for row in (PRE_K_PRICE, PRE_K_COST, PRE_K_WAGE, PRE_K_CAPEX):
                _paint(ws.cell(row, col), "0.000", fill=SECTION)
            ws.cell(PRE_OPVR, col, (
                f'=IF({year_cell}>=2028,0.05,IF({year_cell}>=2027,0.045,'
                f'IF({year_cell}>=2026,0.035,IF({year_cell}>=2025,0.025,0.015))))'
            ))
            _paint(ws.cell(33, col), "0", fill=SECTION)
            _paint(ws.cell(34, col), "0", fill=SECTION)
            _paint(ws.cell(PRE_OPVR, col), PCT, fill=SECTION)
        ws.column_dimensions["A"].width = 42
        ws.column_dimensions["B"].width = 22
        ws.column_dimensions["C"].width = 78
        for index in range(self.n):
            ws.column_dimensions[get_column_letter(3 + index)].width = 16
        del headers

    def _other_income_row(self) -> int:
        products = [row for row in (self.form.get("volume_sales") or []) if str(row.get("name") or "").strip()]
        variable = [row for row in (self.form.get("variable_other") or []) if str(row.get("name") or "").strip()]
        return 8 + max(len(products), 1) + 2 + max(len(variable), 1) + 2

    def _funding(self) -> None:
        ws = self.sheets[FIN]
        _heading(
            ws,
            "1. Структура финансирования",
            self.subtitle,
            "Месяца покупки нет: в анкете заполняется только год. Суммы, доли и итоги — формулы. "
            "Жёлтым отмечены строки анкеты.",
        )
        _header(ws, 4, ["Статья", "Год", "Стоимость", "Собственные", "Привлечённые", "Амортизируется, 1 = да", "Срок, лет", "CAPEX, 1 = да"])
        rows = self.result.funding_rows
        self.fin_first = 5
        self.fin_last = 4 + max(len(rows), 1)
        for index, row in enumerate(rows):
            line = 5 + index
            values = [
                row["name"], row["year"], row["cost"], row["own"], row["external"],
                1 if row["depreciable"] else 0, row["life"] or 0, 0 if row.get("working") else 1,
            ]
            for column, value in enumerate(values, start=1):
                ws.cell(line, column, value)
                fmt = MONEY if column in (3, 4, 5) else ("0" if column in (2, 6, 7, 8) else None)
                _paint(ws.cell(line, column), fmt, fill=INPUT)
        if not rows:
            for column in range(1, 9):
                _paint(ws.cell(5, column), fill=INPUT)
        last = self.fin_last
        total = last + 2
        self.fin_total = total
        specs = [
            (0, "Сумма проекта", f"=SUM(C5:C{last})", "ProjectCost"),
            (1, "Собственные", f"=SUM(D5:D{last})", "OwnFunds"),
            (2, "Привлечённые", f"=SUM(E5:E{last})", "AttractedFunds"),
        ]
        for shift, label, formula, name in specs:
            ws.cell(total + shift, 1, label)
            ws.cell(total + shift, 3, formula)
            _paint(ws.cell(total + shift, 1), bold=True, fill=SECTION)
            _paint(ws.cell(total + shift, 3), MONEY, bold=True, fill=SECTION)
            self._name(name, f"'{FIN}'!$C${total + shift}")
        ws.cell(total + 3, 1, "Источник привлечённых")
        ws.cell(total + 3, 3, self.result.assumptions["financing"])
        _paint(ws.cell(total + 3, 1), bold=True, fill=SECTION)
        _paint(ws.cell(total + 3, 3), fill=INPUT)
        self._name("SourceName", f"'{FIN}'!$C${total + 3}")
        derived = [
            (4, "Долг D", '=IF(SourceName="Кредит",AttractedFunds,0)', "DebtFunds"),
            (5, "Грант", '=IF(SourceName="Грант",AttractedFunds,0)', "GrantFunds"),
            (6, "Собственный капитал E", '=OwnFunds+IF(SourceName="Инвестор",AttractedFunds,0)', "EquityFunds"),
            (7, "Доля E", "=IF(EquityFunds+DebtFunds<=0,0,EquityFunds/(EquityFunds+DebtFunds))", None),
            (8, "Доля D", "=IF(EquityFunds+DebtFunds<=0,0,DebtFunds/(EquityFunds+DebtFunds))", None),
        ]
        for shift, label, formula, name in derived:
            ws.cell(total + shift, 1, label)
            ws.cell(total + shift, 3, formula)
            _paint(ws.cell(total + shift, 1), bold=True, fill=SECTION)
            _paint(ws.cell(total + shift, 3), PCT if shift >= 7 else MONEY, bold=True, fill=SECTION)
            if name:
                self._name(name, f"'{FIN}'!$C${total + shift}")
        note = total + 10
        ws.cell(note, 1, (
            "Год строки — год приобретения из анкеты. Если этот год раньше горизонта, сумма попадает в первый год плана. "
            "Внутри года покупка не привязана к календарному месяцу: отдельного поля в анкете нет. "
            "Оборотные строки (запас, реклама, зарплата на инвестпериод) в CAPEX не входят: запас один раз увеличивает "
            "оборотный капитал в месяц запуска, остальные расходы идут через ОПиУ."
        ))
        ws.cell(note, 1).font = MUTED
        ws.row_dimensions[note].height = 48
        for column, width in enumerate([46, 12, 20, 18, 18, 24, 14, 16], start=1):
            ws.column_dimensions[get_column_letter(column)].width = width

    def _revenue_inputs(self) -> None:
        ws = self.sheets[REV]
        _heading(
            ws,
            "5. План доходов",
            self.subtitle,
            "Жёлтые ячейки — потоки анкеты. Годовая выручка и себестоимость внизу суммируют помесячный расчёт.",
        )
        _header(ws, 7, ["Поток", "Мощность в месяц", "Цена 1-го года", "Себестоимость ед.", *list(range(1, 13))])
        products = self.result.revenue_rows
        self.rev_first = 8
        self.rev_last = 7 + max(len(products), 1)
        for index, product in enumerate(products):
            line = 8 + index
            ws.cell(line, 1, product["name"])
            ws.cell(line, 2, product["monthly"])
            ws.cell(line, 3, product["price"])
            ws.cell(line, 4, product["unit_cost"])
            for month, weight in enumerate(product["season"], start=5):
                ws.cell(line, month, weight)
            for column in range(1, 17):
                fmt = "0.00" if column >= 5 else (MONEY if column > 1 else None)
                _paint(ws.cell(line, column), fmt, fill=INPUT)
        if not products:
            for column in range(1, 17):
                _paint(ws.cell(8, column), fill=INPUT)
        variable = [row for row in (self.form.get("variable_other") or []) if str(row.get("name") or "").strip()]
        header = self.rev_last + 2
        ws.cell(header, 1, "Прочие переменные").font = BOLD
        _header(ws, header + 1, ["Статья", "Сумма периода", "Период, мес."])
        self.var_first = header + 2
        self.var_last = self.var_first + max(len(variable), 1) - 1
        for index, row in enumerate(variable):
            line = self.var_first + index
            period = int(float(row.get("period") or 1))
            ws.cell(line, 1, str(row.get("name") or "").strip())
            ws.cell(line, 2, float(row.get("value") or 0))
            ws.cell(line, 3, period)
            for column in range(1, 4):
                _paint(ws.cell(line, column), MONEY if column == 2 else "0", fill=INPUT)
        if not variable:
            for column in range(1, 4):
                _paint(ws.cell(self.var_first, column), fill=INPUT)
        income = self._other_income_row()
        ws.cell(income, 1, "Прочие доходы в месяц")
        ws.cell(income, 2, float(self.form.get("other_income") or 0))
        _paint(ws.cell(income, 1), bold=True)
        _paint(ws.cell(income, 2), MONEY, fill=INPUT)
        self.income_row = income
        ws.column_dimensions["A"].width = 36
        ws.column_dimensions["B"].width = 22
        ws.column_dimensions["C"].width = 20
        ws.column_dimensions["D"].width = 22

    def _payroll_inputs(self) -> None:
        ws = self.sheets[PAY]
        _heading(ws, "6. ФОТ", self.subtitle, "Оклад в месяц — формула из численности, оклада и премии. Год суммирует помесячный лист.")
        _header(ws, 7, ["Должность", "Численность", "Оклад", "Премия, %", "Инвестпериод, 1 = да", "Оклад в месяц"])
        staff = self.result.payroll_rows
        self.pay_first = 8
        self.pay_last = 7 + max(len(staff), 1)
        for index, person in enumerate(staff):
            line = 8 + index
            ws.cell(line, 1, person["name"])
            ws.cell(line, 2, person["count"])
            ws.cell(line, 3, person["salary"])
            ws.cell(line, 4, person["bonus"] * 100)
            ws.cell(line, 5, 1 if person["invest"] else 0)
            ws.cell(line, 6, f"=B{line}*C{line}*(1+D{line}/100)")
            for column in range(1, 6):
                _paint(ws.cell(line, column), MONEY if column == 3 else "0.00", fill=INPUT)
            _paint(ws.cell(line, 6), MONEY, fill=SECTION)
        if not staff:
            for column in range(1, 7):
                _paint(ws.cell(8, column), fill=INPUT)
            ws["F8"] = "=B8*C8*(1+D8/100)"
        rates = [
            (5, "Социальные отчисления", 0.05, "SocialContrib", "От 90% ФОТ."),
            (6, "База социальных отчислений", 0.90, "SocialBase", "90% фонда."),
            (7, "ООСМС", 0.03, "MedicalRate", "3% ФОТ."),
            (8, "Соцналог, общий режим с 2026", 0.06, "SocialTaxGeneral", "6% ФОТ без вычета СО (НК 2026)."),
            (9, "Соцналог, упрощёнка", 0.0, "SocialTaxSimple", "На упрощёнке СН не платится."),
        ]
        ws.cell(4, 8, "Ставки налогов на ФОТ").font = BOLD
        for row, label, value, name, note in rates:
            ws.cell(row, 8, label)
            ws.cell(row, 9, value)
            ws.cell(row, 10, note)
            _paint(ws.cell(row, 8))
            _paint(ws.cell(row, 9), PCT, fill=INPUT)
            _paint(ws.cell(row, 10))
            self._name(name, f"'{PAY}'!$I${row}")
        ws.column_dimensions["A"].width = 32
        ws.column_dimensions["H"].width = 32
        ws.column_dimensions["I"].width = 14
        ws.column_dimensions["J"].width = 36

    def _admin_inputs(self) -> None:
        ws = self.sheets[ADM]
        _heading(ws, "7. Административные расходы", self.subtitle, "Сумма периода — из анкеты. Год собирает помесячный расчёт и налог на имущество.")
        _header(ws, 7, ["Статья", "Сумма периода", "Период, мес.", "Инвестпериод, 1 = да"])
        rows = self.result.admin_rows
        self.adm_first = 8
        self.adm_last = 7 + max(len(rows), 1)
        for index, item in enumerate(rows):
            line = 8 + index
            ws.cell(line, 1, item["name"])
            ws.cell(line, 2, item["value"])
            ws.cell(line, 3, item["period"])
            ws.cell(line, 4, 1 if item["invest"] else 0)
            for column in range(1, 5):
                _paint(ws.cell(line, column), MONEY if column == 2 else "0", fill=INPUT)
        if not rows:
            for column in range(1, 5):
                _paint(ws.cell(8, column), fill=INPUT)
        ws.column_dimensions["A"].width = 42
        ws.column_dimensions["B"].width = 20

    def _loans(self) -> None:
        ws = self.sheets[LOAN]
        _heading(
            ws,
            "3. График платежей",
            self.subtitle,
            "Платёж k — через k месяцев после выдачи. Льготные месяцы: тело 0, проценты = остаток × ставка / 12. "
            "В последнем месяце тело закрывает остаток. ОПиУ и ДДС суммируют колонки процентов и тела.",
        )
        _header(ws, 8, ["Год", "Месяц", "Остаток на начало", "Проценты", "Тело", "Платёж", "Остаток на конец"])
        row = 9
        for loan in self.result.loans:
            ws.cell(row, 3, loan["name"]).font = BOLD
            row += 1
            principal = loan["principal"]
            rate = loan["rate"]
            term = int(loan["term"])
            grace = int(loan["grace"])
            annuity = 1 if loan["annuity"] else 0
            start = loan["start"]
            params = [principal, rate, term, grace, annuity, start[0], start[1]]
            for column, value in enumerate(params, start=3):
                ws.cell(row, column, value)
                _paint(ws.cell(row, column), PCT if column == 4 else MONEY, fill=INPUT)
            base = row
            pmt = f"IF(MAX(C{base + 0}0,0)<=0,0,PMT(D{base}/12,C{base}-F{base},-C{base}))"
            # params sit on `base`: C amount, D rate, E term, F grace, G annuity, H year, I month.
            # The pmt string above used wrong columns. Rewrite explicitly below.
            del pmt
            row += 1
            ws.cell(row, 3, "Сумма")
            ws.cell(row, 4, "Ставка")
            ws.cell(row, 5, "Срок")
            ws.cell(row, 6, "Льгота")
            ws.cell(row, 7, "Аннуитет 1/0")
            ws.cell(row, 8, "Год выдачи")
            ws.cell(row, 9, "Месяц выдачи")
            ws.cell(row, 10, "Платёж аннуитета")
            for column in range(3, 11):
                _paint(ws.cell(row, column))
            # Move the values onto the label row's next line — they were written on `base`,
            # which is the row before these labels. Keep values where they are and point PMT at them.
            value_row = base
            label_row = row
            paying = f"MAX(E{value_row}-F{value_row},0)"
            ws.cell(label_row, 11, f'=IF({paying}<=0,0,PMT(D{value_row}/12,{paying},-C{value_row}))')
            _paint(ws.cell(label_row, 11), MONEY, fill=SECTION)
            row += 1
            first_payment = row
            for step in range(1, max(term, 0) + 1):
                prev_close = f"G{row - 1}" if step > 1 else f"C{value_row}"
                ws.cell(row, 1, f"=INT((H{value_row}*12+I{value_row}-1+{step})/12)")
                ws.cell(row, 2, f"=MOD(H{value_row}*12+I{value_row}-1+{step},12)+1")
                ws.cell(row, 3, f"={prev_close}" if step > 1 else f"=C{value_row}")
                ws.cell(row, 4, f"=C{row}*D{value_row}/12")
                ws.cell(row, 5, (
                    f'=IF({step}=E{value_row},C{row},'
                    f'IF({step}<=F{value_row},0,'
                    f'IF(G{value_row}=1,MIN(C{row},MAX(0,$K${label_row}-D{row})),'
                    f'MIN(C{row},C{value_row}/MAX(E{value_row}-F{value_row},1)))))'
                ))
                ws.cell(row, 6, f"=E{row}+D{row}")
                ws.cell(row, 7, f"=MAX(0,C{row}-E{row})")
                for column in range(1, 8):
                    _paint(ws.cell(row, column), "0" if column <= 2 else MONEY, fill=SECTION)
                row += 1
            row += 1
            del first_payment
        if not self.result.loans:
            ws.cell(9, 1, "Кредитов нет: источник в структуре финансирования не «Кредит», проценты и тело равны нулю.")
            ws.cell(9, 1).font = MUTED
        for column, width in enumerate([22, 12, 22, 16, 16, 16, 22, 16, 16, 22, 22], start=1):
            ws.column_dimensions[get_column_letter(column)].width = width

    def _depreciation(self) -> None:
        ws = self.sheets[DEP]
        _heading(
            ws,
            "4. Амортизация",
            self.subtitle,
            "Стоимость, год и срок ссылаются на структуру финансирования. "
            "В год ввода норма делится на 12 и умножается на число месяцев этого года внутри плана "
            "(от первого месяца года в горизонте, а не от вписанного месяца покупки). Дальше — полная норма. "
            "Замена — в следующем году после полного износа, по индексу CAPEX.",
        )
        labels = ["Статья", "Стоимость", "Год ввода", "Срок, лет", "Норма"]
        _header(ws, 5, labels + [str(year) for year in self.years])
        for index in range(self.n):
            col = 6 + index
            ws.cell(5, col, f"='{PRE}'!{get_column_letter(3 + index)}{PRE_YEAR}")
            _paint(ws.cell(5, col), "0", bold=True, fill=HEADER_FILL)
            ws.cell(5, col).font = HEADER_FONT
        assets = []
        for offset, row in enumerate(self.result.funding_rows):
            if row["depreciable"] and row["life"] > 0:
                assets.append(5 + offset)
        self.dep_assets = assets
        self.dep_origin = 6
        for index, fin_row in enumerate(assets):
            origin = 6 + index * 5
            ws.cell(origin, 1, f"='{FIN}'!A{fin_row}")
            ws.cell(origin, 2, f"='{FIN}'!C{fin_row}")
            ws.cell(origin, 3, f"='{FIN}'!B{fin_row}")
            ws.cell(origin, 4, f"='{FIN}'!G{fin_row}")
            ws.cell(origin, 5, f'=IF(D{origin}=0,0,1/D{origin})')
            ws.cell(origin + 1, 1, "Остаток на начало")
            ws.cell(origin + 2, 1, "Остаток на конец")
            ws.cell(origin + 3, 1, "Замена, CAPEX")
            ws.cell(origin + 4, 1, "База нормы")
            for column in range(1, 6):
                fmt = MONEY if column == 2 else (PCT if column == 5 else "0")
                _paint(ws.cell(origin, column), None if column == 1 else fmt, fill=SECTION)
            for extra in range(1, 5):
                _paint(ws.cell(origin + extra, 1))
            for year_index in range(self.n):
                col = 6 + year_index
                letter = get_column_letter(col)
                prev = get_column_letter(col - 1)
                year_ref = f"{letter}$5"
                k = f"SUMIF('{PRE}'!{YEAR_SPAN},{year_ref},'{PRE}'!{YEAR_SPAN.replace('32', str(PRE_K_CAPEX))})"
                intro = f"12-IF($C{origin}=PlanStartYear,PlanStartMonth,1)+1"
                if year_index == 0:
                    ws.cell(origin + 3, col, 0)
                    ws.cell(origin + 1, col, f'=IF(OR($D{origin}=0,{year_ref}<$C{origin}),0,IF({year_ref}=$C{origin},$B{origin},0))')
                    ws.cell(origin + 4, col, f'=IF({year_ref}=$C{origin},$B{origin},0)')
                else:
                    ws.cell(origin + 3, col, (
                        f'=IF(AND($D{origin}>0,{prev}{origin + 1}>0,{prev}{origin + 2}<=1),$B{origin}*({k}),0)'
                    ))
                    ws.cell(origin + 1, col, (
                        f'=IF(OR($D{origin}=0,{year_ref}<$C{origin}),0,'
                        f'IF({year_ref}=$C{origin},$B{origin},{prev}{origin + 2}+{letter}{origin + 3}))'
                    ))
                    ws.cell(origin + 4, col, (
                        f'=IF({year_ref}<$C{origin},0,IF({letter}{origin + 3}>0,$B{origin}*({k}),'
                        f'IF({year_ref}=$C{origin},$B{origin},{prev}{origin + 4})))'
                    ))
                ws.cell(origin, col, (
                    f'=IF(OR($D{origin}=0,{year_ref}<$C{origin}),0,'
                    f'MIN({letter}{origin + 1},{letter}{origin + 4}/$D{origin}/12*IF({year_ref}=$C{origin},{intro},12)))'
                ))
                ws.cell(origin + 2, col, f"=MAX(0,{letter}{origin + 1}-{letter}{origin})")
                for extra in range(5):
                    _paint(ws.cell(origin + extra, col), MONEY, fill=SECTION)
        footer = 6 + len(assets) * 5 + 1
        self.dep_total = footer
        self.dep_open = footer + 1
        self.dep_close = footer + 2
        self.dep_repl = footer + 3
        self.dep_property = footer + 4
        titles = [
            (footer, "Износ за год"),
            (footer + 1, "Балансовая на начало"),
            (footer + 2, "Балансовая на конец"),
            (footer + 3, "Замена CAPEX"),
            (footer + 4, "Налог на имущество"),
        ]
        for row, title in titles:
            ws.cell(row, 1, title)
            _paint(ws.cell(row, 1), bold=True, fill=SECTION)
        for year_index in range(self.n):
            col = 6 + year_index
            letter = get_column_letter(col)
            dep_cells = "+".join(f"{letter}{6 + index * 5}" for index in range(len(assets))) or "0"
            open_cells = "+".join(f"{letter}{7 + index * 5}" for index in range(len(assets))) or "0"
            close_cells = "+".join(f"{letter}{8 + index * 5}" for index in range(len(assets))) or "0"
            repl_cells = "+".join(f"{letter}{9 + index * 5}" for index in range(len(assets))) or "0"
            ws.cell(footer, col, f"={dep_cells}")
            ws.cell(footer + 1, col, f"={open_cells}")
            ws.cell(footer + 2, col, f"={close_cells}")
            ws.cell(footer + 3, col, f"={repl_cells}")
            ws.cell(footer + 4, col, f"=({letter}{footer + 1}+{letter}{footer + 2})/2*PropertyRate")
            for row in range(footer, footer + 5):
                _paint(ws.cell(row, col), MONEY, bold=True, fill=SECTION)
        ws.column_dimensions["A"].width = 42
        for column in range(2, 6 + self.n):
            ws.column_dimensions[get_column_letter(column)].width = 16

    def _monthly(self) -> None:
        ws = self.sheets[MON]
        _heading(
            ws,
            "Помесячный расчёт",
            self.subtitle,
            "Список месяцев — горизонт плана со шага 1. Год и месяц строки задают календарь. "
            "Выручка, затраты, касса и остаток в строке — формулы. Годовые листы суммируют эти колонки.",
        )
        headers = [
            "Год", "Месяц", "Работает", "Загрузка", "K цен", "K затрат", "K зарплат",
            "Выручка", "Себестоимость", "ФОТ", "Налоги на ФОТ", "Адм. расходы", "CAPEX",
            "Проценты", "Тело долга", "Налог на имущество", "Поступления", "НДС",
            "Прирост ОК", "Свои", "Инвестор", "Грант", "Кредит", "Дивиденды",
            "Операционный поток", "Инвестиционный поток", "Финансовый поток",
            "Остаток на начало", "Остаток на конец", "Разрыв", "Оборотный капитал",
        ]
        _header(ws, 4, headers)
        first = self.mon_first
        last = self.mon_last
        span = f"$A${first}:$A${last}"
        year_match = f"'{PRE}'!{YEAR_SPAN}"
        parts: list[str] = []
        col = 34
        for line in range(self.rev_first, self.rev_last + 1):
            parts.append((col, "rev", line))
            col += 1
        income_col = col
        col += 1
        cogs_cols = []
        for line in range(self.rev_first, self.rev_last + 1):
            cogs_cols.append(col)
            col += 1
        var_cols = []
        for line in range(self.var_first, self.var_last + 1):
            var_cols.append((col, line))
            col += 1
        adm_cols = []
        for line in range(self.adm_first, self.adm_last + 1):
            adm_cols.append((col, line))
            col += 1
        self.part_headers = parts, income_col, cogs_cols, var_cols, adm_cols

        for offset, (year, month) in enumerate(self.calendar or [(self.years[0] if self.years else 2026, 1)]):
            row = first + offset
            nxt = row + 1
            ws.cell(row, 1, year)
            ws.cell(row, 2, month)
            _paint(ws.cell(row, 1), "0", fill=INPUT)
            _paint(ws.cell(row, 2), "0", fill=INPUT)
            ws.cell(row, 3, f'=IF(AND(A{row}*12+B{row}>=SalesYear*12+SalesMonth,A{row}*12+B{row}<=PlanEndYear*12+PlanEndMonth),1,0)')
            ws.cell(row, 4, f'=IF(C{row}=0,0,SUMIF({year_match},A{row},\'{PRE}\'!{YEAR_SPAN.replace("32", str(PRE_LOAD))}))')
            ws.cell(row, 5, f'=SUMIF({year_match},A{row},\'{PRE}\'!{YEAR_SPAN.replace("32", str(PRE_K_PRICE))})')
            ws.cell(row, 6, f'=SUMIF({year_match},A{row},\'{PRE}\'!{YEAR_SPAN.replace("32", str(PRE_K_COST))})')
            ws.cell(row, 7, f'=SUMIF({year_match},A{row},\'{PRE}\'!{YEAR_SPAN.replace("32", str(PRE_K_WAGE))})')
            for column, kind, line in [(item[0], item[1], item[2]) for item in parts]:
                letter = get_column_letter(column)
                ws.cell(row, column, (
                    f'=C{row}*D{row}*E{row}*\'{REV}\'!$B{line}*\'{REV}\'!$C{line}'
                    f'*INDEX(\'{REV}\'!$E{line}:$P{line},1,B{row})'
                ))
                ws.cell(4, column, f"Выручка: строка {line}")
                _paint(ws.cell(row, column), MONEY)
            income_letter = get_column_letter(income_col)
            ws.cell(row, income_col, f"=C{row}*E{row}*OtherIncome")
            ws.cell(4, income_col, "Прочие доходы")
            rev_sum = "+".join(get_column_letter(item[0]) + str(row) for item in parts) or "0"
            ws.cell(row, 8, f"={rev_sum}+{income_letter}{row}")
            for column in cogs_cols:
                # paired with product order
                pass
            for index, column in enumerate(cogs_cols):
                line = self.rev_first + index
                ws.cell(row, column, (
                    f'=C{row}*D{row}*F{row}*\'{REV}\'!$B{line}*\'{REV}\'!$D{line}'
                    f'*INDEX(\'{REV}\'!$E{line}:$P{line},1,B{row})'
                ))
                ws.cell(4, column, f"Себестоимость: строка {line}")
                _paint(ws.cell(row, column), MONEY)
            for column, line in var_cols:
                ws.cell(row, column, (
                    f'=IF(OR(C{row}=0,\'{REV}\'!$C{line}<=0),0,'
                    f'IF(OR(\'{REV}\'!$C{line}=1,MOD(B{row}-SalesMonth,\'{REV}\'!$C{line})=0),\'{REV}\'!$B{line}*F{row},0))'
                ))
                ws.cell(4, column, f"Переменные: строка {line}")
                _paint(ws.cell(row, column), MONEY)
            cogs_sum = "+".join(f"{get_column_letter(column)}{row}" for column in cogs_cols + [item[0] for item in var_cols]) or "0"
            ws.cell(row, 9, f"={cogs_sum}")
            ws.cell(row, 10, f"=C{row}*G{row}*SUMIF('{PAY}'!$E${self.pay_first}:$E${self.pay_last},0,'{PAY}'!$F${self.pay_first}:$F${self.pay_last})")
            ws.cell(row, 11, (
                f'=J{row}*(SocialBase*SocialContrib+SUMIF({year_match},A{row},\'{PRE}\'!{YEAR_SPAN.replace("32", str(PRE_OPVR))})'
                f'+MedicalRate+IF(GeneralRegime=1,MAX(0,SocialTaxGeneral-SocialBase*SocialContrib),SocialTaxSimple))'
            ))
            for column, line in adm_cols:
                ws.cell(row, column, (
                    f'=IF(OR(C{row}=0,\'{ADM}\'!$D{line}=1),0,'
                    f'IF(\'{ADM}\'!$C{line}<=0,IF(AND(A{row}=SalesYear,B{row}=SalesMonth),\'{ADM}\'!$B{line}*G{row},0),'
                    f'IF(OR(\'{ADM}\'!$C{line}=1,MOD(B{row}-1,\'{ADM}\'!$C{line})=0),\'{ADM}\'!$B{line}*G{row},0)))'
                ))
                ws.cell(4, column, f"Адм.: строка {line}")
                _paint(ws.cell(row, column), MONEY)
            adm_sum = "+".join(f"{get_column_letter(column)}{row}" for column, _line in adm_cols) or "0"
            ws.cell(row, 12, f"={adm_sum}")
            prev_year = f"A{row - 1}" if offset else '"год"'
            ws.cell(row, 13, (
                f'=IF(A{row}<>{prev_year},'
                f'SUMIFS(\'{FIN}\'!$C${self.fin_first}:$C${self.fin_last},\'{FIN}\'!$B${self.fin_first}:$B${self.fin_last},A{row},'
                f'\'{FIN}\'!$H${self.fin_first}:$H${self.fin_last},1)'
                f'+SUMIF(\'{DEP}\'!$F$5:${get_column_letter(5 + self.n)}$5,A{row},\'{DEP}\'!$F${self.dep_repl}:${get_column_letter(5 + self.n)}${self.dep_repl}),0)'
            ))
            ws.cell(row, 14, f"=SUMIFS('{LOAN}'!$D$9:$D$500,'{LOAN}'!$A$9:$A$500,A{row},'{LOAN}'!$B$9:$B$500,B{row})")
            ws.cell(row, 15, f"=SUMIFS('{LOAN}'!$E$9:$E$500,'{LOAN}'!$A$9:$A$500,A{row},'{LOAN}'!$B$9:$B$500,B{row})")
            ws.cell(row, 16, (
                f'=IF(A{row}<>A{nxt},SUMIF(\'{DEP}\'!$F$5:${get_column_letter(5 + self.n)}$5,A{row},'
                f'\'{DEP}\'!$F${self.dep_property}:${get_column_letter(5 + self.n)}${self.dep_property}),0)'
            ))
            ws.cell(row, 17, f"=H{row}*(1+VatRate)")
            ws.cell(row, 18, f"=H{row}*VatRate-(I{row}+L{row})*VatRate")
            ws.cell(row, 31, (
                f'=IF(I{row}=0,0,I{row}/30*StockDays)+IF(AND(A{row}=SalesYear,B{row}=SalesMonth),OpeningStock,0)'
                f'+IF(H{row}=0,0,H{row}/30*ReceivableDays)-IF(I{row}=0,0,I{row}/30*PayableDays)'
            ))
            if offset == 0:
                ws.cell(row, 19, f"=AE{row}")
            else:
                ws.cell(row, 19, f"=AE{row}-AE{row - 1}")
            same_start = f"AND(A{row}=PlanStartYear,B{row}=PlanStartMonth)"
            ws.cell(row, 20, f'=IF({same_start},OwnFunds,0)')
            ws.cell(row, 21, f'=IF({same_start},IF(SourceName="Инвестор",AttractedFunds,0),0)')
            ws.cell(row, 22, f'=IF({same_start},GrantFunds,0)')
            ws.cell(row, 23, f'=IF({same_start},DebtFunds,0)')
            first_year = self.years[0] if self.years else 0
            ws.cell(row, 24, (
                f'=IF(AND(A{row}<>{first_year},B{row}=PlanStartMonth,A{row}-{first_year}<=DividendYears,'
                f'SUMIF(\'{PNL}\'!$C$5:${get_column_letter(2 + self.n)}$5,A{row}-1,\'{PNL}\'!$C$16:${get_column_letter(2 + self.n)}$16)>0),'
                f'SUMIF(\'{PNL}\'!$C$5:${get_column_letter(2 + self.n)}$5,A{row}-1,\'{PNL}\'!$C$16:${get_column_letter(2 + self.n)}$16)*DividendRate,0)'
            ))
            ws.cell(row, 25, f"=Q{row}-(I{row}+L{row})*(1+VatRate)-J{row}-K{row}-P{row}-N{row}-IF(A{row}<>A{nxt},SUMIF('{PNL}'!$C$5:${get_column_letter(2 + self.n)}$5,A{row},'{PNL}'!$C$15:${get_column_letter(2 + self.n)}$15),0)-R{row}-S{row}")
            if offset == 0:
                ws.cell(row, 26, f"=-M{row}*(1+VatRate)")
                ws.cell(row, 28, "=0")
            else:
                ws.cell(row, 26, f"=-M{row}*(1+VatRate)+M{row - 1}*VatRate")
                ws.cell(row, 28, f"=AC{row - 1}")
            ws.cell(row, 27, f"=T{row}+U{row}+V{row}+W{row}-O{row}-X{row}")
            ws.cell(row, 29, f"=AB{row}+Y{row}+Z{row}+AA{row}")
            ws.cell(row, 30, f"=IF(AC{row}<-1,1,0)")
            for column in range(3, 32):
                if ws.cell(row, column).fill.fgColor is None or column <= 31:
                    fmt = "0" if column in (3, 30) else ("0.000" if column in (4, 5, 6, 7) else MONEY)
                    if column == 3:
                        fmt = "0"
                    _paint(ws.cell(row, column), fmt, fill=SECTION if column != 1 and column != 2 else INPUT)
        ws.conditional_formatting.add(
            f"AC{first}:AC{last}",
            CellIsRule(operator="lessThan", formula=["0"], fill=BAD_FILL),
        )
        ws.auto_filter.ref = None
        ws.freeze_panes = "C5"
        ws.column_dimensions["A"].width = 12
        for column in range(2, 32):
            ws.column_dimensions[get_column_letter(column)].width = 16
        self.mon_span = span

    def _year_header(self, ws: Worksheet, row: int) -> None:
        _header(ws, row, ["Строка", "Откуда", *[str(year) for year in self.years]])
        for index in range(self.n):
            cell = ws.cell(row, 3 + index, f"='{PRE}'!{get_column_letter(3 + index)}{PRE_YEAR}")
            _paint(cell, "0", bold=True, fill=HEADER_FILL)
            cell.font = HEADER_FONT

    def _sum_year(self, ws: Worksheet, row: int, column_letter: str, label: str, source: str, header_row: int) -> None:
        ws.cell(row, 1, label)
        ws.cell(row, 2, source)
        _paint(ws.cell(row, 1), bold=True, fill=SECTION)
        _paint(ws.cell(row, 2))
        span = self.mon_span
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            ws.cell(
                row, 3 + index,
                f"=SUMIF('{MON}'!{span},{letter}${header_row},'{MON}'!${column_letter}${self.mon_first}:${column_letter}${self.mon_last})",
            )
            _paint(ws.cell(row, 3 + index), MONEY, bold=True, fill=SECTION)

    def _revenue_years(self) -> None:
        ws = self.sheets[REV]
        row = self.income_row + 2
        self.rev_year_row = row
        self._year_header(ws, row)
        self.rev_total_row = row + 1
        self.cogs_total_row = row + 2
        self._sum_year(ws, row + 1, "H", "Выручка", "Сумма помесячного расчёта → ОПиУ", row)
        self._sum_year(ws, row + 2, "I", "Себестоимость", "Сумма помесячного расчёта → ОПиУ", row)

    def _payroll_years(self) -> None:
        ws = self.sheets[PAY]
        row = self.pay_last + 2
        self.pay_year_row = row
        self._year_header(ws, row)
        self.pay_total_row = row + 1
        self.tax_total_row = row + 2
        self._sum_year(ws, row + 1, "J", "ФОТ", "Сумма помесячного расчёта → ОПиУ", row)
        self._sum_year(ws, row + 2, "K", "Налоги на ФОТ", "СО + ОПВР + ООСМС + соцналог → ОПиУ", row)

    def _admin_years(self) -> None:
        ws = self.sheets[ADM]
        row = self.adm_last + 2
        self.adm_year_row = row
        self._year_header(ws, row)
        self.adm_total_row = row + 1
        ws.cell(row + 1, 1, "Административные расходы")
        ws.cell(row + 1, 2, "Помесячный расчёт + налог на имущество из амортизации → ОПиУ")
        _paint(ws.cell(row + 1, 1), bold=True, fill=SECTION)
        _paint(ws.cell(row + 1, 2))
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            dep_letter = get_column_letter(6 + index)
            ws.cell(row + 1, 3 + index, (
                f"=SUMIF('{MON}'!{self.mon_span},{letter}${row},'{MON}'!$L${self.mon_first}:$L${self.mon_last})"
                f"+'{DEP}'!{dep_letter}{self.dep_property}"
            ))
            _paint(ws.cell(row + 1, 3 + index), MONEY, bold=True, fill=SECTION)

    def _link_row(self, ws: Worksheet, row: int, label: str, source: str, formula_at) -> None:
        ws.cell(row, 1, label)
        ws.cell(row, 2, source)
        bold = label in {"Валовая прибыль", "Прибыль до налога", "Чистая прибыль", "EBITDA", "Операционный поток", "Инвестиционный поток", "Финансовый поток", "Остаток на конец", "FCF"}
        _paint(ws.cell(row, 1), bold=bold, fill=SECTION if bold else None)
        _paint(ws.cell(row, 2))
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            ws.cell(row, 3 + index, formula_at(letter, index))
            _paint(ws.cell(row, 3 + index), MONEY, bold=bold, fill=SECTION if bold else None)

    def _pnl(self) -> None:
        ws = self.sheets[PNL]
        _heading(ws, "8. ОПиУ", self.subtitle, "Этот лист ничего не вводит. Каждая строка ссылается на план доходов, ФОТ, адм. расходы, амортизацию или график платежей.")
        self._year_header(ws, 5)
        rev = f"'{REV}'!{{col}}{self.rev_total_row}"
        cogs = f"'{REV}'!{{col}}{self.cogs_total_row}"
        pay = f"'{PAY}'!{{col}}{self.pay_total_row}"
        tax = f"'{PAY}'!{{col}}{self.tax_total_row}"
        adm = f"'{ADM}'!{{col}}{self.adm_total_row}"

        def col_ref(template: str):
            return lambda letter, index: "=" + template.format(col=letter)

        self._link_row(ws, 6, "Выручка", "План доходов", col_ref(rev))
        self._link_row(ws, 7, "Себестоимость", "План доходов", col_ref(cogs))
        self._link_row(ws, 8, "Валовая прибыль", "Выручка − себестоимость", lambda letter, index: f"={letter}6-{letter}7")
        self._link_row(ws, 9, "ФОТ", "ФОТ", col_ref(pay))
        self._link_row(ws, 10, "Налоги на ФОТ", "ФОТ", col_ref(tax))
        self._link_row(ws, 11, "Административные расходы", "Адм. расходы", col_ref(adm))
        self._link_row(
            ws, 12, "Амортизация", "Амортизация",
            lambda letter, index: f"='{DEP}'!{get_column_letter(6 + index)}{self.dep_total}",
        )
        self._link_row(
            ws, 13, "Проценты", "График платежей",
            lambda letter, index: f"=SUMIF('{LOAN}'!$A$9:$A$500,{letter}$5,'{LOAN}'!$D$9:$D$500)",
        )
        self._link_row(ws, 14, "Прибыль до налога", "Валовая − ФОТ − налоги − адм. − износ − проценты", lambda letter, index: f"={letter}8-{letter}9-{letter}10-{letter}11-{letter}12-{letter}13")
        ws.cell(22, 1, "Перенос убытка на начало")
        ws.cell(23, 1, "Использовано убытка")
        ws.cell(24, 1, "Перенос убытка на конец")
        for row in (22, 23, 24):
            _paint(ws.cell(row, 1))
            ws.cell(row, 2, "Только общий режим")
            _paint(ws.cell(row, 2))
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            prev = get_column_letter(2 + index)
            if index == 0:
                ws.cell(22, 3 + index, "=0")
            else:
                ws.cell(22, 3 + index, f"={prev}24")
            ws.cell(23, 3 + index, f"=IF(GeneralRegime=1,MIN(MAX({letter}14,0),{letter}22),0)")
            ws.cell(24, 3 + index, f"=IF(GeneralRegime=1,{letter}22-{letter}23+MAX(-{letter}14,0),0)")
            for row in (22, 23, 24):
                _paint(ws.cell(row, 3 + index), MONEY)
        self._link_row(
            ws, 15, "КПН", "Упрощёнка: выручка × ставка. Общий: прибыль после переноса × ставка",
            lambda letter, index: f"=IF(GeneralRegime=1,MAX(0,{letter}14-{letter}23)*RateCIT,MAX(0,{letter}6)*RateCIT)",
        )
        self._link_row(ws, 16, "Чистая прибыль", "Прибыль до налога − КПН", lambda letter, index: f"={letter}14-{letter}15")
        self._link_row(ws, 17, "Рентабельность продаж", "Чистая прибыль / выручка", lambda letter, index: f'=IF({letter}6=0,0,{letter}16/{letter}6)')
        for index in range(self.n):
            _paint(ws.cell(17, 3 + index), PCT)
        self._link_row(ws, 18, "EBITDA", "Прибыль до налога + амортизация + проценты", lambda letter, index: f"={letter}14+{letter}12+{letter}13")
        self._link_row(ws, 19, "Среднемесячная выручка", "Годовая выручка / 12", lambda letter, index: f"={letter}6/12")
        self._link_row(ws, 20, "Среднемесячная чистая прибыль", "Годовая чистая прибыль / 12", lambda letter, index: f"={letter}16/12")
        ws.column_dimensions["A"].width = 42
        ws.column_dimensions["B"].width = 62

    def _cash(self) -> None:
        ws = self.sheets[CF]
        _heading(ws, "9. ДДС", self.subtitle, "Годовые строки суммируют помесячный расчёт. Амортизации в деньгах нет. Остаток на конец — последний месяц года.")
        self._year_header(ws, 5)
        mapping = [
            (6, "Поступления", "H", "Выручка с НДС"),
            (7, "Себестоимость, оплата", "I", "Себестоимость; НДС добавляет помесячная формула поступлений отдельно"),
            (8, "ФОТ", "J", "Выплата зарплаты"),
            (9, "Налоги на ФОТ", "K", "Платёж работодателя"),
            (10, "Административные, оплата", "L", "Без налога на имущество: он в своей колонке месяца"),
            (11, "Проценты", "N", "График платежей через помесячный расчёт"),
            (12, "КПН", "Y", "В помесячном операционном потоке, отдельной годовой строкой ниже не дублируется"),
        ]
        # Explicit SUMIF rows for the lines that are their own monthly columns.
        lines = [
            (6, "Поступления", "Q", "Выручка × (1+НДС)"),
            (7, "Себестоимость, оплата", "I", "Сумма себестоимости; НДС сидит в колонке НДС"),
            (8, "ФОТ", "J", "Помесячный расчёт"),
            (9, "Налоги на ФОТ", "K", "Помесячный расчёт"),
            (10, "Административные, оплата", "L", "Помесячный расчёт без налога на имущество"),
            (11, "Проценты", "N", "График платежей"),
            (13, "НДС к уплате", "R", "Исходящий − входящий"),
            (14, "Прирост оборотного капитала", "S", "Запасы + дебиторка − кредиторка, прирост к прошлому месяцу"),
            (15, "Операционный поток", "Y", "Поступления − выплаты − прирост ОК − КПН"),
            (16, "CAPEX", "M", "Год покупки из структуры + замена из амортизации"),
            (17, "Инвестиционный поток", "Z", "CAPEX с НДС минус возврат НДС следующего месяца"),
            (18, "Собственные средства", "T", "В месяц начала плана"),
            (19, "Инвестор", "U", "Привлечённые, если источник — инвестор"),
            (20, "Грант", "V", "Привлечённые, если источник — грант"),
            (21, "Получение кредитов", "W", "Привлечённые, если источник — кредит"),
            (22, "Погашение тела долга", "O", "График платежей"),
            (23, "Дивиденды", "X", "Доля чистой прибыли прошлого года, в месяц начала плана"),
            (24, "Финансовый поток", "AA", "Поступления − выплаты"),
        ]
        del mapping
        for row, label, column, source in lines:
            self._sum_year(ws, row, column, label, source, 5)
        ws.cell(12, 1, "КПН")
        ws.cell(12, 2, "ОПиУ, выплата в последнем месяце года уже входит в операционный поток")
        _paint(ws.cell(12, 1), fill=SECTION)
        _paint(ws.cell(12, 2))
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            ws.cell(12, 3 + index, f"='{PNL}'!{letter}15")
            _paint(ws.cell(12, 3 + index), MONEY, fill=SECTION)
        ws.cell(25, 1, "Остаток на конец")
        ws.cell(25, 2, "Остаток последнего месяца этого года")
        _paint(ws.cell(25, 1), bold=True, fill=SECTION)
        _paint(ws.cell(25, 2))
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            ws.cell(25, 3 + index, f"=LOOKUP(2,1/('{MON}'!{self.mon_span}={letter}$5),'{MON}'!$AC${self.mon_first}:$AC${self.mon_last})")
            _paint(ws.cell(25, 3 + index), MONEY, bold=True, fill=SECTION)
        # Admin cash in the engine is admin*(1+vat)+property. Monthly operating already includes that.
        # The annual "admin payment" line above is without VAT. Add a formula line that matches the cash definition.
        ws.cell(26, 1, "Административные с НДС и налогом на имущество")
        ws.cell(26, 2, "Адм. расходы × (1+НДС) + налог на имущество. Эта сумма входит в операционный поток.")
        _paint(ws.cell(26, 1))
        _paint(ws.cell(26, 2))
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            dep_letter = get_column_letter(6 + index)
            ws.cell(26, 3 + index, f"={letter}10*(1+VatRate)+'{DEP}'!{dep_letter}{self.dep_property}")
            _paint(ws.cell(26, 3 + index), MONEY)
        ws.cell(28, 1, f'=IF(COUNTIF(\'{MON}\'!$AD${self.mon_first}:$AD${self.mon_last},1)=0,"Помесячная проверка: остаток не уходит в минус.","Кассовый разрыв: "&TEXT(INDEX(\'{MON}\'!$B${self.mon_first}:$B${self.mon_last},MATCH(1,\'{MON}\'!$AD${self.mon_first}:$AD${self.mon_last},0)),"00")&"."&INDEX(\'{MON}\'!$A${self.mon_first}:$A${self.mon_last},MATCH(1,\'{MON}\'!$AD${self.mon_first}:$AD${self.mon_last},0)))')
        ws.cell(28, 1).font = BOLD
        ws.column_dimensions["A"].width = 55
        ws.column_dimensions["B"].width = 62

    def _analysis(self) -> None:
        ws = self.sheets[AN]
        _heading(
            ws,
            "10. Анализ эффективности",
            self.subtitle,
            "FCF = операционный поток + проценты × (1−T) + инвестиционный поток. Долг и дивиденды в FCF не входят. "
            "NPV, IRR, MIRR и сроки — формулы по этой строке.",
        )
        self._year_header(ws, 5)
        self._link_row(
            ws, 6, "FCF", "ДДС: операционный + проценты × (1−T) + инвестиционный",
            lambda letter, index: f"='{CF}'!{letter}15+'{CF}'!{letter}11*(1-TaxShield)+'{CF}'!{letter}17",
        )
        for index in range(self.n):
            letter = get_column_letter(3 + index)
            prev = get_column_letter(2 + index)
            if index == 0:
                ws.cell(7, 3, "=1")
            else:
                ws.cell(7, 3 + index, f"={prev}7/(1+WaccRate)")
            ws.cell(8, 3 + index, f"={letter}6*{letter}7")
            if index == 0:
                ws.cell(9, 3, f"={letter}6")
                ws.cell(10, 3, f"={letter}8")
            else:
                ws.cell(9, 3 + index, f"={prev}9+{letter}6")
                ws.cell(10, 3 + index, f"={prev}10+{letter}8")
            ws.cell(11, 3 + index, f'=IF(\'{CF}\'!{letter}11+\'{CF}\'!{letter}22<=1,NA(),(\'{CF}\'!{letter}15+\'{CF}\'!{letter}11)/(\'{CF}\'!{letter}11+\'{CF}\'!{letter}22))')
            _paint(ws.cell(7, 3 + index), "0.000", fill=SECTION)
            _paint(ws.cell(8, 3 + index), MONEY, fill=SECTION)
            _paint(ws.cell(9, 3 + index), MONEY)
            _paint(ws.cell(10, 3 + index), MONEY)
            _paint(ws.cell(11, 3 + index), "0.00")
        for row, label, source in (
            (7, "Коэффициент дисконтирования", "d(1)=1, d(t)=d(t−1)/(1+WACC)"),
            (8, "Дисконтированный поток", "FCF × d"),
            (9, "Накопленный FCF", "Для простого срока"),
            (10, "Накопленный DCF", "Для дисконтированного срока"),
            (11, "DSCR", "(операционный + проценты) / (проценты + тело)"),
        ):
            ws.cell(row, 1, label)
            ws.cell(row, 2, source)
            _paint(ws.cell(row, 1), bold=row in (6, 8))
            _paint(ws.cell(row, 2))
        last = get_column_letter(2 + self.n)
        fcf = f"C6:{last}6"
        dcf = f"C8:{last}8"
        cards = [
            (13, "NPV", f"=SUM(C8:{last}8)", MONEY, "Сумма дисконтированного потока"),
            (14, "IRR", f'=IFERROR(IRR({fcf}),"не считается")', PCT, "Ставка, при которой NPV = 0"),
            (15, "MIRR", f'=IFERROR(MIRR({fcf},WaccRate,WaccRate),"не считается")', PCT, "Ставка реинвестирования = WACC"),
            (16, "Инвестиции IC", f"=SUM('{CF}'!C16:{last}16)", MONEY, "Сумма CAPEX всех лет, включая замену"),
            (17, "P, NPV / инвестиции", f'=IF(B16=0,"не считается",B13/B16)', PCT, "IC — строка выше"),
            (18, "PI", f'=IF(ISNUMBER(B17),B17+1,"не считается")', "0.00", "P + 1"),
            (19, "Простой срок, лет", "=" + _payback(9, 6, self.n), "0.00", "От первого года плана, по накопленному FCF"),
            (20, "Дисконтированный срок, лет", "=" + _payback(10, 8, self.n), "0.00", "По накопленному DCF"),
            (21, "ARR", f'=IF(OR(B16=0,{self.n}<2),"не считается",AVERAGE(D6:{last}6)/B16)', PCT, "Средний FCF со второго года / IC"),
            (22, "Минимальный DSCR", f'=IFERROR(AGGREGATE(5,6,C11:{last}11),"не считается")', "0.00", "Пустые годы без долга не участвуют"),
            (23, "Минимальный остаток", f"=MIN('{MON}'!$AC${self.mon_first}:$AC${self.mon_last})", MONEY, "По всем месяцам горизонта"),
            (24, "WACC", "=WaccRate", PCT, "Лист предпосылок"),
        ]
        _header(ws, 12, ["Показатель", "Значение", "Как считается"])
        for row, label, formula, fmt, note in cards:
            ws.cell(row, 1, label)
            ws.cell(row, 2, formula)
            ws.cell(row, 3, note)
            _paint(ws.cell(row, 1), bold=True)
            _paint(ws.cell(row, 2), fmt, fill=SECTION)
            _paint(ws.cell(row, 3))
        ws.cell(26, 1, "Сценарии пересчитаны отдельно: это не ссылки на жёлтые ячейки, а повтор всего расчёта с другим вводом.").font = MUTED
        _header(ws, 27, ["Сценарий", "NPV", "IRR", "Мин. DSCR", "Мин. остаток", "Примечание"])
        for index, row in enumerate(self.result.scenarios):
            line = 28 + index
            values = [
                row["name"], row["npv"],
                row["irr"] if row["irr"] is not None else "не считается",
                row["min_dscr"] if row["min_dscr"] is not None else "—",
                row["min_cash"], row["note"],
            ]
            for column, value in enumerate(values, start=1):
                ws.cell(line, column, value)
                fmt = None
                if column in (2, 5):
                    fmt = MONEY
                elif column == 3 and isinstance(value, float):
                    fmt = PCT
                elif column == 4 and isinstance(value, float):
                    fmt = "0.00"
                _paint(ws.cell(line, column), fmt)
        check_row = 30 + len(self.result.scenarios)
        ws.cell(check_row, 1, "Проверки на момент выгрузки. Если поменять жёлтую ячейку, текст проверки сам не обновится — обновятся формулы выше.").font = MUTED
        _header(ws, check_row + 1, ["Проверка", "Уровень", "Результат", "Что поменять"])
        fills = {"ок": OK_FILL, "критично": BAD_FILL, "предупреждение": WARN_FILL, "информация": SECTION}
        for index, item in enumerate(self.result.checks):
            line = check_row + 2 + index
            for column, value in enumerate([item["name"], item["level"], item["text"], item["hint"]], start=1):
                ws.cell(line, column, value)
                _paint(ws.cell(line, column), fill=fills.get(item["level"]) if column == 2 else None)
            ws.row_dimensions[line].height = 32
        ws.column_dimensions["A"].width = 42
        ws.column_dimensions["B"].width = 28
        ws.column_dimensions["C"].width = 78
        ws.column_dimensions["F"].width = 55


def _payback(cum_row: int, flow_row: int, count: int) -> str:
    first = f"C{flow_row}"
    expr = f'IF({first}>=0,0,'
    for index in range(1, count):
        prev = f"{get_column_letter(2 + index)}{cum_row}"
        current = f"{get_column_letter(3 + index)}{cum_row}"
        amount = f"{get_column_letter(3 + index)}{flow_row}"
        expr += f'IF(AND({prev}<0,{current}>=0),IF({amount}=0,"не считается",{index}+ABS({prev})/{amount}),'
    expr += '"не окупается в горизонте прогноза"' + ")" * count
    return expr


def _heading(ws: Worksheet, title: str, subtitle: str, note: str) -> None:
    ws["A1"] = title
    ws["A1"].font = Font(size=16, bold=True, color="1A2233")
    ws["A2"] = subtitle
    ws["A2"].font = Font(size=10, color="6B7489")
    ws["A3"] = note
    ws["A3"].font = MUTED
    ws["A3"].alignment = Alignment(wrap_text=True, vertical="center")
    ws.row_dimensions[3].height = 48
    ws.freeze_panes = "A5"
    ws.sheet_view.showGridLines = False
    ws.sheet_view.zoomScale = 110
    ws.page_setup.orientation = "landscape"
    ws.page_setup.fitToPage = True
    ws.page_setup.fitToWidth = 1
    ws.page_setup.fitToHeight = 0
    ws.page_setup.paperSize = ws.PAPERSIZE_A4
    ws.page_margins.left = 0.4
    ws.page_margins.right = 0.4
    ws.oddFooter.right.text = "ФЭМ версия 2 · стр. &P"
    ws.print_title_rows = "1:3"


def _header(ws: Worksheet, row: int, labels: list) -> None:
    for column, label in enumerate(labels, start=1):
        cell = ws.cell(row, column, label)
        cell.font = HEADER_FONT
        cell.fill = HEADER_FILL
        cell.alignment = Alignment(wrap_text=True, horizontal="center", vertical="center")
        cell.border = BORDER
    ws.row_dimensions[row].height = 32


def _paint(cell, fmt: str | None = None, bold: bool = False, fill: PatternFill | None = None) -> None:
    cell.border = BORDER
    cell.alignment = Alignment(vertical="center", wrap_text=True)
    if fmt:
        cell.number_format = fmt
    if bold:
        cell.font = BOLD
    if fill:
        cell.fill = fill


def _label(ws: Worksheet, row: int, text: str) -> None:
    ws.cell(row, 1, text)
    _paint(ws.cell(row, 1))


def _input_row(ws: Worksheet, row: int, label: str, value, fmt: str | None, note: str) -> None:
    _label(ws, row, label)
    ws.cell(row, 2, value)
    _paint(ws.cell(row, 2), fmt, fill=INPUT if not (isinstance(value, str) and value.startswith("=")) else SECTION)
    ws.cell(row, 3, note)
    _paint(ws.cell(row, 3))
