import { lazy, Suspense } from 'react'
import { Routes, Route } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import ProtectedRoute from './components/ProtectedRoute'
import Footer from './components/Footer'

// FeedPage loaded eagerly (landing page)
import FeedPage from './pages/FeedPage'

// All other pages loaded lazily
const PublicProductPage = lazy(() => import('./pages/PublicProductPage'))
const UserProfilePage = lazy(() => import('./pages/UserProfilePage'))
const UserCollectionPage = lazy(() => import('./pages/UserCollectionPage'))
const DiscoverPage = lazy(() => import('./pages/DiscoverPage'))
const VerifySubscriptionPage = lazy(() => import('./pages/VerifySubscriptionPage'))
const UnsubscribePage = lazy(() => import('./pages/UnsubscribePage'))
const LoginPage = lazy(() => import('./pages/LoginPage'))
const RegisterPage = lazy(() => import('./pages/RegisterPage'))
const DashboardPage = lazy(() => import('./pages/DashboardPage'))
const WatchDetailPage = lazy(() => import('./pages/WatchDetailPage'))
const BookmarkletPage = lazy(() => import('./pages/BookmarkletPage'))
const AddWatchPage = lazy(() => import('./pages/AddWatchPage'))
const PrivacyPage = lazy(() => import('./pages/PrivacyPage'))
const TermsPage = lazy(() => import('./pages/TermsPage'))
const ContactPage = lazy(() => import('./pages/ContactPage'))
const VerifyEmailPage = lazy(() => import('./pages/VerifyEmailPage'))
const ForgotPasswordPage = lazy(() => import('./pages/ForgotPasswordPage'))
const ResetPasswordPage = lazy(() => import('./pages/ResetPasswordPage'))
const SettingsPage = lazy(() => import('./pages/SettingsPage'))
const AdminPage = lazy(() => import('./pages/AdminPage'))
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'))

function PageSpinner() {
  return (
    <div className="flex items-center justify-center min-h-[50vh]">
      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>
  )
}

function App() {
  return (
    <AuthProvider>
      <div className="min-h-screen flex flex-col">
        <div className="flex-1">
          <Suspense fallback={<PageSpinner />}>
            <Routes>
              {/* Public feed pages */}
              <Route path="/" element={<FeedPage />} />
              <Route path="/product/:id" element={<PublicProductPage />} />
              <Route path="/u/:username" element={<UserProfilePage />} />
              <Route path="/u/:username/:slug" element={<UserCollectionPage />} />
              <Route path="/discover" element={<DiscoverPage />} />
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
                  <ProtectedRoute requiredRole="ROLE_ADMIN">
                    <AdminPage />
                  </ProtectedRoute>
                }
              />
              <Route path="*" element={<NotFoundPage />} />
            </Routes>
          </Suspense>
        </div>
        <Footer />
      </div>
    </AuthProvider>
  )
}

export default App
