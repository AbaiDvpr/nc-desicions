import { useState } from 'react'

const LABELS = {
  queued: 'В очереди',
  running: 'В работе',
  done: 'Готово',
  error: 'Не вышло',
}

const REVIEW = {
  ok: 'Норм',
  fix: 'Нужно поправить',
  error: 'Проверка не вышла',
  running: 'Смотрит блок',
}

export default function BpTasks({ job, onReview, reviewBusy }) {
  const [openId, setOpenId] = useState(null)
  const [draft, setDraft] = useState('')
  if (!job) return null
  const tasks = job.tasks || []
  const current = tasks.find((task) => task.status === 'running')
  const ready = tasks.filter((task) => task.status === 'done').length

  const toggle = (task) => {
    if (openId === task.id) {
      setOpenId(null)
      return
    }
    setOpenId(task.id)
    setDraft(task.answer || '')
  }

  return (
    <div className="bp-tasks">
      <p className="bp-tasks__now">
        {current
          ? `Сейчас пишется «${current.title}». Собрано ${ready} из ${tasks.length} разделов.`
          : tasks.length > 0 && ready === tasks.length
            ? `Все ${tasks.length} разделов собраны.`
            : tasks.length > 0
              ? `Собрано ${ready} из ${tasks.length} разделов.`
              : 'Разделы ещё не собраны.'}
      </p>
      <ol className="bp-tasks__list">
        {tasks.map((task, index) => {
          const canEdit = task.source === 'ai' && task.status === 'done' && task.answer
          const looking = task.review_status === 'running'
          return (
            <li key={task.id || task.key} className={`bp-task bp-task--${task.status}`}>
              <div className="bp-task__row">
                <span className="bp-task__index">{index + 1}</span>
                <span className="bp-task__name">{task.title}</span>
                <em className={`bp-task__badge bp-task__badge--${task.status}`}>
                  {LABELS[task.status] || task.status}
                  {task.source === 'ai' && task.attempts > 1 ? ` · ${task.attempts} из 3` : ''}
                </em>
              </div>
              {task.status === 'error' && task.error && <p className="bp-task__error">{task.error}</p>}
              {canEdit && (
                <div className="bp-task__tools">
                  <button type="button" className="bp-task__edit-btn" onClick={() => toggle(task)}>
                    {openId === task.id ? 'Свернуть' : 'Править'}
                  </button>
                  {looking && <span className="bp-task__review is-run">{REVIEW.running}</span>}
                  {!looking && task.review_status && REVIEW[task.review_status] && (
                    <span className={`bp-task__review is-${task.review_status}`}>
                      {REVIEW[task.review_status]}
                      {task.review ? `. ${task.review}` : ''}
                    </span>
                  )}
                </div>
              )}
              {canEdit && openId === task.id && (
                <div className="bp-task__editor">
                  <textarea value={draft} onChange={(event) => setDraft(event.target.value)} rows={8} />
                  <button
                    type="button"
                    className="btn btn--primary btn--small"
                    disabled={reviewBusy || looking || draft.trim().length < 20}
                    onClick={() => onReview?.(task.id, draft)}
                  >
                    Отправить на проверку
                  </button>
                  <p>Модель смотрит только этот блок: нормально ли читается текст. Правка попадёт в документ.</p>
                </div>
              )}
            </li>
          )
        })}
      </ol>
    </div>
  )
}
