import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, finModelFiles } from '../api.js'
import { ExcelIcon, WordIcon } from '../components/FileIcons.jsx'
import { capexProblem, toPayload } from './finmodel/helpers.js'
import { STEPS, initialForm } from './finmodel/options.js'
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

function SparkIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
      <path d="M12 2l2.2 6.3L20.5 10l-6.3 2.2L12 18.5l-2.2-6.3L3.5 10l6.3-1.7L12 2z" />
      <path d="M19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9L19 15z" opacity="0.6" />
    </svg>
  )
}

function Stepper({ current }) {
  return (
    <ol className="stepper">
      {STEPS.map((step, index) => {
        const state = index < current ? 'done' : index === current ? 'active' : 'upcoming'
        return (
          <li key={step.label} className={`stepper__item stepper__item--${state}`}>
            <div className="stepper__circle">{state === 'done' ? <CheckIcon /> : index + 1}</div>
            <div className="stepper__label">
              <span className="stepper__caption">Шаг {index + 1}</span>
              {step.label}
            </div>
          </li>
        )
      })}
    </ol>
  )
}

function Generation({ model, error, onRetry, onEdit }) {
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
  const files = model && finModelFiles(model.id)

  return (
    <div className="card generation">
      <div className={`ai-orb ${done ? 'ai-orb--done' : ''}`}>
        {done ? <CheckIcon /> : <span>NC-AI</span>}
      </div>

      <h2>{done ? 'Отчёт готов' : 'Генерируем через NC-AI'}</h2>
      <p className="muted">
        {done
          ? `Финансовая модель «${model.name}» сохранена. Скачайте бизнес-план в Word или модель в Excel.`
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
          <Link to="/cabinet/models" className="btn">
            К фин моделям
          </Link>
          <a href={files.word} download className="btn btn--primary">
            <WordIcon />
            Скачать Word
          </a>
          <a href={files.excel} download className="btn btn--excel">
            <ExcelIcon />
            Скачать Excel
          </a>
        </div>
      )}
    </div>
  )
}

export default function FinModelCreate() {
  const [current, setCurrent] = useState(0)
  const [form, setForm] = useState(initialForm)
  const [model, setModel] = useState(null)
  const [error, setError] = useState('')
  const [attempt, setAttempt] = useState(0)
  const [notice, setNotice] = useState('')
  const generating = current >= STEPS.length
  const isLast = current === STEPS.length - 1
  const step = STEPS[current]
  const StepFields = STEP_COMPONENTS[current]

  const set = (key, value) => setForm((currentForm) => ({ ...currentForm, [key]: value }))

  const goTo = (index) => {
    setCurrent(index)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const generate = async () => {
    setModel(null)
    setError('')
    setAttempt((a) => a + 1)
    try {
      setModel(await api.createFinModel(toPayload(form)))
    } catch (err) {
      setError(err.message)
    }
  }

  const handleSubmit = (event) => {
    event.preventDefault()
    if (form.planning_to < form.planning_from) {
      setNotice('Дата окончания проекта раньше даты начала')
      return
    }
    if (isLast) {
      const problem = capexProblem(form)
      if (problem) {
        setNotice(problem)
        return
      }
    }
    setNotice('')
    goTo(current + 1)
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
        <h1>Новая фин модель</h1>
        <p className="muted">Заполните 4 шага — NC-AI соберёт помесячную модель и бизнес-план</p>
      </header>

      <Stepper current={current} />

      {generating ? (
        <Generation
          key={attempt}
          model={model}
          error={error}
          onRetry={generate}
          onEdit={() => goTo(STEPS.length - 1)}
        />
      ) : (
        <form className="card wizard__card" onSubmit={handleSubmit} onInvalidCapture={openCollapsedSection}>
          <div className="wizard__step-caption">
            Шаг {current + 1} из {STEPS.length}
          </div>
          <h2>{step.title}</h2>
          <p className="muted">{step.description}</p>
          <p className="wizard__result">
            <b>Результат:</b> {step.result}
          </p>

          <div className="wizard__fields">
            <StepFields form={form} set={set} />
          </div>

          {notice && <div className="alert">{notice}</div>}

          <div className="wizard__actions">
            <button type="button" className="btn" onClick={() => goTo(current - 1)} disabled={current === 0}>
              Назад
            </button>
            <button type="submit" className="btn btn--primary">
              {isLast ? (
                <>
                  <SparkIcon />
                  Сгенерировать через NC-AI
                </>
              ) : (
                'Далее'
              )}
            </button>
          </div>
        </form>
      )}
    </div>
  )
}
