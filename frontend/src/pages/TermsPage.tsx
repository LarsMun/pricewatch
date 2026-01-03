import { Link } from 'react-router-dom'

export default function TermsPage() {
  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="max-w-3xl mx-auto px-4">
        <Link to="/" className="text-primary-600 hover:text-primary-700 mb-8 inline-block">
          &larr; Terug naar home
        </Link>

        <h1 className="text-3xl font-bold text-gray-900 mb-8">Algemene Voorwaarden</h1>

        <div className="bg-white rounded-lg shadow p-8 space-y-6 text-gray-700">
          <p className="text-sm text-gray-500">Laatst bijgewerkt: januari 2026</p>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">1. De dienst</h2>
            <p>
              ShopQ is een prijsmonitor-dienst waarmee gebruikers productprijzen op externe
              webshops kunnen volgen. ShopQ is geen webshop en verkoopt zelf geen producten.
              Alle producten worden verkocht door de betreffende webshops.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">2. Accountverantwoordelijkheid</h2>
            <p className="mb-3">Als gebruiker ben je verantwoordelijk voor:</p>
            <ul className="list-disc list-inside space-y-2">
              <li>Het geheimhouden van je inloggegevens</li>
              <li>Alle activiteiten die onder je account plaatsvinden</li>
              <li>Het verstrekken van een geldig e-mailadres</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">3. Geen garantie op data</h2>
            <p>
              Wij streven ernaar om nauwkeurige prijsinformatie te tonen, maar kunnen dit niet
              garanderen. Prijzen kunnen afwijken door technische problemen, wijzigingen op
              webshops, of vertragingen in onze monitoring. Controleer altijd de actuele prijs
              op de webshop voordat je een aankoop doet.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">4. Intellectueel eigendom</h2>
            <p>
              Productafbeeldingen, -namen en -beschrijvingen zijn eigendom van de betreffende
              webshops en/of fabrikanten. ShopQ claimt geen eigendomsrechten op content van
              externe websites. Wij tonen deze informatie uitsluitend ter referentie en linken
              altijd naar de originele bron.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">5. Aansprakelijkheid</h2>
            <p>
              ShopQ is niet aansprakelijk voor:
            </p>
            <ul className="list-disc list-inside space-y-2 mt-3">
              <li>Onjuiste of verouderde prijsinformatie</li>
              <li>Beslissingen genomen op basis van onze data</li>
              <li>Problemen met aankopen bij externe webshops</li>
              <li>Tijdelijke onbeschikbaarheid van de dienst</li>
              <li>Verlies van data door technische storingen</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">6. Toegestaan gebruik</h2>
            <p className="mb-3">Je mag ShopQ niet gebruiken voor:</p>
            <ul className="list-disc list-inside space-y-2">
              <li>Commerciele doeleinden zonder onze toestemming</li>
              <li>Het overmatig belasten van onze servers</li>
              <li>Het omzeilen van beveiligingsmaatregelen</li>
              <li>Activiteiten die de wet overtreden</li>
            </ul>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">7. Beeindiging</h2>
            <p>
              Wij behouden ons het recht voor om accounts te beindigen die deze voorwaarden
              overtreden, zonder voorafgaande waarschuwing. Je kunt je account op elk moment
              zelf verwijderen via je accountinstellingen.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">8. Wijzigingen</h2>
            <p>
              Wij kunnen deze voorwaarden wijzigen. Bij significante wijzigingen informeren
              wij je via e-mail. Voortgezet gebruik van de dienst na wijzigingen betekent
              acceptatie van de nieuwe voorwaarden.
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">9. Contact</h2>
            <p>
              Voor vragen over deze voorwaarden:{' '}
              <a href="mailto:legal@shopq.app" className="text-primary-600 hover:text-primary-700">
                legal@shopq.app
              </a>
            </p>
          </section>

          <section>
            <h2 className="text-xl font-semibold text-gray-900 mb-3">10. Toepasselijk recht</h2>
            <p>
              Op deze voorwaarden is Nederlands recht van toepassing. Geschillen worden
              voorgelegd aan de bevoegde rechter in Nederland.
            </p>
          </section>
        </div>

        <div className="mt-8 text-center text-sm text-gray-500">
          <Link to="/privacy" className="hover:text-primary-600">Privacybeleid</Link>
          {' | '}
          <Link to="/contact" className="hover:text-primary-600">Contact</Link>
        </div>
      </div>
    </div>
  )
}
