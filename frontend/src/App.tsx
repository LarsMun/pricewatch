import { Routes, Route } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import ProtectedRoute from './components/ProtectedRoute'
import HomePage from './pages/HomePage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import DashboardPage from './pages/DashboardPage'
import WatchDetailPage from './pages/WatchDetailPage'
import BookmarkletPage from './pages/BookmarkletPage'
import AddWatchPage from './pages/AddWatchPage'

function App() {
  return (
    <AuthProvider>
      <div className="min-h-screen">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <DashboardPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/watch/:id"
            element={
              <ProtectedRoute>
                <WatchDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/bookmarklet"
            element={
              <ProtectedRoute>
                <BookmarkletPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/add-watch"
            element={
              <ProtectedRoute>
                <AddWatchPage />
              </ProtectedRoute>
            }
          />
        </Routes>
      </div>
    </AuthProvider>
  )
}

export default App
