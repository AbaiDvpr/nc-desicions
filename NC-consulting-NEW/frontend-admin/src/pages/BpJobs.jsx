import { useEffect, useState } from 'react'
import { api, formatDate } from '../api.js'

const STATUS = {
  queued: 'В очереди',
  running: 'В работе',
  done: 'Готово',
  error: 'Ошибка',
}

export default function BpJobs() {
  const [rows, setRows] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api.bpJobs().then(setRows).catch((err) => setError(err.message))
  }, [])

  return (
    <div>
      <h1>Сборки бизнес-плана</h1>
      <p className="muted">Промпт, ответ и модель видны только здесь. Клиент видит статусы разделов.</p>
      {error && <div className="alert">{error}</div>}
      {rows && rows.length === 0 && <p className="muted">Заявок ещё нет.</p>}
      {rows &&
        rows.map((job) => (
          <article key={job.id} className="card bp-job">
            <header className="bp-job__head">
              <div>
                <strong>{job.project || 'Проект'}</strong>
                <div className="muted">
                  {job.client} · {job.email}
                </div>
              </div>
              <div className="bp-job__meta">
                <span>{job.template_name || 'Шаблон'}</span>
                <span>{job.model_label || job.model_name}</span>
                <span>{STATUS[job.status] || job.status}</span>
                <span>{formatDate(job.created_at)}</span>
              </div>
            </header>
            {job.tasks.map((task) => (
              <details key={task.id || task.key} className="bp-job__task">
                <summary>
                  {task.title}
                  <em>{STATUS[task.status] || task.status}</em>
                </summary>
                {task.method && (
                  <p>
                    <b>Промпт.</b> {task.method}
                  </p>
                )}
                {task.answer && (
                  <p>
                    <b>Ответ.</b> {task.answer}
                  </p>
                )}
                {task.error && <p className="bp-job__error">{task.error}</p>}
              </details>
            ))}
          </article>
        ))}
    </div>
  )
}
