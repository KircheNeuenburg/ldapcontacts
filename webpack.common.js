const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')
const StyleLintPlugin = require('stylelint-webpack-plugin')
const packageJson = require('./package.json')
const appName = packageJson.name
const ESLintPlugin = require('eslint-webpack-plugin')

module.exports = {
	entry: {
		main: path.join(__dirname, 'src', 'main.js'),
		settingsPersonal: path.join(__dirname, 'src', 'settingsPersonal.js'),
		settings: path.join(__dirname, 'src', 'settings.js'),
		statistics: path.join(__dirname, 'src', 'statistics.js')
	},
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: `${appName}_[name].js`,
		chunkFilename: 'chunks/[name]-[hash].js'
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader']
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader']
			},
			{
				test: /\.svg$/,
				use: ['ignore-loader']
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				exclude: /node_modules/
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: [ /node_modules/, /\.min\.js$/ ]
			}
		]
	},
	plugins: [
		new VueLoaderPlugin(),
		new StyleLintPlugin(),
		new ESLintPlugin({
			 extensions: ['js', 'vue'],
			 exclude: [ 'node_modules', 'src/*.min.js' ],
		})
	],
	resolve: {
		extensions: ['*', '.js', '.vue']
	}
}
