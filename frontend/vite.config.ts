import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig(({ mode }) => ({
  plugins: [react(), tailwindcss()],
  base: mode === 'integrated' ? '/ticketdesk/' : '/',
  server: {
    proxy: {
      '/api': 'http://127.0.0.1:8000',
    },
  },
  build: {
    outDir: mode === 'integrated' ? '../backend/public/ticketdesk' : 'dist',
    emptyOutDir: true,
  },
}))
