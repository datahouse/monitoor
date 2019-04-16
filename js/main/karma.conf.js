// Karma configuration
// http://karma-runner.github.io/0.12/config/configuration-file.html
// Generated on 2015-04-07 using
// generator-karma 0.9.0

module.exports = function(config) {
  'use strict';

  config.set({
    // enable / disable watching file and executing tests whenever any file changes
    autoWatch: false,

    // FILES: have to be loaded in gulpfile.js

    // base path, that will be used to resolve files and exclude
    basePath: '',

    // testing framework to use (jasmine/mocha/qunit/...)
    frameworks: ['jasmine'],

    // list of files / patterns to exclude
    exclude: [
    ],

    // web server port
    port: 9876,

    // Start these browsers, currently available:
    // - Chrome
    // - ChromeCanary
    // - Firefox
    // - Opera
    // - Safari (only Mac)
    // - PhantomJS
    // - IE (only Windows)
    browsers: [
      'PhantomJS'
    ],

    // Which plugins to enable
    plugins: [
      'karma-phantomjs-launcher',
      'karma-chrome-launcher',
      'karma-firefox-launcher',
      'karma-jasmine',
      'karma-junit-reporter',
      'karma-coverage'
    ],

    preprocessors: {
      'app/scripts/controllers/**/*.js': ['coverage'],
      'app/scripts/services/**/*.js': ['coverage']
    },

    // Continuous Integration mode
    // if true, it capture browsers, run tests and exit
    singleRun: true,
    reporters: ['dots', 'junit', 'coverage'],
    junitReporter: {
      outputFile: 'build/logs/test-results.xml'
    },
    coverageReporter: {
      dir: 'build/logs/',
      reporters: [
        { type : 'cobertura', subdir : '.', file: 'coverage.xml' },
        { type : 'html', subdir : './coverage/' }
      ]
    },

    // level of logging
    // possible values: LOG_DISABLE || LOG_ERROR || LOG_WARN || LOG_INFO || LOG_DEBUG
    logLevel: config.LOG_INFO

    // Uncomment the following lines if you are using grunt's server to run the tests
    // proxies: {
    //   '/': 'http://localhost:9000/'
    // },
    // URL root prevent conflicts with the site root
    // urlRoot: '_karma_'
  });
};
