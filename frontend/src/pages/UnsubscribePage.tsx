import { useEffect, useState } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { useUnsubscribe } from '../hooks/usePublicFeed'

export default function UnsubscribePage() {
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token')
  const unsubscribeMutation = useUnsubscribe()
  const [unsubscribed, setUnsubscribed] = useState(false)

  useEffect(() => {
    if (token && !unsubscribed && !unsubscribeMutation.isPending && !unsubscribeMutation.isError) {
      unsubscribeMutation.mutate(token, {
        onSuccess: () => {
          setUnsubscribed(true)
        },
      })
    }
  }, [token])

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-4xl mx-auto px-4 py-4">
          <Link to="/" className="text-2xl font-bold text-blue-600">
            ShopQ
          </Link>
        </div>
      </header>

      <div className="max-w-md mx-auto px-4 py-16 text-center">
        {!token ? (
          <>
            <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-red-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Ongeldige link</h1>
            <p className="text-gray-600 mb-4">
              Deze uitschrijflink is ongeldig. Controleer of je de juiste link hebt geopend.
            </p>
            <Link to="/" className="text-blue-600 hover:underline">
              Terug naar ShopQ
            </Link>
          </>
        ) : unsubscribeMutation.isPending ? (
          <>
            <svg
              className="animate-spin w-12 h-12 text-blue-600 mx-auto mb-4"
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
            <h1 className="text-2xl font-bold text-gray-900">Bezig met uitschrijven...</h1>
          </>
        ) : unsubscribeMutation.isError ? (
          <>
            <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-red-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Uitschrijven mislukt</h1>
            <p className="text-gray-600 mb-4">
              {unsubscribeMutation.error?.message ||
                'De link is ongeldig of al gebruikt.'}
            </p>
            <Link to="/" className="text-blue-600 hover:underline">
              Terug naar ShopQ
            </Link>
          </>
        ) : unsubscribed ? (
          <>
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-green-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Uitgeschreven</h1>
            <p className="text-gray-600 mb-4">
              Je ontvangt geen emails meer voor dit product.
            </p>
            <Link
              to="/"
              className="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Terug naar ShopQ
            </Link>
          </>
        ) : null}
      </div>
    </div>
  )
}
