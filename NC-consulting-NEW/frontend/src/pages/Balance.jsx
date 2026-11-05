import { useEffect, useState } from 'react'
import { api } from '../api.js'
import { useAuth } from '../auth/AuthContext.jsx'
import Toast from '../components/Toast.jsx'
import { formatMoney } from '../utils.js'

const WAYS = [
  {
    id: 'wooppay',
    title: 'Wooppay',
    text: 'Карта или кошелёк. Деньги приходят сразу.',
  },
  {
    id: 'ioka',
    title: 'Ioka',
    text: 'Оплата картой на странице Ioka.',
  },
  {
    id: 'request',
    title: 'Заявка',
    text: 'Счёт на Kaspi. Администратор выставит его на телефон.',
  },
]

export default function Balance() {
  const { user, setUser } = useAuth()
  const [way, setWay] = useState('')
  const [amount, setAmount] = useState('')
  const [phone, setPhone] = useState(user?.phone || '')
  const [fullName, setFullName] = useState(user?.full_name || '')
  const [comment, setComment] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const [toast, setToast] = useState('')

  useEffect(() => {
    api.me().then(setUser).catch(() => {})
  }, [setUser])

  const back = () => {
    setWay('')
    setError('')
  }

  const payOnline = async (event) => {
    event.preventDefault()
    setError('')
    setBusy(true)
    const payload = { amount: Number(amount), phone, email: user.email }
    try {
      const { url } = way === 'ioka' ? await api.topupIoka(payload) : await api.topupWooppay(payload)
      window.location.assign(url)
    } catch (err) {
      setError(err.message)
      setBusy(false)
    }
  }

  const sendRequest = async (event) => {
    event.preventDefault()
    setError('')
    setBusy(true)
    try {
      const result = await api.requestRefill({
        full_name: fullName,
        phone,
        amount: Number(amount),
        comment,
      })
      setAmount('')
      setComment('')
      setWay('')
      setToast(result.success)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="page tariff-buy">
      <header className="page__header">
        <h1>Баланс</h1>
        <p className="muted">Сейчас на счёте {formatMoney(user.balance ?? 0)}</p>
      </header>

      <section className="card">
        <p className="balance-step">{way ? 'Шаг 2 из 2' : 'Шаг 1 из 2'}</p>
        {error ? <div className="alert alert--error">{error}</div> : null}

        {!way ? (
          <div className="balance-ways">
            {WAYS.map((item) => (
              <button key={item.id} type="button" className="balance-way" onClick={() => setWay(item.id)}>
                <strong>{item.title}</strong>
                <span>{item.text}</span>
              </button>
            ))}
          </div>
        ) : null}

        {way === 'wooppay' || way === 'ioka' ? (
          <form onSubmit={payOnline}>
            <h2>{way === 'ioka' ? 'Ioka' : 'Wooppay'}</h2>
            <label className="field">
              <span>Сумма, ₸</span>
              <input type="number" min="100" required value={amount} onChange={(event) => setAmount(event.target.value)} />
            </label>
            <label className="field">
              <span>Телефон</span>
              <input value={phone} onChange={(event) => setPhone(event.target.value)} required minLength={5} placeholder="+7" />
            </label>
            <div className="card__actions">
              <button className="btn" type="button" onClick={back} disabled={busy}>Назад</button>
              <button className="btn btn--primary" type="submit" disabled={busy}>
                {busy ? 'Открываем оплату…' : 'Оплатить'}
              </button>
            </div>
          </form>
        ) : null}

        {way === 'request' ? (
          <form onSubmit={sendRequest}>
            <h2>Заявка</h2>
            <label className="field">
              <span>Сумма, ₸</span>
              <input type="number" min="100" required value={amount} onChange={(event) => setAmount(event.target.value)} />
            </label>
            <label className="field">
              <span>ФИО</span>
              <input value={fullName} onChange={(event) => setFullName(event.target.value)} required maxLength={100} />
            </label>
            <label className="field">
              <span>Телефон Kaspi</span>
              <input value={phone} onChange={(event) => setPhone(event.target.value)} required minLength={5} placeholder="+7" />
            </label>
            <label className="field">
              <span>Комментарий</span>
              <textarea value={comment} onChange={(event) => setComment(event.target.value)} maxLength={200} rows={3} />
            </label>
            <div className="card__actions">
              <button className="btn" type="button" onClick={back} disabled={busy}>Назад</button>
              <button className="btn btn--primary" type="submit" disabled={busy}>
                {busy ? 'Отправляем…' : 'Отправить'}
              </button>
            </div>
          </form>
        ) : null}
      </section>
      <Toast message={toast} onClose={() => setToast('')} />
    </div>
  )
}
