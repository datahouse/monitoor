'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:DisclaimerCtrl
 * @description
 * # DisclaimerCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('DisclaimerCtrl', function ($scope, $rootScope, auth) {
    $rootScope.headerTitle = 'disclaimer.title';
    $scope.isLoggedIn = auth.isLoggedIn();

    $scope.awesomeThings = [
      'HTML5 Boilerplate',
      'AngularJS',
      'Karma'
    ];

  });
