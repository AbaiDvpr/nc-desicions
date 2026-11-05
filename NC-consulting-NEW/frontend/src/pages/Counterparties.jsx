import { useEffect, useMemo, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { api } from '../api.js'
import Toast from '../components/Toast.jsx'

function PlusIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
      <path d="M12 5v14M5 12h14" />
    </svg>
  )
}

export default function Counterparties() {
  const location = useLocation()
  const [items, setItems] = useState(null)
  const [error, setError] = useState('')
  const [query, setQuery] = useState('')
  const [notice, setNotice] = useState(location.state?.notice || '')
  const [pendingId, setPendingId] = useState(null)

  function load() {
    return api.listCounterparties().then(setItems).catch((err) => setError(err.message))
  }

  useEffect(() => {
    load()
  }, [])

  const visible = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!items) return []
    if (!needle) return items
    return items.filter((item) =>
      [item.name, item.email, item.bin, item.director, item.bank_name]
        .join(' ')
        .toLowerCase()
        .includes(needle),
    )
  }, [items, query])

  async function remove(id) {
    setError('')
    try {
      await api.deleteCounterparty(id)
      setPendingId(null)
      setNotice('Контрагент удалён')
      await load()
    } catch (err) {
      setError(err.message)
    }
  }

  return (
    <div className="page">
      <Toast message={notice} onClose={() => setNotice('')} />
      <header className="page__header page__header--row">
        <div>
          <h1>Контрагенты</h1>
          <p className="muted">Компании и физические лица, с которыми вы работаете</p>
        </div>
        <Link to="/cabinet/counterparties/new" className="btn btn--primary">
          <PlusIcon />
          Добавить
        </Link>
      </header>

      {error && <div className="alert alert--error">{error}</div>}

      <label className="field dir-search">
        Поиск
        <input
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Название, БИН, email или банк"
        />
      </label>

      {items === null && !error && <div className="spinner" />}

      {items?.length === 0 && (
        <div className="card empty">
          <div className="empty__title">Пока нет контрагентов</div>
          <p className="muted">Добавьте компанию или физическое лицо.</p>
          <Link to="/cabinet/counterparties/new" className="btn btn--primary empty__action">
            <PlusIcon />
            Добавить
          </Link>
        </div>
      )}

      {items?.length > 0 && visible.length === 0 && (
        <div className="card empty">
          <div className="empty__title">Ничего не найдено</div>
        </div>
      )}

      {visible.length > 0 && (
        <div className="dir-list">
          {visible.map((item) => (
            <div className="card dir-card dir-card--party" key={item.id}>
              <div>
                <div className="dir-card__name">{item.name}</div>
                <div className="muted">{item.director}</div>
              </div>
              <div>
                <div>{item.phone || '—'}</div>
                <div className="muted">{item.email}</div>
              </div>
              <div>
                <div>БИН {item.bin}</div>
                <div className="muted">{item.bank_name}</div>
              </div>
              <div className="dir-card__actions">
                <Link to={`/cabinet/counterparties/${item.id}`} className="btn btn--small">Изменить</Link>
                {pendingId === item.id ? (
                  <button type="button" className="btn btn--small btn--danger" onClick={() => remove(item.id)}>
                    Удалить
                  </button>
                ) : (
                  <button type="button" className="btn btn--small" onClick={() => setPendingId(item.id)}>
                    Удалить
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
