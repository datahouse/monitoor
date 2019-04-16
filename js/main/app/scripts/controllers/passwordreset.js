'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:PasswordresetCtrl
 * @description
 * # PasswordresetCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('PasswordresetCtrl', function(
    $scope, $rootScope, $translate, $routeParams, $http,
    $location, $timeout, auth, flash
  ) {
    $rootScope.headerTitle = 'passwordReset.title';
    if ($routeParams.token === undefined || $routeParams.token === '') {
      $timeout(function() {
        flash.error = 'passwordReset.errorFlash';
        $location.path('/');
      },0);
    }

    $scope.data = {};
    $scope.error = [];

    $scope.send = function () {
      if ($scope.pwResetForm.$valid) {
        $scope.data.hashValue = $routeParams.token;
        $http.post('api/v1/user/pwd/' + $translate.use(), $scope.data)
          .success(function (data) {
            auth.setToken(data.token.id);
            $timeout(function() {
              $location.path('/dashboard');
            }, 0);
          })
          .error(function (data, status) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      } else {
        $scope.formSubmitted = true;
      }
    };

  });
