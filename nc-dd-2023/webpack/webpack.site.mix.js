const mix = require('laravel-mix');

require('laravel-mix-merge-manifest');

const entryPath = 'resources/frontend/site';
const outputPath = 'public/static/frontend/site';

mix.options({
    terser: { extractComments: false }
});

/* Assets */
mix.sass(`${entryPath}/scss/app.scss`, `${outputPath}`).mergeManifest();
mix.js(`${entryPath}/js/app.js`, `${outputPath}`).mergeManifest();

/* Versions */
if (mix.inProduction()) {
    mix.version();
} else {
    mix.sourceMaps()
}
