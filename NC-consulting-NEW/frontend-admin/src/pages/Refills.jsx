import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, formatDate, formatMoney } from '../api.js'

export default function Refills() {
  const [rows, setRows] = useState(null)
  const [error, setError] = useState('')
  const [busyId, setBusyId] = useState(null)

  useEffect(() => {
    api.refills().then(setRows).catch((err) => setError(err.message))
  }, [])

  async function pay(id) {
    setError('')
    setBusyId(id)
    try {
      const saved = await api.payRefill(id)
      setRows((current) => current.map((row) => (row.id === saved.id ? saved : row)))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  return (
    <div>
      <h1>Запросы на пополнение</h1>
      <p className="muted">Заявки Kaspi. Зачисление один раз добавляет сумму на баланс.</p>
      {error && <div className="alert">{error}</div>}
      {rows && (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Дата</th>
                <th>Клиент</th>
                <th>Телефон</th>
                <th>Сумма</th>
                <th>Комментарий</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td>{formatDate(row.created_at)}</td>
                  <td>
                    <Link to={`/users/${row.user_id}`}>{row.full_name}</Link>
                    <div className="muted">{row.user_email}</div>
                  </td>
                  <td>{row.phone}</td>
                  <td>{formatMoney(row.amount)}</td>
                  <td>{row.comment || '—'}</td>
                  <td>
                    {row.is_paid ? (
                      <span className="pill">Зачислено</span>
                    ) : (
                      <button type="button" className="btn" disabled={busyId === row.id} onClick={() => pay(row.id)}>
                        Зачислить
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={6} className="muted">Заявок нет</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
