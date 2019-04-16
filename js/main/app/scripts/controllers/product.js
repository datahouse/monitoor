'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:ProductCtrl
 * @description
 * # ProductCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('ProductCtrl', function ($scope, $rootScope) {
    $rootScope.headerTitle = 'product.title';

    $scope.awesomeThings = [
      'HTML5 Boilerplate',
      'AngularJS',
      'Karma'
    ];
  });
