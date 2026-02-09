import { Link } from 'react-router-dom'
import { useHomepage } from '../hooks/useDiscover'
import { useAuth } from '../contexts/AuthContext'
import CollectionCard from '../components/CollectionCard'
import UserCard from '../components/UserCard'

export default function HomePage() {
  const { data: homepageData, isLoading: isLoadingHomepage } = useHomepage()
  const { user } = useAuth()

  return (
    <div className="min-h-screen bg-white">
      {/* Header */}
      <header className="absolute top-0 left-0 right-0 z-10">
        <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
          <Link to="/" className="text-2xl font-bold text-primary-600">
            ShopQ
          </Link>
          <nav className="flex items-center gap-6">
            <Link to="/" className="text-gray-600 hover:text-gray-900 font-medium">
              Producten
            </Link>
            <Link to="/discover" className="text-gray-600 hover:text-gray-900 font-medium">
              Ontdek
            </Link>
            {user ? (
              <Link
                to="/dashboard"
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium"
              >
                Dashboard
              </Link>
            ) : (
              <div className="flex items-center gap-3">
                <Link to="/login" className="text-gray-600 hover:text-gray-900 font-medium">
                  Inloggen
                </Link>
                <Link
                  to="/register"
                  className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium"
                >
                  Registreren
                </Link>
              </div>
            )}
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="relative overflow-hidden bg-gradient-to-b from-primary-50 to-white pt-16">
        <div className="max-w-5xl mx-auto px-4 py-20 md:py-32">
          <div className="text-center">
            <p className="text-primary-600 font-medium tracking-wide uppercase text-sm mb-4">
              Koop op het juiste moment
            </p>
            <h1 className="text-4xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
              ShopQ helpt je beslissen<br />
              <span className="text-primary-600">wanneer je koopt.</span>
            </h1>
            <p className="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
              Volg prijzen van producten bij Nederlandse webshops en koop wanneer het klopt.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/register"
                className="px-8 py-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium text-lg shadow-lg shadow-primary-600/20"
              >
                Begin met volgen
              </Link>
              <a
                href="#hoe-het-werkt"
                className="px-8 py-4 border-2 border-gray-200 text-gray-700 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition font-medium text-lg"
              >
                Zo werkt het
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* Value Proposition */}
      <section className="py-20 bg-white">
        <div className="max-w-4xl mx-auto px-4 text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-12">
            Minder twijfel. Betere aankopen.
          </h2>
          <div className="space-y-6 text-xl text-gray-600 max-w-2xl mx-auto">
            <p>Je weet wat iets kost.</p>
            <p>Je ziet wat er gebeurt.</p>
            <p>Je koopt wanneer jij er klaar voor bent.</p>
          </div>
          <p className="mt-10 text-lg text-gray-500 italic">
            ShopQ geeft je timing — jij maakt de deal.
          </p>
        </div>
      </section>

      {/* Why People Use ShopQ */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-5xl mx-auto px-4">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Waarom mensen ShopQ gebruiken
            </h2>
            <p className="text-xl text-gray-500">Omdat timing geld waard is</p>
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="bg-white p-8 rounded-2xl shadow-sm">
              <div className="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-6">
                <svg className="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-3">
                Prijsinzicht
              </h3>
              <p className="text-gray-600">
                Je ziet of een prijs stabiel, dalend of stijgend is
              </p>
            </div>
            <div className="bg-white p-8 rounded-2xl shadow-sm">
              <div className="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-6">
                <svg className="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-3">
                Tijd besparen
              </h3>
              <p className="text-gray-600">
                Je hoeft niet steeds opnieuw te checken
              </p>
            </div>
            <div className="bg-white p-8 rounded-2xl shadow-sm">
              <div className="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-6">
                <svg className="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-3">
                Vertrouwen
              </h3>
              <p className="text-gray-600">
                Je koopt met vertrouwen, niet op hoop
              </p>
            </div>
          </div>
          <p className="text-center mt-12 text-lg text-gray-500 font-medium">
            Geen haast. Geen ruis. Wel actie.
          </p>
        </div>
      </section>

      {/* Use Case Section */}
      <section className="py-20 bg-white">
        <div className="max-w-4xl mx-auto px-4">
          <div className="md:flex md:items-center md:gap-16">
            <div className="md:w-1/2 mb-10 md:mb-0">
              <h2 className="text-3xl font-bold text-gray-900 mb-6">
                Je staat op het punt om te kopen.
              </h2>
              <p className="text-xl text-gray-500 mb-8">
                Niet vandaag, misschien morgen. Of volgende week.
              </p>
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg className="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <p className="text-gray-700">houdt de prijs in de gaten</p>
                </div>
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg className="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <p className="text-gray-700">laat je weten wanneer het interessant wordt</p>
                </div>
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg className="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <p className="text-gray-700">helpt je kiezen wanneer je afrekent</p>
                </div>
              </div>
            </div>
            <div className="md:w-1/2">
              <div className="bg-gray-50 rounded-2xl p-8 border border-gray-100">
                <p className="text-xl text-gray-700 leading-relaxed">
                  Zo koop je met rust — en op het juiste moment.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section id="hoe-het-werkt" className="py-20 bg-gray-50">
        <div className="max-w-4xl mx-auto px-4">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-16">
            Hoe het werkt
          </h2>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="text-center">
              <div className="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                1
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Voeg een product toe
              </h3>
              <p className="text-gray-500">
                Plak de URL van het product dat je wilt volgen
              </p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                2
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                ShopQ volgt de prijs
              </h3>
              <p className="text-gray-500">
                Wij checken regelmatig en houden je op de hoogte
              </p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                3
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Jij koopt wanneer het moment daar is
              </h3>
              <p className="text-gray-500">
                Krijg een melding bij prijswijzigingen
              </p>
            </div>
          </div>
          <p className="text-center mt-12 text-gray-500 italic">
            Meer hoeft het niet te zijn.
          </p>
        </div>
      </section>

      {/* Trending Products Section */}
      {!isLoadingHomepage && homepageData?.trendingProducts && homepageData.trendingProducts.length > 0 && (
        <section className="py-20 bg-white">
          <div className="max-w-6xl mx-auto px-4">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-2xl md:text-3xl font-bold text-gray-900">
                Trending producten
              </h2>
              <Link
                to="/discover"
                className="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
              >
                Bekijk meer
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
              {homepageData.trendingProducts.slice(0, 4).map((product) => (
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
                        <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                  <div className="p-3">
                    <h3 className="font-medium text-gray-900 line-clamp-2 text-sm">
                      {product.productName}
                    </h3>
                    {product.currentPrice && (
                      <p className="text-lg font-bold text-gray-900 mt-1">
                        &euro; {product.currentPrice}
                      </p>
                    )}
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Recent Collections Section */}
      {!isLoadingHomepage && homepageData?.recentCollections && homepageData.recentCollections.length > 0 && (
        <section className="py-20 bg-gray-50">
          <div className="max-w-6xl mx-auto px-4">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-2xl md:text-3xl font-bold text-gray-900">
                Nieuwe collecties
              </h2>
              <Link
                to="/discover"
                className="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
              >
                Bekijk meer
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {homepageData.recentCollections.slice(0, 3).map((collection) => (
                <CollectionCard key={collection.id} collection={collection} />
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Active Users Section */}
      {!isLoadingHomepage && homepageData?.activeUsers && homepageData.activeUsers.length > 0 && (
        <section className="py-20 bg-white">
          <div className="max-w-6xl mx-auto px-4">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-2xl md:text-3xl font-bold text-gray-900">
                Actieve gebruikers
              </h2>
              <Link
                to="/discover"
                className="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
              >
                Bekijk meer
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
              {homepageData.activeUsers.slice(0, 4).map((user) => (
                <UserCard key={user.id} user={user} showFollowButton={false} />
              ))}
            </div>
          </div>
        </section>
      )}

      {/* CTA Section */}
      <section className="py-20 bg-primary-600">
        <div className="max-w-3xl mx-auto px-4 text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
            Weet wanneer je koopt
          </h2>
          <p className="text-xl text-primary-100 mb-10">
            Start met volgen en neem beslissingen met zekerheid.
          </p>
          <Link
            to="/register"
            className="inline-block px-10 py-4 bg-white text-primary-600 rounded-lg hover:bg-primary-50 transition font-semibold text-lg shadow-lg"
          >
            Gratis starten
          </Link>
          <p className="mt-6 text-primary-200 text-sm">
            Binnen een minuut klaar. Daarna werkt ShopQ voor je.
          </p>
        </div>
      </section>

      {/* Closing Statement */}
      <section className="py-16 bg-white">
        <div className="max-w-3xl mx-auto px-4 text-center">
          <p className="text-2xl text-gray-700 leading-relaxed">
            Goede aankopen voelen niet impulsief.<br />
            <span className="font-semibold text-gray-900">Ze voelen goed getimed.</span>
          </p>
        </div>
      </section>
    </div>
  )
}
