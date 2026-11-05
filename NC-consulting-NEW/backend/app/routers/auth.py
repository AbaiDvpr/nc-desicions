from fastapi import APIRouter, BackgroundTasks, Depends, HTTPException, Response, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.database import get_db
from app.models import User
from app.schemas import LoginIn, RegisterIn, UserOut
from app.security import (
    clear_auth_cookie,
    get_current_user,
    hash_password,
    set_auth_cookie,
    verify_password,
)
from app.tariffs import assign_default
from app.telegram import notify_new_user

router = APIRouter(prefix="/api/auth", tags=["auth"])


def _find_by_email(db: Session, email: str) -> User | None:
    return db.scalar(select(User).where(func.lower(User.email) == email.lower()))


@router.post("/register", response_model=UserOut, status_code=status.HTTP_201_CREATED)
def register(
    data: RegisterIn,
    response: Response,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db),
):
    if _find_by_email(db, data.email):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Пользователь с таким email уже существует",
        )

    user = User(
        email=data.email.lower(),
        full_name=data.full_name.strip(),
        company=(data.company or "").strip() or None,
        phone=(data.phone or "").strip() or None,
        hashed_password=hash_password(data.password),
    )
    db.add(user)
    db.flush()
    assign_default(db, user)
    db.commit()
    db.refresh(user)

    set_auth_cookie(response, user.id)
    background_tasks.add_task(notify_new_user, user)
    return user


@router.post("/login", response_model=UserOut)
def login(data: LoginIn, response: Response, db: Session = Depends(get_db)):
    user = _find_by_email(db, data.email)
    if user is None or not verify_password(data.password, user.hashed_password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Неверный email или пароль",
        )

    set_auth_cookie(response, user.id)
    return user


@router.post("/logout", status_code=status.HTTP_204_NO_CONTENT)
def logout(response: Response):
    clear_auth_cookie(response)


@router.get("/me", response_model=UserOut)
def me(user: User = Depends(get_current_user)):
    return user
