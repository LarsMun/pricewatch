import { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { api, TOKEN_EXPIRED_EVENT } from '../api/client'
import type { User } from '../types'

interface AuthContextType {
  user: User | null
  token: string | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (email: string, password: string) => Promise<void>
  logout: () => void
  refreshUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | null>(null)

const TOKEN_KEY = 'pricewatch_token'

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY))
  const [isLoading, setIsLoading] = useState(true)

  async function fetchUser(authToken: string) {
    try {
      const userData = await api.get<User>('/api/me', authToken)
      setUser(userData)
    } catch {
      // Token is invalid, clear it
      localStorage.removeItem(TOKEN_KEY)
      setToken(null)
      setUser(null)
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    if (token) {
      fetchUser(token)
    } else {
      setIsLoading(false)
    }
  }, [token])

  // Sync auth state when another tab changes the token
  useEffect(() => {
    function handleStorageChange(e: StorageEvent) {
      if (e.key === TOKEN_KEY) {
        const newToken = e.newValue
        if (newToken !== token) {
          queryClient.clear()
          setUser(null)
          setToken(newToken)
          if (newToken) {
            setIsLoading(true)
          }
        }
      }
    }

    window.addEventListener('storage', handleStorageChange)
    return () => window.removeEventListener('storage', handleStorageChange)
  }, [token, queryClient])

  async function login(email: string, password: string) {
    const response = await api.post<{ token: string }>('/api/login', {
      username: email,
      password,
    })

    localStorage.setItem(TOKEN_KEY, response.token)
    setIsLoading(true) // Prevent redirect while fetching user
    setToken(response.token)
  }

  async function register(email: string, password: string) {
    await api.post('/api/register', { email, password })
    // Auto-login after registration
    await login(email, password)
  }

  const logout = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY)
    setToken(null)
    setUser(null)
    queryClient.clear()
  }, [queryClient])

  // Listen for token expiration events from API client
  useEffect(() => {
    function handleTokenExpired() {
      logout()
    }

    window.addEventListener(TOKEN_EXPIRED_EVENT, handleTokenExpired)
    return () => window.removeEventListener(TOKEN_EXPIRED_EVENT, handleTokenExpired)
  }, [logout])

  async function refreshUser() {
    if (token) {
      await fetchUser(token)
    }
  }

  return (
    <AuthContext.Provider value={{ user, token, isLoading, login, register, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}
