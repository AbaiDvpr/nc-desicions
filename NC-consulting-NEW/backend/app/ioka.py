import httpx

from app.config import settings

PAID = {"PAID", "ON_HOLD"}


class IokaError(Exception):
    def __init__(self, message: str):
        super().__init__(message)


def _headers() -> dict[str, str]:
    if not settings.ioka_api_key:
        raise IokaError("Оплата через Ioka не настроена")
    return {
        "API-KEY": settings.ioka_api_key,
        "Content-Type": "application/json",
    }


def _order(payload: dict) -> dict:
    order = payload.get("order") if isinstance(payload.get("order"), dict) else payload
    if not isinstance(order, dict) or not order.get("id"):
        raise IokaError("Ioka не вернула заказ")
    return order


def create_order(*, amount: int, description: str, external_id: str, back_url: str) -> tuple[str, str]:
    try:
        response = httpx.post(
            f"{settings.ioka_api_url.rstrip('/')}/v2/orders",
            headers=_headers(),
            json={
                "amount": amount * 100,
                "currency": "KZT",
                "capture_method": "AUTO",
                "description": description,
                "external_id": external_id,
                "success_url": back_url,
                "failure_url": back_url,
                "back_url": back_url,
            },
            timeout=30,
        )
    except httpx.HTTPError as exc:
        raise IokaError("Ioka сейчас недоступна") from exc
    if response.status_code >= 400:
        raise IokaError("Ioka не приняла заказ")
    order = _order(response.json())
    checkout = order.get("checkout_url")
    if not checkout:
        raise IokaError("Ioka не вернула страницу оплаты")
    return str(order["id"]), checkout


def order_status(order_id: str) -> str | None:
    try:
        response = httpx.get(
            f"{settings.ioka_api_url.rstrip('/')}/v2/orders/{order_id}",
            headers=_headers(),
            timeout=30,
        )
        if response.status_code >= 400:
            return None
        return str(_order(response.json()).get("status") or "")
    except (httpx.HTTPError, IokaError, ValueError):
        return None
