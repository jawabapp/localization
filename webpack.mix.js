// webpack.mix.js

let mix = require('laravel-mix');

mix.setPublicPath('public');
mix.js('resources/js/app.js', 'public');
mix.sass('resources/sass/app.scss', 'public');
mix.version();