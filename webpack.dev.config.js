var path = require('path')
var webpack = require('webpack')

module.exports = {
  devtool: 'cheap-module-eval-source-map',
  devServer: {
    hot: true,
    contentBase: './'
  },
  entry: [
    'webpack-dev-server/client?http://localhost:8080/',
    'webpack/hot/dev-server',
    'babel-polyfill',
    'whatwg-fetch',
    './index'
  ],
  output: {
    path: path.join(__dirname, 'dist'),
    filename: 'bundle.js',
    publicPath: '/dist/'
  },
  plugins: [
    new webpack.HotModuleReplacementPlugin(),
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
      loaders: ['react-hot', 'babel?presets[]=react,presets[]=es2015'],
      exclude: /node_modules/,
      include: __dirname
    }, {
      test: /\.css?$/,
      loaders: [ 'style', 'css' ],
      include: __dirname
    }, {
      test: /\.less$/, 
      loader: 'style!css!less'
    }, { 
      test: /\.(woff2|woff|ttf|svg|eot)$/, 
      loader: 'file' 
    }, { 
      test: /\.(png|jpg|gif)$/, 
      loader: 'url-loader?limit=25000',
      include: '/img/' 
    }]
  }
}