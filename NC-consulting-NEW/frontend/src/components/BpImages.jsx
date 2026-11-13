import { useEffect, useState } from 'react'
import { api } from '../api.js'
import { compressImage } from '../image.js'

const UPLOAD_EDGE = 1800

function ImageRow({ modelId, item, sections, onChange, onRemove }) {
  const [caption, setCaption] = useState(item.caption || '')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  const save = async (payload) => {
    setBusy(true)
    setError('')
    try {
      onChange(await api.updateBpImage(modelId, item.id, payload))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  const remove = async () => {
    setBusy(true)
    setError('')
    try {
      await api.deleteBpImage(modelId, item.id)
      onRemove(item.id)
    } catch (err) {
      setError(err.message)
      setBusy(false)
    }
  }

  return (
    <li className={`bp-images__item${busy ? ' is-busy' : ''}`}>
      <a className="bp-images__thumb" href={item.url} target="_blank" rel="noreferrer">
        <img src={item.url} alt={item.caption || 'Картинка бизнес-плана'} loading="lazy" />
      </a>
      <div className="bp-images__fields">
        <select
          value={item.section}
          disabled={busy}
          aria-label="Раздел бизнес-плана"
          onChange={(event) => save({ section: event.target.value })}
        >
          {sections.map((section) => (
            <option key={section.key} value={section.key}>
              {section.title}
            </option>
          ))}
        </select>
        <input
          type="text"
          value={caption}
          maxLength={200}
          disabled={busy}
          placeholder="Подпись под картинкой"
          onChange={(event) => setCaption(event.target.value)}
          onBlur={() => {
            if (caption.trim() !== (item.caption || '')) save({ caption })
          }}
          onKeyDown={(event) => {
            if (event.key === 'Enter') event.currentTarget.blur()
          }}
        />
        {error && <span className="bp-images__error">{error}</span>}
      </div>
      <button type="button" className="bp-images__remove" disabled={busy} onClick={remove} aria-label="Удалить картинку" title="Удалить">
        ×
      </button>
    </li>
  )
}

export default function BpImages({ modelId }) {
  const [items, setItems] = useState(null)
  const [sections, setSections] = useState([])
  const [limit, setLimit] = useState(20)
  const [section, setSection] = useState('appendices')
  const [uploading, setUploading] = useState('')
  const [error, setError] = useState('')
  const [dragOver, setDragOver] = useState(false)

  useEffect(() => {
    let stop = false
    api
      .bpImages(modelId)
      .then((data) => {
        if (stop) return
        setItems(data.items)
        setSections(data.sections)
        setLimit(data.limit)
      })
      .catch((err) => {
        if (!stop) setError(err.message)
      })
    return () => {
      stop = true
    }
  }, [modelId])

  const upload = async (fileList) => {
    const files = Array.from(fileList || []).filter((file) => file.type.startsWith('image/'))
    if (!files.length) {
      if (fileList?.length) setError('Можно загрузить только изображения')
      return
    }
    const room = limit - (items?.length || 0)
    if (room <= 0) {
      setError(`Не больше ${limit} картинок`)
      return
    }
    setError('')
    const batch = files.slice(0, room)
    for (const [index, file] of batch.entries()) {
      setUploading(batch.length > 1 ? `Загружаем ${index + 1} из ${batch.length}…` : 'Загружаем…')
      try {
        const compressed = await compressImage(file, { maxEdge: UPLOAD_EDGE, quality: 0.88, name: 'bp.jpg' })
        const saved = await api.uploadBpImage(modelId, compressed, { section })
        setItems((current) => [...(current || []), saved])
      } catch (err) {
        setError(`${file.name}: ${err.message}`)
        break
      }
    }
    if (files.length > room) setError(`Загружены первые ${room}: больше ${limit} картинок нельзя`)
    setUploading('')
  }

  const full = items !== null && items.length >= limit

  return (
    <section className="bp-images">
      <div className="bp-images__head">
        <h3>Картинки в бизнес-план</h3>
        {items !== null && (
          <span className="muted">
            {items.length} из {limit}
          </span>
        )}
      </div>
      <p className="bp-images__hint">
        Фото помещения, оборудования, продукции, логотип. Картинки встают в конец выбранного раздела Word и PDF при скачивании —
        заново собирать план не нужно.
      </p>
      {!full && (
        <div className="bp-images__add">
          <label className="bp-images__where">
            <span>Куда вставить новые картинки</span>
            <select value={section} onChange={(event) => setSection(event.target.value)}>
              {sections.map((item) => (
                <option key={item.key} value={item.key}>
                  {item.title}
                </option>
              ))}
            </select>
          </label>
          <label
            className={`bp-images__drop${dragOver ? ' is-over' : ''}${uploading ? ' is-busy' : ''}`}
            onDragEnter={(event) => {
              event.preventDefault()
              setDragOver(true)
            }}
            onDragOver={(event) => {
              event.preventDefault()
              setDragOver(true)
            }}
            onDragLeave={(event) => {
              if (!event.currentTarget.contains(event.relatedTarget)) setDragOver(false)
            }}
            onDrop={(event) => {
              event.preventDefault()
              setDragOver(false)
              if (!uploading) upload(event.dataTransfer.files)
            }}
          >
            {uploading ? (
              <>
                <span className="spinner spinner--inline" aria-hidden="true" />
                <span>{uploading}</span>
              </>
            ) : (
              <span>Перетащите картинки сюда или нажмите, чтобы выбрать</span>
            )}
            <input
              type="file"
              accept="image/*"
              multiple
              disabled={Boolean(uploading) || items === null}
              onChange={(event) => {
                const files = Array.from(event.target.files || [])
                event.target.value = ''
                upload(files)
              }}
            />
          </label>
        </div>
      )}
      {error && <div className="alert alert--error">{error}</div>}
      {items === null && !error && <div className="spinner spinner--inline" />}
      {items?.length > 0 && (
        <ul className="bp-images__list">
          {items.map((item) => (
            <ImageRow
              key={item.id}
              modelId={modelId}
              item={item}
              sections={sections}
              onChange={(saved) => setItems((current) => current.map((row) => (row.id === saved.id ? saved : row)))}
              onRemove={(id) => setItems((current) => current.filter((row) => row.id !== id))}
            />
          ))}
        </ul>
      )}
    </section>
  )
}
