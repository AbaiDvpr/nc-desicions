import { useEffect, useRef } from 'react'

function CheckIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M8 12.5 11 15.5 16.5 9" />
    </svg>
  )
}

function AlertIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7.5v6" />
      <path d="M12 16.5h.01" />
    </svg>
  )
}

export default function Toast({ message, onClose, tone = 'ok', place = 'bottom', duration = 2400 }) {
  const closeRef = useRef(onClose)
  closeRef.current = onClose

  useEffect(() => {
    if (!message) return undefined
    const timer = setTimeout(() => closeRef.current(), duration)
    return () => clearTimeout(timer)
  }, [message, duration])

  if (!message) return null

  return (
    <div className={`toast toast--${tone} toast--${place}`} role="status">
      {tone === 'error' ? <AlertIcon /> : <CheckIcon />}
      <span>{message}</span>
    </div>
  )
}
