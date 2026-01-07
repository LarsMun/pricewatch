import { useState } from 'react'
import { useCollections, useDeleteCollection, useUpdateCollection, useAddWatchToCollection } from '../hooks/useCollections'
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
  const [dragOverId, setDragOverId] = useState<number | null>(null)
  const updateCollection = useUpdateCollection()
  const deleteCollection = useDeleteCollection()
  const addWatchToCollection = useAddWatchToCollection()

  const handleDragOver = (e: React.DragEvent, collectionId: number) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'copy'
    setDragOverId(collectionId)
  }

  const handleDragLeave = () => {
    setDragOverId(null)
  }

  const handleDrop = (e: React.DragEvent, collectionId: number) => {
    e.preventDefault()
    setDragOverId(null)
    const watchId = parseInt(e.dataTransfer.getData('watchId'), 10)
    if (watchId && collectionId) {
      addWatchToCollection.mutate({ collectionId, watchId })
    }
  }

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

  const handleDelete = (id: number) => {
    if (confirm('Weet je zeker dat je deze collectie wilt verwijderen? De watches blijven bestaan.')) {
      deleteCollection.mutate(id, {
        onSuccess: () => {
          if (selectedCollection === id) {
            onSelectCollection(null)
          }
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
                onDragOver={(e) => handleDragOver(e, collection.id)}
                onDragLeave={handleDragLeave}
                onDrop={(e) => handleDrop(e, collection.id)}
                className={`px-4 py-2 rounded-lg font-medium transition pr-8 ${
                  dragOverId === collection.id
                    ? 'bg-primary-400 text-white ring-2 ring-primary-600 ring-offset-2'
                    : selectedCollection === collection.id
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
                  <div className="hidden absolute right-0 top-full mt-1 bg-white border rounded-lg shadow-lg py-1 z-10 min-w-[120px]">
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
                        handleDelete(collection.id)
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
    </div>
  )
}
