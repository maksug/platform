import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  server: {
    // 0.0.0.0 — сервер доступен и из docker-сети, и через проброс порта на хост.
    host: true,
    port: 5173,
    strictPort: true,
    watch: {
      // dev-сервер работает в контейнере, исходники приходят bind-mount'ом с
      // Windows: нативные файловые события до Docker Desktop доходят не всегда,
      // поэтому включаем polling.
      usePolling: true,
      interval: 300,
    },
  },
})
