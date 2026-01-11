import { useState } from 'react'
import { Link } from 'react-router-dom'
import { usePublicFeed, usePopularDomains, type PublicProduct } from '../hooks/usePublicFeed'
import { useAuth } from '../contexts/AuthContext'
import SubscribeModal from '../components/SubscribeModal'

function ProductCard({
  product,
  onSubscribe,
}: {
  product: PublicProduct
  onSubscribe: (product: PublicProduct) => void
}) {
  const priceChange =
    product.previousPrice && product.currentPrice
      ? (
          ((parseFloat(product.currentPrice) - parseFloat(product.previousPrice)) /
            parseFloat(product.previousPrice)) *
          100
        ).toFixed(1)
      : null

  const isPriceDown = priceChange && parseFloat(priceChange) < 0

  return (
    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
      <Link to={`/product/${product.id}`} className="block">
        <div className="aspect-square bg-gray-100 relative">
          {product.imageUrl ? (
            <img
              src={product.imageUrl}
              alt={product.productName}
              className="w-full h-full object-contain p-4"
              loading="lazy"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-gray-400">
              <svg className="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={1}
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
          )}
          {priceChange && (
            <span
              className={`absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-semibold ${
                isPriceDown ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
              }`}
            >
              {isPriceDown ? '' : '+'}
              {priceChange}%
            </span>
          )}
        </div>
      </Link>

      <div className="p-4">
        <Link to={`/product/${product.id}`}>
          <h3 className="font-medium text-gray-900 line-clamp-2 hover:text-blue-600 min-h-[48px]">
            {product.productName}
          </h3>
        </Link>

        <p className="text-xs text-gray-500 mt-1">{product.domain}</p>

        <div className="mt-2 flex items-baseline gap-2">
          {product.currentPrice && (
            <span className="text-xl font-bold text-gray-900">&euro; {product.currentPrice}</span>
          )}
          {product.previousPrice && product.previousPrice !== product.currentPrice && (
            <span className="text-sm text-gray-400 line-through">
              &euro; {product.previousPrice}
            </span>
          )}
        </div>

        <div className="mt-3 flex items-center justify-between">
          <div className="flex items-center gap-1 text-xs text-gray-500">
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>
            {product.subscriberCount + 1}
          </div>

          <button
            onClick={() => onSubscribe(product)}
            className="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
          >
            Alert
          </button>
        </div>

        {product.username && (
          <Link
            to={`/u/${product.username}`}
            className="mt-2 block text-xs text-gray-400 hover:text-blue-600"
          >
            via @{product.username}
          </Link>
        )}
      </div>
    </div>
  )
}

export default function FeedPage() {
  const { user } = useAuth()
  const [page, setPage] = useState(1)
  const [selectedDomain, setSelectedDomain] = useState<string | undefined>()
  const [subscribeProduct, setSubscribeProduct] = useState<PublicProduct | null>(null)

  const { data: feedData, isLoading, error } = usePublicFeed(page, 24, selectedDomain)
  const { data: domainsData } = usePopularDomains()

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
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
              <>
                <Link to="/login" className="text-gray-600 hover:text-gray-900">
                  Inloggen
                </Link>
                <Link
                  to="/register"
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                  Registreren
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      {/* Hero */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-12">
        <div className="max-w-7xl mx-auto px-4 text-center">
          <h1 className="text-4xl font-bold mb-4">Ontdek producten. Volg prijzen.</h1>
          <p className="text-xl text-blue-100 max-w-2xl mx-auto">
            Bekijk wat anderen volgen en ontvang een melding wanneer de prijs daalt.
          </p>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Sidebar */}
          <aside className="lg:w-64 flex-shrink-0">
            <div className="bg-white rounded-lg border border-gray-200 p-4 sticky top-4">
              <h2 className="font-semibold text-gray-900 mb-3">Webshops</h2>
              <button
                onClick={() => setSelectedDomain(undefined)}
                className={`block w-full text-left px-3 py-2 rounded-lg text-sm ${
                  !selectedDomain ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'
                }`}
              >
                Alle webshops
              </button>
              {domainsData?.domains &&
                Object.entries(domainsData.domains)
                  .slice(0, 10)
                  .map(([domain, count]) => (
                    <button
                      key={domain}
                      onClick={() => setSelectedDomain(domain)}
                      className={`block w-full text-left px-3 py-2 rounded-lg text-sm ${
                        selectedDomain === domain
                          ? 'bg-blue-50 text-blue-700'
                          : 'text-gray-600 hover:bg-gray-50'
                      }`}
                    >
                      {domain}{' '}
                      <span className="text-gray-400">({count})</span>
                    </button>
                  ))}
            </div>
          </aside>

          {/* Main content */}
          <main className="flex-1">
            {isLoading ? (
              <div className="flex items-center justify-center py-12">
                <svg
                  className="animate-spin w-8 h-8 text-blue-600"
                  fill="none"
                  viewBox="0 0 24 24"
                >
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
            ) : error ? (
              <div className="text-center py-12">
                <p className="text-red-600">{error.message}</p>
              </div>
            ) : feedData?.products.length === 0 ? (
              <div className="text-center py-12">
                <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg
                    className="w-12 h-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={1}
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                  </svg>
                </div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">Nog geen producten</h3>
                <p className="text-gray-500 mb-4">
                  {selectedDomain
                    ? `Geen producten gevonden voor ${selectedDomain}`
                    : 'Wees de eerste om een product toe te voegen!'}
                </p>
                <Link
                  to="/register"
                  className="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                  Maak een account
                </Link>
              </div>
            ) : (
              <>
                <div className="mb-4 flex items-center justify-between">
                  <p className="text-sm text-gray-500">
                    {feedData?.totalCount} producten gevonden
                  </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {feedData?.products.map((product) => (
                    <ProductCard
                      key={product.id}
                      product={product}
                      onSubscribe={setSubscribeProduct}
                    />
                  ))}
                </div>

                {/* Pagination */}
                {feedData && feedData.totalPages > 1 && (
                  <div className="mt-8 flex justify-center gap-2">
                    <button
                      onClick={() => setPage((p) => Math.max(1, p - 1))}
                      disabled={page === 1}
                      className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Vorige
                    </button>
                    <span className="px-4 py-2 text-gray-600">
                      Pagina {page} van {feedData.totalPages}
                    </span>
                    <button
                      onClick={() => setPage((p) => Math.min(feedData.totalPages, p + 1))}
                      disabled={page === feedData.totalPages}
                      className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Volgende
                    </button>
                  </div>
                )}
              </>
            )}
          </main>
        </div>
      </div>

      {/* Subscribe Modal */}
      {subscribeProduct && (
        <SubscribeModal
          product={subscribeProduct}
          isOpen={!!subscribeProduct}
          onClose={() => setSubscribeProduct(null)}
        />
      )}
    </div>
  )
}
