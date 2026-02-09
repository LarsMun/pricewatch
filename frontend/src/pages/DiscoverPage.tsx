import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  useDiscoverCollections,
  useDiscoverUsers,
  DISCOVER_SORT_OPTIONS,
  type DiscoverSortOption,
} from '../hooks/useDiscover'
import { usePublicFeed, SORT_OPTIONS, type SortOption } from '../hooks/usePublicFeed'
import { useAuth } from '../contexts/AuthContext'
import CollectionCard from '../components/CollectionCard'
import UserCard from '../components/UserCard'
import { SEO } from '../components/SEO'

type TabType = 'collections' | 'products' | 'users'

export default function DiscoverPage() {
  const { user } = useAuth()
  const [activeTab, setActiveTab] = useState<TabType>('collections')
  const [collectionsSort, setCollectionsSort] = useState<DiscoverSortOption>('recent')
  const [collectionsPage, setCollectionsPage] = useState(1)
  const [usersSort, setUsersSort] = useState<DiscoverSortOption>('popular')
  const [usersPage, setUsersPage] = useState(1)
  const [productsSort, setProductsSort] = useState<SortOption>('popular')
  const [productsPage, setProductsPage] = useState(1)

  const collectionsQuery = useDiscoverCollections(collectionsSort, collectionsPage, 12)
  const usersQuery = useDiscoverUsers(usersSort, usersPage, 12)
  const productsQuery = usePublicFeed(productsPage, 24, undefined, undefined, productsSort)

  const tabs: { id: TabType; label: string }[] = [
    { id: 'collections', label: 'Collecties' },
    { id: 'products', label: 'Producten' },
    { id: 'users', label: 'Gebruikers' },
  ]

  return (
    <div className="min-h-screen bg-gray-50">
      <SEO
        title="Ontdek - Collecties, Producten & Gebruikers"
        description="Ontdek populaire productcollecties, trending producten en actieve gebruikers op ShopQ."
        canonicalUrl="https://shopq.nl/discover"
      />

      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-8">
            <Link to="/" className="text-2xl font-bold text-primary-600">
              ShopQ
            </Link>
            <nav className="hidden md:flex items-center gap-6">
              <Link to="/" className="text-gray-600 hover:text-gray-900 font-medium">
                Producten
              </Link>
              <Link to="/discover" className="text-gray-900 font-medium">
                Ontdek
              </Link>
            </nav>
          </div>
          <div className="flex items-center gap-4">
            {user ? (
              <Link
                to="/dashboard"
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
              >
                Dashboard
              </Link>
            ) : (
              <>
                <Link to="/login" className="text-gray-600 hover:text-gray-900">
                  Inloggen
                </Link>
                <Link
                  to="/register"
                  className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                >
                  Registreren
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Page Title */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Ontdek</h1>
          <p className="text-gray-500 mt-1">
            Verken collecties, producten en gebruikers
          </p>
        </div>

        {/* Tab Navigation */}
        <div className="flex gap-1 mb-6 border-b border-gray-200">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`px-4 py-3 font-medium text-sm transition-colors relative ${
                activeTab === tab.id
                  ? 'text-primary-600'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              {tab.label}
              {activeTab === tab.id && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600" />
              )}
            </button>
          ))}
        </div>

        {/* Collections Tab */}
        {activeTab === 'collections' && (
          <div>
            {/* Sort Dropdown */}
            <div className="flex justify-end mb-4">
              <select
                value={collectionsSort}
                onChange={(e) => {
                  setCollectionsSort(e.target.value as DiscoverSortOption)
                  setCollectionsPage(1)
                }}
                className="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                {Object.entries(DISCOVER_SORT_OPTIONS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>

            {/* Collections Grid */}
            {collectionsQuery.isLoading ? (
              <div className="flex items-center justify-center py-12">
                <svg className="animate-spin w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              </div>
            ) : collectionsQuery.data?.collections.length === 0 ? (
              <div className="text-center py-12 text-gray-500">
                Geen collecties gevonden
              </div>
            ) : (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {collectionsQuery.data?.collections.map((collection) => (
                    <CollectionCard key={collection.id} collection={collection} />
                  ))}
                </div>

                {/* Pagination */}
                {collectionsQuery.data && collectionsQuery.data.totalPages > 1 && (
                  <div className="flex items-center justify-center gap-2 mt-8">
                    <button
                      onClick={() => setCollectionsPage((p) => Math.max(1, p - 1))}
                      disabled={collectionsPage === 1}
                      className="px-4 py-2 border border-gray-200 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Vorige
                    </button>
                    <span className="text-sm text-gray-500">
                      Pagina {collectionsPage} van {collectionsQuery.data.totalPages}
                    </span>
                    <button
                      onClick={() => setCollectionsPage((p) => p + 1)}
                      disabled={collectionsPage >= collectionsQuery.data.totalPages}
                      className="px-4 py-2 border border-gray-200 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Volgende
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        )}

        {/* Products Tab */}
        {activeTab === 'products' && (
          <div>
            {/* Sort Dropdown */}
            <div className="flex justify-end mb-4">
              <select
                value={productsSort}
                onChange={(e) => {
                  setProductsSort(e.target.value as SortOption)
                  setProductsPage(1)
                }}
                className="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                {Object.entries(SORT_OPTIONS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>

            {/* Products Grid */}
            {productsQuery.isLoading ? (
              <div className="flex items-center justify-center py-12">
                <svg className="animate-spin w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              </div>
            ) : productsQuery.data?.products.length === 0 ? (
              <div className="text-center py-12 text-gray-500">
                Geen producten gevonden
              </div>
            ) : (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                  {productsQuery.data?.products.map((product) => (
                    <Link
                      key={product.id}
                      to={`/product/${product.id}`}
                      className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow"
                    >
                      <div className="aspect-square bg-gray-100">
                        {product.imageUrl ? (
                          <img
                            src={product.imageUrl}
                            alt={product.productName}
                            className="w-full h-full object-contain p-4"
                            loading="lazy"
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center text-gray-400">
                            <svg className="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={1}
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                              />
                            </svg>
                          </div>
                        )}
                      </div>
                      <div className="p-4">
                        <h3 className="font-medium text-gray-900 line-clamp-2 min-h-[48px]">
                          {product.productName}
                        </h3>
                        <p className="text-xs text-gray-500 mt-1">{product.domain}</p>
                        {product.currentPrice && (
                          <p className="text-lg font-bold text-gray-900 mt-2">
                            &euro; {product.currentPrice}
                          </p>
                        )}
                      </div>
                    </Link>
                  ))}
                </div>

                {/* Pagination */}
                {productsQuery.data && productsQuery.data.totalPages > 1 && (
                  <div className="flex items-center justify-center gap-2 mt-8">
                    <button
                      onClick={() => setProductsPage((p) => Math.max(1, p - 1))}
                      disabled={productsPage === 1}
                      className="px-4 py-2 border border-gray-200 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Vorige
                    </button>
                    <span className="text-sm text-gray-500">
                      Pagina {productsPage} van {productsQuery.data.totalPages}
                    </span>
                    <button
                      onClick={() => setProductsPage((p) => p + 1)}
                      disabled={productsPage >= productsQuery.data.totalPages}
                      className="px-4 py-2 border border-gray-200 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Volgende
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        )}

        {/* Users Tab */}
        {activeTab === 'users' && (
          <div>
            {/* Sort Dropdown */}
            <div className="flex justify-end mb-4">
              <select
                value={usersSort}
                onChange={(e) => {
                  setUsersSort(e.target.value as DiscoverSortOption)
                  setUsersPage(1)
                }}
                className="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                {Object.entries(DISCOVER_SORT_OPTIONS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>

            {/* Users Grid */}
            {usersQuery.isLoading ? (
              <div className="flex items-center justify-center py-12">
                <svg className="animate-spin w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              </div>
            ) : usersQuery.data?.users.length === 0 ? (
              <div className="text-center py-12 text-gray-500">
                Geen gebruikers gevonden
              </div>
            ) : (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                  {usersQuery.data?.users.map((discoverUser) => (
                    <UserCard key={discoverUser.id} user={discoverUser} />
                  ))}
                </div>

                {/* Pagination */}
                {usersQuery.data && usersQuery.data.totalPages > 1 && (
                  <div className="flex items-center justify-center gap-2 mt-8">
                    <button
                      onClick={() => setUsersPage((p) => Math.max(1, p - 1))}
                      disabled={usersPage === 1}
                      className="px-4 py-2 border border-gray-200 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Vorige
                    </button>
                    <span className="text-sm text-gray-500">
                      Pagina {usersPage} van {usersQuery.data.totalPages}
                    </span>
                    <button
                      onClick={() => setUsersPage((p) => p + 1)}
                      disabled={usersPage >= usersQuery.data.totalPages}
                      className="px-4 py-2 border border-gray-200 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Volgende
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
