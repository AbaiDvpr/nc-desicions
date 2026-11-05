import { useEffect } from 'react'

export default function Toast({ message, onClose }) {
  useEffect(() => {
    if (!message) return undefined
    const timer = setTimeout(onClose, 2400)
    return () => clearTimeout(timer)
  }, [message, onClose])

  if (!message) return null

  return (
    <div className="toast" role="status">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9" />
        <path d="M8 12.5 11 15.5 16.5 9" />
      </svg>
      <span>{message}</span>
    </div>
  )
}
