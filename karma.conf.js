'use strict';

module.exports = function (config) {
    config.set({

        // base path, that will be used to resolve files and exclude
        basePath: '',

        // frameworks to use
        frameworks: ['jasmine', 'browserify'],

        // list of files / patterns to load in the browser
        files: [
            'static/js/components/jquery/dist/jquery.min.js',
            'static/js/components/jquery-ui/jquery-ui.min.js',
            'static/js/components/angular/angular.min.js',
            'static/js/components/angular-dragdrop/src/angular-dragdrop.min.js',
            'static/js/components/angular-sanitize/angular-sanitize.min.js',
            'static/js/components/angular-mocks/angular-mocks.js',
            './js/angular-app.js',
            './js/controllers.js',
            './js/services.js',
            './js/directives.js',
            './js/tests/controllers/*.js',
            './js/tests/directives/*.js',
            './js/tests/services/*.js'
        ],

        // list of files to exclude
        exclude: [],

        // test results reporter to use
        // possible values: 'dots', 'progress', 'junit', 'growl', 'coverage'
        reporters: ['progress'],

        // web server port
        port: 9876,

        // enable / disable colors in the output (reporters and logs)
        colors: true,

        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,

        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: false,

        // Start these browsers, currently available:
        // - Chrome
        // - ChromeCanary
        // - Firefox
        // - Opera (has to be installed with `npm install karma-opera-launcher`)
        // - Safari (only Mac; has to be installed with `npm install karma-safari-launcher`)
        // - PhantomJS
        // - IE (only Windows; has to be installed with `npm install karma-ie-launcher`)
        browsers: ['PhantomJS', 'Firefox', 'Chrome'],

        // If browser does not capture in given timeout [ms], kill it
        captureTimeout: 60000,

        // Continuous Integration mode
        // if true, it capture browsers, run tests and exit
        singleRun: true
    });
};
