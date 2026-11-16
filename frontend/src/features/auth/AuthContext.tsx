/* eslint-disable react-hooks/set-state-in-effect, react-refresh/only-export-components */
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { api, setUnauthorizedHandler } from '../../lib/api'
import type { User } from '../../types'

type AuthContextValue = { user: User | null; isLoading: boolean; login: (email: string, password: string) => Promise<void>; logout: () => Promise<void> }
const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const clearSession = useCallback(() => { sessionStorage.removeItem('ticketdesk_token'); setUser(null) }, [])
  useEffect(() => { setUnauthorizedHandler(clearSession); const token = sessionStorage.getItem('ticketdesk_token'); if (!token) { setIsLoading(false); return } api.me().then((res) => setUser(res.data)).catch(clearSession).finally(() => setIsLoading(false)) }, [clearSession])
  const value = useMemo<AuthContextValue>(() => ({ user, isLoading, login: async (email, password) => { const res = await api.login({ email, password }); sessionStorage.setItem('ticketdesk_token', res.token); setUser(res.user) }, logout: async () => { try { await api.logout() } finally { clearSession() } } }), [clearSession, isLoading, user])
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
export const useAuth = () => { const context = useContext(AuthContext); if (!context) throw new Error('useAuth must be used within AuthProvider'); return context }
