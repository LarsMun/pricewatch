export interface User {
  id: number
  email: string
  isVerified: boolean
  roles: string[]
  createdAt: string
  discordWebhookUrl: string | null
  slackWebhookUrl: string | null
  username: string | null
  isPublic: boolean
}

export interface AdminStats {
  users: {
    total: number
    verified: number
    unverified: number
    newLast7Days: number
  }
  watches: {
    total: number
    active: number
    paused: number
  }
  priceChecks: {
    last24h: number
    successful: number
    failed: number
    successRate: number
  }
  notifications: {
    last7Days: number
  }
  topDomains: Array<{ domain: string; count: number }>
}

export interface AdminUser {
  id: number
  email: string
  isVerified: boolean
  roles: string[]
  createdAt: string
  watchCount: number
}

export interface AdminUserDetail extends AdminUser {
  watches: Array<{
    id: number
    url: string
    domain: string
    productName: string | null
    currentPrice: string | null
    isActive: boolean
    consecutiveFailures: number
    lastCheckedAt: string | null
    createdAt: string
  }>
}

export interface RecentCheck {
  id: number
  price: string | null
  wasSuccessful: boolean
  httpStatus: number | null
  durationMs: number | null
  errorMessage: string | null
  checkedAt: string
  domain: string
  productName: string | null
  userEmail: string
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
  collectionIds: number[]
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
  jsonLdCategory?: string
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
  jsonLdCategory?: string | null
}

export interface WatchDetailResponse extends ProductWatch {
  priceChecks: PriceCheck[]
}

export interface Collection {
  id: number
  name: string
  description: string | null
  watchCount: number
  createdAt: string
  updatedAt: string | null
}

export interface CollectionWithWatches extends Collection {
  watches: ProductWatch[]
}

export interface CreateCollectionRequest {
  name: string
  description?: string
}

export interface UpdateCollectionRequest {
  name?: string
  description?: string | null
}

export interface Category {
  id: number
  name: string
  slug: string
  icon: string | null
  productCount: number
  children: Category[]
}

export interface CategoryInfo {
  id: number
  name: string
  slug: string
  icon: string | null
}
