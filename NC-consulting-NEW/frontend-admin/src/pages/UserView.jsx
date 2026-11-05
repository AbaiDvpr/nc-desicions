import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api, formatDate, formatMoney } from '../api.js'

export default function UserView() {
  const { id } = useParams()
  const [user, setUser] = useState(null)
  const [amount, setAmount] = useState('')
  const [comment, setComment] = useState('')
  const [direction, setDirection] = useState('credit')
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api.user(id).then(setUser).catch((err) => setError(err.message))
  }, [id])

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
          </div>

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
