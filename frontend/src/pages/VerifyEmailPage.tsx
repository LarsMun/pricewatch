import { useEffect, useState, useRef } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { api } from '../api/client'

export default function VerifyEmailPage() {
  const [searchParams] = useSearchParams()
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading')
  const [error, setError] = useState('')
  const verifyAttempted = useRef(false)

  useEffect(() => {
    if (verifyAttempted.current) return
    verifyAttempted.current = true

    const token = searchParams.get('token')
    if (!token) {
      setStatus('error')
      setError('Geen verificatietoken gevonden')
      return
    }
    verifyEmail(token)
  }, [searchParams])

  async function verifyEmail(token: string) {
    try {
      await api.post('/api/verify-email', { token })
      setStatus('success')
    } catch (err) {
      setStatus('error')
      setError(err instanceof Error ? err.message : 'Verificatie mislukt')
    }
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center p-4">
      <div className="w-full max-w-md text-center">
        {status === 'loading' && (
          <>
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
            <h1 className="text-2xl font-bold mb-2">E-mailadres verifiëren...</h1>
            <p className="text-gray-600">Even geduld</p>
          </>
        )}

        {status === 'success' && (
          <>
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold mb-2 text-green-700">E-mailadres geverifieerd!</h1>
            <p className="text-gray-600 mb-6">
              Je kunt nu volledige toegang krijgen tot alle functies.
            </p>
            <Link
              to="/dashboard"
              className="inline-block py-3 px-6 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
            >
              Ga naar dashboard
            </Link>
          </>
        )}

        {status === 'error' && (
          <>
            <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold mb-2 text-red-700">Verificatie mislukt</h1>
            <p className="text-gray-600 mb-6">{error}</p>
            <p className="text-sm text-gray-500 mb-4">
              De link is mogelijk verlopen of al gebruikt.
            </p>
            <Link
              to="/dashboard"
              className="inline-block py-3 px-6 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
            >
              Ga naar dashboard
            </Link>
          </>
        )}
      </div>
    </div>
  )
}
