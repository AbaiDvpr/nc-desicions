"""Валюты анкеты и компании. Суммы уже в выбранной валюте, курс их не пересчитывает."""

from typing import Literal

CabinetCode = Literal["kzt", "usd", "eur", "rub", "uzs", "kgs", "tjs", "aed"]
ModelCode = Literal["KZT", "USD", "EUR", "RUB", "UZS", "KGS", "TJS", "AED"]

CODE_PATTERN = r"^(kzt|usd|eur|rub|uzs|kgs|tjs|aed)$"

# code -> название, знак, ISO, единица в таблице, «суммы в …»
_ITEMS: dict[str, tuple[str, str, str, str, str]] = {
    "kzt": ("тенге", "₸", "KZT", "тенге", "тенге"),
    "usd": ("доллар США", "$", "USD", "доллар", "долларах США"),
    "eur": ("евро", "€", "EUR", "евро", "евро"),
    "rub": ("рубль", "₽", "RUB", "рубль", "рублях"),
    "uzs": ("узбекский сум", "сум", "UZS", "сум", "узбекских сумах"),
    "kgs": ("кыргызский сом", "сом", "KGS", "сом", "кыргызских сомах"),
    "tjs": ("таджикский сомони", "смн", "TJS", "сомони", "таджикских сомони"),
    "aed": ("дирхам ОАЭ", "Dh", "AED", "дирхам", "дирхамах ОАЭ"),
}

SIGN_BY_ISO = {item[2]: item[1] for item in _ITEMS.values()}


def _item(code: str) -> tuple[str, str, str, str, str]:
    return _ITEMS.get(str(code or "kzt").strip().lower(), _ITEMS["kzt"])


def pair(code: str) -> tuple[str, str]:
    name, sign, *_rest = _item(code)
    return name, sign


def iso(code: str) -> str:
    return _item(code)[2]


def unit(code: str) -> str:
    return _item(code)[3]


def in_phrase(code: str) -> str:
    key = str(code or "KZT").strip()
    if key.isupper():
        for item in _ITEMS.values():
            if item[2] == key:
                return item[4]
        return _ITEMS["kzt"][4]
    return _item(key)[4]


def prose(code: str) -> str:
    name, *_rest = _item(code)
    return "доллары США" if str(code or "").strip().lower() == "usd" else name


def choice_label(code: str) -> str:
    name, sign, iso_code, *_rest = _item(code)
    return f"{sign} {name[:1].upper()}{name[1:]} ({iso_code})"


AUDIT_CURRENCIES = {code: choice_label(code) for code in _ITEMS}
MONEY = {code: pair(code) for code in _ITEMS}
