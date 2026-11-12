"""JSON-выгрузка анкеты для ИИ-аудита: значения + пояснения по полям.

Шаги 1–4 анкеты финансовой модели.
"""

from __future__ import annotations

from datetime import datetime, timezone

LEGAL_FORMS = {1: "ИП", 2: "ТОО"}
YES_NO = {1: "Да", 0: "Нет"}
BOOL_YES_NO = {True: "Да", False: "Нет"}
TAX_REGIMES = {1: "Общеустановленный порядок (ОУР)", 4: "Упрощённая декларация"}
CURRENCIES = {"kzt": "₸ Тенге (KZT)", "usd": "$ Доллар (USD)", "eur": "€ Евро (EUR)"}
TIME_PARAMS = {1: "По годам", 3: "Помесячно"}
SCHEDULES = {
    1: "5 дней в неделю (8 часов)",
    2: "6 дней в неделю (8 часов)",
    3: "Каждый день (8 часов)",
}
FINANCING = {
    1: "Долевое финансирование (инвестор)",
    2: "Долговое (кредитное) финансирование",
    3: "Грантовое финансирование",
    4: "Инвестор и кредит",
}
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
OTHER_INCOME_PERIODS = {1: "Ежемесячно", 2: "Ежегодно", 3: "Разово"}
EXPENSE_PERIODS = {
    0: "Единоразово",
    1: "Ежемесячно",
    2: "Каждые 2 месяца",
    3: "Ежеквартально",
    4: "3 раза в год",
    6: "2 раза в год",
    12: "Раз в год",
}
EXPENSE_TYPES = {0: "Прочее", 2: "Аренда"}
LAND_UNITS = {"m2": "м²", "ha": "га"}
COST_MODES = {"unit": "На единицу продукции", "monthly": "Помесячно (устаревший/служебный)"}
MONTH_NAMES = {
    1: "Янв",
    2: "Фев",
    3: "Мар",
    4: "Апр",
    5: "Май",
    6: "Июн",
    7: "Июл",
    8: "Авг",
    9: "Сен",
    10: "Окт",
    11: "Ноя",
    12: "Дек",
}
ASSET_GROUPS = (
    ("equipment", "Оборудование", "Амортизируемый CAPEX. Входит в инвестиции и амортизацию."),
    ("premises", "Помещения / здания", "Недвижимость. Обычно срок амортизации 20–25 лет."),
    ("smr", "СМР / капремонт", "Строительно-монтажные работы и капремонт."),
    ("overhaul", "Материалы при СМР", "Материалы ремонта. CAPEX с амортизацией."),
    ("inventory", "Товарный запас", "Первоначальный запас. Без амортизации, один раз в ДДС (U34)."),
)
PAYROLL_RATES = (
    ("opv_rate", "ОПВ работника", "Удержание из зарплаты к выплате. Дефолт из админки, можно менять в анкете."),
    ("vosms_rate", "ВОСМС работника", "Удержание из зарплаты к выплате."),
    ("so_rate", "СО", "Социальные отчисления работодателя."),
    (
        "opvr_rate",
        "ОПВР (2026)",
        "Пенсионные взносы работодателя. Ставка анкеты — для месяцев до 2026 включительно; "
        "2027 и с 2028 — ставки справочника админки (по умолчанию 4,5% и 5%), видны на «Предпосылках», строки 28–29.",
    ),
    ("osms_rate", "ООСМС", "Медстрахование работодателя."),
)
INVEST_FIELDS = (
    ("smr", "Перечень видов работ СМР, капитального ремонта", "capex"),
    ("overhaul", "Перечень материалов при СМР, капитальном ремонте", "capex"),
    ("ready_premises", "Помещения, здания, сооружения", "capex"),
    ("equipment", "Приобретение оборудования, техники, мебели", "capex"),
    ("raw", "Первоначальный запас сырья и товаров (закупка, не денежный резерв)", "operating"),
    ("advertising", "Расходы на рекламу", "operating"),
    ("wages", "Расходы по выплате заработной платы", "operating"),
    ("rental", "Расходы по аренде", "operating"),
    ("other", "Прочие расходы", "operating"),
)
CAPEX_SOURCE = {
    "smr": "smr",
    "overhaul": "overhaul",
    "ready_premises": "premises",
    "equipment": "equipment",
}
OPERATING_MONTH_KEYS = {
    "raw": "months_raw",
    "wages": "months_wages",
    "advertising": "months_advertising",
    "rental": "months_rental",
    "other": "months_other",
}


def _as_int(value, default=None):
    try:
        if value is None or value == "":
            return default
        return int(value)
    except (TypeError, ValueError):
        return default


def _display(value, mapping: dict | None = None, suffix: str = ""):
    if value is None or value == "":
        return None
    if mapping is not None:
        key = value
        try:
            key = int(value) if not isinstance(value, str) or str(value).isdigit() else value
        except (TypeError, ValueError):
            key = value
        label = mapping.get(key) or mapping.get(str(value))
        if label is not None:
            return label
    text = str(value)
    return f"{text}{suffix}" if suffix else text


def _field(
    key: str,
    label: str,
    value,
    *,
    explain: str,
    required: bool = False,
    type_: str = "text",
    display=None,
    options: list | None = None,
    unit: str | None = None,
    visible: bool = True,
    note: str | None = None,
) -> dict:
    item = {
        "key": key,
        "label": label,
        "value": value if value != "" else None,
        "display": display if display is not None else (None if value in (None, "") else value),
        "type": type_,
        "required": required,
        "visible_in_form": visible,
        "explain": explain,
    }
    if unit:
        item["unit"] = unit
    if options:
        item["options"] = options
    if note:
        item["note"] = note
    return item


def _horizon_months(start, end) -> int | None:
    try:
        sy, sm = str(start)[:7].split("-")
        ey, em = str(end)[:7].split("-")
        months = (int(ey) - int(sy)) * 12 + (int(em) - int(sm)) + 1
        return months if months > 0 else None
    except (TypeError, ValueError):
        return None


def _as_num(value):
    if value is None or value == "":
        return None
    try:
        number = float(value)
    except (TypeError, ValueError):
        return value
    if float(number).is_integer():
        return int(number)
    return number


def _as_bool(value) -> bool | None:
    if value is None or value == "":
        return None
    if isinstance(value, bool):
        return value
    if value in (1, "1", "true", "True", "yes", "on"):
        return True
    if value in (0, "0", "false", "False", "no", "off"):
        return False
    return bool(value)


def _years_between(start, end) -> list[int]:
    try:
        sy = int(str(start)[:4])
        ey = int(str(end)[:4])
    except (TypeError, ValueError):
        return []
    if ey < sy:
        return []
    return list(range(sy, ey + 1))


def _program_payload(program) -> dict | None:
    if not isinstance(program, dict) or not program.get("slug"):
        return None
    return {
        "slug": program.get("slug"),
        "title": program.get("title"),
        "kind": program.get("kind"),
        "kind_label": program.get("kind_label"),
        "url": program.get("url"),
        "rate": program.get("rate"),
        "client_rate": program.get("client_rate"),
        "amount": program.get("amount"),
        "detail": program.get("detail"),
        "purpose": program.get("purpose"),
        "term_min_months": program.get("term_min_months"),
        "term_max_months": program.get("term_max_months"),
        "term_kind": program.get("term_kind"),
        "logic": program.get("logic"),
        "allows_investment": program.get("allows_investment"),
        "allows_wc": program.get("allows_wc"),
        "grace_max_months": program.get("grace_max_months"),
    }


def _product_item(row: dict, index: int) -> dict:
    units = _as_int(row.get("units"), 1)
    months = row.get("months") or []
    capacity = row.get("planned_capacity") or {}
    return {
        "index": index,
        "name": row.get("name"),
        "count": _as_num(row.get("count")),
        "units": units,
        "units_display": _display(units, UNITS),
        "price": _as_num(row.get("price")),
        "seasonal": bool(row.get("seasonal")),
        "months": months,
        "months_display": [_display(m, MONTH_NAMES) for m in months] if months else [],
        "sales_start": row.get("sales_start") or None,
        "own_capacity": bool(row.get("own_capacity")),
        "planned_capacity": {str(k): _as_num(v) for k, v in capacity.items()},
        "explain": (
            "Строка продукции: объём при 100% загрузке и цена. "
            "seasonal+months — только выбранные месяцы. "
            "sales_start пустой = общий старт продаж проекта. "
            "own_capacity — своя загрузка по годам вместо общей planned_capacity."
        ),
    }


def _asset_item(row: dict, index: int, *, inventory: bool = False) -> dict:
    item = {
        "index": index,
        "name": row.get("name"),
        "count": _as_num(row.get("count")),
        "price_one": _as_num(row.get("price_one")),
        "price": _as_num(row.get("price")),
    }
    if inventory:
        item["explain"] = (
            "Товарный запас: count × price_one = price. Без амортизации, один раз в оборотном капитале/ДДС."
        )
        return item
    item.update(
        {
            "year": _as_int(row.get("year")),
            "life_years": _as_int(row.get("life_years")),
            "pay_month": row.get("pay_month") or None,
            "commission_month": row.get("commission_month") or None,
            "already_owned": bool(row.get("already_owned")),
            "book_value": _as_num(row.get("book_value")),
            "explain": (
                "Актив CAPEX: price — стоимость к оплате; life_years — срок амортизации; "
                "pay_month — платёж в ДДС; commission_month — ввод в эксплуатацию; "
                "already_owned — уже есть на балансе (book_value)."
            ),
        }
    )
    return item


def _is_land_tax(row: dict) -> bool:
    if bool(row.get("land_tax")):
        return True
    return "земельн" in str(row.get("name") or "").strip().lower()


def _variable_cost_item(row: dict, index: int) -> dict:
    return {
        "index": index,
        "product": row.get("product"),
        "cost_type": row.get("cost_type"),
        "price": _as_num(row.get("price")),
        "explain": (
            "Себестоимость на единицу: price умножается на продажи выбранного product. "
            "Первоначальный товарный запас из инвестиций сюда не копируется."
        ),
    }


def _expense_item(row: dict, index: int) -> dict:
    period = _as_int(row.get("period"), 1)
    type_expense = _as_int(row.get("type_expense"), 0)
    land = _is_land_tax(row)
    unit = "ha" if str(row.get("land_unit") or "").lower() in ("ha", "га", "hectare", "hectares") else "m2"
    item = {
        "index": index,
        "name": row.get("name"),
        "value": _as_num(row.get("value")),
        "invest": bool(row.get("invest")),
        "period": period,
        "period_display": _display(period, EXPENSE_PERIODS),
        "type_expense": type_expense,
        "type_expense_display": _display(type_expense, EXPENSE_TYPES),
        "land_tax": land,
        "explain": (
            "Постоянный/переменный расход по графику period. "
            "invest=true — платить с начала проекта (и до продаж). "
            "period=12 — раз в год; 0 — единоразово."
        ),
    }
    if land:
        item.update(
            {
                "land_unit": unit,
                "land_unit_display": _display(unit, LAND_UNITS),
                "land_area": _as_num(row.get("land_area")),
                "land_rate": _as_num(row.get("land_rate")),
                "explain": (
                    "Земельный налог: value ≈ land_area × land_rate за год (period обычно 12). "
                    "Единица площади land_unit: m2 или ha."
                ),
            }
        )
    return item


def _salary_item(row: dict, index: int) -> dict:
    return {
        "index": index,
        "name": row.get("name"),
        "count": _as_num(row.get("count")),
        "salary": _as_num(row.get("salary")),
        "percent": _as_num(row.get("percent")),
        "invest": bool(row.get("invest")),
        "industrial": _as_int(row.get("industrial"), 0) == 1,
        "staff_kind": "производственный (ФОТ в себестоимости)" if _as_int(row.get("industrial"), 0) == 1 else "административный (ФОТ в общих расходах)",
        "opv_exempt": bool(row.get("opv_exempt")),
        "vosms_exempt": bool(row.get("vosms_exempt")),
        "so_exempt": bool(row.get("so_exempt")),
        "opvr_exempt": bool(row.get("opvr_exempt")),
        "osms_exempt": bool(row.get("osms_exempt")),
        "services": row.get("services") or [],
        "explain": (
            "Должность ФОТ. salary — оклад до удержаний; percent — доля от выручки. "
            "invest=true — только до начала продаж; industrial=true — производственный персонал (себестоимость), иначе АУП. "
            "*_exempt=true отключает соответствующий взнос только для этой должности. "
            "Ставки взносов общие для проекта: opv_rate/vosms_rate/so_rate/opvr_rate/osms_rate."
        ),
    }


def build_step1(form: dict) -> dict:
    legal = _as_int(form.get("legal_form"))
    nds = _as_int(form.get("nds"))
    regime = _as_int(form.get("tax_regime"))
    too = legal == 2
    vat_on = regime == 1 and nds == 1
    months = _horizon_months(form.get("planning_from"), form.get("planning_to"))

    return {
        "step": 1,
        "title": "Параметры проекта",
        "description": (
            "Первый шаг анкеты финансовой модели: название, форма бизнеса, налоги, валюта, "
            "график работы, ставки режима и горизонт планирования."
        ),
        "sections": [
            {
                "title": "Основная информация",
                "description": "Базовые данные о проекте",
                "fields": [
                    _field(
                        "name",
                        "Название проекта",
                        form.get("name"),
                        type_="text",
                        required=True,
                        explain="Человекочитаемое имя модели. Попадает в шапку Excel и список моделей.",
                    ),
                    _field(
                        "legal_form",
                        "Форма бизнеса",
                        legal,
                        type_="enum",
                        required=True,
                        display=_display(legal, LEGAL_FORMS),
                        options=[{"value": k, "label": v} for k, v in LEGAL_FORMS.items()],
                        explain=(
                            "1 = ИП, 2 = ТОО. Влияет на ставки упрощёнки/КПН/ИПН, соцналог (только ТОО на ОУР) "
                            "и часть удержаний ФОТ."
                        ),
                    ),
                    _field(
                        "nds",
                        "Плательщик НДС",
                        nds,
                        type_="enum",
                        required=True,
                        display=_display(nds, YES_NO),
                        options=[{"value": k, "label": v} for k, v in YES_NO.items()],
                        explain=(
                            "1 = да, 0 = нет. НДС в модели учитывается только вместе с ОУР (tax_regime=1): "
                            "в ДДС к потокам добавляется НДС, ставка берётся из vat_rate."
                        ),
                    ),
                ],
            },
            {
                "title": "Финансовые настройки",
                "description": "Параметры расчёта модели",
                "fields": [
                    _field(
                        "tax_regime",
                        "Налоговый режим",
                        regime,
                        type_="enum",
                        required=True,
                        display=_display(regime, TAX_REGIMES),
                        options=[{"value": k, "label": v} for k, v in TAX_REGIMES.items()],
                        explain=(
                            "1 = общий режим (налог с прибыли/ИПН режима), 4 = упрощёнка (налог с выручки). "
                            "Определяет, какая ставка режима активна и доступен ли НДС."
                        ),
                    ),
                    _field(
                        "currency",
                        "Валюта",
                        form.get("currency"),
                        type_="enum",
                        required=True,
                        display=_display(form.get("currency"), CURRENCIES),
                        options=[{"value": k, "label": v} for k, v in CURRENCIES.items()],
                        explain="Валюта отображения сумм в анкете и Excel (kzt/usd/eur).",
                    ),
                    _field(
                        "time_param",
                        "Периодичность расчёта",
                        _as_int(form.get("time_param")),
                        type_="enum",
                        required=True,
                        display=_display(_as_int(form.get("time_param")), TIME_PARAMS),
                        options=[{"value": k, "label": v} for k, v in TIME_PARAMS.items()],
                        explain="3 = помесячная модель (основной режим FEM v5), 1 = по годам.",
                    ),
                    _field(
                        "schedule",
                        "График работы",
                        _as_int(form.get("schedule")),
                        type_="enum",
                        required=True,
                        display=_display(_as_int(form.get("schedule")), SCHEDULES),
                        options=[{"value": k, "label": v} for k, v in SCHEDULES.items()],
                        explain="Рабочий календарь проекта: 5/6/7 дней в неделю. Влияет на рабочие дни в помесячном плане.",
                    ),
                ],
            },
            {
                "title": "Ставки налогов и взносов",
                "description": (
                    "Ставки режима из справочника админа; клиент может переопределить для модели. "
                    "ФОТ-ставки (ОПВ/ВОСМС/СО/ОПВР/ООСМС) задаются на шаге расходов рядом с должностями — "
                    "в шаг 1 этой выгрузки не входят."
                ),
                "fields": [
                    _field(
                        "tax_simplified_rate",
                        "Упрощёнка" + (" (ТОО)" if too else " (ИП)"),
                        form.get("tax_simplified_rate"),
                        type_="percent",
                        unit="%",
                        required=regime == 4,
                        visible=regime == 4,
                        display=_display(form.get("tax_simplified_rate"), suffix="%"),
                        explain="Ставка СНР упрощённой декларации. Активна только при tax_regime=4.",
                        note=None if regime == 4 else "Поле скрыто: выбран не упрощённый режим.",
                    ),
                    _field(
                        "tax_cit_rate",
                        "КПН" if too else "ИПН / КПН режима",
                        form.get("tax_cit_rate"),
                        type_="percent",
                        unit="%",
                        required=regime == 1,
                        visible=regime == 1,
                        display=_display(form.get("tax_cit_rate"), suffix="%"),
                        explain="Ставка общего режима. Активна только при tax_regime=1.",
                        note=None if regime == 1 else "Поле скрыто: выбран не общий режим.",
                    ),
                    _field(
                        "vat_rate",
                        "НДС",
                        form.get("vat_rate"),
                        type_="percent",
                        unit="%",
                        required=vat_on,
                        visible=vat_on,
                        display=_display(form.get("vat_rate"), suffix="%"),
                        explain="Ставка НДС. Используется в ДДС, если ОУР и nds=1.",
                        note=None if vat_on else "Поле скрыто: НДС не включён (нужны ОУР и плательщик НДС).",
                    ),
                    _field(
                        "social_tax_rate",
                        "Социальный налог",
                        form.get("social_tax_rate"),
                        type_="percent",
                        unit="%",
                        required=too and regime == 1,
                        visible=too and regime == 1,
                        display=_display(form.get("social_tax_rate"), suffix="%"),
                        explain="Соцналог работодателя. В модели для ТОО на ОУР с 2026.",
                        note=None if (too and regime == 1) else "Поле скрыто: нужно ТОО на ОУР.",
                    ),
                ],
            },
            {
                "title": "Период планирования",
                "description": "Горизонт расчёта и срок проекта",
                "fields": [
                    _field(
                        "planning_from",
                        "Начало проекта",
                        form.get("planning_from"),
                        type_="month",
                        required=True,
                        explain="Первый месяц плана (YYYY-MM). Якорь CAPEX/ФОТ «с начала проекта» и WACC-горизонта.",
                    ),
                    _field(
                        "planning_to",
                        "Окончание проекта",
                        form.get("planning_to"),
                        type_="month",
                        required=True,
                        explain="Последний месяц плана (YYYY-MM). Вместе с planning_from задаёт число лет/месяцев модели.",
                    ),
                    _field(
                        "planning_horizon_months",
                        "Горизонт планирования (расчётный)",
                        months,
                        type_="number",
                        unit="мес.",
                        required=False,
                        visible=True,
                        display=f"{months} мес." if months else None,
                        explain=(
                            "Служебное поле: число месяцев между началом и окончанием включительно. "
                            "В анкете не вводится вручную, показывается как подсказка."
                        ),
                    ),
                ],
            },
        ],
    }


def build_step2(form: dict) -> dict:
    financing = _as_int(form.get("financing"), 1)
    need_investor = financing in (1, 4)
    need_loan = financing in (2, 4)
    need_grant = financing == 3
    start_month = str(form.get("sales_start") or "")[:7]
    full_start = len(start_month) == 7 and start_month == str(form.get("planned_volume") or "")[:7]
    program = _program_payload(form.get("gov_program"))
    guarantee = _program_payload(form.get("gov_guarantee"))
    products = [item for item in (form.get("volume_sales") or []) if isinstance(item, dict)]
    years = _years_between(form.get("planning_from"), form.get("planning_to"))
    capacity = form.get("planned_capacity") or {}
    other_period = _as_int(form.get("other_income_period"), 1)

    finance_fields = [
        _field(
            "financing",
            "Тип финансирования",
            financing,
            type_="enum",
            required=True,
            display=_display(financing, FINANCING),
            options=[{"value": k, "label": v} for k, v in FINANCING.items()],
            explain="1 инвестор, 2 кредит, 3 грант, 4 инвестор+кредит. Включает блоки условий ниже.",
        ),
        _field(
            "investor_term",
            "Срок участия инвестора",
            _as_int(form.get("investor_term")),
            type_="number",
            unit="лет",
            required=need_investor,
            visible=need_investor,
            display=_display(form.get("investor_term"), suffix=" лет"),
            explain="Сколько лет инвестор участвует / получает дивиденды. Обязательно при financing 1 или 4.",
            note=None if need_investor else "Скрыто: финансирование без инвестора.",
        ),
        _field(
            "investor_dividend",
            "Дивиденды инвестора",
            _as_num(form.get("investor_dividend")),
            type_="percent",
            unit="%",
            required=need_investor,
            visible=need_investor,
            display=_display(form.get("investor_dividend"), suffix="%"),
            explain="Доля чистой прибыли прошлого года, выплачиваемая инвестору.",
            note=None if need_investor else "Скрыто: финансирование без инвестора.",
        ),
        _field(
            "risk_premium",
            "Премия за риск проекта",
            _as_num(form.get("risk_premium")),
            type_="percent",
            unit="%",
            required=need_investor,
            visible=need_investor,
            display=_display(form.get("risk_premium"), suffix="%"),
            explain="Надбавка сверх базовой ставки НБРК в цене денег инвестора и WACC.",
            note=None if need_investor else "Скрыто: финансирование без инвестора.",
        ),
        _field(
            "gov_program",
            "Госпрограмма Damu",
            program,
            type_="object",
            required=False,
            visible=need_loan,
            display=(program or {}).get("title") if program else "Без госпрограммы",
            explain=(
                "Выбранная программа субсидии/кредита. Может подставлять ставку клиента, срок, каникулы. "
                "Пусто — обычный коммерческий кредит."
            ),
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "gov_guarantee",
            "Гарантия Damu",
            guarantee,
            type_="object",
            required=False,
            visible=need_loan,
            display=(guarantee or {}).get("title") if guarantee else "Без гарантии",
            explain="Дополнение к госпрограмме. На ставку клиента не влияет.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "loan_term",
            "Срок кредита на инвестиции",
            _as_int(form.get("loan_term")),
            type_="number",
            unit="мес.",
            required=need_loan,
            visible=need_loan,
            display=_display(form.get("loan_term"), suffix=" мес."),
            explain="Срок инвестиционного займа (помещение, оборудование, СМР).",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "loan_grace",
            "Каникулы по инвестиционному кредиту",
            _as_int(form.get("loan_grace")),
            type_="number",
            unit="мес.",
            required=False,
            visible=need_loan,
            display=_display(form.get("loan_grace"), suffix=" мес."),
            explain="Льготный период без тела долга, до 36 мес. (или лимит программы).",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "loan_rate",
            "Ставка для клиента (инвесткредит)",
            _as_num(form.get("loan_rate")),
            type_="percent",
            unit="%",
            required=need_loan,
            visible=need_loan,
            display=_display(form.get("loan_rate"), suffix="%"),
            explain="Эффективная ставка, которую платит проект. При субсидии = ставка клиента Damu.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "loan_bank_rate",
            "Ставка банка",
            _as_num(form.get("loan_bank_rate")),
            type_="percent",
            unit="%",
            required=False,
            visible=need_loan and bool(program),
            display=_display(form.get("loan_bank_rate"), suffix="%"),
            explain="Полная банковская ставка до субсидии. Нужна при госпрограмме.",
            note=None if (need_loan and program) else "Скрыто без кредита/госпрограммы.",
        ),
        _field(
            "loan_subsidy_rate",
            "Субсидия Damu",
            _as_num(form.get("loan_subsidy_rate")),
            type_="percent",
            unit="%",
            required=False,
            visible=need_loan and bool(program),
            display=_display(form.get("loan_subsidy_rate"), suffix="%"),
            explain="Обычно банк − клиент. Действует loan_subsidy_term месяцев.",
            note=None if (need_loan and program) else "Скрыто без кредита/госпрограммы.",
        ),
        _field(
            "loan_subsidy_term",
            "Срок субсидии",
            _as_int(form.get("loan_subsidy_term")),
            type_="number",
            unit="мес.",
            required=False,
            visible=need_loan and bool(program),
            display=_display(form.get("loan_subsidy_term"), suffix=" мес."),
            explain="После окончания клиент платит полную ставку банка.",
            note=None if (need_loan and program) else "Скрыто без кредита/госпрограммы.",
        ),
        _field(
            "loan_nbk_plus_4",
            "НБ РК + N%",
            _as_bool(form.get("loan_nbk_plus_4")),
            type_="boolean",
            required=False,
            visible=need_loan,
            display=_display(_as_bool(form.get("loan_nbk_plus_4")), BOOL_YES_NO),
            explain="Подставляет ставку банка = базовая НБРК + надбавка из админки.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "loan_annuity",
            "Аннуитет по инвестиционному кредиту",
            _as_bool(form.get("loan_annuity")),
            type_="boolean",
            required=False,
            visible=need_loan,
            display=_display(_as_bool(form.get("loan_annuity")), BOOL_YES_NO),
            explain="True — аннуитет; False — равными долями тела. Оборотный займ всегда равными долями.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "wc_term",
            "Срок займа на оборотные средства",
            _as_int(form.get("wc_term")),
            type_="number",
            unit="мес.",
            required=False,
            visible=need_loan,
            display=_display(form.get("wc_term"), suffix=" мес."),
            explain="Опционально 24–36 мес. Пусто/0 — без оборотки.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "wc_grace",
            "Каникулы по оборотным средствам",
            _as_int(form.get("wc_grace")),
            type_="number",
            unit="мес.",
            required=False,
            visible=need_loan,
            display=_display(form.get("wc_grace"), suffix=" мес."),
            explain="Льготный период по оборотному займу.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "wc_rate",
            "Ставка по оборотным средствам",
            _as_num(form.get("wc_rate")),
            type_="percent",
            unit="%",
            required=False,
            visible=need_loan,
            display=_display(form.get("wc_rate"), suffix="%"),
            explain="Ставка оборотного займа. При субсидии часто равна ставке клиента программы.",
            note=None if need_loan else "Скрыто: кредит не выбран.",
        ),
        _field(
            "grant_note",
            "Грант",
            "Сумма гранта задаётся на шаге инвестиций" if need_grant else None,
            type_="note",
            required=False,
            visible=need_grant,
            display="Сумма гранта — на шаге 4",
            explain="При financing=3 на шаге 2 доп. условий нет; объём гранта — в структуре финансирования.",
            note=None if need_grant else "Скрыто: выбран не грант.",
        ),
    ]

    asset_sections = []
    for key, title, explain in ASSET_GROUPS:
        rows = [item for item in (form.get(key) or []) if isinstance(item, dict)]
        inventory = key == "inventory"
        items = [_asset_item(row, index, inventory=inventory) for index, row in enumerate(rows)]
        total = sum(float(item.get("price") or 0) for item in items)
        asset_sections.append(
            {
                "key": key,
                "title": title,
                "description": explain,
                "count": len(items),
                "total_price": total,
                "items": items,
                "explain": (
                    f"Список «{title}». Пустой список допустим. "
                    + ("Без амортизации." if inventory else "Каждая строка амортизируется по life_years.")
                ),
            }
        )

    return {
        "step": 2,
        "title": "Продажи и вложения в активы",
        "description": (
            "Второй шаг: финансирование, сроки продаж, продукция, загрузка по годам, прочие доходы, "
            "оборудование/помещения/СМР/материалы/товарный запас."
        ),
        "sections": [
            {
                "title": "Финансирование",
                "description": "Откуда придут привлекаемые деньги",
                "fields": finance_fields,
            },
            {
                "title": "Сроки продаж",
                "description": "Старт, загрузка в первый месяц и выход на 100%",
                "fields": [
                    _field(
                        "sales_start",
                        "Начало продаж",
                        form.get("sales_start"),
                        type_="month",
                        required=True,
                        explain="Первый месяц выручки. Не раньше planning_from. До этой даты выручки нет.",
                    ),
                    _field(
                        "sales_start_load",
                        "Загрузка в первый месяц продаж",
                        100 if full_start else _as_num(form.get("sales_start_load")),
                        type_="percent",
                        unit="%",
                        required=not full_start,
                        display="100%" if full_start else _display(form.get("sales_start_load"), suffix="%"),
                        explain="Доля от планового объёма в месяц sales_start. Дальше линейный рост до 100% к planned_volume.",
                        note=(
                            "sales_start = planned_volume: разгона нет, поле в анкете заблокировано, применяется 100% "
                            "с первого месяца. Введённое ранее значение игнорируется."
                        ) if full_start else None,
                    ),
                    _field(
                        "planned_volume",
                        "Выход на 100%",
                        form.get("planned_volume"),
                        type_="month",
                        required=True,
                        explain="Месяц, когда продажи достигают полной загрузки. Не раньше sales_start.",
                    ),
                ],
            },
            {
                "title": "Продукция",
                "description": "Количество и цена при полной загрузке",
                "fields": [
                    _field(
                        "volume_sales",
                        "Список продукции",
                        [_product_item(row, index) for index, row in enumerate(products)],
                        type_="list",
                        required=True,
                        display=f"{len(products)} поз.",
                        explain=(
                            "Обязателен хотя бы один продукт. count/price — при 100% загрузке "
                            f"({'в месяц' if _as_int(form.get('time_param')) == 3 else 'в год'})."
                        ),
                    ),
                ],
            },
            {
                "title": "Загрузка по годам",
                "description": "Общая мощность по календарным годам горизонта планирования",
                "fields": [
                    _field(
                        "planned_capacity",
                        "Загрузка по годам, %",
                        {str(year): _as_num(capacity.get(str(year), capacity.get(year))) for year in years},
                        type_="object",
                        required=False,
                        display="; ".join(
                            f"{year}: {_as_num(capacity.get(str(year), capacity.get(year))) or 'авто'}%"
                            for year in years
                        )
                        or None,
                        explain=(
                            "Ключ — год, значение — % загрузки. Пусто = авто (линейный рост от sales_start_load "
                            "до 100% к planned_volume). Годы только горизонта шага 1."
                        ),
                    ),
                ],
            },
            {
                "title": "Прочие доходы",
                "description": "Ежемесячные и ежегодные индексируются; разовый — один раз без индекса",
                "fields": [
                    _field(
                        "other_income",
                        "Сумма прочих доходов",
                        _as_num(form.get("other_income")),
                        type_="money",
                        required=False,
                        explain="0/пусто — нет прочих доходов. Период задаёт other_income_period.",
                    ),
                    _field(
                        "other_income_start",
                        "Начало прочих доходов",
                        form.get("other_income_start") or None,
                        type_="month",
                        required=False,
                        explain="Пусто — с начала продаж.",
                    ),
                    _field(
                        "other_income_period",
                        "Как поступают прочие доходы",
                        other_period,
                        type_="enum",
                        required=False,
                        display=_display(other_period, OTHER_INCOME_PERIODS),
                        options=[{"value": k, "label": v} for k, v in OTHER_INCOME_PERIODS.items()],
                        explain="1 ежемесячно, 2 ежегодно, 3 разово.",
                    ),
                ],
            },
            {
                "title": "Активы",
                "description": "Оборудование, помещения, СМР, материалы, товарный запас",
                "fields": [
                    _field(
                        "assets",
                        "Группы активов",
                        asset_sections,
                        type_="list",
                        required=False,
                        display="; ".join(f"{block['title']}: {block['count']}" for block in asset_sections),
                        explain=(
                            "Пять групп: equipment, premises, smr, overhaul, inventory. "
                            "Пустые группы допустимы. Смотри items[] внутри каждой группы."
                        ),
                    ),
                ],
            },
        ],
    }


def build_step3(form: dict) -> dict:
    variable_costs = [item for item in (form.get("variable_costs") or []) if isinstance(item, dict)]
    variable_other = [item for item in (form.get("variable_other") or []) if isinstance(item, dict)]
    salary = [item for item in (form.get("salary") or []) if isinstance(item, dict)]
    admin_all = [item for item in (form.get("admin_expenses") or []) if isinstance(item, dict)]
    admin_expenses = [item for item in admin_all if not _is_land_tax(item)]
    land_tax = [item for item in admin_all if _is_land_tax(item)]
    marketing = [item for item in (form.get("marketing_expenses") or []) if isinstance(item, dict)]
    cost_mode = str(form.get("cost_mode") or "unit")
    from app.project_form import zero_cogs_state

    cogs = zero_cogs_state(form)

    payroll_fields = [
        _field(
            key,
            label,
            _as_num(form.get(key)),
            type_="percent",
            unit="%",
            required=False,
            display=_display(form.get(key), suffix="%"),
            explain=explain,
        )
        for key, label, explain in PAYROLL_RATES
    ]

    return {
        "step": 3,
        "title": "Себестоимость и расходы",
        "description": (
            "Третий шаг: себестоимость на единицу и платежи по графику, ФОТ с тумблерами взносов, "
            "административные/маркетинговые расходы и земельный налог."
        ),
        "sections": [
            {
                "title": "Себестоимость",
                "description": (
                    "Закупочная цена на единицу × продажи. Платежи по графику (variable_other) складываются отдельно. "
                    "Первоначальный запас из инвестиций сюда не копируется."
                ),
                "fields": [
                    _field(
                        "cost_mode",
                        "Режим себестоимости",
                        cost_mode,
                        type_="enum",
                        required=False,
                        display=_display(cost_mode, COST_MODES),
                        options=[{"value": k, "label": v} for k, v in COST_MODES.items()],
                        explain="Обычный режим анкеты — unit (на единицу). monthly — служебный/legacy.",
                    ),
                    _field(
                        "variable_costs",
                        "Затраты на единицу продукции",
                        [_variable_cost_item(row, index) for index, row in enumerate(variable_costs)],
                        type_="list",
                        required=False,
                        display=f"{len(variable_costs)} поз.",
                        explain="Список {product, cost_type, price}. Пусто допустимо, если нет переменной себестоимости.",
                    ),
                    _field(
                        "variable_other",
                        "Переменные платежи по графику",
                        [_expense_item(row, index) for index, row in enumerate(variable_other)],
                        type_="list",
                        required=False,
                        display=f"{len(variable_other)} поз.",
                        explain=(
                            "Доп. платежи себестоимости с period. "
                            "«Каждые 2 месяца» — платёж через один месяц, не сумма за два."
                        ),
                    ),
                    _field(
                        "zero_cogs_confirmed",
                        "Подтверждено: переменной себестоимости нет",
                        cogs["confirmed"],
                        type_="boolean",
                        required=cogs["zero"],
                        display="да" if cogs["confirmed"] else "нет",
                        visible=cogs["zero"],
                        explain=(
                            "Галочка показывается и обязательна, только если продажи > 0, а себестоимость "
                            "(на единицу и по графику) = 0. Без неё модель не сохраняется. "
                            "Подходит для бизнеса без закупок (услуги, консалтинг, софт); для торговли и производства "
                            "нулевая себестоимость обычно ошибка анкеты."
                        ),
                    ),
                ],
                "checks": {
                    "zero_cogs": cogs["zero"],
                    "zero_cogs_confirmed": cogs["confirmed"],
                    "products_without_unit_cost": cogs["products_without_cost"],
                    "warning": (
                        "Себестоимость = 0 при продажах > 0: вся выручка уходит в валовую прибыль. "
                        + ("Пользователь подтвердил допущение." if cogs["confirmed"] else "Допущение не подтверждено.")
                    ) if cogs["zero"] else None,
                },
            },
            {
                "title": "Зарплаты (ФОТ)",
                "description": "Должности, оклад, доля от выручки, тумблеры взносов и ставки",
                "fields": [
                    _field(
                        "salary",
                        "Штатное расписание",
                        [_salary_item(row, index) for index, row in enumerate(salary)],
                        type_="list",
                        required=False,
                        display=f"{len(salary)} должн.",
                        explain=(
                            "Пустой штат допустим. Смотри invest/industrial/*_exempt у каждой должности. "
                            "Ставки взносов — поля ниже (общие для проекта)."
                        ),
                    ),
                    *payroll_fields,
                ],
            },
            {
                "title": "Постоянные расходы",
                "description": "Административные, маркетинг и земельный налог",
                "fields": [
                    _field(
                        "admin_expenses",
                        "Административные расходы",
                        [_expense_item(row, index) for index, row in enumerate(admin_expenses)],
                        type_="list",
                        required=False,
                        display=f"{len(admin_expenses)} поз.",
                        explain=(
                            "Обычные адм. расходы без земельных строк. type_expense: 0 прочее, 2 аренда. "
                            "invest — платить с начала проекта."
                        ),
                    ),
                    _field(
                        "marketing_expenses",
                        "Маркетинг",
                        [_expense_item(row, index) for index, row in enumerate(marketing)],
                        type_="list",
                        required=False,
                        display=f"{len(marketing)} поз.",
                        explain="Маркетинговые/рекламные расходы по графику period.",
                    ),
                    _field(
                        "land_tax",
                        "Земельный налог",
                        [_expense_item(row, index) for index, row in enumerate(land_tax)],
                        type_="list",
                        required=False,
                        display=f"{len(land_tax)} участок.",
                        explain=(
                            "Строки из admin_expenses с land_tax=true (или именем «земельн…»). "
                            "В UI отдельная вкладка; в данных хранятся внутри admin_expenses."
                        ),
                    ),
                ],
            },
        ],
    }


def _amount_map(source) -> dict:
    data = source if isinstance(source, dict) else {}
    return {key: _as_num(data.get(key)) or 0 for key, *_ in INVEST_FIELDS}


def _rows_sum(rows, price_key="price") -> float:
    total = 0.0
    for row in rows or []:
        if not isinstance(row, dict):
            continue
        total += float(_as_num(row.get(price_key)) or 0)
    return total


def _expense_launch_total(row: dict, months: float) -> float:
    value = float(_as_num(row.get("value")) or 0)
    window = max(0.0, float(months or 0))
    if value <= 0 or window <= 0:
        return 0.0
    period = _as_int(row.get("period"), 1)
    if period is None or period <= 0:
        return value
    payments = int((window - 1) // period) + 1
    return value * max(0, payments)


def _sum_expense_launch(rows, months: float) -> float:
    return sum(_expense_launch_total(row, months) for row in (rows or []) if isinstance(row, dict))


def _inventory_total(form: dict) -> float:
    return _rows_sum(form.get("inventory") or [])


def _capex_total(form: dict, key: str) -> float:
    """Сумма CAPEX к покупке: already_owned в новую потребность не входит."""
    source_key = CAPEX_SOURCE.get(key)
    if not source_key:
        return 0.0
    total = 0.0
    for row in form.get(source_key) or []:
        if not isinstance(row, dict) or row.get("already_owned"):
            continue
        total += float(_as_num(row.get("price")) or 0)
    return total


def _average_monthly_cogs(form: dict) -> float:
    """Упрощённо: сумма (среднемес. объём × себест./ед.) по продуктам."""
    unit_cost: dict[str, float] = {}
    for row in form.get("variable_costs") or []:
        if not isinstance(row, dict):
            continue
        name = str(row.get("product") or "").strip()
        if not name:
            continue
        unit_cost[name] = unit_cost.get(name, 0.0) + float(_as_num(row.get("price")) or 0)
    monthly = _as_int(form.get("time_param"), 3) == 3
    total = 0.0
    for row in form.get("volume_sales") or []:
        if not isinstance(row, dict):
            continue
        name = str(row.get("name") or "").strip()
        if not name:
            continue
        count = float(_as_num(row.get("count")) or 0)
        volume = count if monthly else count / 12.0
        total += volume * unit_cost.get(name, 0.0)
    return total


def _wages_monthly(form: dict) -> float:
    total = 0.0
    for row in form.get("salary") or []:
        if not isinstance(row, dict):
            continue
        total += float(_as_num(row.get("count")) or 0) * float(_as_num(row.get("salary")) or 0)
    return total


def _investment_lines(form: dict) -> list[dict]:
    financing = _as_int(form.get("financing"), 1)
    with_own = _as_int(form.get("own_invest"), 0) == 1
    mixed = financing == 4
    own = _amount_map(form.get("own"))
    attracted = _amount_map(form.get("attracted"))
    credit = _amount_map(form.get("credit"))
    stock = _inventory_total(form)
    stock_listed = stock > 0

    admin = [row for row in (form.get("admin_expenses") or []) if isinstance(row, dict)]
    expense_rows = {
        "advertising": [row for row in (form.get("marketing_expenses") or []) if isinstance(row, dict)],
        "rental": [row for row in admin if _as_int(row.get("type_expense"), 0) == 2],
        "other": [row for row in admin if _as_int(row.get("type_expense"), 0) != 2],
    }

    lines = []
    for key, label, kind in INVEST_FIELDS:
        months_key = OPERATING_MONTH_KEYS.get(key)
        raw_months = form.get(months_key) if months_key else None
        if key == "raw" and stock_listed:
            months = 1
            months_editable = False
        elif kind == "operating":
            months = 1 if raw_months in (None, "") else max(0, int(_as_num(raw_months) or 0))
            months_editable = True
        else:
            months = 1
            months_editable = False

        if key in ("advertising", "rental", "other"):
            total = _sum_expense_launch(expense_rows[key], months)
            base = (total / months) if months > 0 else total
            source = "expenses_with_period"
        elif key == "raw":
            base = stock if stock_listed else _average_monthly_cogs(form)
            total = base if stock_listed else base * months
            source = "inventory_list" if stock_listed else "cogs_monthly_x_months"
        elif key == "wages":
            base = _wages_monthly(form)
            total = base * months
            source = "salary_monthly_x_months"
        else:
            base = _capex_total(form, key)
            total = base
            source = "capex_from_step2"

        own_value = own[key] if with_own else 0
        attracted_value = attracted[key]
        # В UI привлекаемые = max(0, total - own); в форме могут уже быть посчитаны.
        attracted_calc = max(0.0, float(total) - float(own_value or 0))
        credit_value = credit[key] if mixed else (attracted_calc if financing == 2 else 0)
        if financing == 2:
            credit_value = attracted_calc
        elif not mixed:
            credit_value = 0
        investor_value = max(0.0, attracted_calc - float(credit_value or 0)) if financing in (1, 4) else 0

        if float(total or 0) <= 0:
            continue

        lines.append(
            {
                "key": key,
                "label": label,
                "kind": kind,
                "source": source,
                "months_key": months_key,
                "months": months if kind == "operating" else None,
                "months_editable": months_editable,
                "months_value_in_form": _as_num(raw_months) if months_key else None,
                "base_monthly_or_unit": _as_num(base),
                "total_needed": _as_num(total),
                "own": _as_num(own_value),
                "attracted_in_form": _as_num(attracted_value),
                "attracted_calc": _as_num(attracted_calc),
                "credit": _as_num(credit_value) if financing in (2, 4) else 0,
                "investor_calc": _as_num(investor_value),
                "grant_calc": _as_num(attracted_calc) if financing == 3 else 0,
                "explain": (
                    "Строка потребности инвестиций. total_needed считается из шагов 2–3 "
                    "(CAPEX списки / ФОТ×мес. / расходы с учётом period). "
                    "own — свои деньги (если own_invest=1). "
                    "attracted_calc = total − own. "
                    "При financing=4 credit вводит клиент, investor_calc = attracted − credit. "
                    "При financing=2 вся привлекаемая часть — кредит; при 3 — грант."
                    + (
                        " raw — закупка первоначального запаса, не денежный резерв. В ДДС отдельного оттока нет: "
                        "в первом месяце поставщику платится MAX(запас, закупка месяца), остаток запаса (колонка P "
                        "«Запас») гасит закупки следующих месяцев. Итого оплат = закупки + остаток запаса, без двойного учёта."
                        if key == "raw"
                        else ""
                    )
                ),
            }
        )
    return lines


def build_step4(form: dict) -> dict:
    financing = _as_int(form.get("financing"), 1)
    own_invest = _as_int(form.get("own_invest"), 0)
    with_own = own_invest == 1
    lines = _investment_lines(form)
    investment_total = sum(float(line.get("total_needed") or 0) for line in lines)
    own_total = sum(float(line.get("own") or 0) for line in lines) if with_own else 0
    attracted_total = sum(float(line.get("attracted_calc") or 0) for line in lines)
    credit_total = sum(float(line.get("credit") or 0) for line in lines)
    investor_total = sum(float(line.get("investor_calc") or 0) for line in lines)
    grant_total = sum(float(line.get("grant_calc") or 0) for line in lines)

    month_fields = [
        _field(
            months_key,
            f"Месяцев запуска: {label}",
            _as_num(form.get(months_key)),
            type_="number",
            unit="мес.",
            required=False,
            visible=True,
            display=_display(form.get(months_key), suffix=" мес."),
            explain=(
                f"Сколько месяцев операционной статьи «{label}» заложить в стартовые инвестиции. "
                "Для advertising/rental/other учитывается period расхода (год ≠ × месяцев). "
                "Для raw при заполненном inventory месяцы не умножаются (разовая сумма запаса)."
            ),
        )
        for key, label, kind in INVEST_FIELDS
        if kind == "operating"
        for months_key in [OPERATING_MONTH_KEYS[key]]
    ]

    return {
        "step": 4,
        "title": "Собственные и привлекаемые средства",
        "description": (
            "Четвёртый шаг: нужны ли свои деньги, на сколько месяцев заложить операционные расходы, "
            "и как разделить потребность на свои / инвестора / кредит / грант."
        ),
        "sections": [
            {
                "title": "Свои вложения",
                "description": "Если да, укажите только свою часть. Остальное посчитается само.",
                "fields": [
                    _field(
                        "own_invest",
                        "Участвуете своими деньгами",
                        own_invest,
                        type_="enum",
                        required=True,
                        display=_display(own_invest, YES_NO),
                        options=[{"value": k, "label": v} for k, v in YES_NO.items()],
                        explain="1 — да, клиент заполняет own[] по статьям; 0 — всё привлекаемое.",
                    ),
                ],
            },
            {
                "title": "Месяцы запуска (операционные статьи)",
                "description": "Множители для ФОТ/рекламы/аренды/прочего/сырья без списка запаса",
                "fields": month_fields,
            },
            {
                "title": "На что нужны инвестиции",
                "description": "Потребность по статьям и разложение на источники",
                "fields": [
                    _field(
                        "investment_lines",
                        "Строки инвестиций",
                        lines,
                        type_="list",
                        required=False,
                        display=f"{len(lines)} статей · нужно {_as_num(investment_total)}",
                        explain=(
                            "Только статьи с total_needed>0. CAPEX берётся из шага 2; "
                            "операционные — из шага 3 × months_* (с учётом period). "
                            "Проверь: own ≤ total; при financing=4 credit ≤ attracted_calc."
                        ),
                    ),
                    _field(
                        "own",
                        "Свои суммы по статьям (форма)",
                        _amount_map(form.get("own")),
                        type_="object",
                        required=False,
                        visible=with_own,
                        explain="Редактируемые свои вложения. Игнорируются, если own_invest=0.",
                        note=None if with_own else "Скрыто/обнуляется: свои деньги не участвуют.",
                    ),
                    _field(
                        "attracted",
                        "Привлекаемые суммы по статьям (форма)",
                        _amount_map(form.get("attracted")),
                        type_="object",
                        required=False,
                        explain="Обычно total − own. В UI считается автоматически.",
                    ),
                    _field(
                        "credit",
                        "Кредитная часть по статьям (форма)",
                        _amount_map(form.get("credit")),
                        type_="object",
                        required=False,
                        visible=financing == 4,
                        explain="Только при financing=4 (инвестор+кредит): сколько из привлекаемых берётся кредитом.",
                        note=None if financing == 4 else "Редактируется в UI только при смешанном финансировании.",
                    ),
                    _field(
                        "totals",
                        "Итоги шага",
                        {
                            "investment_total": _as_num(investment_total),
                            "own_total": _as_num(own_total),
                            "attracted_total": _as_num(attracted_total),
                            "credit_total": _as_num(credit_total),
                            "investor_total": _as_num(investor_total),
                            "grant_total": _as_num(grant_total),
                            "financing": financing,
                            "financing_display": _display(financing, FINANCING),
                        },
                        type_="object",
                        required=False,
                        display=(
                            f"нужно {_as_num(investment_total)}; свои {_as_num(own_total)}; "
                            f"привлеч. {_as_num(attracted_total)}"
                        ),
                        explain="Сводка для проверки сходимости источников и потребности.",
                    ),
                ],
            },
        ],
    }


def build_audit_payload(model_id: int, model_name: str, form: dict, *, steps: list[int] | None = None) -> dict:
    wanted = steps or [1, 2, 3, 4]
    form = form or {}
    builders = {1: build_step1, 2: build_step2, 3: build_step3, 4: build_step4}
    built = [builders[number](form) for number in wanted if number in builders]
    included = [item["step"] for item in built]

    return {
        "format": "nc-fin-model-audit-json",
        "format_version": 1,
        "purpose": "Выгрузка анкеты финансовой модели для ИИ-аудита полноты и корректности заполнения.",
        "instructions_for_ai": (
            "Это JSON анкеты NC Consulting FEM. Проверь шаг(и) из steps: каждое поле из sections[].fields, "
            "не пропускай hidden/неактивные — у них visible_in_form=false и note с причиной. "
            "Смотри value (сырое) и display (человекочитаемое). options — допустимые значения enum. "
            "explain — смысл поля в модели. required=true значит поле обязательно при visible_in_form=true. "
            "Списки (type=list/object) разверни и проверь каждый элемент. "
            "Не выдумывай поля вне JSON. Если чего-то не хватает для аудита шага — явно перечисли. "
            f"Сейчас в выгрузке шаги: {', '.join(str(n) for n in included) or 'нет'} (полная анкета 1–4)."
        ),
        "exported_at": datetime.now(timezone.utc).isoformat(),
        "model": {"id": model_id, "name": model_name},
        "steps_included": included,
        "steps": built,
    }
