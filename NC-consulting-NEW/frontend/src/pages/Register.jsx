import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext.jsx'
import BrandLogo from '../components/BrandLogo.jsx'
import PasswordInput from '../components/PasswordInput.jsx'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    full_name: '',
    email: '',
    company: '',
    phone: '',
    password: '',
    confirm: '',
  })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const onChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const onSubmit = async (e) => {
    e.preventDefault()
    setError('')
    if (form.password.length < 8) {
      setError('Пароль должен быть не короче 8 символов')
      return
    }
    if (form.password !== form.confirm) {
      setError('Пароли не совпадают')
      return
    }

    setSubmitting(true)
    try {
      const { confirm: _confirm, ...payload } = form
      await register(payload)
      navigate('/cabinet', { replace: true })
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="auth-page">
      <form className="auth-card" onSubmit={onSubmit}>
        <div className="auth-card__brand">
          <BrandLogo variant="dark" />
        </div>
        <h1>Регистрация</h1>
        <p className="muted">Создайте аккаунт клиента</p>

        {error && <div className="alert alert--error">{error}</div>}

        <label className="field">
          <span>ФИО *</span>
          <input name="full_name" autoComplete="name" required value={form.full_name} onChange={onChange} />
        </label>

        <label className="field">
          <span>Email *</span>
          <input name="email" type="email" autoComplete="email" required value={form.email} onChange={onChange} />
        </label>

        <div className="field-row">
          <label className="field">
            <span>Компания</span>
            <input name="company" autoComplete="organization" value={form.company} onChange={onChange} />
          </label>
          <label className="field">
            <span>Телефон</span>
            <input name="phone" type="tel" autoComplete="tel" value={form.phone} onChange={onChange} />
          </label>
        </div>

        <label className="field">
          <span>Пароль *</span>
          <PasswordInput name="password" autoComplete="new-password" required minLength={8} value={form.password} onChange={onChange} />
        </label>

        <label className="field">
          <span>Повторите пароль *</span>
          <PasswordInput name="confirm" autoComplete="new-password" required value={form.confirm} onChange={onChange} />
        </label>

        <button className="btn btn--primary btn--block" type="submit" disabled={submitting}>
          {submitting ? 'Создаём…' : 'Зарегистрироваться'}
        </button>

        <p className="auth-card__switch">
          Уже есть аккаунт? <Link to="/login">Войти</Link>
        </p>
      </form>
    </div>
  )
}
