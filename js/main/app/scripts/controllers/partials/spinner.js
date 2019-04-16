'use strict';

/**
 * @ngdoc function
 * @name monApp.controller: SpinnerCtrl
 * @description
 * # SpinnerCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('SpinnerCtrl', function($scope, instanceHelper) {
    var i = 0;
    $scope.spinner = false;

    $scope.isApp = instanceHelper.isApp();

    $scope.$on('spinner-start', function() {
      ++i;
      $scope.spinner = true;
    });

    $scope.$on('spinner-stop', function() {
      --i;
      if (i < 1) {
        $scope.spinner = false;
        i = 0;
      }
    });

});
