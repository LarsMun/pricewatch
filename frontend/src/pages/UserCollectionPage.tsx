import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useUserCollection, type PublicProduct } from '../hooks/usePublicFeed'
import { useAuth } from '../contexts/AuthContext'
import SubscribeModal from '../components/SubscribeModal'
import { SEO } from '../components/SEO'

export default function UserCollectionPage() {
  const { username, slug } = useParams<{ username: string; slug: string }>()
  const { user } = useAuth()
  const { data, isLoading, error } = useUserCollection(username || '', slug || '')
  const [subscribeProduct, setSubscribeProduct] = useState<PublicProduct | null>(null)

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

  if (error || !data) {
    return (
      <div className="min-h-screen bg-gray-50">
        <header className="bg-white border-b border-gray-200">
          <div className="max-w-6xl mx-auto px-4 py-4">
            <Link to="/" className="text-2xl font-bold text-blue-600">
              ShopQ
            </Link>
          </div>
        </header>
        <div className="max-w-6xl mx-auto px-4 py-12 text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Collectie niet gevonden</h1>
          <p className="text-gray-600 mb-4">
            Deze collectie bestaat niet of is niet publiek.
          </p>
          <Link to="/" className="text-blue-600 hover:underline">
            Terug naar overzicht
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <SEO
        title={`${data.collection.name} - @${data.username}`}
        description={data.collection.description || `Bekijk de collectie "${data.collection.name}" van @${data.username} met ${data.productCount} producten.`}
        canonicalUrl={`https://shopq.nl/u/${data.username}/${data.collection.slug}`}
      />

      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
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

      <div className="max-w-6xl mx-auto px-4 py-8">
        <Link
          to={`/u/${data.username}`}
          className="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900 mb-6"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
          Terug naar @{data.username}
        </Link>

        {/* Collection Header */}
        <div className="bg-white rounded-lg border border-gray-200 p-6 mb-8">
          <div className="flex items-center gap-3 mb-2">
            <h1 className="text-2xl font-bold text-gray-900">{data.collection.name}</h1>
            <span className="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">
              {data.productCount} {data.productCount === 1 ? 'product' : 'producten'}
            </span>
          </div>
          {data.collection.description && (
            <p className="text-gray-600">{data.collection.description}</p>
          )}
          <p className="text-sm text-gray-500 mt-2">
            Door{' '}
            <Link to={`/u/${data.username}`} className="text-blue-600 hover:underline">
              @{data.username}
            </Link>
          </p>
        </div>

        {/* Products */}
        {data.products.length === 0 ? (
          <div className="bg-white rounded-lg border border-gray-200 p-8 text-center">
            <p className="text-gray-500">Deze collectie bevat nog geen publieke producten.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {data.products.map((product) => (
              <div
                key={product.id}
                className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow"
              >
                <Link to={`/product/${product.id}`} className="block">
                  <div className="aspect-square bg-gray-100">
                    {product.imageUrl ? (
                      <img
                        src={product.imageUrl}
                        alt={product.productName}
                        className="w-full h-full object-contain p-4"
                        loading="lazy"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-gray-400">
                        <svg
                          className="w-16 h-16"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
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
                </Link>

                <div className="p-4">
                  <Link to={`/product/${product.id}`}>
                    <h3 className="font-medium text-gray-900 line-clamp-2 hover:text-blue-600">
                      {product.productName}
                    </h3>
                  </Link>
                  <p className="text-xs text-gray-500 mt-1">{product.domain}</p>

                  {product.currentPrice && (
                    <p className="text-lg font-bold text-gray-900 mt-2">
                      &euro; {product.currentPrice}
                    </p>
                  )}

                  <button
                    onClick={() => setSubscribeProduct(product)}
                    className="mt-3 w-full py-2 px-4 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700"
                  >
                    Prijsalert instellen
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
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
