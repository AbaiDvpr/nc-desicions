import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, finModelFiles } from '../api.js'
import { ExcelIcon, WordIcon } from '../components/FileIcons.jsx'
import { formatDate } from '../utils.js'

function PlusIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
      <path d="M12 5v14M5 12h14" />
    </svg>
  )
}

function ModelCard({ model }) {
  const files = finModelFiles(model.id)
  return (
    <div className="card model-card">
      <div className="model-card__info">
        <div className="model-card__name">{model.name}</div>
        <div className="muted model-card__meta">
          {[model.industry, `${model.horizon_months} мес.`, formatDate(model.created_at)].filter(Boolean).join(' · ')}
        </div>
      </div>
      <div className="model-card__actions">
        <a href={files.word} download className="btn btn--small">
          <WordIcon />
          Word
        </a>
        <a href={files.excel} download className="btn btn--small btn--excel">
          <ExcelIcon />
          Excel
        </a>
      </div>
    </div>
  )
}

export default function FinModels() {
  const [models, setModels] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api
      .listFinModels()
      .then(setModels)
      .catch((err) => setError(err.message))
  }, [])

  return (
    <div className="page">
      <header className="page__header page__header--row">
        <div>
          <h1>Фин модели</h1>
          <p className="muted">Ваши финансовые модели</p>
        </div>
        <Link to="/cabinet/models/new" className="btn btn--primary">
          <PlusIcon />
          Создать фин модель
        </Link>
      </header>

      {error && <div className="alert alert--error">{error}</div>}

      {models === null && !error && <div className="spinner" />}

      {models?.length === 0 && (
        <div className="card empty">
          <div className="empty__title">Пока нет ни одной модели</div>
          <p className="muted">Создайте первую финансовую модель — это займёт несколько минут.</p>
          <Link to="/cabinet/models/new" className="btn btn--primary empty__action">
            <PlusIcon />
            Создать фин модель
          </Link>
        </div>
      )}

      {models?.length > 0 && (
        <div className="model-list">
          {models.map((model) => (
            <ModelCard key={model.id} model={model} />
          ))}
        </div>
      )}
    </div>
  )
}
