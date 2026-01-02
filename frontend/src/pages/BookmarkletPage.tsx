import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

export default function BookmarkletPage() {
  const { user } = useAuth()

  const bookmarkletCode = `javascript:(function(){var s=document.createElement('script');s.src='http://localhost:8100/api/bookmarklet.js?t='+Date.now();document.body.appendChild(s);})();`

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-4xl mx-auto px-4 py-6">
          <Link to="/dashboard" className="text-primary-600 hover:underline text-sm mb-2 inline-block">
            &larr; Terug naar dashboard
          </Link>
          <h1 className="text-2xl font-bold text-gray-900">PrijsWacht Knop</h1>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-8 space-y-8">
        {!user && (
          <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
            <Link to="/login" className="font-medium hover:underline">Log eerst in</Link> om de PrijsWacht knop te gebruiken.
          </div>
        )}

        {/* Step 1: Install */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start gap-4">
            <div className="flex-shrink-0 w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold">
              1
            </div>
            <div className="flex-1">
              <h2 className="text-lg font-semibold mb-2">Sleep de knop naar je bladwijzerbalk</h2>
              <p className="text-gray-600 mb-4">
                Sleep onderstaande knop naar je bladwijzerbalk (bookmark bar).
                Als je bladwijzerbalk niet zichtbaar is, druk op <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-sm">Ctrl+Shift+B</kbd> (Windows)
                of <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-sm">Cmd+Shift+B</kbd> (Mac).
              </p>

              <div className="flex items-center gap-4">
                <a
                  href={bookmarkletCode}
                  onClick={(e) => e.preventDefault()}
                  draggable="true"
                  className="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg font-medium shadow-lg hover:bg-primary-700 cursor-grab active:cursor-grabbing"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                  </svg>
                  PrijsWacht
                </a>
                <span className="text-gray-500 text-sm">&larr; Sleep mij!</span>
              </div>
            </div>
          </div>
        </div>

        {/* Step 2: Use */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start gap-4">
            <div className="flex-shrink-0 w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold">
              2
            </div>
            <div className="flex-1">
              <h2 className="text-lg font-semibold mb-2">Ga naar een productpagina</h2>
              <p className="text-gray-600">
                Open de productpagina van een webshop waar je de prijs wilt volgen.
                Bijvoorbeeld een TV op Coolblue of een bank bij IKEA.
              </p>
            </div>
          </div>
        </div>

        {/* Step 3: Select */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start gap-4">
            <div className="flex-shrink-0 w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold">
              3
            </div>
            <div className="flex-1">
              <h2 className="text-lg font-semibold mb-2">Klik op de PrijsWacht knop</h2>
              <p className="text-gray-600 mb-4">
                Klik op de PrijsWacht knop in je bladwijzerbalk.
                Er verschijnt een overlay waarmee je het prijselement kunt selecteren.
              </p>
              <div className="bg-gray-100 rounded-lg p-4">
                <img
                  src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='200' viewBox='0 0 400 200'%3E%3Crect fill='%23f9fafb' width='400' height='200'/%3E%3Crect fill='%23fff' x='20' y='20' width='360' height='160' rx='8'/%3E%3Crect fill='%232563eb' x='150' y='60' width='100' height='30' rx='4'/%3E%3Ctext x='200' y='80' text-anchor='middle' fill='white' font-size='12' font-family='system-ui'>€ 499,00</text%3E%3Crect fill='none' stroke='%232563eb' stroke-width='3' x='145' y='55' width='110' height='40' rx='2'/%3E%3Ctext x='200' y='130' text-anchor='middle' fill='%23666' font-size='11' font-family='system-ui'>Klik op het prijselement%3C/text%3E%3C/svg%3E"
                  alt="Voorbeeld van prijsselectie"
                  className="w-full max-w-md mx-auto"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Step 4: Confirm */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start gap-4">
            <div className="flex-shrink-0 w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold">
              4
            </div>
            <div className="flex-1">
              <h2 className="text-lg font-semibold mb-2">Bevestig en ontvang notificaties</h2>
              <p className="text-gray-600">
                Na het selecteren van de prijs word je teruggestuurd naar PrijsWacht om te bevestigen.
                Vanaf dan houden we de prijs voor je in de gaten en sturen we een e-mail als de prijs verandert.
              </p>
            </div>
          </div>
        </div>

        {/* Troubleshooting */}
        <div className="bg-gray-100 rounded-lg p-6">
          <h3 className="font-semibold mb-3">Problemen?</h3>
          <ul className="space-y-2 text-sm text-gray-600">
            <li>
              <strong>Knop slepen lukt niet?</strong> Klik met rechtermuisknop op de knop en kies "Bladwijzer toevoegen" of "Add to bookmarks".
            </li>
            <li>
              <strong>Prijs niet gevonden?</strong> Sommige webshops laden prijzen via JavaScript.
              Probeer een andere selector of voeg de watch handmatig toe via het dashboard.
            </li>
            <li>
              <strong>Andere vragen?</strong> Neem contact op via lars@munne.me
            </li>
          </ul>
        </div>
      </main>
    </div>
  )
}
