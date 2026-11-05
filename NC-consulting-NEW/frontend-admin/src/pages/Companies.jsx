import { useEffect, useState } from 'react'
import { api, formatDate } from '../api.js'

const FORMS = { 1: 'ИП', 2: 'ТОО' }

export default function Companies() {
  const [rows, setRows] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api.companies().then(setRows).catch((err) => setError(err.message))
  }, [])

  return (
    <div>
      <h1>Управленческий учёт</h1>
      {error && <div className="alert">{error}</div>}
      {rows && (
        <div className="table-wrap">
          <table>
            <thead>
              <tr><th>Компания</th><th>Владелец</th><th>Email</th><th>Создана</th></tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td>{FORMS[row.legal_form] || ''} {row.name}</td>
                  <td>{row.user_name}</td>
                  <td>{row.user_email}</td>
                  <td>{formatDate(row.created_at)}</td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={4} className="muted">Компаний нет</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
