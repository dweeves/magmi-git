'use strict';

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')({
		DEBUG: false,
		pattern: '*'
	});

// Variables
var url = 'http://dagoth.bp.local/github/magmi-git/';
var magmi = 'magmi';

var paths = {
	img: magmi + '/web/images',
	js: magmi + '/web/js',
	sass: magmi + '/web/sass',
	css: magmi + '/web/css'
};

// BrowserSync task
gulp.task('browserSync', ['sass'], function() {
	plugins.browserSync.init({
		proxy: url,
		reloadOnRestart: true,
		open: false,
		rewriteRules: [{
			match: /Content-Security-Policy/,
			fn: function (match) {
				return "DISABLED-Content-Security-Policy";
			}
		}]
	});
    gulp.watch(paths.sass + '/**/*.sass', ['sass']);
	gulp.watch(paths.js + '/**/*.js').on('change', plugins.browserSync.reload);
	gulp.watch(magmi + '/**/*.php').on('change', plugins.browserSync.reload);
});

// Clean CSS task
gulp.task('cleancss', function () {
	gulp.src(paths.css + '/**/*.css')
	.pipe(plugins.plumber({
		errorHandler: function (error) {
			console.log(error.message);
			this.emit('end');
	}}))
	.pipe(plugins.cleanCss())
	.on('error', function(err) {})
	.pipe(gulp.dest(paths.css));
});

// Sass task
gulp.task('sass', function() {
	gulp.src(paths.sass + '/**/*.sass')
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