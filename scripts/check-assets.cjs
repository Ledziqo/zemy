const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const escapeClassName = require('tailwindcss/lib/util/escapeClassName').default;

const root = path.resolve(__dirname, '..');
const css = fs.readFileSync(path.join(root, 'public/assets/app.css'), 'utf8');
const classes = new Set([
  '!block', '!grid', 'rotate-180', 'dark:hidden', 'dark:inline',
  'border-l-zem-gold', 'border-l-gray-400', 'border-l-yellow-400',
  'bg-zem-gold/10', 'border-zem-gold/40',
  'min-h-[56px]', 'tracking-[.2em]', 'ring-zem-gold',
]);
const views = [];
function walk(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const file = path.join(directory, entry.name);
    if (entry.isDirectory()) walk(file);
    else if (file.endsWith('.blade.php')) views.push(file);
  }
}
walk(path.join(root, 'resources/views'));
const runtimeReferences = [];
for (const file of views) {
  const source = fs.readFileSync(file, 'utf8');
  if (/cdn\.tailwindcss\.com|tailwind\.config|(?:unpkg\.com|cdn\.jsdelivr\.net).*alpinejs/.test(source)) {
    runtimeReferences.push(path.relative(root, file));
  }
  // Literal classes in JS mutations and Alpine bindings must survive compilation.
  const expressions = source.matchAll(/classList\.(?:add|remove|toggle)\(([^\n;]+)|className\s*=\s*([^\n;]+)|:class="([^"]+)"/g);
  for (const expression of expressions) {
    for (const literal of (expression[1] || expression[2] || expression[3]).matchAll(/'([^']*)'/g)) {
      if (literal[1].includes('{{')) continue;
      for (const candidate of literal[1].split(/\s+/)) {
        if (candidate && !['dark', 'active', 'completed', 'all', 'en', 'am', 'error', 'guest', 'staff', 'animate-slide-in'].includes(candidate) && !candidate.startsWith('cat-')) {
          classes.add(candidate);
        }
      }
    }
  }
}
const missing = [...classes].filter(name => !css.includes(`.${escapeClassName(name)}`));
assert.deepEqual(missing, [], `Missing dynamic utility classes: ${missing.join(', ')}`);
for (const variable of ['accent-rgb', 'bg', 'card', 'text', 'muted', 'border', 'soft']) {
  assert(css.includes(`var(--zem-${variable}`), `Missing theme variable: ${variable}`);
}
const alpineRoot = path.dirname(require.resolve('alpinejs/package.json'));
assert.deepEqual(fs.readFileSync(path.join(root, 'public/assets/alpine.min.js')), fs.readFileSync(path.join(alpineRoot, 'dist/cdn.min.js')));
console.log(`Verified ${classes.size} dynamic/critical utilities, theme variables, and pinned Alpine bytes.`);
assert.deepEqual(runtimeReferences, [], `Runtime CDN/config migration still pending in: ${runtimeReferences.join(', ')}`);
console.log(`Verified all ${views.length} Blade templates use no Tailwind/Alpine CDN runtime.`);
