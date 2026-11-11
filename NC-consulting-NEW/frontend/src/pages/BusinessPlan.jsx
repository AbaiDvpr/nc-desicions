import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api } from '../api.js'
import BpDocument from '../components/BpDocument.jsx'
import BpTasks from '../components/BpTasks.jsx'

const MONTHS = [
  '',
  'январь',
  'февраль',
  'март',
  'апрель',
  'май',
  'июнь',
  'июль',
  'август',
  'сентябрь',
  'октябрь',
  'ноябрь',
  'декабрь',
]
const LEGAL = { 1: 'ИП', 2: 'ТОО' }
const FINANCING = { 1: 'Инвестор', 2: 'Кредит', 3: 'Грант', 4: 'Инвестор и кредит' }

function monthLabel(value) {
  const text = String(value || '')
  const month = Number(text.slice(5, 7))
  if (!text || month < 1 || month > 12) return ''
  return `${MONTHS[month]} ${text.slice(0, 4)}`
}

function money(value) {
  return new Intl.NumberFormat('ru-RU').format(Math.round(Number(value) || 0))
}

function factsFromForm(form) {
  const start = monthLabel(form.planning_from)
  const end = monthLabel(form.planning_to)
  const financing = Number(form.financing)
  const products = (form.volume_sales || [])
    .filter((row) => String(row.name || '').trim())
    .slice(0, 8)
    .map((row) => ({
      name: String(row.name).trim(),
      price: money(row.price),
      volume: money(row.count),
    }))
  return {
    name: String(form.name || 'Проект'),
    legal: LEGAL[Number(form.legal_form)] || '',
    financing: FINANCING[financing] || '',
    period: [start, end].filter(Boolean).join(' — '),
    products,
    loan: financing === 2 || financing === 4 ? `${form.loan_term || 0} мес., ставка ${form.loan_rate || 0}%` : '',
  }
}

export default function BusinessPlan() {
  const { id } = useParams()
  const [catalog, setCatalog] = useState(null)
  const [facts, setFacts] = useState(null)
  const [picked, setPicked] = useState(null)
  const [job, setJob] = useState(null)
  const [sending, setSending] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let live = true
    Promise.all([api.bpCatalog(), api.getFinModel(id)])
      .then(([list, model]) => {
        if (!live) return
        setCatalog(list)
        setFacts(factsFromForm(model.form || {}))
        setPicked(list.templates[0]?.id ?? null)
      })
      .catch((err) => {
        if (live) setError(err.message)
      })
    return () => {
      live = false
    }
  }, [id])

  useEffect(() => {
    api.bpJob(id).then(setJob).catch((err) => {
      if (err.status !== 404) setError(err.message)
    })
  }, [id])

  useEffect(() => {
    if (!job || (job.status !== 'queued' && job.status !== 'running')) return undefined
    const timer = setInterval(() => {
      api.bpJob(id).then(setJob).catch(() => {})
    }, 2000)
    return () => clearInterval(timer)
  }, [id, job?.status])

  const template = catalog?.templates.find((item) => item.id === picked)
  const running = job?.status === 'queued' || job?.status === 'running'

  const send = async () => {
    setSending(true)
    setError('')
    try {
      setJob(await api.startBpJob(id, { templateId: picked, force: Boolean(job) && !running }))
    } catch (err) {
      setError(err.message)
    } finally {
      setSending(false)
    }
  }

  return (
    <div className="page bp-page">
      <div className="page__header page__header--row">
        <div>
          <Link to="/cabinet/models" className="bp-back">
            К списку
          </Link>
          <h1>Бизнес-план</h1>
          {facts && <p className="muted">{facts.name}</p>}
        </div>
      </div>
      {error && <div className="alert">{error}</div>}
      {catalog && facts && (
        <>
          <p className="bp-lead">
            Выберите шаблон и отправьте сборку. Текст пишет модель, которую задал администратор.
            Word на списке скачается, когда по всем пунктам будет ответ или ошибка.
          </p>
          <div className="bp-send">
            <button type="button" className="btn btn--primary" disabled={sending || running || !picked} onClick={send}>
              {running ? 'Собирается' : job ? 'Собрать ещё раз' : 'Отправить на сборку'}
            </button>
          </div>
          {job && <BpTasks job={job} busy={sending} onRetry={send} />}
          <div className="bp-pick">
            {catalog.templates.map((item, index) => (
              <button
                key={item.id}
                type="button"
                className={`bp-pick__card${item.id === picked ? ' is-on' : ''}`}
                style={{ '--bp': item.accent }}
                onClick={() => setPicked(item.id)}
              >
                <span className={`bp-pick__mini bp-pick__mini--${item.cover}`}>
                  <span>Бизнес-план</span>
                  <strong>{facts.name}</strong>
                </span>
                <span className="bp-pick__body">
                  <strong>{`#${String(index + 1).padStart(3, '0')}`}</strong>
                  <span>{item.description}</span>
                </span>
              </button>
            ))}
          </div>
          {template && (
            <div className="bp-stage">
              <BpDocument template={template} facts={facts} />
            </div>
          )}
        </>
      )}
    </div>
  )
}
