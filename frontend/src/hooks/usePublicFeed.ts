import { useQuery, useMutation } from '@tanstack/react-query'
import { api } from '../api/client'

export interface PublicProduct {
  id: number
  productName: string
  url: string
  domain: string
  imageUrl: string | null
  currentPrice: string | null
  previousPrice: string | null
  originalPrice: string | null
  currency: string
  subscriberCount: number
  createdAt: string
  username?: string
  lastPriceChange?: {
    type: 'price_decrease' | 'price_increase'
    oldPrice: string
    newPrice: string
    changedAt: string
  }
}

export interface PublicProductDetail extends PublicProduct {
  priceHistory: Array<{
    price: string
    checkedAt: string
  }>
  watcherCount: number
}

export interface FeedResponse {
  products: PublicProduct[]
  totalCount: number
  page: number
  totalPages: number
}

export interface UserProfile {
  username: string
  memberSince: string
  productCount: number
  products: PublicProduct[]
}

export interface DomainsResponse {
  domains: Record<string, number>
}

export function usePublicFeed(page: number = 1, limit: number = 24, domain?: string) {
  const queryParams = new URLSearchParams({
    page: String(page),
    limit: String(limit),
  })
  if (domain) {
    queryParams.set('domain', domain)
  }

  return useQuery({
    queryKey: ['public-feed', page, limit, domain],
    queryFn: () => api.get<FeedResponse>(`/api/public/feed?${queryParams}`),
  })
}

export function useRecentPriceChanges(limit: number = 12) {
  return useQuery({
    queryKey: ['public-recent-changes', limit],
    queryFn: () =>
      api.get<{ products: PublicProduct[]; totalCount: number }>(
        `/api/public/feed/recent-changes?limit=${limit}`
      ),
  })
}

export function usePopularDomains() {
  return useQuery({
    queryKey: ['public-domains'],
    queryFn: () => api.get<DomainsResponse>('/api/public/feed/domains'),
  })
}

export function usePublicProduct(id: number) {
  return useQuery({
    queryKey: ['public-product', id],
    queryFn: () => api.get<{ product: PublicProductDetail }>(`/api/public/products/${id}`),
    enabled: !!id,
  })
}

export function useUserProfile(username: string) {
  return useQuery({
    queryKey: ['public-user', username],
    queryFn: () => api.get<{ user: UserProfile }>(`/api/public/users/${username}`),
    enabled: !!username,
  })
}

export function useSubscribe() {
  return useMutation({
    mutationFn: (data: { email: string; productId: number }) =>
      api.post<{ message: string }>('/api/public/subscribe', data),
  })
}

export function useVerifySubscription() {
  return useMutation({
    mutationFn: (token: string) =>
      api.post<{ message: string; productName: string }>('/api/public/verify-subscription', {
        token,
      }),
  })
}

export function useUnsubscribe() {
  return useMutation({
    mutationFn: (token: string) =>
      api.post<{ message: string }>('/api/public/unsubscribe', { token }),
  })
}
