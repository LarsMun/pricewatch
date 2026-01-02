import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../api/client'
import { useAuth } from '../contexts/AuthContext'
import type { ProductWatch, PriceCheck, CreateWatchRequest, AnalyzeUrlResponse } from '../types'

interface WatchListResponse {
  watches: ProductWatch[]
  total: number
}

interface WatchDetailResponse {
  watch: ProductWatch & { lastSeenRawText?: string }
  priceHistory: PriceCheck[]
}

export function useWatches() {
  const { token } = useAuth()

  return useQuery({
    queryKey: ['watches'],
    queryFn: async () => {
      const response = await api.get<WatchListResponse>('/api/watches', token!)
      return response.watches
    },
    enabled: !!token,
  })
}

export function useWatch(id: number) {
  const { token } = useAuth()

  return useQuery({
    queryKey: ['watches', id],
    queryFn: async () => {
      const response = await api.get<WatchDetailResponse>(`/api/watches/${id}`, token!)
      return {
        ...response.watch,
        priceChecks: response.priceHistory,
      }
    },
    enabled: !!token && !!id,
  })
}

interface CreateWatchResponse {
  message: string
  watch: ProductWatch
}

export function useCreateWatch() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (data: CreateWatchRequest) => {
      const response = await api.post<CreateWatchResponse>('/api/watches', data, token!)
      return response.watch
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['watches'] })
    },
  })
}

export function useDeleteWatch() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) =>
      api.delete<void>(`/api/watches/${id}`, token!),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['watches'] })
    },
  })
}

export function useToggleWatch() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, isActive }: { id: number; isActive: boolean }) =>
      api.patch<ProductWatch>(`/api/watches/${id}`, { isActive }, token!),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['watches'] })
    },
  })
}

interface CheckAllResponse {
  total: number
  success: number
  failed: number
  checks: Array<{
    id: number
    name: string
    success: boolean
    price?: string
    error?: string
  }>
}

export function useCheckAllWatches() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async () => {
      const response = await api.post<CheckAllResponse>('/api/watches/check-all', {}, token!)
      return response
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['watches'] })
    },
  })
}

export function useAnalyzeUrl() {
  const { token } = useAuth()

  return useMutation({
    mutationFn: async (url: string) => {
      const response = await api.post<AnalyzeUrlResponse>('/api/watches/analyze', { url }, token!)
      return response
    },
  })
}
