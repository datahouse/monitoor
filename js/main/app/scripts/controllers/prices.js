'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:PricesCtrl
 * @description
 * # PricesCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('PricesCtrl', function ($scope, $rootScope, $http, $location, flash) {
    $rootScope.headerTitle = 'prices.title';
    $scope.pricing = [];
    $http.get('api/v1/pricing/listing/de')
      .success(function(data) {
        $scope.pricing = data;
      })
      .error(function (data, status) {
        if (status !== 400) {
          flash.error = data.msg.join();
        } else {
          $scope.error = data.msg;
        }
      });

    $scope.registration = function(id) {
      $location.path('/registration/' + id);
    };

    $scope.awesomeThings = [
      'HTML5 Boilerplate',
      'AngularJS',
      'Karma'
    ];
  });
