import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useWatches, useDeleteWatch, useToggleWatch } from '../hooks/useWatches'
import { useCollections, useAddWatchToCollection, useRemoveWatchFromCollection } from '../hooks/useCollections'
import ConfirmModal from './ConfirmModal'
import type { ProductWatch, Collection } from '../types'

// Floating action bar for bulk operations
interface SelectionBarProps {
  selectedIds: Set<number>
  watches: ProductWatch[]
  collections: Collection[]
  onClear: () => void
}

function SelectionBar({ selectedIds, watches, collections, onClear }: SelectionBarProps) {
  const [showCollections, setShowCollections] = useState(false)
  const addToCollection = useAddWatchToCollection()

  const selectedWatches = watches.filter(w => selectedIds.has(w.id))
  const count = selectedIds.size

  const handleAddToCollection = (collectionId: number) => {
    selectedIds.forEach(watchId => {
      addToCollection.mutate({ collectionId, watchId })
    })
    setShowCollections(false)
    onClear()
  }

  if (count === 0) return null

  return (
    <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 animate-slide-up">
      <div className="bg-gray-900 text-white rounded-2xl shadow-2xl px-4 py-3 flex items-center gap-4">
        {/* Selected count with mini avatars */}
        <div className="flex items-center gap-3">
          <div className="flex -space-x-2">
            {selectedWatches.slice(0, 3).map((watch, i) => (
              <img
                key={watch.id}
                src={watch.imageUrl || '/placeholder.svg'}
                alt=""
                className="w-8 h-8 rounded-full border-2 border-gray-900 object-cover bg-gray-700"
                style={{ zIndex: 3 - i }}
                onError={(e) => { e.currentTarget.src = '/placeholder.svg' }}
              />
            ))}
            {count > 3 && (
              <div className="w-8 h-8 rounded-full border-2 border-gray-900 bg-gray-700 flex items-center justify-center text-xs font-medium">
                +{count - 3}
              </div>
            )}
          </div>
          <span className="text-sm font-medium">
            {count} {count === 1 ? 'item' : 'items'}
          </span>
        </div>

        {/* Divider */}
        <div className="w-px h-8 bg-gray-700" />

        {/* Add to collection button */}
        <div className="relative">
          <button
            onClick={() => setShowCollections(!showCollections)}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-500 rounded-xl transition font-medium text-sm"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Collectie
          </button>

          {/* Collection dropdown */}
          {showCollections && (
            <>
              <div className="fixed inset-0" onClick={() => setShowCollections(false)} />
              <div className="absolute bottom-full left-0 mb-2 bg-white rounded-xl shadow-xl border py-2 min-w-[200px] max-h-64 overflow-y-auto">
                {collections.length === 0 ? (
                  <div className="px-4 py-3 text-gray-500 text-sm">
                    Geen collecties gevonden
                  </div>
                ) : (
                  collections.map(collection => (
                    <button
                      key={collection.id}
                      onClick={() => handleAddToCollection(collection.id)}
                      disabled={addToCollection.isPending}
                      className="w-full px-4 py-2 text-left text-gray-900 hover:bg-gray-50 text-sm flex items-center justify-between disabled:opacity-50"
                    >
                      <span>{collection.name}</span>
                      <span className="text-gray-500 text-xs">{collection.watchCount}</span>
                    </button>
                  ))
                )}
              </div>
            </>
          )}
        </div>

        {/* Clear selection button */}
        <button
          onClick={onClear}
          className="p-2 hover:bg-gray-800 rounded-lg transition"
          title="Selectie wissen"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  )
}

function formatPrice(price: string | null, currency: string): string {
  if (!price) return '-'
  const num = parseFloat(price)
  return new Intl.NumberFormat('nl-NL', {
    style: 'currency',
    currency,
  }).format(num)
}

function formatDate(date: string | null): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-NL', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function WatchStatusBadge({ watch }: { watch: ProductWatch }) {
  if (!watch.isActive) {
    return (
      <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
        Gepauzeerd
      </span>
    )
  }
  if (watch.consecutiveFailures >= 5) {
    return (
      <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">
        Fout
      </span>
    )
  }
  if (watch.consecutiveFailures > 0) {
    return (
      <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">
        {watch.consecutiveFailures}x mislukt
      </span>
    )
  }
  return (
    <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">
      Actief
    </span>
  )
}

interface CollectionDropdownProps {
  watch: ProductWatch
  collections: Collection[]
  watchCollectionIds: number[]
}

function CollectionDropdown({ watch, collections, watchCollectionIds }: CollectionDropdownProps) {
  const [isOpen, setIsOpen] = useState(false)
  const addToCollection = useAddWatchToCollection()
  const removeFromCollection = useRemoveWatchFromCollection()

  const handleToggleCollection = (collectionId: number, isInCollection: boolean) => {
    if (isInCollection) {
      removeFromCollection.mutate({ collectionId, watchId: watch.id })
    } else {
      addToCollection.mutate({ collectionId, watchId: watch.id })
    }
  }

  if (collections.length === 0) return null

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition"
        title="Collecties beheren"
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
      </button>

      {isOpen && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setIsOpen(false)} />
          <div className="absolute right-0 top-full mt-1 bg-white border rounded-lg shadow-lg py-1 z-20 min-w-[180px]">
            <div className="px-3 py-2 text-xs font-medium text-gray-500 border-b">
              Collecties
            </div>
            {collections.map((collection) => {
              const isInCollection = watchCollectionIds.includes(collection.id)
              return (
                <button
                  key={collection.id}
                  onClick={() => handleToggleCollection(collection.id, isInCollection)}
                  disabled={addToCollection.isPending || removeFromCollection.isPending}
                  className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center justify-between gap-2 disabled:opacity-50"
                >
                  <span className="truncate">{collection.name}</span>
                  {isInCollection && (
                    <svg className="w-4 h-4 text-primary-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  )}
                </button>
              )
            })}
          </div>
        </>
      )}
    </div>
  )
}

interface WatchCardProps {
  watch: ProductWatch
  collections: Collection[]
  isSelected: boolean
  onToggleSelect: (id: number) => void
  hasSelection: boolean
}

function WatchCard({ watch, collections, isSelected, onToggleSelect, hasSelection }: WatchCardProps) {
  const deleteWatch = useDeleteWatch()
  const toggleWatch = useToggleWatch()
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)

  const handleDelete = () => {
    deleteWatch.mutate(watch.id)
    setShowDeleteConfirm(false)
  }

  const handleToggle = () => {
    toggleWatch.mutate({ id: watch.id, isActive: !watch.isActive })
  }

  const priceChange = watch.currentPrice && watch.originalPrice
    ? parseFloat(watch.currentPrice) - parseFloat(watch.originalPrice)
    : null

  const priceChangePercent = priceChange && watch.originalPrice
    ? (priceChange / parseFloat(watch.originalPrice)) * 100
    : null

  return (
    <div
      className={`bg-white rounded-lg shadow overflow-hidden hover:shadow-md transition relative group ${
        isSelected ? 'ring-2 ring-primary-500 ring-offset-2' : ''
      }`}
    >
      {/* Selection checkbox - visible on hover or when there's any selection */}
      <div
        className={`absolute top-3 left-3 z-10 transition-opacity ${
          hasSelection || isSelected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'
        }`}
      >
        <button
          onClick={(e) => {
            e.preventDefault()
            onToggleSelect(watch.id)
          }}
          className={`w-6 h-6 rounded-full border-2 flex items-center justify-center transition ${
            isSelected
              ? 'bg-primary-600 border-primary-600 text-white'
              : 'bg-white/90 border-gray-300 hover:border-primary-400'
          }`}
        >
          {isSelected && (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
            </svg>
          )}
        </button>
      </div>
      {/* Title header */}
      <div className="p-4 pb-3 pl-12">
        <div className="flex justify-between items-start gap-2">
          <Link
            to={`/watch/${watch.id}`}
            className="text-lg font-semibold text-gray-900 hover:text-primary-600 line-clamp-2 flex-1"
          >
            {watch.productName || watch.domain}
          </Link>
          <div className="flex items-center gap-1 flex-shrink-0">
            <CollectionDropdown
              watch={watch}
              collections={collections}
              watchCollectionIds={watch.collectionIds}
            />
            <WatchStatusBadge watch={watch} />
          </div>
        </div>
      </div>

      {/* Full-width image with source attribution */}
      <Link to={`/watch/${watch.id}`} className="block relative">
        <img
          src={watch.imageUrl || '/placeholder.svg'}
          alt={watch.productName || 'Product'}
          className="w-full h-48 object-cover bg-gray-100"
          loading="lazy"
          onError={(e) => {
            e.currentTarget.src = '/placeholder.svg'
          }}
        />
        <span className="absolute bottom-2 right-2 text-xs text-white bg-black/50 px-2 py-1 rounded">
          via {watch.domain}
        </span>
      </Link>

      {/* Info below image */}
      <div className="p-4">
        {/* View on shop link */}
        <a
          href={watch.url}
          target="_blank"
          rel="noopener noreferrer"
          className="text-sm text-primary-600 hover:text-primary-700 hover:underline inline-flex items-center gap-1 mb-3"
        >
          {watch.domain}
          <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
        </a>

        {/* Price info */}
        <div className="flex items-baseline gap-3 mb-3">
          <span className="text-2xl font-bold text-gray-900">
            {formatPrice(watch.currentPrice, watch.currency)}
          </span>
          {priceChange !== null && priceChange !== 0 && (
            <span className={`text-sm font-medium ${priceChange < 0 ? 'text-green-600' : 'text-red-600'}`}>
              {priceChange < 0 ? '↓' : '↑'} {formatPrice(Math.abs(priceChange).toString(), watch.currency)}
              {priceChangePercent !== null && (
                <span className="ml-1">({priceChangePercent > 0 ? '+' : ''}{priceChangePercent.toFixed(1)}%)</span>
              )}
            </span>
          )}
        </div>

        {/* Original price */}
        {watch.originalPrice && watch.originalPrice !== watch.currentPrice && (
          <div className="text-sm text-gray-500 mb-3">
            Startprijs: {formatPrice(watch.originalPrice, watch.currency)}
          </div>
        )}

        {/* Meta info */}
        <div className="flex justify-between items-center text-xs text-gray-500 border-t pt-3">
          <span>Gecheckt: {formatDate(watch.lastCheckedAt)}</span>
          <div className="flex gap-1">
            <button
              onClick={handleToggle}
              disabled={toggleWatch.isPending}
              className="px-2 py-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded transition"
            >
              {watch.isActive ? 'Pauze' : 'Hervat'}
            </button>
            <button
              onClick={() => setShowDeleteConfirm(true)}
              disabled={deleteWatch.isPending}
              className="px-2 py-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition"
            >
              Wis
            </button>
          </div>
        </div>
      </div>

      <ConfirmModal
        isOpen={showDeleteConfirm}
        title="Watch verwijderen"
        message={`Weet je zeker dat je "${watch.productName || watch.domain}" wilt verwijderen? Dit kan niet ongedaan worden gemaakt.`}
        confirmLabel="Verwijderen"
        cancelLabel="Annuleren"
        variant="danger"
        onConfirm={handleDelete}
        onCancel={() => setShowDeleteConfirm(false)}
      />
    </div>
  )
}

interface EmptyStateProps {
  onAddWatch?: () => void
}

function EmptyState({ onAddWatch }: EmptyStateProps) {
  return (
    <div className="text-center py-12 px-4">
      {/* Illustration */}
      <div className="mb-6">
        <svg className="mx-auto w-32 h-32 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 100 100">
          <rect x="15" y="20" width="70" height="55" rx="4" strokeWidth="2" className="text-primary-300" />
          <circle cx="50" cy="42" r="12" strokeWidth="2" className="text-primary-400" />
          <path d="M50 38v8M46 42h8" strokeWidth="2" strokeLinecap="round" className="text-primary-500" />
          <path d="M25 58h50" strokeWidth="2" strokeLinecap="round" className="text-primary-300" />
          <path d="M25 64h30" strokeWidth="2" strokeLinecap="round" className="text-primary-200" />
          <path d="M72 12l6 8-6 8" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-green-400" />
          <path d="M22 12l-6 8 6 8" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-400" />
        </svg>
      </div>

      {/* Heading */}
      <h3 className="text-xl font-semibold text-gray-900 mb-2">
        Begin met prijzen volgen
      </h3>

      {/* Description */}
      <p className="text-gray-600 max-w-md mx-auto mb-6">
        Voeg producten toe die je wilt volgen. ShopQ controleert automatisch de prijs
        en stuurt je een melding zodra de prijs daalt.
      </p>

      {/* Primary CTA */}
      <div className="flex flex-col sm:flex-row gap-3 justify-center items-center">
        <button
          onClick={onAddWatch}
          className="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition shadow-sm"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Eerste product toevoegen
        </button>

        <span className="text-gray-400">of</span>

        <Link
          to="/bookmarklet"
          className="inline-flex items-center gap-2 px-4 py-2 text-primary-600 hover:text-primary-700 font-medium"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
          </svg>
          Gebruik de bookmarklet
        </Link>
      </div>

      {/* Tip */}
      <p className="mt-8 text-sm text-gray-500">
        💡 Tip: Met de bookmarklet voeg je producten toe met één klik vanuit elke webshop.
      </p>
    </div>
  )
}

interface WatchListProps {
  selectedCollectionId?: number | null
  onAddWatch?: () => void
}

export default function WatchList({ selectedCollectionId, onAddWatch }: WatchListProps) {
  const { data: watches, isLoading: watchesLoading, error: watchesError } = useWatches()
  const { data: collections, isLoading: collectionsLoading } = useCollections()
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set())

  const isLoading = watchesLoading || collectionsLoading
  const error = watchesError

  const handleToggleSelect = (id: number) => {
    setSelectedIds(prev => {
      const next = new Set(prev)
      if (next.has(id)) {
        next.delete(id)
      } else {
        next.add(id)
      }
      return next
    })
  }

  const handleClearSelection = () => {
    setSelectedIds(new Set())
  }

  if (isLoading) {
    return (
      <div className="text-center py-8 text-gray-500">
        Laden...
      </div>
    )
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
        Fout bij laden: {error.message}
      </div>
    )
  }

  if (!watches || watches.length === 0) {
    return <EmptyState onAddWatch={onAddWatch} />
  }

  // Filter watches by selected collection using collectionIds from the watch
  let filteredWatches = watches
  if (selectedCollectionId) {
    filteredWatches = watches.filter((w) => w.collectionIds.includes(selectedCollectionId))
  }

  if (filteredWatches.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        Geen watches in deze collectie.
      </div>
    )
  }

  const hasSelection = selectedIds.size > 0

  return (
    <>
      <div className="grid gap-4 md:grid-cols-2">
        {filteredWatches.map((watch) => (
          <WatchCard
            key={watch.id}
            watch={watch}
            collections={collections || []}
            isSelected={selectedIds.has(watch.id)}
            onToggleSelect={handleToggleSelect}
            hasSelection={hasSelection}
          />
        ))}
      </div>

      <SelectionBar
        selectedIds={selectedIds}
        watches={watches || []}
        collections={collections || []}
        onClear={handleClearSelection}
      />
    </>
  )
}
