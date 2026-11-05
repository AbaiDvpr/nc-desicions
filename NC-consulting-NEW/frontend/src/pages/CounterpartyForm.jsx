import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { api } from '../api.js'

const EMPTY = {
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

export default function CounterpartyForm() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [form, setForm] = useState(EMPTY)
  const [banks, setBanks] = useState([])
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api.listBanks().then(setBanks).catch((err) => setError(err.message))
    if (!id) return
    api
      .getCounterparty(id)
      .then((current) => {
        setForm({
          name: current.name,
          legal_address: current.legal_address,
          actual_address: current.actual_address || '',
          email: current.email,
          phone: current.phone || '',
          director: current.director,
          bin: current.bin,
          iik: current.iik,
          bank_name: current.bank_name,
          bik: current.bik,
        })
      })
      .catch((err) => setError(err.message))
  }, [id])

  function set(key, value) {
    setForm((current) => ({ ...current, [key]: value }))
  }

  function onBank(name) {
    const bank = banks.find((item) => item.name === name)
    setForm((current) => ({ ...current, bank_name: name, bik: bank?.bik || '' }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    setError('')
    if (!/^\d{12}$/.test(form.bin.trim())) {
      setError('БИН или ИИН — 12 цифр')
      return
    }
    if (!form.bank_name) {
      setError('Выберите банк')
      return
    }
    setBusy(true)
    const payload = {
      ...form,
      name: form.name.trim(),
      legal_address: form.legal_address.trim(),
      actual_address: form.actual_address.trim(),
      email: form.email.trim(),
      phone: form.phone.trim(),
      director: form.director.trim(),
      bin: form.bin.trim(),
      iik: form.iik.trim(),
    }
    try {
      if (id) await api.updateCounterparty(id, payload)
      else await api.createCounterparty(payload)
      navigate('/cabinet/counterparties', {
        state: { notice: id ? 'Контрагент сохранён' : 'Контрагент добавлен' },
      })
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="page">
      <header className="page__header">
        <h1>{id ? 'Контрагент' : 'Новый контрагент'}</h1>
        <p className="muted">Реквизиты компании или физического лица</p>
      </header>

      <form className="card" onSubmit={onSubmit}>
        {error && <div className="alert alert--error">{error}</div>}

        <label className="field">
          Наименование компании / физ. лица
          <input value={form.name} onChange={(event) => set('name', event.target.value)} required minLength={2} />
        </label>

        <div className="field-row">
          <label className="field">
            Юридический адрес
            <input value={form.legal_address} onChange={(event) => set('legal_address', event.target.value)} required minLength={3} />
          </label>
          <label className="field">
            Фактический адрес
            <input value={form.actual_address} onChange={(event) => set('actual_address', event.target.value)} />
          </label>
        </div>

        <div className="field-row">
          <label className="field">
            E-mail
            <input type="email" value={form.email} onChange={(event) => set('email', event.target.value)} required />
          </label>
          <label className="field">
            Телефон
            <input value={form.phone} onChange={(event) => set('phone', event.target.value)} placeholder="+7" />
          </label>
        </div>

        <div className="field-row">
          <label className="field">
            Директор
            <input value={form.director} onChange={(event) => set('director', event.target.value)} required minLength={3} />
          </label>
          <label className="field">
            Банк
            <select value={form.bank_name} onChange={(event) => onBank(event.target.value)} required>
              <option value="">Выберите банк</option>
              {banks.map((bank) => (
                <option key={bank.bik} value={bank.name}>{bank.name}</option>
              ))}
            </select>
          </label>
        </div>

        <div className="field-row">
          <label className="field">
            БИН / ИИН
            <input value={form.bin} onChange={(event) => set('bin', event.target.value)} required inputMode="numeric" minLength={12} maxLength={12} />
          </label>
          <label className="field">
            ИИК
            <input value={form.iik} onChange={(event) => set('iik', event.target.value)} required />
          </label>
          <label className="field">
            БИК
            <input value={form.bik} disabled />
          </label>
        </div>

        <div className="card__actions">
          <Link to="/cabinet/counterparties" className="btn">Назад</Link>
          <button type="submit" className="btn btn--primary" disabled={busy}>
            {busy ? 'Сохраняем…' : 'Сохранить'}
          </button>
        </div>
      </form>
    </div>
  )
}
