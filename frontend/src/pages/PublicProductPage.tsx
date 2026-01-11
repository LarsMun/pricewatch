import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { usePublicProduct } from '../hooks/usePublicFeed'
import { useAuth } from '../contexts/AuthContext'
import SubscribeModal from '../components/SubscribeModal'

export default function PublicProductPage() {
  const { id } = useParams<{ id: string }>()
  const { user } = useAuth()
  const { data, isLoading, error } = usePublicProduct(Number(id))
  const [showSubscribe, setShowSubscribe] = useState(false)

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <svg className="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
          <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          />
          <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
      </div>
    )
  }

  if (error || !data?.product) {
    return (
      <div className="min-h-screen bg-gray-50">
        <header className="bg-white border-b border-gray-200">
          <div className="max-w-4xl mx-auto px-4 py-4">
            <Link to="/" className="text-2xl font-bold text-blue-600">
              ShopQ
            </Link>
          </div>
        </header>
        <div className="max-w-4xl mx-auto px-4 py-12 text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Product niet gevonden</h1>
          <p className="text-gray-600 mb-4">
            Dit product bestaat niet of is niet meer beschikbaar.
          </p>
          <Link to="/" className="text-blue-600 hover:underline">
            Terug naar overzicht
          </Link>
        </div>
      </div>
    )
  }

  const product = data.product
  const priceChange =
    product.previousPrice && product.currentPrice
      ? (
          ((parseFloat(product.currentPrice) - parseFloat(product.previousPrice)) /
            parseFloat(product.previousPrice)) *
          100
        ).toFixed(1)
      : null

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
          <Link to="/" className="text-2xl font-bold text-blue-600">
            ShopQ
          </Link>
          <div className="flex items-center gap-4">
            {user ? (
              <Link
                to="/dashboard"
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Dashboard
              </Link>
            ) : (
              <Link
                to="/register"
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Registreren
              </Link>
            )}
          </div>
        </div>
      </header>

      <div className="max-w-4xl mx-auto px-4 py-8">
        <Link to="/" className="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900 mb-6">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
          Terug naar overzicht
        </Link>

        <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
          <div className="md:flex">
            {/* Image */}
            <div className="md:w-1/2 bg-gray-100 p-8 flex items-center justify-center">
              {product.imageUrl ? (
                <img
                  src={product.imageUrl}
                  alt={product.productName}
                  className="max-w-full max-h-96 object-contain"
                />
              ) : (
                <div className="w-48 h-48 flex items-center justify-center text-gray-400">
                  <svg className="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={1}
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                </div>
              )}
            </div>

            {/* Details */}
            <div className="md:w-1/2 p-6">
              <p className="text-sm text-gray-500 mb-2">{product.domain}</p>
              <h1 className="text-2xl font-bold text-gray-900 mb-4">{product.productName}</h1>

              <div className="flex items-baseline gap-3 mb-4">
                {product.currentPrice && (
                  <span className="text-3xl font-bold text-gray-900">
                    &euro; {product.currentPrice}
                  </span>
                )}
                {product.previousPrice && product.previousPrice !== product.currentPrice && (
                  <span className="text-lg text-gray-400 line-through">
                    &euro; {product.previousPrice}
                  </span>
                )}
                {priceChange && (
                  <span
                    className={`px-2 py-1 rounded-full text-sm font-semibold ${
                      parseFloat(priceChange) < 0
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'
                    }`}
                  >
                    {parseFloat(priceChange) < 0 ? '' : '+'}
                    {priceChange}%
                  </span>
                )}
              </div>

              {product.originalPrice && product.originalPrice !== product.currentPrice && (
                <p className="text-sm text-gray-500 mb-4">
                  Oorspronkelijke prijs: &euro; {product.originalPrice}
                </p>
              )}

              <div className="flex items-center gap-4 text-sm text-gray-500 mb-6">
                <span className="flex items-center gap-1">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                  {product.watcherCount} volgers
                </span>
              </div>

              <div className="flex gap-3">
                <button
                  onClick={() => setShowSubscribe(true)}
                  className="flex-1 py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center gap-2"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                  </svg>
                  Prijsalert instellen
                </button>
                <a
                  href={product.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="py-3 px-4 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                    />
                  </svg>
                  Bekijk product
                </a>
              </div>

              {product.username && (
                <p className="mt-4 text-sm text-gray-500">
                  Toegevoegd door{' '}
                  <Link to={`/u/${product.username}`} className="text-blue-600 hover:underline">
                    @{product.username}
                  </Link>
                </p>
              )}
            </div>
          </div>

          {/* Price History */}
          {product.priceHistory && product.priceHistory.length > 0 && (
            <div className="border-t border-gray-200 p-6">
              <h2 className="font-semibold text-gray-900 mb-4">Prijsgeschiedenis</h2>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-gray-500">
                      <th className="pb-2">Datum</th>
                      <th className="pb-2 text-right">Prijs</th>
                    </tr>
                  </thead>
                  <tbody>
                    {product.priceHistory.slice(0, 10).map((check, i) => (
                      <tr key={i} className="border-t border-gray-100">
                        <td className="py-2">
                          {new Date(check.checkedAt).toLocaleDateString('nl-NL', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                          })}
                        </td>
                        <td className="py-2 text-right font-medium">&euro; {check.price}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Subscribe Modal */}
      <SubscribeModal
        product={product}
        isOpen={showSubscribe}
        onClose={() => setShowSubscribe(false)}
      />
    </div>
  )
}
