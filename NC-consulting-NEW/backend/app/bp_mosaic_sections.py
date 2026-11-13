"""Разделы шаблона #006 по оглавлению банковского плана «Радуга здоровья».

Пункты 1.1–7.8 одни и те же для всех заявок. Меняются названия и наполнение пунктов про деньги:
кредит, инвестор, грант или инвестор вместе с кредитом.
"""

from app.bp_plan import (
    _block,
    _capital_risk,
    _markdown_blocks,
    _month,
    _num,
    _pct,
    _plain_number,
    _rate,
    _sales_risk,
    _years,
)
from app.bp_profile import clean as clean_profile
from app.bp_templates import money_title

YEARS_PER_TABLE = 5
FORM_GROUPS = {"smr": "smr", "overhaul": "overhaul", "ready_premises": "premises", "equipment": "equipment", "raw": "inventory"}
# Если в старом варианте нет ответа под новый ключ, берём текст универсального шаблона.
FALLBACK = {
    "goal": ("use",),
    "results": ("use",),
    "share": ("competitors",),
    "materials": ("operations",),
}
IMAGE_SECTIONS = {"company": 2, "offer": 4, "sales": 5, "costs": 6, "invest": 7}


def _n(value) -> str:
    amount = int(round(_num(value)))
    text = f"{abs(amount):,}".replace(",", " ")
    return f"−{text}" if amount < 0 else text


def _q(value) -> str:
    number = _num(value)
    if abs(number - round(number)) < 1e-9:
        return _n(number)
    return f"{number:,.1f}".replace(",", " ").replace(".", ",")


def _share(part: float, whole: float) -> str:
    if not whole:
        return "—"
    return f"{part / whole * 100:.0f}%"


def _ratio(value) -> str:
    if not isinstance(value, (int, float)):
        return "—"
    return f"{value * 100:.1f}".replace(".", ",") + "%"


def _period(first: str, last: str) -> str:
    a, b = _month(first), _month(last)
    if a == "не указано":
        return b if b != "не указано" else "—"
    if b == "не указано" or a == b:
        return a
    if first[:4] == last[:4]:
        return f"{a.split(' ')[0]}–{b}"
    return f"{a} – {b}"


def _row(results: dict, key: str, label: str) -> list[float] | None:
    for item in results.get(key) or []:
        if item["label"].startswith(label) and item.get("values") is not None:
            return item["values"]
    return None


class _Builder:
    def __init__(self, facts: dict, form: dict, answers: dict, results: dict | None, profile: dict, images: list[dict]):
        self.facts = facts
        self.form = form
        self.answers = answers or {}
        self.results = results or {}
        self.profile = clean_profile(profile)
        self.images = images or []
        self.code = facts["financing_code"]
        self.credit_on = self.code in (2, 4)
        self.investor_on = self.code in (1, 4)
        self.grant_on = self.code == 3
        self.cur = facts["currency"]
        self.tables = 0
        self.split = self._split()

    # ---------- общие куски ----------

    def money(self, value) -> str:
        return f"{_n(value)} {self.cur}"

    def cap(self, title: str) -> tuple:
        self.tables += 1
        return _block("cap", f"Таблица {self.tables} – {title}")

    def table(self, title: str, headers: list[str], rows: list[list[str]], widths: list[float] | None = None) -> list[tuple]:
        if not rows:
            return []
        return [self.cap(title), _block("table", headers, rows, widths)] if widths else [self.cap(title), _block("table", headers, rows)]

    def miss(self, label: str) -> tuple:
        return _block(
            "miss",
            f"Заполните «{label}» в карточке компании (раздел «Компании») или в блоке «Данные компании для банковского плана». "
            "Пункт обновится при следующем скачивании файла.",
        )

    def answer(self, key: str) -> list[tuple]:
        for name in (key, *FALLBACK.get(key, ())):
            text = str(self.answers.get(name) or "").strip()
            if text:
                return _markdown_blocks(text)
        return []

    def text(self, value: str) -> list[tuple]:
        return [_block("p", line.strip()) for line in str(value or "").split("\n") if line.strip()]

    def bullets(self, items: list[str]) -> list[tuple]:
        return [_block("rich", [(f"• {item}", False)]) for item in items if item]

    def years(self, keep=None) -> list[int]:
        years = list(self.results.get("years") or [])
        if keep is None:
            return years
        return [year for index, year in enumerate(years) if keep(index)]

    def year_tables(self, title: str, rows: list[tuple[str, list, str]], keep=None, first: str = "Показатель") -> list[tuple]:
        """Годовая таблица, разбитая по 5 лет, чтобы влезала в лист."""
        years = self.results.get("years") or []
        indexes = [index for index in range(len(years)) if keep is None or keep(index)]
        if not indexes or not rows:
            return []
        blocks = []
        for start in range(0, len(indexes), YEARS_PER_TABLE):
            part = indexes[start : start + YEARS_PER_TABLE]
            headers = [first, *[str(years[index]) for index in part]]
            body = []
            for label, values, kind in rows:
                if values is None:
                    body.append([label, *[""] * len(part)])
                    continue
                show = _ratio if kind == "pct" else (_q if kind == "count" else _n)
                body.append([label, *[show(values[index]) if index < len(values) else "—" for index in part]])
            width = 0.34 if len(part) > 2 else 0.5
            rest = (1 - width) / len(part)
            suffix = "" if start == 0 else " (продолжение)"
            blocks.extend(self.table(title + suffix, headers, body, [width, *[rest] * len(part)]))
        return blocks

    def active_years(self, values: list[float] | None):
        if not values:
            return None
        first = next((index for index, value in enumerate(values) if abs(value) > 0.5), None)
        if first is None:
            return None
        return lambda index: index >= first

    # ---------- деньги ----------

    def _split(self) -> list[dict]:
        """Статьи вложений по источникам так же, как их делит лист «Структура финансирования»."""
        form = self.form
        rows = []
        no_wc = self.facts.get("gov_wc") == "отсутствует"
        for item in self.facts["lines"]:
            external = item["attracted"]
            loan = min(item["credit"], external) if self.code == 4 else 0.0
            debt = external if self.code == 2 else loan
            investor = external - loan if self.code == 4 else (external if self.code == 1 else 0.0)
            grant = external if self.code == 3 else 0.0
            own = item["own"]
            if no_wc and item["kind"] == "wc" and debt > 0:
                own += debt
                debt = 0.0
            group = FORM_GROUPS.get(item["key"])
            assets = (form.get(group) or []) if group else []
            months = []
            for asset in assets:
                for field in ("pay_month", "commission_month"):
                    value = str(asset.get(field) or "")
                    if len(value) >= 7:
                        months.append(value[:7])
            when = _period(min(months), max(months)) if months else f"с {self.facts['start']}"
            rows.append(
                {
                    "label": item["label"],
                    "key": item["key"],
                    "total": own + debt + investor + grant,
                    "own": own,
                    "credit": debt,
                    "investor": investor,
                    "grant": grant,
                    "when": when,
                }
            )
        return rows

    def sums(self) -> dict:
        keys = ("total", "own", "credit", "investor", "grant")
        return {key: sum(row[key] for row in self.split) for key in keys}

    def source_columns(self) -> list[tuple[str, str]]:
        columns = []
        if self.credit_on:
            columns.append(("credit", "Заемные средства"))
        if self.investor_on:
            columns.append(("investor", "Средства инвестора"))
        if self.grant_on:
            columns.append(("grant", "Грант"))
        return columns

    def amount_title(self) -> str:
        return money_title("amount", self.code)

    def term_title(self) -> str:
        return money_title("term", self.code)

    def source_title(self) -> str:
        return money_title("source", self.code)

    def targets(self, key: str) -> str:
        names = [row["label"].lower() for row in self.split if row[key] > 0]
        return ", ".join(names) or "статьи проекта"

    # ---------- 1 ----------

    def part_terms(self) -> list:
        sums = self.sums()
        blocks = [_block("sub", self.amount_title())]
        if self.credit_on:
            blocks.append(_block("p", f"Сумма кредита – {self.money(sums['credit'])} – на цели инвестиций: {self.targets('credit')}."))
        if self.investor_on:
            blocks.append(_block("p", f"Сумма инвестиций – {self.money(sums['investor'])} – капитал инвестора на: {self.targets('investor')}. Это не заём."))
        if self.grant_on:
            blocks.append(_block("p", f"Сумма гранта – {self.money(sums['grant'])} – на: {self.targets('grant')}. Грант не возвращается и не является займом."))
        sources = [("Собственные средства", sums["own"])]
        for key, label in self.source_columns():
            sources.append((label, sums[key]))
        rows = [[label, _n(value), _share(value, sums["total"])] for label, value in sources if value]
        rows.append(["Итого стоимость проекта", _n(sums["total"]), "100%"])
        blocks.extend(self.table(f"Источники финансирования, {self.cur}", ["Источник", "Сумма", "Доля"], rows, [0.5, 0.3, 0.2]))

        blocks.append(_block("sub", "Цель финансирования"))
        columns = self.source_columns()
        headers = ["№", "Наименование", "Итоговая стоимость", *[label for _key, label in columns], "Собственные средства", "Сроки осуществления"]
        body = []
        for index, row in enumerate(self.split, start=1):
            body.append([str(index), row["label"], _n(row["total"]), *[_n(row[key]) for key, _label in columns], _n(row["own"]), row["when"]])
        body.append(["", "Итого", _n(sums["total"]), *[_n(sums[key]) for key, _label in columns], _n(sums["own"]), ""])
        middle = len(columns) + 2
        name = 0.3 if middle <= 3 else 0.22
        widths = [0.05, name, *[(0.81 - name) / middle] * middle, 0.14]
        blocks.extend(self.table(f"Структура финансирования, {self.cur}", headers, body, widths))
        blocks.extend(self.answer("goal"))

        blocks.append(_block("sub", self.term_title()))
        blocks.append(_block("p", self.timeline()))
        if self.credit_on:
            blocks.extend(self.credit_terms())
        if self.investor_on:
            blocks.extend(self.investor_terms())
        if self.grant_on:
            blocks.append(_block("p", f"Грант осваивается в сроки реализации проекта по статьям таблицы «Структура финансирования». Софинансирование собственными средствами – {self.money(sums['own'])} ({_share(sums['own'], sums['total'])})."))

        blocks.append(_block("sub", self.source_title()))
        blocks.extend(self.repayment())
        return blocks

    def timeline(self) -> str:
        capex = self.results.get("capex") or []
        stages = []
        for item in capex:
            stages.append(f"{item['name'].lower()} – {_period(item['paid'], item['started'])}")
        first = min((item["paid"] for item in capex if item["paid"]), default="")
        last = max((item["started"] for item in capex if item["started"]), default="")
        span = _period(first, last) if first else f"{self.facts['start']} – {self.facts['sales_start']}"
        tail = f" ({'; '.join(stages)})" if stages else ""
        return f"Сроки реализации проекта: {span}{tail}. Старт продаж – {self.facts['sales_start']}, выход на полный объём – {self.facts['full_volume']}. Горизонт модели – {self.facts['start']} – {self.facts['end']}."

    def credit_terms(self) -> list:
        facts = self.facts
        method = "аннуитет" if facts["loan_annuity"] else "дифференцированные платежи"
        blocks = [_block("p", f"Предварительный график погашения по кредиту, {method}.")]
        items = [f"Сумма – {self.money(self.sums()['credit'])}", f"Срок кредита – {facts['loan_term']} мес."]
        if facts.get("loan_bank_rate"):
            line = f"Ставка вознаграждения банка – {_pct(facts['loan_bank_rate'])} годовых"
            if facts.get("loan_subsidy_rate"):
                program = f" в рамках программы «{facts['gov_program'].strip('«»')}»" if facts.get("gov_program") else ""
                term = f" на {facts['loan_subsidy_term']} мес." if facts.get("loan_subsidy_term") else ""
                line += f"; с учётом субсидирования {_pct(facts['loan_subsidy_rate'])}{program} ставка для заёмщика – {_pct(facts['loan_rate'])}{term}"
            items.append(line)
        else:
            items.append(f"Ставка вознаграждения – {_pct(facts['loan_rate'])} годовых")
        if facts["loan_grace"]:
            items.append(f"Льготный период по основному долгу – {facts['loan_grace']} мес.")
        if facts.get("gov_program") and not facts.get("loan_subsidy_rate"):
            items.append(f"Госпрограмма – {facts['gov_program']}")
        blocks.extend(self.bullets(items))
        for schedule in self.results.get("schedules") or []:
            subsidy = any(row["subsidy"] for row in schedule["rows"])
            headers = ["№", "Дата", "Взнос", "Основной долг", "Проценты", "Сальдо"] + (["Субсидия"] if subsidy else [])
            body = []
            for row in schedule["rows"]:
                line = [str(row["n"]), _month(row["date"]), _n(row["payment"]), _n(row["principal"]), _n(row["interest"]), _n(row["balance"])]
                if subsidy:
                    line.append(_n(row["subsidy"]))
                body.append(line)
            total = schedule.get("total")
            if total:
                body.append(["", "Итого", _n(total[0]), _n(total[1]), _n(total[2]), "", *([_n(total[5])] if subsidy else [])])
            count = len(headers) - 2
            title = "График погашения" + (f" – {schedule['title']}" if schedule.get("title") else "") + f", {self.cur}"
            blocks.extend(self.table(title, headers, body, [0.07, 0.17, *[0.76 / count] * count]))
        if not self.results.get("schedules"):
            blocks.append(_block("miss", "Помесячный график появится, когда расчёт модели доступен."))
        collateral = self.profile["collateral"]
        if collateral:
            rows = [[str(index), row["name"], row["owner"] or "—", row["value"] or "—"] for index, row in enumerate(collateral, start=1)]
            blocks.extend(self.table("Залоговое обеспечение", ["№", "Наименование объекта", "Залогодатель", "Рыночная стоимость"], rows, [0.07, 0.45, 0.26, 0.22]))
        else:
            blocks.append(self.cap("Залоговое обеспечение"))
            blocks.append(self.miss("Залоговое обеспечение"))
        return blocks

    def investor_terms(self) -> list:
        facts = self.facts
        info = self.results.get("investor") or {}
        rows = [
            ["Средства инвестора", self.money(self.sums()["investor"])],
            ["Срок участия инвестора", f"{facts['investor_years']} лет"],
            ["Доля распределяемой прибыли инвестору", _pct(facts["dividend"])],
        ]
        if isinstance(info.get("rate"), (int, float)):
            rows.append(["Требуемая доходность инвестора", _ratio(info["rate"])])
        for key, label in (("dividends", "Дивиденды за горизонт модели"), ("exit", "Выход инвестора")):
            if isinstance(info.get(key), (int, float)):
                rows.append([label, self.money(info[key])])
        return [
            _block("p", "Инвестор входит капиталом и получает долю распределяемой прибыли. Срок участия – это не график возврата тела вложений."),
            *self.table("Условия участия инвестора", ["Условие", "Значение"], rows, [0.55, 0.45]),
        ]

    def repayment(self) -> list:
        names = ", ".join(f"«{item['name']}»" for item in self.facts["products"])
        products = f"по позициям {names}" if names else "от основной деятельности"
        blocks = []
        if self.credit_on:
            blocks.append(_block("p", f"Источником погашения кредита будет являться доход от текущей деятельности компании: выручка {products}."))
        if self.investor_on:
            blocks.append(_block("p", f"Выплаты инвестору производятся из чистой прибыли проекта: {_pct(self.facts['dividend'])} распределяемой прибыли в течение {self.facts['investor_years']} лет."))
        if self.grant_on:
            blocks.append(_block("p", f"Грант не возвращается. Текущие расходы после освоения гранта покрываются выручкой {products}."))
        if not self.results:
            return blocks
        rows = [("Операционный денежный поток", _row(self.results, "cash_rows", "Итого движение денег по операц"), "money")]
        if self.credit_on:
            rows.append(("Выплата вознаграждения", _row(self.results, "cash_rows", "Выплата вознаграждений"), "money"))
            rows.append(("Выплата основного долга", _row(self.results, "cash_rows", "Выплата основного долга"), "money"))
        if self.investor_on:
            rows.append(("Чистая прибыль", _row(self.results, "pnl_rows", "Чистая прибыль"), "money"))
            rows.append(("Дивиденды инвестору", _row(self.results, "cash_rows", "Дивиденды инвестору"), "money"))
        rows.append(("Денежные средства на конец года", _row(self.results, "cash_rows", "Денежные средства на конец"), "money"))
        rows = [row for row in rows if row[1] is not None]
        title = "Покрытие обслуживания долга" if self.credit_on else ("Выплаты инвестору" if self.investor_on else "Денежный поток после гранта")
        blocks.extend(self.year_tables(f"{title}, {self.cur}", rows))
        if self.credit_on and isinstance(self.results.get("dscr"), (int, float)):
            blocks.append(_block("p", f"Минимальный коэффициент покрытия долга (DSCR) по годам – {_plain_number(self.results['dscr'])}."))
        deficit = self.results.get("deficit")
        if isinstance(deficit, (int, float)) and deficit > 0.5:
            blocks.append(_block("warn", f"Модель показывает кассовый разрыв до {self.money(deficit)}. Его нужно закрыть до подачи заявки: собственными средствами, оборотным займом или сдвигом сроков."))
        return blocks

    # ---------- 2 ----------

    def part_company(self) -> list:
        profile = self.profile
        facts = self.facts
        blocks = [_block("sub", "Полное наименование хозяйствующего субъекта.")]
        if profile["company_name"]:
            line = f"Заявителем является {profile['company_name']}"
            if profile["bin"]:
                line += f" (БИН {profile['bin']})"
            blocks.append(_block("p", line + "."))
            if profile["director"]:
                blocks.append(_block("p", f"Руководитель – {profile['director']}."))
            if profile["address"]:
                blocks.append(_block("p", f"Адрес: {profile['address']}."))
            blocks.append(_block("p", f"Организационно-правовая форма – {facts['legal']}. Налоговый режим – {facts['tax']}. Плательщик НДС – {facts['nds']}."))
        else:
            blocks.append(self.miss("Полное наименование"))
        blocks.append(_block("sub", "Дата образования, перерегистрации и т.д. Свидетельство о регистрации."))
        if profile["registered"] or profile["registration_doc"]:
            if profile["registered"]:
                blocks.append(_block("p", f"Дата первичной регистрации: {profile['registered']}."))
            blocks.extend(self.text(profile["registration_doc"]))
        else:
            blocks.append(self.miss("Дата первичной регистрации"))
        blocks.append(_block("sub", "Основные собственники компании."))
        blocks.extend(self.text(profile["owners"]) or [self.miss("Собственники и доли")])
        blocks.append(_block("sub", "Первый руководитель."))
        blocks.append(_block("p", f"Первый руководитель – {profile['director']}.") if profile["director"] else self.miss("Первый руководитель"))
        blocks.append(_block("sub", "Основные виды деятельности. Описание деятельности компании."))
        if profile["activity"]:
            blocks.append(_block("p", f"Основной вид деятельности компании – {profile['activity']}."))
        blocks.extend(self.answer("company") or self.text(profile["about"]) or [_block("p", "Деятельность проекта: " + (", ".join(item["name"] for item in facts["products"]) or "—") + ".")])
        blocks.append(_block("sub", "Опыт компании в реализации подобных проектов (подробное описание)."))
        blocks.extend(self.text(profile["experience"]) or self.answer("experience") or [self.miss("Опыт в подобных проектах")])
        blocks.append(_block("sub", "Численность работников компании."))
        blocks.extend(self.headcount())
        blocks.append(_block("sub", "Характеристика управленческого персонала компании (квалификация и опыт работы)."))
        managers = profile["managers"]
        if managers:
            blocks.append(_block("p", "Информация о ключевом менеджменте:"))
            rows = [[row["name"], row["position"] or "—", row["education"] or "—", row["experience"] or "—"] for row in managers]
            blocks.extend(self.table("Характеристика управленческого персонала", ["ФИО", "Должность", "Образование, квалификация", "Общий опыт работы"], rows, [0.24, 0.2, 0.3, 0.26]))
        else:
            blocks.append(self.miss("Менеджмент"))
        blocks.append(_block("sub", "Деловая репутация компании."))
        blocks.extend(self.text(profile["reputation"]) or self.answer("reputation") or [self.miss("Деловая репутация")])
        blocks.append(_block("sub", "Кредитная история"))
        blocks.extend(self.text(profile["credit_history"]) or [self.miss("Кредитная история")])
        blocks.append(_block("sub", "Фактические финансовые показатели"))
        turnover = profile["turnover"]
        if turnover:
            rows = [[row["period"] or "—", row["amount"] or "—"] for row in turnover]
            blocks.extend(self.table(f"Фактический оборот, {self.cur}", ["Период", "Оборот"], rows, [0.55, 0.45]))
        else:
            blocks.append(self.miss("Фактические обороты"))
        return blocks

    def headcount(self) -> list:
        staff = self.facts["staff"]
        total = sum(item["count"] for item in staff)
        blocks = []
        if self.profile["staff_now"]:
            blocks.append(_block("p", f"Численность сейчас: {self.profile['staff_now']}."))
        payroll = self.results.get("payroll") or {}
        years = self.results.get("years") or []
        counts = {}
        for person in payroll.get("people") or []:
            for index, value in enumerate(person["count"]):
                fund = person["fund"][index] if index < len(person["fund"]) else 0
                if fund:
                    counts[index] = counts.get(index, 0) + value
        if counts:
            first = min(counts)
            last = max(counts)
            text = f"По штатному расписанию проекта численность составит {_q(counts[first])} чел. в {years[first]} году"
            if counts[last] != counts[first]:
                text += f" и {_q(counts[last])} чел. в {years[last]} году"
            blocks.append(_block("p", text + "."))
        else:
            blocks.append(_block("p", f"По штатному расписанию проекта численность – {_q(total)} чел."))
        rows = [[item["name"], _q(item["count"]), _n(item["salary"])] for item in staff]
        if rows:
            rows.append(["Итого, фонд окладов в месяц", _q(total), _n(self.facts["payroll"])])
            blocks.extend(self.table(f"Штатное расписание, {self.cur}", ["Должность", "Численность", "Оклад в месяц"], rows, [0.5, 0.2, 0.3]))
        return blocks

    # ---------- 3 ----------

    def part_risks(self) -> list:
        blocks = [_block("sub", "Институциональные риски.")]
        blocks.extend(self.answer("risks"))
        blocks.append(_block("p", "Для оценки рисков применяется количественный метод: вероятность наступления события и его влияние на цель проекта оцениваются по шкале от 1 до 5, коэффициент риска равен их произведению."))
        blocks.extend(self.table(
            "Оценка вероятности возникновения рисков",
            ["Балл", "Вероятность", "Значение, %"],
            [["1", "Очень низкая", "до 10"], ["2", "Низкая", "10–30"], ["3", "Средняя", "30–50"], ["4", "Высокая", "50–70"], ["5", "Очень высокая", "более 70"]],
            [0.15, 0.5, 0.35],
        ))
        blocks.extend(self.table(
            "Влияние рисков на достижение целей",
            ["Балл", "Значимость", "Влияние на достижение целей", "Значение, %"],
            [
                ["1", "Незначительная", "Цели достигаются без отклонений", "до 10"],
                ["2", "Умеренная", "Небольшое отклонение сроков или затрат", "10–30"],
                ["3", "Существенная", "Заметное отклонение показателей", "30–50"],
                ["4", "Высокая", "Под угрозой отдельные цели", "50–70"],
                ["5", "Критическая", "Цели проекта не достигаются", "более 70"],
            ],
            [0.1, 0.22, 0.48, 0.2],
        ))
        financial = {
            1: ("Финансовый", "Снижение прибыли и дивидендов инвестору", "Отсрочка выплат, спор о доле", "Резерв денег, контроль бюджета, отчётность инвестору"),
            2: ("Финансовый", "Нехватка денег на платежи по кредиту", "Просрочка, штрафы, обращение взыскания на залог", "Резерв на 2–3 платежа, контроль дебиторки, график закупок"),
            3: ("Финансовый", "Нарушение условий гранта", "Возврат гранта", "Целевое расходование, отчётность по статьям"),
            4: ("Финансовый", "Нехватка денег на кредит и выплаты инвестору", "Просрочка, конфликт источников", "Приоритет платежей по кредиту, резерв, отчётность"),
        }.get(self.code, ("Финансовый", "Нехватка денег", "Срыв графика", "Резерв денег"))
        rows = [
            list(financial),
            ["Технический", "Поломка оборудования", "Простой, потеря выручки", "Сервисный договор, плановое обслуживание"],
            ["Юридический", "Нарушение обязательств контрагентами", "Задержки, дополнительные затраты", "Проверка контрагентов, договоры с ответственностью сторон"],
            ["Организационный", "Нехватка и текучесть персонала", "Снижение качества и объёма", "Подбор, обучение, мотивация"],
            ["Коммерческий", "Снижение спроса, рост конкуренции", "Выручка ниже плана", "Маркетинг, программы лояльности, контроль цен"],
            ["Отраслевой", "Изменение регулирования отрасли", "Новые требования и затраты", "Мониторинг законодательства"],
            ["Экологический", "Чрезвычайные ситуации", "Ущерб имуществу, простой", "Страхование, пожарная безопасность"],
            ["Институциональный", "Изменение налогов и правил", "Рост налоговой нагрузки", "Мониторинг законодательства, резерв"],
        ]
        blocks.extend(self.table("Риски проекта и меры по минимизации", ["Вид риска", "Содержание риска", "Возможные последствия", "Меры по минимизации"], rows, [0.18, 0.27, 0.27, 0.28]))
        blocks.append(_block("p", _capital_risk(self.facts)))
        return blocks

    # ---------- 4 ----------

    def part_offer(self) -> list:
        facts = self.facts
        blocks = [_block("sub", "Описание предлагаемых товаров (услуг). Необходимо указать все базовые характеристики, соответствия ГОСТам, международным стандартам и т.д.")]
        blocks.extend(self.answer("product"))
        rows = [[item["name"], item["unit"], _q(item["volume"]), _n(item["price"]), _n(item["revenue"])] for item in facts["products"]]
        blocks.extend(self.table(f"Продуктовая линейка при 100% загрузке, в месяц, {self.cur}", ["Позиция", "Ед.", "Объём", "Цена", "Выручка"], rows, [0.36, 0.1, 0.16, 0.18, 0.2]))
        blocks.append(_block("sub", "Наличие специальных ограничений и условий на продажу товаров (лицензии, квоты, таможенные ограничения, государственное регулирование и т.п.)."))
        if self.profile["licenses"]:
            blocks.extend(self.text(self.profile["licenses"]))
        blocks.extend(self.answer("licenses") or ([] if self.profile["licenses"] else [self.miss("Лицензии и разрешения")]))
        blocks.append(_block("sub", "Маркетинговая стратегия:"))
        blocks.extend(self.answer("marketing"))
        if facts["marketing_rows"]:
            rows = [[item["name"], _n(item["value"]), _n(item["value"] * 12)] for item in facts["marketing_rows"]]
            blocks.extend(self.table(f"Маркетинговый бюджет, {self.cur}", ["Мероприятие", "В месяц", "В год"], rows, [0.5, 0.25, 0.25]))
        blocks.append(_block("sub", "Емкость и динамика рынка"))
        blocks.extend(self.answer("market"))
        blocks.append(_block("sub", "Доля рынка, принадлежащая компании (при наличии такой информации)."))
        blocks.extend(self.answer("share"))
        competitors = self.profile["competitors"]
        if competitors:
            rows = [[str(index), row["name"], row["format"] or "—", row["strong"] or "—", row["weak"] or "—"] for index, row in enumerate(competitors, start=1)]
            blocks.extend(self.table("Основные конкуренты", ["№", "Конкурент", "Формат", "Сильные стороны", "Слабые стороны"], rows, [0.06, 0.22, 0.2, 0.26, 0.26]))
        blocks.append(_block("sub", "Политика сбыта (основные потребители, опыт работы с потребителями, планируемая цена продажи продукции/оказания услуг и т.п.)."))
        blocks.extend(self.answer("sales_policy"))
        income = self.results.get("income") or {}
        years = self.results.get("years") or []
        prices = []
        for product in income.get("products") or []:
            values = [value for value in product["price"] if value]
            if values:
                prices.append([product["name"], _n(values[0]), _n(values[-1])])
        if prices and years:
            blocks.extend(self.table(f"Планируемые цены, {self.cur}", ["Позиция", f"Цена {years[0]}", f"Цена {years[-1]}"], prices, [0.5, 0.25, 0.25]))
        return blocks

    # ---------- 5 ----------

    def part_sales(self) -> list:
        facts = self.facts
        blocks = [_block("sub", "План доходов")]
        blocks.append(_block("p", "Доходная часть проекта формируется за счёт:"))
        blocks.extend(self.bullets([item["name"] for item in facts["products"]]))
        income = self.results.get("income") or {}
        total = income.get("total")
        keep = self.active_years(total)
        if income.get("products") and keep:
            rows = []
            if income.get("months"):
                rows.append(("Месяцев продаж", income["months"], "count"))
            if income.get("load"):
                rows.append(("Средняя загрузка", income["load"], "pct"))
            for product in income["products"]:
                rows.append((f"{product['name']}: количество, {product['unit'] or 'ед.'}", product["count"], "count"))
                rows.append((f"{product['name']}: цена", product["price"], "money"))
                rows.append((f"{product['name']}: выручка", product["revenue"], "money"))
            rows.append(("Итого выручка", total, "money"))
            blocks.extend(self.year_tables(f"План доходов по проекту по годам, {self.cur}", rows, keep))
            blocks.append(_block("p", "Источник: финансовая модель, лист «План доходов»."))
            years = self.results["years"]
            first = next(index for index in range(len(years)) if keep(index))
            months = int(income["months"][first]) if income.get("months") else 0
            line = f"В {years[first]} году выручка рассчитана за {months} мес. работы" if months else f"Продажи начинаются в {years[first]} году"
            line += f" (старт продаж – {facts['sales_start']}), полный объём – с {facts['full_volume']}."
            product = income["products"][0]
            if len(product["price"]) > first + 1 and product["price"][first]:
                growth = product["price"][first + 1] / product["price"][first] - 1
                if growth > 0.001:
                    line += f" Цены индексируются на {growth * 100:.1f}% в год.".replace(".", ",", 1)
            blocks.append(_block("p", line))
        else:
            rows = [[year, f"{value:g}%"] for year, value in facts["capacity"]]
            blocks.extend(self.table("Загрузка по годам", ["Год", "Загрузка"], rows, [0.5, 0.5]))
        blocks.append(_block("sub", "Контракты и оферты, подтверждающие сбыт (при наличии)."))
        if self.profile["contracts"]:
            blocks.extend(self.text(self.profile["contracts"]))
        else:
            blocks.extend(self.answer("contracts") or [self.miss("Контракты и оферты")])
        blocks.append(_block("sub", "Сбытовые и отраслевые риски."))
        blocks.extend(self.answer("sales_risks"))
        blocks.append(_block("p", _sales_risk(facts, self.money)))
        return blocks

    # ---------- 6 ----------

    def part_costs(self) -> list:
        facts = self.facts
        form = self.form
        blocks = [_block("sub", "ПОТРЕБЛЯЕМЫЕ И ПРИОБРЕТАЕМЫЕ МАТЕРИАЛЫ, СЫРЬЁ")]
        blocks.extend(self.answer("materials"))
        purchases = []
        for row in form.get("variable_costs") or []:
            product = str(row.get("product") or "").strip()
            kind = str(row.get("cost_type") or "").strip()
            if product or kind:
                purchases.append([product or "—", kind or "—", _n(_num(row.get("price")))])
        for row in form.get("variable_other") or []:
            name = str(row.get("name") or "").strip()
            if name:
                purchases.append([name, "прочая закупка", _n(_num(row.get("value")))])
        blocks.extend(self.table(f"Закупки на единицу продукции, {self.cur}", ["Позиция", "Вид затрат", "Сумма"], purchases, [0.45, 0.3, 0.25]))
        admin = self.results.get("admin") or []
        totals = next((row["values"] for row in admin if row["total"]), None)
        keep = self.active_years(totals)
        if admin and keep:
            rows = [(row["label"], row["values"], "money") for row in admin if not row["total"]]
            rows.append(("Итого", totals, "money"))
            blocks.extend(self.year_tables(f"План расходов проекта по годам, {self.cur}", rows, keep, "Наименование"))
            blocks.append(_block("p", "Источник: финансовая модель, лист «Адм расходы»."))
        elif facts["admin_rows"] or facts["rent_rows"]:
            rows = [[item["name"], _n(item["value"])] for item in facts["rent_rows"] + facts["admin_rows"]]
            blocks.extend(self.table(f"Постоянные расходы в месяц, {self.cur}", ["Статья", "В месяц"], rows, [0.6, 0.4]))
        blocks.append(_block("sub", "Основные поставщики, опыт работы с поставщиками."))
        if self.profile["suppliers"]:
            blocks.extend(self.text(self.profile["suppliers"]))
        blocks.extend(self.answer("suppliers"))
        blocks.append(_block("sub", "Условия поставки (цены, сроки, условия поставки, ответственность сторон)."))
        blocks.extend(self.answer("supply_terms"))
        blocks.append(_block("sub", "Потребляемые объёмы."))
        blocks.append(_block("p", "Объёмы потребления формируются от количества проданных единиц и оказанных услуг, режима работы и загрузки."))
        income = self.results.get("income") or {}
        cogs = _row(self.results, "pnl_rows", "Себестоимость")
        keep = self.active_years(income.get("total"))
        if income.get("products") and keep:
            rows = [(f"{product['name']}, {product['unit'] or 'ед.'}", product["count"], "count") for product in income["products"]]
            if cogs:
                rows.append((f"Себестоимость закупок, {self.cur}", cogs, "money"))
            blocks.extend(self.year_tables("Потребляемые объёмы по годам", rows, keep))
        wc = facts["wc_months"]
        blocks.append(_block("sub", "Политика закупок."))
        blocks.extend(self.answer("procurement"))
        rows = [["Сырьё и товары", f"{wc['raw']} мес."], ["Реклама", f"{wc['advertising']} мес."], ["Заработная плата", f"{wc['wages']} мес."], ["Аренда", f"{wc['rental']} мес."], ["Прочие", f"{wc['other']} мес."]]
        blocks.extend(self.table("Запас оборотных средств", ["Статья", "Запас"], rows, [0.6, 0.4]))
        return blocks

    # ---------- 7 ----------

    def part_invest(self) -> list:
        facts = self.facts
        sums = self.sums()
        blocks = [_block("sub", "Цель финансирования. Ожидаемые результаты от финансирования")]
        parts = [f"{label.lower()} – {self.money(sums[key])} ({_share(sums[key], sums['total'])})" for key, label in self.source_columns() if sums[key]]
        if sums["own"]:
            parts.append(f"собственные средства – {self.money(sums['own'])} ({_share(sums['own'], sums['total'])})")
        blocks.append(_block("p", f"Общая стоимость проекта составляет {self.money(sums['total'])}, в том числе " + ", ".join(parts) + "."))
        blocks.append(_block("p", "Средства проекта будут направлены на следующие цели:"))
        items = []
        for row in self.split:
            sources = [f"{label.lower()} – {self.money(row[key])}" for key, label in self.source_columns() if row[key]]
            if row["own"]:
                sources.append(f"собственные средства – {self.money(row['own'])}")
            items.append(f"{row['label']} – {self.money(row['total'])} ({'; '.join(sources)}), {row['when']}")
        blocks.extend(self.bullets(items))
        blocks.extend(self.answer("results"))
        blocks.append(_block("sub", "Риски, связанные с возможным срывом приобретения сырья и материалов."))
        blocks.extend(self.answer("supply_risks"))
        blocks.append(_block("sub", "Процесс инвестируемого производства"))
        capex = self.results.get("capex") or []
        if capex:
            rows = [
                [str(index), item["name"], item["category"] or "—", _n(item["amount"]), _month(item["paid"]), _month(item["started"]), _q(item["life"]) if item["life"] else "—"]
                for index, item in enumerate(capex, start=1)
            ]
            rows.append(["", "Итого", "", _n(sum(item["amount"] for item in capex)), "", "", ""])
            blocks.extend(self.table(f"План финансирования, {self.cur}", ["№", "Наименование", "Категория", "Сумма", "Оплата", "Ввод", "Срок службы, лет"], rows, [0.05, 0.27, 0.14, 0.15, 0.14, 0.14, 0.11]))
        elif facts["assets"]:
            rows = [[item["name"], item["group"], _n(item["amount"]), _q(item["life"])] for item in facts["assets"]]
            blocks.extend(self.table(f"План финансирования, {self.cur}", ["Наименование", "Группа", "Сумма", "Срок службы, лет"], rows, [0.36, 0.3, 0.18, 0.16]))
        blocks.extend(self.answer("operations"))
        blocks.append(_block("sub", "Анализ инвестиций (финансовые данные)"))
        blocks.extend(self.analysis())
        blocks.append(_block("sub", "Административный и производственный персонал, в том числе уровень квалификации такого персонала."))
        blocks.extend(self.answer("staff"))
        blocks.extend(self.staff_tables())
        blocks.append(_block("sub", "Планируемые изменения профиля объекта/технологии производства."))
        blocks.extend(self.answer("profile_change") or [_block("p", "Проектом не предусматривается изменение профиля деятельности.")])
        blocks.append(_block("sub", "Планируемые финансовые результаты."))
        blocks.extend(self.results_text())
        blocks.append(_block("sub", "Отчет о движении денежных средств для финансового планирования на период планирования проекта."))
        blocks.extend(self.cash_text())
        return blocks

    def analysis(self) -> list:
        results = self.results
        blocks = [
            _block("p", "Привлекательность проекта оценивается по интегральным показателям, рассчитанным на основе дисконтированных денежных потоков:"),
            *self.bullets([
                "чистый приведённый доход – NPV (Net Present Value);",
                "внутренняя норма доходности – IRR (Internal Rate of Return);",
                "индекс рентабельности – PI (Profitability Index);",
                "модифицированная внутренняя норма доходности – MIRR;",
                "простой и дисконтированный срок окупаемости – PP и DPP.",
            ]),
        ]
        if not results:
            return blocks + [_block("miss", "Показатели появятся, когда расчёт модели доступен.")]
        rows = [
            ["Ставка дисконтирования (WACC)", _ratio(results.get("wacc"))],
            ["Чистая приведённая стоимость (NPV)", self.money(results["npv"]) if isinstance(results.get("npv"), (int, float)) else "—"],
            ["Внутренняя норма доходности (IRR)", _rate(results.get("irr"))],
            ["Модифицированная IRR (MIRR)", _rate(results.get("mirr"))],
            ["Индекс рентабельности (PI)", _plain_number(results.get("pi"))],
            ["Простой срок окупаемости", _years(results.get("payback"))],
            ["Дисконтированный срок окупаемости", _years(results.get("discounted"))],
        ]
        if self.credit_on:
            rows.append(["Минимальный DSCR", _plain_number(results.get("dscr"))])
        blocks.extend(self.table("Основные финансовые показатели", ["Финансовые показатели эффективности", "Значение"], rows, [0.6, 0.4]))
        info = results.get("investor") or {}
        if self.investor_on and isinstance(info.get("npv"), (int, float)):
            rows = [
                ["NPV инвестора", self.money(info["npv"])],
                ["IRR инвестора", _rate(info.get("irr"))],
                ["PI инвестора", _plain_number(info.get("pi"))],
                ["Срок окупаемости для инвестора", _years(info.get("payback"))],
            ]
            blocks.extend(self.table("Показатели для инвестора", ["Показатель", "Значение"], rows, [0.6, 0.4]))
        npv = results.get("npv")
        if isinstance(npv, (int, float)):
            if npv > 0:
                blocks.append(_block("p", f"NPV положительный: проект окупает вложения с учётом ставки дисконтирования {_ratio(results.get('wacc'))} в горизонте модели."))
            else:
                blocks.append(_block("warn", "NPV отрицательный: при выбранной ставке дисконтирования проект не окупает вложения в горизонте модели."))
        return blocks

    def staff_tables(self) -> list:
        payroll = self.results.get("payroll") or {}
        people = payroll.get("people") or []
        fot = next((row["values"] for row in payroll.get("totals") or [] if row["label"] == "ФОТ"), None)
        keep = self.active_years(fot)
        if not people or not keep:
            return []
        rows = [(f"{person['name']}, чел.", person["count"], "count") for person in people]
        rows.append(("ФОТ", fot, "money"))
        full = next((row["values"] for row in payroll["totals"] if row["label"].startswith("ФОТ с отчислениями")), None)
        if full:
            rows.append(("ФОТ с отчислениями работодателя", full, "money"))
        years = self.results["years"]
        first = next(index for index in range(len(years)) if keep(index))
        heads = sum(person["count"][first] for person in people)
        blocks = [_block("p", f"Общая численность персонала – {_q(heads)} чел. в {years[first]} году. Фонд оплаты труда в {years[first]} году – {self.money(fot[first])}.")]
        blocks.extend(self.year_tables(f"Численность и фонд оплаты труда, {self.cur}", rows, keep))
        return blocks

    def results_text(self) -> list:
        results = self.results
        revenue = _row(results, "pnl_rows", "Выручка итого") or _row(results, "pnl", "Выручка")
        profit = _row(results, "pnl_rows", "Чистая прибыль") or _row(results, "pnl", "Чистая прибыль")
        if not revenue or not profit:
            return [_block("miss", "Финансовые результаты появятся, когда расчёт модели доступен.")]
        years = results["years"]
        keep = self.active_years(revenue)
        blocks = [_block("p", "Прогнозный отчет о прибылях и убытках приведен в Приложении 3 к настоящему бизнес-плану.")]
        if keep:
            index = [i for i in range(len(years)) if keep(i)]
            first, last = index[0], index[-1]
            second = index[1] if len(index) > 1 else first
            line = f"Выручка в {years[first]} году – {self.money(revenue[first])}"
            if second != first:
                line += f", в {years[second]} году – {self.money(revenue[second])}"
            line += f", в {years[last]} году – {self.money(revenue[last])}."
            blocks.append(_block("p", line))
            line = f"Чистая прибыль в {years[first]} году – {self.money(profit[first])}"
            if second != first:
                line += f", в {years[second]} году – {self.money(profit[second])}"
            line += f", в {years[last]} году – {self.money(profit[last])}."
            margin = _row(results, "pnl_rows", "Рентабельность продаж")
            if margin:
                line += f" Рентабельность продаж – {_ratio(margin[second])} в {years[second]} году и {_ratio(margin[last])} в {years[last]} году."
            blocks.append(_block("p", line))
        blocks.append(_block("p", f"Расчёт выполнен по годам. Налоговый режим – {self.facts['tax']}."))
        rows = [
            ("Выручка", revenue, "money"),
            ("Себестоимость", _row(results, "pnl_rows", "Себестоимость"), "money"),
            ("Валовая прибыль", _row(results, "pnl_rows", "Валовая прибыль"), "money"),
            ("EBITDA", _row(results, "pnl_rows", "EBITDA"), "money"),
            ("Чистая прибыль", profit, "money"),
            ("Рентабельность продаж", _row(results, "pnl_rows", "Рентабельность продаж"), "pct"),
        ]
        blocks.extend(self.year_tables(f"Планируемые финансовые результаты, {self.cur}", [row for row in rows if row[1] is not None], keep))
        return blocks

    def cash_text(self) -> list:
        results = self.results
        operating = _row(results, "cash_rows", "Итого движение денег по операц")
        ending = _row(results, "cash_rows", "Денежные средства на конец")
        if not operating or not ending:
            return [_block("miss", "Движение денег появится, когда расчёт модели доступен.")]
        years = results["years"]
        sums = self.sums()
        sources = [f"{label.lower()} – {self.money(sums[key])}" for key, label in self.source_columns() if sums[key]]
        if sums["own"]:
            sources.append(f"собственных средств – {self.money(sums['own'])}")
        blocks = [
            _block("p", "Прогнозный отчет о движении денежных средств приведен в Приложении 4 к настоящему бизнес-плану."),
            _block("p", f"Инвестиционные затраты в размере {self.money(sums['total'])} финансируются за счёт: " + ", ".join(sources) + "."),
        ]
        negative = [str(years[index]) for index, value in enumerate(operating) if value < -0.5]
        if negative:
            blocks.append(_block("p", f"Операционный поток отрицательный в годы: {', '.join(negative)}; это период запуска и льготы."))
        else:
            blocks.append(_block("p", "Чистый денежный поток от операционной деятельности положителен на протяжении всего прогнозного периода."))
        low = [str(years[index]) for index, value in enumerate(ending) if value < -0.5]
        if low:
            blocks.append(_block("warn", f"Остаток денег на конец года отрицательный в годы: {', '.join(low)}. Разрыв нужно закрыть до подачи заявки."))
        else:
            blocks.append(_block("p", f"Остаток денежных средств на конец каждого года положителен: {self.money(ending[-1])} на конец {years[-1]} года."))
        rows = [
            ("Операционная деятельность", operating, "money"),
            ("Инвестиционная деятельность", _row(results, "cash_rows", "Итого движение денег по инвест"), "money"),
            ("Финансовая деятельность", _row(results, "cash_rows", "Итого движение денег по финанс"), "money"),
            ("Денежные средства на конец года", ending, "money"),
        ]
        blocks.extend(self.year_tables(f"Движение денежных средств, {self.cur}", [row for row in rows if row[1] is not None]))
        return blocks

    # ---------- выводы и приложения ----------

    def part_close(self) -> list:
        blocks = list(self.answer("summary"))
        sums = self.sums()
        rows = [["Стоимость проекта", self.money(sums["total"])]]
        for key, label in self.source_columns():
            rows.append([label, f"{self.money(sums[key])} ({_share(sums[key], sums['total'])})"])
        rows.append(["Собственные средства", f"{self.money(sums['own'])} ({_share(sums['own'], sums['total'])})"])
        if self.results:
            if isinstance(self.results.get("npv"), (int, float)):
                rows.append(["NPV", self.money(self.results["npv"])])
            rows.append(["IRR", _rate(self.results.get("irr"))])
            rows.append(["Простой срок окупаемости", _years(self.results.get("payback"))])
            if self.credit_on:
                rows.append(["Минимальный DSCR", _plain_number(self.results.get("dscr"))])
        if self.investor_on:
            rows.append(["Срок участия инвестора", f"{self.facts['investor_years']} лет"])
        blocks.extend(self.table("Ключевые показатели проекта", ["Показатель", "Значение"], rows, [0.55, 0.45]))
        return blocks

    def appendices(self) -> list[tuple[str, list]]:
        results = self.results
        found = []
        payroll = results.get("payroll") or {}
        fot = next((row["values"] for row in payroll.get("totals") or [] if row["label"] == "ФОТ"), None)
        keep = self.active_years(fot)
        if payroll.get("people") and keep:
            rows = [(f"{person['name']} (оклад {_n(person['salary'])})", person["fund"], "money") for person in payroll["people"]]
            rows.extend((row["label"], row["values"], "money") for row in payroll["totals"])
            found.append((f"Приложение {len(found) + 1}. Расходы по фонду оплаты труда, {self.cur}", self.year_tables(f"Фонд оплаты труда, {self.cur}", rows, keep, "Наименование должности") + [_block("p", "Источник: финансовая модель, лист «ФОТ».")]))
        income = results.get("income") or {}
        keep = self.active_years(income.get("total"))
        if income.get("products") and keep:
            rows = []
            for product in income["products"]:
                rows.append((product["name"], None, "money"))
                rows.append(("Количество", product["count"], "count"))
                rows.append(("Цена", product["price"], "money"))
                rows.append(("Выручка", product["revenue"], "money"))
            rows.append(("Итого выручка", income["total"], "money"))
            found.append((f"Приложение {len(found) + 1}. План доходов, {self.cur}", self.year_tables(f"План доходов, {self.cur}", rows, keep) + [_block("p", "Источник: финансовая модель, лист «План доходов».")]))
        if results.get("pnl_rows"):
            rows = [(row["label"], row["values"], "pct" if row["label"].startswith("Рентабельность") else "money") for row in results["pnl_rows"] if not row["label"].startswith(("Перенос убытка", "Использовано убытка"))]
            found.append((f"Приложение {len(found) + 1}. Прогнозный отчет о прибылях и убытках, {self.cur}", self.year_tables(f"Отчет о прибылях и убытках, {self.cur}", rows, first="Наименование") + [_block("p", "Источник: финансовая модель, лист «ОПиУ».")]))
        if results.get("cash_rows"):
            rows = [(row["label"], row["values"], "money") for row in results["cash_rows"]]
            found.append((f"Приложение {len(found) + 1}. Прогнозный отчет о движении денежных средств, {self.cur}", self.year_tables(f"Отчет о движении денежных средств, {self.cur}", rows, first="Наименование") + [_block("p", "Источник: финансовая модель, лист «ДДС».")]))
        pictures = [item for item in self.images if item["section"] == "appendices"]
        if pictures:
            found.append((f"Приложение {len(found) + 1}. Фото и документы", [_block("image", item["data"], item["ratio"], item["caption"]) for item in pictures]))
        return found

    def build(self) -> list[tuple]:
        parts = [
            (1, "Общие исходные данные и условия.", self.part_terms()),
            (2, "Информация о хозяйствующем субъекте", self.part_company()),
            (3, "Анализ рисков", self.part_risks()),
            (4, "Реализация продукции (оказание услуг)", self.part_offer()),
            (5, "Прогнозы продаж и планируемый график продаж.", self.part_sales()),
            (6, "План расходов", self.part_costs()),
            (7, "Оценка вложений инвестиций", self.part_invest()),
            (8, "Выводы", self.part_close()),
        ]
        sections = [(0, "Содержание", [])]
        for number, title, blocks in parts:
            key = next((name for name, target in IMAGE_SECTIONS.items() if target == number), None)
            pictures = [_block("image", item["data"], item["ratio"], item["caption"]) for item in self.images if key and item["section"] == key]
            sections.append((number, title, [*blocks, *pictures]))
        for title, blocks in self.appendices():
            sections.append((None, title, blocks))
        return sections


def mosaic_sections(facts: dict, form: dict, answers: dict, results: dict | None, profile: dict | None, images: list[dict] | None) -> list[tuple]:
    return _Builder(facts, form, answers, results, profile or {}, images or []).build()
