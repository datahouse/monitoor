'use strict';

angular.module('monApp')
  .controller('LoginCtrl', function(
    $scope, $rootScope, $translate, $http,
    $location, $timeout, instanceHelper, auth, flash, urlFlag
  ) {
    $rootScope.headerTitle = 'login.title';
    $scope.data = {};

    $scope.send = function () {
      if ($scope.loginForm.$valid) {
        $http.post('api/v1/user/login/' + $translate.use(), $scope.data)
          .success(function (data) {
            auth.setToken(data.token.id);
            instanceHelper.redirectLogin();
            var path = urlFlag.popFlag();
            $timeout($location.path(path), 0);
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
