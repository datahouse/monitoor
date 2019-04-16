'use strict';

angular.module('monApp')
  .controller('ProfileCtrl', function(
    $scope, $rootScope, $translate, $http, flash,
    instanceHelper
  ) {
    $rootScope.headerTitle = 'profile.title';
    $scope.user = {};
    $scope.pwMode = false;
    $scope.mainCss = instanceHelper.getMainNavigationClass();

    $scope.send = function() {
      if ($scope.profileForm.$valid) {
        $http.post('api/v1/user/update/' + $translate.use(), $scope.user)
          .success(function () {
            flash.success = 'profile.successFlashUser';
            $scope.editMode = false;
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

    $scope.sendPwd = function() {
      if ($scope.pwResetForm.$valid) {
        $http.post('api/v1/user/password/' + $translate.use(), $scope.data)
          .success(function() {
            flash.success = 'profile.successFlashPw';
            $scope.pwMode = false;
          })
          .error(function (data, status) {
            if (status === 420) {
              flash.error = 'profile.errorFlashPw';
            }  else if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    $scope.togglePw = function() {
      $scope.pwMode = !$scope.pwMode;
    };

    $http.get('api/v1/user/get/' + $translate.use())
      .success(function(data) {
        $scope.user = data;
        $scope.error = [];
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });
  });
