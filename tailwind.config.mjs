/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,ts,tsx}'],
  theme: {
    extend: {
      colors: {
        // ── Paleta primaria de marca ─────────────────────────────
        yellow: {
          DEFAULT: '#F4B400', // Primario marca — dominante
          dk: '#C49000', // Hover / sombras
          lt: '#FFE066', // Backgrounds suaves / badges
        },
        cream: '#FFF9E6', // Fondos alternativos suaves
        ink: '#1D1D1D', // Texto / contraste fuerte
        // ── Colores terciarios (romper el amarillo, tipo Starface) ─
        // Inspirados en el amanecer sobre Teotihuacán.
        sky: '#7FC7E8', // cielo del amanecer
        coral: '#FF8A5B', // sol naciente
        mint: '#6FD8A4', // valle
        grape: '#B98BD6', // lavanda / altura
        rose: '#FF6FA5', // romántico (paquete LOVE)
      },
      fontFamily: {
        display: ['Bangers', 'cursive'],
        // "Ahkio" no está en Google Fonts; usamos Fredoka como equivalente
        // amistoso/redondeado con carácter para subheadings (ver global.css).
        sub: ['Fredoka', 'system-ui', 'sans-serif'],
        body: ['Inter', 'system-ui', 'sans-serif'],
      },
      letterSpacing: {
        display: '0.04em',
      },
      boxShadow: {
        card: '0 10px 30px -12px rgba(29,29,29,0.18)',
        cardHover: '0 18px 40px -12px rgba(244,180,0,0.45)',
        pop: '6px 6px 0 0 #1D1D1D',
      },
      borderRadius: {
        blob: '2rem',
      },
      keyframes: {
        bob: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-3px)' },
        },
        floaty: {
          '0%, 100%': { transform: 'translateY(0) rotate(-2deg)' },
          '50%': { transform: 'translateY(-10px) rotate(2deg)' },
        },
      },
      animation: {
        bob: 'bob 2s ease-in-out infinite',
        floaty: 'floaty 6s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
