import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { api } from '../api.js'
import { CURRENCIES, LEGAL_FORMS, TAX_REGIMES } from '../directory.js'

const EMPTY = {
  name: '',
  legal_form: '2',
  tax_regime: '1',
  currency: 'kzt',
  nds: '0',
  description: '',
}

export default function CompanyCreate() {
  const navigate = useNavigate()
  const [form, setForm] = useState(EMPTY)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  function set(key, value) {
    setForm((current) => ({ ...current, [key]: value }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    setError('')
    setBusy(true)
    try {
      const company = await api.createCompany({
        name: form.name.trim(),
        legal_form: form.legal_form,
        tax_regime: form.tax_regime,
        currency: form.currency,
        nds: form.nds === '1',
        description: form.description.trim(),
      })
      navigate(`/cabinet/companies/${company.id}`, { state: { notice: 'Компания создана' } })
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="page">
      <header className="page__header">
        <h1>Новая компания</h1>
        <p className="muted">Название, форма, налог и валюта учёта</p>
      </header>

      <form className="card" onSubmit={onSubmit}>
        {error && <div className="alert alert--error">{error}</div>}

        <div className="field-row">
          <label className="field">
            Название
            <input value={form.name} onChange={(event) => set('name', event.target.value)} required minLength={3} />
          </label>
          <label className="field">
            Организационно-правовая форма
            <select value={form.legal_form} onChange={(event) => set('legal_form', event.target.value)}>
              {LEGAL_FORMS.map((item) => (
                <option key={item.value} value={item.value}>{item.label}</option>
              ))}
            </select>
          </label>
        </div>

        <div className="field-row">
          <label className="field">
            Режим налогообложения
            <select value={form.tax_regime} onChange={(event) => set('tax_regime', event.target.value)}>
              {TAX_REGIMES.map((item) => (
                <option key={item.value} value={item.value}>{item.label}</option>
              ))}
            </select>
          </label>
          <label className="field">
            Валюта
            <select value={form.currency} onChange={(event) => set('currency', event.target.value)}>
              {CURRENCIES.map((item) => (
                <option key={item.value} value={item.value}>{item.label}</option>
              ))}
            </select>
          </label>
        </div>

        <label className="field">
          НДС
          <select value={form.nds} onChange={(event) => set('nds', event.target.value)}>
            <option value="0">Нет</option>
            <option value="1">Да</option>
          </select>
        </label>

        <label className="field">
          Описание
          <textarea value={form.description} onChange={(event) => set('description', event.target.value)} maxLength={1000} rows={4} />
        </label>

        <div className="card__actions">
          <Link to="/cabinet/companies" className="btn">Назад</Link>
          <button type="submit" className="btn btn--primary" disabled={busy}>
            {busy ? 'Сохраняем…' : 'Создать компанию'}
          </button>
        </div>
      </form>
    </div>
  )
}
