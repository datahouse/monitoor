'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:ContactCtrl
 * @description
 * # ContactCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('ContactCtrl', function(
    $scope, $rootScope, $translate,
    $http, $timeout, $location, flash
  ) {
    $rootScope.headerTitle = 'contact.title';

    $scope.send = function () {
      if ($scope.contactForm.$valid) {
        $http.post('api/v1/contact/send/' + $translate.use(), $scope.data)
          .success(function () {
            flash.success = 'contact.successFlash';
            $timeout(function() {
              $location.path('/product');
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
