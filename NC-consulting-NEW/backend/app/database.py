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
    fin_columns = {column["name"] for column in fin_name}
    if "status" not in fin_columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE fin_models ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ready'"))
    if "is_deleted" not in fin_columns:
        with engine.begin() as conn:
            conn.execute(text("ALTER TABLE fin_models ADD COLUMN is_deleted BOOLEAN NOT NULL DEFAULT false"))

    if inspect(engine).has_table("bp_jobs"):
        job_columns = {column["name"] for column in inspect(engine).get_columns("bp_jobs")}
        if "provider" not in job_columns:
            with engine.begin() as conn:
                conn.execute(
                    text("ALTER TABLE bp_jobs ADD COLUMN provider VARCHAR(40) NOT NULL DEFAULT 'deepseek'")
                )

    if inspect(engine).has_table("bp_job_tasks"):
        task_columns = {column["name"] for column in inspect(engine).get_columns("bp_job_tasks")}
        if "review" not in task_columns:
            with engine.begin() as conn:
                conn.execute(text("ALTER TABLE bp_job_tasks ADD COLUMN review TEXT"))
        if "review_status" not in task_columns:
            with engine.begin() as conn:
                conn.execute(text("ALTER TABLE bp_job_tasks ADD COLUMN review_status VARCHAR(20) NOT NULL DEFAULT ''"))
        if "attempts" not in task_columns:
            with engine.begin() as conn:
                conn.execute(text("ALTER TABLE bp_job_tasks ADD COLUMN attempts INTEGER NOT NULL DEFAULT 0"))

    if inspect(engine).has_table("user_tariffs"):
        tariff_columns = {column["name"] for column in inspect(engine).get_columns("user_tariffs")}
        if "models_extra" not in tariff_columns:
            with engine.begin() as conn:
                conn.execute(text("ALTER TABLE user_tariffs ADD COLUMN models_extra INTEGER NOT NULL DEFAULT 0"))

    if inspect(engine).has_table("fem_rate_settings"):
        rate_columns = {column["name"] for column in inspect(engine).get_columns("fem_rate_settings")}
        if "mzp_schedule" not in rate_columns:
            with engine.begin() as conn:
                conn.execute(
                    text(
                        "ALTER TABLE fem_rate_settings ADD COLUMN mzp_schedule VARCHAR(500) "
                        "NOT NULL DEFAULT '2022:60000,2023:70000,2024:85000,2025:85000,2026:85000'"
                    )
                )
        if "simplified_ip_rate" not in rate_columns:
            with engine.begin() as conn:
                conn.execute(
                    text(
                        "ALTER TABLE fem_rate_settings ADD COLUMN simplified_ip_rate DOUBLE PRECISION "
                        "NOT NULL DEFAULT 4"
                    )
                )
                conn.execute(text("UPDATE fem_rate_settings SET simplified_ip_rate = simplified_rate"))
        if "simplified_too_rate" not in rate_columns:
            with engine.begin() as conn:
                conn.execute(
                    text(
                        "ALTER TABLE fem_rate_settings ADD COLUMN simplified_too_rate DOUBLE PRECISION "
                        "NOT NULL DEFAULT 4"
                    )
                )
                conn.execute(text("UPDATE fem_rate_settings SET simplified_too_rate = simplified_rate"))
        payroll_defaults = {
            "opv_rate": 10,
            "vosms_rate": 2,
            "ipn_rate": 10,
            "ipn_rate_high": 15,
            "so_rate": 5,
            "opvr_rate": 3.5,
            "opvr_rate_2027": 4.5,
            "opvr_rate_2028": 5,
            "osms_rate": 3,
            "mrp": 4325,
            "vat_threshold_mrp": 10000,
            "simplified_limit_mrp": 600000,
        }
        for column, default in payroll_defaults.items():
            if column not in rate_columns:
                with engine.begin() as conn:
                    conn.execute(
                        text(
                            f"ALTER TABLE fem_rate_settings ADD COLUMN {column} DOUBLE PRECISION "
                            f"NOT NULL DEFAULT {default}"
                        )
                    )

    from app.tariffs import backfill_tariffs
    from app.fem_rates import ensure_fem_rates
    from app.gov_catalog import ensure_gov_catalog

    backfill_tariffs()
    with SessionLocal() as db:
        ensure_fem_rates(db)
        ensure_gov_catalog(db)


def get_db() -> Generator[Session, None, None]:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
