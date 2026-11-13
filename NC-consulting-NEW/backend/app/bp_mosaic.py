"""Шаблон «мозаика»: обложка и оглавление по образцу банковского бизнес-плана «Радуга здоровья»."""

from copy import deepcopy
from datetime import date
from io import BytesIO
from math import ceil

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT, WD_TAB_LEADER
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn
from docx.shared import Cm, Pt
from fpdf.enums import XPos, YPos
from fpdf.errors import FPDFException
from PIL import Image, ImageDraw, ImageFont

from app.bp_plan import (
    MOSAIC_HEAD,
    SERIF_BOLD,
    _docx_block,
    _paint,
    _Pdf,
    _pdf_block,
    _pdf_color,
    _pdf_write,
    _plain,
    _rgb,
    _run_paragraph,
)

FONT = "Times New Roman"
PAGE_W, PAGE_H = 21.0, 29.7
MARGIN_TOP, MARGIN_BOTTOM, MARGIN_LEFT, MARGIN_RIGHT = 2.0, 2.0, 2.0, 1.75
TEXT_W = PAGE_W - MARGIN_LEFT - MARGIN_RIGHT

BACK = "EEECE1"
PANEL = "C4BC96"
OLIVE = "C2D69B"
OLIVE_LIGHT = "D6E3BC"
GRAY = "BFBFBF"
RED = "C0504D"

# Фигуры обложки в сантиметрах от левого верхнего угла листа: x, y, ширина, высота, цвет.
PANEL_BOX = (5.38, 0.44, 14.52, 28.72)
YEAR_BOX = (3.16, 0.44, 2.90, 3.07)
SHAPES = (
    (0.72, 0.0, 19.84, PAGE_H, BACK),
    (*PANEL_BOX, PANEL),
    (-0.90, 6.32, 2.68, 2.96, OLIVE),
    (-0.90, 9.28, 2.68, 2.96, PANEL),
    (1.78, 9.28, 2.68, 2.96, OLIVE_LIGHT),
    (-0.90, 12.24, 2.68, 2.96, OLIVE_LIGHT),
    (1.78, 12.24, 2.68, 2.96, PANEL),
    (1.78, 15.20, 2.68, 2.96, OLIVE_LIGHT),
    (*YEAR_BOX, OLIVE_LIGHT),
    (17.78, 27.69, 0.67, 0.74, GRAY),
    (17.78, 28.43, 0.67, 0.74, RED),
    (17.11, 28.43, 0.67, 0.74, GRAY),
)
TEXT_LEFT = PANEL_BOX[0] + 0.64
TEXT_RIGHT = PANEL_BOX[0] + PANEL_BOX[2] - 1.27
TEXT_TOP = PANEL_BOX[1] + 3.81


def _year() -> str:
    return str(date.today().year)


def _cover_lines(plan: dict) -> list[tuple[str, float, bool, float]]:
    """Строки на панели обложки: текст, кегль, жирность, отступ сверху в пунктах."""
    return [
        ("БИЗНЕС-ПЛАН", 14, True, 0),
        (plan["facts"]["name"], 20, True, 10),
    ]


def _entries(plan: dict) -> list[str]:
    """Как в оглавлении «Радуги»: только пункты n.k, без строк разделов и приложений."""
    entries = []
    for number, _title, blocks in plan["sections"]:
        if not number:
            continue
        sub = 0
        for block in blocks:
            if block[0] == "sub":
                sub += 1
                entries.append(_sub(number, sub, block[1]))
    return entries


def _head(number: int | None, title: str) -> str:
    if number is None:
        return title
    return f"{number}. {title.upper()}"


def _sub(number: int, index: int, text: str) -> str:
    return f"{number}.{index}. {text}"


# ---------- Word ----------


def _cover_png(plan: dict) -> bytes:
    dpi = 150
    scale = dpi / 2.54
    image = Image.new("RGB", (round(PAGE_W * scale), round(PAGE_H * scale)), "#FFFFFF")
    draw = ImageDraw.Draw(image)
    for x, y, w, h, color in SHAPES:
        box = [round(x * scale), round(y * scale), round((x + w) * scale), round((y + h) * scale)]
        draw.rectangle(box, fill=f"#{color}", outline="#FFFFFF", width=2)
    font = ImageFont.truetype(SERIF_BOLD, round(16 / 72 * dpi))
    x, y, w, h = YEAR_BOX
    draw.text((round((x + 0.35) * scale), round((y + h - 0.35) * scale)), _year(), font=font, fill="#000000", anchor="ls")
    buffer = BytesIO()
    image.save(buffer, "PNG")
    return buffer.getvalue()


def _float_behind(run, width_cm: float, height_cm: float) -> None:
    """Картинку из строки делаем подложкой на весь лист, за текстом."""
    drawing = run._r.find(qn("w:drawing"))
    inline = drawing[0]
    anchor = parse_xml(
        f'<wp:anchor {nsdecls("wp", "a", "pic", "r")} distT="0" distB="0" distL="0" distR="0" simplePos="0" '
        'relativeHeight="0" behindDoc="1" locked="1" layoutInCell="1" allowOverlap="1">'
        '<wp:simplePos x="0" y="0"/>'
        '<wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH>'
        '<wp:positionV relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionV>'
        f'<wp:extent cx="{Cm(width_cm)}" cy="{Cm(height_cm)}"/>'
        '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
        "<wp:wrapNone/>"
        "</wp:anchor>"
    )
    for tag in ("wp:docPr", "wp:cNvGraphicFramePr", "a:graphic"):
        node = inline.find(qn(tag))
        if node is not None:
            anchor.append(deepcopy(node))
    drawing.remove(inline)
    drawing.append(anchor)


def _cover_docx(doc: Document, plan: dict) -> None:
    holder = doc.add_paragraph()
    holder.paragraph_format.space_before = Pt(0)
    holder.paragraph_format.space_after = Pt((TEXT_TOP - MARGIN_TOP) / 2.54 * 72 - 2)
    holder.paragraph_format.line_spacing = Pt(2)
    run = holder.add_run()
    run.add_picture(BytesIO(_cover_png(plan)), width=Cm(PAGE_W), height=Cm(PAGE_H))
    _float_behind(run, PAGE_W, PAGE_H)
    left = TEXT_LEFT - MARGIN_LEFT
    right = PAGE_W - MARGIN_RIGHT - TEXT_RIGHT
    for text, size, bold, before in _cover_lines(plan):
        paragraph = _run_paragraph(doc, text, FONT, size, "000000", bold, before=round(before), after=0)
        paragraph.paragraph_format.left_indent = Cm(left)
        paragraph.paragraph_format.right_indent = Cm(right)
        paragraph.paragraph_format.first_line_indent = Cm(0)
        paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
    doc.add_page_break()


def _fld(run, kind: str) -> None:
    char = OxmlElement("w:fldChar")
    char.set(qn("w:fldCharType"), kind)
    run._r.append(char)


def _instr(run, instruction: str) -> None:
    node = OxmlElement("w:instrText")
    node.set(qn("xml:space"), "preserve")
    node.text = f" {instruction} "
    run._r.append(node)


def _footer_docx(section) -> None:
    section.different_first_page_header_footer = True
    paragraph = section.footer.paragraphs[0]
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    _fld(paragraph.add_run(), "begin")
    _instr(paragraph.add_run(), "PAGE")
    _fld(paragraph.add_run(), "separate")
    paragraph.add_run("2")
    _fld(paragraph.add_run(), "end")
    for run in paragraph.runs:
        _paint(run, FONT, 10, "000000", False)


def _toc_docx(doc: Document, plan: dict) -> None:
    title = _run_paragraph(doc, "Оглавление", FONT, 16, "000000", False, before=0, after=12)
    title.paragraph_format.keep_with_next = True
    entries = _entries(plan)
    pages = _pdf_pages(plan)
    for index, text in enumerate(entries):
        paragraph = doc.add_paragraph()
        fmt = paragraph.paragraph_format
        fmt.space_before = Pt(0)
        fmt.space_after = Pt(3)
        fmt.left_indent = Cm(0)
        fmt.right_indent = Cm(0)
        fmt.first_line_indent = Cm(0)
        fmt.tab_stops.add_tab_stop(Cm(TEXT_W), WD_TAB_ALIGNMENT.RIGHT, WD_TAB_LEADER.DOTS)
        if index == 0:
            _fld(paragraph.add_run(), "begin")
            _instr(paragraph.add_run(), 'TOC \\o "2-2" \\h \\z \\u')
            _fld(paragraph.add_run(), "separate")
        _paint(paragraph.add_run(_plain(text)), FONT, 12, "000000", False)
        _paint(paragraph.add_run("\t"), FONT, 12, "000000", False)
        page = str(pages[index]) if index < len(pages) else ""
        _paint(paragraph.add_run(page), FONT, 12, "000000", False)
        if index == len(entries) - 1:
            _fld(paragraph.add_run(), "end")
    settings = doc.settings.element
    update = OxmlElement("w:updateFields")
    update.set(qn("w:val"), "true")
    settings.append(update)
    doc.add_page_break()


def render_mosaic_docx(plan: dict) -> bytes:
    doc = Document()
    normal = doc.styles["Normal"]
    normal.font.name = FONT
    normal.font.size = Pt(12)
    for style_name, size in (("Heading 1", 13), ("Heading 2", 12)):
        style = doc.styles[style_name]
        style.font.name = FONT
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.italic = False
        style.font.color.rgb = _rgb("000000")
        fonts = style.element.get_or_add_rPr().get_or_add_rFonts()
        for key in ("w:ascii", "w:hAnsi", "w:cs", "w:eastAsia"):
            fonts.set(qn(key), FONT)
        for key in ("w:asciiTheme", "w:hAnsiTheme", "w:cstheme", "w:eastAsiaTheme"):
            fonts.attrib.pop(qn(key), None)
    section = doc.sections[0]
    section.page_width = Cm(PAGE_W)
    section.page_height = Cm(PAGE_H)
    section.top_margin = Cm(MARGIN_TOP)
    section.bottom_margin = Cm(MARGIN_BOTTOM)
    section.left_margin = Cm(MARGIN_LEFT)
    section.right_margin = Cm(MARGIN_RIGHT)
    _footer_docx(section)
    _cover_docx(doc, plan)
    _toc_docx(doc, plan)
    look = {
        "size": 12,
        "justify": True,
        "head_fill": MOSAIC_HEAD,
        "head_color": "000000",
        "cell": 9,
        "zebra": False,
        "usable": TEXT_W,
    }
    for number, title, blocks in plan["sections"]:
        if number == 0:
            continue
        if number is None:
            doc.add_page_break()
        heading = doc.add_heading(_head(number, title), level=1)
        heading.paragraph_format.space_before = Pt(18)
        heading.paragraph_format.space_after = Pt(6)
        sub = 0
        for block in blocks:
            kind = block[0]
            if kind == "sub":
                sub += 1
                paragraph = doc.add_heading(_sub(number, sub, block[1]), level=2)
                paragraph.paragraph_format.space_before = Pt(10)
                paragraph.paragraph_format.space_after = Pt(4)
            elif kind == "cap":
                paragraph = _run_paragraph(doc, block[1], FONT, 12, "000000", True, before=6, after=4)
                paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
                paragraph.paragraph_format.keep_with_next = True
            elif kind == "h":
                paragraph = _run_paragraph(doc, block[1], FONT, 12, "000000", True, before=6, after=3)
                paragraph.paragraph_format.keep_with_next = True
            else:
                _docx_block(doc, block, FONT, "000000", look)
    buffer = BytesIO()
    doc.save(buffer)
    return buffer.getvalue()


# ---------- PDF ----------


def _pdf_cover(pdf: _Pdf, plan: dict) -> None:
    pdf.set_auto_page_break(False)
    pdf.set_draw_color(255, 255, 255)
    pdf.set_line_width(0.35)
    for x, y, w, h, color in SHAPES:
        pdf.set_fill_color(*_pdf_color(color))
        pdf.rect(x * 10, y * 10, w * 10, h * 10, "FD")
    x, y, w, h = YEAR_BOX
    pdf.set_font("Body", "B", 16)
    pdf.set_text_color(0, 0, 0)
    pdf.set_xy(x * 10 + 3.5, (y + h) * 10 - 10)
    pdf.cell(w * 10 - 4, 7, _year())
    left = TEXT_LEFT * 10
    width = (TEXT_RIGHT - TEXT_LEFT) * 10
    pdf.set_y(TEXT_TOP * 10)
    for text, size, bold, before in _cover_lines(plan):
        pdf.set_y(pdf.get_y() + before * 0.3528)
        pdf.set_x(left)
        pdf.set_font("Body", "B" if bold else "", size)
        pdf.multi_cell(width, size * 0.3528 * 1.25, _plain(text), align="L", new_x=XPos.LEFT, new_y=YPos.NEXT)
    pdf.set_line_width(0.2)
    pdf.set_auto_page_break(True, MARGIN_BOTTOM * 10)


def _toc_pages(plan: dict) -> int:
    """Сколько листов займёт оглавление при тех же шрифтах, что в _pdf_toc."""
    probe = _Pdf(plan)
    probe.set_margins(MARGIN_LEFT * 10, MARGIN_TOP * 10, MARGIN_RIGHT * 10)
    probe.add_page()
    height = 11.0
    probe.set_font("Body", "", 10.5)
    for text in _entries(plan):
        width = probe.epw - probe.get_string_width("000") - 9
        lines = probe.multi_cell(width, 5.6, text, dry_run=True, output="LINES") or [text]
        height += len(lines) * 5.6 + 0.6
    usable = (PAGE_H - MARGIN_TOP - MARGIN_BOTTOM) * 10
    return max(1, ceil(height / usable))


def _pdf_toc(pdf: _Pdf, outline) -> None:
    _pdf_write(pdf, 8, "Оглавление", 16, False, (0, 0, 0))
    pdf.ln(3)
    for section in outline:
        if section.level != 1:
            continue
        indent = 0
        size = 10.5
        page = str(section.page_number)
        pdf.set_font("Body", "", size)
        page_w = pdf.get_string_width(page) + 1
        text_w = pdf.epw - indent - page_w - 8
        lines = pdf.multi_cell(text_w, 5.6, section.name, dry_run=True, output="LINES") or [section.name]
        link = pdf.add_link(page=section.page_number)
        pdf.ln(0.6)
        for index, line in enumerate(lines):
            pdf.set_x(pdf.l_margin + indent)
            pdf.set_text_color(0, 0, 0)
            if index < len(lines) - 1:
                pdf.cell(text_w, 5.6, line, link=link, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
                continue
            line_w = pdf.get_string_width(line)
            pdf.cell(line_w + 1, 5.6, line, link=link)
            dots_w = pdf.epw - indent - line_w - 1 - page_w
            pdf.set_font("Body", "", size)
            dot = pdf.get_string_width(".")
            pdf.set_text_color(90, 90, 90)
            pdf.cell(dots_w, 5.6, "." * max(int(dots_w / dot) - 1, 0), align="R", link=link)
            pdf.set_text_color(0, 0, 0)
            pdf.cell(page_w, 5.6, page, align="R", link=link, new_x=XPos.LMARGIN, new_y=YPos.NEXT)


def _build_pdf(plan: dict, toc_pages: int) -> _Pdf:
    pdf = _Pdf(plan)
    pdf.set_margins(MARGIN_LEFT * 10, MARGIN_TOP * 10, MARGIN_RIGHT * 10)
    pdf.set_auto_page_break(True, MARGIN_BOTTOM * 10)
    pdf.add_page()
    _pdf_cover(pdf, plan)
    pdf.add_page()
    pdf.insert_toc_placeholder(_pdf_toc, pages=toc_pages)
    for number, title, blocks in plan["sections"]:
        if number == 0:
            continue
        if number is None:
            pdf.add_page()
        else:
            pdf.ln(4)
        if pdf.get_y() > pdf.page_break_trigger - 30:
            pdf.add_page()
        head = _head(number, title)
        pdf.start_section(head, level=0)
        _pdf_write(pdf, 6.5, head, 12.5, True, (0, 0, 0))
        pdf.ln(1.5)
        sub = 0
        for block in blocks:
            kind = block[0]
            if kind == "sub":
                sub += 1
                text = _sub(number, sub, block[1])
                pdf.ln(1.5)
                if pdf.get_y() > pdf.page_break_trigger - 25:
                    pdf.add_page()
                pdf.start_section(text, level=1)
                _pdf_write(pdf, 6, text, 11.5, True, (0, 0, 0))
            elif kind == "cap":
                if pdf.get_y() > pdf.page_break_trigger - 25:
                    pdf.add_page()
                pdf.ln(1)
                pdf.set_font("Body", "B", 10.5)
                pdf.set_text_color(0, 0, 0)
                pdf.multi_cell(pdf.epw, 5.5, _plain(block[1]), align="R", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
                pdf.ln(0.5)
            elif kind == "h":
                pdf.ln(1)
                _pdf_write(pdf, 5.5, block[1], 10.5, True, (0, 0, 0))
            else:
                _pdf_block(pdf, block)
    return pdf


def _fit_pdf(plan: dict) -> tuple[_Pdf, bytes]:
    """Оглавлению даётся ровно столько листов, сколько нужно, чтобы номера страниц не съезжали."""
    pages = _toc_pages(plan)
    for _attempt in range(4):
        pdf = _build_pdf(plan, pages)
        try:
            return pdf, bytes(pdf.output())
        except FPDFException as exc:
            message = str(exc)
            if "too many page breaks" in message:
                pages += 1
            elif "too few page breaks" in message and pages > 1:
                pages -= 1
            else:
                raise
    raise RuntimeError("Оглавление не уместилось")


def _pdf_pages(plan: dict) -> list[int]:
    """Номера страниц для оглавления Word: Word пересчитает их сам при открытии."""
    pdf, _data = _fit_pdf(plan)
    return [section.page_number for section in pdf._outline if section.level == 1]


def render_mosaic_pdf(plan: dict) -> bytes:
    return _fit_pdf(plan)[1]
