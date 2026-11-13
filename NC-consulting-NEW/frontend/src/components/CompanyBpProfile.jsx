import { useEffect, useState } from 'react'
import { api } from '../api.js'
import { COMPANY_TABS, COMPANY_TEXT_KEYS, profileItems } from '../bpProfileFields.js'
import { FieldGrid, ListEditor } from './BpProfileFields.jsx'

function withDefaults(data, defaults) {
  const result = { ...data }
  for (const key of COMPANY_TEXT_KEYS) {
    if (!result[key] && defaults[key]) result[key] = defaults[key]
  }
  return result
}

export default function CompanyBpProfile({ companyId }) {
  const [data, setData] = useState(null)
  const [saved, setSaved] = useState(null)
  const [tab, setTab] = useState(COMPANY_TABS[0].key)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [note, setNote] = useState('')

  const apply = (payload) => {
    const next = withDefaults(payload.data, payload.defaults)
    setData(next)
    setSaved(JSON.stringify(next))
  }

  useEffect(() => {
    let stop = false
    api
      .companyBpProfile(companyId)
      .then((payload) => {
        if (!stop) apply(payload)
      })
      .catch((err) => {
        if (!stop) setError(err.message)
      })
    return () => {
      stop = true
    }
  }, [companyId])

  const dirty = data !== null && JSON.stringify(data) !== saved
  const set = (key, value) => {
    setNote('')
    setData((current) => ({ ...current, [key]: value }))
  }
  const count = (groups, lists) => {
    const items = profileItems(groups, lists, data)
    return { done: items.filter((item) => item.done).length, total: items.length }
  }
  const all = data ? count(COMPANY_TABS.flatMap((item) => item.groups), COMPANY_TABS.flatMap((item) => item.lists)) : null
  const current = COMPANY_TABS.find((item) => item.key === tab)

  const save = async () => {
    setBusy(true)
    setError('')
    try {
      apply(await api.saveCompanyBpProfile(companyId, data))
      setNote('Сохранено')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <section className="card company-bp">
      <div className="card__header company-bp__head">
        <div>
          <h2>Для банковского бизнес-плана</h2>
          <p className="muted">Заполняется один раз и подставляется в бизнес-план каждого проекта этой компании.</p>
        </div>
        {data && (
          <div className="company-bp__save">
            <span className="muted">{dirty ? 'Есть изменения' : note || `Заполнено ${all.done} из ${all.total}`}</span>
            <button type="button" className="btn btn--primary" disabled={busy || !dirty} onClick={save}>
              {busy ? 'Сохраняем…' : 'Сохранить'}
            </button>
          </div>
        )}
      </div>
      {error && <div className="alert alert--error">{error}</div>}
      {data === null && !error && <div className="spinner spinner--inline" />}
      {data !== null && (
        <>
          <div className="req-tabs">
            {COMPANY_TABS.map((item) => {
              const { done, total } = count(item.groups, item.lists)
              return (
                <button
                  key={item.key}
                  type="button"
                  className={`req-tabs__item ${item.key === tab ? 'is-active' : ''}`}
                  onClick={() => setTab(item.key)}
                >
                  {item.title}{' '}
                  <span className={`company-bp__count${done === total ? ' is-full' : ''}`}>
                    {done}/{total}
                  </span>
                </button>
              )
            })}
          </div>
          <div className="bp-profile bp-profile--flat">
            <FieldGrid groups={current.groups} data={data} onChange={set} />
            {current.lists.map((list) => (
              <ListEditor key={list.key} list={list} rows={data[list.key] || []} onChange={(rows) => set(list.key, rows)} />
            ))}
          </div>
        </>
      )}
    </section>
  )
}
