import { Link } from 'react-router-dom'

export default function NotFoundPage() {
  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
      <div className="max-w-md mx-auto px-4 text-center">
        <h1 className="text-6xl font-bold text-gray-300 mb-4">404</h1>
        <h2 className="text-xl font-semibold text-gray-900 mb-2">Pagina niet gevonden</h2>
        <p className="text-gray-600 mb-8">
          De pagina die je zoekt bestaat niet of is verplaatst.
        </p>
        <Link
          to="/"
          className="inline-block bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 font-medium"
        >
          Terug naar home
        </Link>
      </div>
    </div>
  )
}
