import { useState } from 'react'
import { finModelFiles } from '../api.js'

function fileNameFromHeader(header, fallback) {
  if (!header) return fallback
  const utf = /filename\*=UTF-8''([^;]+)/i.exec(header)
  if (utf?.[1]) {
    try {
      return decodeURIComponent(utf[1].trim())
    } catch {
      return utf[1].trim()
    }
  }
  const plain = /filename="?([^";]+)"?/i.exec(header)
  return plain?.[1]?.trim() || fallback
}

function JsonIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M8 4c-2 0-3 1.5-3 4v2c0 1.5-.5 2.5-2 3 1.5.5 2 1.5 2 3v2c0 2.5 1 4 3 4" />
      <path d="M16 4c2 0 3 1.5 3 4v2c0 1.5.5 2.5 2 3-1.5.5-2 1.5-2 3v2c0 2.5-1 4-3 4" />
    </svg>
  )
}

export default function AuditDownload({ id, small = false }) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const className = `${small ? 'btn btn--small btn--audit' : 'btn btn--audit'}${busy ? ' is-loading' : ''}`

  async function download() {
    if (busy || !id) return
    setBusy(true)
    setError('')
    try {
      const response = await fetch(finModelFiles(id).audit, { credentials: 'include' })
      if (!response.ok) {
        const data = await response.json().catch(() => null)
        const detail = typeof data?.detail === 'string' ? data.detail : 'Не удалось скачать JSON аудита'
        throw new Error(detail)
      }
      const blob = await response.blob()
      const name = fileNameFromHeader(response.headers.get('Content-Disposition'), `NC-audit-steps1-2-3-4-${id}.json`)
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = name
      document.body.appendChild(link)
      link.click()
      link.remove()
      URL.revokeObjectURL(url)
    } catch (err) {
      setError(err.message || 'Не удалось скачать JSON аудита')
    } finally {
      setBusy(false)
    }
  }

  return (
    <span className="excel-download">
      <button
        type="button"
        className={className}
        disabled={busy}
        onClick={download}
        aria-busy={busy}
        title="JSON шагов 1–4 для ИИ-аудита"
      >
        <JsonIcon />
        {busy ? 'Готовим…' : 'JSON'}
      </button>
      {busy ? <span className="spinner spinner--inline" aria-hidden="true" /> : null}
      {error ? <span className="excel-download__error">{error}</span> : null}
    </span>
  )
}
