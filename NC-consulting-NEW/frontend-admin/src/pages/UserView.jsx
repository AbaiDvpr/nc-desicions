import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api, formatDate, formatMoney } from '../api.js'

function almatyDay(value) {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Almaty',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(new Date(value))
  const pick = (type) => parts.find((part) => part.type === type).value
  return `${pick('year')}-${pick('month')}-${pick('day')}`
}

function previewQuota(dates, period, startedOn, limit, extra) {
  if (!startedOn) return null
  const start = new Date(`${startedOn}T00:00:00+05:00`)
  const today = almatyDay(new Date())
  const monthStart = new Date(`${today.slice(0, 7)}-01T00:00:00+05:00`)
  let from = start
  let to
  if (period === 'year') {
    to = new Date(start)
    to.setMonth(to.getMonth() + 12)
  } else {
    from = start > monthStart ? start : monthStart
    const [year, month] = today.slice(0, 7).split('-').map(Number)
    const next = month === 12 ? `${year + 1}-01-01` : `${year}-${String(month + 1).padStart(2, '0')}-01`
    to = new Date(`${next}T00:00:00+05:00`)
  }
  const used = (dates || []).filter((item) => {
    const moment = new Date(item)
    return moment >= from && moment < to
  }).length
  const allowance = Number(limit || 0) + Number(extra || 0)
  return { used, allowance, left: Math.max(allowance - used, 0) }
}

export default function UserView() {
  const { id } = useParams()
  const [user, setUser] = useState(null)
  const [plans, setPlans] = useState([])
  const [amount, setAmount] = useState('')
  const [comment, setComment] = useState('')
  const [direction, setDirection] = useState('credit')
  const [tariffId, setTariffId] = useState('')
  const [startedOn, setStartedOn] = useState('')
  const [extra, setExtra] = useState('0')
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api.user(id).then((row) => {
      setUser(row)
      setTariffId(String(row.quota?.tariff_id || ''))
      setStartedOn(row.quota?.started_at ? almatyDay(row.quota.started_at) : almatyDay(new Date()))
      setExtra(String(row.quota?.models_extra || 0))
    }).catch((err) => setError(err.message))
    api.tariffs().then(setPlans).catch(() => setPlans([]))
  }, [id])

  const plan = plans.find((item) => String(item.id) === String(tariffId))
  const forecast = previewQuota(user?.model_dates, plan?.period || user?.quota?.period, startedOn, plan?.models_limit ?? user?.quota?.models_limit, extra)

  async function onTariff(event) {
    event.preventDefault()
    setError('')
    setNotice('')
    setBusy(true)
    try {
      const next = await api.assignTariff(id, {
        tariff_id: Number(tariffId),
        started_on: startedOn,
        models_extra: Number(extra || 0),
      })
      setUser(next)
      setNotice('Тариф обновлён')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function onSubmit(event) {
    event.preventDefault()
    setError('')
    setNotice('')
    setBusy(true)
    try {
      const next = await api.adjustBalance(id, {
        amount: Number(amount),
        direction,
        comment: comment.trim(),
      })
      setUser(next)
      setAmount('')
      setComment('')
      setNotice(direction === 'credit' ? 'Баланс пополнен' : 'Сумма списана')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  if (!user && !error) return <div className="boot">Загрузка…</div>

  return (
    <div>
      <Link to="/users" className="back">← Пользователи</Link>
      <h1>{user?.full_name || 'Пользователь'}</h1>
      {error && <div className="alert">{error}</div>}
      {notice && <div className="ok">{notice}</div>}
      {user && (
        <>
          <div className="facts">
            <div><span className="muted">Email</span><div>{user.email}</div></div>
            <div><span className="muted">Телефон</span><div>{user.phone || '—'}</div></div>
            <div><span className="muted">Компания</span><div>{user.company || '—'}</div></div>
            <div><span className="muted">Тариф</span><div>{user.tariff_name || '—'}</div></div>
            <div><span className="muted">Баланс</span><div>{formatMoney(user.balance)}</div></div>
            <div>
              <span className="muted">Модели за {user.quota?.period_label || 'период'}</span>
              <div>{user.quota ? `${user.quota.left} из ${user.quota.allowance}` : '—'}</div>
            </div>
          </div>

          <form className="card form" onSubmit={onTariff}>
            <h2>Тариф</h2>
            <p className="muted">
              Месячный тариф считает модели только текущего месяца, начиная с выбранной даты. В новом месяце лимит начинается заново.
              Годовой считает модели с выбранной даты до конца срока. Добавка прибавляется к лимиту, остаток считается сам.
            </p>
            <div className="form__row">
              <label>
                Тариф
                <select value={tariffId} onChange={(event) => setTariffId(event.target.value)} required>
                  <option value="">Выберите</option>
                  {plans.map((item) => (
                    <option key={item.id} value={item.id}>
                      {item.name}, {item.period === 'year' ? 'год' : 'месяц'}, {item.models_limit} моделей
                    </option>
                  ))}
                </select>
              </label>
              <label>
                С какого периода
                <input type="date" value={startedOn} onChange={(event) => setStartedOn(event.target.value)} required />
              </label>
              <label>
                Добавка моделей
                <input type="number" min="0" value={extra} onChange={(event) => setExtra(event.target.value)} required />
              </label>
              <button type="submit" className="btn" disabled={busy || !tariffId}>Сохранить тариф</button>
            </div>
            {forecast ? (
              <p>
                Уже создано за период: {forecast.used}. Можно ещё: {forecast.left} из {forecast.allowance}.
              </p>
            ) : null}
          </form>

          <form className="card form" onSubmit={onSubmit}>
            <h2>Баланс</h2>
            <div className="form__row">
              <label>
                Операция
                <select value={direction} onChange={(event) => setDirection(event.target.value)}>
                  <option value="credit">Зачислить</option>
                  <option value="debit">Списать</option>
                </select>
              </label>
              <label>
                Сумма, ₸
                <input type="number" min="1" value={amount} onChange={(event) => setAmount(event.target.value)} required />
              </label>
              <label className="grow">
                Комментарий
                <input value={comment} onChange={(event) => setComment(event.target.value)} required minLength={2} />
              </label>
              <button type="submit" className="btn" disabled={busy}>Сохранить</button>
            </div>
          </form>

          <h2>Движения</h2>
          <div className="table-wrap">
            <table>
              <thead>
                <tr><th>Дата</th><th>Сумма</th><th>Комментарий</th></tr>
              </thead>
              <tbody>
                {user.ledger.map((row) => (
                  <tr key={row.id}>
                    <td>{formatDate(row.created_at)}</td>
                    <td>{formatMoney(row.amount)}</td>
                    <td>{row.comment}</td>
                  </tr>
                ))}
                {user.ledger.length === 0 && <tr><td colSpan={3} className="muted">Движений нет</td></tr>}
              </tbody>
            </table>
          </div>
        </>
      )}
    </div>
  )
}
