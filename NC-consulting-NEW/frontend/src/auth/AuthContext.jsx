import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { api } from '../api.js'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api
      .me()
      .then(setUser)
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [])

  const login = useCallback(async (payload) => {
    setUser(await api.login(payload))
  }, [])

  const register = useCallback(async (payload) => {
    setUser(await api.register(payload))
  }, [])

  const logout = useCallback(async () => {
    await api.logout().catch(() => {})
    setUser(null)
  }, [])

  const value = useMemo(
    () => ({ user, setUser, loading, login, register, logout }),
    [user, loading, login, register, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
