import { Link } from 'react-router-dom'

export default function HomePage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center p-4">
      <div className="text-center">
        <h1 className="text-4xl font-bold text-primary-600 mb-4">
          PrijsWacht
        </h1>
        <p className="text-xl text-gray-600 mb-8">
          Monitor prijzen op Nederlandse webshops
        </p>
        <div className="flex gap-4 justify-center">
          <Link
            to="/login"
            className="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
          >
            Inloggen
          </Link>
          <Link
            to="/register"
            className="px-6 py-3 border border-primary-600 text-primary-600 rounded-lg hover:bg-primary-50 transition"
          >
            Registreren
          </Link>
        </div>
      </div>
    </div>
  )
}
