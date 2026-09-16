const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const source = path.dirname(require.resolve('alpinejs/package.json'));
const output = path.join(root, 'public/assets');
fs.mkdirSync(output, { recursive: true });
fs.copyFileSync(path.join(source, 'dist/cdn.min.js'), path.join(output, 'alpine.min.js'));
fs.copyFileSync(path.join(root, 'resources/vendor/alpine.LICENSE.md'), path.join(output, 'alpine.LICENSE.md'));
// Alpine's browser bundle embeds Vue reactivity and shared helpers.
fs.copyFileSync(path.join(path.dirname(require.resolve('@vue/reactivity/package.json')), 'LICENSE'), path.join(output, 'vue-reactivity.LICENSE'));
fs.copyFileSync(path.join(path.dirname(require.resolve('@vue/shared/package.json')), 'LICENSE'), path.join(output, 'vue-shared.LICENSE'));
