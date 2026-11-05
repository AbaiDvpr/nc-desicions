import { useId } from 'react'
import { OTHER } from './helpers.js'

const MONTHS = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек']
const THIS_YEAR = new Date().getFullYear()

function PlusIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
      <path d="M12 5v14M5 12h14" />
    </svg>
  )
}

function TrashIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
    </svg>
  )
}

export function Field({ label, required, hint, wide, group, children }) {
  const Tag = group ? 'div' : 'label'
  return (
    <Tag className={`fm-field ${wide ? 'fm-field--wide' : ''}`} role={group ? 'group' : undefined}>
      <span className="fm-field__label">
        {label}
        {required && <span className="fm-field__required"> *</span>}
      </span>
      {children}
      {hint && <span className="fm-field__hint">{hint}</span>}
    </Tag>
  )
}

export function TextInput({ value, onChange, ...props }) {
  return <input type="text" maxLength={255} value={value} onChange={(e) => onChange(e.target.value)} {...props} />
}

export function TextArea({ value, onChange, rows = 3, ...props }) {
  return <textarea rows={rows} maxLength={5000} value={value} onChange={(e) => onChange(e.target.value)} {...props} />
}

export function NumberInput({ value, onChange, suffix, signed, integer, max, ...props }) {
  return (
    <div className="fm-input-suffix">
      <input
        type="number"
        inputMode={integer ? 'numeric' : 'decimal'}
        min={signed ? undefined : 0}
        max={max}
        step={integer ? 1 : 'any'}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onWheel={(e) => e.currentTarget.blur()}
        {...props}
      />
      {suffix && <span className="fm-input-suffix__text">{suffix}</span>}
    </div>
  )
}

export function Select({ value, onChange, options, ...props }) {
  const pairs = options.map((option) => (Array.isArray(option) ? option : [option, option]))
  const change = (e) => onChange(pairs.find(([optionValue]) => String(optionValue) === e.target.value)[0])
  return (
    <select value={String(value)} onChange={change} {...props}>
      {pairs.map(([optionValue, label]) => (
        <option key={optionValue} value={String(optionValue)}>
          {label}
        </option>
      ))}
    </select>
  )
}

export function SelectOrOther({ value, onChange, options, required, otherLabel = 'Другое (указать)' }) {
  const isOther = value !== '' && !options.includes(value)
  const selectValue = isOther || value === OTHER ? OTHER : value
  return (
    <div className="fm-stack">
      <select
        value={selectValue}
        required={required}
        onChange={(e) => onChange(e.target.value === OTHER ? OTHER : e.target.value)}
      >
        <option value="">Выберите…</option>
        {options.map((option) => (
          <option key={option} value={option}>
            {option}
          </option>
        ))}
        <option value={OTHER}>{otherLabel}</option>
      </select>
      {selectValue === OTHER && (
        <input
          type="text"
          maxLength={255}
          required={required}
          placeholder="Укажите свой вариант"
          value={value === OTHER ? '' : value}
          onChange={(e) => onChange(e.target.value || OTHER)}
        />
      )}
    </div>
  )
}

export function MonthSelect({ value, onChange, required, emptyLabel = 'С начала проекта' }) {
  const [year, month] = value ? value.split('-') : ['', '']
  const years = Array.from({ length: 13 }, (_, i) => String(THIS_YEAR - 2 + i))
  if (year && !years.includes(year)) years.unshift(year)

  const change = (nextYear, nextMonth) => {
    if (!nextYear && !nextMonth) return onChange('')
    onChange(`${nextYear || THIS_YEAR}-${nextMonth || '01'}`)
  }

  return (
    <div className="fm-month">
      <select value={month} required={required} onChange={(e) => change(year, e.target.value)}>
        {!required && <option value="">{emptyLabel}</option>}
        {MONTHS.map((label, index) => {
          const optionValue = String(index + 1).padStart(2, '0')
          return (
            <option key={optionValue} value={optionValue}>
              {label}
            </option>
          )
        })}
      </select>
      <select value={year} required={required} onChange={(e) => change(e.target.value, month)}>
        {!required && <option value="">Год</option>}
        {years.map((y) => (
          <option key={y} value={y}>
            {y}
          </option>
        ))}
      </select>
    </div>
  )
}

export function Checkbox({ value, onChange, label }) {
  return (
    <label className="fm-checkbox">
      <input type="checkbox" checked={value} onChange={(e) => onChange(e.target.checked)} />
      {label}
    </label>
  )
}

export function Chips({ value, onChange, options }) {
  const toggle = (option) =>
    onChange(value.includes(option) ? value.filter((v) => v !== option) : [...value, option])
  return (
    <div className="fm-chips">
      {options.map((option) => (
        <button
          key={option}
          type="button"
          className={`fm-chip ${value.includes(option) ? 'fm-chip--active' : ''}`}
          aria-pressed={value.includes(option)}
          onClick={() => toggle(option)}
        >
          {option}
        </button>
      ))}
    </div>
  )
}

export function Section({ title, description, optional, defaultOpen = false, children }) {
  const head = (
    <>
      <span className="fm-section__title">
        {title}
        {optional && <span className="fm-section__badge">необязательно</span>}
      </span>
      {description && <span className="fm-section__description">{description}</span>}
    </>
  )

  if (optional) {
    return (
      <details className="fm-section fm-section--collapsible" open={defaultOpen}>
        <summary className="fm-section__head">{head}</summary>
        <div className="fm-section__body">{children}</div>
      </details>
    )
  }
  return (
    <section className="fm-section">
      <div className="fm-section__head">{head}</div>
      <div className="fm-section__body">{children}</div>
    </section>
  )
}

export function Grid({ children, columns }) {
  return <div className={`fm-grid ${columns ? `fm-grid--${columns}` : ''}`}>{children}</div>
}

export function MonthChips({ value, onChange, options }) {
  const selected = value.map(String)
  const toggle = (month) => {
    const key = String(month)
    onChange(selected.includes(key) ? value.filter((item) => String(item) !== key) : [...value, month])
  }
  return (
    <div className="fm-chips">
      {options.map(([month, label]) => (
        <button
          key={month}
          type="button"
          className={`fm-chip ${selected.includes(String(month)) ? 'fm-chip--active' : ''}`}
          aria-pressed={selected.includes(String(month))}
          onClick={() => toggle(month)}
        >
          {label}
        </button>
      ))}
    </div>
  )
}

export function ListEditor({ value: items, onChange, create, title, addLabel, emptyText, max = 30, children, compact }) {
  const id = useId()
  const patch = (index) => (partial) =>
    onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  const update = (index) => (key, value) => patch(index)({ [key]: value })
  const remove = (index) => onChange(items.filter((_, i) => i !== index))

  return (
    <div className="fm-list">
      {items.length === 0 && emptyText && <div className="fm-list__empty">{emptyText}</div>}
      {items.map((item, index) => (
        <div key={`${id}-${index}`} className={`fm-list__item ${compact ? 'fm-list__item--compact' : ''}`}>
          {!compact && (
            <div className="fm-list__head">
              <span className="fm-list__title">{title(item, index)}</span>
              <button type="button" className="fm-icon-btn" onClick={() => remove(index)} aria-label="Удалить">
                <TrashIcon />
              </button>
            </div>
          )}
          <div className="fm-list__body">
            {children(item, update(index), index, patch(index))}
            {compact && (
              <button type="button" className="fm-icon-btn" onClick={() => remove(index)} aria-label="Удалить">
                <TrashIcon />
              </button>
            )}
          </div>
        </div>
      ))}
      {items.length < max && (
        <button type="button" className="btn btn--small fm-list__add" onClick={() => onChange([...items, create()])}>
          <PlusIcon />
          {addLabel}
        </button>
      )}
    </div>
  )
}

export function Summary({ rows }) {
  return (
    <dl className="fm-summary">
      {rows.map(([label, value, tone]) => (
        <div key={label} className={`fm-summary__row ${tone ? `fm-summary__row--${tone}` : ''}`}>
          <dt>{label}</dt>
          <dd>{value}</dd>
        </div>
      ))}
    </dl>
  )
}
