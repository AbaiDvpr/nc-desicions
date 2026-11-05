import { Link, useParams } from 'react-router-dom'

const TITLES = {
  opu: 'Отчет о прибыли и убытках',
  dds: 'Движение денежных средств',
  balance: 'Статистика и баланс',
  accounting: 'Ежедневный учёт',
  calendar: 'Календарь',
}

export default function CompanySection() {
  const { id, section } = useParams()
  const title = TITLES[section] || 'Раздел'

  return (
    <div className="page">
      <header className="page__header">
        <Link to={`/cabinet/companies/${id}`} className="muted">← Управленческий учёт</Link>
        <h1>{title}</h1>
      </header>
      <div className="card empty">
        <div className="empty__title">Пока нет операций</div>
        <p className="muted">Когда в компании появятся проводки, здесь будет отчёт.</p>
      </div>
    </div>
  )
}
