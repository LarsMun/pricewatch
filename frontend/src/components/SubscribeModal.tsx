import { useState } from 'react'
import { useSubscribe, type PublicProduct } from '../hooks/usePublicFeed'

interface SubscribeModalProps {
  product: PublicProduct
  isOpen: boolean
  onClose: () => void
}

export default function SubscribeModal({ product, isOpen, onClose }: SubscribeModalProps) {
  const [email, setEmail] = useState('')
  const [success, setSuccess] = useState(false)
  const subscribe = useSubscribe()

  if (!isOpen) return null

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await subscribe.mutateAsync({ email, productId: product.id })
      setSuccess(true)
    } catch {
      // Error is handled by mutation
    }
  }

  const handleClose = () => {
    setEmail('')
    setSuccess(false)
    subscribe.reset()
    onClose()
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-md w-full p-6 relative">
        <button
          onClick={handleClose}
          className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        {success ? (
          <div className="text-center py-4">
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
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Check je inbox!</h3>
            <p className="text-gray-600 mb-4">
              We hebben een bevestigingsmail gestuurd naar <strong>{email}</strong>.
            </p>
            <p className="text-sm text-gray-500">
              Klik op de link in de email om je prijsalert te activeren.
            </p>
            <button
              onClick={handleClose}
              className="mt-6 w-full py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Sluiten
            </button>
          </div>
        ) : (
          <>
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Prijsalert instellen</h3>

            <div className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg mb-4">
              {product.imageUrl && (
                <img
                  src={product.imageUrl}
                  alt={product.productName}
                  className="w-16 h-16 object-contain rounded"
                />
              )}
              <div className="flex-1 min-w-0">
                <p className="font-medium text-gray-900 truncate">{product.productName}</p>
                <p className="text-sm text-gray-500">{product.domain}</p>
                {product.currentPrice && (
                  <p className="text-lg font-bold text-green-600 mt-1">
                    &euro; {product.currentPrice}
                  </p>
                )}
              </div>
            </div>

            <form onSubmit={handleSubmit}>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Je e-mailadres
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="naam@voorbeeld.nl"
                required
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />

              {subscribe.error && (
                <p className="mt-2 text-sm text-red-600">{subscribe.error.message}</p>
              )}

              <p className="mt-3 text-xs text-gray-500">
                Je ontvangt een email wanneer de prijs van dit product verandert. Je kunt je op elk
                moment uitschrijven.
              </p>

              <button
                type="submit"
                disabled={subscribe.isPending}
                className="mt-4 w-full py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {subscribe.isPending ? (
                  <>
                    <svg className="animate-spin w-4 h-4" viewBox="0 0 24 24">
                      <circle
                        className="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        strokeWidth="4"
                        fill="none"
                      />
                      <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                      />
                    </svg>
                    Bezig...
                  </>
                ) : (
                  <>
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                      />
                    </svg>
                    Prijsalert instellen
                  </>
                )}
              </button>
            </form>
          </>
        )}
      </div>
    </div>
  )
}
