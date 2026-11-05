const mix = require('laravel-mix');

require('laravel-mix-merge-manifest');

const entryPath = 'resources/admin';
const outputPath = 'public/admin';

mix.options({
    terser: { extractComments: false }
});

/* Assets */
mix.sass(`${entryPath}/sass/app.scss`, `${outputPath}/css`).mergeManifest();
mix.js(`${entryPath}/js/app.js`, `${outputPath}/js`).mergeManifest();
mix.js(`${entryPath}/js/users/users.js`, `${outputPath}/js/users/users.min.js`).mergeManifest();
mix.js(`${entryPath}/js/users/requestRefill.js`, `${outputPath}/js/users/requestRefill.min.js`).mergeManifest();
mix.js(`${entryPath}/js/courses/modules/order.js`, `${outputPath}/js/courses/modules`).mergeManifest();
mix.js(`${entryPath}/js/courses/lessons/order.js`, `${outputPath}/js/courses/lessons`).mergeManifest();

/* Versions */
if (mix.inProduction()) {
    mix.version();
} else {
    mix.sourceMaps()
}
