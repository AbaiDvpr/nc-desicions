import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { api } from '../api.js'

const LINKS = [
  { to: '/', label: 'Главная', end: true },
  { to: '/tasks', label: 'Задачи' },
  { to: '/users', label: 'Пользователи' },
  { to: '/models', label: 'Проекты' },
  { to: '/companies', label: 'Управленческий учёт' },
  { to: '/tariffs', label: 'Тарифы' },
  { to: '/refills', label: 'Запросы на пополнение' },
  { to: '/bp', label: 'Бизнес-план', end: true },
  { to: '/bp/jobs', label: 'Сборки' },
  { to: '/fem-rates', label: 'Ставки FEM' },
  { to: '/gov-programs', label: 'Госпрограммы' },
]

export default function AdminLayout({ admin, onLogout }) {
  const navigate = useNavigate()

  async function logout() {
    await api.logout()
    onLogout()
    navigate('/login')
  }

  return (
    <div className="shell">
      <aside className="side">
        <img src="/logo.svg" alt="NC-Desicion" className="side__logo" />
        <nav>
          {LINKS.map((link) => (
            <NavLink key={link.to} to={link.to} end={link.end} className="side__link">
              {link.label}
            </NavLink>
          ))}
        </nav>
        <button type="button" className="side__out" onClick={logout}>Выйти</button>
      </aside>
      <main className="main">
        <div className="main__who">{admin.full_name}</div>
        <Outlet />
      </main>
    </div>
  )
}
