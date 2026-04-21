const path = require('path');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');

module.exports = {
    mode: 'production', // Use 'development' for debugging
    entry: {
        main: './assets/src/js/main.js',
        ajaxFilter: './assets/src/js/ajax-filter.js',
        ajaxSlider: './assets/src/js/ajax-load-cpt.js',
    },
    output: {
        filename: '[name].js',
        chunkFilename: '[name].js', //  force clean names, not [contenthash]
        path: path.resolve(__dirname, 'assets/dist/js'),
        clean: true,
    },

    optimization: {
        minimize: true,
        splitChunks: {
            chunks: 'all',
            cacheGroups: {
                default: false, //   disable default behavior
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors', //   will output vendors.js
                    chunks: 'all',
                    enforce: true,   //  force it even if small
                },
            },
        },
    },

    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                    },
                },
            },
            {
                test: /\.(sa|sc|c)ss$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'postcss-loader',
                    'sass-loader',
                ],
            }
        ],
    },
    resolve: {
        alias: {
            slick: path.resolve(__dirname, 'node_modules/slick-carousel/slick/slick.js'),
        },
    },
    devtool: false,
    plugins: [
        new BundleAnalyzerPlugin({
            analyzerMode: 'static', //  disables the server
            openAnalyzer: false,    //  doesn't open browser
            reportFilename: 'report.html'
        })
    ]

};
