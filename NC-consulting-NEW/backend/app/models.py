from datetime import datetime

from sqlalchemy import Boolean, DateTime, ForeignKey, Integer, String, func
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
    data: Mapped[dict] = mapped_column(JSONB)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
