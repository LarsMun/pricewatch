import { Link } from 'react-router-dom'

export default function ContactPage() {
  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="max-w-3xl mx-auto px-4">
        <Link to="/" className="text-primary-600 hover:text-primary-700 mb-8 inline-block">
          &larr; Terug naar home
        </Link>

        <h1 className="text-3xl font-bold text-gray-900 mb-8">Contact</h1>

        <div className="bg-white rounded-lg shadow p-8 space-y-8 text-gray-700">
          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">Algemene vragen</h2>
            <p>
              Voor algemene vragen over ShopQ kun je contact opnemen via:{' '}
              <a href="mailto:info@shopq.app" className="text-primary-600 hover:text-primary-700">
                info@shopq.app
              </a>
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">Privacyvragen</h2>
            <p>
              Voor vragen over je privacy of persoonsgegevens:{' '}
              <a href="mailto:privacy@shopq.app" className="text-primary-600 hover:text-primary-700">
                privacy@shopq.app
              </a>
            </p>
          </section>

          <section className="border-t pt-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-3">
              Juridische klachten & Takedown verzoeken
            </h2>
            <p className="mb-4">
              Voor auteursrechtelijke of andere juridische klachten kun je contact opnemen via:{' '}
              <a href="mailto:legal@shopq.app" className="text-primary-600 hover:text-primary-700">
                legal@shopq.app
              </a>
            </p>

            <div className="bg-gray-50 rounded-lg p-4">
              <p className="font-medium text-gray-900 mb-3">
                Vermeld in uw bericht:
              </p>
              <ul className="list-disc list-inside space-y-2 text-gray-600">
                <li>Uw volledige contactgegevens</li>
                <li>Welke content het betreft (URL of productomschrijving)</li>
                <li>Reden waarom de content verwijderd moet worden</li>
                <li>Bewijs dat u rechthebbende bent (indien van toepassing)</li>
              </ul>
            </div>

            <p className="mt-4 text-gray-600">
              Wij reageren binnen 48 uur op juridische verzoeken.
            </p>
          </section>
        </div>

        <div className="mt-8 text-center text-sm text-gray-500">
          <Link to="/privacy" className="hover:text-primary-600">Privacybeleid</Link>
          {' | '}
          <Link to="/terms" className="hover:text-primary-600">Algemene Voorwaarden</Link>
        </div>
      </div>
    </div>
  )
}
