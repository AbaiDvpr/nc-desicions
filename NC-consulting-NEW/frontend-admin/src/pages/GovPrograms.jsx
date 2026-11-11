import { useEffect, useState } from 'react'
import { api } from '../api.js'

const KIND_OPTIONS = [
  {
    value: 'subsidy',
    label: 'Субсидия ставки',
    hint: 'Банк начисляет свою ставку, Damu снижает платёж клиента',
    groupTitle: 'Субсидии ставки',
    groupHint: 'Клиент платит сниженную ставку. Субсидия Damu = банк − клиент.',
  },
  {
    value: 'loan',
    label: 'Льготный кредит',
    hint: 'Кредит по программе с готовой ставкой',
    groupTitle: 'Льготные кредиты',
    groupHint: 'Готовая ставка программы, без отдельной субсидии Damu.',
  },
  {
    value: 'leasing',
    label: 'Лизинг',
    hint: 'Лизинговая программа Damu',
    groupTitle: 'Лизинг',
    groupHint: 'Лизинговые программы Damu.',
  },
  {
    value: 'guarantee',
    label: 'Гарантия',
    hint: 'Гарантия по кредиту, без ставки субсидии',
    groupTitle: 'Гарантии',
    groupHint: 'Гарантийные программы. Ставка кредита задаётся отдельно.',
  },
]

function kindMeta(kind) {
  return KIND_OPTIONS.find((item) => item.value === kind) || {
    value: kind || 'other',
    label: 'Прочее',
    groupTitle: 'Прочее',
    groupHint: 'Программы без стандартного типа.',
  }
}

function purposeTags(row) {
  const tags = []
  if (row.allows_investment) tags.push('Инвестиции')
  if (row.allows_wc) tags.push('Оборотные')
  return tags
}

function termLabel(row) {
  if (!row.term_max_months) return 'без лимита'
  if (row.term_kind === 'subsidy') return `субсидия до ${row.term_max_months} мес.`
  if (row.term_kind === 'loan') return `кредит до ${row.term_max_months} мес.`
  return `до ${row.term_max_months} мес.`
}

function groupPrograms(rows) {
  const buckets = new Map()
  for (const option of KIND_OPTIONS) buckets.set(option.value, [])
  buckets.set('other', [])
  for (const row of rows) {
    const key = buckets.has(row.kind) ? row.kind : 'other'
    buckets.get(key).push(row)
  }
  return [...buckets.entries()]
    .filter(([, items]) => items.length)
    .map(([kind, items]) => ({
      kind,
      meta: kindMeta(kind),
      items: [...items].sort((a, b) => (a.sort_order - b.sort_order) || a.title.localeCompare(b.title, 'ru')),
    }))
}

const TERM_KIND_OPTIONS = [
  { value: 'subsidy', label: 'Срок субсидии', hint: 'Сколько месяцев действует сниженная ставка для клиента' },
  { value: 'loan', label: 'Срок кредита', hint: 'Ограничение на полный срок займа' },
  { value: '', label: 'Без ограничения', hint: 'Срок задаёт клиент в анкете сам' },
]

const empty = () => ({
  slug: '',
  title: '',
  kind: 'subsidy',
  kind_label: 'Субсидия',
  url: '',
  client_rate: 12.6,
  amount: '',
  detail: '',
  purpose: '',
  term_min_months: '',
  term_max_months: 36,
  term_kind: 'subsidy',
  logic: '',
  allows_investment: true,
  allows_wc: true,
  grace_max_months: '',
  is_active: true,
  sort_order: 0,
})

function Field({ label, hint, wide, children }) {
  return (
    <label className={`drawer__field${wide ? ' drawer__field--wide' : ''}`}>
      <span className="drawer__label">{label}</span>
      {children}
      {hint ? <span className="drawer__hint">{hint}</span> : null}
    </label>
  )
}

function Toggle({ checked, onChange, label, hint }) {
  return (
    <button
      type="button"
      className={`toggle ${checked ? 'is-on' : ''}`}
      role="switch"
      aria-checked={checked}
      onClick={() => onChange(!checked)}
    >
      <span className="toggle__track" aria-hidden="true">
        <span className="toggle__thumb" />
      </span>
      <span className="toggle__text">
        <strong>{label}</strong>
        {hint ? <span>{hint}</span> : null}
      </span>
    </button>
  )
}

export default function GovPrograms() {
  const [programs, setPrograms] = useState([])
  const [draft, setDraft] = useState(null)
  const [creating, setCreating] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [ok, setOk] = useState('')
  const [drawerError, setDrawerError] = useState('')
  const [defaultRate, setDefaultRate] = useState(12.6)

  const load = () =>
    api
      .govPrograms()
      .then((data) => {
        setPrograms(data.programs || [])
        if (data.default_subsidy_client_rate != null) setDefaultRate(data.default_subsidy_client_rate)
      })
      .catch((err) => setError(err.message))

  useEffect(() => {
    load()
  }, [])

  useEffect(() => {
    if (!draft) return undefined
    const onKey = (event) => {
      if (event.key === 'Escape' && !busy) {
        setDraft(null)
        setCreating(false)
        setDrawerError('')
      }
    }
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKey)
    return () => {
      document.body.style.overflow = ''
      window.removeEventListener('keydown', onKey)
    }
  }, [draft, busy])

  const closeDrawer = () => {
    setDraft(null)
    setCreating(false)
    setDrawerError('')
  }

  const edit = (row) => {
    setCreating(false)
    setDraft({
      ...row,
      client_rate: row.client_rate ?? row.rate ?? '',
      term_min_months: row.term_min_months ?? '',
      term_max_months: row.term_max_months ?? '',
      grace_max_months: row.grace_max_months ?? '',
    })
    setOk('')
    setError('')
    setDrawerError('')
  }

  const setField = (key, value) => setDraft((current) => ({ ...current, [key]: value }))

  async function save() {
    if (!draft) return
    setBusy(true)
    setDrawerError('')
    setOk('')
    const payload = {
      title: draft.title,
      kind: draft.kind,
      kind_label: draft.kind_label,
      url: draft.url,
      client_rate: draft.client_rate === '' || draft.client_rate == null ? null : Number(draft.client_rate),
      amount: draft.amount,
      detail: draft.detail,
      purpose: draft.purpose,
      term_min_months: draft.term_min_months === '' ? null : Number(draft.term_min_months),
      term_max_months: draft.term_max_months === '' ? null : Number(draft.term_max_months),
      term_kind: draft.term_kind,
      logic: draft.logic,
      allows_investment: Boolean(draft.allows_investment),
      allows_wc: Boolean(draft.allows_wc),
      grace_max_months: draft.grace_max_months === '' ? null : Number(draft.grace_max_months),
      is_active: Boolean(draft.is_active),
      sort_order: Number(draft.sort_order) || 0,
    }
    try {
      if (creating) {
        const saved = await api.createGovProgram({ ...payload, slug: draft.slug })
        setPrograms((rows) => [...rows, saved].sort((a, b) => a.sort_order - b.sort_order || a.title.localeCompare(b.title)))
        setOk(`Добавлена «${saved.title}». Ставка клиента ${saved.rate ?? '—'}%.`)
        closeDrawer()
      } else {
        const saved = await api.updateGovProgram(draft.id, payload)
        setPrograms((rows) => rows.map((row) => (row.id === saved.id ? saved : row)))
        setOk(`Сохранено «${saved.title}». Ставка клиента ${saved.rate ?? '—'}%.`)
        closeDrawer()
      }
    } catch (err) {
      setDrawerError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function refresh() {
    setBusy(true)
    setError('')
    setOk('')
    try {
      const data = await api.refreshGovPrograms()
      setPrograms(data.programs || [])
      setOk(
        `Обновлено с Damu: +${data.created || 0} новых, ${data.updated || 0} обновлено. Ручные ставки клиента не затираются.`,
      )
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div>
      <div className="page-head">
        <div>
          <h1>Госпрограммы Damu</h1>
          <p className="muted">
            Кабинет читает список из БД. Ставка для клиента (по умолчанию {defaultRate}%) подставляется при выборе
            программы. Субсидия Damu = ставка банка − ставка клиента.
          </p>
        </div>
        <div className="page-head__actions">
          <button type="button" className="btn btn--ghost" disabled={busy} onClick={refresh}>
            {busy ? 'Обновляем…' : 'Обновить с Damu'}
          </button>
          <button
            type="button"
            className="btn"
            disabled={busy}
            onClick={() => {
              setCreating(true)
              setDraft(empty())
              setOk('')
              setError('')
              setDrawerError('')
            }}
          >
            Добавить
          </button>
        </div>
      </div>

      {error && <div className="alert">{error}</div>}
      {ok && <div className="ok">{ok}</div>}

      {!programs.length ? (
        <section className="card">
          <p className="muted" style={{ margin: 0 }}>
            Пусто. Нажмите «Обновить с Damu» или добавьте вручную.
          </p>
        </section>
      ) : (
        <div className="gov-groups">
          {groupPrograms(programs).map((group) => (
            <section key={group.kind} className="gov-group">
              <header className="gov-group__head">
                <div>
                  <h2>{group.meta.groupTitle || group.meta.label}</h2>
                  <p className="muted">{group.meta.groupHint || group.meta.hint}</p>
                </div>
                <span className="gov-group__count">{group.items.length}</span>
              </header>
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Программа</th>
                      <th>На что</th>
                      <th>Ставка клиента</th>
                      <th>Срок</th>
                      <th>Статус</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    {group.items.map((row) => (
                      <tr
                        key={row.id}
                        className={draft && !creating && draft.id === row.id ? 'is-selected' : undefined}
                      >
                        <td>
                          <div className="cell-title">{row.title}</div>
                          <div className="cell-meta">
                            {row.amount ? <span>{row.amount}</span> : null}
                            {row.purpose ? <span>{row.purpose}</span> : null}
                            {row.source === 'damu' ? <span>с Damu</span> : <span>ручная</span>}
                          </div>
                        </td>
                        <td>
                          <div className="tag-row">
                            {purposeTags(row).length
                              ? purposeTags(row).map((tag) => (
                                  <span key={tag} className="tag">
                                    {tag}
                                  </span>
                                ))
                              : <span className="muted">не указано</span>}
                          </div>
                        </td>
                        <td>
                          {row.rate != null || row.client_rate != null ? (
                            <strong>{row.rate ?? row.client_rate}%</strong>
                          ) : (
                            <span className="muted">нет</span>
                          )}
                        </td>
                        <td>{termLabel(row)}</td>
                        <td>
                          <span className={`pill ${row.is_active ? '' : 'pill--off'}`}>
                            {row.is_active ? 'в кабинете' : 'скрыта'}
                          </span>
                        </td>
                        <td>
                          <button type="button" className="btn btn--ghost btn--sm" onClick={() => edit(row)}>
                            Изменить
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          ))}
        </div>
      )}

      {draft ? (
        <div className="drawer-root" role="presentation">
          <button type="button" className="drawer-backdrop" aria-label="Закрыть" disabled={busy} onClick={closeDrawer} />
          <aside className="drawer" role="dialog" aria-modal="true" aria-labelledby="gov-drawer-title">
            <header className="drawer__head">
              <div>
                <div className="drawer__eyebrow">{creating ? 'Новая программа' : 'Редактирование'}</div>
                <h2 id="gov-drawer-title">{creating ? 'Добавить программу' : draft.title || 'Без названия'}</h2>
              </div>
              <button type="button" className="drawer__close" disabled={busy} onClick={closeDrawer} aria-label="Закрыть">
                ×
              </button>
            </header>

            <div className="drawer__body">
              {drawerError ? <div className="alert">{drawerError}</div> : null}

              <p className="drawer__lead">
                Эти поля видит клиент в анкете финмодели. Ставка для клиента подставляется автоматически при выборе
                программы; субсидия Damu считается как ставка банка минус ставка клиента.
              </p>

              <div className="drawer__grid">
                {creating ? (
                  <Field
                    wide
                    label="Код программы"
                    hint="Только латиница, например isker_aymak. Нужен системе, клиент его не видит."
                  >
                    <input
                      value={draft.slug}
                      onChange={(e) => setField('slug', e.target.value)}
                      placeholder="isker_aymak"
                    />
                  </Field>
                ) : null}

                <Field wide label="Название программы" hint="Как показывается в списке госпрограмм в кабинете">
                  <input value={draft.title} onChange={(e) => setField('title', e.target.value)} />
                </Field>

                <Field
                  label="Ставка для клиента, %"
                  hint={`То, что платит проект в срок субсидии. Для Damu обычно ${defaultRate}%.`}
                >
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={draft.client_rate}
                    onChange={(e) => setField('client_rate', e.target.value)}
                  />
                </Field>

                <Field
                  label="Тип программы"
                  hint={KIND_OPTIONS.find((item) => item.value === draft.kind)?.hint}
                >
                  <select
                    value={draft.kind}
                    onChange={(e) => {
                      const kind = e.target.value
                      const option = KIND_OPTIONS.find((item) => item.value === kind)
                      setDraft((current) => ({
                        ...current,
                        kind,
                        kind_label: option?.label || current.kind_label,
                        term_kind: kind === 'subsidy' ? 'subsidy' : current.term_kind === 'subsidy' ? 'loan' : current.term_kind,
                      }))
                    }}
                  >
                    {KIND_OPTIONS.map((item) => (
                      <option key={item.value} value={item.value}>
                        {item.label}
                      </option>
                    ))}
                  </select>
                </Field>

                <Field
                  label="Что ограничивает срок"
                  hint={TERM_KIND_OPTIONS.find((item) => item.value === (draft.term_kind || ''))?.hint}
                >
                  <select value={draft.term_kind || ''} onChange={(e) => setField('term_kind', e.target.value)}>
                    {TERM_KIND_OPTIONS.map((item) => (
                      <option key={item.value || 'none'} value={item.value}>
                        {item.label}
                      </option>
                    ))}
                  </select>
                </Field>

                <Field label="Макс. срок, мес." hint="Для «Іскер аймақ» обычно 36 месяцев субсидии">
                  <input
                    type="number"
                    min="0"
                    value={draft.term_max_months}
                    onChange={(e) => setField('term_max_months', e.target.value)}
                  />
                </Field>

                <Field label="Мин. срок, мес." hint="Можно оставить пустым, если минимума нет">
                  <input
                    type="number"
                    min="0"
                    value={draft.term_min_months}
                    onChange={(e) => setField('term_min_months', e.target.value)}
                  />
                </Field>

                <Field label="Макс. каникулы, мес." hint="Льготный период без основного долга. Пусто — без лимита">
                  <input
                    type="number"
                    min="0"
                    value={draft.grace_max_months}
                    onChange={(e) => setField('grace_max_months', e.target.value)}
                  />
                </Field>

                <Field label="Порядок в списке" hint="Меньше число — выше в кабинете">
                  <input type="number" value={draft.sort_order} onChange={(e) => setField('sort_order', e.target.value)} />
                </Field>

                <Field wide label="Ссылка на программу" hint="Страница Damu или другая официальная страница">
                  <input value={draft.url} onChange={(e) => setField('url', e.target.value)} placeholder="https://damu.kz/..." />
                </Field>

                <Field label="Лимит суммы" hint="Как в каталоге, например «200 млн теңге»">
                  <input value={draft.amount} onChange={(e) => setField('amount', e.target.value)} />
                </Field>

                <Field label="Назначение" hint="На что можно тратить: инвестиции, ПОС и т.д.">
                  <input value={draft.purpose} onChange={(e) => setField('purpose', e.target.value)} />
                </Field>

                <Field wide label="Краткое описание" hint="Размер субсидии или другая короткая пометка из каталога">
                  <input value={draft.detail} onChange={(e) => setField('detail', e.target.value)} />
                </Field>
              </div>

              <Field
                label="Пояснение для клиента"
                hint="Этот текст показывается в анкете под выбранной программой"
              >
                <textarea rows={5} value={draft.logic} onChange={(e) => setField('logic', e.target.value)} />
              </Field>

              <div className="drawer__toggles">
                <Toggle
                  checked={Boolean(draft.is_active)}
                  onChange={(value) => setField('is_active', value)}
                  label="Показывать в кабинете"
                  hint="Выключена — клиенты не увидят программу в списке"
                />
                <Toggle
                  checked={Boolean(draft.allows_investment)}
                  onChange={(value) => setField('allows_investment', value)}
                  label="Подходит для инвестиций"
                  hint="Помещение, оборудование и CAPEX"
                />
                <Toggle
                  checked={Boolean(draft.allows_wc)}
                  onChange={(value) => setField('allows_wc', value)}
                  label="Подходит для оборотных"
                  hint="ПОС / оборотный капитал"
                />
              </div>
            </div>

            <footer className="drawer__foot">
              <button type="button" className="btn btn--ghost" disabled={busy} onClick={closeDrawer}>
                Отмена
              </button>
              <button type="button" className="btn" disabled={busy} onClick={save}>
                {busy ? 'Сохраняем…' : 'Сохранить'}
              </button>
            </footer>
          </aside>
        </div>
      ) : null}
    </div>
  )
}
