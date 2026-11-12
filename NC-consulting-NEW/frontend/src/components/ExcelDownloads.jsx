import { useState } from 'react'
import { finModelFiles } from '../api.js'
import { ExcelIcon } from './FileIcons.jsx'

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

export default function ExcelDownloads({ id, small = false }) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const className = `${small ? 'btn btn--small btn--excel' : 'btn btn--excel'}${busy ? ' is-loading' : ''}`

  async function download() {
    if (busy || !id) return
    setBusy(true)
    setError('')
    try {
      const response = await fetch(finModelFiles(id).excel, { credentials: 'include' })
      if (!response.ok) {
        const data = await response.json().catch(() => null)
        const detail = typeof data?.detail === 'string' ? data.detail : 'Не удалось скачать фин модель'
        throw new Error(detail)
      }
      const blob = await response.blob()
      const name = fileNameFromHeader(response.headers.get('Content-Disposition'), `NC-fem-v5-${id}.xlsx`)
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = name
      document.body.appendChild(link)
      link.click()
      link.remove()
      URL.revokeObjectURL(url)
    } catch (err) {
      setError(err.message || 'Не удалось скачать фин модель')
    } finally {
      setBusy(false)
    }
  }

  return (
    <span className="excel-download">
      <button type="button" className={className} disabled={busy} onClick={download} aria-busy={busy}>
        <ExcelIcon />
        {busy ? 'Скачиваем…' : 'Фин модель'}
      </button>
      {busy ? <span className="spinner spinner--inline" aria-hidden="true" /> : null}
      {error ? <span className="excel-download__error">{error}</span> : null}
    </span>
  )
}
