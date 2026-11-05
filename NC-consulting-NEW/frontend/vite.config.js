import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      // 8000 занят сайтом nc-dd-2023, API нового проекта слушает 8010.
      '/api': 'http://127.0.0.1:8010',
    },
  },
})
