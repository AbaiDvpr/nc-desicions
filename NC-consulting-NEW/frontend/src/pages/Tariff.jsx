import { useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { api } from '../api.js'
import Success from '../components/Success.jsx'
import { formatDate, formatMoney, formatSignedMoney } from '../utils.js'

function daysLabel(count) {
  const mod10 = count % 10
  const mod100 = count % 100
  if (mod10 === 1 && mod100 !== 11) return 'день'
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'дня'
  return 'дней'
}

function Meter({ label, value, hint, percent }) {
  return (
    <div className="tariff-meter">
      <div className="tariff-meter__top">
        <span>{label}</span>
        <strong>{value}</strong>
      </div>
      <div className="tariff-meter__track" aria-hidden="true">
        <span style={{ width: `${percent}%` }} />
      </div>
      {hint ? <p className="muted">{hint}</p> : null}
    </div>
  )
}

export default function Tariff() {
  const location = useLocation()
  const [data, setData] = useState(null)
  const [error, setError] = useState('')
  const notice = location.state?.notice || ''

  useEffect(() => {
    let active = true
    api.currentTariff()
      .then((payload) => {
        if (active) setData(payload)
      })
      .catch((err) => {
        if (active) setError(err.message)
      })
    return () => {
      active = false
    }
  }, [])

  const sub = data?.subscription
  const allowance = data?.models_allowance || sub?.models_limit || 0
  const modelPercent = allowance ? Math.round((data.models_left / allowance) * 100) : 0
  const usedHint = data
    ? `Использовано за ${data.period_label || 'период'}: ${data.models_used}.${
        data.models_extra ? ` Лимит ${sub?.models_limit || 0}, добавка ${data.models_extra}.` : ''
      }`
    : ''

  return (
    <div className="page tariff">
      <header className="page__header tariff__header">
        <h1>Мой тариф</h1>
        <Link to="/cabinet/tariffs" className="btn btn--primary">Сменить тариф</Link>
      </header>

      <Success>{notice}</Success>
      {error ? <div className="alert alert--error">{error}</div> : null}
      {!data && !error ? <p className="muted">Загружаем…</p> : null}

      {sub ? (
        <>
          <section className="card tariff-current">
            <div>
              <p className="muted">Текущий план</p>
              <h2>{sub.name}</h2>
              <p className="tariff-current__price">
                {sub.price ? formatMoney(sub.price) : 'Бесплатно'}
                <span>{sub.period === 'year' ? 'в год' : 'в месяц'}</span>
              </p>
            </div>
            <dl>
              <div>
                <dt>Действует до</dt>
                <dd>{sub.everlasting ? 'Бессрочно' : formatDate(sub.expires_at)}</dd>
              </div>
              <div>
                <dt>Баланс</dt>
                <dd>{formatMoney(data.balance)} <Link to="/cabinet/balance">Пополнить</Link></dd>
              </div>
            </dl>
            {data.expired ? <div className="alert alert--error">Срок тарифа истёк. Выберите новый план.</div> : null}
          </section>

          <section className="tariff-meters">
            <Meter
              label="Моделей осталось"
              value={`${data.models_left} из ${allowance}`}
              percent={modelPercent}
              hint={usedHint}
            />
            <Meter
              label="Срок"
              value={sub.everlasting ? 'Бессрочно' : `${data.days_left} ${daysLabel(data.days_left)}`}
              percent={sub.everlasting ? 100 : data.expire_progress}
            />
          </section>

          <section className="card">
            <h2>Что входит</h2>
            <ul className="tariff-features">
              {sub.features.map((item) => <li key={item}>{item}</li>)}
            </ul>
          </section>
        </>
      ) : null}

      <section className="card">
        <h2>История платежей</h2>
        {data?.payments.length ? (
          <ul className="tariff-history">
            {data.payments.map((row) => (
              <li key={row.id}>
                <div>
                  <strong>{row.comment}</strong>
                  <span className="muted">{formatDate(row.created_at)}</span>
                </div>
                <b className={row.amount < 0 ? 'is-out' : 'is-in'}>{formatSignedMoney(row.amount)}</b>
              </li>
            ))}
          </ul>
        ) : (
          <p className="muted">Платежей пока нет.</p>
        )}
      </section>
    </div>
  )
}
