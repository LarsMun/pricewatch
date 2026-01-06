import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../api/client'
import { useAuth } from '../contexts/AuthContext'
import type { Collection, CollectionWithWatches, CreateCollectionRequest, UpdateCollectionRequest } from '../types'

interface CollectionsListResponse {
  collections: Collection[]
}

interface CollectionResponse {
  collection: CollectionWithWatches
}

interface CollectionMutationResponse {
  message: string
  collection: Collection
}

export function useCollections() {
  const { token } = useAuth()

  return useQuery({
    queryKey: ['collections'],
    queryFn: async () => {
      const response = await api.get<CollectionsListResponse>('/api/collections', token!)
      return response.collections
    },
    enabled: !!token,
  })
}

export function useCollection(id: number | null) {
  const { token } = useAuth()

  return useQuery({
    queryKey: ['collections', id],
    queryFn: async () => {
      const response = await api.get<CollectionResponse>(`/api/collections/${id}`, token!)
      return response.collection
    },
    enabled: !!token && !!id,
  })
}

export function useCreateCollection() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (data: CreateCollectionRequest) => {
      const response = await api.post<CollectionMutationResponse>('/api/collections', data, token!)
      return response.collection
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['collections'] })
    },
  })
}

export function useUpdateCollection() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: UpdateCollectionRequest }) => {
      const response = await api.patch<CollectionMutationResponse>(`/api/collections/${id}`, data, token!)
      return response.collection
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['collections'] })
      queryClient.invalidateQueries({ queryKey: ['collections', variables.id] })
    },
  })
}

export function useDeleteCollection() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) =>
      api.delete<void>(`/api/collections/${id}`, token!),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['collections'] })
    },
  })
}

export function useAddWatchToCollection() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ collectionId, watchId }: { collectionId: number; watchId: number }) => {
      const response = await api.post<CollectionMutationResponse>(
        `/api/collections/${collectionId}/watches/${watchId}`,
        {},
        token!
      )
      return response.collection
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['collections'] })
      queryClient.invalidateQueries({ queryKey: ['collections', variables.collectionId] })
    },
  })
}

export function useRemoveWatchFromCollection() {
  const { token } = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ collectionId, watchId }: { collectionId: number; watchId: number }) => {
      const response = await api.delete<CollectionMutationResponse>(
        `/api/collections/${collectionId}/watches/${watchId}`,
        token!
      )
      return response.collection
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['collections'] })
      queryClient.invalidateQueries({ queryKey: ['collections', variables.collectionId] })
    },
  })
}
