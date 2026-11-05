import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api.js'
import { formatMoney } from '../utils.js'

const LIMITS = [
  ['models_limit', 'модель', 'модели', 'моделей'],
  ['edits_limit', 'правка', 'правки', 'правок'],
  ['companies_limit', 'компания', 'компании', 'компаний'],
  ['plans_limit', 'план', 'плана', 'планов'],
]

function plural(count, one, few, many) {
  const mod10 = count % 10
  const mod100 = count % 100
  if (mod10 === 1 && mod100 !== 11) return one
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return few
  return many
}

function isCurrent(plan, current) {
  return Boolean(current?.name === plan.name && current?.period === plan.period && !current?.expired)
}

export default function Tariffs() {
  const [period, setPeriod] = useState('month')
  const [plans, setPlans] = useState(null)
  const [current, setCurrent] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.currentTariff()
      .then((payload) => {
        if (!active || !payload.subscription) return
        setCurrent({ ...payload.subscription, expired: payload.expired })
      })
      .catch(() => {})
    return () => {
      active = false
    }
  }, [])

  useEffect(() => {
    let active = true
    setError('')
    api.listTariffs(period)
      .then((rows) => {
        if (active) setPlans(rows)
      })
      .catch((err) => {
        if (active) setError(err.message)
      })
    return () => {
      active = false
    }
  }, [period])

  return (
    <div className="page tariffs">
      <header className="plans__head">
        <div className="plans__title">
          <h1>Тарифы</h1>
          <Link to="/cabinet/tariff">Мой тариф</Link>
        </div>
        <div className="period-switch" role="tablist" aria-label="Срок оплаты">
          <button type="button" role="tab" aria-selected={period === 'month'} className={period === 'month' ? 'is-active' : ''} onClick={() => setPeriod('month')}>
            Месяц
          </button>
          <button type="button" role="tab" aria-selected={period === 'year'} className={period === 'year' ? 'is-active' : ''} onClick={() => setPeriod('year')}>
            Год
            <span className="period-switch__badge">−50%</span>
          </button>
        </div>
      </header>

      {error ? <div className="alert alert--error">{error}</div> : null}
      {!plans && !error ? <p className="muted">Загружаем…</p> : null}

      <div className={`plan-grid ${plans?.length === 2 ? 'plan-grid--pair' : ''}`}>
        {plans?.map((plan) => {
          const active = isCurrent(plan, current)
          const monthly = plan.period === 'year' ? Math.round(plan.price / 12) : plan.price
          const label = active ? (plan.is_default ? 'Текущий' : 'Продлить') : (plan.price ? 'Купить' : 'Выбрать')
          return (
            <article className={`plan ${active ? 'is-current' : ''}`} key={plan.id}>
              <div className="plan__top">
                <h2>{plan.name}</h2>
                {active ? <span className="plan__now">Сейчас</span> : null}
              </div>
              <p className="plan__price">
                {plan.price ? formatMoney(monthly) : 'Бесплатно'}
                <small>
                  {plan.price
                    ? plan.period === 'year'
                      ? `в месяц, ${formatMoney(plan.price)} за год`
                      : 'в месяц'
                    : 'без срока'}
                </small>
              </p>
              <ul className="plan__limits">
                {LIMITS.map(([key, one, few, many]) => (
                  <li key={key}>
                    <b>{plan[key]}</b>
                    <span>{plural(plan[key], one, few, many)}</span>
                  </li>
                ))}
              </ul>
              <Link
                to={active && plan.is_default ? '/cabinet/tariff' : `/cabinet/tariffs/${plan.id}`}
                className={`btn ${active && plan.is_default ? '' : 'btn--primary'}`}
              >
                {label}
              </Link>
            </article>
          )
        })}
      </div>
    </div>
  )
}
