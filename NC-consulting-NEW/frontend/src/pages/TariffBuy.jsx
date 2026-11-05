import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { api } from '../api.js'
import { useAuth } from '../auth/AuthContext.jsx'
import { formatMoney } from '../utils.js'

export default function TariffBuy() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user, setUser } = useAuth()
  const [plan, setPlan] = useState(null)
  const [phone, setPhone] = useState(user?.phone || '')
  const [comment, setComment] = useState('')
  const [confirm, setConfirm] = useState(false)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState('')

  useEffect(() => {
    api.me().then(setUser).catch(() => {})
  }, [setUser])

  useEffect(() => {
    let active = true
    api.getTariff(id)
      .then((row) => {
        if (active) setPlan(row)
      })
      .catch((err) => {
        if (active) setError(err.message)
      })
    return () => {
      active = false
    }
  }, [id])

  const payload = { email: user.email, phone, comment, confirm }

  const payFromBalance = async (event) => {
    event.preventDefault()
    setError('')
    setBusy('balance')
    try {
      const bought = await api.buyTariff(id, payload)
      const fresh = await api.me()
      setUser(fresh)
      navigate('/cabinet/tariff', {
        replace: true,
        state: { notice: `Тариф «${bought.name}» подключён` },
      })
    } catch (err) {
      setError(err.message)
      setBusy('')
    }
  }

  const payWooppay = async () => {
    setError('')
    setBusy('wooppay')
    try {
      const { url } = await api.payTariffWooppay(id, payload)
      window.location.assign(url)
    } catch (err) {
      setError(err.message)
      setBusy('')
    }
  }

  const shown = plan?.period === 'year' ? Math.round(plan.price / 12) : plan?.price

  return (
    <div className="page tariff-buy">
      <header className="page__header">
        <h1>Подтверждение</h1>
        <Link to="/cabinet/tariffs" className="muted">К списку тарифов</Link>
      </header>

      {error ? <div className="alert alert--error">{error}</div> : null}
      {!plan && !error ? <p className="muted">Загружаем…</p> : null}

      {plan ? (
        <form className="card tariff-buy__card" onSubmit={payFromBalance}>
          <div className="tariff-buy__summary">
            <div>
              <p className="muted">Тариф</p>
              <h2>{plan.name}</h2>
            </div>
            <p className="tariff-current__price">
              {plan.price ? formatMoney(plan.price) : 'Бесплатно'}
              <span>{plan.period === 'year' ? 'за год' : 'за месяц'}</span>
            </p>
          </div>
          {plan.period === 'year' && plan.price ? (
            <p className="muted">Это {formatMoney(shown)} в месяц при оплате за год.</p>
          ) : null}
          <p className="muted">
            На балансе {formatMoney(user.balance ?? 0)}.
            {plan.price > (user.balance ?? 0)
              ? ` Не хватает ${formatMoney(plan.price - (user.balance ?? 0))} — с баланса этот тариф не спишется.`
              : ''}
            {' '}
            <Link to="/cabinet/balance">Пополнить</Link>
          </p>

          <label className="field">
            <span>Email</span>
            <input value={user.email} disabled />
          </label>
          <label className="field">
            <span>Телефон</span>
            <input value={phone} onChange={(event) => setPhone(event.target.value)} required minLength={5} placeholder="+7" />
          </label>
          <label className="field">
            <span>Комментарий</span>
            <textarea value={comment} onChange={(event) => setComment(event.target.value)} maxLength={500} rows={3} />
          </label>
          <label className="check">
            <input type="checkbox" checked={confirm} onChange={(event) => setConfirm(event.target.checked)} required />
            <span>Подтвердить и оплатить</span>
          </label>
          <div className="card__actions">
            <button className="btn btn--primary" type="submit" disabled={Boolean(busy) || !confirm || (plan.price > 0 && (user.balance ?? 0) < plan.price)}>
              {busy === 'balance' ? 'Подключаем…' : plan.price ? 'С баланса' : 'Подключить'}
            </button>
            {plan.price > 0 ? (
              <button className="btn" type="button" onClick={payWooppay} disabled={Boolean(busy) || !confirm}>
                {busy === 'wooppay' ? 'Открываем оплату…' : 'Через Wooppay'}
              </button>
            ) : null}
          </div>
        </form>
      ) : null}
    </div>
  )
}
