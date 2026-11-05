import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api } from '../api.js'
import Toast from '../components/Toast.jsx'
import { CURRENCIES, LEGAL_FORMS, TAX_REGIMES } from '../directory.js'

const EMPTY_REQUISITE = {
  name: '',
  legal_address: '',
  actual_address: '',
  email: '',
  phone: '',
  director: '',
  bin: '',
  iik: '',
  bank_name: '',
  bik: '',
}

export default function CompanySettings() {
  const { id } = useParams()
  const [form, setForm] = useState(null)
  const [requisites, setRequisites] = useState([])
  const [current, setCurrent] = useState(null)
  const [banks, setBanks] = useState([])
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [busy, setBusy] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)

  useEffect(() => {
    api.listBanks().then(setBanks).catch((err) => setError(err.message))
    api
      .getCompany(id)
      .then((company) => {
        setForm({
          name: company.name,
          legal_form: company.legal_form,
          tax_regime: company.tax_regime,
          currency: company.currency,
          nds: company.nds ? '1' : '0',
          description: company.description || '',
        })
      })
      .catch((err) => setError(err.message))
    api
      .listRequisites(id)
      .then((rows) => {
        setRequisites(rows)
        setCurrent(rows[0]?.id ?? null)
      })
      .catch((err) => setError(err.message))
  }, [id])

  function setField(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  function patchRequisite(key, value) {
    setRequisites((rows) => rows.map((row) => (row.id === current ? { ...row, [key]: value } : row)))
  }

  function onBank(name) {
    const bank = banks.find((item) => item.name === name)
    setRequisites((rows) =>
      rows.map((row) => (row.id === current ? { ...row, bank_name: name, bik: bank?.bik || '' } : row)),
    )
  }

  const active = requisites.find((row) => row.id === current) || null

  async function saveProfile(event) {
    event.preventDefault()
    setError('')
    setBusy(true)
    try {
      await api.updateCompany(id, {
        name: form.name.trim(),
        legal_form: form.legal_form,
        tax_regime: form.tax_regime,
        currency: form.currency,
        nds: form.nds === '1',
        description: form.description.trim(),
      })
      setNotice('Данные компании сохранены')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function addRequisite() {
    setError('')
    setBusy(true)
    try {
      const created = await api.createRequisite(id, { ...EMPTY_REQUISITE, name: `Реквизит ${requisites.length + 1}` })
      setRequisites((rows) => [...rows, created])
      setCurrent(created.id)
      setConfirmDelete(false)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function saveRequisite() {
    if (!active) return
    if (active.bin && !/^\d{12}$/.test(active.bin.trim())) {
      setError('БИН или ИИН — 12 цифр')
      return
    }
    setError('')
    setBusy(true)
    try {
      const saved = await api.updateRequisite(id, active.id, {
        name: active.name.trim(),
        legal_address: active.legal_address.trim(),
        actual_address: active.actual_address.trim(),
        email: active.email.trim(),
        phone: active.phone.trim(),
        director: active.director.trim(),
        bin: active.bin.trim(),
        iik: active.iik.trim(),
        bank_name: active.bank_name,
        bik: active.bik,
      })
      setRequisites((rows) => rows.map((row) => (row.id === saved.id ? saved : row)))
      setNotice('Реквизит сохранён')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function removeRequisite() {
    if (!active) return
    setError('')
    setBusy(true)
    try {
      await api.deleteRequisite(id, active.id)
      const next = requisites.filter((row) => row.id !== active.id)
      setRequisites(next)
      setCurrent(next[0]?.id ?? null)
      setConfirmDelete(false)
      setNotice('Реквизит удалён')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="page">
      <Toast message={notice} onClose={() => setNotice('')} />
      <header className="page__header">
        <Link to={`/cabinet/companies/${id}`} className="muted">← Управленческий учёт</Link>
        <h1>Настройки компании</h1>
      </header>

      {error && <div className="alert alert--error">{error}</div>}
      {form === null && !error && <div className="spinner" />}

      {form && (
        <div className="settings-grid">
          <form className="card" onSubmit={saveProfile}>
            <div className="card__header"><h2>Профиль</h2></div>
            <label className="field">
              Наименование
              <span className="field-inline">
                <input value={form.name} onChange={(event) => setField('name', event.target.value)} required minLength={3} />
                <select value={form.legal_form} onChange={(event) => setField('legal_form', event.target.value)}>
                  {LEGAL_FORMS.map((item) => (
                    <option key={item.value} value={item.value}>{item.label}</option>
                  ))}
                </select>
              </span>
            </label>
            <label className="field">
              Форма налогообложения
              <select value={form.tax_regime} onChange={(event) => setField('tax_regime', event.target.value)}>
                {TAX_REGIMES.map((item) => (
                  <option key={item.value} value={item.value}>{item.label}</option>
                ))}
              </select>
            </label>
            <div className="field-row">
              <label className="field">
                Валюта
                <select value={form.currency} onChange={(event) => setField('currency', event.target.value)}>
                  {CURRENCIES.map((item) => (
                    <option key={item.value} value={item.value}>{item.label}</option>
                  ))}
                </select>
              </label>
              <label className="field">
                НДС
                <select value={form.nds} onChange={(event) => setField('nds', event.target.value)}>
                  <option value="0">Нет</option>
                  <option value="1">Да</option>
                </select>
              </label>
            </div>
            <label className="field">
              Описание
              <textarea value={form.description} onChange={(event) => setField('description', event.target.value)} rows={6} maxLength={1000} />
            </label>
            <div className="card__actions">
              <button type="submit" className="btn btn--primary" disabled={busy}>Сохранить</button>
            </div>
          </form>

          <section className="card">
            <div className="card__header page__header--row">
              <h2>Реквизиты</h2>
              {requisites.length < 5 && (
                <button type="button" className="btn btn--small btn--primary" onClick={addRequisite} disabled={busy}>
                  Добавить
                </button>
              )}
            </div>

            {requisites.length === 0 && <p className="muted">Реквизитов пока нет.</p>}

            {requisites.length > 0 && (
              <>
                <div className="req-tabs">
                  {requisites.map((row, index) => (
                    <button
                      key={row.id}
                      type="button"
                      className={`req-tabs__item ${row.id === current ? 'is-active' : ''}`}
                      onClick={() => {
                        setCurrent(row.id)
                        setConfirmDelete(false)
                      }}
                    >
                      Реквизит {index + 1}
                    </button>
                  ))}
                </div>

                {active && (
                  <div>
                    <label className="field">
                      Наименование компании / физ. лица
                      <input value={active.name} onChange={(event) => patchRequisite('name', event.target.value)} />
                    </label>
                    <div className="field-row">
                      <label className="field">
                        Юридический адрес
                        <input value={active.legal_address} onChange={(event) => patchRequisite('legal_address', event.target.value)} />
                      </label>
                      <label className="field">
                        Фактический адрес
                        <input value={active.actual_address} onChange={(event) => patchRequisite('actual_address', event.target.value)} />
                      </label>
                    </div>
                    <div className="field-row">
                      <label className="field">
                        E-mail
                        <input value={active.email} onChange={(event) => patchRequisite('email', event.target.value)} />
                      </label>
                      <label className="field">
                        Телефон
                        <input value={active.phone} onChange={(event) => patchRequisite('phone', event.target.value)} />
                      </label>
                    </div>
                    <div className="field-row">
                      <label className="field">
                        Директор
                        <input value={active.director} onChange={(event) => patchRequisite('director', event.target.value)} />
                      </label>
                      <label className="field">
                        Банк
                        <select value={active.bank_name} onChange={(event) => onBank(event.target.value)}>
                          <option value="">Укажите банк</option>
                          {banks.map((bank) => (
                            <option key={bank.bik} value={bank.name}>{bank.name}</option>
                          ))}
                        </select>
                      </label>
                    </div>
                    <div className="field-row">
                      <label className="field">
                        БИН / ИИН
                        <input value={active.bin} onChange={(event) => patchRequisite('bin', event.target.value)} inputMode="numeric" maxLength={12} />
                      </label>
                      <label className="field">
                        ИИК
                        <input value={active.iik} onChange={(event) => patchRequisite('iik', event.target.value)} />
                      </label>
                      <label className="field">
                        БИК
                        <input value={active.bik} disabled />
                      </label>
                    </div>
                    <div className="card__actions">
                      {confirmDelete ? (
                        <button type="button" className="btn btn--danger" onClick={removeRequisite} disabled={busy}>
                          Удалить
                        </button>
                      ) : (
                        <button type="button" className="btn" onClick={() => setConfirmDelete(true)}>Удалить</button>
                      )}
                      <button type="button" className="btn btn--primary" onClick={saveRequisite} disabled={busy}>
                        Сохранить
                      </button>
                    </div>
                  </div>
                )}
              </>
            )}
          </section>
        </div>
      )}
    </div>
  )
}
