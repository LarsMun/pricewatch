import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    watch: {
      usePolling: true,
    },
  },
  build: {
    // Generate source maps for error tracking
    sourcemap: true,
    // Minification
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
    // Code splitting
    rollupOptions: {
      output: {
        manualChunks: {
          // Vendor chunk for React
          'vendor-react': ['react', 'react-dom', 'react-router-dom'],
          // Vendor chunk for data fetching
          'vendor-query': ['@tanstack/react-query'],
          // Sentry in separate chunk (loaded conditionally)
          'vendor-sentry': ['@sentry/react'],
        },
      },
    },
    // Chunk size warning limit
    chunkSizeWarningLimit: 500,
  },
})
