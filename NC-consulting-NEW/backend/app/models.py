from datetime import datetime

from sqlalchemy import Boolean, DateTime, Float, ForeignKey, Integer, String, Text, func
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.orm import Mapped, mapped_column

from app.database import Base


class User(Base):
    __tablename__ = "users"

    id: Mapped[int] = mapped_column(primary_key=True)
    email: Mapped[str] = mapped_column(String(255), unique=True, index=True)
    full_name: Mapped[str] = mapped_column(String(255))
    company: Mapped[str | None] = mapped_column(String(255), nullable=True)
    phone: Mapped[str | None] = mapped_column(String(50), nullable=True)
    hashed_password: Mapped[str] = mapped_column(String(255))
    avatar_version: Mapped[int | None] = mapped_column(Integer, nullable=True)
    balance: Mapped[int] = mapped_column(Integer, default=0, server_default="0")
    current_tariff_id: Mapped[int | None] = mapped_column(Integer, nullable=True)
    is_admin: Mapped[bool] = mapped_column(Boolean, default=False, server_default="false")
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )

    @property
    def avatar_url(self) -> str | None:
        if not self.avatar_version:
            return None
        return f"/api/profile/avatar?v={self.avatar_version}"


class Tariff(Base):
    __tablename__ = "tariffs"

    id: Mapped[int] = mapped_column(primary_key=True)
    slug: Mapped[str] = mapped_column(String(50), unique=True)
    name: Mapped[str] = mapped_column(String(100))
    period: Mapped[str] = mapped_column(String(10))
    price: Mapped[int] = mapped_column(Integer)
    models_limit: Mapped[int] = mapped_column(Integer)
    edits_limit: Mapped[int] = mapped_column(Integer)
    companies_limit: Mapped[int] = mapped_column(Integer)
    plans_limit: Mapped[int] = mapped_column(Integer)
    features: Mapped[list] = mapped_column(JSONB)
    is_default: Mapped[bool] = mapped_column(Boolean, default=False, server_default="false")
    sort_order: Mapped[int] = mapped_column(Integer, default=0)


class UserTariff(Base):
    __tablename__ = "user_tariffs"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    tariff_id: Mapped[int] = mapped_column(ForeignKey("tariffs.id"))
    name: Mapped[str] = mapped_column(String(100))
    period: Mapped[str] = mapped_column(String(10))
    price: Mapped[int] = mapped_column(Integer)
    models_limit: Mapped[int] = mapped_column(Integer)
    models_extra: Mapped[int] = mapped_column(Integer, default=0, server_default="0")
    edits_limit: Mapped[int] = mapped_column(Integer)
    features: Mapped[list] = mapped_column(JSONB)
    started_at: Mapped[datetime] = mapped_column(DateTime(timezone=True))
    expires_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True), nullable=True)


class LedgerEntry(Base):
    __tablename__ = "ledger_entries"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    amount: Mapped[int] = mapped_column(Integer)
    comment: Mapped[str] = mapped_column(String(500))
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class Invoice(Base):
    __tablename__ = "invoices"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    tariff_id: Mapped[int | None] = mapped_column(ForeignKey("tariffs.id"), nullable=True)
    amount: Mapped[int] = mapped_column(Integer)
    email: Mapped[str] = mapped_column(String(255))
    phone: Mapped[str] = mapped_column(String(50))
    service: Mapped[str] = mapped_column(String(200))
    description: Mapped[str] = mapped_column(String(500), default="")
    provider: Mapped[str] = mapped_column(String(20), default="wooppay", server_default="wooppay")
    operation_id: Mapped[int | None] = mapped_column(Integer, nullable=True)
    external_id: Mapped[str | None] = mapped_column(String(64), nullable=True)
    paid_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True), nullable=True)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class RefillRequest(Base):
    __tablename__ = "refill_requests"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    full_name: Mapped[str] = mapped_column(String(100))
    phone: Mapped[str] = mapped_column(String(50))
    amount: Mapped[int] = mapped_column(Integer)
    comment: Mapped[str] = mapped_column(String(200), default="")
    is_paid: Mapped[bool] = mapped_column(Boolean, default=False, server_default="false")
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class Company(Base):
    __tablename__ = "companies"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    name: Mapped[str] = mapped_column(String(256))
    legal_form: Mapped[str] = mapped_column(String(10))
    tax_regime: Mapped[str] = mapped_column(String(10))
    currency: Mapped[str] = mapped_column(String(10))
    nds: Mapped[bool] = mapped_column(Boolean, default=False, server_default="false")
    description: Mapped[str] = mapped_column(String(1000), default="")
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class CompanyRequisite(Base):
    __tablename__ = "company_requisites"

    id: Mapped[int] = mapped_column(primary_key=True)
    company_id: Mapped[int] = mapped_column(ForeignKey("companies.id", ondelete="CASCADE"), index=True)
    name: Mapped[str] = mapped_column(String(255))
    legal_address: Mapped[str] = mapped_column(String(255), default="")
    actual_address: Mapped[str] = mapped_column(String(255), default="")
    email: Mapped[str] = mapped_column(String(255), default="")
    phone: Mapped[str] = mapped_column(String(50), default="")
    director: Mapped[str] = mapped_column(String(255), default="")
    bin: Mapped[str] = mapped_column(String(12), default="")
    iik: Mapped[str] = mapped_column(String(34), default="")
    bank_name: Mapped[str] = mapped_column(String(100), default="")
    bik: Mapped[str] = mapped_column(String(20), default="")
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class Counterparty(Base):
    __tablename__ = "counterparties"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    name: Mapped[str] = mapped_column(String(255))
    legal_address: Mapped[str] = mapped_column(String(255))
    actual_address: Mapped[str] = mapped_column(String(255), default="")
    email: Mapped[str] = mapped_column(String(255))
    phone: Mapped[str] = mapped_column(String(50), default="")
    director: Mapped[str] = mapped_column(String(255))
    bin: Mapped[str] = mapped_column(String(12))
    iik: Mapped[str] = mapped_column(String(34))
    bank_name: Mapped[str] = mapped_column(String(100))
    bik: Mapped[str] = mapped_column(String(20))
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class FinModel(Base):
    __tablename__ = "fin_models"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(
        ForeignKey("users.id", ondelete="CASCADE"), index=True
    )
    name: Mapped[str] = mapped_column(String(500))
    status: Mapped[str] = mapped_column(String(20), default="ready", server_default="ready")
    is_deleted: Mapped[bool] = mapped_column(Boolean, default=False, server_default="false")
    data: Mapped[dict] = mapped_column(JSONB)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )


class BpSetting(Base):
    """Какая модель пишет текст бизнес-плана. Одна строка на всю систему."""

    __tablename__ = "bp_settings"

    id: Mapped[int] = mapped_column(primary_key=True)
    provider: Mapped[str] = mapped_column(String(40), default="deepseek")
    model: Mapped[str] = mapped_column(String(80), default="deepseek-ai/deepseek-v4.1-flash-1017")


class FemRateSetting(Base):
    """Дефолтные ставки FEM (одна строка). Клиент может переопределить в анкете."""

    __tablename__ = "fem_rate_settings"

    id: Mapped[int] = mapped_column(primary_key=True)
    simplified_rate: Mapped[float] = mapped_column(Float, default=4.0)  # legacy = упрощёнка ИП
    simplified_ip_rate: Mapped[float] = mapped_column(Float, default=4.0)
    simplified_too_rate: Mapped[float] = mapped_column(Float, default=4.0)
    cit_ip_rate: Mapped[float] = mapped_column(Float, default=10.0)
    cit_too_rate: Mapped[float] = mapped_column(Float, default=20.0)
    vat_rate: Mapped[float] = mapped_column(Float, default=16.0)
    opv_rate: Mapped[float] = mapped_column(Float, default=10.0)
    vosms_rate: Mapped[float] = mapped_column(Float, default=2.0)
    ipn_rate: Mapped[float] = mapped_column(Float, default=10.0)
    ipn_rate_high: Mapped[float] = mapped_column(Float, default=15.0)
    so_rate: Mapped[float] = mapped_column(Float, default=5.0)
    opvr_rate: Mapped[float] = mapped_column(Float, default=3.5)
    opvr_rate_2027: Mapped[float] = mapped_column(Float, default=4.5)
    opvr_rate_2028: Mapped[float] = mapped_column(Float, default=5.0)
    osms_rate: Mapped[float] = mapped_column(Float, default=3.0)
    social_tax_rate: Mapped[float] = mapped_column(Float, default=6.0)
    nbk_bank_spread: Mapped[float] = mapped_column(Float, default=4.0)
    mrp: Mapped[float] = mapped_column(Float, default=4325.0)
    vat_threshold_mrp: Mapped[float] = mapped_column(Float, default=10000.0)
    simplified_limit_mrp: Mapped[float] = mapped_column(Float, default=600000.0)
    # «год:сумма» через запятую — МЗП для Excel и потолков СО/ОПВР/ООСМС.
    mzp_schedule: Mapped[str] = mapped_column(String(500), default="2022:60000,2023:70000,2024:85000,2025:85000,2026:85000")


class GovProgram(Base):
    """Каталог госпрограмм Damu. Клиент читает из БД; админ правит и может обновить с сайта."""

    __tablename__ = "gov_programs"

    id: Mapped[int] = mapped_column(primary_key=True)
    slug: Mapped[str] = mapped_column(String(80), unique=True, index=True)
    title: Mapped[str] = mapped_column(String(240), default="")
    kind: Mapped[str] = mapped_column(String(20), default="subsidy")
    kind_label: Mapped[str] = mapped_column(String(40), default="Субсидия")
    url: Mapped[str] = mapped_column(String(300), default="")
    # Ставка, которую платит клиент в срок субсидии (для Damu часто 12,6%).
    client_rate: Mapped[float | None] = mapped_column(Float, nullable=True)
    amount: Mapped[str] = mapped_column(String(120), default="")
    detail: Mapped[str] = mapped_column(String(300), default="")
    purpose: Mapped[str] = mapped_column(String(240), default="")
    term_min_months: Mapped[int | None] = mapped_column(Integer, nullable=True)
    term_max_months: Mapped[int | None] = mapped_column(Integer, nullable=True)
    term_kind: Mapped[str] = mapped_column(String(20), default="")
    logic: Mapped[str] = mapped_column(String(800), default="")
    allows_investment: Mapped[bool] = mapped_column(Boolean, default=True, server_default="true")
    allows_wc: Mapped[bool] = mapped_column(Boolean, default=True, server_default="true")
    grace_max_months: Mapped[int | None] = mapped_column(Integer, nullable=True)
    is_active: Mapped[bool] = mapped_column(Boolean, default=True, server_default="true")
    sort_order: Mapped[int] = mapped_column(Integer, default=0, server_default="0")
    source: Mapped[str] = mapped_column(String(20), default="manual")  # damu | manual
    updated_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())


class BpTemplate(Base):
    __tablename__ = "bp_templates"

    id: Mapped[int] = mapped_column(primary_key=True)
    slug: Mapped[str] = mapped_column(String(40), unique=True)
    name: Mapped[str] = mapped_column(String(120))
    description: Mapped[str] = mapped_column(String(400), default="")
    cover: Mapped[str] = mapped_column(String(20), default="classic")
    accent: Mapped[str] = mapped_column(String(7), default="#1e3a5f")
    sections: Mapped[list] = mapped_column(JSONB)
    sort_order: Mapped[int] = mapped_column(Integer, default=0)


class BpJob(Base):
    """Фоновая сборка бизнес-плана. Word отдаётся только когда задача дошла до конца."""

    __tablename__ = "bp_jobs"

    id: Mapped[int] = mapped_column(primary_key=True)
    user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="CASCADE"), index=True)
    fin_model_id: Mapped[int] = mapped_column(
        ForeignKey("fin_models.id", ondelete="CASCADE"), index=True
    )
    template_id: Mapped[int] = mapped_column(ForeignKey("bp_templates.id"))
    template_name: Mapped[str] = mapped_column(String(120), default="")
    provider: Mapped[str] = mapped_column(String(40), default="deepseek", server_default="deepseek")
    model_name: Mapped[str] = mapped_column(String(80), default="deepseek-ai/deepseek-v4.1-flash-1017")
    status: Mapped[str] = mapped_column(String(20), default="queued")
    case_text: Mapped[str] = mapped_column(Text, default="")
    error: Mapped[str] = mapped_column(Text, default="")
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
    finished_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True), nullable=True)


class BpJobTask(Base):
    __tablename__ = "bp_job_tasks"

    id: Mapped[int] = mapped_column(primary_key=True)
    job_id: Mapped[int] = mapped_column(ForeignKey("bp_jobs.id", ondelete="CASCADE"), index=True)
    position: Mapped[int] = mapped_column(Integer, default=0)
    key: Mapped[str] = mapped_column(String(40))
    title: Mapped[str] = mapped_column(String(200))
    source: Mapped[str] = mapped_column(String(20), default="ai")
    method: Mapped[str] = mapped_column(Text, default="")
    status: Mapped[str] = mapped_column(String(20), default="queued")
    attempts: Mapped[int] = mapped_column(Integer, default=0, server_default="0")
    answer: Mapped[str] = mapped_column(Text, default="")
    error: Mapped[str] = mapped_column(Text, default="")
    review: Mapped[str] = mapped_column(Text, default="")
    review_status: Mapped[str] = mapped_column(String(20), default="")
