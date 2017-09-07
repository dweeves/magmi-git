'use strict';

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')({
		DEBUG: false,
		pattern: '*'
	});

// Variables
var url = 'http://dagoth.bp.local/github/magmi-git/';
var template = 'magmi/web';

var paths = {
	img: template + '/' + 'images',
	js: template + '/' + 'js',
	sass: template + '/' + 'sass',
	css: template + '/' + 'css'
};

// BrowserSync task
gulp.task('browserSync', ['sass'], function() {
	plugins.browserSync.init({
		proxy: url,
		reloadOnRestart: true,
		open: false
	});
    gulp.watch(paths.sass + '/**/*.scss', ['sass']);
	gulp.watch(paths.js + '/**/*.js').on('change', plugins.browserSync.reload);
	gulp.watch(template + '/**/*.php').on('change', plugins.browserSync.reload);
});

// Autoprefixer task
gulp.task('autoprefixer', function () {
	gulp.src([paths.css + '/*.css', paths.css + '/**/*.css'])
	.pipe(plugins.plumber({
		errorHandler: function (error) {
			console.log(error.message);
			this.emit('end');
	}}))
	.pipe(plugins.postcss([plugins.autoprefixer()]))
	.on('error', function(err) {})
	.pipe(gulp.dest(paths.css));
});

// Sass task
gulp.task('sass', function() {
	gulp.src([paths.sass + '/*.scss', paths.sass + '/**/*.scss'])
	.pipe(plugins.plumber({
		errorHandler: function (error) {
			console.log(error.message);
			this.emit('end');
	}}))
	.pipe(plugins.sass.sync().on('error', plugins.sass.logError))
	.on('error', function(err) {})
	.pipe(plugins.csscomb())
	.pipe(gulp.dest(paths.css))
	.pipe(plugins.browserSync.stream());
});

// Default task
gulp.task('default', ['browserSync']);
