import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../api/client'

export default function SettingsPage() {
  const { user, token, refreshUser } = useAuth()
  const [username, setUsername] = useState(user?.username || '')
  const [isPublic, setIsPublic] = useState(user?.isPublic ?? true)
  const [discordWebhookUrl, setDiscordWebhookUrl] = useState(user?.discordWebhookUrl || '')
  const [slackWebhookUrl, setSlackWebhookUrl] = useState(user?.slackWebhookUrl || '')
  const [isLoading, setIsLoading] = useState(false)
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)
    setMessage(null)

    try {
      await api.patch('/api/me/settings', {
        username: username || null,
        isPublic,
        discordWebhookUrl: discordWebhookUrl || null,
        slackWebhookUrl: slackWebhookUrl || null,
      }, token!)
      await refreshUser()
      setMessage({ type: 'success', text: 'Instellingen opgeslagen!' })
    } catch (error) {
      setMessage({ type: 'error', text: error instanceof Error ? error.message : 'Er is een fout opgetreden' })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
          <div className="flex items-center gap-4">
            <Link to="/dashboard" className="text-gray-500 hover:text-gray-700">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </Link>
            <h1 className="text-2xl font-bold text-gray-900">Instellingen</h1>
          </div>
        </div>
      </header>

      <main className="max-w-2xl mx-auto px-4 py-8">
        {/* Profiel sectie */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Publiek Profiel</h2>
          <p className="text-gray-600 mb-6">
            Stel een gebruikersnaam in om je producten te delen met anderen.
          </p>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label htmlFor="username" className="block text-sm font-medium text-gray-700 mb-1">
                Gebruikersnaam
              </label>
              <div className="flex items-center gap-2">
                <span className="text-gray-500">shopq.app/u/</span>
                <input
                  type="text"
                  id="username"
                  value={username}
                  onChange={(e) => setUsername(e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, ''))}
                  placeholder="jouw_naam"
                  minLength={3}
                  maxLength={50}
                  className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <p className="text-sm text-gray-500 mt-1">
                Alleen kleine letters, cijfers en underscores. Min. 3 karakters.
              </p>
              {user?.username && (
                <p className="text-sm text-primary-600 mt-2">
                  <a href={`/u/${user.username}`} target="_blank" rel="noopener noreferrer" className="hover:underline">
                    Bekijk je publieke profiel →
                  </a>
                </p>
              )}
            </div>

            <div className="flex items-center justify-between">
              <div>
                <label htmlFor="isPublic" className="text-sm font-medium text-gray-700">
                  Profiel openbaar
                </label>
                <p className="text-sm text-gray-500">
                  Anderen kunnen je gevolgde producten zien
                </p>
              </div>
              <button
                type="button"
                onClick={() => setIsPublic(!isPublic)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                  isPublic ? 'bg-primary-600' : 'bg-gray-200'
                }`}
              >
                <span
                  className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                    isPublic ? 'translate-x-6' : 'translate-x-1'
                  }`}
                />
              </button>
            </div>

            {message && (
              <div className={`p-3 rounded-lg ${message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                {message.text}
              </div>
            )}

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-2 px-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
            >
              {isLoading ? 'Opslaan...' : 'Opslaan'}
            </button>
          </form>
        </div>

        {/* Webhooks sectie */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Webhook Notificaties</h2>
          <p className="text-gray-600 mb-6">
            Ontvang prijsmeldingen direct in Discord of Slack naast e-mail notificaties.
          </p>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label htmlFor="discord" className="block text-sm font-medium text-gray-700 mb-1">
                Discord Webhook URL
              </label>
              <input
                type="url"
                id="discord"
                value={discordWebhookUrl}
                onChange={(e) => setDiscordWebhookUrl(e.target.value)}
                placeholder="https://discord.com/api/webhooks/..."
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
              <p className="text-sm text-gray-500 mt-1">
                <a
                  href="https://support.discord.com/hc/nl/articles/228383668"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary-600 hover:underline"
                >
                  Hoe maak ik een Discord webhook?
                </a>
              </p>
            </div>

            <div>
              <label htmlFor="slack" className="block text-sm font-medium text-gray-700 mb-1">
                Slack Webhook URL
              </label>
              <input
                type="url"
                id="slack"
                value={slackWebhookUrl}
                onChange={(e) => setSlackWebhookUrl(e.target.value)}
                placeholder="https://hooks.slack.com/services/..."
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
              <p className="text-sm text-gray-500 mt-1">
                <a
                  href="https://api.slack.com/messaging/webhooks"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary-600 hover:underline"
                >
                  Hoe maak ik een Slack webhook?
                </a>
              </p>
            </div>

            {message && (
              <div className={`p-3 rounded-lg ${message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                {message.text}
              </div>
            )}

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-2 px-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
            >
              {isLoading ? 'Opslaan...' : 'Opslaan'}
            </button>
          </form>
        </div>

        <div className="mt-8 bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Account</h2>
          
          <div className="space-y-4">
            <div className="flex justify-between items-center py-2 border-b">
              <span className="text-gray-600">E-mail</span>
              <span className="font-medium">{user?.email}</span>
            </div>
            
            <div className="flex justify-between items-center py-2 border-b">
              <span className="text-gray-600">Status</span>
              <span className={`font-medium ${user?.isVerified ? 'text-green-600' : 'text-yellow-600'}`}>
                {user?.isVerified ? 'Geverifieerd' : 'Niet geverifieerd'}
              </span>
            </div>
            
            <div className="flex justify-between items-center py-2">
              <span className="text-gray-600">Lid sinds</span>
              <span className="font-medium">
                {user?.createdAt ? new Date(user.createdAt).toLocaleDateString('nl-NL') : '-'}
              </span>
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}
