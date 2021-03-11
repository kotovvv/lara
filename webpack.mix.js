const mix = require('laravel-mix');


const LiveReloadPlugin = require('webpack-livereload-plugin');

const VuetifyLoaderPlugin = require('vuetify-loader/lib/plugin');

// const CaseSensitivePathsPlugin = require('case-sensitive-paths-webpack-plugin');

var webpackConfig = {
    module: {
        rules: [{
            test: /\.js?$/,
            use: [{
                loader: 'babel-loader',
                // options: mix.config.babel()
            }]
        }]
    },
    plugins: [
        new VuetifyLoaderPlugin(),
        new LiveReloadPlugin
        // new CaseSensitivePathsPlugin(),
    ]
}

mix.webpackConfig(webpackConfig);

mix.js('resources/js/app.js', 'public/js')
    .vue()
    .sass('resources/sass/app.scss', 'public/css');
mix.js('resources/js/admin.js', 'public/js')
    .vue();
//     .sass('resources/sass/app.scss', 'public/css');

