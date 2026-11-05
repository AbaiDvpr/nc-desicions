from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=Path(__file__).resolve().parent.parent / ".env",
        extra="ignore",
    )

    database_url: str
    jwt_secret: str
    jwt_algorithm: str = "HS256"
    jwt_expire_minutes: int = 60 * 24 * 7
    cookie_name: str = "access_token"
    cookie_secure: bool = False
    telegram_bot_token: str | None = None
    telegram_chat_id: str | None = None
    frontend_url: str = "http://127.0.0.1:5173"
    api_public_url: str = "http://127.0.0.1:8010"
    wooppay_endpoint: str = "https://www.test.wooppay.com/api/wsdl?ws=1"
    wooppay_username: str = "test_merch"
    wooppay_password: str = "A12345678a"
    ioka_api_url: str = "https://stage-api.ioka.kz"
    ioka_api_key: str = ""
    admin_email: str = ""
    admin_password: str = ""
    admin_cookie_name: str = "admin_token"


settings = Settings()
