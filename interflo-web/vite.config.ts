import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    // ⚠️ Les ports 5173/5174 sont utilisés par d'autres applications locales
    // du PO. La console web écoute sur un port réservé, avec `strictPort` pour
    // échouer explicitement s'il est occupé (plutôt que de glisser au suivant).
    port: 5175,
    strictPort: true,
  },
})
