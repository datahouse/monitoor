'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:DefaultconfirmCtrl
 * @description
 * # DefaultconfirmCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('DefaultconfirmCtrl', function($scope, $modalInstance) {
    $scope.ok = function() {
      $modalInstance.close();
    };

    $scope.cancel = function() {
      $modalInstance.dismiss('cancel');
    };
  });