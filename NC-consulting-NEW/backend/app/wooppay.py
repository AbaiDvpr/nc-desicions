import re
from xml.sax.saxutils import escape

import httpx

from app.config import settings

NS = "urn:XmlControllerwsdl"
DONE = 4


class WooppayError(Exception):
    def __init__(self, message: str):
        super().__init__(message)


def _envelope(operation: str, inner: str) -> str:
    return (
        '<?xml version="1.0" encoding="UTF-8"?>'
        '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" '
        f'xmlns:ns1="{NS}" xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
        'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
        'xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" '
        'SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
        f"<SOAP-ENV:Body><ns1:{operation}>{inner}</ns1:{operation}></SOAP-ENV:Body>"
        "</SOAP-ENV:Envelope>"
    )


def _field(name: str, value: str, kind: str = "string") -> str:
    return f'<{name} xsi:type="xsd:{kind}">{escape(value)}</{name}>'


def _call(operation: str, inner: str, session: str | None = None) -> str:
    headers = {
        "Content-Type": "text/xml; charset=utf-8",
        "SOAPAction": f'"{NS}#{operation}"',
    }
    if session:
        headers["Cookie"] = f"session={session}"
    try:
        response = httpx.post(
            settings.wooppay_endpoint,
            content=_envelope(operation, inner).encode(),
            headers=headers,
            timeout=30,
        )
    except httpx.HTTPError as exc:
        raise WooppayError("Платёжная система сейчас недоступна") from exc
    if response.status_code >= 500:
        raise WooppayError("Платёжная система сейчас недоступна")
    text = response.text
    if "<faultstring>" in text:
        raise WooppayError("Платёжная система не приняла запрос")
    code = re.search(r"<error_code[^>]*>(\d+)</error_code>", text)
    error_code = int(code.group(1)) if code else 500
    if error_code != 0:
        if error_code in {5, 101, 308}:
            raise WooppayError("Wooppay не принял логин мерчанта. Проверьте данные в настройках")
        raise WooppayError("Не удалось выставить счёт в Wooppay")
    return text


def _tag(text: str, name: str) -> str:
    match = re.search(rf"<{name}[^>]*>([^<]*)</{name}>", text)
    if not match or not match.group(1):
        raise WooppayError("Платёжная система вернула пустой ответ")
    return match.group(1)


def login() -> str:
    text = _call(
        "core_login",
        "<coreLoginRequest xsi:type=\"ns1:CoreLoginRequest\">"
        f"{_field('username', settings.wooppay_username)}"
        f"{_field('password', settings.wooppay_password)}"
        "</coreLoginRequest>",
    )
    return _tag(text, "session")


def create_invoice(
    *,
    session: str,
    reference_id: str,
    back_url: str,
    request_url: str,
    amount: int,
    death_date: str,
    description: str,
    email: str,
    phone: str,
) -> tuple[int, str]:
    inner = (
        '<cashCreateInvoiceExtended2Request xsi:type="ns1:CashCreateInvoiceExtended2Request">'
        f"{_field('cardForbidden', '0', 'int')}"
        f"{_field('userEmail', email)}"
        f"{_field('userPhone', phone)}"
        f"{_field('referenceId', reference_id)}"
        f"{_field('backUrl', back_url)}"
        f"{_field('requestUrl', request_url)}"
        f"{_field('addInfo', description)}"
        f"{_field('amount', str(amount), 'float')}"
        f"{_field('deathDate', death_date)}"
        f"{_field('serviceType', '4', 'int')}"
        f"{_field('description', description)}"
        f"{_field('orderNumber', '')}"
        "</cashCreateInvoiceExtended2Request>"
    )
    text = _call("cash_createInvoice2Extended", inner, session)
    return int(_tag(text, "operationId")), _tag(text, "operationUrl")


def operation_status(session: str, operation_id: int) -> int | None:
    inner = (
        '<cashGetOperationDataRequest xsi:type="ns1:CashGetOperationDataRequest">'
        '<operationId SOAP-ENC:arrayType="xsd:int[1]" xsi:type="SOAP-ENC:Array">'
        f"{_field('item', str(operation_id), 'int')}"
        "</operationId></cashGetOperationDataRequest>"
    )
    try:
        text = _call("cash_getOperationData", inner, session)
    except WooppayError:
        return None
    match = re.search(r"<status[^>]*>(\d+)</status>", text)
    return int(match.group(1)) if match else None
