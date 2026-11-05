const mix = require('laravel-mix');
const path = require('path');

require('laravel-mix-merge-manifest');

const entryPath = 'resources/frontend/cabinet';
const outputPath = 'public/static/frontend/cabinet';
const outputPathChunks = 'static/frontend/cabinet/chunks';

mix.webpackConfig({
    output: {
        filename: `[name].js`,
        chunkFilename: `${outputPathChunks}/[name].js?id=${Date.now()}`,
        publicPath: '/'
    },
});

mix.options({
    terser: { extractComments: false }
});

mix.alias({
    '@': path.join(__dirname, `../${entryPath}/js`),
    '@styles': path.join(__dirname, `../${entryPath}/scss`),
});

/* Assets */
mix.sass(`${entryPath}/scss/app.scss`, outputPath).mergeManifest();
mix.js(`${entryPath}/js/app.js`, outputPath).vue({
    globalStyles: `resources/frontend/common/scss/utils/utils.scss`
}).mergeManifest();

/* Versions */
if (mix.inProduction()) {
    mix.version();
} else {
    mix.sourceMaps()
}
