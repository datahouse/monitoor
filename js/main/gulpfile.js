'use strict';

var gulp = require('gulp');
var jshint = require('gulp-jshint');
//var jscs = require('gulp-jscs');
//var jscsStylish = require('gulp-jscs-stylish');
var less = require('gulp-less');
var minifyCss = require('gulp-minify-css');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var order = require('gulp-order');
var karma = require('gulp-karma');
var del = require('del');
var inject = require('gulp-inject');
var print = require('gulp-print');
var series = require('stream-series');

var angularLib = [
  './vendor/jquery/dist/jquery.js',
  './vendor/google-diff-match-patch/diff_match_patch.js',
  './vendor/angular/angular.js',
  './vendor/d3/d3.js',
  './vendor/nvd3/build/nv.d3.js',
  './vendor/bootstrap/dist/js/bootstrap.js',
  './vendor/angular-animate/angular-animate.js',
  './vendor/angular-cookies/angular-cookies.js',
  './vendor/angular-resource/angular-resource.js',
  './vendor/angular-route/angular-route.js',
  './vendor/angular-sanitize/angular-sanitize.js',
  './vendor/angular-touch/angular-touch.js',
  './vendor/angular-mocks/angular-mocks.js',
  './vendor/angular-bootstrap/ui-bootstrap-tpls.js',
  './vendor/angular-drag-and-drop-lists/angular-drag-and-drop-lists.js',
  './vendor/angular-diff-match-patch/angular-diff-match-patch.js',
  './vendor/angular-flash/dist/angular-flash.js',
  './vendor/angular-nvd3/dist/angular-nvd3.js',
  './vendor/ngInfiniteScroll/build/ng-infinite-scroll.js',
  './vendor/ng-prettyjson/dist/ng-prettyjson.min.js',
  './vendor/angular-translate/angular-translate.js',
  './vendor/angular-translate-loader-url/angular-translate-loader-url.js',
  './vendor/angular-translate-loader-partial/angular-translate-loader-partial.js',
  './vendor/angular-translate-storage-cookie/angular-translate-storage-cookie.js',
  './vendor/angularjs-slider/dist/rzslider.js',
  './vendor/angulartics/src/angulartics.js',
  './vendor/angulartics/src/angulartics-piwik.js',
  './vendor/angular-ui-select/dist/select.js'
];

var libOrder = [
  'vendor/jquery/dist/jquery.js',
  'vendor/angular/angular.js',
  'vendor/d3/d3.js',
  'vendor/nvd3/build/nv.d3.js',
  'vendor/angular-nvd3/dist/angular-nvd3.js',
  'vendor/angular-translate/angular-translate.js',
  'vendor/angular-translate-loader-url/angular-translate-loader-url.js',
  'vendor/angular-translate-loader-partial/angular-translate-loader-partial.js',
  'vendor/angular-translate-storage-cookie/angular-translate-storage-cookie.js',
  'vendor/**/*.js',
  'app/scripts/app.js',
  'app/scripts/services/**/*.js',
  'app/scripts/directives/**/*.js',
  'app/scripts/controllers/**/*.js',
  'test/mock/**/*.js',
  'test/spec/**/*.js'
];

gulp.task('css', ['less', 'minify-css']);
gulp.task('copy', ['copy:externals', 'copy:css', 'copy:fonts']);
gulp.task('build', ['build:angular', 'checkstyle']); // test
gulp.task('build:css', ['css', 'copy:css']);
gulp.task('build:all', ['build', 'copy', 'css', 'inject']); // test
gulp.task('clean:all', ['clean', 'clean:vendor']);

gulp.task('clean', function(cb) {
  del(['build/*', 'app/styles/*', 'app/javascripts/*', 'app/fonts/*', '!app/fonts/mon.*'], cb);
});

gulp.task('clean:vendor', function(cb) {
  del(['vendor', 'node_modules'], cb);
});

gulp.task('checkstyle', function() {
  return gulp.src(['./app/**/*.js', './test/spec/**/*.js'])
    .pipe(jshint())
    //.pipe(jscs())
    //.on('error', function(){})
    //.pipe(jscsStylish.combineWithHintResults())
    .pipe(jshint.reporter('gulp-checkstyle-jenkins-reporter', {
      filename: 'build/logs/checkstyle.xml'
    }));
});

gulp.task('less', function () {
  return gulp.src('./app/less/design.less')
    .pipe(less({
      paths: ['.']
    }))
    .pipe(gulp.dest('app/styles'));
});

gulp.task('minify-css', ['less'], function() {
  return gulp.src('./app/styles/design.css')
    .pipe(minifyCss({ compatibility: 'ie8', rebase: false }))
    .pipe(gulp.dest('app/styles'));
});

gulp.task('build:angular', function() {
  var files = angularLib.concat(['./app/scripts/**/*.js']);

  return gulp.src(files)
    .pipe(order(libOrder, { base: '.'}))
    .pipe(print())
    .pipe(concat("core.js"))
    .pipe(uglify({ mangle: false, compress: true }))
    .pipe(gulp.dest("app/javascripts"));
});

gulp.task('build-debug:angular', function() {
  var files = angularLib.concat(['./app/scripts/**/*.js']);

  return gulp.src(files)
    .pipe(order(libOrder, { base: '.'}))
    .pipe(print())
    .pipe(concat("core.js"))
    .pipe(gulp.dest("app/javascripts"));
});

gulp.task('copy:externals', function() {
  return gulp.src(
    [
      './vendor/html5shiv/dist/html5shiv.min.js',
      './vendor/respond/dest/respond.min.js'
    ])
    .pipe(gulp.dest('app/javascripts/ie'));
});

gulp.task('copy:css', function() {
  return gulp.src(
    [
      './app/less/fonts.css',
      './vendor/bootstrap/dist/css/bootstrap.min.css',
      './vendor/font-awesome/css/font-awesome.min.css',
      './vendor/nvd3/build/nv.d3.min.css',
      './vendor/ng-prettyjson/dist/ng-prettyjson.min.css',
      './vendor/angularjs-slider/dist/rzslider.min.css',
      './vendor/angular-ui-select/dist/select.min.css'
    ])
    .pipe(gulp.dest('app/styles'));
});

gulp.task('copy:fonts', function() {
  return gulp.src(
    [
      './vendor/font-awesome/fonts/**/*'
    ])
    .pipe(gulp.dest('app/fonts'));
});

gulp.task('inject', ['css', 'build:angular' ], function () {
  var jsStream = gulp.src(['app/javascripts/*.js'], { read: false });
  var minCss = gulp.src(['app/styles/**/*.min.css', 'app/styles/**/*.ext.css'], { read: false });
  var mainCss = gulp.src(['app/styles/design.css'], { read: false });

  return gulp.src('./app/index.html')
    .pipe(inject(gulp.src('app/javascripts/ie/*.js', { read: false }), {starttag: '<!-- inject:ie:{{ext}} -->', ignorePath: 'app', addRootSlash: false}))
    .pipe(inject(series(jsStream, minCss, mainCss), { ignorePath: 'app', addRootSlash: false }))
    .pipe(gulp.dest('app'));
});

gulp.task('test', function() {
  // list of files / patterns to load in the browser
  var files = [
    './app/scripts/**/*.js',
    './test/mock/**/*.js',
    './test/spec/**/*.js'
  ];

  files = angularLib.concat(files);

  // Be sure to return the stream
  return gulp.src(files)
    .pipe(order(libOrder, { base: '.' }))
    .pipe(karma({
      configFile: 'karma.conf.js',
      action: 'run'
    }))
    .on('error', function(err) {
      // Make sure failed tests cause gulp to exit non-zero
      throw err;
    });
});

gulp.task('watch', ['watch:css', 'watch:app']);

gulp.task('watch:css', function() {
  gulp.watch('app/less/**/*.less', ['css']);
});

gulp.task('watch:app', function() {
  gulp.watch('app/scripts/**/*.js', ['build:angular']);
});
