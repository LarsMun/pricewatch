import { useState } from 'react'
import { useCollections, useDeleteCollection, useUpdateCollection } from '../hooks/useCollections'
import ConfirmModal from './ConfirmModal'
import type { Collection } from '../types'

interface CollectionTabsProps {
  selectedCollection: number | null
  onSelectCollection: (id: number | null) => void
  onCreateNew: () => void
  totalWatchCount: number
}

export default function CollectionTabs({
  selectedCollection,
  onSelectCollection,
  onCreateNew,
  totalWatchCount,
}: CollectionTabsProps) {
  const { data: collections, isLoading } = useCollections()
  const [editingId, setEditingId] = useState<number | null>(null)
  const [editName, setEditName] = useState('')
  const [deleteConfirmId, setDeleteConfirmId] = useState<number | null>(null)
  const updateCollection = useUpdateCollection()
  const deleteCollection = useDeleteCollection()

  const collectionToDelete = collections?.find(c => c.id === deleteConfirmId)

  const handleStartEdit = (collection: Collection) => {
    setEditingId(collection.id)
    setEditName(collection.name)
  }

  const handleSaveEdit = () => {
    if (editingId && editName.trim()) {
      updateCollection.mutate(
        { id: editingId, data: { name: editName.trim() } },
        { onSuccess: () => setEditingId(null) }
      )
    }
  }

  const handleConfirmDelete = () => {
    if (deleteConfirmId) {
      deleteCollection.mutate(deleteConfirmId, {
        onSuccess: () => {
          if (selectedCollection === deleteConfirmId) {
            onSelectCollection(null)
          }
          setDeleteConfirmId(null)
        }
      })
    }
  }

  if (isLoading) {
    return (
      <div className="flex gap-2 mb-4">
        <div className="h-10 w-24 bg-gray-200 rounded-lg animate-pulse" />
        <div className="h-10 w-24 bg-gray-200 rounded-lg animate-pulse" />
      </div>
    )
  }

  return (
    <div className="flex flex-wrap items-center gap-2 mb-4">
      {/* All watches tab */}
      <button
        onClick={() => onSelectCollection(null)}
        className={`px-4 py-2 rounded-lg font-medium transition ${
          selectedCollection === null
            ? 'bg-primary-600 text-white'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        }`}
      >
        Alle ({totalWatchCount})
      </button>

      {/* Collection tabs */}
      {collections?.map((collection) => (
        <div key={collection.id} className="relative group">
          {editingId === collection.id ? (
            <div className="flex items-center gap-1">
              <input
                type="text"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') handleSaveEdit()
                  if (e.key === 'Escape') setEditingId(null)
                }}
                className="px-3 py-2 border rounded-lg text-sm w-32"
                autoFocus
              />
              <button
                onClick={handleSaveEdit}
                disabled={updateCollection.isPending}
                className="p-2 text-green-600 hover:bg-green-50 rounded"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </button>
              <button
                onClick={() => setEditingId(null)}
                className="p-2 text-gray-500 hover:bg-gray-100 rounded"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          ) : (
            <>
              <button
                onClick={() => onSelectCollection(collection.id)}
                className={`px-4 py-2 rounded-lg font-medium transition pr-8 ${
                  selectedCollection === collection.id
                    ? 'bg-primary-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                {collection.name} ({collection.watchCount})
              </button>

              {/* Edit/delete dropdown on hover */}
              <div className="absolute right-1 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition">
                <div className="relative">
                  <button
                    onClick={(e) => {
                      e.stopPropagation()
                      const menu = e.currentTarget.nextElementSibling
                      menu?.classList.toggle('hidden')
                    }}
                    className={`p-1 rounded hover:bg-black/10 ${
                      selectedCollection === collection.id ? 'text-white/80' : 'text-gray-500'
                    }`}
                  >
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                    </svg>
                  </button>
                  <div className="hidden absolute right-0 top-full mt-1 bg-white border rounded-lg shadow-lg py-1 z-10 min-w-[160px]">
                    <button
                      onClick={(e) => {
                        e.stopPropagation()
                        handleStartEdit(collection)
                      }}
                      className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                    >
                      Bewerken
                    </button>
                    <button
                      onClick={(e) => {
                        e.stopPropagation()
                        updateCollection.mutate({
                          id: collection.id,
                          data: { isPublic: !collection.isPublic }
                        })
                        e.currentTarget.closest('.hidden')?.classList.add('hidden')
                      }}
                      className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                    >
                      {collection.isPublic ? (
                        <>
                          <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          Openbaar
                        </>
                      ) : (
                        <>
                          <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                          </svg>
                          Privé
                        </>
                      )}
                    </button>
                    {collection.isPublic && collection.shareUrl && (
                      <button
                        onClick={(e) => {
                          e.stopPropagation()
                          navigator.clipboard.writeText(window.location.origin + collection.shareUrl)
                          e.currentTarget.closest('.hidden')?.classList.add('hidden')
                        }}
                        className="w-full px-3 py-2 text-left text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2"
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Kopieer link
                      </button>
                    )}
                    <hr className="my-1" />
                    <button
                      onClick={(e) => {
                        e.stopPropagation()
                        setDeleteConfirmId(collection.id)
                      }}
                      className="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                    >
                      Verwijderen
                    </button>
                  </div>
                </div>
              </div>
            </>
          )}
        </div>
      ))}

      {/* Add new collection button */}
      <button
        onClick={onCreateNew}
        className="px-3 py-2 border-2 border-dashed border-gray-300 text-gray-500 rounded-lg hover:border-primary-400 hover:text-primary-600 transition"
        title="Nieuwe collectie"
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
        </svg>
      </button>

      <ConfirmModal
        isOpen={deleteConfirmId !== null}
        title="Collectie verwijderen"
        message={`Weet je zeker dat je "${collectionToDelete?.name}" wilt verwijderen? De watches in deze collectie blijven bestaan.`}
        confirmLabel="Verwijderen"
        cancelLabel="Annuleren"
        variant="danger"
        onConfirm={handleConfirmDelete}
        onCancel={() => setDeleteConfirmId(null)}
      />
    </div>
  )
}
