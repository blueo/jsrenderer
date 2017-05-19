/*
    ./webpack.config.js
*/
const path = require('path');
module.exports = {
  entry: {
    index: './src/index.js',
    worker: './src/worker.js',
  },
  output: {
    path: path.resolve('dist'),
    filename: '[name].js'
  },
  module: {
    loaders: [
      { test: /\.js$/, loader: 'babel-loader', exclude: /node_modules/ },
      { test: /\.jsx$/, loader: 'babel-loader', exclude: /node_modules/ }
    ]
  }
}
