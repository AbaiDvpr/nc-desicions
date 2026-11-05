import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from './AuthContext.jsx'

function FullScreenLoader() {
  return (
    <div className="loader-screen">
      <div className="spinner" />
    </div>
  )
}

export function ProtectedRoute() {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <FullScreenLoader />
  if (!user) return <Navigate to="/login" replace state={{ from: location }} />
  return <Outlet />
}

export function PublicOnlyRoute() {
  const { user, loading } = useAuth()

  if (loading) return <FullScreenLoader />
  if (user) return <Navigate to="/cabinet" replace />
  return <Outlet />
}
