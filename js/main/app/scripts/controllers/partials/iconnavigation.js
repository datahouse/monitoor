'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:IconnavigationCtrl
 * @description
 * # IconnavigationCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('IconNavigationCtrl', function ($scope, $translate, $cookies, auth) {
    $scope.auth = auth;

    $scope.languages = [
      { name: 'init.navLang.de', lang: 'de' },
      { name: 'init.navLang.en', lang: 'en' }
    ];

    $scope.currentLanguage = $translate.use();
    $scope.setLanguage = function(language) {
      $translate.use(language);
    };

    $scope.isActive = function(lang) {
      return ($translate.use() === lang);
    };
  });
