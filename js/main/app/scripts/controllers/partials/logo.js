'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:LogoCtrl
 * @description
 * # LogoCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('LogoCtrl', function ($scope, $location, $timeout, auth) {
    $scope.auth = auth;

    $scope.logoLink = function() {
      var link = '/';
      if ($scope.auth.isLoggedIn()) {
        link = '/dashboard';
      }

      $timeout(function() {
        $location.path(link);
      }, 0);
    };
  });
