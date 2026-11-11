import { useEffect, useState } from 'react'
import { api } from '../api.js'

const IP_FIELDS = [
  { key: 'simplified_ip_rate', label: 'Упрощёнка', unit: '%', hint: 'СНР упрощённая декларация для ИП' },
  { key: 'cit_ip_rate', label: 'ИПН / КПН на ОУР', unit: '%', hint: 'Общий режим для ИП' },
]

const TOO_FIELDS = [
  { key: 'simplified_too_rate', label: 'Упрощёнка', unit: '%', hint: 'СНР упрощённая декларация для ТОО' },
  { key: 'cit_too_rate', label: 'КПН на ОУР', unit: '%', hint: 'Общий режим для ТОО' },
  { key: 'social_tax_rate', label: 'Социальный налог', unit: '%', hint: 'ТОО на ОУР с 2026' },
]

const SHARED_FIELDS = [
  { key: 'vat_rate', label: 'НДС', unit: '%', hint: 'В ДДС при плательщике НДС' },
  { key: 'vosms_rate', label: 'ВОСМС работника', unit: '%', hint: 'Удержание из зарплаты к выплате' },
  { key: 'nbk_bank_spread', label: 'НБ РК + N%', unit: 'п.п.', hint: 'Надбавка к базовой ставке НБРК для кредита' },
]

const ALL_RATE_KEYS = [...IP_FIELDS, ...TOO_FIELDS, ...SHARED_FIELDS].map((field) => field.key)

function money(value) {
  const amount = Number(value)
  if (!Number.isFinite(amount)) return '—'
  return `${new Intl.NumberFormat('ru-RU').format(amount)} ₸`
}

function RateGrid({ fields, form, onChange }) {
  return (
    <div className="rates-grid">
      {fields.map((field) => (
        <label key={field.key} className="rates-field">
          <span className="rates-field__label">{field.label}</span>
          <span className="rates-field__control">
            <input
              type="number"
              min="0"
              max={field.key === 'nbk_bank_spread' ? 50 : 100}
              step="0.01"
              value={form[field.key] ?? ''}
              onChange={(event) => onChange(field.key, event.target.value)}
            />
            <span className="rates-field__unit">{field.unit}</span>
          </span>
          <span className="rates-field__hint">{field.hint}</span>
        </label>
      ))}
    </div>
  )
}

export default function FemRates() {
  const [form, setForm] = useState(null)
  const [mzpRows, setMzpRows] = useState([])
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [ok, setOk] = useState('')

  useEffect(() => {
    api
      .femRates()
      .then((data) => {
        setForm(data)
        setMzpRows(
          (data.mzp_rows || []).map((row) => ({
            year: String(row.year),
            amount: String(row.amount),
          })),
        )
      })
      .catch((err) => setError(err.message))
  }, [])

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const setMzp = (index, partial) => {
    setMzpRows((rows) => rows.map((row, i) => (i === index ? { ...row, ...partial } : row)))
  }

  async function save() {
    setBusy(true)
    setError('')
    setOk('')
    try {
      const payload = {
        ...Object.fromEntries(ALL_RATE_KEYS.map((key) => [key, Number(form[key])])),
        mzp_rows: mzpRows
          .filter((row) => row.year !== '' && row.amount !== '')
          .map((row) => ({ year: Number(row.year), amount: Number(String(row.amount).replace(/\s/g, '')) })),
      }
      const saved = await api.updateFemRates(payload)
      setForm(saved)
      setMzpRows((saved.mzp_rows || []).map((row) => ({ year: String(row.year), amount: String(row.amount) })))
      const latest = (saved.mzp_rows || []).at(-1)
      setOk(
        `Сохранено. ИП упрощёнка ${saved.simplified_ip_rate}%, ТОО упрощёнка ${saved.simplified_too_rate}%. МЗП: ${
          latest ? `${latest.year} — ${money(latest.amount)}` : 'не задана'
        }.`,
      )
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  if (!form && !error) return <div className="boot">Загрузка…</div>

  return (
    <div className="rates-page">
      <div className="page-head">
        <div>
          <h1>Ставки FEM</h1>
          <p className="muted">
            Отдельно для ИП и ТОО: упрощёнка и общий режим. Клиент в анкете видит дефолт своей формы бизнеса и может
            поменять для проекта.
          </p>
        </div>
        <div className="page-head__actions">
          <button type="button" className="btn" disabled={busy || !form} onClick={save}>
            {busy ? 'Сохраняем…' : 'Сохранить'}
          </button>
        </div>
      </div>

      {error && <div className="alert">{error}</div>}
      {ok && <div className="ok">{ok}</div>}

      {form && (
        <>
          <div className="rates-split">
            <section className="card rates-card">
              <header className="rates-card__head">
                <div>
                  <h2>ИП</h2>
                  <p className="muted">Упрощёнка и общий режим для индивидуального предпринимателя.</p>
                </div>
              </header>
              <RateGrid fields={IP_FIELDS} form={form} onChange={set} />
            </section>

            <section className="card rates-card">
              <header className="rates-card__head">
                <div>
                  <h2>ТОО</h2>
                  <p className="muted">Упрощёнка, КПН и соцналог для товарищества.</p>
                </div>
              </header>
              <RateGrid fields={TOO_FIELDS} form={form} onChange={set} />
            </section>
          </div>

          <section className="card rates-card">
            <header className="rates-card__head">
              <div>
                <h2>Общие ставки</h2>
                <p className="muted">Одинаковы для ИП и ТОО. Кредит: «{form.label_nbk || 'НБ РК + 4%'}».</p>
              </div>
            </header>
            <RateGrid fields={SHARED_FIELDS} form={form} onChange={set} />
          </section>

          <section className="card rates-card">
            <header className="rates-card__head">
              <div>
                <h2>МЗП по годам</h2>
                <p className="muted">
                  Минимальная заработная плата в тенге. Для года без своей суммы берётся последняя известная не позже
                  него.
                </p>
              </div>
              <button
                type="button"
                className="btn btn--ghost btn--sm"
                onClick={() => {
                  const years = mzpRows.map((row) => Number(row.year)).filter(Boolean)
                  const nextYear = years.length ? Math.max(...years) + 1 : new Date().getFullYear()
                  const lastAmount = mzpRows.at(-1)?.amount || '85000'
                  setMzpRows((rows) => [...rows, { year: String(nextYear), amount: String(lastAmount) }])
                }}
              >
                Добавить год
              </button>
            </header>

            <div className="mzp-list">
              {mzpRows.map((row, index) => (
                <div key={`${row.year}-${index}`} className="mzp-row">
                  <label className="rates-field">
                    <span className="rates-field__label">Год</span>
                    <span className="rates-field__control">
                      <input
                        type="number"
                        min="2000"
                        max="2100"
                        value={row.year}
                        onChange={(event) => setMzp(index, { year: event.target.value })}
                      />
                    </span>
                  </label>
                  <label className="rates-field">
                    <span className="rates-field__label">Сумма МЗП</span>
                    <span className="rates-field__control">
                      <input
                        type="number"
                        min="0"
                        step="1"
                        value={row.amount}
                        onChange={(event) => setMzp(index, { amount: event.target.value })}
                      />
                      <span className="rates-field__unit">₸</span>
                    </span>
                  </label>
                  <button
                    type="button"
                    className="btn btn--ghost btn--sm mzp-row__remove"
                    disabled={mzpRows.length <= 1}
                    onClick={() => setMzpRows((rows) => rows.filter((_, i) => i !== index))}
                  >
                    Удалить
                  </button>
                </div>
              ))}
              {!mzpRows.length ? <p className="muted">Нет строк. Добавьте год МЗП.</p> : null}
            </div>
          </section>
        </>
      )}
    </div>
  )
}
