export default function DashboardPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-6">
          <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        </div>
      </header>
      <main className="max-w-7xl mx-auto px-4 py-8">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-semibold mb-4">Mijn prijswatches</h2>
          <p className="text-gray-600">
            Je hebt nog geen prijswatches. Gebruik de bookmarklet om je eerste product toe te voegen.
          </p>
        </div>
      </main>
    </div>
  )
}
