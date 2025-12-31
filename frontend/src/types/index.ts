export interface User {
  id: number
  email: string
  isVerified: boolean
  createdAt: string
}

export interface ProductWatch {
  id: number
  url: string
  domain: string
  productName: string | null
  priceSelector: string
  currency: string
  currentPrice: string | null
  previousPrice: string | null
  originalPrice: string | null
  checkMethod: 'http' | 'browser'
  consecutiveFailures: number
  nextCheckAt: string
  lastCheckedAt: string | null
  lastSuccessfulCheckAt: string | null
  isActive: boolean
  createdAt: string
}

export interface PriceCheck {
  id: number
  price: string | null
  rawText: string | null
  wasSuccessful: boolean
  httpStatus: number | null
  durationMs: number | null
  errorMessage: string | null
  checkedAt: string
}

export interface Notification {
  id: number
  oldPrice: string | null
  newPrice: string | null
  type: 'price_decrease' | 'price_increase' | 'site_broken'
  sentAt: string
}

export interface ApiError {
  error: {
    code: string
    message: string
  }
}

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
}

export interface RegisterRequest {
  email: string
  password: string
}
