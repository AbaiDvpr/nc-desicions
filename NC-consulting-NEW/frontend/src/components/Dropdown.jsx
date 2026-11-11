import { useEffect, useRef } from 'react'
import { useMenu } from './useMenu.js'

function pairsOf(options) {
  return options.map((option) => (Array.isArray(option) ? option : [option, option]))
}

export function Dropdown({
  value,
  onChange,
  options,
  placeholder = 'Выберите',
  required = false,
  disabled = false,
  className = '',
}) {
  const { open, placement, rootRef, toggle, close } = useMenu()
  const menuRef = useRef(null)
  const pairs = pairsOf(options)
  const current = pairs.find(([optionValue]) => String(optionValue) === String(value))
  const label = current ? current[1] : ''

  useEffect(() => {
    if (!open) return
    menuRef.current?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' })
  }, [open])

  const choose = (optionValue) => {
    onChange(optionValue)
    close()
  }

  return (
    <div className={`picker ${open ? 'picker--open' : ''} ${className}`.trim()} ref={rootRef}>
      <input
        className={`picker__control ${label ? '' : 'picker__control--empty'}`}
        readOnly
        required={required}
        disabled={disabled}
        value={label}
        placeholder={placeholder}
        aria-haspopup="listbox"
        aria-expanded={open}
        onClick={() => {
          if (!disabled) toggle()
        }}
      />
      <span className="picker__chevron" aria-hidden="true" />
      {open && (
        <div className={`picker__menu picker__menu--${placement}`} role="listbox" ref={menuRef}>
          {pairs.map(([optionValue, optionLabel]) => {
            const active = String(optionValue) === String(value)
            return (
              <button
                key={optionValue}
                type="button"
                role="option"
                aria-selected={active}
                className={`picker__option ${active ? 'picker__option--active' : ''}`}
                onClick={() => choose(optionValue)}
              >
                {optionLabel}
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}
