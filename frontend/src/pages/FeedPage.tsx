import { useState, useMemo } from 'react'
import { Link } from 'react-router-dom'
import {
  usePublicFeed,
  usePopularDomains,
  useCategories,
  SORT_OPTIONS,
  type PublicProduct,
  type SortOption,
} from '../hooks/usePublicFeed'
import { useAuth } from '../contexts/AuthContext'
import { useWatches } from '../hooks/useWatches'
import { useCollections } from '../hooks/useCollections'
import SubscribeModal from '../components/SubscribeModal'
import CategorySidebar from '../components/CategorySidebar'
import { SEO, createWebSiteSchema, createItemListSchema } from '../components/SEO'
import type { ProductWatch } from '../types'

function ProductCard({
  product,
  onSubscribe,
}: {
  product: PublicProduct
  onSubscribe: (product: PublicProduct) => void
}) {
  const priceChange =
    product.previousPrice && product.currentPrice
      ? (
          ((parseFloat(product.currentPrice) - parseFloat(product.previousPrice)) /
            parseFloat(product.previousPrice)) *
          100
        ).toFixed(1)
      : null

  const isPriceDown = priceChange && parseFloat(priceChange) < 0

  return (
    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
      <Link to={`/product/${product.id}`} className="block">
        <div className="aspect-square bg-gray-100 relative">
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
          {priceChange && (
            <span
              className={`absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-semibold ${
                isPriceDown ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
              }`}
            >
              {isPriceDown ? '' : '+'}
              {priceChange}%
            </span>
          )}
        </div>
      </Link>

      <div className="p-4">
        <Link to={`/product/${product.id}`}>
          <h3 className="font-medium text-gray-900 line-clamp-2 hover:text-blue-600 min-h-[48px]">
            {product.productName}
          </h3>
        </Link>

        <p className="text-xs text-gray-500 mt-1">{product.domain}</p>

        {product.category && (
          <span className="inline-flex items-center gap-1 mt-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
            {product.category.icon && <span>{product.category.icon}</span>}
            {product.category.name}
          </span>
        )}

        <div className="mt-2 flex items-baseline gap-2">
          {product.currentPrice && (
            <span className="text-xl font-bold text-gray-900">&euro; {product.currentPrice}</span>
          )}
          {product.previousPrice && product.previousPrice !== product.currentPrice && (
            <span className="text-sm text-gray-400 line-through">
              &euro; {product.previousPrice}
            </span>
          )}
        </div>

        <div className="mt-3 flex items-center justify-between">
          <div className="flex items-center gap-1 text-xs text-gray-500">
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>
            {product.subscriberCount + 1}
          </div>

          <button
            onClick={() => onSubscribe(product)}
            className="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
          >
            Alert
          </button>
        </div>

        {product.username && (
          <Link
            to={`/u/${product.username}`}
            className="mt-2 block text-xs text-gray-400 hover:text-blue-600"
          >
            via @{product.username}
          </Link>
        )}
      </div>
    </div>
  )
}

// Convert user's ProductWatch to PublicProduct format for unified display
function watchToPublicProduct(watch: ProductWatch): PublicProduct {
  return {
    id: watch.id,
    productName: watch.productName || watch.domain,
    url: watch.url,
    domain: watch.domain,
    imageUrl: watch.imageUrl,
    currentPrice: watch.currentPrice,
    previousPrice: watch.previousPrice,
    originalPrice: watch.originalPrice,
    currency: watch.currency,
    subscriberCount: 0,
    createdAt: watch.createdAt,
  }
}

export default function FeedPage() {
  const { user } = useAuth()
  const [page, setPage] = useState(1)
  const [selectedDomain, setSelectedDomain] = useState<string | undefined>()
  const [selectedCategory, setSelectedCategory] = useState<string | undefined>()
  const [selectedSort, setSelectedSort] = useState<SortOption>('popular')
  const [subscribeProduct, setSubscribeProduct] = useState<PublicProduct | null>(null)

  // My watches filter state: null = public feed, 'all' = all my watches, number = specific collection
  const [myWatchesFilter, setMyWatchesFilter] = useState<'all' | number | null>(null)
  const [myWatchesExpanded, setMyWatchesExpanded] = useState(false)

  const { data: feedData, isLoading, error } = usePublicFeed(page, 24, selectedDomain, selectedCategory, selectedSort)
  const { data: domainsData } = usePopularDomains()
  const { data: categoriesData } = useCategories()

  // User's watches and collections (only fetched when logged in)
  const { data: myWatches } = useWatches()
  const { data: myCollections } = useCollections()

  // Filter user's watches based on selected collection
  const filteredMyWatches = useMemo(() => {
    if (!myWatches || myWatchesFilter === null) return []

    if (myWatchesFilter === 'all') {
      return myWatches
    }

    // Filter by collection ID
    return myWatches.filter(w => w.collectionIds?.includes(myWatchesFilter as number))
  }, [myWatches, myWatchesFilter])

  // Helper to calculate price drop percentage
  const getPriceDropPercent = (product: PublicProduct): number => {
    if (!product.previousPrice || !product.currentPrice) return 0
    const prev = parseFloat(product.previousPrice)
    const curr = parseFloat(product.currentPrice)
    if (prev <= 0 || curr >= prev) return 0
    return ((prev - curr) / prev) * 100
  }

  // Products to display: either user's watches or public feed
  const displayProducts = useMemo((): PublicProduct[] => {
    let products: PublicProduct[]

    if (myWatchesFilter !== null) {
      products = filteredMyWatches.map(watchToPublicProduct)

      // Apply client-side sorting for my watches
      switch (selectedSort) {
        case 'price_drop':
          products = products
            .filter(p => getPriceDropPercent(p) > 0)
            .sort((a, b) => getPriceDropPercent(b) - getPriceDropPercent(a))
          break
        case 'newest':
          products = [...products].sort(
            (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
          )
          break
        case 'price_low':
          products = [...products].sort(
            (a, b) => parseFloat(a.currentPrice || '0') - parseFloat(b.currentPrice || '0')
          )
          break
        case 'price_high':
          products = [...products].sort(
            (a, b) => parseFloat(b.currentPrice || '0') - parseFloat(a.currentPrice || '0')
          )
          break
        case 'popular':
        default:
          products = [...products].sort((a, b) => b.subscriberCount - a.subscriberCount)
          break
      }
    } else {
      products = feedData?.products || []
    }

    return products
  }, [myWatchesFilter, filteredMyWatches, feedData, selectedSort])

  const isShowingMyWatches = myWatchesFilter !== null

  // Generate JSON-LD for SEO
  const jsonLd = useMemo(() => {
    if (isShowingMyWatches) return null

    const schemas = [createWebSiteSchema()]
    if (displayProducts.length > 0) {
      const categoryName = selectedCategory
        ? categoriesData?.categories.find(c => c.slug === selectedCategory)?.name
        : undefined
      const listName = categoryName
        ? `${categoryName} producten`
        : selectedDomain
          ? `Producten van ${selectedDomain}`
          : 'Populaire producten'
      schemas.push(createItemListSchema(displayProducts, listName))
    }
    return schemas
  }, [isShowingMyWatches, displayProducts, selectedCategory, selectedDomain, categoriesData])

  // Generate page title and description for SEO
  const pageTitle = useMemo(() => {
    if (selectedCategory) {
      const category = categoriesData?.categories.find(c => c.slug === selectedCategory)
      return category ? `${category.name} prijzen vergelijken` : undefined
    }
    if (selectedDomain) {
      return `${selectedDomain} prijzen volgen`
    }
    return undefined
  }, [selectedCategory, selectedDomain, categoriesData])

  const pageDescription = useMemo(() => {
    if (selectedCategory) {
      const category = categoriesData?.categories.find(c => c.slug === selectedCategory)
      return category
        ? `Vergelijk ${category.name.toLowerCase()} prijzen en ontvang alerts bij prijsdalingen. Volg ${feedData?.totalCount || 0} producten.`
        : undefined
    }
    if (selectedDomain) {
      return `Volg ${selectedDomain} prijzen en ontvang meldingen bij kortingen. ${feedData?.totalCount || 0} producten beschikbaar.`
    }
    return undefined
  }, [selectedCategory, selectedDomain, categoriesData, feedData])

  return (
    <div className="min-h-screen bg-gray-50">
      <SEO
        title={pageTitle}
        description={pageDescription}
        canonicalUrl={`https://shopq.nl${selectedCategory ? `/?category=${selectedCategory}` : selectedDomain ? `/?domain=${selectedDomain}` : ''}`}
        jsonLd={jsonLd || undefined}
        noIndex={isShowingMyWatches}
      />

      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-8">
            <Link to="/" className="text-2xl font-bold text-blue-600">
              ShopQ
            </Link>
            <nav className="hidden md:flex items-center gap-6">
              <Link to="/" className="text-gray-900 font-medium">
                Producten
              </Link>
              <Link to="/discover" className="text-gray-600 hover:text-gray-900 font-medium">
                Ontdek
              </Link>
            </nav>
          </div>
          <div className="flex items-center gap-4">
            {user ? (
              <Link
                to="/dashboard"
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
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
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                  Registreren
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      {/* Hero */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-12">
        <div className="max-w-7xl mx-auto px-4 text-center">
          <h1 className="text-4xl font-bold mb-4">Ontdek producten. Volg prijzen.</h1>
          <p className="text-xl text-blue-100 max-w-2xl mx-auto">
            Bekijk wat anderen volgen en ontvang een melding wanneer de prijs daalt.
          </p>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Sidebar */}
          <aside className="lg:w-64 flex-shrink-0 space-y-4">
            <div className="sticky top-4 space-y-4">
              {/* My Watches - only for logged in users */}
              {user && myWatches && myWatches.length > 0 && (
                <div className="bg-white rounded-lg border border-gray-200 p-4">
                  <button
                    onClick={() => setMyWatchesExpanded(!myWatchesExpanded)}
                    className="w-full flex items-center justify-between"
                  >
                    <div className="flex items-center gap-2">
                      <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <span className="font-semibold text-gray-900">Mijn Watches</span>
                      <span className="text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">
                        {myWatches.length}
                      </span>
                    </div>
                    <svg
                      className={`w-4 h-4 text-gray-400 transition-transform ${myWatchesExpanded ? 'rotate-180' : ''}`}
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>

                  {myWatchesExpanded && (
                    <div className="mt-3 space-y-1">
                      {/* All my watches */}
                      <button
                        onClick={() => {
                          setMyWatchesFilter(myWatchesFilter === 'all' ? null : 'all')
                          setSelectedCategory(undefined)
                          setSelectedDomain(undefined)
                          setPage(1)
                        }}
                        className={`block w-full text-left px-3 py-2 rounded-lg text-sm ${
                          myWatchesFilter === 'all'
                            ? 'bg-blue-50 text-blue-700 font-medium'
                            : 'text-gray-600 hover:bg-gray-50'
                        }`}
                      >
                        Alle watches
                      </button>

                      {/* Collections */}
                      {myCollections && myCollections.length > 0 && (
                        <>
                          <div className="text-xs text-gray-400 uppercase tracking-wide px-3 pt-2">
                            Collecties
                          </div>
                          {myCollections.map((collection) => {
                            const watchCount = myWatches.filter(w =>
                              w.collectionIds?.includes(collection.id)
                            ).length
                            return (
                              <button
                                key={collection.id}
                                onClick={() => {
                                  setMyWatchesFilter(myWatchesFilter === collection.id ? null : collection.id)
                                  setSelectedCategory(undefined)
                                  setSelectedDomain(undefined)
                                  setPage(1)
                                }}
                                className={`block w-full text-left px-3 py-2 rounded-lg text-sm ${
                                  myWatchesFilter === collection.id
                                    ? 'bg-blue-50 text-blue-700 font-medium'
                                    : 'text-gray-600 hover:bg-gray-50'
                                }`}
                              >
                                {collection.name}
                                <span className="text-gray-400 ml-1">({watchCount})</span>
                              </button>
                            )
                          })}
                        </>
                      )}

                      {/* Back to public feed link */}
                      {myWatchesFilter !== null && (
                        <button
                          onClick={() => setMyWatchesFilter(null)}
                          className="block w-full text-left px-3 py-2 text-sm text-blue-600 hover:text-blue-700"
                        >
                          ← Terug naar alle producten
                        </button>
                      )}
                    </div>
                  )}
                </div>
              )}

              {/* Categories */}
              {categoriesData?.categories && (
                <CategorySidebar
                  categories={categoriesData.categories}
                  selectedCategory={selectedCategory}
                  onSelectCategory={(slug) => {
                    setSelectedCategory(slug)
                    setMyWatchesFilter(null)
                    setPage(1)
                  }}
                />
              )}

              {/* Domains - only show when viewing public feed */}
              {!isShowingMyWatches && (
                <div className="bg-white rounded-lg border border-gray-200 p-4">
                  <h2 className="font-semibold text-gray-900 mb-3">Webshops</h2>
                  <button
                    onClick={() => {
                      setSelectedDomain(undefined)
                      setPage(1)
                    }}
                    className={`block w-full text-left px-3 py-2 rounded-lg text-sm ${
                      !selectedDomain ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'
                    }`}
                  >
                    Alle webshops
                  </button>
                  {domainsData?.domains &&
                    Object.entries(domainsData.domains)
                      .slice(0, 10)
                      .map(([domain, count]) => (
                        <button
                          key={domain}
                          onClick={() => {
                            setSelectedDomain(domain)
                            setPage(1)
                          }}
                          className={`block w-full text-left px-3 py-2 rounded-lg text-sm ${
                            selectedDomain === domain
                              ? 'bg-blue-50 text-blue-700'
                              : 'text-gray-600 hover:bg-gray-50'
                          }`}
                        >
                          {domain}{' '}
                          <span className="text-gray-400">({count})</span>
                        </button>
                      ))}
                </div>
              )}
            </div>
          </aside>

          {/* Main content */}
          <main className="flex-1">
            {/* Header with filter info and sort */}
            <div className="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div className="flex items-center gap-2">
                {isShowingMyWatches ? (
                  <>
                    <span className="text-sm font-medium text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                      {myWatchesFilter === 'all'
                        ? 'Mijn Watches'
                        : myCollections?.find(c => c.id === myWatchesFilter)?.name || 'Collectie'}
                    </span>
                    <span className="text-sm text-gray-500">
                      {displayProducts.length} {displayProducts.length === 1 ? 'product' : 'producten'}
                    </span>
                  </>
                ) : (
                  <span className="text-sm text-gray-500">
                    {feedData?.totalCount || 0} producten
                  </span>
                )}
              </div>

              {/* Sort dropdown */}
              <div className="flex items-center gap-2">
                <label htmlFor="sort" className="text-sm text-gray-500">
                  Sorteer op:
                </label>
                <select
                  id="sort"
                  value={selectedSort}
                  onChange={(e) => {
                    setSelectedSort(e.target.value as SortOption)
                    setPage(1)
                  }}
                  className="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  {Object.entries(SORT_OPTIONS).map(([value, label]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {!isShowingMyWatches && isLoading ? (
              <div className="flex items-center justify-center py-12">
                <svg
                  className="animate-spin w-8 h-8 text-blue-600"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                  />
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                  />
                </svg>
              </div>
            ) : !isShowingMyWatches && error ? (
              <div className="text-center py-12">
                <p className="text-red-600">{error.message}</p>
              </div>
            ) : displayProducts.length === 0 ? (
              <div className="text-center py-12">
                <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg
                    className="w-12 h-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={1}
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                  </svg>
                </div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  {isShowingMyWatches ? 'Geen watches in deze collectie' : 'Nog geen producten'}
                </h3>
                <p className="text-gray-500 mb-4">
                  {isShowingMyWatches
                    ? 'Voeg watches toe aan deze collectie vanuit je dashboard.'
                    : selectedDomain || selectedCategory
                      ? `Geen producten gevonden${selectedCategory ? ` in deze categorie` : ''}${selectedDomain ? ` voor ${selectedDomain}` : ''}`
                      : 'Wees de eerste om een product toe te voegen!'}
                </p>
                {isShowingMyWatches ? (
                  <Link
                    to="/dashboard"
                    className="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    Naar dashboard
                  </Link>
                ) : (
                  <Link
                    to="/register"
                    className="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    Maak een account
                  </Link>
                )}
              </div>
            ) : (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {displayProducts.map((product) => (
                    <ProductCard
                      key={product.id}
                      product={product}
                      onSubscribe={setSubscribeProduct}
                    />
                  ))}
                </div>

                {/* Pagination - only for public feed */}
                {!isShowingMyWatches && feedData && feedData.totalPages > 1 && (
                  <div className="mt-8 flex justify-center gap-2">
                    <button
                      onClick={() => setPage((p) => Math.max(1, p - 1))}
                      disabled={page === 1}
                      className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Vorige
                    </button>
                    <span className="px-4 py-2 text-gray-600">
                      Pagina {page} van {feedData.totalPages}
                    </span>
                    <button
                      onClick={() => setPage((p) => Math.min(feedData.totalPages, p + 1))}
                      disabled={page === feedData.totalPages}
                      className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                      Volgende
                    </button>
                  </div>
                )}
              </>
            )}
          </main>
        </div>
      </div>

      {/* Subscribe Modal */}
      {subscribeProduct && (
        <SubscribeModal
          product={subscribeProduct}
          isOpen={!!subscribeProduct}
          onClose={() => setSubscribeProduct(null)}
        />
      )}
    </div>
  )
}
