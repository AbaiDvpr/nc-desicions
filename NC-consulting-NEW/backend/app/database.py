from collections.abc import Generator

from sqlalchemy import create_engine, inspect, text
from sqlalchemy.orm import DeclarativeBase, Session, sessionmaker

from app.config import settings

engine = create_engine(settings.database_url, pool_pre_ping=True)
SessionLocal = sessionmaker(bind=engine, autoflush=False, expire_on_commit=False)


class Base(DeclarativeBase):
    pass


def ensure_schema() -> None:
    Base.metadata.create_all(bind=engine)
    columns = {column["name"] for column in inspect(engine).get_columns("users")}
    if "avatar_version" not in columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE users ADD COLUMN avatar_version INTEGER"))
    if "balance" not in columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE users ADD COLUMN balance INTEGER NOT NULL DEFAULT 0"))
    if "current_tariff_id" not in columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE users ADD COLUMN current_tariff_id INTEGER"))
    if "is_admin" not in columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE users ADD COLUMN is_admin BOOLEAN NOT NULL DEFAULT false"))

    refill_columns = {column["name"] for column in inspect(engine).get_columns("refill_requests")}
    if "is_paid" not in refill_columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE refill_requests ADD COLUMN is_paid BOOLEAN NOT NULL DEFAULT false"))

    invoice_columns = {column["name"] for column in inspect(engine).get_columns("invoices")}
    if "provider" not in invoice_columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE invoices ADD COLUMN provider VARCHAR(20) NOT NULL DEFAULT 'wooppay'"))
    if "external_id" not in invoice_columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE invoices ADD COLUMN external_id VARCHAR(64)"))

    fin_name = inspect(engine).get_columns("fin_models")
    name_column = next(column for column in fin_name if column["name"] == "name")
    name_length = getattr(name_column.get("type"), "length", None)
    if isinstance(name_length, int) and name_length < 500:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE fin_models ALTER COLUMN name TYPE VARCHAR(500)"))

    from app.tariffs import backfill_tariffs

    backfill_tariffs()


def get_db() -> Generator[Session, None, None]:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
