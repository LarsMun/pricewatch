import { useState } from 'react'
import { useCreateWatch, useAnalyzeUrl } from '../hooks/useWatches'
import type { AnalyzeUrlResponse } from '../types'

interface AddWatchModalProps {
  isOpen: boolean
  onClose: () => void
}

type Step = 'url' | 'confirm'

function formatPrice(price: string | null, currency: string): string {
  if (!price) return '-'
  const num = parseFloat(price)
  return new Intl.NumberFormat('nl-NL', {
    style: 'currency',
    currency,
  }).format(num)
}

export default function AddWatchModal({ isOpen, onClose }: AddWatchModalProps) {
  const [step, setStep] = useState<Step>('url')
  const [url, setUrl] = useState('')
  const [error, setError] = useState('')

  // Analyzed data
  const [analysis, setAnalysis] = useState<AnalyzeUrlResponse | null>(null)

  // Editable fields (populated from analysis)
  const [productName, setProductName] = useState('')
  const [priceSelector, setPriceSelector] = useState('')
  const [currency, setCurrency] = useState('EUR')
  const [imageUrl, setImageUrl] = useState('')

  const analyzeUrl = useAnalyzeUrl()
  const createWatch = useCreateWatch()

  if (!isOpen) return null

  const resetForm = () => {
    setStep('url')
    setUrl('')
    setError('')
    setAnalysis(null)
    setProductName('')
    setPriceSelector('')
    setCurrency('EUR')
    setImageUrl('')
  }

  const handleClose = () => {
    resetForm()
    onClose()
  }

  const handleAnalyze = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (!url.trim()) {
      setError('URL is verplicht')
      return
    }

    try {
      const result = await analyzeUrl.mutateAsync(url.trim())
      setAnalysis(result)

      // Pre-fill fields from analysis
      setProductName(result.productName || '')
      setPriceSelector(result.priceSelector || '')
      setCurrency(result.currency || 'EUR')
      setImageUrl(result.imageUrl || '')

      setStep('confirm')
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Kon URL niet analyseren')
    }
  }

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (!priceSelector.trim()) {
      setError('Prijs selector is verplicht')
      return
    }

    try {
      await createWatch.mutateAsync({
        url: url.trim(),
        priceSelector: priceSelector.trim(),
        productName: productName.trim() || undefined,
        currency: currency,
        imageUrl: imageUrl.trim() || undefined,
      })
      handleClose()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Er is een fout opgetreden')
    }
  }

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      handleClose()
    }
  }

  const handleSelectSelector = (selector: string) => {
    setPriceSelector(selector)
  }

  return (
    <div
      className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      onClick={handleBackdropClick}
    >
      <div className="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex justify-between items-center p-4 border-b shrink-0">
          <div className="flex items-center gap-3">
            <h2 className="text-lg font-semibold">Nieuwe prijswatch</h2>
            <div className="flex gap-1">
              <div className={`w-2 h-2 rounded-full ${step === 'url' ? 'bg-primary-600' : 'bg-gray-300'}`} />
              <div className={`w-2 h-2 rounded-full ${step === 'confirm' ? 'bg-primary-600' : 'bg-gray-300'}`} />
            </div>
          </div>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-600"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Content */}
        <div className="p-4 overflow-y-auto flex-1">
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm mb-4">
              {error}
            </div>
          )}

          {step === 'url' && (
            <form onSubmit={handleAnalyze} className="space-y-4">
              <div>
                <label htmlFor="url" className="block text-sm font-medium text-gray-700 mb-1">
                  Product URL
                </label>
                <input
                  type="url"
                  id="url"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="https://webshop.nl/product/..."
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  autoFocus
                  required
                />
                <p className="mt-2 text-sm text-gray-500">
                  Plak de URL van het product dat je wilt volgen. We analyseren automatisch de prijs.
                </p>
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={handleClose}
                  className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                >
                  Annuleren
                </button>
                <button
                  type="submit"
                  disabled={analyzeUrl.isPending}
                  className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition flex items-center justify-center gap-2"
                >
                  {analyzeUrl.isPending ? (
                    <>
                      <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      Analyseren...
                    </>
                  ) : (
                    <>
                      <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      Analyseer URL
                    </>
                  )}
                </button>
              </div>
            </form>
          )}

          {step === 'confirm' && analysis && (
            <form onSubmit={handleCreate} className="space-y-4">
              {/* Detection status */}
              {analysis.detectionMethod === 'jsonld' && (
                <div className="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 text-sm flex items-center gap-2">
                  <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                  Productgegevens automatisch gevonden via gestructureerde data
                </div>
              )}
              {analysis.detectionMethod === 'css' && (
                <div className="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg p-3 text-sm flex items-center gap-2">
                  <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Prijs gevonden via CSS selector. Controleer of dit correct is.
                </div>
              )}
              {analysis.detectionMethod === 'none' && (
                <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm flex items-center gap-2">
                  <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  Geen prijs automatisch gevonden. Voer handmatig een CSS selector in.
                </div>
              )}

              {/* Preview card */}
              <div className="border rounded-lg overflow-hidden">
                <div className="flex gap-4 p-3 bg-gray-50">
                  {imageUrl ? (
                    <img
                      src={imageUrl}
                      alt={productName || 'Product'}
                      className="w-20 h-20 object-cover rounded bg-white"
                    />
                  ) : (
                    <div className="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                      <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                  )}
                  <div className="flex-1 min-w-0">
                    <div className="font-medium text-gray-900 truncate">
                      {productName || analysis.domain}
                    </div>
                    <div className="text-sm text-gray-500 truncate">{analysis.domain}</div>
                    {analysis.price && (
                      <div className="text-lg font-bold text-primary-600 mt-1">
                        {formatPrice(analysis.price, currency)}
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Editable fields */}
              <div>
                <label htmlFor="productName" className="block text-sm font-medium text-gray-700 mb-1">
                  Productnaam
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

              <div>
                <label htmlFor="priceSelector" className="block text-sm font-medium text-gray-700 mb-1">
                  Prijs selector *
                </label>
                <input
                  type="text"
                  id="priceSelector"
                  value={priceSelector}
                  onChange={(e) => setPriceSelector(e.target.value)}
                  placeholder="jsonld:offers.price of .price-class"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                  required
                />

                {/* Available selectors */}
                {analysis.availableSelectors.length > 0 && (
                  <div className="mt-2">
                    <p className="text-xs text-gray-500 mb-1">Beschikbare selectors:</p>
                    <div className="flex flex-wrap gap-2">
                      {analysis.availableSelectors.map((sel, idx) => (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => handleSelectSelector(sel.selector)}
                          className={`px-2 py-1 text-xs rounded border transition ${
                            priceSelector === sel.selector
                              ? 'bg-primary-100 border-primary-300 text-primary-700'
                              : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'
                          } ${sel.recommended ? 'ring-1 ring-green-400' : ''}`}
                        >
                          <span className="font-mono">{sel.selector}</span>
                          <span className="ml-1 text-gray-400">({formatPrice(sel.price, currency)})</span>
                          {sel.recommended && <span className="ml-1 text-green-600">✓</span>}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setStep('url')}
                  className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                >
                  Terug
                </button>
                <button
                  type="submit"
                  disabled={createWatch.isPending || !priceSelector.trim()}
                  className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition"
                >
                  {createWatch.isPending ? 'Toevoegen...' : 'Toevoegen'}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  )
}
