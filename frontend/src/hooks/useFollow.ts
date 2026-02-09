import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../api/client'
import { useAuth } from '../contexts/AuthContext'
import type { FollowersResponse, FollowingResponse, FollowingIdsResponse } from '../types'

interface FollowResponse {
  message: string
  followerCount: number
}

export function useFollowingIds() {
  const { token } = useAuth()

  return useQuery({
    queryKey: ['following-ids'],
    queryFn: () => api.get<FollowingIdsResponse>('/api/me/following/ids', token!),
    enabled: !!token,
    staleTime: 30 * 1000, // 30 seconds
  })
}

export function useFollowers(userId: number, page: number = 1, limit: number = 20) {
  return useQuery({
    queryKey: ['followers', userId, page, limit],
    queryFn: () =>
      api.get<FollowersResponse>(`/api/users/${userId}/followers?page=${page}&limit=${limit}`),
    enabled: !!userId,
  })
}

export function useFollowing(userId: number, page: number = 1, limit: number = 20) {
  return useQuery({
    queryKey: ['following', userId, page, limit],
    queryFn: () =>
      api.get<FollowingResponse>(`/api/users/${userId}/following?page=${page}&limit=${limit}`),
    enabled: !!userId,
  })
}

export function useFollowUser() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (userId: number) =>
      api.post<FollowResponse>(`/api/users/${userId}/follow`, {}, token!),
    onSuccess: (data, userId) => {
      // Invalidate relevant queries
      queryClient.invalidateQueries({ queryKey: ['following-ids'] })
      queryClient.invalidateQueries({ queryKey: ['followers', userId] })
      queryClient.invalidateQueries({ queryKey: ['following'] })
      queryClient.invalidateQueries({ queryKey: ['public-user'] })
    },
  })
}

export function useUnfollowUser() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (userId: number) =>
      api.delete<FollowResponse>(`/api/users/${userId}/follow`, token!),
    onSuccess: (data, userId) => {
      // Invalidate relevant queries
      queryClient.invalidateQueries({ queryKey: ['following-ids'] })
      queryClient.invalidateQueries({ queryKey: ['followers', userId] })
      queryClient.invalidateQueries({ queryKey: ['following'] })
      queryClient.invalidateQueries({ queryKey: ['public-user'] })
    },
  })
}

export function useIsFollowing(userId: number): boolean {
  const { data: followingData } = useFollowingIds()
  return followingData?.followingIds.includes(userId) ?? false
}
