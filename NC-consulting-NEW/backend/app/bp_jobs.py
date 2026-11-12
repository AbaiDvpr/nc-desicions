"""Фоновая сборка бизнес-плана: по каждому разделу свой ответ или полная ошибка."""

from datetime import datetime, timezone
import threading
import time

import httpx
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.bp_templates import DEFAULT_MODEL, DEFAULT_PROVIDER, model_label, provider_for_model
from app.config import settings
from app.database import SessionLocal
from app.models import BpJob, BpJobTask, BpSetting, BpTemplate, FinModel, User

LEGAL = {1: "ИП", 2: "ТОО"}
FINANCING = {
    1: "долевое финансирование, инвестор",
    2: "долговое финансирование, кредит",
    3: "грантовое финансирование",
    4: "инвестор и кредит",
}
TAX = {1: "общеустановленный", 4: "упрощённый"}
UNITS = {1: "шт", 2: "кг", 3: "тонн", 4: "усл", 5: "м2", 6: "м3", 7: "л", 8: "м.п.", 9: "км", 10: "чел.", 11: "ед."}
MONEY = {
    "kzt": ("тенге", "₸"),
    "usd": ("доллар США", "$"),
    "eur": ("евро", "€"),
}
INVEST = (
    ("smr", "Работы СМР и капитальный ремонт"),
    ("overhaul", "Материалы СМР и капитального ремонта"),
    ("inventory", "Товарный запас / оборотный капитал"),
    ("ready_premises", "Помещения, здания, сооружения"),
    ("equipment", "Оборудование, техника, мебель"),
    ("raw", "Сырьё, товары, расходные материалы"),
    ("advertising", "Реклама"),
    ("wages", "Заработная плата"),
    ("rental", "Аренда"),
    ("other", "Прочие расходы"),
)


def _num(value) -> str:
    try:
        return f"{int(round(float(value or 0))):,}".replace(",", " ")
    except (TypeError, ValueError):
        return "0"


def _unit(form: dict) -> tuple[str, str]:
    code = str(form.get("currency") or "kzt").strip().lower()
    return MONEY.get(code, MONEY["kzt"])


def _money(form: dict, value) -> str:
    _, sign = _unit(form)
    return f"{_num(value)} {sign}"


def _funds(title: str, block: dict | None, form: dict) -> list[str]:
    data = block or {}
    rows = [f"{label}: {_money(form, data.get(key))}" for key, label in INVEST if data.get(key)]
    return _lines(title, rows)


def _lines(title: str, rows: list[str]) -> list[str]:
    filled = [row for row in rows if row]
    if not filled:
        return [f"{title}: нет"]
    return [f"{title}:"] + [f"- {row}" for row in filled]


def case_brief(form: dict) -> str:
    """Кейс целиком: все поля проекта, которые есть в модели."""
    from app.bp_plan import project_case

    return project_case(form)


# У этих моделей OpenAI можно задавать temperature. У GPT-5 и новее — только значение по умолчанию.
_OPENAI_SAMPLING = {"gpt-4.1", "gpt-4.1-mini", "gpt-4o", "gpt-4o-mini"}
# Сколько раз можно отправить один раздел. Сбой сервера повторяется сам, отказ по балансу — нет.
SECTION_SENDS = 3
_BALANCE_HINTS = (
    "insufficient_quota",
    "insufficient_balance",
    "insufficient balance",
    "insufficient quota",
    "billing_hard_limit",
    "billing_not_active",
    "exceeded your current quota",
    "credit balance is too low",
    "payment required",
    "недостаточно средств",
    "не хватает баланса",
    "пополните баланс",
    "insufficient_user_quota",
)


class BalanceError(RuntimeError):
    """Сервис модели отказал из‑за баланса. Повторно тот же пункт не отправляем."""


def _balance_failure(status: int, body: str) -> bool:
    if status == 402:
        return True
    text = (body or "").lower()
    return any(hint in text for hint in _BALANCE_HINTS)


def _public_error(exc: Exception, attempts: int) -> str:
    text = " ".join(str(exc).split())
    if len(text) > 280:
        text = text[:280].rstrip() + "…"
    if attempts >= SECTION_SENDS and not isinstance(exc, BalanceError):
        return f"Не вышло за 3 попытки. {text}"
    return text


def _route(model_name: str, provider: str | None) -> tuple[str, str, str, str]:
    chosen = (provider or "").strip() or provider_for_model(model_name) or DEFAULT_PROVIDER
    if chosen == "openai":
        return "openai", "OpenAI", settings.openai_api_url.strip(), settings.openai_api_key.strip()
    return "deepseek", "DeepSeek", settings.llm_api_url.strip(), settings.llm_api_key.strip()


def _complete(
    model_name: str,
    messages: list[dict],
    max_tokens: int,
    temperature: float,
    provider: str | None = None,
) -> str:
    provider_id, label, url, key = _route(model_name, provider)
    if not key:
        raise RuntimeError(
            f"Ключ {label} не задан на сервере. Команда на сборку принята, "
            f"но запрос к {model_name} не ушёл."
        )
    payload = {"model": model_name, "stream": False, "messages": messages}
    if provider_id == "openai":
        # У моделей с рассуждением часть лимита уходит на внутренний ход, до видимого текста.
        limit = max_tokens if model_name in _OPENAI_SAMPLING else max(max_tokens, 16000 if max_tokens > 1000 else 4000)
        payload["max_completion_tokens"] = limit
        if model_name in _OPENAI_SAMPLING:
            payload["temperature"] = temperature
            payload["top_p"] = 0.9
    else:
        payload["temperature"] = temperature
        payload["top_p"] = 0.9
        payload["max_tokens"] = max_tokens
    try:
        response = httpx.post(
            url,
            headers={"Authorization": f"Bearer {key}"},
            json=payload,
            timeout=120,
        )
    except httpx.HTTPError as exc:
        raise RuntimeError(f"Сеть до {label}: {exc}") from exc
    if response.status_code >= 400:
        if _balance_failure(response.status_code, response.text):
            raise BalanceError("Не хватает баланса у сервиса модели. Повтор сам не отправлялся.")
        raise RuntimeError(f"{label} {response.status_code}: {response.text[:4000]}")
    try:
        content = response.json()["choices"][0]["message"]["content"]
    except (KeyError, IndexError, TypeError, ValueError) as exc:
        raise RuntimeError(f"Ответ {label} без текста: {response.text[:4000]}") from exc
    if isinstance(content, list):
        parts = []
        for item in content:
            if isinstance(item, str):
                parts.append(item)
            elif isinstance(item, dict):
                parts.append(str(item.get("text") or ""))
        text = "".join(parts)
    else:
        text = str(content or "")
    text = text.strip()
    if not text:
        raise RuntimeError("Модель вернула пустой текст.")
    return text


def ask_model(model_name: str, method: str, case: str, provider: str | None = None) -> str:
    return _complete(
        model_name,
        [
            {
                "role": "system",
                "content": (
                    "Ты пишешь один раздел бизнес-плана на русском. "
                    "Отвечай только на вопрос этого раздела. Не переноси в него инвестиции, штат, срок и финансирование, если вопрос раздела не про них. "
                    "Пиши только то, что следует из кейса. Чего в кейсе нет, не упоминай: не пиши, что данных не хватает, поле пустое, нюанс отсутствует или раздел нечем заполнить. "
                    "Не выдумывай адрес, лицензии, объём рынка, конкурентов, CAC, NPV и другие цифры. "
                    "Каждое предложение опирается на цифру, название или срок из кейса. Общую фразу без такой опоры не пиши. "
                    "Если результат месяца до налога положительный, не пиши, что проект убыточен и что дивиденды не из чего платить. "
                    "Деньги пиши знаком из кейса: тенге — ₸, доллар — $, евро — €. "
                    "Коды KZT, USD и EUR не используй. Названия статей оставляй по-русски. Проверки в конце кейса не сглаживай. "
                    "Пиши обычным текстом: без решёток, звёздочек и других знаков разметки."
                ),
            },
            {
                "role": "user",
                "content": f"Метод раздела:\n{method}\n\nКейс финансовой модели:\n{case}",
            },
        ],
        8192,
        0.4,
        provider,
    )


def ask_review(model_name: str, title: str, text: str, provider: str | None = None) -> tuple[str, str]:
    raw = _complete(
        model_name,
        [
            {
                "role": "system",
                "content": (
                    "Ты проверяешь один готовый блок бизнес-плана. "
                    "Смотри, нормально ли читается текст: целые фразы, нет обрыва на полуслове, нет знаков ## и **, нет пустой воды. "
                    "Первая строка ответа — только НОРМ или ПОПРАВИТЬ. "
                    "Вторая строка — одна короткая фраза по-русски, без решёток и звёздочек."
                ),
            },
            {
                "role": "user",
                "content": f"Блок «{title}»:\n{text}",
            },
        ],
        400,
        0.2,
        provider,
    )
    from app.bp_plan import readable_answer

    lines = [line.strip() for line in raw.splitlines() if line.strip()]
    head = lines[0].upper() if lines else ""
    note = readable_answer(" ".join(lines[1:]) if len(lines) > 1 else "")
    if "ПОПРАВ" in head:
        status = "fix"
    elif "НОРМ" in head:
        status = "ok"
    else:
        status = "fix"
        note = readable_answer(raw)
    return status, (note or "Текст нужно перечитать.")[:500]


ATTEMPT_LIMIT = 3
VARIANT_LABELS = ("Первый вариант", "Второй вариант", "Третий вариант")


class AttemptLimit(Exception):
    pass


class NothingToRetry(Exception):
    pass


class RetryBusy(Exception):
    pass


def _latest(db: Session, user_id: int, model_id: int) -> BpJob | None:
    return db.scalar(
        select(BpJob)
        .where(BpJob.user_id == user_id, BpJob.fin_model_id == model_id)
        .order_by(BpJob.id.desc())
        .limit(1)
    )


def _jobs(db: Session, user_id: int, model_id: int) -> list[BpJob]:
    return list(
        db.scalars(
            select(BpJob)
            .where(BpJob.user_id == user_id, BpJob.fin_model_id == model_id)
            .order_by(BpJob.id.asc())
        ).all()
    )


def _brief_tasks(db: Session, job_id: int) -> list[dict]:
    from app.bp_plan import readable_answer

    tasks = db.scalars(
        select(BpJobTask).where(BpJobTask.job_id == job_id).order_by(BpJobTask.position, BpJobTask.id)
    ).all()
    rows = []
    for task in tasks:
        row = {
            "id": task.id,
            "key": task.key,
            "title": task.title,
            "source": task.source,
            "status": task.status,
            "attempts": task.attempts or 0,
            "error": task.error or "",
            "review": task.review or "",
            "review_status": task.review_status or "",
        }
        if task.source == "ai" and (task.answer or "").strip():
            row["answer"] = readable_answer(task.answer)
        rows.append(row)
    return rows


def client_job_payload(db: Session, job: BpJob) -> dict:
    """Клиенту только статусы. Промпт, ответ и имя модели остаются в админке."""
    jobs = _jobs(db, job.user_id, job.fin_model_id)
    attempts = []
    for number, item in enumerate(jobs, start=1):
        attempts.append(
            {
                "id": item.id,
                "number": number,
                "label": VARIANT_LABELS[number - 1] if number <= len(VARIANT_LABELS) else f"Вариант {number}",
                "status": item.status,
                "complete": item.status == "done",
                "template_id": item.template_id,
                "created_at": item.created_at,
                "finished_at": item.finished_at,
                "tasks": _brief_tasks(db, item.id),
            }
        )
    current = next(item for item in attempts if item["id"] == job.id)
    return {
        "id": job.id,
        "fin_model_id": job.fin_model_id,
        "template_id": job.template_id,
        "status": job.status,
        "tasks": current["tasks"],
        "attempts": attempts,
        "used": len(attempts),
        "limit": ATTEMPT_LIMIT,
        "complete_count": sum(1 for item in attempts if item["complete"]),
    }


def job_payload(db: Session, job: BpJob) -> dict:
    tasks = db.scalars(
        select(BpJobTask).where(BpJobTask.job_id == job.id).order_by(BpJobTask.position, BpJobTask.id)
    ).all()
    return {
        "id": job.id,
        "fin_model_id": job.fin_model_id,
        "template_id": job.template_id,
        "template_name": job.template_name,
        "provider": job.provider or provider_for_model(job.model_name) or DEFAULT_PROVIDER,
        "model_name": job.model_name,
        "model_label": model_label(job.model_name),
        "status": job.status,
        "error": job.error or "",
        "case_text": job.case_text or "",
        "created_at": job.created_at,
        "finished_at": job.finished_at,
        "tasks": [
            {
                "id": task.id,
                "key": task.key,
                "title": task.title,
                "source": task.source,
                "method": task.method or "",
                "status": task.status,
                "attempts": task.attempts or 0,
                "answer": task.answer or "",
                "error": task.error or "",
                "review": task.review or "",
                "review_status": task.review_status or "",
            }
            for task in tasks
        ],
    }


def latest_status(db: Session, user_id: int, model_ids: list[int]) -> dict[int, str]:
    return {model_id: item["bp_status"] for model_id, item in bp_cards(db, user_id, model_ids).items()}


def bp_cards(db: Session, user_id: int, model_ids: list[int]) -> dict[int, dict]:
    """Сводка по бизнес-планам для карточки в списке."""
    if not model_ids:
        return {}
    rows = db.execute(
        select(BpJob.fin_model_id, BpJob.status, BpJob.created_at, BpJob.finished_at)
        .where(BpJob.user_id == user_id, BpJob.fin_model_id.in_(model_ids))
        .order_by(BpJob.id.asc())
    ).all()
    found: dict[int, dict] = {}
    for model_id, status, created_at, finished_at in rows:
        item = found.setdefault(
            model_id,
            {
                "bp_status": "",
                "bp_count": 0,
                "bp_complete": 0,
                "bp_started_at": created_at,
                "bp_finished_at": None,
            },
        )
        item["bp_count"] += 1
        item["bp_status"] = status
        if status == "done":
            item["bp_complete"] += 1
        if finished_at is not None and status not in ("queued", "running"):
            item["bp_finished_at"] = finished_at
    return found


def start_job(db: Session, user: User, model: FinModel, template_id: int | None, force: bool) -> tuple[BpJob, bool]:
    del force
    jobs = _jobs(db, user.id, model.id)
    current = jobs[-1] if jobs else None
    if current and current.status in ("queued", "running"):
        return current, False
    if len(jobs) >= ATTEMPT_LIMIT:
        raise AttemptLimit("Бизнес-план можно собрать только три раза")
    template = None
    if template_id:
        template = db.get(BpTemplate, template_id)
    if template is None:
        template = db.scalar(select(BpTemplate).order_by(BpTemplate.sort_order, BpTemplate.id).limit(1))
    if template is None:
        raise LookupError("Нет шаблона бизнес-плана")
    setting = db.get(BpSetting, 1)
    model_name = setting.model if setting and provider_for_model(setting.model) else DEFAULT_MODEL
    provider = provider_for_model(model_name) or DEFAULT_PROVIDER
    brief = case_brief(model.data or {})
    job = BpJob(
        user_id=user.id,
        fin_model_id=model.id,
        template_id=template.id,
        template_name=template.name,
        provider=provider,
        model_name=model_name,
        status="queued",
        case_text=brief,
    )
    db.add(job)
    db.flush()
    for index, section in enumerate(template.sections or []):
        source = section.get("source") or "ai"
        method = section.get("prompt") or ""
        if source != "ai":
            method = "Раздел берётся из кейса финансовой модели, без запроса к модели."
        db.add(
            BpJobTask(
                job_id=job.id,
                position=index,
                key=str(section.get("key") or f"s{index}"),
                title=str(section.get("title") or "Раздел"),
                source=source,
                method=method,
                status="queued",
            )
        )
    db.commit()
    db.refresh(job)
    return job, True


def retry_failed(db: Session, user: User, model: FinModel, job_id: int) -> tuple[BpJob, bool]:
    """Повтор только пунктов со статусом ошибки. Новый вариант не создаётся."""
    jobs = _jobs(db, user.id, model.id)
    active = next((item for item in jobs if item.status in ("queued", "running")), None)
    job = db.get(BpJob, job_id)
    if job is None or job.user_id != user.id or job.fin_model_id != model.id:
        raise LookupError("Такого варианта нет")
    if active is not None:
        if active.id == job.id:
            return job, False
        raise RetryBusy("Сначала дождитесь, пока соберётся текущий вариант.")
    tasks = db.scalars(
        select(BpJobTask).where(BpJobTask.job_id == job.id, BpJobTask.status == "error")
    ).all()
    eligible = [task for task in tasks if (task.attempts or 0) < SECTION_SENDS]
    if not tasks:
        raise NothingToRetry("Несобранных пунктов нет.")
    if not eligible:
        raise NothingToRetry("Каждый такой раздел уже отправлялся 3 раза.")
    for task in eligible:
        task.status = "queued"
        task.error = ""
        task.answer = ""
        task.review = ""
        task.review_status = ""
    job.status = "queued"
    job.error = ""
    job.finished_at = None
    db.commit()
    db.refresh(job)
    return job, True


def recover_jobs(db: Session) -> None:
    """После перезапуска оборванная задача не должна вечно висеть «в работе»."""
    rows = db.scalars(select(BpJob).where(BpJob.status.in_(("queued", "running")))).all()
    if not rows:
        return
    message = "Задача оборвалась: сервер перезапустился до ответа модели."
    for job in rows:
        job.status = "error"
        job.error = message
        job.finished_at = datetime.now(timezone.utc)
        tasks = db.scalars(select(BpJobTask).where(BpJobTask.job_id == job.id)).all()
        for task in tasks:
            if task.status in ("queued", "running"):
                task.status = "error"
                task.error = message
    db.commit()


def _run(job_id: int) -> None:
    db = SessionLocal()
    try:
        job = db.get(BpJob, job_id)
        if job is None or job.status not in ("queued", "running"):
            return
        job.status = "running"
        db.commit()
        tasks = db.scalars(
            select(BpJobTask).where(BpJobTask.job_id == job.id).order_by(BpJobTask.position, BpJobTask.id)
        ).all()
        failed = False
        balance_stop = ""
        for task in tasks:
            if task.status == "done":
                continue
            if task.source != "ai":
                task.status = "running"
                db.commit()
                task.answer = "Раздел собран из данных финансовой модели."
                task.status = "done"
                task.error = ""
                task.review = ""
                task.review_status = ""
                db.commit()
                continue
            if balance_stop:
                failed = True
                task.status = "error"
                task.answer = ""
                task.error = balance_stop
                task.review = ""
                task.review_status = ""
                db.commit()
                continue
            while (task.attempts or 0) < SECTION_SENDS:
                task.attempts = (task.attempts or 0) + 1
                task.status = "running"
                task.error = ""
                db.commit()
                try:
                    task.answer = ask_model(job.model_name, task.method, job.case_text, job.provider)
                    task.status = "done"
                    task.error = ""
                    task.review = ""
                    task.review_status = ""
                    db.commit()
                    break
                except BalanceError as exc:
                    failed = True
                    balance_stop = _public_error(exc, task.attempts)
                    task.status = "error"
                    task.answer = ""
                    task.error = balance_stop
                    task.review = ""
                    task.review_status = ""
                    db.commit()
                    break
                except Exception as exc:
                    task.answer = ""
                    task.review = ""
                    task.review_status = ""
                    task.error = _public_error(exc, task.attempts)
                    if task.attempts >= SECTION_SENDS:
                        failed = True
                        task.status = "error"
                        db.commit()
                        break
                    task.status = "running"
                    db.commit()
                    time.sleep(2)
            if task.status != "done":
                failed = True
                if task.status != "error":
                    task.status = "error"
                    task.error = task.error or "Раздел не собрался."
                    db.commit()
        job.status = "error" if failed else "done"
        job.error = "Есть пункты с ошибкой. Текст ошибки лежит в задаче." if failed else ""
        job.finished_at = datetime.now(timezone.utc)
        db.commit()
    except Exception as exc:
        db.rollback()
        job = db.get(BpJob, job_id)
        if job is not None and job.status in ("queued", "running"):
            job.status = "error"
            job.error = str(exc)
            job.finished_at = datetime.now(timezone.utc)
            db.commit()
    finally:
        db.close()


def launch(job_id: int) -> None:
    threading.Thread(target=_run, args=(job_id,), daemon=True).start()


def start_review(db: Session, user: User, model: FinModel, job_id: int, task_id: int, answer: str) -> tuple[BpJob, bool]:
    job = db.get(BpJob, job_id)
    if job is None or job.user_id != user.id or job.fin_model_id != model.id:
        raise LookupError("Такого варианта нет")
    if job.status in ("queued", "running"):
        raise RetryBusy("Сначала дождитесь сборки этого варианта.")
    task = db.get(BpJobTask, task_id)
    if task is None or task.job_id != job.id:
        raise LookupError("Такого блока нет")
    if task.source != "ai":
        raise NothingToRetry("Этот блок собран из расчёта. Его правит таблица, не текст.")
    text = str(answer or "").replace("\r\n", "\n").strip()
    text = "".join(ch if ch in "\n\t" or ord(ch) >= 32 else " " for ch in text)
    if len(text) < 20:
        raise NothingToRetry("В блоке слишком мало текста для проверки.")
    if len(text) > 20000:
        text = text[:20000]
    busy = db.scalar(
        select(BpJobTask).where(BpJobTask.job_id == job.id, BpJobTask.review_status == "running")
    )
    if busy is not None:
        if busy.id == task.id:
            return job, False
        raise RetryBusy("Сначала дождитесь проверки текущего блока.")
    task.answer = text
    task.review = ""
    task.review_status = "running"
    db.commit()
    db.refresh(job)
    return job, True


def _review(task_id: int) -> None:
    db = SessionLocal()
    try:
        task = db.get(BpJobTask, task_id)
        if task is None or task.review_status != "running":
            return
        job = db.get(BpJob, task.job_id)
        status, note = ask_review(job.model_name, task.title, task.answer, job.provider)
        task.review_status = status
        task.review = note
        db.commit()
    except Exception:
        db.rollback()
        task = db.get(BpJobTask, task_id)
        if task is not None and task.review_status == "running":
            task.review_status = "error"
            task.review = "Не получилось посмотреть этот блок."
            db.commit()
    finally:
        db.close()


def launch_review(task_id: int) -> None:
    threading.Thread(target=_review, args=(task_id,), daemon=True).start()


def _plan(db: Session, job: BpJob, form: dict, template: BpTemplate | None):
    from app.bp_plan import assemble

    if template is None and job.template_id:
        template = db.get(BpTemplate, job.template_id)
    tasks = db.scalars(
        select(BpJobTask).where(BpJobTask.job_id == job.id).order_by(BpJobTask.position, BpJobTask.id)
    ).all()
    answers = {
        task.key: task.answer
        for task in tasks
        if task.source == "ai" and task.status == "done" and (task.answer or "").strip()
    }
    from app.fem_values import read_statements

    return assemble(form, template, answers, read_statements(form))


def render_job(db: Session, job: BpJob, form: dict, template: BpTemplate | None = None) -> bytes:
    from app.bp_plan import render_docx

    return render_docx(_plan(db, job, form, template))


def render_job_pdf(db: Session, job: BpJob, form: dict, template: BpTemplate | None = None) -> bytes:
    from app.bp_plan import render_pdf

    return render_pdf(_plan(db, job, form, template))
