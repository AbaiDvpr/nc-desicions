"""Каталог госпрограмм Damu. Сайт отдаёт карточки, страница программы — срок и ставку."""

from __future__ import annotations

import re
import threading
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from html import unescape

SOURCE = "https://damu.kz/bitrix/templates/Corp_Damu_copy/ajax/load_programs.php?TAB=all&lang=ru"
PAGE = "https://damu.kz/ru/programmi?tab=all"
_CACHE_SECONDS = 6 * 60 * 60
_lock = threading.Lock()
_cache: dict | None = None
_cached_at = 0.0

_KINDS = (
    ("/subsidy/", "subsidy", "Субсидия"),
    ("/guarantee/", "guarantee", "Гарантия"),
    ("/loans/", "loan", "Кредитование"),
    ("/leasing/", "leasing", "Лизинг"),
)


def _plain(html: str) -> str:
    text = re.sub(r"<[^>]+>", " ", html)
    text = unescape(text).replace("\xa0", " ")
    return re.sub(r"\s+", " ", text).strip()


def _kind(url: str) -> tuple[str, str]:
    for needle, code, label in _KINDS:
        if needle in url:
            return code, label
    return "other", "Программа"


def _rate(text: str) -> float | None:
    match = re.search(r"Процентная ставка:\s*(\d+(?:[.,]\d+)?)", text)
    if not match:
        return None
    return float(match.group(1).replace(",", "."))


def _amount(text: str) -> str:
    match = re.search(r"Сумма:\s*(.+?)(?:Процентная ставка:|$)", text)
    return match.group(1).strip(" .") if match else ""


def _detail(text: str) -> str:
    for label in ("Размер субсидии:", "Размер гарантии:"):
        match = re.search(rf"{label}\s*(.+?)(?:Сумма:|Процентная ставка:|$)", text)
        if match:
            return match.group(1).strip(" .")
    if "Льготное кредитование" in text:
        return "Льготное кредитование"
    if "Для снижения ставки" in text:
        return "Для снижения ставки"
    return ""


def _years(months: int | None) -> str:
    if not months:
        return ""
    if months % 12:
        return f"{months} мес."
    years = months // 12
    tail = years % 100
    if 11 <= tail <= 14:
        word = "лет"
    elif years % 10 == 1:
        word = "год"
    elif years % 10 in (2, 3, 4):
        word = "года"
    else:
        word = "лет"
    return f"{years} {word} ({months} мес.)"


def _conditions(text: str) -> str:
    for marker in (
        "Ключевые параметры",
        "Ставка для предпринимателя",
        "Процентная ставка",
        "Условия Сумма",
        "Условия Срок",
    ):
        index = text.find(marker)
        if index >= 0:
            return text[max(0, index - 40) : index + 1100]
    index = text.rfind("Условия")
    if index >= 0:
        return text[index : index + 1100]
    return text[:1100]


def _page_rate(chunk: str, fallback: float | None) -> float | None:
    for pattern in (
        r"Ставка для предпринимателя\s+(\d+(?:[.,]\d+)?)",
        r"Процентная ставка(?:\s+не более)?\s+(\d+(?:[.,]\d+)?)",
    ):
        match = re.search(pattern, chunk)
        if match:
            return float(match.group(1).replace(",", "."))
    return fallback


def _purpose(chunk: str) -> str:
    match = re.search(
        r"(?:Цель финансирования|Целевое назначение)\s+(.+?)(?:\s+\*|Калькулятор|Участники|Параметр|\sСумма\b|$)",
        chunk,
    )
    return match.group(1).strip(" .")[:240] if match else ""


def _term(chunk: str) -> tuple[int | None, int | None, str]:
    subsidy = re.search(r"Срок субсидирования\s+(\d+)\s+год", chunk)
    window = re.search(r"От\s+(\d+)\s+до\s+(\d+)\s+мес", chunk)
    if subsidy and window:
        return int(window.group(1)), int(window.group(2)), "subsidy"
    calculator = re.search(
        r"Срок\s*\([^)]{0,40}\)\s*(\d+)\s*мес\.\s*(\d+)\s*мес\.",
        chunk,
    )
    if calculator:
        return int(calculator.group(1)), int(calculator.group(2)), "loan"
    cap = re.search(r"Срок\s+(?:до|не более|До)\s+(\d+)(?:-х)?\s+лет", chunk)
    if cap:
        return None, int(cap.group(1)) * 12, "loan"
    return None, None, ""


def _named(title: str) -> str:
    title = (title or "Программа").strip()
    if title.startswith("«"):
        return title
    return f"«{title}»"


def _bounds(low: str, high: str) -> str:
    low = low.rstrip(".")
    high = high.rstrip(".")
    if low and high:
        return f"Минимальный срок — {low}. Максимальный срок — {high}."
    if high:
        return f"Максимальный срок — {high}. Минимальный срок каталог не публикует."
    if low:
        return f"Минимальный срок — {low}. Максимальный срок каталог не публикует."
    return "Срок каталог не публикует."


def _logic(program: dict) -> str:
    title = _named(program.get("title") or "")
    rate = program.get("rate")
    amount = program.get("amount") or ""
    detail = program.get("detail") or ""
    purpose = program.get("purpose") or ""
    kind = program.get("kind")
    term_kind = program.get("term_kind") or ""
    low = _years(program.get("term_min_months"))
    high = _years(program.get("term_max_months"))
    money = f" Сумма программы: {amount}." if amount else ""
    goal = f" Назначение: {purpose}." if purpose else ""
    if term_kind == "subsidy" and (low or high):
        rate_line = f"Ставка для предпринимателя — {rate}%." if rate is not None else "Готовой ставки в каталоге нет."
        return (
            f"{title} снижает банковскую ставку. {rate_line} {_bounds(low, high)} "
            f"Это срок субсидии, без продления. Полный срок кредита задаётся отдельно в поле ниже.{money}{goal} "
            "Ставка в полях кредита равна ставке программы."
        )
    if kind == "guarantee":
        cover = f" Размер гарантии: {detail}." if detail else ""
        limit = f" {_bounds(low, high)}" if low or high else " Срок кредита каталог не задаёт."
        return (
            f"{title} закрывает часть залога.{cover}{limit}{money}{goal} "
            "Ставку программа не задаёт. Срок, если каталог его опубликовал, держится внутри минимума и максимума."
        )
    if kind == "leasing":
        rate_line = f"Ставка {rate}%." if rate is not None else "Ставку каталог не публикует."
        return (
            f"{title} — лизинг. {rate_line} {_bounds(low, high)}{money}{goal} "
            "В кредите на инвестиции ставка равна ставке программы, срок — только внутри минимума и максимума."
        )
    rate_line = f"Ставка для проекта — {rate}%." if rate is not None else "Ставку каталог не публикует."
    subsidy = f" Субсидия: {detail}." if kind == "subsidy" and detail else ""
    filled = "Ставка кредита равна ставке программы. " if rate is not None else "Ставку каталог не задаёт, её указывают в поле. "
    return (
        f"{title}. {rate_line} {_bounds(low, high)}{subsidy}{money}{goal} "
        f"{filled}Срок можно поставить только внутри этого диапазона."
    )


def _download_text(url: str) -> str:
    request = urllib.request.Request(url, headers={"User-Agent": "NC-Decision"})
    with urllib.request.urlopen(request, timeout=20) as response:
        html = response.read().decode("utf-8", "replace")
    return _plain(re.sub(r"<(script|style)\b[^>]*>.*?</\1>", " ", html, flags=re.S | re.I))


def _program_page(url: str) -> str:
    text = _download_text(url)
    if "Условия" in text and any(word in text for word in ("Срок", "Ставка", "Сумма")):
        return text
    extra = _download_text(url + ("&" if "?" in url else "?") + "view=body")
    return extra if len(extra) > len(text) else text


def _coverage(purpose: str, kind: str) -> tuple[bool, bool]:
    text = (purpose or "").lower().replace("ё", "е")
    if not text:
        return True, True
    working = bool(re.search(r"(?<![а-я])пос(?![а-я])|оборот", text))
    investment = bool(re.search(r"инвест|рефинанс|лизинг", text))
    if kind == "leasing":
        return True, False
    if not working and not investment:
        return True, True
    return investment, working


def _grace_cap(text: str) -> int | None:
    match = re.search(
        r"льгот\w*\s+период\s+по\s+погашению\s+основного\s+долга[:\s]+(?:не более|до)\s+(\d+)",
        text,
        re.I,
    )
    return int(match.group(1)) if match else None


def _term_span(text: str) -> tuple[int, int] | None:
    match = re.search(r"Срок\s+(\d+)\s*[–—-]\s*(\d+)\s+месяц", text)
    if not match:
        return None
    return int(match.group(1)), int(match.group(2))


def _enrich(program: dict) -> dict:
    page = ""
    try:
        page = _program_page(program["url"])
    except Exception:
        page = ""
    chunk = _conditions(page) if page else ""
    if chunk:
        program["rate"] = _page_rate(chunk, program.get("rate"))
        program["purpose"] = _purpose(chunk)
        low, high, term_kind = _term(chunk)
        # Субсидия: срок в калькуляторе — окно субсидии, не жёсткий срок всего кредита.
        if program.get("kind") == "subsidy" and term_kind == "loan":
            term_kind = "subsidy"
        program["term_min_months"] = low
        program["term_max_months"] = high
        program["term_kind"] = term_kind
    program.setdefault("purpose", "")
    program.setdefault("term_min_months", None)
    program.setdefault("term_max_months", None)
    program.setdefault("term_kind", "")
    if program.get("term_kind") != "subsidy":
        span = _term_span(page)
        if span and not program.get("term_min_months"):
            program["term_min_months"] = span[0]
            if not program.get("term_max_months"):
                program["term_max_months"] = span[1]
            if not program.get("term_kind"):
                program["term_kind"] = "loan"
    if program.get("kind") == "subsidy" and program.get("term_kind") != "subsidy":
        program["term_kind"] = "subsidy"
    investment, working = _coverage(program.get("purpose") or "", program.get("kind") or "")
    program["allows_investment"] = investment
    program["allows_wc"] = working
    program["grace_max_months"] = _grace_cap(page)
    program["logic"] = _logic(program)
    notes = []
    if program["grace_max_months"]:
        notes.append(f"Каникулы по телу долга — до {program['grace_max_months']} мес.")
    else:
        notes.append("Каникулы каталог не ограничивает, в модели они до 36 мес.")
    if not working:
        notes.append("Заём на оборотные средства программа не даёт.")
    elif not investment:
        notes.append("Программа только на оборотные средства, не на помещение и оборудование.")
    program["logic"] = f"{program['logic']} {' '.join(notes)}"[:800]
    return program


def parse_programs(html: str) -> list[dict]:
    programs = []
    seen: set[str] = set()
    for chunk in re.split(r'<div class="program-content">', html)[1:]:
        title_match = re.search(r"<h5>(.*?)</h5>", chunk, re.S)
        link_match = re.search(r'href="(/ru/programmi/[^"?#]+)"', chunk)
        if not title_match or not link_match:
            continue
        path = link_match.group(1).rstrip("/")
        slug = path.rsplit("/", 1)[-1][:80]
        if not slug or slug in seen:
            continue
        seen.add(slug)
        text = _plain(chunk.split("Подробнее")[0])
        kind, kind_label = _kind(path)
        programs.append(
            {
                "slug": slug,
                "title": _plain(title_match.group(1))[:240],
                "kind": kind,
                "kind_label": kind_label,
                "url": f"https://damu.kz{path}",
                "rate": _rate(text),
                "amount": _amount(text)[:120],
                "detail": _detail(text)[:300],
                "purpose": "",
                "term_min_months": None,
                "term_max_months": None,
                "term_kind": "",
                "logic": "",
                "allows_investment": True,
                "allows_wc": True,
                "grace_max_months": None,
            }
        )
    return programs


def _download() -> str:
    request = urllib.request.Request(SOURCE, headers={"User-Agent": "NC-Decision"})
    with urllib.request.urlopen(request, timeout=20) as response:
        return response.read().decode("utf-8", "replace")


def list_programs() -> dict:
    """Свежий список Damu. Если сайт молчит, отдаём прошлый удачный ответ."""
    global _cache, _cached_at
    now = time.time()
    with _lock:
        if _cache is not None and now - _cached_at < _CACHE_SECONDS:
            return _cache
        try:
            programs = parse_programs(_download())
            if programs:
                with ThreadPoolExecutor(max_workers=6) as pool:
                    programs = list(pool.map(_enrich, programs))
        except Exception:
            programs = []
        if programs:
            _cache = {"source": PAGE, "programs": programs, "stale": False}
            _cached_at = now
            return _cache
        if _cache is not None:
            return {**_cache, "stale": True}
    return {"source": PAGE, "programs": [], "stale": False}
