'use strict';

/**
 * @ngdoc overview
 * @name monApp
 * @description
 * # monApp
 *
 * Main module of the application.
 */
angular
  .module('monApp', [
    'ngAnimate',
    'ngCookies',
    'ngResource',
    'ngRoute',
    'ngSanitize',
    'ngTouch',
    'ui.bootstrap',
    'dndLists',
    'diff-match-patch',
    'angular-flash.service',
    'angular-flash.flash-alert-directive',
    'nvd3',
    'infinite-scroll',
    'ngPrettyJson',
    'pascalprecht.translate',
    'rzModule',
    'angulartics',
    'angulartics.piwik',
    'ui.select'
  ])
  .constant('appType', window.GLOBAL_APPTYPE)
  .config(function($httpProvider) {
    if (!$httpProvider.defaults.headers.get) {
        $httpProvider.defaults.headers.get = {};
    }

    $httpProvider.defaults.headers.get['If-Modified-Since'] = 'Mon, 26 Oct 1970 05:00:00 GMT';
    $httpProvider.defaults.headers.get['Cache-Control'] = 'no-cache';
    $httpProvider.defaults.headers.get['Pragma'] = 'no-cache';

    $httpProvider.interceptors.push(function($injector, $q) {
      var spinner = $injector.get('spinner');

      return {
        'request': function(config) {
          // workaround firefox bug spinner dont stop on this api call
          if (config.url !== 'api/v1/change/share') {
            spinner.start();
          }
          return config;
        },
        'requestError': function(rejection) {
          spinner.stop();
          return $q.reject(rejection);
        },
        'response': function(response) {
          spinner.stop();
          return response;
        },
        'responseError': function(rejection) {
          spinner.stop();
          return $q.reject(rejection);
        }
      };
    });
  })
  .config(function($compileProvider){
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|file|javascript):/);
  })
  .config(function($translateProvider, $translatePartialLoaderProvider) {
    $translateProvider.useSanitizeValueStrategy('sanitizeParameters');
    $translatePartialLoaderProvider.addPart('init');
    $translateProvider.useLoader('$translatePartialLoader', {
      urlTemplate: 'api/v1/i18/trans/{part}/{lang}'
    });
    $translateProvider.registerAvailableLanguageKeys(['de', 'en']);
    $translateProvider.useCookieStorage();
    $translateProvider.fallbackLanguage(['en', 'de']);
  })
  .config(function (flashProvider) {
    flashProvider.errorClassnames.push('alert-danger');
    flashProvider.warnClassnames.push('alert-warning');
    flashProvider.infoClassnames.push('alert-info');
    flashProvider.successClassnames.push('alert-success');
  })
  .config(function ($routeProvider, $analyticsProvider) {
    var resolve = {
      authInterceptor: function(auth) {
        return auth.checkLoggedIn();
      }
    };

    $routeProvider
      .when('/dashboard', {
        templateUrl: 'views/dashboard.html',
        controller: 'DashboardCtrl',
        resolve: resolve
      })
      .when('/alerts', {
        templateUrl: 'views/alerts.html',
        controller: 'AlertsCtrl',
        resolve: resolve
      })
      .when('/alerts/add/:urlGroupId', {
        templateUrl: 'views/alertadd.html',
        controller: 'AlertaddCtrl',
        resolve: resolve
      })
      .when('/alerts/edit/:alertId', {
        templateUrl: 'views/alertedit.html',
        controller: 'AlerteditCtrl',
        resolve: resolve
      })
      .when('/profile', {
        templateUrl: 'views/profile.html',
        controller: 'ProfileCtrl',
        resolve: resolve
      })
      .when('/urls', {
        templateUrl: 'views/urls.html',
        controller: 'UrlsCtrl',
        resolve: resolve
      })
      .when('/urls/add', {
        templateUrl: 'views/urladd.html',
        controller: 'UrladdCtrl',
        resolve: resolve
      })
      .when('/urls/edit/:urlId', {
        templateUrl: 'views/urledit.html',
        controller: 'UrleditCtrl',
        resolve: resolve
      })
      .when('/urlGroups/:urlGroupId?', {
        templateUrl: 'views/urlgroups.html',
        controller: 'UrlgroupsCtrl',
        resolve: resolve
      })
      .when('/login', {
        templateUrl: 'views/login.html',
        controller: 'LoginCtrl',
        resolve: resolve
      })
      .when('/logout', {
        template: '',
        controller: 'LogoutCtrl',
        resolve: resolve
      })
      .when('/passwordRecovery', {
        templateUrl: 'views/passwordrecovery.html',
        controller: 'PasswordrecoveryCtrl'
      })
      .when('/passwordReset/:token', {
        templateUrl: 'views/passwordreset.html',
        controller: 'PasswordresetCtrl'
      })
      .when('/alerts/detail/:alertId', {
        templateUrl: 'views/alertdetail.html',
        controller: 'AlertdetailCtrl',
        resolve: resolve
      })
      .when('/urlGroup/:id', {
        templateUrl: 'views/urlgroup.html',
        controller: 'UrlgroupCtrl',
        resolve: resolve
      })
      .when('/url/:id', {
        templateUrl: 'views/url.html',
        controller: 'UrlCtrl',
        resolve: resolve
      })
      .when('/developer/:api?', {
        templateUrl: 'views/developer.html',
        controller: 'DeveloperCtrl'
      })
      .when('/registration/:priceId?', {
        templateUrl: 'views/registration.html',
        controller: 'RegistrationCtrl'
      })
      .when('/activate/:hash?', {
        templateUrl: 'views/activate.html',
        controller: 'ActivateCtrl'
      })
      .when('/impressum', {
        templateUrl: 'views/impressum.html',
        controller: 'ImpressumCtrl'
      })
      .when('/disclaimer', {
        templateUrl: 'views/disclaimer.html',
        controller: 'DisclaimerCtrl'
      })
      .when('/subscribe', {
        templateUrl: 'views/subscribe.html',
        controller: 'SubscribeCtrl',
        resolve: resolve
      })
      .when('/favorite', {
        templateUrl: 'views/favorite.html',
        controller: 'FavoriteCtrl',
        resolve: resolve
      })
      .when('/share/:hash', {
        templateUrl: 'views/share.html',
        controller: 'ShareCtrl'
      })
      .otherwise({
        redirectTo: '/login'
      });
  })
  .run(function($rootScope, $location, $translate, $translatePartialLoader, $translateCookieStorage) {
    // SET Default lang
    if ($translateCookieStorage.get('NG_TRANSLATE_LANG_KEY') !== undefined) {
      $translate.use($translateCookieStorage.get('NG_TRANSLATE_LANG_KEY'));
    } else {
      $translate.use('de');
    }

    $rootScope.$on('$routeChangeStart', function () {
      var part = 'product';

      if ($location.path() !== undefined && $location.path() !== '/' && $location.path() !== '') {
        part = $location.path().match(/([a-zA-Z])([^/]*)/)[0];
      }

      $translatePartialLoader.addPart(part);
    });

    $rootScope.$on('$translatePartialLoaderStructureChanged', function () {
      $translate.refresh();
    });
  })
  .run(function($rootScope, $location, urlFlag) {
    $rootScope.$on('$routeChangeStart', function () {
      var path = $location.path();
      var search = $location.search();
      urlFlag.setFlag(path, search);
    });
  })
  .run(function(auth) {
    auth.checkLoggedIn();
  })
  .run(function(
    appType, $window, $translate, $rootScope,
    registerUrl, pushHandler, instanceHelper
  ) {
    $rootScope.showRootHeader = instanceHelper.showRootHeader();
    $rootScope.showRootFooter = instanceHelper.showRootFooter();

    if (appType === 'app') {
      $window.monitoor = {
        setLanguage: function(lang) {
          $translate.use(lang);
        },
        registerURL: registerUrl.registerUrl,
        registerPushToken: pushHandler.registerPushToken
      };
    }
  });
