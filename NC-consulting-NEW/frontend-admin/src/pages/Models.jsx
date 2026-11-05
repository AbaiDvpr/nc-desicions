import { useEffect, useState } from 'react'
import { api, formatDate } from '../api.js'

export default function Models() {
  const [rows, setRows] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api.models().then(setRows).catch((err) => setError(err.message))
  }, [])

  return (
    <div>
      <h1>Проекты</h1>
      <p className="muted">Финансовые модели пользователей</p>
      {error && <div className="alert">{error}</div>}
      {rows && (
        <div className="table-wrap">
          <table>
            <thead>
              <tr><th>Название</th><th>Пользователь</th><th>Email</th><th>Создана</th></tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td>{row.name}</td>
                  <td>{row.user_name}</td>
                  <td>{row.user_email}</td>
                  <td>{formatDate(row.created_at)}</td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={4} className="muted">Проектов нет</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
