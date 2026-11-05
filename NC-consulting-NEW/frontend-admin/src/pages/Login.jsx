import { useState } from 'react'
import { api } from '../api.js'

export default function Login({ onLogin }) {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function onSubmit(event) {
    event.preventDefault()
    setError('')
    setBusy(true)
    try {
      const admin = await api.login({ email: email.trim(), password })
      onLogin(admin)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="login">
      <form className="card login__card" onSubmit={onSubmit}>
        <img src="/logo-dark.svg" alt="NC-Desicion" className="login__logo" />
        <h1>Админка</h1>
        {error && <div className="alert">{error}</div>}
        <label>
          Email
          <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
        </label>
        <label>
          Пароль
          <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
        </label>
        <button type="submit" className="btn" disabled={busy}>{busy ? 'Входим…' : 'Войти'}</button>
      </form>
    </div>
  )
}
