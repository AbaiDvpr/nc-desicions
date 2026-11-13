import { useEffect, useState } from 'react'
import { api } from '../api.js'

const COLUMNS = [
  { id: 'todo', title: 'Сделать' },
  { id: 'doing', title: 'В работе' },
  { id: 'done', title: 'Готово' },
]

function byColumn(tasks, column) {
  return tasks.filter((task) => task.column === column).sort((a, b) => a.position - b.position || a.id - b.id)
}

export default function TaskBoard() {
  const [tasks, setTasks] = useState(null)
  const [error, setError] = useState('')
  const [drafts, setDrafts] = useState({ todo: '', doing: '', done: '' })
  const [adding, setAdding] = useState('')
  const [over, setOver] = useState('')

  useEffect(() => {
    api.tasks().then(setTasks).catch((err) => setError(err.message))
  }, [])

  async function add(column) {
    const title = drafts[column].trim()
    if (!title) return
    setError('')
    setAdding(column)
    try {
      const created = await api.createTask({ title, column })
      setTasks((current) => [...(current || []), created])
      setDrafts((current) => ({ ...current, [column]: '' }))
    } catch (err) {
      setError(err.message)
    } finally {
      setAdding('')
    }
  }

  async function save(id, patch) {
    setError('')
    try {
      const saved = await api.updateTask(id, patch)
      setTasks((current) => current.map((task) => (task.id === saved.id ? saved : task)))
    } catch (err) {
      setError(err.message)
      throw err
    }
  }

  async function move(id, column, beforeId) {
    const task = tasks.find((item) => item.id === id)
    if (!task || (task.column === column && beforeId === id)) return
    setError('')
    const previous = tasks
    setTasks((current) => {
      const next = current.map((item) => ({ ...item }))
      const moving = next.find((item) => item.id === id)
      if (!moving) return current
      const lane = next.filter((item) => item.column === column && item.id !== id)
      const index = beforeId ? lane.findIndex((item) => item.id === beforeId) : lane.length
      const at = index < 0 ? lane.length : index
      lane.splice(at, 0, moving)
      moving.column = column
      lane.forEach((item, position) => {
        item.position = position
      })
      const others = next.filter((item) => item.column !== column && item.id !== id)
      return [...others, ...lane]
    })
    try {
      setTasks(await api.moveTask(id, { column, before_id: beforeId || null }))
    } catch (err) {
      setTasks(previous)
      setError(err.message)
    }
  }

  async function remove(id) {
    setError('')
    const previous = tasks
    setTasks((current) => current.filter((task) => task.id !== id))
    try {
      await api.deleteTask(id)
    } catch (err) {
      setTasks(previous)
      setError(err.message)
    }
  }

  function dropOnColumn(event, column) {
    event.preventDefault()
    const id = Number(event.dataTransfer.getData('text/plain'))
    setOver('')
    if (id) move(id, column, null)
  }

  return (
    <section className="board">
      <div className="board__head">
        <h1>Задачи</h1>
      </div>
      {error && <div className="alert">{error}</div>}
      {tasks === null ? (
        <p className="muted">Загрузка…</p>
      ) : (
        <div className="board__columns">
          {COLUMNS.map((column) => (
            <div
              key={column.id}
              className={`board__column${over === column.id ? ' is-over' : ''}`}
              onDragOver={(event) => {
                event.preventDefault()
                setOver(column.id)
              }}
              onDragLeave={() => setOver((current) => (current === column.id ? '' : current))}
              onDrop={(event) => dropOnColumn(event, column.id)}
            >
              <div className="board__title">
                {column.title}
                <span>{byColumn(tasks, column.id).length}</span>
              </div>
              <form
                className="board__add"
                onSubmit={(event) => {
                  event.preventDefault()
                  add(column.id)
                }}
              >
                <input
                  value={drafts[column.id]}
                  placeholder="Новая задача"
                  maxLength={200}
                  aria-label={`Новая задача: ${column.title}`}
                  onChange={(event) => setDrafts((current) => ({ ...current, [column.id]: event.target.value }))}
                />
                <button type="submit" className="btn btn--sm" disabled={adding === column.id || !drafts[column.id].trim()}>
                  Добавить
                </button>
              </form>
              <div className="board__cards">
                {byColumn(tasks, column.id).map((task) => (
                  <Card
                    key={task.id}
                    task={task}
                    onSave={save}
                    onDelete={remove}
                    onDropBefore={(id) => move(id, column.id, task.id)}
                  />
                ))}
                {byColumn(tasks, column.id).length === 0 && <p className="board__empty">Пока пусто</p>}
              </div>
            </div>
          ))}
        </div>
      )}
    </section>
  )
}

function Card({ task, onSave, onDelete, onDropBefore }) {
  const [editing, setEditing] = useState(false)
  const [title, setTitle] = useState(task.title)
  const [note, setNote] = useState(task.note || '')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    setTitle(task.title)
    setNote(task.note || '')
  }, [task.title, task.note])

  function cancel() {
    setTitle(task.title)
    setNote(task.note || '')
    setEditing(false)
  }

  async function commit() {
    const nextTitle = title.trim()
    const nextNote = note.trim()
    if (!nextTitle) {
      cancel()
      return
    }
    if (nextTitle === task.title && nextNote === (task.note || '')) {
      setEditing(false)
      return
    }
    setBusy(true)
    try {
      await onSave(task.id, { title: nextTitle, note: nextNote })
      setEditing(false)
    } catch {
      /* текст ошибки показывает доска */
    } finally {
      setBusy(false)
    }
  }

  return (
    <article
      className={`board__card${editing ? ' is-editing' : ''}`}
      draggable={!editing}
      onDragStart={(event) => {
        event.dataTransfer.setData('text/plain', String(task.id))
        event.dataTransfer.effectAllowed = 'move'
      }}
      onDragOver={(event) => event.preventDefault()}
      onDrop={(event) => {
        event.preventDefault()
        event.stopPropagation()
        const id = Number(event.dataTransfer.getData('text/plain'))
        if (id && id !== task.id) onDropBefore(id)
      }}
    >
      {editing ? (
        <form
          className="board__edit"
          onSubmit={(event) => {
            event.preventDefault()
            commit()
          }}
        >
          <textarea
            value={title}
            maxLength={200}
            rows={3}
            autoFocus
            aria-label="Текст задачи"
            onChange={(event) => setTitle(event.target.value)}
            onKeyDown={(event) => {
              if (event.key === 'Escape') cancel()
            }}
          />
          <textarea
            value={note}
            maxLength={2000}
            rows={3}
            placeholder="Заметка"
            aria-label="Заметка"
            onChange={(event) => setNote(event.target.value)}
            onKeyDown={(event) => {
              if (event.key === 'Escape') cancel()
            }}
          />
          <div className="board__edit-actions">
            <button type="submit" className="btn btn--sm" disabled={busy || !title.trim()}>Сохранить</button>
            <button type="button" className="btn btn--ghost btn--sm" onClick={cancel}>Отмена</button>
          </div>
        </form>
      ) : (
        <>
          <p className="board__name">{task.title}</p>
          {task.note && <p className="board__note">{task.note}</p>}
        </>
      )}
      <div className="board__tools">
        <button
          type="button"
          className={`board__pencil${editing ? ' is-on' : ''}`}
          aria-label={editing ? 'Закрыть правку' : 'Редактировать задачу'}
          aria-pressed={editing}
          onMouseDown={(event) => event.stopPropagation()}
          onClick={() => (editing ? cancel() : setEditing(true))}
        >
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
          </svg>
        </button>
        {!editing && (
          <button
            type="button"
            className="board__delete"
            aria-label="Удалить задачу"
            onMouseDown={(event) => event.stopPropagation()}
            onClick={() => onDelete(task.id)}
          >
            ×
          </button>
        )}
      </div>
    </article>
  )
}
