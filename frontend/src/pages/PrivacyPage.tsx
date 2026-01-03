import { Link } from 'react-router-dom'

export default function PrivacyPage() {
  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="max-w-3xl mx-auto px-4">
        <Link to="/" className="text-primary-600 hover:text-primary-700 mb-8 inline-block">
          &larr; Terug naar home
        </Link>

        <h1 className="text-3xl font-bold text-gray-900 mb-8">Privacybeleid</h1>

        <div className="bg-white rounded-lg shadow p-8 space-y-6 text-gray-700">
          <p className="text-sm text-gray-500">Laatst bijgewerkt: januari 2026</p>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">1. Wie zijn wij?</h2>
            <p>
              ShopQ is een prijsmonitor-dienst waarmee je productprijzen op Nederlandse webshops
              kunt volgen. Wij zijn geen webshop en verkopen zelf geen producten.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">2. Welke gegevens verzamelen wij?</h2>
            <ul className="list-disc list-inside space-y-2">
              <li><strong>Accountgegevens:</strong> E-mailadres en wachtwoord (versleuteld opgeslagen)</li>
              <li><strong>Watches:</strong> De product-URLs die je volgt, inclusief productnaam en prijsselector</li>
              <li><strong>Prijshistorie:</strong> Historische prijsgegevens van je gevolgde producten</li>
              <li><strong>Notificaties:</strong> Logboek van verzonden e-mailmeldingen</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">3. Waarom verzamelen wij deze gegevens?</h2>
            <ul className="list-disc list-inside space-y-2">
              <li>Om je account te beheren en te beveiligen</li>
              <li>Om prijzen te monitoren en je te notificeren bij wijzigingen</li>
              <li>Om de dienst te verbeteren en problemen op te lossen</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">4. Hoe lang bewaren wij gegevens?</h2>
            <ul className="list-disc list-inside space-y-2">
              <li><strong>Accountgegevens:</strong> Tot je je account verwijdert</li>
              <li><strong>Prijshistorie:</strong> Maximaal 90 dagen</li>
              <li><strong>Notificatie-logboek:</strong> Maximaal 90 dagen</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">5. Jouw rechten</h2>
            <p className="mb-3">Op grond van de AVG heb je de volgende rechten:</p>
            <ul className="list-disc list-inside space-y-2">
              <li><strong>Inzage:</strong> Je kunt je gegevens downloaden via je accountinstellingen</li>
              <li><strong>Correctie:</strong> Je kunt je e-mailadres wijzigen in je account</li>
              <li><strong>Verwijdering:</strong> Je kunt je account volledig verwijderen</li>
              <li><strong>Dataportabiliteit:</strong> Je kunt je gegevens exporteren als JSON-bestand</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">6. Cookies</h2>
            <p>
              Wij gebruiken alleen functionele cookies die nodig zijn voor de werking van de
              dienst, zoals het onthouden van je inlogsessie. Wij gebruiken geen tracking- of
              advertentiecookies.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">7. Beveiliging</h2>
            <p>
              Wij nemen passende technische en organisatorische maatregelen om je gegevens
              te beschermen, waaronder versleuteling van wachtwoorden en beveiligde verbindingen (HTTPS).
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">8. Contact</h2>
            <p>
              Voor vragen over je privacy kun je contact opnemen via:{' '}
              <a href="mailto:privacy@shopq.app" className="text-primary-600 hover:text-primary-700">
                privacy@shopq.app
              </a>
            </p>
          </section>
        </div>

        <div className="mt-8 text-center text-sm text-gray-500">
          <Link to="/terms" className="hover:text-primary-600">Algemene Voorwaarden</Link>
          {' | '}
          <Link to="/contact" className="hover:text-primary-600">Contact</Link>
        </div>
      </div>
    </div>
  )
}
