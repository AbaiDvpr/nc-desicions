import { useEffect, useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import { api } from '../api.js'
import Toast from '../components/Toast.jsx'
import { LEGAL_FORMS, labelOf } from '../directory.js'
import { formatMoney } from '../utils.js'

const SECTIONS = [
  { slug: 'opu', title: 'Отчет о прибыли и убытках', tone: 'primary' },
  { slug: 'dds', title: 'Движение денежных средств', tone: 'info' },
  { slug: 'balance', title: 'Статистика и баланс', tone: 'danger' },
  { slug: 'accounting', title: 'Ежедневный учёт', tone: 'success' },
  { slug: 'calendar', title: 'Календарь', tone: 'primary' },
]

export default function CompanyView() {
  const { id } = useParams()
  const location = useLocation()
  const [company, setCompany] = useState(null)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState(location.state?.notice || '')

  useEffect(() => {
    api.getCompany(id).then(setCompany).catch((err) => setError(err.message))
  }, [id])

  const title = company ? `${labelOf(LEGAL_FORMS, company.legal_form)} ${company.name}` : 'Компания'

  return (
    <div className="page">
      <Toast message={notice} onClose={() => setNotice('')} />
      <header className="page__header page__header--row">
        <div>
          <Link to="/cabinet/companies" className="muted">← Компании</Link>
          <h1>Управленческий учёт</h1>
          <p className="muted">{title}</p>
        </div>
        <Link to={`/cabinet/companies/${id}/profile`} className="btn">
          Настройки компании
        </Link>
      </header>

      {error && <div className="alert alert--error">{error}</div>}
      {company === null && !error && <div className="spinner" />}

      {company && (
        <>
          <div className="acct-nav">
            {SECTIONS.map((section) => (
              <Link
                key={section.slug}
                to={`/cabinet/companies/${id}/${section.slug}`}
                className={`acct-nav__item acct-nav__item--${section.tone}`}
              >
                {section.title}
              </Link>
            ))}
          </div>

          <div className="acct-metrics">
            <div className="card">
              <div className="muted">Чистая прибыль</div>
              <div className="acct-metric">{formatMoney(0)}</div>
              <div className="muted">за год</div>
            </div>
            <div className="card">
              <div className="muted">Денежные средства на конец периода</div>
              <div className="acct-metric">{formatMoney(0)}</div>
              <div className="muted">за год</div>
            </div>
          </div>
        </>
      )}
    </div>
  )
}
