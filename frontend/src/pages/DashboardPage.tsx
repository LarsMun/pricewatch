import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { useCheckAllWatches, useWatches } from '../hooks/useWatches'
import { useCollections } from '../hooks/useCollections'
import WatchList from '../components/WatchList'
import AddWatchModal from '../components/AddWatchModal'
import VerificationBanner from '../components/VerificationBanner'
import CollectionTabs from '../components/CollectionTabs'
import CreateCollectionModal from '../components/CreateCollectionModal'

export default function DashboardPage() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [isAddModalOpen, setIsAddModalOpen] = useState(false)
  const [isCreateCollectionModalOpen, setIsCreateCollectionModalOpen] = useState(false)
  const [selectedCollectionId, setSelectedCollectionId] = useState<number | null>(null)
  const checkAll = useCheckAllWatches()
  const { data: watches } = useWatches()
  const { data: collections } = useCollections()

  const handleLogout = () => {
    logout()
    navigate('/')
  }

  const handleCheckAll = () => {
    checkAll.mutate()
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
          <h1 className="text-2xl font-bold text-gray-900">ShopQ - Mijn overzicht</h1>
          <div className="flex items-center gap-4">
            <span className="text-gray-600">{user?.email}</span>
            {user?.roles?.includes('ROLE_ADMIN') && (
              <Link
                to="/admin"
                className="px-4 py-2 text-sm bg-purple-100 text-purple-700 hover:bg-purple-200 rounded-lg transition"
              >
                Admin
              </Link>
            )}
            <Link
              to="/settings"
              className="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition"
            >
              Instellingen
            </Link>
            <button
              onClick={handleLogout}
              className="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition"
            >
              Uitloggen
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        <VerificationBanner />

        {/* Collection Tabs */}
        <CollectionTabs
          selectedCollection={selectedCollectionId}
          onSelectCollection={setSelectedCollectionId}
          onCreateNew={() => setIsCreateCollectionModalOpen(true)}
          totalWatchCount={watches?.length || 0}
        />

        <div className="flex justify-between items-center mb-6">
          <h2 className="text-xl font-semibold text-gray-900">
            {selectedCollectionId && collections
              ? collections.find(c => c.id === selectedCollectionId)?.name || 'Collectie'
              : 'Mijn prijswatches'}
          </h2>
          <div className="flex items-center gap-3">
            <button
              onClick={handleCheckAll}
              disabled={checkAll.isPending}
              className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2 disabled:opacity-50"
            >
              {checkAll.isPending ? (
                <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              )}
              {checkAll.isPending ? 'Checken...' : 'Check alle'}
            </button>
            <Link
              to="/bookmarklet"
              className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
              Bookmarklet
            </Link>
            <button
              onClick={() => setIsAddModalOpen(true)}
              className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center gap-2"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              Nieuwe watch
            </button>
          </div>
        </div>

        {checkAll.isSuccess && checkAll.data && (
          <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div className="flex items-center gap-2 text-green-700 font-medium">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
              Alle watches gecheckt: {checkAll.data.success} succesvol, {checkAll.data.failed} mislukt
            </div>
          </div>
        )}

        {checkAll.isError && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            Fout bij checken: {checkAll.error?.message}
          </div>
        )}

        <WatchList
          selectedCollectionId={selectedCollectionId}
        />
      </main>

      <AddWatchModal
        isOpen={isAddModalOpen}
        onClose={() => setIsAddModalOpen(false)}
      />

      <CreateCollectionModal
        isOpen={isCreateCollectionModalOpen}
        onClose={() => setIsCreateCollectionModalOpen(false)}
      />
    </div>
  )
}
