import { Routes, Route } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import ProtectedRoute from './components/ProtectedRoute'
import Footer from './components/Footer'
import FeedPage from './pages/FeedPage'
import PublicProductPage from './pages/PublicProductPage'
import UserProfilePage from './pages/UserProfilePage'
import VerifySubscriptionPage from './pages/VerifySubscriptionPage'
import UnsubscribePage from './pages/UnsubscribePage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import DashboardPage from './pages/DashboardPage'
import WatchDetailPage from './pages/WatchDetailPage'
import BookmarkletPage from './pages/BookmarkletPage'
import AddWatchPage from './pages/AddWatchPage'
import PrivacyPage from './pages/PrivacyPage'
import TermsPage from './pages/TermsPage'
import ContactPage from './pages/ContactPage'
import VerifyEmailPage from './pages/VerifyEmailPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import ResetPasswordPage from './pages/ResetPasswordPage'
import SettingsPage from './pages/SettingsPage'
import AdminPage from './pages/AdminPage'

function App() {
  return (
    <AuthProvider>
      <div className="min-h-screen flex flex-col">
        <div className="flex-1">
          <Routes>
            {/* Public feed pages */}
            <Route path="/" element={<FeedPage />} />
            <Route path="/product/:id" element={<PublicProductPage />} />
            <Route path="/u/:username" element={<UserProfilePage />} />
            <Route path="/verify-subscription" element={<VerifySubscriptionPage />} />
            <Route path="/unsubscribe" element={<UnsubscribePage />} />

            {/* Auth pages */}
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/verify-email" element={<VerifyEmailPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/reset-password" element={<ResetPasswordPage />} />

            {/* Legal pages */}
            <Route path="/privacy" element={<PrivacyPage />} />
            <Route path="/terms" element={<TermsPage />} />
            <Route path="/contact" element={<ContactPage />} />
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
            <Route
              path="/settings"
              element={
                <ProtectedRoute>
                  <SettingsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/admin"
              element={
                <ProtectedRoute>
                  <AdminPage />
                </ProtectedRoute>
              }
            />
          </Routes>
        </div>
        <Footer />
      </div>
    </AuthProvider>
  )
}

export default App
