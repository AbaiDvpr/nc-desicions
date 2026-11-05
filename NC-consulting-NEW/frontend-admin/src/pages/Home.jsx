import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api.js'

const CARDS = [
  { key: 'users', label: 'Пользователи', to: '/users' },
  { key: 'models', label: 'Проекты', to: '/models' },
  { key: 'companies', label: 'Компании', to: '/companies' },
  { key: 'open_refills', label: 'Открытые заявки', to: '/refills' },
]

export default function Home() {
  const [stats, setStats] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api.stats().then(setStats).catch((err) => setError(err.message))
  }, [])

  return (
    <div>
      <h1>Главная</h1>
      {error && <div className="alert">{error}</div>}
      {stats && (
        <div className="stats">
          {CARDS.map((card) => (
            <Link key={card.key} to={card.to} className="card stat">
              <div className="muted">{card.label}</div>
              <div className="stat__num">{stats[card.key]}</div>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
