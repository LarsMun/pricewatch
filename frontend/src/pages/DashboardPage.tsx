import { useState, useEffect, useRef } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { useCheckAllWatches, useWatches } from '../hooks/useWatches'
import { useCollections, useUpdateCollection } from '../hooks/useCollections'
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
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const checkAll = useCheckAllWatches()
  const { data: watches } = useWatches()
  const { data: collections } = useCollections()
  const updateCollection = useUpdateCollection()
  const [sharePopoverOpen, setSharePopoverOpen] = useState(false)
  const [linkCopied, setLinkCopied] = useState(false)
  const shareButtonRef = useRef<HTMLDivElement>(null)

  const selectedCollection = selectedCollectionId
    ? collections?.find(c => c.id === selectedCollectionId)
    : null

  const handleLogout = () => {
    logout()
    navigate('/')
  }

  const handleCheckAll = () => {
    checkAll.mutate()
  }

  // Auto-dismiss success message after 5 seconds
  useEffect(() => {
    if (checkAll.isSuccess) {
      const timer = setTimeout(() => {
        checkAll.reset()
      }, 5000)
      return () => clearTimeout(timer)
    }
  }, [checkAll.isSuccess])

  // Close share popover when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (shareButtonRef.current && !shareButtonRef.current.contains(event.target as Node)) {
        setSharePopoverOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  // Reset link copied state when popover closes
  useEffect(() => {
    if (!sharePopoverOpen) {
      setLinkCopied(false)
    }
  }, [sharePopoverOpen])

  const handleShare = () => {
    if (!selectedCollection) return

    if (!selectedCollection.isPublic) {
      // Make public first
      updateCollection.mutate({
        id: selectedCollection.id,
        data: { isPublic: true }
      })
    }
    setSharePopoverOpen(true)
  }

  const handleCopyLink = () => {
    if (selectedCollection?.shareUrl) {
      navigator.clipboard.writeText(window.location.origin + selectedCollection.shareUrl)
      setLinkCopied(true)
    }
  }

  const handleMakePrivate = () => {
    if (selectedCollection) {
      updateCollection.mutate({
        id: selectedCollection.id,
        data: { isPublic: false }
      })
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-4 md:py-6 flex justify-between items-center">
          <h1 className="text-xl md:text-2xl font-bold text-gray-900">ShopQ</h1>

          {/* Desktop menu */}
          <div className="hidden md:flex items-center gap-4">
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
              to="/bookmarklet"
              className="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition flex items-center gap-2"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
              Bookmarklet
            </Link>
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

          {/* Mobile hamburger button */}
          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="md:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
          >
            {isMobileMenuOpen ? (
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            ) : (
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            )}
          </button>
        </div>

        {/* Mobile menu dropdown */}
        {isMobileMenuOpen && (
          <div className="md:hidden border-t bg-white">
            <div className="px-4 py-3 space-y-2">
              <div className="text-sm text-gray-600 py-2 border-b">{user?.email}</div>
              {user?.roles?.includes('ROLE_ADMIN') && (
                <Link
                  to="/admin"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="block px-4 py-3 text-sm bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg transition"
                >
                  Admin
                </Link>
              )}
              <Link
                to="/bookmarklet"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-4 py-3 text-sm bg-gray-50 hover:bg-gray-100 rounded-lg transition"
              >
                Bookmarklet
              </Link>
              <Link
                to="/settings"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-4 py-3 text-sm bg-gray-50 hover:bg-gray-100 rounded-lg transition"
              >
                Instellingen
              </Link>
              <button
                onClick={() => {
                  setIsMobileMenuOpen(false)
                  handleLogout()
                }}
                className="w-full text-left px-4 py-3 text-sm bg-gray-50 hover:bg-gray-100 rounded-lg transition"
              >
                Uitloggen
              </button>
            </div>
          </div>
        )}
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

        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
          <h2 className="text-xl font-semibold text-gray-900">
            {selectedCollectionId && collections
              ? collections.find(c => c.id === selectedCollectionId)?.name || 'Collectie'
              : 'Mijn prijswatches'}
          </h2>
          <div className="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
            {user?.roles?.includes('ROLE_ADMIN') && (
              <button
                onClick={handleCheckAll}
                disabled={checkAll.isPending}
                className="p-2 sm:px-4 sm:py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center justify-center gap-2 disabled:opacity-50"
                title="Check alle"
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
                <span className="hidden sm:inline">{checkAll.isPending ? 'Checken...' : 'Check alle'}</span>
              </button>
            )}
            {selectedCollectionId && selectedCollection && (
              <div className="relative" ref={shareButtonRef}>
                <button
                  onClick={handleShare}
                  className={`p-2 sm:px-4 sm:py-2 border rounded-lg transition flex items-center justify-center gap-2 ${
                    selectedCollection.isPublic
                      ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                      : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                  }`}
                  title={selectedCollection.isPublic ? 'Gedeeld' : 'Deel'}
                >
                  {selectedCollection.isPublic ? (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  ) : (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                  )}
                  <span className="hidden sm:inline">{selectedCollection.isPublic ? 'Gedeeld' : 'Deel'}</span>
                </button>

                {sharePopoverOpen && (
                  <div className="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg p-4 z-20 min-w-[280px]">
                    {selectedCollection.isPublic ? (
                      <>
                        <div className="text-sm font-medium text-gray-900 mb-2">Deel link</div>
                        <div className="flex items-center gap-2 mb-3">
                          <input
                            type="text"
                            readOnly
                            value={selectedCollection.shareUrl ? window.location.origin + selectedCollection.shareUrl : ''}
                            className="flex-1 px-3 py-2 border rounded-lg text-sm bg-gray-50 text-gray-600"
                          />
                          <button
                            onClick={handleCopyLink}
                            className="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm"
                          >
                            {linkCopied ? 'Gekopieerd!' : 'Kopieer'}
                          </button>
                        </div>
                        <button
                          onClick={handleMakePrivate}
                          className="w-full flex items-center justify-center gap-2 px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                          </svg>
                          Maak privé
                        </button>
                      </>
                    ) : (
                      <>
                        <div className="flex items-center gap-2 mb-3 text-gray-600">
                          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                          </svg>
                          <span className="text-sm">Deze collectie is privé</span>
                        </div>
                        <button
                          onClick={handleShare}
                          className="w-full flex items-center justify-center gap-2 px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          Maak openbaar
                        </button>
                      </>
                    )}
                  </div>
                )}
              </div>
            )}
            <button
              onClick={() => setIsAddModalOpen(true)}
              className="flex-1 sm:flex-none px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center justify-center gap-2"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              <span className="sm:inline">Toevoegen</span>
            </button>
          </div>
        </div>

        {checkAll.isSuccess && checkAll.data && (
          <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg animate-fade-in-down">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-green-700 font-medium">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                Alle watches gecheckt: {checkAll.data.success} succesvol, {checkAll.data.failed} mislukt
              </div>
              <button
                onClick={() => checkAll.reset()}
                className="text-green-600 hover:text-green-800 p-1"
                title="Sluiten"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
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
          onAddWatch={() => setIsAddModalOpen(true)}
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
