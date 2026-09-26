// Tokens couleur centralisés : source unique src/config/colors.js (CONVENTIONS.md §3)
const colors = require('./src/config/colors');

/** @type {import('tailwindcss').Config} */
module.exports = {
  // Chemins des fichiers contenant des classes Tailwind (NativeWind v4)
  content: ['./App.tsx', './src/**/*.{js,jsx,ts,tsx}'],
  presets: [require('nativewind/preset')],
  theme: {
    extend: {
      colors,
    },
  },
  plugins: [],
};
