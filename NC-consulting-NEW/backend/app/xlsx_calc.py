"""Пересчёт Excel через LibreOffice, чтобы в файле были кэшированные значения.

openpyxl пишет только формулы без результатов. Превью в мессенджерах и на телефоне
формулы не считает — без кэша ячейки пустые. soffice --convert-to xlsx оставляет
формулы и записывает посчитанные значения.
"""

from __future__ import annotations

import logging
import re
import subprocess
import tempfile
import threading
import zipfile
from io import BytesIO
from pathlib import Path

logger = logging.getLogger(__name__)

_LOCK = threading.Lock()
_DEFAULT_TIMEOUT = 300

# LibreOffice при сохранении xlsx пишет функции Analysis AddIn своими именами, а AGGREGATE —
# без префикса _xlfn. Excel таких имён не знает и показывает #ИМЯ?.
_ADDIN = re.compile(r"com\.sun\.star\.sheet\.addin\.Analysis\.get([A-Za-z]+)\(")
_XLFN = re.compile(r"(?<![A-Za-z0-9_.])(aggregate|AGGREGATE)\(")
_FOREIGN = re.compile(r"com\.sun\.star|(?<![A-Za-z0-9_.])(?:ORG\.OPENOFFICE|ORG\.LIBREOFFICE)\.")


def excel_function_names(payload: bytes) -> bytes:
    """Вернуть Excel-имена функций в формулах листов. Значения и остальное содержимое не трогаются."""
    source = zipfile.ZipFile(BytesIO(payload))
    target = BytesIO()
    with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as out:
        for item in source.infolist():
            data = source.read(item.filename)
            if item.filename.startswith("xl/worksheets/") and item.filename.endswith(".xml"):
                text = data.decode("utf-8")
                text = _ADDIN.sub(lambda match: f"{match.group(1).upper()}(", text)
                text = _XLFN.sub("_xlfn.AGGREGATE(", text)
                if _FOREIGN.search(text):
                    logger.warning("В %s остались имена функций LibreOffice", item.filename)
                data = text.encode("utf-8")
            out.writestr(item, data)
    return target.getvalue()


class XlsxCalcError(RuntimeError):
    """LibreOffice не смог пересчитать книгу."""


def recalculate_xlsx(payload: bytes, *, timeout: int = _DEFAULT_TIMEOUT) -> bytes:
    """Вернуть xlsx с теми же формулами и заполненным кэшем значений."""
    if not payload:
        raise XlsxCalcError("Пустой файл Excel")
    with _LOCK:
        with tempfile.TemporaryDirectory(prefix="nc-xlsx-calc-") as folder:
            root = Path(folder)
            source_dir = root / "in"
            out_dir = root / "out"
            profile = root / "profile"
            source_dir.mkdir()
            out_dir.mkdir()
            source = source_dir / "model.xlsx"
            source.write_bytes(payload)
            try:
                completed = subprocess.run(
                    [
                        "soffice",
                        "--headless",
                        "--norestore",
                        "--nolockcheck",
                        "--nologo",
                        f"-env:UserInstallation=file://{profile}",
                        "--convert-to",
                        "xlsx",
                        "--outdir",
                        str(out_dir),
                        str(source),
                    ],
                    check=False,
                    capture_output=True,
                    text=True,
                    timeout=timeout,
                )
            except FileNotFoundError as exc:
                raise XlsxCalcError("LibreOffice (soffice) не установлен") from exc
            except subprocess.TimeoutExpired as exc:
                raise XlsxCalcError("Таймаут пересчёта Excel") from exc
            calculated = out_dir / "model.xlsx"
            if completed.returncode != 0 or not calculated.exists():
                detail = (completed.stderr or completed.stdout or "").strip()[-1500:]
                logger.error("LibreOffice recalc failed: %s", detail or completed.returncode)
                raise XlsxCalcError(detail or "LibreOffice не пересчитал файл")
            return excel_function_names(calculated.read_bytes())
