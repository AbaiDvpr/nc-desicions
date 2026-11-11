import { useEffect, useState } from 'react'
import { api } from '../api.js'
import BpDocument from '../components/BpDocument.jsx'

const COVERS = [
  { id: 'classic', label: 'Деловая обложка' },
  { id: 'investor', label: 'Обложка для инвестора' },
  { id: 'brief', label: 'Короткая обложка' },
]

export default function BpTemplates() {
  const [demo, setDemo] = useState(null)
  const [providers, setProviders] = useState([])
  const [provider, setProvider] = useState('deepseek')
  const [model, setModel] = useState('deepseek-ai/deepseek-v4.1-flash-1017')
  const [rows, setRows] = useState([])
  const [draft, setDraft] = useState(null)
  const [error, setError] = useState('')
  const [ok, setOk] = useState('')
  const [busy, setBusy] = useState(false)
  const [requestText, setRequestText] = useState('')

  useEffect(() => {
    api
      .bp()
      .then((payload) => {
        setDemo(payload.demo)
        setProviders(payload.providers || [])
        setProvider(payload.provider || 'deepseek')
        setModel(payload.model)
        setRows(payload.templates)
        setDraft(payload.templates[0] ? structuredClone(payload.templates[0]) : null)
      })
      .catch((err) => setError(err.message))
  }, [])

  function select(id) {
    const row = rows.find((item) => item.id === id)
    setDraft(row ? structuredClone(row) : null)
    setRequestText('')
    setOk('')
  }

  function patch(partial) {
    setDraft((current) => ({ ...current, ...partial }))
    setOk('')
  }

  function patchSection(index, partial) {
    setDraft((current) => ({
      ...current,
      sections: current.sections.map((section, i) => (i === index ? { ...section, ...partial } : section)),
    }))
    setOk('')
  }

  function move(index, delta) {
    setDraft((current) => {
      const next = current.sections.slice()
      const target = index + delta
      if (target < 0 || target >= next.length) return current
      const [item] = next.splice(index, 1)
      next.splice(target, 0, item)
      return { ...current, sections: next }
    })
    setOk('')
  }

  function addSection() {
    setDraft((current) => ({
      ...current,
      sections: [
        ...current.sections,
        {
          key: `s${Date.now()}`,
          title: 'Новый раздел',
          source: 'ai',
          prompt: 'Напиши этот раздел по фактам модели. 2–3 абзаца.',
          sample: 'Здесь будет текст, который напишет модель.',
        },
      ],
    }))
  }

  function chooseProvider(next) {
    const group = providers.find((item) => item.id === next)
    const ids = (group?.models || []).map((item) => item.id)
    setProvider(next)
    setModel((current) => (ids.includes(current) ? current : ids[0] || ''))
    setOk('')
  }

  async function saveModel() {
    setBusy(true)
    setError('')
    setOk('')
    try {
      const saved = await api.updateBpSettings({ provider, model })
      setProvider(saved.provider)
      setModel(saved.model)
      setOk(`Для всех клиентов запросы идут в ${saved.label}`)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function saveTemplate() {
    setBusy(true)
    setError('')
    setOk('')
    try {
      const saved = await api.updateBpTemplate(draft.id, {
        name: draft.name,
        description: draft.description,
        cover: draft.cover,
        accent: draft.accent,
        sections: draft.sections,
      })
      setRows((current) => current.map((item) => (item.id === saved.id ? saved : item)))
      setDraft(structuredClone(saved))
      setOk('Шаблон сохранён. Клиент увидит его сразу.')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function showRequest() {
    setBusy(true)
    setError('')
    try {
      const body = await api.bpRequest({
        name: draft.name,
        description: draft.description,
        cover: draft.cover,
        accent: draft.accent,
        sections: draft.sections,
      })
      setRequestText(JSON.stringify(body, null, 2))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div>
      <h1>Бизнес-план</h1>
      <p className="muted bp-note">
        Клиент выбирает только шаблон. Языковая модель и её вариант задаются здесь и действуют для всех клиентов.
        У клиента этого выбора нет. Ключи остаются на сервере.
      </p>
      {error && <div className="alert">{error}</div>}
      {ok && <div className="ok">{ok}</div>}
      <section className="card bp-model">
        <label>
          Языковая модель
          <select value={provider} onChange={(event) => chooseProvider(event.target.value)}>
            {providers.map((item) => (
              <option key={item.id} value={item.id}>
                {item.label}
              </option>
            ))}
          </select>
        </label>
        <label>
          Вариант
          <select value={model} onChange={(event) => { setModel(event.target.value); setOk('') }}>
            {(providers.find((item) => item.id === provider)?.models || []).map((item) => (
              <option key={item.id} value={item.id}>
                {item.label}
              </option>
            ))}
          </select>
        </label>
        <button type="button" className="btn" disabled={busy || !model} onClick={saveModel}>
          Сохранить модель
        </button>
      </section>
      {draft && demo && (
        <div className="bp-work">
          <div className="bp-editor">
            <div className="bp-switch">
              {rows.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  className={item.id === draft.id ? 'is-on' : ''}
                  onClick={() => select(item.id)}
                >
                  {item.name}
                </button>
              ))}
            </div>
            <label>
              Название
              <input value={draft.name} onChange={(event) => patch({ name: event.target.value })} />
            </label>
            <label>
              Как выглядит
              <textarea
                rows={2}
                value={draft.description}
                onChange={(event) => patch({ description: event.target.value })}
              />
            </label>
            <div className="bp-editor__row">
              <label>
                Обложка
                <select value={draft.cover} onChange={(event) => patch({ cover: event.target.value })}>
                  {COVERS.map((item) => (
                    <option key={item.id} value={item.id}>
                      {item.label}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                Цвет
                <input value={draft.accent} onChange={(event) => patch({ accent: event.target.value })} />
              </label>
            </div>
            {draft.sections.map((section, index) => (
              <fieldset key={section.key} className="bp-section">
                <legend>
                  {index + 1}. {section.source === 'ai' ? 'Пишет модель' : 'Цифры из финмодели'}
                </legend>
                <label>
                  Заголовок
                  <input value={section.title} onChange={(event) => patchSection(index, { title: event.target.value })} />
                </label>
                <label>
                  Откуда текст
                  <select
                    value={section.source}
                    onChange={(event) => patchSection(index, { source: event.target.value })}
                  >
                    <option value="ai">Запрос к модели</option>
                    <option value="facts">Факты финмодели</option>
                  </select>
                </label>
                {section.source === 'ai' && (
                  <>
                    <label>
                      Что спросить
                      <textarea
                        rows={3}
                        value={section.prompt}
                        onChange={(event) => patchSection(index, { prompt: event.target.value })}
                      />
                    </label>
                    <label>
                      Как выглядит абзац, пока модель не ответила
                      <textarea
                        rows={3}
                        value={section.sample}
                        onChange={(event) => patchSection(index, { sample: event.target.value })}
                      />
                    </label>
                  </>
                )}
                <div className="bp-section__actions">
                  <button type="button" onClick={() => move(index, -1)} disabled={index === 0}>
                    Выше
                  </button>
                  <button type="button" onClick={() => move(index, 1)} disabled={index === draft.sections.length - 1}>
                    Ниже
                  </button>
                  <button
                    type="button"
                    onClick={() =>
                      patch({ sections: draft.sections.filter((item) => item.key !== section.key) })
                    }
                    disabled={draft.sections.length === 1}
                  >
                    Убрать
                  </button>
                </div>
              </fieldset>
            ))}
            <button type="button" className="bp-add" onClick={addSection} disabled={draft.sections.length >= 16}>
              Добавить раздел
            </button>
            <div className="bp-editor__save">
              <button type="button" className="btn" disabled={busy} onClick={saveTemplate}>
                Сохранить шаблон
              </button>
              <button type="button" className="bp-ghost" disabled={busy} onClick={showRequest}>
                Как уйдёт запрос
              </button>
            </div>
            {requestText && <pre className="bp-request">{requestText}</pre>}
          </div>
          <div className="bp-stage">
            <BpDocument template={draft} facts={demo} />
          </div>
        </div>
      )}
    </div>
  )
}
