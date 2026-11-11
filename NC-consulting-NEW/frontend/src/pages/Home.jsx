import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api.js'
import { useAuth } from '../auth/AuthContext.jsx'
import { formatDate } from '../utils.js'

export default function Home() {
  const { user } = useAuth()
  const [modelCount, setModelCount] = useState(null)

  useEffect(() => {
    api
      .listFinModels()
      .then((page) => setModelCount(page.total))
      .catch(() => setModelCount(null))
  }, [])

  return (
    <div className="page">
      <header className="page__header">
        <h1>Здравствуйте, {user.full_name.split(' ')[0]}!</h1>
        <p className="muted">Это ваш личный кабинет клиента NC-Desicion</p>
      </header>

      <div className="grid">
        <div className="card">
          <div className="card__header">
            <h2>Профиль</h2>
          </div>
          <dl className="details">
            <dt>Компания</dt>
            <dd>{user.company || '—'}</dd>
            <dt>Email</dt>
            <dd>{user.email}</dd>
            <dt>Клиент с</dt>
            <dd>{formatDate(user.created_at)}</dd>
          </dl>
          <Link to="/cabinet/profile" className="btn card__link">
            Открыть профиль
          </Link>
        </div>

        <div className="card">
          <div className="card__header">
            <h2>Финансовые модели</h2>
          </div>
          <div className="stat">
            <div className="stat__value">{modelCount ?? '—'}</div>
            <div className="muted">моделей создано</div>
          </div>
          <Link to="/cabinet/models/new" className="btn btn--primary">
            Создать фин модель
          </Link>
        </div>
      </div>
    </div>
  )
}
