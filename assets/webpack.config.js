const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

const isDev = process.env.NODE_ENV === 'development';

module.exports = {
  mode: isDev ? 'development' : 'production',
  
  entry: {
    // Frontend assets
    'frontend': './assets/src/js/frontend.js',
    'frontend-style': './assets/src/scss/frontend.scss',
    
    // Admin assets
    'admin': './assets/src/js/admin.js',
    'admin-style': './assets/src/scss/admin.scss',
    
    // Test form assets
    'test-form': './assets/src/js/test-form.js',
    'test-form-style': './assets/src/scss/test-form.scss',
    
    // Result viewer assets
    'result-viewer': './assets/src/js/result-viewer.js',
    'result-viewer-style': './assets/src/scss/result-viewer.scss',
    
    // User panel assets
    'user-panel': './assets/src/js/user-panel.js',
    'user-panel-style': './assets/src/scss/user-panel.scss'
  },
  
  output: {
    path: path.resolve(__dirname, 'assets/dist'),
    filename: 'js/[name].js',
    publicPath: '/wp-content/plugins/club-psychology-pro/assets/dist/',
    clean: true
  },
  
  module: {
    rules: [
      // JavaScript
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', {
                targets: {
                  browsers: ['> 1%', 'last 2 versions', 'ie >= 11']
                }
              }]
            ],
            plugins: [
              '@babel/plugin-proposal-class-properties',
              '@babel/plugin-proposal-object-rest-spread'
            ]
          }
        }
      },
      
      // SCSS/CSS
      {
        test: /\.s[ac]ss$/i,
        use: [
          isDev ? 'style-loader' : MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: isDev,
              importLoaders: 2
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: isDev,
              postcssOptions: {
                plugins: [
                  require('autoprefixer'),
                  require('cssnano')({
                    preset: 'default'
                  })
                ]
              }
            }
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: isDev,
              sassOptions: {
                outputStyle: isDev ? 'expanded' : 'compressed'
              }
            }
          }
        ]
      },
      
      // Images
      {
        test: /\.(png|jpe?g|gif|svg)$/i,
        type: 'asset/resource',
        generator: {
          filename: 'images/[name][ext]'
        }
      },
      
      // Fonts
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/i,
        type: 'asset/resource',
        generator: {
          filename: 'fonts/[name][ext]'
        }
      }
    ]
  },
  
  plugins: [
    new CleanWebpackPlugin(),
    
    new MiniCssExtractPlugin({
      filename: 'css/[name].css',
      chunkFilename: 'css/[id].css'
    }),
    
    ...(isDev ? [
      new BrowserSyncPlugin({
        proxy: 'http://localhost:8000', // Ajustar según tu setup local
        files: [
          '**/*.php',
          'assets/dist/**/*'
        ],
        reload: false
      })
    ] : [])
  ],
  
  optimization: {
    minimize: !isDev,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          compress: {
            drop_console: !isDev
          }
        }
      }),
      new OptimizeCSSAssetsPlugin()
    ],
    
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          chunks: 'all',
          filename: 'js/vendors.js'
        }
      }
    }
  },
  
  resolve: {
    extensions: ['.js', '.jsx', '.scss', '.css'],
    alias: {
      '@': path.resolve(__dirname, 'assets/src'),
      '@js': path.resolve(__dirname, 'assets/src/js'),
      '@scss': path.resolve(__dirname, 'assets/src/scss'),
      '@images': path.resolve(__dirname, 'assets/src/images')
    }
  },
  
  devtool: isDev ? 'source-map' : false,
  
  devServer: {
    static: {
      directory: path.join(__dirname, 'assets/dist')
    },
    compress: true,
    port: 8080,
    hot: true,
    watchFiles: ['**/*.php']
  },
  
  stats: {
    colors: true,
    hash: false,
    version: false,
    timings: true,
    assets: false,
    chunks: false,
    modules: false,
    reasons: false,
    children: false,
    source: false,
    errors: true,
    errorDetails: true,
    warnings: true,
    publicPath: false
  }
};