import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, finModelFiles } from '../api.js'
import BpDocument from '../components/BpDocument.jsx'
import BpEmpty from '../components/BpEmpty.jsx'
import BpTasks from '../components/BpTasks.jsx'
import ExcelDownloads from '../components/ExcelDownloads.jsx'
import { WordIcon } from '../components/FileIcons.jsx'

function PlusIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
      <path d="M12 5v14M5 12h14" />
    </svg>
  )
}

function PencilIcon() {
  return (
    <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
      <path
        fill="currentColor"
        d="M 36 5.0097656 C 34.205301 5.0097656 32.410791 5.6901377 31.050781 7.0507812 L 8.9160156 29.183594 C 8.4960384 29.603571 8.1884588 30.12585 8.0253906 30.699219 L 5.0585938 41.087891 A 1.50015 1.50015 0 0 0 6.9121094 42.941406 L 17.302734 39.974609 A 1.50015 1.50015 0 0 0 17.304688 39.972656 C 17.874212 39.808939 18.39521 39.50518 18.816406 39.083984 L 40.949219 16.949219 C 43.670344 14.228094 43.670344 9.7719064 40.949219 7.0507812 C 39.589209 5.6901377 37.794699 5.0097656 36 5.0097656 z M 36 7.9921875 C 37.020801 7.9921875 38.040182 8.3855186 38.826172 9.171875 A 1.50015 1.50015 0 0 0 38.828125 9.171875 C 40.403 10.74675 40.403 13.25325 38.828125 14.828125 L 36.888672 16.767578 L 31.232422 11.111328 L 33.171875 9.171875 C 33.957865 8.3855186 34.979199 7.9921875 36 7.9921875 z M 29.111328 13.232422 L 34.767578 18.888672 L 16.693359 36.962891 C 16.634729 37.021121 16.560472 37.065723 16.476562 37.089844 L 8.6835938 39.316406 L 10.910156 31.521484 A 1.50015 1.50015 0 0 0 10.910156 31.519531 C 10.933086 31.438901 10.975086 31.366709 11.037109 31.304688 L 29.111328 13.232422 z"
      />
    </svg>
  )
}

function TrashIcon() {
  return (
    <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
      <path
        fill="currentColor"
        d="M 20.5 4 A 1.50015 1.50015 0 0 0 19.066406 6 L 14.640625 6 C 12.803372 6 11.082924 6.9194511 10.064453 8.4492188 L 7.6972656 12 L 7.5 12 A 1.50015 1.50015 0 1 0 7.5 15 L 8.2636719 15 A 1.50015 1.50015 0 0 0 8.6523438 15.007812 L 11.125 38.085938 C 11.423352 40.868277 13.795836 43 16.59375 43 L 31.404297 43 C 34.202211 43 36.574695 40.868277 36.873047 38.085938 L 39.347656 15.007812 A 1.50015 1.50015 0 0 0 39.728516 15 L 40.5 15 A 1.50015 1.50015 0 1 0 40.5 12 L 40.302734 12 L 37.935547 8.4492188 C 36.916254 6.9202798 35.196001 6 33.359375 6 L 28.933594 6 A 1.50015 1.50015 0 0 0 27.5 4 L 20.5 4 z M 14.640625 9 L 33.359375 9 C 34.196749 9 34.974746 9.4162203 35.439453 10.113281 L 36.697266 12 L 11.302734 12 L 12.560547 10.113281 A 1.50015 1.50015 0 0 0 12.5625 10.111328 C 13.025982 9.4151428 13.801878 9 14.640625 9 z M 11.669922 15 L 36.330078 15 L 33.890625 37.765625 C 33.752977 39.049286 32.694383 40 31.404297 40 L 16.59375 40 C 15.303664 40 14.247023 39.049286 14.109375 37.765625 L 11.669922 15 z"
      />
    </svg>
  )
}

function ArchiveIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
      <path d="M20.5 7V13C20.5 16.7712 20.5 18.6569 19.3284 19.8284C18.1569 21 16.2712 21 12.5 21H11.5M3.5 7V13C3.5 16.7712 3.5 18.6569 4.67157 19.8284C5.37634 20.5332 6.3395 20.814 7.81608 20.9259" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
      <path d="M12 3H4C3.05719 3 2.58579 3 2.29289 3.29289C2 3.58579 2 4.05719 2 5C2 5.94281 2 6.41421 2.29289 6.70711C2.58579 7 3.05719 7 4 7H20C20.9428 7 21.4142 7 21.7071 6.70711C22 6.41421 22 5.94281 22 5C22 4.05719 22 3.58579 21.7071 3.29289C21.4142 3 20.9428 3 20 3H16" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
      <path d="M12 7L12 16M12 16L15 12.6667M12 16L9 12.6667" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function RestoreIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3 12a9 9 0 1 0 3-6.7" />
      <path d="M3 4v5h5" />
    </svg>
  )
}

function formatCreated(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  return `${day}.${month}.${date.getFullYear()}`
}

function formatStamp(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${formatCreated(value)}, ${hours}:${minutes}`
}

function pageWindow(current, pages) {
  if (pages <= 7) return Array.from({ length: pages }, (_, index) => index + 1)
  const near = [1, pages, current - 1, current, current + 1].filter((item) => item >= 1 && item <= pages)
  const unique = [...new Set(near)].sort((a, b) => a - b)
  const window = []
  unique.forEach((item, index) => {
    if (index > 0 && item - unique[index - 1] > 1) window.push(null)
    window.push(item)
  })
  return window
}

function variantNote(item) {
  if (!item) return 'Не запрашивали'
  if (item.status === 'done') return 'Готово'
  if (item.status === 'error') return 'Ошибка'
  if (item.status === 'running') return 'В работе'
  if (item.status === 'queued') return 'В очереди'
  return 'Не запрашивали'
}

function designCode(index) {
  return `#${String(index + 1).padStart(3, '0')}`
}

function designMark(designs, templateId) {
  if (!designs || !templateId) return null
  const index = designs.findIndex((item) => item.id === templateId)
  if (index < 0) return null
  return { number: index + 1, name: designCode(index) }
}

function formatMonth(value) {
  if (!value || value.length < 7) return ''
  const [year, month] = value.split('-')
  const date = new Date(Number(year), Number(month) - 1, 1)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' }).replace(' г.', '')
}

function ModelCard({ model, archived, busy, onArchive, onRestore }) {
  const draft = model.status === 'draft'
  const [job, setJob] = useState(null)
  const [tasksOpen, setTasksOpen] = useState(false)
  const [listOpen, setListOpen] = useState(false)
  const [wordBusy, setWordBusy] = useState(false)
  const [wordError, setWordError] = useState('')
  const [designs, setDesigns] = useState(null)
  const [picked, setPicked] = useState(null)
  const [lookOpen, setLookOpen] = useState(false)
  const [variantId, setVariantId] = useState(null)
  const [reviewBusy, setReviewBusy] = useState(false)
  const panelRef = useRef(null)
  const attempts = job?.attempts || []
  const shown = attempts.find((item) => item.id === variantId) || attempts[attempts.length - 1] || job
  const bpStatus = shown?.status || model.bp_status || ''
  const latestStatus = job?.status || model.bp_status || ''
  const live = attempts.some((item) => item.status === 'queued' || item.status === 'running') || latestStatus === 'queued' || latestStatus === 'running'
  const reviewing = attempts.some((item) => (item.tasks || []).some((task) => task.review_status === 'running'))
  const failedTasks = (shown?.tasks || []).filter((task) => task.status === 'error' && (task.attempts || 0) < 3)

  useEffect(() => {
    if (!attempts.length) return
    setVariantId((current) => {
      if (attempts.some((item) => item.id === current)) return current
      const active = attempts.find((item) => item.status === 'queued' || item.status === 'running')
      return (active || attempts[attempts.length - 1]).id
    })
  }, [job])

  useEffect(() => {
    if (!live && !reviewing) return undefined
    let stop = false
    const tick = () => {
      api
        .bpJob(model.id)
        .then((data) => {
          if (!stop) setJob(data)
        })
        .catch(() => {})
    }
    tick()
    const timer = setInterval(tick, 2000)
    return () => {
      stop = true
      clearInterval(timer)
    }
  }, [model.id, live, reviewing])

  useEffect(() => {
    if (!tasksOpen || designs) return undefined
    let stop = false
    api
      .bpCatalog()
      .then((list) => {
        if (stop) return
        setDesigns(list.templates)
        setPicked((current) => current || list.templates[0]?.id || null)
      })
      .catch((err) => {
        if (!stop) setWordError(err.message)
      })
    return () => {
      stop = true
    }
  }, [tasksOpen, designs])

  useEffect(() => {
    if (job?.template_id) setPicked((current) => current || job.template_id)
  }, [job?.template_id])

  const sendJob = async (force, templateId = picked) => {
    const designId = templateId || picked
    if (!designId) return
    setPicked(designId)
    setWordBusy(true)
    setWordError('')
    setTasksOpen(true)
    try {
      const data = await api.startBpJob(model.id, { templateId: designId, force })
      setVariantId(data.id)
      setJob(data)
    } catch (err) {
      setWordError(err.status === 409 ? err.message : 'Не получилось. Попробуйте ещё раз.')
    } finally {
      setWordBusy(false)
    }
  }

  const reviewTask = async (taskId, answer) => {
    if (!shown?.id) return
    setReviewBusy(true)
    setWordError('')
    try {
      const data = await api.reviewBpTask(model.id, shown.id, taskId, answer)
      setVariantId(shown.id)
      setJob(data)
    } catch (err) {
      setWordError(err.status === 409 ? err.message : 'Не получилось. Попробуйте ещё раз.')
    } finally {
      setReviewBusy(false)
    }
  }

  const retryFailed = async () => {
    if (!shown?.id || !failedTasks.length) return
    setWordBusy(true)
    setWordError('')
    try {
      const data = await api.retryBpJob(model.id, shown.id)
      setVariantId(shown.id)
      setJob(data)
    } catch (err) {
      setWordError(err.status === 409 ? err.message : 'Не получилось. Попробуйте ещё раз.')
    } finally {
      setWordBusy(false)
    }
  }

  useEffect(() => {
    if (!tasksOpen) return undefined
    const onKey = (event) => {
      if (event.key !== 'Escape') return
      if (lookOpen) setLookOpen(false)
      else setTasksOpen(false)
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [tasksOpen, lookOpen])

  useEffect(() => {
    panelRef.current?.querySelector('.drawer__body')?.scrollTo(0, 0)
  }, [lookOpen])

  const closeDrawer = () => {
    setLookOpen(false)
    setTasksOpen(false)
    setListOpen(false)
  }

  const openLook = (id) => {
    setPicked(id)
    setLookOpen(true)
  }

  const onWord = async () => {
    setWordError('')
    setTasksOpen(true)
    if (job) return
    setWordBusy(true)
    try {
      setJob(await api.bpJob(model.id))
    } catch (err) {
      if (err.status !== 404) setWordError(err.message)
    } finally {
      setWordBusy(false)
    }
  }
  const from = formatMonth(model.planning_from)
  const to = formatMonth(model.planning_to)
  const period = from && to ? `${from} — ${to}` : from || to
  const pickedDesign = designs?.find((item) => item.id === picked)
  const pickedMark = designMark(designs, pickedDesign?.id)
  const wordQuery = new URLSearchParams()
  if (picked) wordQuery.set('template_id', String(picked))
  if (shown?.id) wordQuery.set('job_id', String(shown.id))
  const fileQuery = wordQuery.toString()
  const wordHref = `${finModelFiles(model.id).word}?${fileQuery}`
  const pdfHref = `${finModelFiles(model.id).pdf}?${fileQuery}`
  const variantNames = ['Первый вариант', 'Второй вариант', 'Третий вариант']
  const limit = job?.limit || 3
  const used = job?.used || 0
  const canAsk = !live && (!job || ((job.status === 'done' || job.status === 'error') && used < limit))
  const askLabel = job ? 'Хочу получить ещё раз' : 'Хочу получить бизнес-план'
  const retryLabel = failedTasks.length === 1
    ? `Пройти ещё раз: ${failedTasks[0].title}`
    : 'Пройти ещё раз по несобранным пунктам'
  const look = {
    name: model.name,
    legal: model.legal_form,
    financing: model.financing,
    period,
    loan: '',
    products: [],
  }
  const gov = model.gov_program ? `Госпрограмма: ${model.gov_program}` : ''
  const facts = [period, model.legal_form, model.financing, gov].filter(Boolean)
  const firstCreated = attempts[0]?.created_at
  const lastFinished = [...attempts].reverse().find((item) => item.finished_at && item.status !== 'queued' && item.status !== 'running')?.finished_at
  return (
    <div className="model-card-wrap">
    <div className="card model-card">
      <div className="model-card__info">
        <div className="model-card__name">
          {draft || archived ? (
            model.name
          ) : (
            <Link to={`/cabinet/models/${model.id}`} className="model-card__title">
              {model.name}
            </Link>
          )}
          {draft && <span className="model-card__badge">Черновик</span>}
        </div>
        {facts.length > 0 && (
          <div className="model-card__facts">
            {facts.map((fact) => (
              <span key={fact} className="model-card__fact">
                {fact}
              </span>
            ))}
          </div>
        )}
        <div className="muted model-card__meta">
          {draft
            ? `${model.completed_steps ? `Сохранено до шага ${model.completed_steps}` : 'Шаг 1 ещё не закончен'} · Дата создания: ${formatCreated(model.created_at)}`
            : `Дата создания: ${formatCreated(model.created_at)}`}
        </div>
      </div>
      <div className="model-card__actions">
        {archived ? (
          <button type="button" className="btn btn--small btn--primary" disabled={busy} onClick={() => onRestore(model)}>
            <RestoreIcon />
            Восстановить
          </button>
        ) : draft ? (
          <>
            <Link to={`/cabinet/models/new?draft=${model.id}`} className="btn btn--small btn--primary">
              Продолжить
            </Link>
            <button
              type="button"
              className="btn btn--small btn--icon btn--archive"
              title="В архив"
              aria-label="В архив"
              disabled={busy}
              onClick={() => onArchive(model)}
            >
              <TrashIcon />
            </button>
          </>
        ) : (
          <>
            <button type="button" className="btn btn--small" onClick={onWord}>
              <WordIcon />
              Бизнес-план
            </button>
            <ExcelDownloads id={model.id} small />
            <div className="model-card__icons">
              <Link
                to={`/cabinet/models/${model.id}`}
                className="btn btn--small btn--icon btn--primary"
                title="Редактировать"
                aria-label="Редактировать"
              >
                <PencilIcon />
              </Link>
              <button
                type="button"
                className="btn btn--small btn--icon btn--archive"
                title="В архив"
                aria-label="В архив"
                disabled={busy}
                onClick={() => onArchive(model)}
              >
                <TrashIcon />
              </button>
            </div>
          </>
        )}
      </div>
    </div>
      {tasksOpen && (
        <div
          className="drawer"
          role="presentation"
          onClick={closeDrawer}
        >
          <aside
            ref={panelRef}
            className="drawer__panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby={`bp-drawer-${model.id}`}
            onClick={(event) => event.stopPropagation()}
          >
            {lookOpen && pickedDesign ? (
            <div className="drawer__body">
                <button type="button" className="drawer__back" onClick={() => setLookOpen(false)}>
                  Назад
                </button>
                <div className="drawer__head">
                  <div>
                    <h2 id={`bp-drawer-${model.id}`}>
                      {pickedMark ? pickedMark.name : designCode(0)}
                    </h2>
                    <p className="muted">{model.name}</p>
                  </div>
                  <button type="button" className="drawer__close" onClick={closeDrawer} aria-label="Закрыть">
                    ×
                  </button>
                </div>
                <div className="drawer__look drawer__look--open">
                  <BpDocument template={pickedDesign} facts={look} />
                </div>
            </div>
            ) : (
            <div className="drawer__body">
            <div className="drawer__head">
              <div>
                <h2 id={`bp-drawer-${model.id}`}>Бизнес-план</h2>
                <p className="muted">{model.name}</p>
              </div>
              <button type="button" className="drawer__close" onClick={closeDrawer} aria-label="Закрыть">
                ×
              </button>
            </div>
            {wordError && <div className="alert alert--error">{wordError}</div>}
            <div className="bp-variants">
                <div className="bp-variants__list">
                  {variantNames.map((name, index) => {
                    const item = attempts.find((attempt) => attempt.number === index + 1)
                    const mark = designMark(designs, item?.template_id)
                    return (
                      <button
                        key={name}
                        type="button"
                        className={`bp-variants__item${item?.id === shown?.id ? ' is-on' : ''}${item?.status ? ` is-${item.status}` : ''}`}
                        disabled={!item}
                        onClick={() => {
                          if (!item) return
                          setVariantId(item.id)
                          setListOpen(false)
                          if (item.template_id) setPicked(item.template_id)
                        }}
                      >
                        <span className="bp-variants__step">{index + 1}</span>
                        <span className="bp-variants__name">{mark?.name || name}</span>
                        <span className="bp-variants__note">{variantNote(item)}</span>
                      </button>
                    )
                  })}
                </div>
                {firstCreated && (
                  <p className="bp-variants__sum">
                    {formatStamp(firstCreated)}
                    {lastFinished ? ` — ${formatStamp(lastFinished)}` : ''}
                  </p>
                )}
            </div>
            {designs && bpStatus !== 'queued' && bpStatus !== 'running' && (
              <div className="bp-pick drawer__pick">
                <div className="bp-pick__row">
                  {designs.map((item, index) => (
                    <button
                      key={item.id}
                      type="button"
                      className={`bp-pick__tile${item.id === picked ? ' is-on' : ''}${item.slug === 'bank' ? ' is-bank' : ''}`}
                      style={{ '--bp': item.accent }}
                      onClick={() => setPicked(item.id)}
                    >
                      <span className={`bp-pick__mini bp-pick__mini--${item.cover}`}>
                        <strong>{designCode(index)}</strong>
                      </span>
                    </button>
                  ))}
                </div>
                {pickedDesign && (
                  <div className="bp-pick__foot">
                    <p>{pickedDesign.description}</p>
                    <div className="bp-pick__actions">
                      {canAsk && (
                        <button
                          type="button"
                          className="btn btn--primary"
                          disabled={wordBusy}
                          onClick={() => sendJob(Boolean(job), picked)}
                        >
                          {askLabel}
                        </button>
                      )}
                      <button type="button" className="btn" onClick={() => openLook(picked)}>
                        Посмотреть больше
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )}
            {!job && !wordBusy && !wordError && <BpEmpty />}
            {wordBusy && !job && <div className="spinner" />}
            {shown?.tasks && (
              <button type="button" className="bp-list-toggle" onClick={() => setListOpen((open) => !open)}>
                {listOpen ? 'Скрыть разделы' : 'Показать разделы'}
              </button>
            )}
            {listOpen && shown?.tasks && <BpTasks job={shown} onReview={reviewTask} reviewBusy={reviewBusy} />}
            {failedTasks.length > 0 && !live && (
              <>
                <button type="button" className="btn btn--primary" disabled={wordBusy} onClick={retryFailed}>
                  {retryLabel}
                </button>
                <p className="bp-variants__limit">Сбой сервера раздел повторяет сам, до 3 раз. Отказ из‑за баланса сам не повторяется. Кнопка отправляет несобранные пункты ещё раз, не больше 3 отправок на пункт.</p>
              </>
            )}
            {job && used >= limit && <p className="bp-variants__limit">Три варианта уже собраны.</p>}
            </div>
            )}
            {shown?.status === 'done' && (
              <div className="drawer__downloads">
                <a className="btn btn--primary" href={wordHref}>
                  Скачать Word
                </a>
                <a className="btn" href={pdfHref}>
                  Скачать PDF
                </a>
              </div>
            )}
          </aside>
        </div>
      )}
    </div>
  )
}

export default function FinModels() {
  const [archived, setArchived] = useState(false)
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)
  const [models, setModels] = useState(null)
  const [loading, setLoading] = useState(true)
  const [meta, setMeta] = useState({ total: 0, pages: 1, page: 1 })
  const [error, setError] = useState('')
  const [busyId, setBusyId] = useState(null)
  const [pendingArchive, setPendingArchive] = useState(null)
  const [reloadKey, setReloadKey] = useState(0)
  const requestId = useRef(0)

  useEffect(() => {
    const id = ++requestId.current
    setLoading(true)
    setError('')
    const handle = setTimeout(() => {
      api
        .listFinModels({ archived, page, q: query.trim() })
        .then((data) => {
          if (id !== requestId.current) return
          setModels(data.items)
          setMeta({ total: data.total, pages: data.pages, page: data.page })
          setLoading(false)
          if (data.page !== page) setPage(data.page)
        })
        .catch((err) => {
          if (id !== requestId.current) return
          setError(err.message)
          setLoading(false)
        })
    }, 250)
    return () => clearTimeout(handle)
  }, [archived, page, query, reloadKey])

  useEffect(() => {
    if (!pendingArchive) return undefined
    const onKey = (event) => {
      if (event.key === 'Escape' && !busyId) setPendingArchive(null)
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [pendingArchive, busyId])

  const confirmArchive = async () => {
    const model = pendingArchive
    if (!model) return
    setBusyId(model.id)
    setError('')
    try {
      await api.archiveFinModel(model.id)
      setPendingArchive(null)
      setReloadKey((value) => value + 1)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  const restoreModel = async (model) => {
    setBusyId(model.id)
    setError('')
    try {
      await api.restoreFinModel(model.id)
      setReloadKey((value) => value + 1)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  return (
    <div className="page">
      <header className="page__header page__header--row">
        <div>
          <h1>{archived ? 'Архив' : 'Фин модели'}</h1>
          <p className="muted">{archived ? 'Убранные модели. Их можно вернуть в список.' : 'Ваши финансовые модели'}</p>
        </div>
        <div className="page__header-actions">
          <button
            type="button"
            className="btn"
            onClick={() => {
              setLoading(true)
              setPage(1)
              setArchived((value) => !value)
            }}
          >
            <ArchiveIcon />
            {archived ? 'К списку' : 'Архив'}
          </button>
          {!archived && (
            <Link to="/cabinet/models/new" className="btn btn--primary">
              <PlusIcon />
              Создать фин модель
            </Link>
          )}
        </div>
      </header>

      {error && <div className="alert alert--error">{error}</div>}

      <label className="model-search">
        <span className="sr-only">Поиск</span>
        <input
          type="search"
          value={query}
          placeholder="Найти по названию"
          onChange={(event) => {
            setLoading(true)
            setQuery(event.target.value)
            setPage(1)
          }}
        />
      </label>

      {loading && !models?.length && !error && <div className="spinner" />}

      {!loading && models?.length === 0 && (
        <div className="card empty">
          <div className="empty__title">
            {query.trim() ? 'Ничего не найдено' : archived ? 'В архиве пусто' : 'Пока нет ни одной модели'}
          </div>
          <p className="muted">
            {query.trim()
              ? `По запросу «${query.trim()}» моделей нет.`
              : archived
                ? 'Сюда попадают модели, которые вы убрали из списка.'
                : 'Создайте первую финансовую модель — это займёт несколько минут.'}
          </p>
          {!archived && !query.trim() && (
            <Link to="/cabinet/models/new" className="btn btn--primary empty__action">
              <PlusIcon />
              Создать фин модель
            </Link>
          )}
        </div>
      )}

      {models?.length > 0 && (
        <>
          <div className="model-list">
            {models.map((model) => (
              <ModelCard
                key={model.id}
                model={model}
                archived={archived}
                busy={busyId === model.id}
                onArchive={setPendingArchive}
                onRestore={restoreModel}
              />
            ))}
          </div>
          <div className="pager">
            <span className="muted pager__status">
              {(meta.page - 1) * 10 + 1}–{(meta.page - 1) * 10 + models.length} из {meta.total}
            </span>
            <nav className="pager__nav" aria-label="Страницы">
                <button type="button" className="pager__btn" disabled={meta.page <= 1 || loading} onClick={() => setPage(meta.page - 1)}>
                  Назад
                </button>
                {pageWindow(meta.page, meta.pages).map((item, index) =>
                  item === null ? (
                    <span key={`gap-${index}`} className="pager__gap">
                      …
                    </span>
                  ) : (
                    <button
                      key={item}
                      type="button"
                      className={`pager__btn${item === meta.page ? ' is-on' : ''}`}
                      aria-current={item === meta.page ? 'page' : undefined}
                      onClick={() => setPage(item)}
                      disabled={loading}
                    >
                      {item}
                    </button>
                  ),
                )}
                <button
                  type="button"
                  className="pager__btn"
                  disabled={meta.page >= meta.pages || loading}
                  onClick={() => setPage(meta.page + 1)}
                >
                  Дальше
                </button>
              </nav>
          </div>
        </>
      )}

      {pendingArchive && (
        <div
          className="modal"
          role="presentation"
          onClick={() => {
            if (!busyId) setPendingArchive(null)
          }}
        >
          <div
            className="modal__card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="archive-title"
            onClick={(event) => event.stopPropagation()}
          >
            <h2 id="archive-title">Отправить в архив?</h2>
            <p className="muted">
              «{pendingArchive.name}» пропадёт из списка. Модель не удаляется: её можно вернуть кнопкой «Восстановить» в архиве.
            </p>
            <div className="modal__actions">
              <button type="button" className="btn" disabled={Boolean(busyId)} onClick={() => setPendingArchive(null)}>
                Отмена
              </button>
              <button type="button" className="btn btn--primary" disabled={Boolean(busyId)} onClick={confirmArchive}>
                В архив
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
