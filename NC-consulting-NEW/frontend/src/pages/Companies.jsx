import { useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { api } from '../api.js'
import Toast from '../components/Toast.jsx'
import { CURRENCIES, LEGAL_FORMS, TAX_REGIMES, labelOf } from '../directory.js'

function PlusIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
      <path d="M12 5v14M5 12h14" />
    </svg>
  )
}

export default function Companies() {
  const location = useLocation()
  const [companies, setCompanies] = useState(null)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState(location.state?.notice || '')

  useEffect(() => {
    api
      .listCompanies()
      .then(setCompanies)
      .catch((err) => setError(err.message))
  }, [])

  return (
    <div className="page">
      <Toast message={notice} onClose={() => setNotice('')} />
      <header className="page__header page__header--row">
        <div>
          <h1>Компании</h1>
          <p className="muted">Компании для управленческого учёта</p>
        </div>
        <Link to="/cabinet/companies/new" className="btn btn--primary">
          <PlusIcon />
          Создать компанию
        </Link>
      </header>

      {error && <div className="alert alert--error">{error}</div>}
      {companies === null && !error && <div className="spinner" />}

      {companies?.length === 0 && (
        <div className="card empty">
          <div className="empty__title">Пока нет компаний</div>
          <p className="muted">Создайте компанию, чтобы вести по ней учёт.</p>
          <Link to="/cabinet/companies/new" className="btn btn--primary empty__action">
            <PlusIcon />
            Создать компанию
          </Link>
        </div>
      )}

      {companies?.length > 0 && (
        <div className="dir-list">
          {companies.map((company) => (
            <div className="card dir-card dir-card--company" key={company.id}>
              <div>
                <div className="dir-card__name">
                  {labelOf(LEGAL_FORMS, company.legal_form)} {company.name}
                </div>
              </div>
              <div className="muted">{labelOf(TAX_REGIMES, company.tax_regime)}</div>
              <div className="muted">НДС: {company.nds ? 'Да' : 'Нет'}</div>
              <div className="muted">{labelOf(CURRENCIES, company.currency)}</div>
              <div className="dir-card__actions">
                <Link to={`/cabinet/companies/${company.id}`} className="btn btn--small btn--primary">
                  Управленческий учёт
                </Link>
                <Link to={`/cabinet/companies/${company.id}/profile`} className="btn btn--small">
                  Настройки компании
                </Link>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
