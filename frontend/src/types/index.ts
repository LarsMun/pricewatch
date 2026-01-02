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
  imageUrl: string | null
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

export interface CreateWatchRequest {
  url: string
  priceSelector: string
  productName?: string
  currency?: string
  imageUrl?: string
}

export interface AnalyzeUrlResponse {
  success: boolean
  url: string
  domain: string
  productName: string | null
  price: string | null
  currency: string
  imageUrl: string | null
  priceSelector: string | null
  detectionMethod: 'jsonld' | 'css' | 'none'
  availableSelectors: Array<{
    selector: string
    price: string
    rawText: string
    recommended?: boolean
  }>
  error?: string
}

export interface WatchDetailResponse extends ProductWatch {
  priceChecks: PriceCheck[]
}
