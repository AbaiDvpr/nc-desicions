from datetime import datetime, timedelta, timezone

import bcrypt
import jwt
from fastapi import Cookie, Depends, HTTPException, Response, status
from sqlalchemy.orm import Session

from app.config import settings
from app.database import get_db
from app.models import User


def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()


def verify_password(password: str, hashed: str) -> bool:
    return bcrypt.checkpw(password.encode(), hashed.encode())


def create_access_token(user_id: int) -> str:
    expire = datetime.now(timezone.utc) + timedelta(minutes=settings.jwt_expire_minutes)
    payload = {"sub": str(user_id), "exp": expire}
    return jwt.encode(payload, settings.jwt_secret, algorithm=settings.jwt_algorithm)


def set_auth_cookie(response: Response, user_id: int) -> None:
    response.set_cookie(
        key=settings.cookie_name,
        value=create_access_token(user_id),
        max_age=settings.jwt_expire_minutes * 60,
        httponly=True,
        secure=settings.cookie_secure,
        samesite="lax",
        path="/",
    )


def clear_auth_cookie(response: Response) -> None:
    response.delete_cookie(key=settings.cookie_name, path="/")


def set_admin_cookie(response: Response, user_id: int) -> None:
    response.set_cookie(
        key=settings.admin_cookie_name,
        value=create_access_token(user_id),
        max_age=settings.jwt_expire_minutes * 60,
        httponly=True,
        secure=settings.cookie_secure,
        samesite="lax",
        path="/",
    )


def clear_admin_cookie(response: Response) -> None:
    response.delete_cookie(key=settings.admin_cookie_name, path="/")


def get_current_admin(
    admin_token: str | None = Cookie(default=None, alias=settings.admin_cookie_name),
    db: Session = Depends(get_db),
) -> User:
    unauthorized = HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Не авторизован")
    if not admin_token:
        raise unauthorized
    try:
        payload = jwt.decode(admin_token, settings.jwt_secret, algorithms=[settings.jwt_algorithm])
        user_id = int(payload["sub"])
    except (jwt.PyJWTError, KeyError, ValueError):
        raise unauthorized

    user = db.get(User, user_id)
    if user is None or not user.is_admin:
        raise unauthorized
    return user


def get_current_user(
    access_token: str | None = Cookie(default=None, alias=settings.cookie_name),
    db: Session = Depends(get_db),
) -> User:
    unauthorized = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED, detail="Не авторизован"
    )
    if not access_token:
        raise unauthorized
    try:
        payload = jwt.decode(
            access_token, settings.jwt_secret, algorithms=[settings.jwt_algorithm]
        )
        user_id = int(payload["sub"])
    except (jwt.PyJWTError, KeyError, ValueError):
        raise unauthorized

    user = db.get(User, user_id)
    if user is None:
        raise unauthorized
    return user
