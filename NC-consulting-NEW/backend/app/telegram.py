import logging
from html import escape
from zoneinfo import ZoneInfo

import httpx

from app.config import settings
from app.models import User

logger = logging.getLogger(__name__)

LOCAL_TZ = ZoneInfo("Asia/Almaty")


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
        f"<b>Клиент:</b> {escape(user.full_name)}\n"
        f"<b>Email:</b> {escape(user.email)}\n"
        f"<b>ФИО в заявке:</b> {escape(full_name)}\n"
        f"<b>Телефон Kaspi:</b> {escape(phone)}\n"
        f"<b>Сумма:</b> {amount} ₸\n"
        f"<b>Комментарий:</b> {escape(comment or '—')}"
    )
    send_message(text)


def notify_payment(user: User, amount: int, service: str, provider: str = "wooppay") -> None:
    title = "Ioka" if provider == "ioka" else "Wooppay"
    text = (
        f"<b>Оплата через {title} прошла</b>\n\n"
        f"<b>Кабинет:</b> {user.id}\n"
        f"<b>Клиент:</b> {escape(user.full_name)}\n"
        f"<b>Услуга:</b> {escape(service)}\n"
        f"<b>Сумма:</b> {amount} ₸"
    )
    send_message(text)


def notify_new_user(user: User) -> None:
    created = user.created_at.astimezone(LOCAL_TZ).strftime("%d.%m.%Y %H:%M")
    text = (
        "🆕 <b>Новая регистрация</b>\n\n"
        f"👤 <b>ФИО:</b> {escape(user.full_name)}\n"
        f"📧 <b>Email:</b> {escape(user.email)}\n"
        f"🏢 <b>Компания:</b> {escape(user.company or '—')}\n"
        f"📞 <b>Телефон:</b> {escape(user.phone or '—')}\n"
        f"🕒 <b>Дата:</b> {created}\n"
        f"🆔 <b>ID:</b> {user.id}"
    )
    send_message(text)
