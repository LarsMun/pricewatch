import { useQuery } from '@tanstack/react-query'
import { api } from '../api/client'
import type { PublicProduct } from './usePublicFeed'

export interface DiscoverCollection {
  id: number
  name: string
  description: string | null
  slug: string
  productCount: number
  thumbnailUrl: string | null
  createdAt: string
  user: {
    id: number
    username: string
  }
}

export interface DiscoverUser {
  id: number
  username: string
  followerCount: number
  productCount: number
  memberSince: string
}

export interface DiscoverCollectionsResponse {
  collections: DiscoverCollection[]
  totalCount: number
  page: number
  totalPages: number
}

export interface DiscoverUsersResponse {
  users: DiscoverUser[]
  totalCount: number
  page: number
  totalPages: number
}

export interface HomepageResponse {
  trendingProducts: PublicProduct[]
  recentCollections: DiscoverCollection[]
  activeUsers: DiscoverUser[]
  stats: {
    totalProducts: number
    totalUsers: number
  }
}

export type DiscoverSortOption = 'recent' | 'popular'

export const DISCOVER_SORT_OPTIONS: Record<DiscoverSortOption, string> = {
  recent: 'Nieuwste',
  popular: 'Populairste',
}

export function useDiscoverCollections(
  sort: DiscoverSortOption = 'recent',
  page: number = 1,
  limit: number = 12
) {
  return useQuery({
    queryKey: ['discover-collections', sort, page, limit],
    queryFn: () =>
      api.get<DiscoverCollectionsResponse>(
        `/api/discover/collections?sort=${sort}&page=${page}&limit=${limit}`
      ),
  })
}

export function useDiscoverUsers(
  sort: DiscoverSortOption = 'recent',
  page: number = 1,
  limit: number = 12
) {
  return useQuery({
    queryKey: ['discover-users', sort, page, limit],
    queryFn: () =>
      api.get<DiscoverUsersResponse>(
        `/api/discover/users?sort=${sort}&page=${page}&limit=${limit}`
      ),
  })
}

export function useHomepage() {
  return useQuery({
    queryKey: ['homepage'],
    queryFn: () => api.get<HomepageResponse>('/api/public/homepage'),
    staleTime: 60 * 1000, // 1 minute
  })
}
