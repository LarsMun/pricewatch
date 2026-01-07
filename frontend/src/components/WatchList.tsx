import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useWatches, useDeleteWatch, useToggleWatch } from '../hooks/useWatches'
import { useCollections, useAddWatchToCollection, useRemoveWatchFromCollection } from '../hooks/useCollections'
import type { ProductWatch, Collection } from '../types'

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
}

function WatchCard({ watch, collections }: WatchCardProps) {
  const deleteWatch = useDeleteWatch()
  const toggleWatch = useToggleWatch()

  const handleDelete = () => {
    if (confirm('Weet je zeker dat je deze watch wilt verwijderen?')) {
      deleteWatch.mutate(watch.id)
    }
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
    <div className="bg-white rounded-lg shadow overflow-hidden hover:shadow-md transition">
      {/* Title header */}
      <div className="p-4 pb-3">
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
        {/* View on shop button */}
        <a
          href={watch.url}
          target="_blank"
          rel="noopener noreferrer"
          className="w-full mb-3 inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm font-medium"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Bekijk op {watch.domain}
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
        <div className="flex justify-between items-center text-xs text-gray-400 border-t pt-3">
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
              onClick={handleDelete}
              disabled={deleteWatch.isPending}
              className="px-2 py-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition"
            >
              Wis
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

interface WatchListProps {
  selectedCollectionId?: number | null
}

export default function WatchList({ selectedCollectionId }: WatchListProps) {
  const { data: watches, isLoading: watchesLoading, error: watchesError } = useWatches()
  const { data: collections, isLoading: collectionsLoading } = useCollections()

  const isLoading = watchesLoading || collectionsLoading
  const error = watchesError

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
    return (
      <div className="text-center py-8 text-gray-500">
        Je hebt nog geen prijswatches.
      </div>
    )
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

  return (
    <div className="grid gap-4 md:grid-cols-2">
      {filteredWatches.map((watch) => (
        <WatchCard
          key={watch.id}
          watch={watch}
          collections={collections || []}
        />
      ))}
    </div>
  )
}
