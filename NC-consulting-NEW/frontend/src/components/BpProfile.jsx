import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api.js'
import { COMPANY_GROUPS, COMPANY_LISTS, PROJECT_GROUPS, PROJECT_LISTS, profileItems } from '../bpProfileFields.js'
import { FieldGrid, ListEditor } from './BpProfileFields.jsx'

export default function BpProfile({ modelId }) {
  const [state, setState] = useState(null)
  const [project, setProject] = useState(null)
  const [saved, setSaved] = useState(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [note, setNote] = useState('')

  const apply = (payload) => {
    setState(payload)
    setProject(payload.project)
    setSaved(JSON.stringify(payload.project))
  }

  useEffect(() => {
    let stop = false
    api
      .bpProfile(modelId)
      .then((payload) => {
        if (!stop) apply(payload)
      })
      .catch((err) => {
        if (!stop) setError(err.message)
      })
    return () => {
      stop = true
    }
  }, [modelId])

  const dirty = project !== null && JSON.stringify(project) !== saved
  const set = (key, value) => {
    setNote('')
    setProject((current) => ({ ...current, [key]: value }))
  }

  const save = async (companyId, nextProject, message) => {
    setBusy(true)
    setError('')
    try {
      apply(await api.saveBpProfile(modelId, { company_id: companyId, project: nextProject }))
      setNote(message)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  const companyItems = state ? (state.company_id ? profileItems(COMPANY_GROUPS, COMPANY_LISTS, state.company) : []) : []
  const projectItems = project ? profileItems(PROJECT_GROUPS, PROJECT_LISTS, project) : []
  const total = profileItems(COMPANY_GROUPS, COMPANY_LISTS, {}).length + projectItems.length
  const done = [...companyItems, ...projectItems].filter((item) => item.done).length
  const company = state?.companies.find((row) => row.id === state.company_id)
  const missing = companyItems.filter((item) => !item.done).map((item) => item.label.toLowerCase())

  return (
    <details className="bp-profile">
      <summary>
        <span className="bp-profile__title">Данные компании для банковского плана</span>
        <span className="muted">
          {state === null ? '…' : `${company ? `${company.name} · ` : ''}заполнено ${done} из ${total}`}
        </span>
      </summary>
      <p className="bp-profile__hint">Регистрация, команда, обороты и конкуренты берутся из карточки выбранной компании.</p>
      {state === null && !error && <div className="spinner spinner--inline" />}
      {state !== null && (
        <>
          <div className="bp-profile__company">
            {state.companies.length > 0 ? (
              <>
                <label className="bp-profile__field">
                  <span>Компания</span>
                  <select
                    value={state.company_id ?? 0}
                    disabled={busy}
                    onChange={(event) => save(Number(event.target.value), project, 'Компания выбрана.')}
                  >
                    <option value={0}>Не выбрана</option>
                    {state.companies.map((row) => (
                      <option key={row.id} value={row.id}>
                        {row.name}
                      </option>
                    ))}
                  </select>
                </label>
                {company && (
                  <Link className="btn btn--small" to={`/cabinet/companies/${company.id}/profile`}>
                    {missing.length ? 'Дополнить' : 'Изменить'} данные компании
                  </Link>
                )}
              </>
            ) : (
              <>
                <span className="muted">Компаний пока нет. Создайте её один раз, и данные будут подставляться во все планы.</span>
                <Link className="btn btn--small" to="/cabinet/companies/new">
                  Создать компанию
                </Link>
              </>
            )}
          </div>
          {company && (
            <p className={`bp-profile__status${missing.length ? '' : ' is-full'}`}>
              {missing.length
                ? `Не заполнено в компании: ${missing.slice(0, 4).join(', ')}${missing.length > 4 ? ` и ещё ${missing.length - 4}` : ''}`
                : 'Данные компании заполнены полностью'}
            </p>
          )}
          <p className="bp-profile__subtitle">Для этого проекта</p>
          <FieldGrid groups={PROJECT_GROUPS} data={project} onChange={set} />
          {PROJECT_LISTS.map((list) => (
            <ListEditor key={list.key} list={list} rows={project[list.key] || []} onChange={(rows) => set(list.key, rows)} />
          ))}
          <div className="bp-profile__actions">
            <button
              type="button"
              className="btn btn--primary btn--small"
              disabled={busy || !dirty}
              onClick={() => save(state.company_id, project, 'Сохранено. Попадёт в Word и PDF при следующем скачивании.')}
            >
              {busy ? 'Сохраняем…' : 'Сохранить'}
            </button>
            {note && !dirty && <span className="muted">{note}</span>}
            {dirty && <span className="muted">Есть несохранённые изменения</span>}
          </div>
        </>
      )}
      {error && <div className="alert alert--error">{error}</div>}
    </details>
  )
}
