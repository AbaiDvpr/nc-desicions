import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, formatDate, formatMoney } from '../api.js'

export default function Users() {
  const [rows, setRows] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api.users().then(setRows).catch((err) => setError(err.message))
  }, [])

  return (
    <div>
      <h1>Пользователи</h1>
      {error && <div className="alert">{error}</div>}
      {rows && (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Имя</th>
                <th>Email</th>
                <th>Тариф</th>
                <th>Баланс</th>
                <th>Проекты</th>
                <th>Компании</th>
                <th>Регистрация</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td><Link to={`/users/${row.id}`}>{row.full_name}</Link></td>
                  <td>{row.email}</td>
                  <td>{row.tariff_name || '—'}</td>
                  <td>{formatMoney(row.balance)}</td>
                  <td>{row.models_count}</td>
                  <td>{row.companies_count}</td>
                  <td>{formatDate(row.created_at)}</td>
                </tr>
              ))}
              {rows.length === 0 && (
                <tr><td colSpan={7} className="muted">Пользователей нет</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
