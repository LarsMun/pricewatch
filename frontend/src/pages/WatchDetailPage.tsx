import { useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { useWatch, useDeleteWatch, useToggleWatch } from '../hooks/useWatches'
import ConfirmModal from '../components/ConfirmModal'

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
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatShortDate(date: string): string {
  return new Date(date).toLocaleDateString('nl-NL', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export default function WatchDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const watchId = parseInt(id || '0', 10)
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)

  const { data: watch, isLoading, error } = useWatch(watchId)
  const deleteWatch = useDeleteWatch()
  const toggleWatch = useToggleWatch()

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-gray-500">Laden...</div>
      </div>
    )
  }

  if (error || !watch) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-4xl mx-auto">
          <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
            {error?.message || 'Watch niet gevonden'}
          </div>
          <Link to="/dashboard" className="text-primary-600 hover:underline mt-4 inline-block">
            Terug naar dashboard
          </Link>
        </div>
      </div>
    )
  }

  const handleDelete = async () => {
    await deleteWatch.mutateAsync(watch.id)
    navigate('/dashboard')
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
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-4xl mx-auto px-4 py-6">
          <Link to="/dashboard" className="text-primary-600 hover:underline text-sm mb-2 inline-block">
            &larr; Terug naar dashboard
          </Link>
          <div className="flex justify-between items-start">
            <div className="flex gap-4">
              {/* Product image with source attribution */}
              <div className="flex-shrink-0">
                <img
                  src={watch.imageUrl || '/placeholder.svg'}
                  alt={watch.productName || 'Product'}
                  className="w-24 h-24 object-cover rounded-lg bg-gray-100"
                  loading="lazy"
                  onError={(e) => {
                    e.currentTarget.src = '/placeholder.svg'
                  }}
                />
                <span className="text-xs text-gray-500 block mt-1 text-center">
                  via {watch.domain}
                </span>
              </div>
              <div>
                <h1 className="text-2xl font-bold text-gray-900">
                  {watch.productName || watch.domain}
                </h1>
                <a
                  href={watch.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-gray-500 hover:text-primary-600 text-sm block mb-2"
                >
                  {watch.url}
                </a>
                <a
                  href={watch.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm font-medium"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                  Bekijk op {watch.domain}
                </a>
              </div>
            </div>
            <div className="flex gap-2">
              <button
                onClick={handleToggle}
                disabled={toggleWatch.isPending}
                className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
              >
                {watch.isActive ? 'Pauzeren' : 'Hervatten'}
              </button>
              <button
                onClick={() => setShowDeleteConfirm(true)}
                disabled={deleteWatch.isPending}
                className="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition"
              >
                Verwijderen
              </button>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-8 space-y-6">
        {/* Price summary */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">Prijsoverzicht</h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
              <span className="text-gray-500 text-sm block">Huidige prijs</span>
              <span className="text-2xl font-bold text-gray-900">
                {formatPrice(watch.currentPrice, watch.currency)}
              </span>
            </div>
            <div>
              <span className="text-gray-500 text-sm block">Originele prijs</span>
              <span className="text-xl text-gray-700">
                {formatPrice(watch.originalPrice, watch.currency)}
              </span>
            </div>
            <div>
              <span className="text-gray-500 text-sm block">Verschil</span>
              {priceChange !== null ? (
                <span className={`text-xl font-medium ${priceChange < 0 ? 'text-green-600' : priceChange > 0 ? 'text-red-600' : 'text-gray-600'}`}>
                  {priceChange < 0 ? '' : '+'}{formatPrice(priceChange.toString(), watch.currency)}
                  {priceChangePercent !== null && (
                    <span className="text-sm ml-1">
                      ({priceChangePercent > 0 ? '+' : ''}{priceChangePercent.toFixed(1)}%)
                    </span>
                  )}
                </span>
              ) : (
                <span className="text-gray-500 text-xl">-</span>
              )}
            </div>
            <div>
              <span className="text-gray-500 text-sm block">Status</span>
              {!watch.isActive ? (
                <span className="text-gray-600">Gepauzeerd</span>
              ) : watch.consecutiveFailures >= 5 ? (
                <span className="text-red-600">Fout - site onbereikbaar</span>
              ) : watch.consecutiveFailures > 0 ? (
                <span className="text-yellow-600">{watch.consecutiveFailures}x mislukt</span>
              ) : (
                <span className="text-green-600">Actief</span>
              )}
            </div>
          </div>
        </div>

        {/* Details */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">Details</h2>
          <dl className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <dt className="text-gray-500">CSS Selector</dt>
              <dd className="font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded mt-1">
                {watch.priceSelector}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">Check methode</dt>
              <dd className="text-gray-900 mt-1">{watch.checkMethod}</dd>
            </div>
            <div>
              <dt className="text-gray-500">Laatst gecheckt</dt>
              <dd className="text-gray-900 mt-1">{formatDate(watch.lastCheckedAt)}</dd>
            </div>
            <div>
              <dt className="text-gray-500">Volgende check</dt>
              <dd className="text-gray-900 mt-1">{formatDate(watch.nextCheckAt)}</dd>
            </div>
            <div>
              <dt className="text-gray-500">Aangemaakt</dt>
              <dd className="text-gray-900 mt-1">{formatDate(watch.createdAt)}</dd>
            </div>
            <div>
              <dt className="text-gray-500">Laatst gevonden tekst</dt>
              <dd className="font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded mt-1 truncate">
                {watch.lastSeenRawText || '-'}
              </dd>
            </div>
          </dl>
        </div>

        {/* Price history */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">Prijshistorie</h2>
          {watch.priceChecks && watch.priceChecks.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left text-gray-500 border-b">
                    <th className="pb-2">Datum</th>
                    <th className="pb-2">Prijs</th>
                    <th className="pb-2">Ruwe tekst</th>
                    <th className="pb-2">Status</th>
                    <th className="pb-2">Duur</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {watch.priceChecks.map((check) => (
                    <tr key={check.id} className={!check.wasSuccessful ? 'bg-red-50' : ''}>
                      <td className="py-2">{formatShortDate(check.checkedAt)}</td>
                      <td className="py-2 font-medium">
                        {check.wasSuccessful
                          ? formatPrice(check.price, watch.currency)
                          : '-'}
                      </td>
                      <td className="py-2 font-mono text-xs text-gray-600 max-w-xs truncate">
                        {check.rawText || '-'}
                      </td>
                      <td className="py-2">
                        {check.wasSuccessful ? (
                          <span className="text-green-600">OK</span>
                        ) : (
                          <span className="text-red-600" title={check.errorMessage || ''}>
                            Fout
                          </span>
                        )}
                      </td>
                      <td className="py-2 text-gray-500">
                        {check.durationMs ? `${check.durationMs}ms` : '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p className="text-gray-500">Nog geen prijschecks uitgevoerd.</p>
          )}
        </div>

        {/* Disclaimer */}
        <div className="bg-gray-100 rounded-lg p-4 text-sm text-gray-600">
          <p>
            Dit product wordt verkocht door <strong>{watch.domain}</strong>.
            ShopQ is alleen een prijsmonitor en verkoopt zelf geen producten.
            Controleer altijd de actuele prijs op de webshop voordat je een aankoop doet.
          </p>
        </div>
      </main>

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
