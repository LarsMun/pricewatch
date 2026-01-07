const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8100'

// Custom event for token expiration
export const TOKEN_EXPIRED_EVENT = 'auth:token-expired'

interface FetchOptions extends RequestInit {
  token?: string
}

async function request<T>(endpoint: string, options: FetchOptions = {}): Promise<T> {
  const { token, ...fetchOptions } = options

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...fetchOptions,
    headers,
  })

  if (!response.ok) {
    // Handle 401 Unauthorized - token expired or invalid
    if (response.status === 401 && token) {
      window.dispatchEvent(new CustomEvent(TOKEN_EXPIRED_EVENT))
      throw new Error('Sessie verlopen. Je wordt uitgelogd.')
    }

    const error = await response.json().catch(() => ({}))
    // Handle different error formats from backend
    const message = error.message || error.error ||
      (error.errors ? Object.values(error.errors).join(', ') : 'Er is een fout opgetreden')
    throw new Error(message)
  }

  return response.json()
}

export const api = {
  get: <T>(endpoint: string, token?: string) =>
    request<T>(endpoint, { method: 'GET', token }),

  post: <T>(endpoint: string, data: unknown, token?: string) =>
    request<T>(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
      token,
    }),

  patch: <T>(endpoint: string, data: unknown, token?: string) =>
    request<T>(endpoint, {
      method: 'PATCH',
      body: JSON.stringify(data),
      token,
    }),

  delete: <T>(endpoint: string, token?: string) =>
    request<T>(endpoint, { method: 'DELETE', token }),
}
