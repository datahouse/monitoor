'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:ImpressumCtrl
 * @description
 * # ImpressumCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('ImpressumCtrl', function ($scope, $rootScope, auth) {
    $rootScope.headerTitle = 'impressum.title';
    $scope.isLoggedIn = auth.isLoggedIn();

    $scope.awesomeThings = [
      'HTML5 Boilerplate',
      'AngularJS',
      'Karma'
    ];
  });
