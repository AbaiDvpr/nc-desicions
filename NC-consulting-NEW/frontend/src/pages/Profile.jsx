import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api.js'
import { useAuth } from '../auth/AuthContext.jsx'
import PasswordInput from '../components/PasswordInput.jsx'
import Toast from '../components/Toast.jsx'
import UserAvatar from '../components/UserAvatar.jsx'
import { compressImage } from '../image.js'
import { formatDate, formatMoney } from '../utils.js'

export default function Profile() {
  const { user, setUser } = useAuth()
  const [form, setForm] = useState({
    full_name: user.full_name,
    company: user.company ?? '',
    phone: user.phone ?? '',
  })
  const [profileError, setProfileError] = useState('')
  const [saving, setSaving] = useState(false)

  const [password, setPassword] = useState({ current: '', next: '', confirm: '' })
  const [passwordError, setPasswordError] = useState('')
  const [changingPassword, setChangingPassword] = useState(false)

  const [preview, setPreview] = useState('')
  const [photoBusy, setPhotoBusy] = useState('')
  const [photoError, setPhotoError] = useState('')
  const [dragOver, setDragOver] = useState(false)
  const [toast, setToast] = useState('')

  useEffect(() => {
    api.me().then(setUser).catch(() => {})
  }, [setUser])

  useEffect(() => () => preview && URL.revokeObjectURL(preview), [preview])

  const onChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  const onSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setProfileError('')
    try {
      setUser(await api.updateProfile(form))
      setToast('Данные сохранены')
    } catch (err) {
      setProfileError(err.message)
    } finally {
      setSaving(false)
    }
  }

  const onPassword = async (e) => {
    e.preventDefault()
    setPasswordError('')
    if (password.next.length < 8) {
      setPasswordError('Новый пароль должен быть не короче 8 символов')
      return
    }
    if (password.next !== password.confirm) {
      setPasswordError('Новый пароль и повтор не совпадают')
      return
    }
    setChangingPassword(true)
    try {
      await api.changePassword({
        current_password: password.current,
        new_password: password.next,
      })
      setPassword({ current: '', next: '', confirm: '' })
      setToast('Пароль изменён')
    } catch (err) {
      setPasswordError(err.message)
    } finally {
      setChangingPassword(false)
    }
  }

  const upload = async (file) => {
    if (!file) return
    setPhotoError('')
    setPhotoBusy('Сохраняем…')
    try {
      const compressed = await compressImage(file)
      const url = URL.createObjectURL(compressed)
      setPreview((current) => {
        if (current) URL.revokeObjectURL(current)
        return url
      })
      setPhotoBusy('Сохраняем…')
      setUser(await api.uploadAvatar(compressed))
      setToast('Фото сохранено')
    } catch (err) {
      setPhotoError(err.message)
    } finally {
      setPhotoBusy('')
    }
  }

  const onFile = (e) => {
    const file = e.target.files?.[0]
    e.target.value = ''
    upload(file)
  }

  const onDrop = (e) => {
    e.preventDefault()
    setDragOver(false)
    upload(e.dataTransfer.files?.[0])
  }

  const removePhoto = async () => {
    setPhotoError('')
    setPhotoBusy('Удаляем…')
    try {
      setUser(await api.deleteAvatar())
      setToast('Фото удалено')
      setPreview((current) => {
        if (current) URL.revokeObjectURL(current)
        return ''
      })
    } catch (err) {
      setPhotoError(err.message)
    } finally {
      setPhotoBusy('')
    }
  }

  return (
    <div className="page profile">
      <header className="page__header">
        <h1>Профиль</h1>
        <p className="muted">Данные, фото и пароль</p>
      </header>

      <div className="profile__layout">
        <section
          className={`card profile__photo ${dragOver ? 'profile__photo--over' : ''}`}
          onDragEnter={(e) => {
            e.preventDefault()
            setDragOver(true)
          }}
          onDragOver={(e) => {
            e.preventDefault()
            setDragOver(true)
          }}
          onDragLeave={(e) => {
            if (!e.currentTarget.contains(e.relatedTarget)) setDragOver(false)
          }}
          onDrop={onDrop}
        >
          <label className="photo-drop">
            <UserAvatar user={user} src={preview} className="avatar--xl" />
            <span className="photo-drop__badge">{photoBusy || (user.avatar_url || preview ? 'Изменить' : 'Добавить фото')}</span>
            <input type="file" accept="image/*" onChange={onFile} disabled={Boolean(photoBusy)} />
          </label>
          <div>
            <div className="profile__name">{user.full_name}</div>
            <div className="muted">{user.email}</div>
          </div>
          <div className="profile__balance">
            <span>Баланс</span>
            <strong>{formatMoney(user.balance ?? 0)}</strong>
            <Link to="/cabinet/balance">Пополнить</Link>
          </div>
          {photoError && <div className="alert alert--error">{photoError}</div>}
          <div className="muted">Клиент с {formatDate(user.created_at)}</div>
          {(user.avatar_url || preview) && (
            <button type="button" className="btn btn--small" onClick={removePhoto} disabled={Boolean(photoBusy)}>
              Удалить фото
            </button>
          )}
        </section>

        <form className="card" onSubmit={onSubmit}>
          <div className="card__header">
            <h2>Данные</h2>
          </div>
          {profileError && <div className="alert alert--error">{profileError}</div>}
          <label className="field">
            <span>ФИО</span>
            <input name="full_name" required value={form.full_name} onChange={onChange} autoComplete="name" />
          </label>
          <div className="field-row">
            <label className="field">
              <span>Компания</span>
              <input name="company" value={form.company} onChange={onChange} autoComplete="organization" />
            </label>
            <label className="field">
              <span>Телефон</span>
              <input name="phone" type="tel" value={form.phone} onChange={onChange} autoComplete="tel" />
            </label>
          </div>
          <label className="field">
            <span>Email</span>
            <input value={user.email} disabled />
            <span className="profile__hint">Используется для входа и здесь не меняется.</span>
          </label>
          <div className="card__actions">
            <button type="submit" className="btn btn--primary" disabled={saving}>
              {saving ? 'Сохраняем…' : 'Сохранить'}
            </button>
          </div>
        </form>

        <form className="card" onSubmit={onPassword}>
          <div className="card__header">
            <h2>Пароль</h2>
          </div>
          {passwordError && <div className="alert alert--error">{passwordError}</div>}
          <label className="field">
            <span>Текущий пароль</span>
            <PasswordInput
              name="current"
              autoComplete="current-password"
              required
              value={password.current}
              onChange={(e) => {
                setPassword({ ...password, current: e.target.value })
              }}
            />
          </label>
          <div className="field-row">
            <label className="field">
              <span>Новый пароль</span>
              <PasswordInput
                name="next"
                autoComplete="new-password"
                required
                minLength={8}
                maxLength={72}
                value={password.next}
                onChange={(e) => {
                  setPassword({ ...password, next: e.target.value })
                }}
              />
            </label>
            <label className="field">
              <span>Повторите пароль</span>
              <PasswordInput
                name="confirm"
                autoComplete="new-password"
                required
                minLength={8}
                maxLength={72}
                value={password.confirm}
                onChange={(e) => {
                  setPassword({ ...password, confirm: e.target.value })
                }}
              />
              <span className="profile__hint">Не короче 8 символов.</span>
            </label>
          </div>
          <div className="card__actions">
            <button type="submit" className="btn btn--primary" disabled={changingPassword}>
              {changingPassword ? 'Меняем…' : 'Сменить пароль'}
            </button>
          </div>
        </form>
      </div>
      <Toast message={toast} onClose={() => setToast('')} />
    </div>
  )
}
