'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:PasswordrecoveryCtrl
 * @description
 * # PasswordrecoveryCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('PasswordrecoveryCtrl', function(
    $scope, $rootScope, $translate, $http, $location, $timeout, flash
  ) {
    var successFlash = '';
    $rootScope.headerTitle = 'passwordRecovery.title';
    $scope.username = '';
    $scope.error = [];

    $scope.send = function() {
      if ($scope.pwRecoveryForm.$valid) {
        $http.post('api/v1/user/recover/' + $translate.use(), { email: $scope.username })
          .success(function() {
                flash.success = 'passwordRecovery.successFlash';
            $timeout(function() {
              $location.path('/');
            }, 0);
          })
          .error(function (data, status) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };
  });
