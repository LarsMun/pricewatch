import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../api/client'
import type { AdminStats, AdminUser, RecentCheck } from '../types'

function StatCard({ label, value, subValue }: { label: string; value: number | string; subValue?: string }) {
  return (
    <div className="bg-white rounded-lg shadow p-4">
      <div className="text-sm text-gray-500">{label}</div>
      <div className="text-2xl font-bold text-gray-900">{value}</div>
      {subValue && <div className="text-sm text-gray-500">{subValue}</div>}
    </div>
  )
}

export default function AdminPage() {
  const { token } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'overview' | 'users' | 'checks'>('overview')

  const stats = useQuery({
    queryKey: ['admin', 'stats'],
    queryFn: () => api.get<AdminStats>('/api/admin/stats', token!),
  })

  const users = useQuery({
    queryKey: ['admin', 'users'],
    queryFn: () => api.get<{ users: AdminUser[]; pagination: { total: number } }>('/api/admin/users?limit=50', token!),
  })

  const recentChecks = useQuery({
    queryKey: ['admin', 'recent-checks'],
    queryFn: () => api.get<RecentCheck[]>('/api/admin/recent-checks?limit=50', token!),
    enabled: activeTab === 'checks',
  })

  const toggleAdmin = useMutation({
    mutationFn: ({ userId, action }: { userId: number; action: 'grant_admin' | 'revoke_admin' }) =>
      api.patch(`/api/admin/users/${userId}/role`, { action }, token!),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'users'] })
    },
  })

  if (stats.isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    )
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
            <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Tabs */}
        <div className="flex gap-4 mb-6">
          {(['overview', 'users', 'checks'] as const).map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`px-4 py-2 rounded-lg font-medium transition ${
                activeTab === tab
                  ? 'bg-primary-600 text-white'
                  : 'bg-white text-gray-600 hover:bg-gray-100'
              }`}
            >
              {tab === 'overview' ? 'Overzicht' : tab === 'users' ? 'Gebruikers' : 'Recente Checks'}
            </button>
          ))}
        </div>

        {/* Overview Tab */}
        {activeTab === 'overview' && stats.data && (
          <div className="space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <StatCard label="Totaal Gebruikers" value={stats.data.users.total} subValue={`${stats.data.users.newLast7Days} nieuw deze week`} />
              <StatCard label="Geverifieerd" value={stats.data.users.verified} subValue={`${stats.data.users.unverified} niet geverifieerd`} />
              <StatCard label="Actieve Watches" value={stats.data.watches.active} subValue={`${stats.data.watches.paused} gepauzeerd`} />
              <StatCard label="Notificaties (7d)" value={stats.data.notifications.last7Days} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="bg-white rounded-lg shadow p-6">
                <h3 className="text-lg font-semibold mb-4">Prijschecks (24u)</h3>
                <div className="space-y-2">
                  <div className="flex justify-between">
                    <span>Totaal</span>
                    <span className="font-medium">{stats.data.priceChecks.last24h}</span>
                  </div>
                  <div className="flex justify-between text-green-600">
                    <span>Succesvol</span>
                    <span className="font-medium">{stats.data.priceChecks.successful}</span>
                  </div>
                  <div className="flex justify-between text-red-600">
                    <span>Mislukt</span>
                    <span className="font-medium">{stats.data.priceChecks.failed}</span>
                  </div>
                  <div className="pt-2 border-t">
                    <div className="flex justify-between">
                      <span>Success Rate</span>
                      <span className="font-medium">{stats.data.priceChecks.successRate}%</span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg shadow p-6">
                <h3 className="text-lg font-semibold mb-4">Top Domeinen</h3>
                <div className="space-y-2">
                  {stats.data.topDomains.slice(0, 5).map((d) => (
                    <div key={d.domain} className="flex justify-between">
                      <span className="truncate">{d.domain}</span>
                      <span className="font-medium text-gray-600">{d.count}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Users Tab */}
        {activeTab === 'users' && users.data && (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Watches</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aangemeld</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {users.data.users.map((user) => (
                  <tr key={user.id}>
                    <td className="px-6 py-4 whitespace-nowrap">{user.email}</td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 py-1 text-xs rounded-full ${user.isVerified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                        {user.isVerified ? 'Geverifieerd' : 'Niet geverifieerd'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">{user.watchCount}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {new Date(user.createdAt).toLocaleDateString('nl-NL')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <button
                        onClick={() => toggleAdmin.mutate({
                          userId: user.id,
                          action: user.roles.includes('ROLE_ADMIN') ? 'revoke_admin' : 'grant_admin'
                        })}
                        disabled={toggleAdmin.isPending}
                        className={`px-2 py-1 text-xs rounded ${
                          user.roles.includes('ROLE_ADMIN')
                            ? 'bg-red-100 text-red-700 hover:bg-red-200'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        }`}
                      >
                        {user.roles.includes('ROLE_ADMIN') ? 'Verwijder Admin' : 'Maak Admin'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Recent Checks Tab */}
        {activeTab === 'checks' && (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {recentChecks.isLoading ? (
              <div className="p-8 text-center">Laden...</div>
            ) : (
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tijd</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domein</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prijs</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duur</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gebruiker</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {recentChecks.data?.map((check) => (
                    <tr key={check.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(check.checkedAt).toLocaleTimeString('nl-NL')}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">{check.domain}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        {check.price ? `€${check.price}` : '-'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 py-1 text-xs rounded-full ${check.wasSuccessful ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                          {check.wasSuccessful ? 'OK' : check.httpStatus || 'Fout'}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {check.durationMs ? `${check.durationMs}ms` : '-'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{check.userEmail}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        )}
      </main>
    </div>
  )
}
