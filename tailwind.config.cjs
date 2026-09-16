const color = (name, fallback) => `rgb(var(--zem-${name}, ${fallback}) / <alpha-value>)`;

module.exports = {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './app/**/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        zem: {
          bg: color('bg', '248 250 252'),
          card: color('card', '255 255 255'),
          gold: color('accent-rgb', '210 38 48'),
          cream: color('text', '0 0 0'),
          muted: color('muted', '71 84 103'),
          border: color('border', '216 224 231'),
          soft: color('soft', '238 243 247'),
          green: '#16a34a',
          red: '#D22630',
          redDark: '#A71D2A',
          coral: '#D22630',
          ink: color('text', '0 0 0'),
          navy: color('text', '0 0 0'),
          charcoal: color('text', '0 0 0'),
          porcelain: color('bg', '248 250 252'),
        },
      },
      fontFamily: {
        sans: ['Inter', 'Noto Sans Ethiopic', 'ui-sans-serif', 'system-ui'],
        display: ['Sora', 'Noto Sans Ethiopic', 'Inter', 'ui-sans-serif'],
      },
    },
  },
};
