/** Tailwind v3 build for the standalone preview server (preview_server.py).
 *  Build: npx -p tailwindcss@3.4.17 tailwindcss -c tailwind.preview.config.js -i resources/css/preview.css -o public/css/preview.css --minify
 */
module.exports = {
  content: ['./preview_server.py'],
  // Classes assembled at runtime (not visible to the content scanner)
  safelist: ['xl:grid-cols-4', 'xl:grid-cols-5', 'lg:grid-cols-4', 'lg:grid-cols-3', 'lg:grid-cols-2'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#F1F4FC', 100: '#E2E8F8', 200: '#C5D2F0', 300: '#98AEE3', 400: '#5F7FD0',
          500: '#3A5BBE', 600: '#2D48A6', 700: '#263B8C', 800: '#243090', 900: '#1E2770', 950: '#151B4D',
        },
        accent: {
          50: '#EEF8FD', 100: '#D8F0FC', 200: '#B4E4FC', 300: '#8FCBEA', 400: '#60A8D8',
          500: '#3F90C6', 600: '#2D76AA', 700: '#235E8A', 800: '#1D4D72', 900: '#183F5D', 950: '#0E2739',
        },
      },
      fontFamily: {
        sans: ['Noto Sans', 'Figtree', 'sans-serif'],
        heading: ['Figtree', 'Noto Sans', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
