var path = require('path')
var webpack = require('webpack')

module.exports = {
  devtool: 'cheap-module-eval-source-map',
  entry: [
    'babel-polyfill',
    'whatwg-fetch',
    path.join(__dirname, '/resources/assets/jsx/index.js')
  ],
  output: {
    path: path.join(__dirname, '/public/jsx/'),
    filename: 'admin.js',
    publicPath: '/public/jsx/'
  },
  plugins: [
    new webpack.optimize.OccurenceOrderPlugin(),
    new webpack.NoErrorsPlugin(),
    new webpack.ProvidePlugin({
        'fetch': 'imports?this=>global!exports?global.fetch!whatwg-fetch',
        $: "jquery",
        jQuery: "jquery"
    })
  ],
  module: {
    loaders: [{
      test: /\.js$/,
      loaders: ['babel?presets[]=react,presets[]=es2015'],
      exclude: /node_modules/,
      include: path.join(__dirname, '/resources/assets/jsx/')
    }]
  }
}