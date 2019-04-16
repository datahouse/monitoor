'use strict';

/**
 * @ngdoc service
 * @name monApp.spinner
 * @description
 * # spinner
 * Factory in the monApp.
 */
angular.module('monApp')
  .factory('spinner', function ($rootScope) {

    return {
      start: function() {
        $rootScope.$broadcast('spinner-start');
      },
      stop: function() {
        $rootScope.$broadcast('spinner-stop');
      }
    };
  });
