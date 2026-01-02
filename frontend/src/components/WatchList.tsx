import { Link } from 'react-router-dom'
import { useWatches, useDeleteWatch, useToggleWatch } from '../hooks/useWatches'
import type { ProductWatch } from '../types'

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

function WatchCard({ watch }: { watch: ProductWatch }) {
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
            className="text-lg font-semibold text-gray-900 hover:text-primary-600 line-clamp-2"
          >
            {watch.productName || watch.domain}
          </Link>
          <WatchStatusBadge watch={watch} />
        </div>
      </div>

      {/* Full-width image */}
      <Link to={`/watch/${watch.id}`} className="block">
        {watch.imageUrl ? (
          <img
            src={watch.imageUrl}
            alt={watch.productName || 'Product'}
            className="w-full h-48 object-cover bg-gray-100"
            onError={(e) => {
              e.currentTarget.style.display = 'none'
              e.currentTarget.nextElementSibling?.classList.remove('hidden')
            }}
          />
        ) : null}
        <div className={`w-full h-48 bg-gray-100 flex items-center justify-center ${watch.imageUrl ? 'hidden' : ''}`}>
          <svg className="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
      </Link>

      {/* Info below image */}
      <div className="p-4">
        {/* Domain */}
        <a
          href={watch.url}
          target="_blank"
          rel="noopener noreferrer"
          className="text-sm text-gray-500 hover:text-primary-600 flex items-center gap-1 mb-3"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          {watch.domain}
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

export default function WatchList() {
  const { data: watches, isLoading, error } = useWatches()

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

  return (
    <div className="grid gap-4 md:grid-cols-2">
      {watches.map((watch) => (
        <WatchCard key={watch.id} watch={watch} />
      ))}
    </div>
  )
}
