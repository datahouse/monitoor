'use strict';

angular.module('monApp')
  .controller('LogoutCtrl', function(
    $scope, $location, $timeout, auth, flash,
    instanceHelper
  ) {
    auth.removeToken();
    instanceHelper.redirectLogout();
    $timeout(function() {
        flash.success = 'logout.successFlash';
        $location.path('/login');
    }, 0);
  });
