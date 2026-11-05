from sqlalchemy import func, select

from app.config import settings
from app.database import SessionLocal
from app.models import User
from app.security import hash_password
from app.tariffs import assign_default


def ensure_admin() -> None:
    """Создаёт администратора из ADMIN_EMAIL и ADMIN_PASSWORD, если его ещё нет."""
    email = settings.admin_email.strip().lower()
    password = settings.admin_password
    if not email or not password:
        return

    db = SessionLocal()
    try:
        user = db.scalar(select(User).where(func.lower(User.email) == email))
        if user is None:
            user = User(
                email=email,
                full_name="Администратор",
                hashed_password=hash_password(password),
                is_admin=True,
            )
            db.add(user)
            db.flush()
            assign_default(db, user)
        else:
            user.is_admin = True
        db.commit()
    finally:
        db.close()
