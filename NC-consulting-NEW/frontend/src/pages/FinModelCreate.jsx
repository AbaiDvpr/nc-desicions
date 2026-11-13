import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { api } from '../api.js'
import ExcelDownloads from '../components/ExcelDownloads.jsx'
import Toast from '../components/Toast.jsx'
import { draftProgress, formWarnings, stepAnchor, stepAssetTab, stepField, stepProblem, toPayload } from './finmodel/helpers.js'
import { DEFAULT_LIFE_YEARS, STEPS, initialForm, newVolume } from './finmodel/options.js'
import { CompanyStep, ExpensesStep, InvestmentStep, SalesStep } from './finmodel/steps.jsx'

const STEP_COMPONENTS = [CompanyStep, SalesStep, ExpensesStep, InvestmentStep]

const GENERATION_STAGES = [
  'Анализируем данные компании',
  'Строим прогноз выручки',
  'Рассчитываем расходы и денежный поток',
  'Собираем отчёт',
]

const STAGE_DURATION_MS = 1500

function CheckIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 12.5l4.5 4.5L19 7.5" />
    </svg>
  )
}

function ArrowLeftIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
  )
}

function ArrowRightIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 12h14M12 5l7 7-7 7" />
    </svg>
  )
}

function SparkIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
      <path d="M12 2l2.2 6.3L20.5 10l-6.3 2.2L12 18.5l-2.2-6.3L3.5 10l6.3-1.7L12 2z" />
      <path d="M19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9L19 15z" opacity="0.6" />
    </svg>
  )
}

function Stepper({ current, reached, onPick }) {
  return (
    <ol className="stepper">
      {STEPS.map((step, index) => {
        const state = index === current ? 'active' : index <= reached ? 'done' : 'upcoming'
        const open = index <= reached && index < STEPS.length
        return (
          <li key={step.label} className={`stepper__item stepper__item--${state}`}>
            <button
              type="button"
              className="stepper__jump"
              disabled={!open || index === current}
              onClick={() => onPick(index)}
            >
              <div className="stepper__circle">{state === 'done' ? <CheckIcon /> : index + 1}</div>
              <div className="stepper__label">
                <span className="stepper__caption">Шаг {index + 1}</span>
                {step.label}
              </div>
            </button>
          </li>
        )
      })}
    </ol>
  )
}

function asDate(value) {
  const text = String(value || '')
  if (/^\d{4}-\d{2}$/.test(text)) return `${text}-01`
  return text
}

function blankCapacity(saved) {
  const source = saved || {}
  const values = Object.values(source)
  if (values.length > 0 && values.every((value) => !Number(value))) return {}
  return { ...source }
}

function emptyRate(value) {
  return value === '' || value === null || value === undefined
}

/** Подставить дефолты справочника только в пустые поля ставок. */
export function withRateDefaults(form, rates) {
  if (!rates) return form
  const next = { ...form }
  const too = Number(next.legal_form) === 2
  if (emptyRate(next.tax_simplified_rate)) {
    next.tax_simplified_rate = too
      ? (rates.simplified_too_rate ?? rates.simplified_rate)
      : (rates.simplified_ip_rate ?? rates.simplified_rate)
  }
  if (emptyRate(next.vat_rate)) next.vat_rate = rates.vat_rate
  if (emptyRate(next.opv_rate)) next.opv_rate = rates.opv_rate ?? 10
  if (emptyRate(next.so_rate)) next.so_rate = rates.so_rate ?? 5
  if (emptyRate(next.opvr_rate)) next.opvr_rate = rates.opvr_rate ?? 3.5
  if (emptyRate(next.osms_rate)) next.osms_rate = rates.osms_rate ?? 3
  if (emptyRate(next.vosms_rate)) next.vosms_rate = rates.vosms_rate
  if (emptyRate(next.social_tax_rate)) next.social_tax_rate = rates.social_tax_rate
  if (emptyRate(next.tax_cit_rate)) {
    next.tax_cit_rate = too ? rates.cit_too_rate : rates.cit_ip_rate
  }
  return next
}

function withAssetLifeDefaults(form) {
  const next = { ...form }
  for (const [key, years] of Object.entries(DEFAULT_LIFE_YEARS)) {
    next[key] = (form[key] || []).map((row) => {
      if (row.life_years !== '' && row.life_years != null) return row
      return { ...row, life_years: String(years) }
    })
  }
  return next
}

function restoreForm(saved, rates) {
  const base = initialForm()
  const merged = {
    ...base,
    ...saved,
    planning_from: asDate(saved.planning_from),
    planning_to: asDate(saved.planning_to),
    sales_start: asDate(saved.sales_start),
    planned_volume: asDate(saved.planned_volume),
    sales_start_load:
      saved.sales_start_load === '' || saved.sales_start_load == null ? 50 : saved.sales_start_load,
    other_income_start: asDate(saved.other_income_start),
    other_income_period: saved.other_income_period || 1,
    own: { ...base.own, ...(saved.own || {}) },
    attracted: { ...base.attracted, ...(saved.attracted || {}) },
    credit: { ...base.credit, ...(saved.credit || {}) },
    planned_capacity: blankCapacity(saved.planned_capacity),
    inventory: saved.inventory || [],
    volume_sales: saved.volume_sales?.length
      ? saved.volume_sales.map((row) => ({ ...newVolume(), ...row, sales_start: asDate(row.sales_start) }))
      : base.volume_sales,
  }
  return withRateDefaults(withAssetLifeDefaults(merged), rates)
}

function Generation({ model, error, onRetry, onEdit, editing }) {
  const [stage, setStage] = useState(0)
  const last = GENERATION_STAGES.length - 1
  const done = stage > last

  useEffect(() => {
    if (error || done || (stage === last && !model)) return
    const timer = setTimeout(() => setStage((s) => s + 1), STAGE_DURATION_MS)
    return () => clearTimeout(timer)
  }, [stage, last, done, model, error])

  if (error) {
    return (
      <div className="card generation">
        <div className="ai-orb ai-orb--error">!</div>
        <h2>Не удалось собрать модель</h2>
        <p className="muted">{error}</p>
        <div className="generation__actions generation__actions--spaced">
          <button type="button" className="btn" onClick={onEdit}>
            Вернуться к данным
          </button>
          <button type="button" className="btn btn--primary" onClick={onRetry}>
            Повторить
          </button>
        </div>
      </div>
    )
  }

  const progress = Math.round((stage / GENERATION_STAGES.length) * 100)

  return (
    <div className="card generation">
      <div className={`ai-orb ${done ? 'ai-orb--done' : ''}`}>
        {done ? <CheckIcon /> : <span>NC-AI</span>}
      </div>

      <h2>{done ? 'Отчёт готов' : 'Генерируем через NC-AI'}</h2>
      <p className="muted">
        {done
          ? `Финансовая модель «${model.name}» ${editing ? 'обновлена' : 'сохранена'}. Фин модель можно скачать. Бизнес-план запрашивается кнопкой «Бизнес-план» в списке.`
          : 'Собираем для вас помесячную финансовую модель. Это займёт несколько секунд.'}
      </p>

      <div className="progress">
        <div className="progress__bar" style={{ width: `${progress}%` }} />
      </div>

      <ul className="generation__stages">
        {GENERATION_STAGES.map((label, index) => {
          const state = index < stage ? 'done' : index === stage ? 'active' : 'upcoming'
          return (
            <li key={label} className={`generation__stage generation__stage--${state}`}>
              <span className="generation__dot">{state === 'done' && <CheckIcon />}</span>
              {label}
            </li>
          )
        })}
      </ul>

      {done && (
        <div className="generation__actions">
          <button type="button" className="btn" onClick={onEdit}>
            Изменить данные
          </button>
          <Link to="/cabinet/models" className="btn">
            К фин моделям
          </Link>
          <ExcelDownloads id={model.id} />
        </div>
      )}
    </div>
  )
}

export default function FinModelCreate() {
  const { id: routeId } = useParams()
  const navigate = useNavigate()
  const editing = Boolean(routeId)
  const [params] = useSearchParams()
  const requestedDraft = editing ? null : params.get('draft')
  const [current, setCurrent] = useState(0)
  const [reached, setReached] = useState(0)
  const [form, setForm] = useState(initialForm)
  const [draftId, setDraftId] = useState(requestedDraft ? Number(requestedDraft) : null)
  const [savedId, setSavedId] = useState(editing ? Number(routeId) : null)
  const [updated, setUpdated] = useState(editing)
  const [model, setModel] = useState(null)
  const [error, setError] = useState('')
  const [attempt, setAttempt] = useState(0)
  const [notice, setNotice] = useState(null)
  const [offer, setOffer] = useState(null)
  const [saving, setSaving] = useState(false)
  const [loadingDraft, setLoadingDraft] = useState(Boolean(requestedDraft || editing))
  const [loadError, setLoadError] = useState('')
  const [rateDefaults, setRateDefaults] = useState(null)
  const ratesRef = useRef(null)
  const generating = current >= STEPS.length
  const isLast = current === STEPS.length - 1
  const step = STEPS[current]
  const StepFields = STEP_COMPONENTS[current]

  const set = (key, value) => setForm((currentForm) => ({ ...currentForm, [key]: value }))
  const showNotice = (tone, text) => setNotice({ tone, text, id: Date.now() })

  useEffect(() => {
    let cancel = false
    const fallback = {
      simplified_rate: 4,
      simplified_ip_rate: 4,
      simplified_too_rate: 4,
      cit_ip_rate: 10,
      cit_too_rate: 20,
      vat_rate: 16,
      opv_rate: 10,
      so_rate: 5,
      opvr_rate: 3.5,
      osms_rate: 3,
      vosms_rate: 2,
      social_tax_rate: 6,
      nbk_bank_spread: 4,
    }
    api
      .femRates()
      .then((rates) => {
        if (cancel) return
        ratesRef.current = rates
        setRateDefaults(rates)
        setForm((current) => withRateDefaults(current, rates))
      })
      .catch(() => {
        if (cancel) return
        ratesRef.current = fallback
        setRateDefaults(fallback)
        setForm((current) => withRateDefaults(current, fallback))
      })
    return () => {
      cancel = true
    }
  }, [])

  const syncDraftUrl = (id) => {
    if (editing || !id) return
    const currentDraft = params.get('draft')
    if (String(currentDraft || '') === String(id)) return
    navigate(`/cabinet/models/new?draft=${id}`, { replace: true })
  }

  /** Черновик при создании (не архив). Для редактирования ready-модели не вызываем. */
  const persistDraft = async ({ nextReached } = {}) => {
    if (editing) return null
    // Индексы шагов 0..3; на «Собрать» next=4 — не слать reached_step=5 (API le=4).
    const lastIndex = STEPS.length - 1
    const effectiveReached = Math.min(lastIndex, Math.max(reached, nextReached ?? reached))
    const progress = draftProgress(form, effectiveReached)
    const payload = {
      form,
      completed_steps: Math.min(STEPS.length, progress.complete),
      reached_step: Math.min(STEPS.length, Math.max(1, effectiveReached + 1)),
    }
    const saved = draftId ? await api.updateDraft(draftId, payload) : await api.saveDraft(payload)
    setDraftId(saved.id)
    syncDraftUrl(saved.id)
    return { saved, progress }
  }

  useEffect(() => {
    if (!editing) return undefined
    let cancel = false
    api
      .getFinModel(routeId)
      .then((detail) => {
        if (cancel) return
        setForm(restoreForm(detail.form, ratesRef.current))
        setSavedId(detail.id)
        setReached(STEPS.length - 1)
        setCurrent(0)
      })
      .catch((err) => {
        if (!cancel) setLoadError(err.message)
      })
      .finally(() => {
        if (!cancel) setLoadingDraft(false)
      })
    return () => {
      cancel = true
    }
  }, [editing, routeId])

  useEffect(() => {
    if (!requestedDraft) return undefined
    let cancel = false
    api
      .getDraft(requestedDraft)
      .then((draft) => {
        if (cancel) return
        setForm(restoreForm(draft.form, ratesRef.current))
        setDraftId(draft.id)
        const opened = Math.max(0, Math.min((draft.reached_step || 1) - 1, STEPS.length - 1))
        const resume = Math.max(0, Math.min(draft.completed_steps || 0, STEPS.length - 1))
        setReached(opened)
        setCurrent(draft.completed_steps < draft.reached_step ? resume : opened)
      })
      .catch((err) => {
        if (!cancel) showNotice('error', err.message)
      })
      .finally(() => {
        if (!cancel) setLoadingDraft(false)
      })
    return () => {
      cancel = true
    }
  }, [requestedDraft])

  const goTo = (index) => {
    setOffer(null)
    setCurrent(index)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const generate = async () => {
    setUpdated(Boolean(savedId))
    setModel(null)
    setError('')
    setAttempt((a) => a + 1)
    try {
      const payload = toPayload(form)
      const saved = savedId
        ? await api.updateFinModel(savedId, payload)
        : await api.createFinModel(payload, draftId)
      setSavedId(saved.id)
      setModel(saved)
    } catch (err) {
      setError(err.message)
    }
  }

  const saveDraft = async () => {
    setSaving(true)
    try {
      const result = await persistDraft({ nextReached: reached })
      const progress = result?.progress || draftProgress(form, reached)
      if (progress.blocked !== null) {
        setOffer({ steps: progress.complete, blocked: progress.blocked })
        const text =
          progress.complete < 1
            ? 'Черновик сохранён. Шаг 1 ещё нужно заполнить.'
            : `Черновик сохранён до шага ${progress.complete}. Шаг ${progress.blocked + 1} заполнен не полностью.`
        showNotice('ok', text)
      } else {
        setOffer(null)
        const text =
          progress.complete === 1 ? 'Черновик сохранён: шаг 1.' : `Черновик сохранён: шаги 1–${progress.complete}.`
        showNotice('ok', text)
      }
    } catch (err) {
      showNotice('error', err.message)
    } finally {
      setSaving(false)
    }
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (form.planning_to < form.planning_from) {
      showNotice('error', 'Дата окончания проекта раньше даты начала')
      return
    }
    const problem = stepProblem(form, current)
    if (problem) {
      showNotice('error', problem)
      const anchor = stepAnchor(form, current)
      const field = stepField(form, current)
      const assetTab = stepAssetTab(form, current)
      if (anchor) {
        window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: anchor }))
      }
      if (assetTab) {
        window.dispatchEvent(new CustomEvent('fm-asset-tab', { detail: assetTab }))
      }
      if (field) {
        window.dispatchEvent(new CustomEvent('fm-highlight-field', { detail: field }))
      }
      requestAnimationFrame(() => {
        const target =
          (field && document.querySelector(`[data-field="${field}"]`)) ||
          (anchor && document.querySelector(`[data-section="${anchor}"]`))
        target?.scrollIntoView({ behavior: 'smooth', block: 'center' })
      })
      return
    }
    setNotice(null)
    setOffer(null)
    const next = current + 1
    if (!editing) {
      setSaving(true)
      try {
        await persistDraft({ nextReached: Math.max(reached, next) })
      } catch (err) {
        showNotice('error', err.message || 'Не удалось сохранить черновик')
        setSaving(false)
        return
      }
      setSaving(false)
    }
    setReached((value) => Math.max(value, next))
    goTo(next)
    if (isLast) generate()
  }

  const openCollapsedSection = (event) => {
    const details = event.target.closest('details')
    if (details && !details.open) details.open = true
  }

  return (
    <div className="page">
      <Link to="/cabinet/models" className="back-link">
        <ArrowLeftIcon />
        К фин моделям
      </Link>

      <header className="page__header">
        <h1>{editing ? 'Редактирование фин модели' : 'Новая фин модель'}</h1>
        <p className="muted">
          {editing
            ? 'Пройдите по шагам, поправьте данные и сохраните. Фин модель соберётся заново. Бизнес-план запрашивается в списке.'
            : 'Заполните 4 шага — NC-AI соберёт помесячную модель. Бизнес-план потом запрашивается в списке.'}
        </p>
      </header>

      <Stepper current={current} reached={reached} onPick={goTo} />

      <Toast
        key={notice?.id}
        message={notice?.text || ''}
        tone={notice?.tone === 'ok' ? 'ok' : 'error'}
        place="top"
        duration={4500}
        onClose={() => setNotice(null)}
      />

      {generating ? (
        <Generation
          key={attempt}
          model={model}
          error={error}
          editing={updated}
          onRetry={generate}
          onEdit={() => goTo(STEPS.length - 1)}
        />
      ) : loadingDraft ? (
        <div className="spinner" />
      ) : loadError ? (
        <div className="alert alert--error">{loadError}</div>
      ) : (
        <form
          className="card wizard__card"
          noValidate
          onSubmit={handleSubmit}
          onInvalidCapture={openCollapsedSection}
        >
          {current !== 0 && (
            <p className="wizard__result">
              <b>Результат:</b> {step.result}
            </p>
          )}

          <div className="wizard__fields">
            <StepFields form={form} set={set} notify={showNotice} rateDefaults={rateDefaults} />
          </div>

          {isLast && formWarnings(form).length > 0 && (
            <div className="alert alert--warn draft-offer form-warnings">
              <b>Проверьте перед формированием модели</b>
              <ul>
                {formWarnings(form).map((warning, index) => (
                  <li key={index}>
                    {warning.text}{' '}
                    <button type="button" className="btn btn--small" onClick={() => goTo(warning.step)}>
                      Шаг {warning.step + 1}
                    </button>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {offer && (
            <div className="alert alert--warn draft-offer">
              <span>
                {offer.steps < 1
                  ? 'Черновик уже в списке. Шаг 1 ещё нужно заполнить.'
                  : `Черновик сохранён до шага ${offer.steps}. Шаг ${offer.blocked + 1} заполнен не полностью.`}
              </span>
              <div className="draft-offer__actions">
                <button
                  type="button"
                  className="btn btn--small"
                  onClick={() => {
                    setOffer(null)
                    goTo(offer.blocked)
                  }}
                >
                  Заполнить шаг {offer.blocked + 1}
                </button>
              </div>
            </div>
          )}

          <div className="wizard__actions">
            <button type="button" className="btn wizard__back" onClick={() => goTo(current - 1)} disabled={current === 0}>
              <ArrowLeftIcon />
              Назад
            </button>
            <div className="wizard__actions-main">
              {!editing && (
                <button type="button" className="btn" disabled={saving} onClick={() => saveDraft()}>
                  {saving ? 'Сохраняем…' : 'Сохранить как черновик'}
                </button>
              )}
              <button type="submit" className="btn btn--primary">
                {isLast ? (
                  <>
                    <SparkIcon />
                    {editing || savedId ? 'Сохранить изменения' : 'Сгенерировать через NC-AI'}
                  </>
                ) : (
                  <>
                    Далее
                    <ArrowRightIcon />
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      )}
    </div>
  )
}
