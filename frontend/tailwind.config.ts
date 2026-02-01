import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './app/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        // Neon Synthwave palette
        'neon-pink': '#ff2d95',
        'neon-cyan': '#00f0ff',
        'neon-purple': '#9d4edd',
        'neon-orange': '#ff6b35',
        // Primary/accent aliases
        primary: '#00f0ff',
        'primary-dark': '#00c8d4',
        accent: '#ff2d95',
        // Dark backgrounds
        dark: {
          900: '#0d0d12',
          800: '#15151d',
          700: '#1a1a25',
          600: '#252530',
          500: '#303040',
          400: '#404055',
        },
        // Text colors
        'text-dim': '#6a6a7a',
        // Campfire Tapes palette
        campfire: {
          earth: '#1c1a17',
          soil: '#252220',
          clay: '#2d2a26',
          sand: '#3a3632',
          amber: '#d4a060',
          ochre: '#c08a40',
          rust: '#a85a38',
          teal: '#5a8a7a',
          cream: '#f5f0e8',
          'cream-aged': '#ebe5d8',
          parchment: '#e0d8c8',
          text: '#e8e0d4',
          'text-body': '#c8c0b4',
          muted: '#9a9488',
          dim: '#7a7468',
        },
      },
      fontFamily: {
        display: ['var(--font-orbitron)', 'sans-serif'],
        mono: ['var(--font-space-mono)', 'monospace'],
        'bebas-neue': ['var(--font-bebas-neue)', 'Impact', 'sans-serif'],
        serif: ['Georgia', 'serif'],
        sans: ['system-ui', 'sans-serif'],
      },
      animation: {
        'pulse-subtle': 'pulse-subtle 2s ease-in-out infinite',
        'float': 'float 8s ease-in-out infinite',
        'float-reverse': 'float 10s ease-in-out infinite reverse',
        'border-glow': 'border-glow 3s linear infinite',
        'pulse-neon': 'pulse-neon 2s ease-in-out infinite',
        'blink': 'blink 1s ease-in-out infinite',
        // Toast animations
        'toast-slide-in': 'toast-slide-in 0.3s ease-out',
        'toast-fade-out': 'toast-fade-out 0.2s ease-in forwards',
      },
      keyframes: {
        'pulse-subtle': {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.85' },
        },
        'float': {
          '0%, 100%': { transform: 'translate(0, 0)' },
          '50%': { transform: 'translate(30px, 20px)' },
        },
        'border-glow': {
          '0%, 100%': { filter: 'drop-shadow(0 0 20px #00f0ff)' },
          '33%': { filter: 'drop-shadow(0 0 20px #ff2d95)' },
          '66%': { filter: 'drop-shadow(0 0 20px #9d4edd)' },
        },
        'pulse-neon': {
          '0%, 100%': {
            boxShadow: '0 0 10px #ff2d95, inset 0 0 10px rgba(255, 45, 149, 0.1)'
          },
          '50%': {
            boxShadow: '0 0 30px #ff2d95, inset 0 0 20px rgba(255, 45, 149, 0.2)'
          },
        },
        'blink': {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.3' },
        },
        // Toast keyframes
        'toast-slide-in': {
          '0%': {
            transform: 'translateX(100%)',
            opacity: '0'
          },
          '100%': {
            transform: 'translateX(0)',
            opacity: '1'
          },
        },
        'toast-fade-out': {
          '0%': { opacity: '1' },
          '100%': { opacity: '0' },
        },
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'neon-gradient': 'linear-gradient(135deg, #00f0ff, #ff2d95, #9d4edd)',
        'title-gradient': 'linear-gradient(180deg, #ffffff 0%, #9d4edd 100%)',
      },
    },
  },
  plugins: [],
};

export default config;
