const ExtractTextWebpackPlugin = require("extract-text-webpack-plugin");
const webpack = require('webpack');
const path = require('path')
const ENV = process.env.APP_ENV;
const isTest = ENV === 'test'
const isProd = ENV === 'prod';

function setDevTool () {
  if (isTest) {
    return 'inline-source-map';
  } else if (isProd) {
    return 'source-map';
  } else {
    return 'eval-source-map';
  }
}

const config = {
  entry: {
    cms_admin: path.resolve('./assets/js/app.js'),
    cms_front: path.resolve('./assets/js/front.js')
  },
  output: {
    path: path.resolve('./src/Resources/public'),
    filename: '[name].js',
  },
  devtool: setDevTool(),
  module: {
    rules: [
      {
        test: /\.js$/,
        use: 'babel-loader',
        exclude: [
          /node_modules/
        ]
      },
      {
        test: /\.(sass|scss)$/,
        use: ExtractTextWebpackPlugin.extract({
          fallback: 'style-loader',
          use: ['css-loader', 'sass-loader'],
        })
      }
    ]
  },
  plugins: [
      new ExtractTextWebpackPlugin("[name].css")
  ]
};

module.exports = config;
