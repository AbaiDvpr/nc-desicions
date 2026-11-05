import { useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext.jsx'
import BrandLogo from '../components/BrandLogo.jsx'
import PasswordInput from '../components/PasswordInput.jsx'

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [form, setForm] = useState({ email: '', password: '' })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const onChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const onSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await login(form)
      navigate(location.state?.from?.pathname ?? '/cabinet', { replace: true })
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
        <h1>Вход</h1>
        <p className="muted">Войдите в личный кабинет</p>

        {error && <div className="alert alert--error">{error}</div>}

        <label className="field">
          <span>Email</span>
          <input name="email" type="email" autoComplete="email" required value={form.email} onChange={onChange} />
        </label>

        <label className="field">
          <span>Пароль</span>
          <PasswordInput name="password" autoComplete="current-password" required value={form.password} onChange={onChange} />
        </label>

        <button className="btn btn--primary btn--block" type="submit" disabled={submitting}>
          {submitting ? 'Входим…' : 'Войти'}
        </button>

        <p className="auth-card__switch">
          Нет аккаунта? <Link to="/register">Зарегистрироваться</Link>
        </p>
      </form>
    </div>
  )
}
