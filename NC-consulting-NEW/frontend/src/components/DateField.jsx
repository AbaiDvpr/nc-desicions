import { useEffect, useState } from 'react'
import { Dropdown } from './Dropdown.jsx'
import { useMenu } from './useMenu.js'

const WEEKDAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

function yearOptions(year, minDate, maxDate) {
  const now = new Date().getFullYear()
  let start = Math.min(now - 30, year)
  let end = Math.max(now + 30, year)
  if (minDate) start = Math.max(start, minDate.getFullYear())
  if (maxDate) end = Math.min(end, maxDate.getFullYear())
  if (end < start) end = start
  const options = []
  for (let item = start; item <= end; item += 1) options.push([item, String(item)])
  return options
}

const MONTHS = [
  'Январь',
  'Февраль',
  'Март',
  'Апрель',
  'Май',
  'Июнь',
  'Июль',
  'Август',
  'Сентябрь',
  'Октябрь',
  'Ноябрь',
  'Декабрь',
]
const MONTH_OPTIONS = MONTHS.map((name, index) => [index, name])

function parseIso(value) {
  const text = String(value || '')
  // Черновики часто хранят YYYY-MM — показываем как 1-е число месяца.
  const normalized = /^\d{4}-\d{2}$/.test(text) ? `${text}-01` : text
  if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return null
  const [year, month, day] = normalized.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null
  return date
}

/** Граница диапазона: YYYY-MM → начало или конец месяца. */
function parseBound(value, edge = 'start') {
  const text = String(value || '').trim()
  if (!text) return null
  if (/^\d{4}-\d{2}$/.test(text)) {
    const [year, month] = text.split('-').map(Number)
    if (edge === 'end') return new Date(year, month, 0)
    return new Date(year, month - 1, 1)
  }
  return parseIso(text)
}

function toIso(date) {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

function startOfDay(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate())
}

export function formatDate(value) {
  const date = parseIso(value)
  if (!date) return ''
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  return `${day}.${month}.${date.getFullYear()}`
}

/** Разбирает ввод: 01.01.2027 / 1.1.2027 / 01012027 / 2027-01-01. */
function parseTypedDate(raw) {
  const text = String(raw || '').trim()
  if (!text) return null
  const iso = parseIso(text)
  if (iso) return iso
  const digits = text.replace(/\D/g, '')
  let day
  let month
  let year
  const dotted = text.replace(/\//g, '.')
  if (/^\d{1,2}\.\d{1,2}\.\d{2,4}$/.test(dotted)) {
    // Accept both 01.01.2027 and 01/01/2027.
    const parts = dotted.split('.')
    day = Number(parts[0])
    month = Number(parts[1])
    year = Number(parts[2].length === 2 ? `20${parts[2]}` : parts[2])
  } else if (digits.length === 8) {
    day = Number(digits.slice(0, 2))
    month = Number(digits.slice(2, 4))
    year = Number(digits.slice(4, 8))
  } else {
    return null
  }
  if (!year || year < 1900 || year > 2100) return null
  const date = new Date(year, month - 1, day)
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null
  return date
}

/** Маска по ходу набора: 01012027 → 01.01.2027 */
function maskDateInput(raw) {
  const digits = String(raw || '').replace(/\D/g, '').slice(0, 8)
  if (digits.length <= 2) return digits
  if (digits.length <= 4) return `${digits.slice(0, 2)}.${digits.slice(2)}`
  return `${digits.slice(0, 2)}.${digits.slice(2, 4)}.${digits.slice(4)}`
}

function sameDay(left, right) {
  return (
    left &&
    right &&
    left.getFullYear() === right.getFullYear() &&
    left.getMonth() === right.getMonth() &&
    left.getDate() === right.getDate()
  )
}

function monthCells(view) {
  const year = view.getFullYear()
  const month = view.getMonth()
  const offset = (new Date(year, month, 1).getDay() + 6) % 7
  const count = new Date(year, month + 1, 0).getDate()
  const cells = Array.from({ length: offset }, () => null)
  for (let day = 1; day <= count; day += 1) cells.push(new Date(year, month, day))
  return cells
}

function clampView(date, minDate, maxDate) {
  let next = new Date(date.getFullYear(), date.getMonth(), 1)
  if (minDate) {
    const minView = new Date(minDate.getFullYear(), minDate.getMonth(), 1)
    if (next < minView) next = minView
  }
  if (maxDate) {
    const maxView = new Date(maxDate.getFullYear(), maxDate.getMonth(), 1)
    if (next > maxView) next = maxView
  }
  return next
}

export function DateField({
  value,
  onChange,
  required = false,
  disabled = false,
  placeholder = 'дд.мм.гггг',
  min,
  max,
}) {
  const { open, placement, rootRef, toggle, close } = useMenu()
  const selected = parseIso(value)
  const minDate = parseBound(min, 'start')
  const maxDate = parseBound(max, 'end')
  const today = new Date()
  const [view, setView] = useState(() =>
    clampView(selected || minDate || new Date(today.getFullYear(), today.getMonth(), 1), minDate, maxDate),
  )
  const [draft, setDraft] = useState(() => formatDate(value))
  const [focused, setFocused] = useState(false)

  useEffect(() => {
    if (!focused) setDraft(formatDate(value))
  }, [value, focused])

  const isDisabledDay = (date) => {
    const day = startOfDay(date)
    if (minDate && day < startOfDay(minDate)) return true
    if (maxDate && day > startOfDay(maxDate)) return true
    return false
  }

  const commitDate = (date) => {
    if (!date || isDisabledDay(date)) return false
    onChange(toIso(date))
    setDraft(formatDate(toIso(date)))
    setView(clampView(date, minDate, maxDate))
    return true
  }

  const openCalendar = () => {
    if (disabled) return
    const fallback = selected || minDate || new Date(today.getFullYear(), today.getMonth(), 1)
    setView(clampView(fallback, minDate, maxDate))
    if (!open) toggle()
  }

  const shiftMonth = (delta) => {
    setView((current) => clampView(new Date(current.getFullYear(), current.getMonth() + delta, 1), minDate, maxDate))
  }

  const choose = (date) => {
    if (!commitDate(date)) return
    close()
  }

  const applyTyped = (raw, { clearIfEmpty = false } = {}) => {
    const text = String(raw || '').trim()
    if (!text) {
      if (clearIfEmpty && !required) {
        onChange('')
        setDraft('')
      } else {
        setDraft(formatDate(value))
      }
      return
    }
    const date = parseTypedDate(text)
    if (!date || isDisabledDay(date)) {
      setDraft(formatDate(value))
      return
    }
    commitDate(date)
  }

  return (
    <div className={`picker ${open ? 'picker--open' : ''}`} ref={rootRef}>
      <input
        className={`picker__control ${selected || draft ? '' : 'picker__control--empty'}`}
        inputMode="numeric"
        autoComplete="off"
        required={required}
        disabled={disabled}
        value={draft}
        placeholder={placeholder}
        aria-haspopup="dialog"
        aria-expanded={open}
        onFocus={() => setFocused(true)}
        onChange={(event) => {
          const next = maskDateInput(event.target.value)
          setDraft(next)
          if (next.replace(/\D/g, '').length === 8) applyTyped(next)
        }}
        onBlur={() => {
          setFocused(false)
          applyTyped(draft, { clearIfEmpty: true })
        }}
        onKeyDown={(event) => {
          if (event.key === 'Enter') {
            event.preventDefault()
            applyTyped(draft, { clearIfEmpty: true })
            close()
          }
          if (event.key === 'ArrowDown' || event.key === 'F4') {
            event.preventDefault()
            openCalendar()
          }
          if (event.key === 'Escape' && open) {
            event.preventDefault()
            close()
          }
        }}
      />
      <button
        type="button"
        className="picker__chevron picker__chevron--calendar"
        disabled={disabled}
        aria-label="Открыть календарь"
        tabIndex={-1}
        onMouseDown={(event) => {
          event.preventDefault()
          openCalendar()
        }}
      />
      {open && (
        <div className={`picker__menu picker__menu--calendar picker__menu--${placement}`} role="dialog">
          <div className="cal__head">
            <button type="button" className="cal__nav" onClick={() => shiftMonth(-1)} aria-label="Предыдущий месяц">
              ‹
            </button>
            <div className="cal__pickers">
              <Dropdown
                className="cal__select"
                value={view.getMonth()}
                options={MONTH_OPTIONS}
                onChange={(month) =>
                  setView((current) => clampView(new Date(current.getFullYear(), month, 1), minDate, maxDate))
                }
              />
              <Dropdown
                className="cal__select cal__select--year"
                value={view.getFullYear()}
                options={yearOptions(view.getFullYear(), minDate, maxDate)}
                onChange={(year) =>
                  setView((current) => clampView(new Date(year, current.getMonth(), 1), minDate, maxDate))
                }
              />
            </div>
            <button type="button" className="cal__nav" onClick={() => shiftMonth(1)} aria-label="Следующий месяц">
              ›
            </button>
          </div>
          <div className="cal__grid">
            {WEEKDAYS.map((day) => (
              <span key={day} className="cal__weekday">
                {day}
              </span>
            ))}
            {monthCells(view).map((date, index) =>
              date ? (
                <button
                  key={toIso(date)}
                  type="button"
                  disabled={isDisabledDay(date)}
                  className={[
                    'cal__day',
                    sameDay(date, today) ? 'cal__day--today' : '',
                    sameDay(date, selected) ? 'cal__day--selected' : '',
                    isDisabledDay(date) ? 'cal__day--disabled' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                  onClick={() => choose(date)}
                >
                  {date.getDate()}
                </button>
              ) : (
                <span key={`empty-${index}`} />
              ),
            )}
          </div>
          <p className="cal__bounds">
            Можно набрать вручную, например 01.01.2027
            {minDate || maxDate
              ? `. ${
                  minDate && maxDate
                    ? `Диапазон: ${formatDate(toIso(minDate))} — ${formatDate(toIso(maxDate))}`
                    : minDate
                      ? `Не раньше ${formatDate(toIso(minDate))}`
                      : `Не позже ${formatDate(toIso(maxDate))}`
                }`
              : ''}
          </p>
        </div>
      )}
    </div>
  )
}
