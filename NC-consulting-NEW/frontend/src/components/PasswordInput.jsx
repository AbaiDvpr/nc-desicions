import { useState } from 'react'

function EyeIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
      <circle cx="12" cy="12" r="3" />
    </svg>
  )
}

function EyeOffIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.6 3.6" />
      <path d="M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6" />
      <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
      <path d="M2 2l20 20" />
    </svg>
  )
}

export default function PasswordInput(props) {
  const [visible, setVisible] = useState(false)

  return (
    <div className="password-input">
      <input {...props} type={visible ? 'text' : 'password'} />
      <button
        type="button"
        className="password-input__toggle"
        onClick={() => setVisible((v) => !v)}
        aria-label={visible ? 'Скрыть пароль' : 'Показать пароль'}
        title={visible ? 'Скрыть пароль' : 'Показать пароль'}
      >
        {visible ? <EyeOffIcon /> : <EyeIcon />}
      </button>
    </div>
  )
}
