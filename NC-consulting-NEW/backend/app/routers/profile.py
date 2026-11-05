from fastapi import APIRouter, Depends, File, HTTPException, UploadFile, status
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session

from app.avatars import avatar_path, delete_avatar, save_avatar
from app.database import get_db
from app.models import User
from app.schemas import PasswordChangeIn, ProfileUpdateIn, UserOut
from app.security import get_current_user, hash_password, verify_password

router = APIRouter(prefix="/api/profile", tags=["profile"])


@router.put("", response_model=UserOut)
def update_profile(
    data: ProfileUpdateIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    user.full_name = data.full_name.strip()
    user.company = (data.company or "").strip() or None
    user.phone = (data.phone or "").strip() or None
    db.add(user)
    db.commit()
    db.refresh(user)
    return user


@router.put("/password", status_code=status.HTTP_204_NO_CONTENT)
def change_password(
    data: PasswordChangeIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    if not verify_password(data.current_password, user.hashed_password):
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Текущий пароль неверный")
    if data.current_password == data.new_password:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Новый пароль совпадает с текущим",
        )
    user.hashed_password = hash_password(data.new_password)
    db.add(user)
    db.commit()


@router.get("/avatar")
def get_avatar(user: User = Depends(get_current_user)):
    path = avatar_path(user.id)
    if not user.avatar_version or not path.is_file():
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Фото не загружено")
    return FileResponse(path, media_type="image/jpeg", headers={"Cache-Control": "private, max-age=86400"})


@router.post("/avatar", response_model=UserOut)
async def upload_avatar(
    file: UploadFile = File(...),
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    save_avatar(user.id, await file.read())
    user.avatar_version = (user.avatar_version or 0) + 1
    db.add(user)
    db.commit()
    db.refresh(user)
    return user


@router.delete("/avatar", response_model=UserOut)
def remove_avatar(
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    delete_avatar(user.id)
    user.avatar_version = None
    db.add(user)
    db.commit()
    db.refresh(user)
    return user
