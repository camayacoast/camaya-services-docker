const path = require("path");
const webpack = require("webpack");

const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
// const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const TerserPlugin = require("terser-webpack-plugin");

const HTMLWebpackPlugin = require('html-webpack-plugin');

const Dotenv = require('dotenv-webpack');

module.exports = (env, options) => {

  return {
      entry: "./resources/js/index.js",
      mode: "development",
      devtool: false,
      node: {
        fs: "empty"
      },
      module: {
        rules: [
          {
            test: /\.js$|jsx/,
            exclude: /(node_modules|bower_components)/,
            loader: "babel-loader",
            options: { presets: ["@babel/env"] }
          },
          {
              test: /\.(pdf|png|jp(e*)g)$/,  
              use: [{
                  loader: 'url-loader',
                  options: { 
                      limit: 8000, // Convert images < 8kb to base64 strings
                      name: 'assets/[hash]-[name].[ext]'
                  } 
              }]
          },
          {
              test: /\.(woff(2)?|ttf|eot)(\?v=\d+\.\d+\.\d+)?$/,
              use: [{
                  loader: 'file-loader',
                  options: {
                      name: '[name].[ext]',
                      outputPath: 'fonts/'
                  }
              }]
          },
          {
            test: /\.svg$/,
            use: [
              {
                loader: '@svgr/webpack',
                options: {
                  replaceAttrValues: { '#000000': '{props.color}' },
                  ext: 'js'
                },
              },
            ],
          },
          {
            test: /\.css$/,
            use: [
                  {
                    loader: MiniCssExtractPlugin.loader,
                    options: {
                      // you can specify a publicPath here
                      // by default it uses publicPath in webpackOptions.output
                      publicPath: '../',
                      hmr: options.mode === 'development',
                    },
                  },
                  'css-loader',
                ],
          },
          {
            test: /\.less$/,
            use: ['style-loader', 'css-loader', {
              loader: 'less-loader',
              options: { javascriptEnabled: true }
            }],
            
          },
        ]
      },
      resolve: { extensions: ["*", ".js", ".jsx"],
        modules: [
          path.resolve(__dirname, './resources/js'),
          'node_modules'
        ]
      },
      output: {
        path: path.resolve(__dirname, "public/dist/"),
        publicPath: "/dist/",
        filename: options.mode !== 'production' ?  "bundle.js" : "bundle.[hash].js",
        chunkFilename: '[chunkhash].js',
      },
      devServer: {
        contentBase: path.join(__dirname, "public/"),
        port: 3000,
        publicPath: "http://localhost:3000/dist/",
        hotOnly: true
      },
      plugins: [
          new webpack.HotModuleReplacementPlugin(),
          new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: options.mode !== 'production' ? '[name].css' : '[name].[hash].css',
            chunkFilename: options.mode !== 'production' ? '[id].css' : '[id].[hash].css',
          }),
          // new webpack.optimize.CommonsChunkPlugin({
          //   names: ['bundle']
          // }),
          new HTMLWebpackPlugin({
              title: "App",
              filename: path.join(__dirname, './resources/views/index.blade.php'),
              template: path.join(__dirname, './resources/js/index.html'),
          }),
          new Dotenv(),
      ],
      performance: {
        hints: false
      },
      watch: options.watch,
      watchOptions: {
        poll: 1000 // Check for changes every second
      },
      optimization: {
        minimize: true,
        minimizer: options.mode === 'production' ? [
          new OptimizeCSSAssetsPlugin({}),
          new TerserPlugin({
                test: /\.js$/,
                exclude: /node_modules/,
                sourceMap: true,
                terserOptions: {
                    compress: {},
                    mangle: true,
                }
          })] : [],
      },
    }
};