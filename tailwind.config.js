/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
      "./resources/**/*.blade.php",
      "./resources/**/*.js",
  ],
  theme: {
    extend: {
        fontFamily: {
            'primary': ['Roboto', 'sans-serif']
        }
    },
  },
  plugins: [],
}
