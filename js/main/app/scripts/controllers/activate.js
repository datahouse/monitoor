'use strict';

angular.module('monApp')
  .controller('ActivateCtrl', function (
    $scope, $rootScope, $routeParams,
    $http, $location, $timeout, auth, flash
  ) {
    $rootScope.headerTitle = 'activate.title';
    var hash = $routeParams.hash;
    if (hash !== null && hash !== '') {
      $http.post('api/v1/register/activate', {activationHash: hash})
        .success(function (data) {
          flash.success = 'activate.successFlash';
          $timeout(function () {
            auth.setToken(data.token.id);
            $location.path('/dashboard');
          }, 0);
        })
        .error(function (data, status) {
          if (status === 403) {
            flash.error = 'activate.errorFlash';
          } else if (status !== 400) {
            flash.error = data.msg.join();
          }  else {
            $scope.error = data.msg;
          }
          $timeout(function () {
            $location.path('/login');
          }, 0);
        });
    }
    flash.error = $scope.errorFlash;
    $timeout(function () {
      $location.path('/login');
    }, 0);
  });
