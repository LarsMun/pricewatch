import { useState, useRef, useEffect } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { api } from '../api/client'

export default function ResetPasswordPage() {
  const [searchParams] = useSearchParams()
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(false)
  const [status, setStatus] = useState<'form' | 'success' | 'invalid'>('form')
  const tokenChecked = useRef(false)

  const token = searchParams.get('token')

  // Validation helpers
  const isPasswordLongEnough = password.length >= 8
  const doPasswordsMatch = password === confirmPassword && confirmPassword !== ''

  useEffect(() => {
    if (tokenChecked.current) return
    tokenChecked.current = true

    if (!token) {
      setStatus('invalid')
      setError('Geen reset token gevonden')
    }
  }, [token])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (password !== confirmPassword) {
      setError('Wachtwoorden komen niet overeen')
      return
    }

    if (password.length < 8) {
      setError('Wachtwoord moet minimaal 8 karakters zijn')
      return
    }

    setIsLoading(true)

    try {
      await api.post('/api/reset-password', { token, password })
      setStatus('success')
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Wachtwoord resetten mislukt')
    } finally {
      setIsLoading(false)
    }
  }

  if (status === 'invalid') {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center p-4">
        <div className="w-full max-w-md text-center">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h1 className="text-2xl font-bold mb-2 text-red-700">Ongeldige link</h1>
          <p className="text-gray-600 mb-6">{error || 'Deze reset link is ongeldig of verlopen.'}</p>
          <Link
            to="/forgot-password"
            className="inline-block py-3 px-6 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
          >
            Nieuwe link aanvragen
          </Link>
        </div>
      </div>
    )
  }

  if (status === 'success') {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center p-4">
        <div className="w-full max-w-md text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h1 className="text-2xl font-bold mb-2 text-green-700">Wachtwoord gewijzigd!</h1>
          <p className="text-gray-600 mb-6">
            Je wachtwoord is succesvol gewijzigd. Je kunt nu inloggen met je nieuwe wachtwoord.
          </p>
          <Link
            to="/login"
            className="inline-block py-3 px-6 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
          >
            Naar inloggen
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center p-4">
      <div className="w-full max-w-md">
        <h1 className="text-2xl font-bold text-center mb-2">Nieuw wachtwoord instellen</h1>
        <p className="text-center text-gray-600 mb-6">
          Kies een nieuw wachtwoord voor je account.
        </p>
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            {error}
          </div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
              Nieuw wachtwoord
            </label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="new-password"
              className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent ${
                password
                  ? isPasswordLongEnough
                    ? 'border-green-300 bg-green-50'
                    : 'border-red-300 bg-red-50'
                  : 'border-gray-300'
              }`}
              required
            />
            <div className="mt-1 flex items-center gap-1">
              <svg
                className={`w-4 h-4 ${password ? (isPasswordLongEnough ? 'text-green-500' : 'text-gray-400') : 'text-gray-300'}`}
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                {isPasswordLongEnough ? (
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                ) : (
                  <circle cx="10" cy="10" r="5" />
                )}
              </svg>
              <span className={`text-sm ${password ? (isPasswordLongEnough ? 'text-green-600' : 'text-gray-500') : 'text-gray-400'}`}>
                Minimaal 8 karakters
              </span>
            </div>
          </div>
          <div>
            <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700 mb-1">
              Bevestig wachtwoord
            </label>
            <input
              type="password"
              id="confirmPassword"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              autoComplete="new-password"
              className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent ${
                confirmPassword
                  ? doPasswordsMatch
                    ? 'border-green-300 bg-green-50'
                    : 'border-red-300 bg-red-50'
                  : 'border-gray-300'
              }`}
              required
            />
            {confirmPassword && !doPasswordsMatch && (
              <p className="mt-1 text-sm text-red-600">Wachtwoorden komen niet overeen</p>
            )}
            {doPasswordsMatch && (
              <p className="mt-1 text-sm text-green-600 flex items-center gap-1">
                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                </svg>
                Wachtwoorden komen overeen
              </p>
            )}
          </div>
          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Bezig...' : 'Wachtwoord wijzigen'}
          </button>
        </form>
      </div>
    </div>
  )
}
