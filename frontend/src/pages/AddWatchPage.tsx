import { useState, useEffect } from 'react'
import { useSearchParams, useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../api/client'
import { useCreateWatch } from '../hooks/useWatches'

interface ValidateResponse {
  success: boolean
  price?: string
  rawText?: string
  domain?: string
  error?: string
}

export default function AddWatchPage() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { user, token } = useAuth()
  const createWatch = useCreateWatch()

  const urlParam = searchParams.get('url') || ''
  const selectorParam = searchParams.get('selector') || ''
  const rawTextParam = searchParams.get('rawText') || ''
  const titleParam = searchParams.get('title') || ''

  const [url, setUrl] = useState(urlParam)
  const [selector, setSelector] = useState(selectorParam)
  const [productName, setProductName] = useState(titleParam)
  const [detectedPrice, setDetectedPrice] = useState(rawTextParam)

  const [isValidating, setIsValidating] = useState(false)
  const [validationResult, setValidationResult] = useState<ValidateResponse | null>(null)
  const [error, setError] = useState('')

  // Auto-validate if we have URL and selector from bookmarklet
  useEffect(() => {
    if (urlParam && selectorParam && token) {
      validateSelector()
    }
  }, [urlParam, selectorParam, token])

  const validateSelector = async () => {
    if (!url || !selector) {
      setError('URL en selector zijn verplicht')
      return
    }

    setIsValidating(true)
    setError('')
    setValidationResult(null)

    try {
      const result = await api.post<ValidateResponse>('/api/watches/validate', {
        url,
        selector,
      }, token!)

      setValidationResult(result)

      if (result.success && result.price) {
        setDetectedPrice(`€ ${result.price}`)
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Validatie mislukt')
    } finally {
      setIsValidating(false)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (!validationResult?.success) {
      setError('Valideer eerst de selector')
      return
    }

    try {
      await createWatch.mutateAsync({
        url,
        priceSelector: selector,
        productName: productName || undefined,
      })
      navigate('/dashboard')
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Kon watch niet aanmaken')
    }
  }

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow p-6 max-w-md w-full text-center">
          <h1 className="text-xl font-bold mb-4">Log in om door te gaan</h1>
          <p className="text-gray-600 mb-4">
            Je moet ingelogd zijn om een prijswatch toe te voegen.
          </p>
          <Link
            to={`/login?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`}
            className="inline-block px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          >
            Inloggen
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-2xl mx-auto px-4 py-6">
          <Link to="/dashboard" className="text-primary-600 hover:underline text-sm mb-2 inline-block">
            &larr; Terug naar dashboard
          </Link>
          <h1 className="text-2xl font-bold text-gray-900">Prijswatch toevoegen</h1>
        </div>
      </header>

      <main className="max-w-2xl mx-auto px-4 py-8">
        <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">
              {error}
            </div>
          )}

          {/* URL */}
          <div>
            <label htmlFor="url" className="block text-sm font-medium text-gray-700 mb-1">
              Product URL
            </label>
            <input
              type="url"
              id="url"
              value={url}
              onChange={(e) => {
                setUrl(e.target.value)
                setValidationResult(null)
              }}
              placeholder="https://webshop.nl/product/..."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
              required
            />
          </div>

          {/* Selector */}
          <div>
            <label htmlFor="selector" className="block text-sm font-medium text-gray-700 mb-1">
              CSS Selector
            </label>
            <input
              type="text"
              id="selector"
              value={selector}
              onChange={(e) => {
                setSelector(e.target.value)
                setValidationResult(null)
              }}
              placeholder=".price-class of jsonld:offers.price"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
              required
            />
            <p className="mt-1 text-xs text-gray-500">
              Gegenereerd door de bookmarklet, of handmatig invullen
            </p>
          </div>

          {/* Validate button */}
          <div>
            <button
              type="button"
              onClick={validateSelector}
              disabled={isValidating || !url || !selector}
              className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 transition text-sm"
            >
              {isValidating ? 'Valideren...' : 'Selector testen'}
            </button>
          </div>

          {/* Validation result */}
          {validationResult && (
            <div className={`rounded-lg p-4 ${validationResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}`}>
              {validationResult.success ? (
                <div>
                  <div className="flex items-center gap-2 text-green-700 font-medium mb-2">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    Prijs gevonden!
                  </div>
                  <div className="text-2xl font-bold text-gray-900">
                    € {validationResult.price}
                  </div>
                  {validationResult.rawText && validationResult.rawText !== validationResult.price && (
                    <div className="text-sm text-gray-500 mt-1">
                      Ruwe tekst: {validationResult.rawText}
                    </div>
                  )}
                </div>
              ) : (
                <div className="text-red-700">
                  <strong>Validatie mislukt:</strong> {validationResult.error}
                </div>
              )}
            </div>
          )}

          {/* Product name */}
          <div>
            <label htmlFor="productName" className="block text-sm font-medium text-gray-700 mb-1">
              Productnaam (optioneel)
            </label>
            <input
              type="text"
              id="productName"
              value={productName}
              onChange={(e) => setProductName(e.target.value)}
              placeholder="Bijv. Samsung TV 55 inch"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>

          {/* Detected price preview */}
          {detectedPrice && (
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="text-sm text-gray-500 mb-1">Gedetecteerde prijs</div>
              <div className="text-xl font-semibold">{detectedPrice}</div>
            </div>
          )}

          {/* Submit */}
          <div className="flex gap-3 pt-4 border-t">
            <Link
              to="/dashboard"
              className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-center"
            >
              Annuleren
            </Link>
            <button
              type="submit"
              disabled={createWatch.isPending || !validationResult?.success}
              className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition"
            >
              {createWatch.isPending ? 'Toevoegen...' : 'Watch toevoegen'}
            </button>
          </div>
        </form>
      </main>
    </div>
  )
}
