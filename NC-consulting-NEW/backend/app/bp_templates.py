"""Шаблоны бизнес-плана и один запрос к модели, которая пишет все текстовые разделы."""

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import BpSetting, BpTemplate

# Администратор выбирает провайдера и вариант. Клиент этот список не видит.
DEEPSEEK_MODELS = (("deepseek-ai/deepseek-v4.1-flash-1017", "DeepSeek V4.1 Flash"),)
OPENAI_MODELS = (
    ("gpt-5.6-terra", "GPT-5.6 Terra"),
    ("gpt-5.6-sol", "GPT-5.6 Sol"),
    ("gpt-5.6-luna", "GPT-5.6 Luna"),
    ("gpt-5.4", "GPT-5.4"),
    ("gpt-5.4-mini", "GPT-5.4 Mini"),
    ("gpt-4.1", "GPT-4.1"),
    ("gpt-4.1-mini", "GPT-4.1 Mini"),
    ("gpt-4o-mini", "GPT-4o Mini"),
)
PROVIDERS = (
    {"id": "deepseek", "label": "DeepSeek", "models": DEEPSEEK_MODELS},
    {"id": "openai", "label": "OpenAI", "models": OPENAI_MODELS},
)
DEFAULT_PROVIDER = "deepseek"
DEFAULT_MODEL = DEEPSEEK_MODELS[0][0]
_BY_MODEL = {
    model_id: (provider["id"], provider["label"], label)
    for provider in PROVIDERS
    for model_id, label in provider["models"]
}
COVERS = ("classic", "investor", "brief", "book")

MONTHS = (
    "",
    "январь",
    "февраль",
    "март",
    "апрель",
    "май",
    "июнь",
    "июль",
    "август",
    "сентябрь",
    "октябрь",
    "ноябрь",
    "декабрь",
)

LEGAL = {1: "ИП", 2: "ТОО"}
FINANCING = {1: "Инвестор", 2: "Кредит", 3: "Грант", 4: "Инвестор и кредит"}


def _section(key: str, title: str, source: str, prompt: str, sample: str) -> dict:
    return {"key": key, "title": title, "source": source, "prompt": prompt, "sample": sample}


def _sections(tone: str) -> list[dict]:
    voice = {
        "classic": "Пиши деловым языком, без рекламных лозунгов. 3–5 абзацев.",
        "investor": "Пиши для инвестора: вывод, деньги и риск. 3–5 абзацев.",
        "brief": "Пиши плотно, без вводных фраз. 2–3 абзаца.",
        "book": "Пиши как страницу годового отчёта: сначала вывод, потом факты. 2–4 абзаца.",
        "bank": "Пиши как раздел банковского бизнес-плана. Сначала вывод, потом абзацы и перечни через дефис. 6–10 абзацев. Числа только из кейса.",
    }[tone]
    stop = "Не пиши, чего в кейсе нет, и не перечисляй пустые поля. Чужой темой раздел не заполняй. "
    return [
        _section("title", "Титульный лист", "facts", "", "Название, стоимость проекта, привлекаемая сумма и отметка о конфиденциальности."),
        _section("summary", "Выводы", "ai", f"Раздел «Выводы». Что продаётся, кому, какая сумма привлекается и на какие статьи, какой результат сверки месяца. Если сверка положительная, не называй проект убытком. NPV не выдумывай. {stop}{voice}", "Вывод по продукту, сумме и результату сверки."),
        _section("company", "Информация о хозяйствующем субъекте", "facts", "", "Форма, налог, деятельность и численность."),
        _section("project", "Общие исходные данные и условия", "facts", "", "Сумма, срок, структура финансирования и условия кредита, если он есть."),
        _section("product", "Описание предлагаемых услуг", "ai", f"Раздел «Реализация продукции». По каждой позиции из кейса: объём, цена, себестоимость и кому она нужна. Новые позиции не добавляй. Лицензии и стандарты не выдумывай. {stop}{voice}", "Каждая позиция со своей ценой и себестоимостью."),
        _section("market", "Ёмкость и динамика рынка", "ai", f"Раздел про покупателя и спрос, которые следуют из продукта и цены. Объём рынка в деньгах не выдумывай и не пиши, что его нет. Выручку и штат не пересказывай. {stop}{voice}", "Кому продаётся продукт и какой спрос виден по цене."),
        _section("competitors", "Конкурентные преимущества", "ai", f"Чем предложение отличается по цене и составу из кейса. Названия чужих компаний не выдумывай. Не пиши, что списка конкурентов нет. SWOT не составляй. {stop}{voice}", "Отличие предложения по цене и составу."),
        _section("marketing", "Маркетинговая стратегия", "ai", f"Как привлекаются клиенты по статьям рекламы из кейса. Направления перечисли через дефис. CAC не считай. Инвестиции и штат не повторяй. {stop}{voice}", "Каналы и суммы рекламы из заявки."),
        _section("sales", "Прогнозы продаж", "facts", "", "Годовой план доходов с учётом сезона и загрузки."),
        _section("operations", "Процесс работы", "ai", f"Как выполняется работа по помещениям и оборудованию из кейса. Поставщиков не добавляй. Не пиши, каких сведений о технологии нет. {stop}{voice}", "Работа по помещениям и оборудованию из заявки."),
        _section("suppliers", "План расходов", "facts", "", "Закупки, постоянные расходы и фонд оплаты."),
        _section("team", "Персонал", "facts", "", "Должности, численность и оклад."),
        _section("use", "Цель финансирования", "ai", f"Куда идут привлечённые деньги по статьям кейса. Затем, что даёт реализация: услуги и рабочие места, если они есть в штате. Новых сумм не добавляй. Если источник — инвестор, не называй деньги кредитом. {stop}{voice}", "Статьи, на которые идут привлечённые деньги."),
        _section("funding", "Структура финансирования", "facts", "", "Собственные и привлечённые средства по источникам."),
        _section("collateral", "Обеспечение", "facts", "", "Условия кредита, если он выбран."),
        _section("finance", "План доходов", "facts", "", "Годовая выручка по сезону и загрузке."),
        _section("efficiency", "Оценка вложений", "facts", "", "Сверка месяца при 100% и при 50% загрузки."),
        _section("risks", "Анализ рисков", "ai", f"Институциональные, сбытовые и финансовые риски, которые следуют из проверок кейса и источника денег. Для каждого: в чём риск и какая мера видна по заявке. Вероятности в процентах не ставь. Если сверка месяца положительная, не называй проект убытком. Если источник — инвестор, это привлечённый капитал, не кредит. {stop}{voice}", "Риски и меры по данным заявки."),
        _section("timeline", "Срок реализации", "facts", "", "Старт продаж, полный объём и горизонт."),
        _section("appendices", "Приложения", "facts", "", "Реестры продукции, штата, расходов и плана доходов."),
    ]


DEFAULTS = [
    {
        "slug": "classic",
        "name": "#001",
        "description": "Универсальный план: все применимые разделы по данным модели. Обложка деловая.",
        "cover": "classic",
        "accent": "#1e3a5f",
        "sort_order": 1,
        "sections": _sections("classic"),
    },
    {
        "slug": "investor",
        "name": "#002",
        "description": "Акцент на доходности, дивидендах и риске. Залог не показывается, если кредита нет.",
        "cover": "investor",
        "accent": "#641aea",
        "sort_order": 2,
        "sections": _sections("investor"),
    },
    {
        "slug": "brief",
        "name": "#003",
        "description": "Те же разделы универсального плана, текст модели короче. Обложка плотнее.",
        "cover": "brief",
        "accent": "#0f766e",
        "sort_order": 3,
        "sections": _sections("brief"),
    },
    {
        "slug": "book",
        "name": "#004",
        "description": "Те же разделы универсального плана. Тёмная обложка и страница показателей.",
        "cover": "book",
        "accent": "#0a2140",
        "sort_order": 4,
        "sections": _sections("book"),
    },
    {
        "slug": "bank",
        "name": "#005",
        "description": "Банковский план: кредит, платёжеспособность и обеспечение. Раздел залога есть всегда.",
        "cover": "classic",
        "accent": "#1e3a5f",
        "sort_order": 5,
        "sections": _sections("bank"),
    },
]


def provider_for_model(model: str) -> str | None:
    row = _BY_MODEL.get(model)
    return row[0] if row else None


def model_label(model: str) -> str:
    row = _BY_MODEL.get(model)
    if row is None:
        return model
    return f"{row[1]} · {row[2]}"


def providers_payload() -> list[dict]:
    return [
        {
            "id": item["id"],
            "label": item["label"],
            "models": [{"id": model_id, "label": label} for model_id, label in item["models"]],
        }
        for item in PROVIDERS
    ]


def ensure_bp(db: Session) -> None:
    setting = db.get(BpSetting, 1)
    if setting is None:
        db.add(BpSetting(id=1, provider=DEFAULT_PROVIDER, model=DEFAULT_MODEL))
    else:
        owner = provider_for_model(setting.model)
        if owner is None:
            setting.provider = DEFAULT_PROVIDER
            setting.model = DEFAULT_MODEL
        elif setting.provider != owner:
            setting.provider = owner
    rows = {row.slug: row for row in db.scalars(select(BpTemplate))}
    if not rows:
        for item in DEFAULTS:
            db.add(BpTemplate(**item))
    else:
        for item in DEFAULTS:
            row = rows.get(item["slug"])
            if row is None:
                db.add(BpTemplate(**item))
                continue
            if row.name != item["name"]:
                row.name = item["name"]
            if row.description != item["description"]:
                row.description = item["description"]
            if row.sections != item["sections"]:
                row.sections = item["sections"]
    db.commit()


def setting_payload(row: BpSetting) -> dict:
    return {
        "provider": row.provider,
        "model": row.model,
        "label": model_label(row.model),
        "providers": providers_payload(),
    }


def template_payload(row: BpTemplate) -> dict:
    return {
        "id": row.id,
        "slug": row.slug,
        "name": row.name,
        "description": row.description,
        "cover": row.cover,
        "accent": row.accent,
        "sections": row.sections or [],
        "sort_order": row.sort_order,
    }


def _month(value: str) -> str:
    text = str(value or "")
    if len(text) < 7 or not text[5:7].isdigit():
        return ""
    month = int(text[5:7])
    if month < 1 or month > 12:
        return text[:7]
    return f"{MONTHS[month]} {text[:4]}"


def facts_from_form(form: dict) -> dict:
    legal = LEGAL.get(int(form.get("legal_form") or 0), "")
    financing = FINANCING.get(int(form.get("financing") or 0), "")
    start = _month(form.get("planning_from"))
    end = _month(form.get("planning_to"))
    products = []
    for row in form.get("volume_sales") or []:
        name = str(row.get("name") or "").strip()
        if not name:
            continue
        products.append(
            {
                "name": name,
                "price": f"{int(float(row.get('price') or 0)):,}".replace(",", " "),
                "volume": f"{int(float(row.get('count') or 0)):,}".replace(",", " "),
            }
        )
    loan = ""
    if int(form.get("financing") or 0) in (2, 4) and form.get("loan_term"):
        loan = f"{int(form.get('loan_term') or 0)} мес., ставка {form.get('loan_rate') or 0}%"
        program = form.get("gov_program") or {}
        title = str(program.get("title") or "").strip() if isinstance(program, dict) else ""
        if title:
            shown = title if title.startswith("«") else f"«{title}»"
            loan = f"{loan}. Госпрограмма {shown}"
            if program.get("allows_wc") is False:
                loan = f"{loan}, оборотные средства отсутствуют"
        else:
            loan = f"{loan}. Без госпрограммы"
    return {
        "name": str(form.get("name") or "Проект"),
        "legal": legal,
        "financing": financing,
        "period": " — ".join(part for part in (start, end) if part),
        "products": products[:8],
        "loan": loan,
    }


def demo_facts() -> dict:
    return {
        "name": "Кофейня «Бариста»",
        "legal": "ТОО",
        "financing": "Инвестор",
        "period": "январь 2027 — декабрь 2030",
        "products": [
            {"name": "Капучино", "price": "1 400", "volume": "3 200"},
            {"name": "Завтрак", "price": "2 900", "volume": "900"},
        ],
        "loan": "",
    }


def request_body(template: dict, facts: dict, model: str) -> dict:
    """Один запрос: все текстовые разделы сразу. Цифры модель не выдумывает, они уже в фактах."""
    blocks = []
    for section in template.get("sections") or []:
        if section.get("source") != "ai":
            continue
        blocks.append(f"Раздел «{section.get('title')}». {section.get('prompt') or ''}".strip())
    return {
        "model": model,
        "messages": [
            {
                "role": "system",
                "content": (
                    "Ты пишешь бизнес-план на русском по фактам финансовой модели. "
                    "Не добавляй суммы, которых нет в фактах. Верни JSON: объект, ключ — key раздела, значение — текст."
                ),
            },
            {
                "role": "user",
                "content": "\n\n".join(
                    [
                        f"Проект: {facts.get('name')}",
                        f"Форма: {facts.get('legal') or '—'}",
                        f"Финансирование: {facts.get('financing') or '—'}",
                        f"Период: {facts.get('period') or '—'}",
                        f"Кредит: {facts.get('loan') or '—'}",
                        "Позиции: "
                        + (
                            "; ".join(
                                f"{item['name']} — {item['volume']} по {item['price']}"
                                for item in facts.get("products") or []
                            )
                            or "—"
                        ),
                        *blocks,
                    ]
                ),
            },
        ],
    }
