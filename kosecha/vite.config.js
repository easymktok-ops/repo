import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// base relativa: el mismo build sirve en la raíz del dominio y en una
// subcarpeta, que es lo que se necesita para subirlo a mano a un hosting.
export default defineConfig({
  base: './',
  plugins: [react()],
  build: {
    assetsInlineLimit: 0,
  },
})
