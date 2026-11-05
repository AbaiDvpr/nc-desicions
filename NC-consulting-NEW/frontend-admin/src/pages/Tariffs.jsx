import { useEffect, useState } from 'react'
import { api, formatMoney } from '../api.js'

const FIELDS = [
  { key: 'price', label: 'Цена, ₸' },
  { key: 'models_limit', label: 'Модели' },
  { key: 'edits_limit', label: 'Правки' },
  { key: 'companies_limit', label: 'Компании' },
  { key: 'plans_limit', label: 'Планы' },
]

function same(row, origin) {
  return FIELDS.every((field) => String(row[field.key]) === String(origin[field.key]))
}

export default function Tariffs() {
  const [rows, setRows] = useState(null)
  const [base, setBase] = useState([])
  const [error, setError] = useState('')
  const [savedId, setSavedId] = useState(null)
  const [busyId, setBusyId] = useState(null)

  useEffect(() => {
    api.tariffs().then((data) => {
      setRows(data)
      setBase(data)
    }).catch((err) => setError(err.message))
  }, [])

  function patch(id, key, value) {
    setSavedId(null)
    setRows((current) => current.map((row) => (row.id === id ? { ...row, [key]: value } : row)))
  }

  async function save(row) {
    setError('')
    setBusyId(row.id)
    try {
      const saved = await api.updateTariff(row.id, {
        price: Number(row.price),
        models_limit: Number(row.models_limit),
        edits_limit: Number(row.edits_limit),
        companies_limit: Number(row.companies_limit),
        plans_limit: Number(row.plans_limit),
      })
      setRows((current) => current.map((item) => (item.id === saved.id ? { ...item, ...saved } : item)))
      setBase((current) => current.map((item) => (item.id === saved.id ? { ...item, ...saved } : item)))
      setSavedId(saved.id)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  const month = rows?.filter((row) => row.period !== 'year') ?? []
  const year = rows?.filter((row) => row.period === 'year') ?? []

  return (
    <div>
      <h1>Тарифы</h1>
      {error && <div className="alert">{error}</div>}
      {rows && (
        <>
          <TariffTable title="На месяц" group={month} />
          <TariffTable title="На год" group={year} yearly />
        </>
      )}
    </div>
  )

  function TariffTable({ title, group, yearly }) {
    return (
      <section className="plan-group">
        <h2>{title}</h2>
        <div className="table-wrap">
          <table className="plan-table">
            <thead>
              <tr>
                <th />
                {group.map((row) => (
                  <th key={row.id}>
                    <div className="plan-table__name">{row.name}</div>
                    <div className="plan-table__sum">{formatMoney(Number(row.price) || 0)}</div>
                    {yearly && Number(row.price) > 0 && (
                      <div className="muted">{formatMoney(Math.round(Number(row.price) / 12))} / мес</div>
                    )}
                    {row.is_default && <span className="pill">Бесплатный</span>}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {FIELDS.map((field) => (
                <tr key={field.key}>
                  <th>{field.label}</th>
                  {group.map((row) => (
                    <td key={row.id}>
                      <input
                        type="number"
                        min="0"
                        aria-label={`${row.name}: ${field.label}`}
                        value={row[field.key]}
                        disabled={field.key === 'price' && row.is_default}
                        onChange={(event) => patch(row.id, field.key, event.target.value)}
                      />
                    </td>
                  ))}
                </tr>
              ))}
              <tr>
                <th />
                {group.map((row) => {
                  const origin = base.find((item) => item.id === row.id) || row
                  const dirty = !same(row, origin)
                  return (
                    <td key={row.id}>
                      <button type="button" className="btn" disabled={!dirty || busyId === row.id} onClick={() => save(row)}>
                        {busyId === row.id ? '…' : savedId === row.id && !dirty ? 'Сохранено' : 'Сохранить'}
                      </button>
                    </td>
                  )
                })}
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    )
  }
}
