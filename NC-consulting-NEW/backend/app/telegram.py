import logging
import re
from html import escape
from zoneinfo import ZoneInfo

import httpx

from app.config import settings
from app.models import User

logger = logging.getLogger(__name__)

LOCAL_TZ = ZoneInfo("Asia/Almaty")


def esc(value: str | None) -> str:
    # quote=False: кавычки в названии компании должны остаться кавычками, а не &quot;
    return escape(value or "", quote=False)


def format_phone(phone: str | None) -> str:
    if not phone or not phone.strip():
        return "не указан"
    digits = re.sub(r"\D", "", phone)
    local = digits[1:] if len(digits) == 11 and digits[0] in "78" else digits
    if len(local) == 10:
        return f"+7 {local[:3]} {local[3:6]}-{local[6:8]}-{local[8:10]}"
    return phone.strip()


def format_company(company: str | None) -> str:
    if not company or not company.strip():
        return "не указана"
    return re.sub(r'"([^"]*)"', r"«\1»", company.strip())


def send_message(text: str) -> None:
    if not settings.telegram_bot_token or not settings.telegram_chat_id:
        return
    try:
        response = httpx.post(
            f"https://api.telegram.org/bot{settings.telegram_bot_token}/sendMessage",
            json={
                "chat_id": settings.telegram_chat_id,
                "text": text,
                "parse_mode": "HTML",
                "disable_web_page_preview": True,
            },
            timeout=10,
        )
        response.raise_for_status()
    except httpx.HTTPError:
        logger.exception("Failed to send Telegram notification")


def notify_refill(user: User, full_name: str, phone: str, amount: int, comment: str) -> None:
    text = (
        "<b>Запрос на пополнение через Kaspi</b>\n\n"
        f"<b>Кабинет:</b> {user.id}\n"
        f"<b>Клиент:</b> {esc(user.full_name)}\n"
        f"<b>Email:</b> {esc(user.email)}\n"
        f"<b>ФИО в заявке:</b> {esc(full_name)}\n"
        f"<b>Телефон Kaspi:</b> {esc(phone)}\n"
        f"<b>Сумма:</b> {amount} ₸\n"
        f"<b>Комментарий:</b> {esc(comment) or '—'}"
    )
    send_message(text)


def notify_payment(user: User, amount: int, service: str, provider: str = "wooppay") -> None:
    title = "Ioka" if provider == "ioka" else "Wooppay"
    text = (
        f"<b>Оплата через {title} прошла</b>\n\n"
        f"<b>Кабинет:</b> {user.id}\n"
        f"<b>Клиент:</b> {esc(user.full_name)}\n"
        f"<b>Услуга:</b> {esc(service)}\n"
        f"<b>Сумма:</b> {amount} ₸"
    )
    send_message(text)


def notify_new_user(user: User) -> None:
    created = user.created_at.astimezone(LOCAL_TZ).strftime("%d.%m.%Y | %H:%M")
    line = "━━━━━━━━━━━━━━━━"
    text = (
        f"{line}\n"
        "🆕 <b>НОВАЯ РЕГИСТРАЦИЯ</b>\n"
        f"{line}\n"
        "\n"
        f"🆔 <b>ID пользователя:</b> #{user.id}\n"
        "\n"
        f"👤 <b>ФИО:</b> {esc(user.full_name)}\n"
        f"🏢 <b>Компания:</b> {esc(format_company(user.company))}\n"
        f"📂 <b>Кабинет:</b> №{user.id}\n"
        "\n"
        f"📧 <b>Email:</b> {esc(user.email)}\n"
        f"📱 <b>Телефон:</b> {esc(format_phone(user.phone))}\n"
        "\n"
        f"🕒 <b>Дата:</b> {created}"
    )
    send_message(text)
