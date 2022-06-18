let mix = require('webpack-mix');

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .copyDirectory('resources/images', 'public/images')
    .copyDirectory('resources/favicon', 'public/favicon')
    .copyDirectory('resources/fonts', 'public/fonts')
    .sourceMaps();