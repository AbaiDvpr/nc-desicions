from io import BytesIO
from pathlib import Path

from fastapi import HTTPException, status
from PIL import Image, ImageOps, UnidentifiedImageError

AVATAR_DIR = Path(__file__).resolve().parent.parent / "data" / "avatars"
MAX_UPLOAD_BYTES = 8 * 1024 * 1024
MAX_EDGE = 512
JPEG_QUALITY = 82


def avatar_path(user_id: int) -> Path:
    return AVATAR_DIR / f"{user_id}.jpg"


def compress_avatar(raw: bytes) -> bytes:
    if len(raw) > MAX_UPLOAD_BYTES:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail="Фото больше 8 МБ",
        )
    try:
        image = Image.open(BytesIO(raw))
        image = ImageOps.exif_transpose(image)
        image = image.convert("RGB")
    except (UnidentifiedImageError, OSError):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Не удалось прочитать изображение",
        )

    image.thumbnail((MAX_EDGE, MAX_EDGE), Image.Resampling.LANCZOS)
    output = BytesIO()
    image.save(output, format="JPEG", quality=JPEG_QUALITY, optimize=True)
    return output.getvalue()


def save_avatar(user_id: int, raw: bytes) -> None:
    AVATAR_DIR.mkdir(parents=True, exist_ok=True)
    avatar_path(user_id).write_bytes(compress_avatar(raw))


def delete_avatar(user_id: int) -> None:
    avatar_path(user_id).unlink(missing_ok=True)
