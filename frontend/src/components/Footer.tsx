import { Link } from 'react-router-dom'

export default function Footer() {
  return (
    <footer className="border-t bg-white mt-auto">
      <div className="max-w-7xl mx-auto px-4 py-6">
        <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
          <div className="text-sm text-gray-500">
            &copy; {new Date().getFullYear()} ShopQ. Alle rechten voorbehouden.
          </div>
          <nav className="flex gap-6 text-sm text-gray-500">
            <Link to="/privacy" className="hover:text-primary-600 transition">
              Privacybeleid
            </Link>
            <Link to="/terms" className="hover:text-primary-600 transition">
              Algemene Voorwaarden
            </Link>
            <Link to="/contact" className="hover:text-primary-600 transition">
              Contact
            </Link>
          </nav>
        </div>
      </div>
    </footer>
  )
}
