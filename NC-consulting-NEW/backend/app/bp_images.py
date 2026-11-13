from io import BytesIO
from pathlib import Path

from fastapi import HTTPException, status
from PIL import Image, ImageOps, UnidentifiedImageError
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import BpImage

IMAGE_DIR = Path(__file__).resolve().parent.parent / "data" / "bp_images"
MAX_UPLOAD_BYTES = 15 * 1024 * 1024
MAX_EDGE = 1800
JPEG_QUALITY = 85
MAX_PER_MODEL = 20

SECTIONS = {
    "company": "Информация о компании",
    "offer": "Продукция и услуги",
    "sales": "Продажи",
    "costs": "План расходов",
    "invest": "Инвестиции",
    "appendices": "Приложения",
}


def image_path(image_id: int) -> Path:
    return IMAGE_DIR / f"{image_id}.jpg"


def clean_section(value: str | None) -> str:
    value = (value or "").strip()
    if value not in SECTIONS:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Нет такого раздела")
    return value


def compress(raw: bytes) -> tuple[bytes, int, int]:
    if len(raw) > MAX_UPLOAD_BYTES:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail="Картинка больше 15 МБ",
        )
    try:
        image = Image.open(BytesIO(raw))
        image = ImageOps.exif_transpose(image)
        if image.mode in ("RGBA", "LA", "P"):
            image = image.convert("RGBA")
            flat = Image.new("RGB", image.size, (255, 255, 255))
            flat.paste(image, mask=image.split()[-1])
            image = flat
        else:
            image = image.convert("RGB")
    except (UnidentifiedImageError, OSError):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Не удалось прочитать изображение",
        )
    image.thumbnail((MAX_EDGE, MAX_EDGE), Image.Resampling.LANCZOS)
    output = BytesIO()
    image.save(output, format="JPEG", quality=JPEG_QUALITY, optimize=True)
    return output.getvalue(), image.width, image.height


def list_images(db: Session, model_id: int) -> list[BpImage]:
    return list(
        db.scalars(
            select(BpImage).where(BpImage.fin_model_id == model_id).order_by(BpImage.position, BpImage.id)
        ).all()
    )


def add_image(db: Session, user_id: int, model_id: int, raw: bytes, section: str, caption: str) -> BpImage:
    current = list_images(db, model_id)
    if len(current) >= MAX_PER_MODEL:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Не больше {MAX_PER_MODEL} картинок в бизнес-плане",
        )
    data, width, height = compress(raw)
    image = BpImage(
        user_id=user_id,
        fin_model_id=model_id,
        section=section,
        caption=caption,
        position=(max((item.position for item in current), default=0) + 1),
        width=width,
        height=height,
    )
    db.add(image)
    db.flush()
    IMAGE_DIR.mkdir(parents=True, exist_ok=True)
    image_path(image.id).write_bytes(data)
    db.commit()
    db.refresh(image)
    return image


def remove_image(db: Session, image: BpImage) -> None:
    image_path(image.id).unlink(missing_ok=True)
    db.delete(image)
    db.commit()


def image_payload(image: BpImage) -> dict:
    return {
        "id": image.id,
        "section": image.section,
        "caption": image.caption,
        "width": image.width,
        "height": image.height,
        "url": f"/api/fin-models/{image.fin_model_id}/bp-images/{image.id}/file",
    }


def plan_images(db: Session, model_id: int) -> list[dict]:
    """Картинки для сборки Word и PDF: байты файла, пропорции и подпись."""
    result = []
    for image in list_images(db, model_id):
        path = image_path(image.id)
        if not path.is_file() or not image.width or not image.height:
            continue
        result.append(
            {
                "section": image.section,
                "caption": image.caption,
                "data": path.read_bytes(),
                "ratio": image.height / image.width,
            }
        )
    return result
