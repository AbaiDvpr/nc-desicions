from datetime import datetime
from io import BytesIO
from zoneinfo import ZoneInfo

from docx import Document
from docx.shared import Pt, RGBColor

from app.excel_report import (
    BONUS_TYPES,
    COST_KINDS,
    LOAN_METHODS,
    PERIODS,
    SEASONALITY_LABELS,
    TAX_REGIMES,
)
from app.currencies import SIGN_BY_ISO
from app.models import User
from app.schemas import FinModelData

LOCAL_TZ = ZoneInfo("Asia/Almaty")
PRIMARY = RGBColor(0x2F, 0x5B, 0xEA)
MUTED = RGBColor(0x6B, 0x74, 0x89)
CURRENCY_SIGNS = SIGN_BY_ISO


def _money(value: float, sign: str) -> str:
    return f"{value:,.0f}".replace(",", " ") + f" {sign}"


def _muted(doc: Document, text: str) -> None:
    run = doc.add_paragraph().add_run(text)
    run.font.color.rgb = MUTED


def _text(doc: Document, heading: str, value: str) -> None:
    doc.add_heading(heading, level=2)
    if value:
        doc.add_paragraph(value)
    else:
        _muted(doc, "Не указано")


def _table(doc: Document, headers: list[str], rows: list[list[str]]) -> None:
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = "Light Grid Accent 1"
    for cell, text in zip(table.rows[0].cells, headers):
        cell.text = text
    for values in rows:
        for cell, text in zip(table.add_row().cells, values):
            cell.text = str(text)


def _key_values(doc: Document, rows: list[tuple[str, str]]) -> None:
    table = doc.add_table(rows=0, cols=2)
    table.style = "Light Grid Accent 1"
    for label, value in rows:
        cells = table.add_row().cells
        cells[0].text = label
        cells[1].text = value or "—"
        cells[0].paragraphs[0].runs[0].bold = True


def build_fin_model_report(user: User, data: FinModelData) -> bytes:
    c, market, rev, exp, inv = data.company, data.market, data.revenue, data.expenses, data.investment
    sign = CURRENCY_SIGNS[c.currency]
    money = lambda value: _money(value, sign)  # noqa: E731

    doc = Document()
    normal = doc.styles["Normal"]
    normal.font.name = "Calibri"
    normal.font.size = Pt(11)

    brand = doc.add_paragraph().add_run("NC-Desicion")
    brand.bold = True
    brand.font.color.rgb = PRIMARY
    doc.add_heading(f"Бизнес-план: {c.name}", level=0)
    _muted(doc, "Описательная часть и исходные предпосылки · сгенерировано NC-AI")
    _key_values(doc, [
        ("Клиент", user.full_name),
        ("Компания / проект", f"{c.legal_form} «{c.name}»" if c.legal_form else c.name),
        ("Отрасль", c.industry),
        ("Регион реализации", ", ".join(x for x in (c.country, c.city) if x)),
        ("Дата отчёта", datetime.now(LOCAL_TZ).strftime("%d.%m.%Y %H:%M")),
    ])

    doc.add_heading("1. О компании и проекте", level=1)
    _key_values(doc, [
        ("Вид деятельности", c.activity),
        ("Стадия бизнеса", c.stage),
    ])
    _text(doc, "Описание проекта", c.description)
    _text(doc, "Целевая аудитория", c.target_audience)
    _text(doc, "Конкурентные преимущества", c.advantages)

    doc.add_heading("Рынок и бизнес-план", level=2)
    _text(doc, "Проблема, которую решает бизнес", market.problem)
    _text(doc, "Размер и особенности рынка", "Неизвестно" if market.market_unknown else market.market_size)
    for heading, items in (("Основные конкуренты", market.competitors),
                           ("Поставщики и партнёры", market.suppliers)):
        doc.add_heading(heading, level=2)
        filled = [i for i in items if i.name]
        if filled:
            for item in filled:
                doc.add_paragraph(f"{item.name}" + (f" — {item.note}" if item.note else ""),
                                  style="List Bullet")
        else:
            _muted(doc, "Не указано")
    _text(doc, "Каналы продаж", ", ".join(market.channels))
    _text(doc, "Маркетинговая стратегия", market.marketing)
    _text(doc, "Основные риски", market.risks)
    _text(doc, "Цель финансирования", market.funding_goal)

    doc.add_heading("Параметры финансовой модели", level=2)
    _key_values(doc, [
        ("Валюта", c.currency),
        ("Дата начала проекта", c.start_date or "следующий месяц"),
        ("Горизонт прогноза", f"{c.horizon_months} мес."),
        ("Шаг расчёта", "Месяц"),
        ("Налоговый режим", TAX_REGIMES[c.tax_regime]),
        ("Ставки налогов", "Автоматически" if c.tax_mode == "auto"
         else f"КПН {c.cit_rate:g}%, налог с дохода {c.turnover_tax_rate:g}%"),
        ("Плательщик НДС", "Да" if c.vat_payer else "Нет"),
        ("Годовая инфляция", f"{c.inflation:g}%"),
        ("Ожидаемый рост бизнеса", f"{c.business_growth:g}% в год"),
        ("Ставка дисконтирования", "Рассчитать автоматически" if c.discount_mode == "auto"
         else f"{c.discount_rate:g}%"),
        ("Начальный остаток денег", money(c.opening_cash)),
    ])

    doc.add_heading("2. План доходов", level=1)
    if rev.products:
        _table(doc, ["Продукт", "Цена", "Объём / мес.", "Себест. ед.", "Рост в год", "Сезонность"], [
            [p.name, money(p.price), f"{p.volume:g} {p.unit}", money(p.cogs_unit), f"{p.growth:g}%",
             SEASONALITY_LABELS[p.seasonality]]
            for p in rev.products
        ])
        first_month = sum(p.price * (1 - p.discount_pct / 100) * p.volume for p in rev.products)
        doc.add_paragraph(f"Выручка от продуктов в первый месяц (без сезонности): {money(first_month)}")
    else:
        _muted(doc, "Продукты не указаны")
    extra = []
    if rev.subscription_enabled:
        extra.append(("Подписка", f"{rev.subscribers_start:g} абонентов × {money(rev.subscription_fee)} в мес."))
    if rev.other_income_amount:
        extra.append(("Прочие доходы", f"{money(rev.other_income_amount)} {PERIODS[rev.other_income_period].lower()}"))
    if rev.one_time_amount:
        extra.append(("Разовый доход", f"{money(rev.one_time_amount)} ({rev.one_time_date or 'первый месяц'})"))
    if rev.leads_per_month:
        extra.append(("Лиды и конверсия", f"{rev.leads_per_month:g} лидов в мес., конверсия {rev.conversion_pct:g}%"))
    if rev.churn_pct or rev.client_growth_pct:
        extra.append(("Клиентская база", f"отток {rev.churn_pct:g}% / рост {rev.client_growth_pct:g}% в мес."))
    if rev.capacity_units:
        extra.append(("Производственная мощность", f"{rev.capacity_units:g} ед. в мес."))
    if rev.inventory_amount:
        extra.append(("Товарные запасы", f"{money(rev.inventory_amount)}, закупка за {rev.inventory_days} дн."))
    if extra:
        doc.add_heading("Дополнительные доходы и настройки", level=2)
        _key_values(doc, extra)

    doc.add_heading("3. Расходы", level=1)
    doc.add_heading("Фонд оплаты труда", level=2)
    if exp.staff:
        _table(doc, ["Должность", "Кол-во", "Оклад", "Выход", "Индексация", "Бонус"], [
            [s.position, f"{s.count:g}", money(s.salary), s.start_date or "с начала", f"{s.indexation:g}%",
             BONUS_TYPES[s.bonus_type]]
            for s in exp.staff
        ])
        payroll = sum(s.count * s.salary for s in exp.staff)
        doc.add_paragraph(f"ФОТ в месяц при полном штате (без налогов): {money(payroll)}")
    else:
        _muted(doc, "Сотрудники не указаны")
    doc.add_heading("Административные и операционные расходы", level=2)
    if exp.costs:
        _table(doc, ["Статья", "Категория", "Тип", "Размер", "Периодичность"], [
            [x.name, x.category, COST_KINDS[x.kind],
             f"{x.value:g}%" if x.kind == "pct_revenue" else money(x.value), PERIODS[x.period]]
            for x in exp.costs
        ])
    else:
        _muted(doc, "Расходы не указаны")
    doc.add_heading("Основные средства", level=2)
    if exp.assets:
        _table(doc, ["Актив", "Стоимость", "Кол-во", "Покупка", "Срок, мес."], [
            [a.name, money(a.cost), f"{a.quantity:g}", a.purchase_date or "с начала", str(a.life_months)]
            for a in exp.assets
        ])
    else:
        _muted(doc, "Основные средства не указаны")

    doc.add_heading("4. Инвестиции и финансирование", level=1)
    capex = sum(a.cost * a.quantity for a in exp.assets) if inv.capex_mode == "assets" else inv.capex_manual
    launch = sum(i.amount for i in inv.launch_costs)
    _key_values(doc, [
        ("Капитальные вложения", money(capex)),
        ("Запуск и подготовка", money(launch)),
        ("Оборотный капитал", "Рассчитывается в модели" if inv.wc_mode == "auto" else money(inv.working_capital)),
        ("Резерв", f"{inv.contingency_value:g}% от капвложений" if inv.contingency_type == "pct"
         else money(inv.contingency_value)),
        ("Существующие вложения", money(inv.existing_investments)),
    ])
    doc.add_heading("Источники", level=2)
    sources = [("Собственные средства", money(inv.equity))]
    if inv.investor_amount:
        sources.append(("Взнос инвестора", f"{money(inv.investor_amount)}, доля {inv.investor_share:g}%"))
    if inv.grant_amount:
        sources.append(("Гранты и субсидии", f"{money(inv.grant_amount)}. {inv.grant_terms}".strip()))
    for contribution in inv.contributions:
        sources.append(("Дополнительный взнос", f"{money(contribution.amount)} ({contribution.date or '—'})"))
    _key_values(doc, sources)
    if inv.loans:
        doc.add_heading("Кредиты и займы", level=2)
        _table(doc, ["Кредит", "Сумма", "Ставка", "Срок, мес.", "Погашение", "Льгота, мес."], [
            [loan.name or "Кредит", money(loan.amount), f"{loan.rate:g}%", str(loan.term_months),
             LOAN_METHODS[loan.method], str(loan.grace_months)]
            for loan in inv.loans
        ])
    doc.add_heading("Сценарии", level=2)
    _table(doc, ["Сценарий", "Продажи", "Расходы"], [
        ["Базовый", "0%", "0%"],
        ["Оптимистичный", f"{inv.scenario_opt_sales:+g}%", f"{inv.scenario_opt_costs:+g}%"],
        ["Пессимистичный", f"{inv.scenario_pes_sales:+g}%", f"{inv.scenario_pes_costs:+g}%"],
    ])

    doc.add_heading("Вывод NC-AI", level=1)
    _muted(doc, "Помесячные расчёты (ОПиУ, ДДС, график платежей) и показатели эффективности "
                "(NPV, IRR, срок окупаемости) — в Excel-модели, лист «Анализ эффект.».")

    buffer = BytesIO()
    doc.save(buffer)
    return buffer.getvalue()
