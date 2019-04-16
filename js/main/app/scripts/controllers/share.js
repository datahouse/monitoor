'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:ShareCtrl
 * @description
 * # ShareCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('ShareCtrl', function ($scope, $rootScope, $http, $location, $routeParams, $translate, flash) {
    var hash = $routeParams.hash;
    $rootScope.headerTitle = 'share.title';
    $scope.change = [];
    $scope.toggleOldValue = [];
    $scope.link = '';
    switch($translate.use()) {
      case 'de':
        $scope.link += 'willkommen';
        break;
      case 'en':
      default:
        $scope.link += 'welcome';
    }

    $http.get('api/v1/change/get/?change_hash=' + encodeURI(hash))
      .success(function(data) {
        $scope.change = data;
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });

    $scope.linkToRoot = function() {
      $location.path(link);

      return false;
    };

    $scope.toggleOld = function(index, event) {
      event.preventDefault();
      event.stopPropagation();

      $scope.toggleOldValue[index] = !$scope.toggleOldValue[index];
      return false;
    };
  });
