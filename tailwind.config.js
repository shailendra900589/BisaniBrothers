/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './admin/**/*.php',
    './includes/**/*.php',
    './assets/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Mulish', 'sans-serif'],
      },
      colors: {
        brand: {
          cyan: '#2fcaf0',
          red: '#cb595c',
          darkBlue: '#173978',
        },
      },
      boxShadow: {
        soft: '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
      },
    },
  },
  plugins: [],
};
