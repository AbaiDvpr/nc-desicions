import { useEffect, useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext.jsx'
import UserAvatar from '../components/UserAvatar.jsx'
import BrandLogo from '../components/BrandLogo.jsx'

function Icon({ children }) {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      {children}
    </svg>
  )
}

const HomeIcon = () => (
  <Icon>
    <path d="M3 10.5 12 3l9 7.5" />
    <path d="M5 9.5V21h14V9.5" />
  </Icon>
)

const UserIcon = () => (
  <Icon>
    <circle cx="12" cy="8" r="4" />
    <path d="M4 21a8 8 0 0 1 16 0" />
  </Icon>
)

const ChartIcon = () => (
  <Icon>
    <path d="M3 3v18h18" />
    <path d="M7 15l4-4 3 3 5-6" />
  </Icon>
)

const LedgerIcon = () => (
  <Icon>
    <rect x="4" y="3" width="16" height="18" rx="2" />
    <path d="M8 7h8M8 11h8M8 15h5" />
  </Icon>
)

const CardIcon = () => (
  <Icon>
    <rect x="2" y="5" width="20" height="14" rx="2" />
    <path d="M2 10h20M6 15h4" />
  </Icon>
)

const TagIcon = () => (
  <Icon>
    <path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z" />
    <circle cx="7.5" cy="7.5" r="1.5" />
  </Icon>
)

const InfoIcon = () => (
  <Icon>
    <circle cx="12" cy="12" r="9" />
    <path d="M12 16v-4M12 8h.01" />
  </Icon>
)

const PeopleIcon = () => (
  <Icon>
    <circle cx="8" cy="8" r="3" />
    <circle cx="16" cy="9" r="2.5" />
    <path d="M2.5 20a5.5 5.5 0 0 1 11 0" />
    <path d="M14 20a4.5 4.5 0 0 1 7.5-3.3" />
  </Icon>
)

const LogoutIcon = () => (
  <Icon>
    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
    <path d="M16 17l5-5-5-5" />
    <path d="M21 12H9" />
  </Icon>
)

const MenuIcon = () => (
  <Icon>
    <path d="M4 6h16M4 12h16M4 18h16" />
  </Icon>
)

const CloseIcon = () => (
  <Icon>
    <path d="M6 6l12 12M18 6L6 18" />
  </Icon>
)

const NAV_ITEMS = [
  { to: '/cabinet', label: 'Главная', icon: HomeIcon, end: true },
  { to: '/cabinet/profile', label: 'Профиль', icon: UserIcon },
  { to: '/cabinet/models', label: 'Фин модели', icon: ChartIcon },
  { to: '/cabinet/companies', label: 'Компании', icon: LedgerIcon },
  { to: '/cabinet/counterparties', label: 'Контрагенты', icon: PeopleIcon },
  { to: '/cabinet/tariff', label: 'Мой тариф', icon: CardIcon },
  { to: '/cabinet/tariffs', label: 'Тарифы', icon: TagIcon },
  { to: '/cabinet/about', label: 'Информация', icon: InfoIcon },
]

export default function CabinetLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const [menuOpen, setMenuOpen] = useState(false)

  useEffect(() => {
    if (!menuOpen) return
    const onKeyDown = (e) => e.key === 'Escape' && setMenuOpen(false)
    document.addEventListener('keydown', onKeyDown)
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', onKeyDown)
      document.body.style.overflow = ''
    }
  }, [menuOpen])

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  const closeOnLinkClick = (e) => {
    if (e.target.closest('a')) setMenuOpen(false)
  }

  return (
    <div className="cabinet">
      <header className="mobile-header">
        <button
          type="button"
          className="mobile-header__burger"
          onClick={() => setMenuOpen(true)}
          aria-label="Открыть меню"
          aria-expanded={menuOpen}
        >
          <MenuIcon />
        </button>
        <span className="mobile-header__brand">
          <BrandLogo />
        </span>
        <NavLink to="/cabinet/profile" className="mobile-header__avatar" aria-label="Профиль">
          <UserAvatar user={user} className="avatar--sm" />
        </NavLink>
      </header>

      <div
        className={`sidebar-backdrop ${menuOpen ? 'sidebar-backdrop--visible' : ''}`}
        onClick={() => setMenuOpen(false)}
        aria-hidden="true"
      />

      <aside className={`sidebar ${menuOpen ? 'sidebar--open' : ''}`} onClick={closeOnLinkClick}>
        <div className="sidebar__brand">
          <BrandLogo />
          <button
            type="button"
            className="sidebar__close"
            onClick={() => setMenuOpen(false)}
            aria-label="Закрыть меню"
          >
            <CloseIcon />
          </button>
        </div>

        <nav className="sidebar__nav">
          {NAV_ITEMS.map(({ to, label, icon: ItemIcon, end }) => (
            <NavLink key={label} to={to} end={end} className="sidebar__link">
              <ItemIcon />
              <span className="sidebar__label">{label}</span>
            </NavLink>
          ))}
        </nav>

        <div className="sidebar__footer">
          <NavLink to="/cabinet/profile" className="sidebar__user">
            <UserAvatar user={user} />
            <div className="sidebar__user-info">
              <div className="sidebar__user-name">{user.full_name}</div>
              <div className="sidebar__user-email">{user.email}</div>
            </div>
          </NavLink>
          <button type="button" className="sidebar__link sidebar__logout" onClick={handleLogout}>
            <LogoutIcon />
            Выйти
          </button>
        </div>
      </aside>

      <main className="cabinet__content">
        <Outlet />
      </main>
    </div>
  )
}
