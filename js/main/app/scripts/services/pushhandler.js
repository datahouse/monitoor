'use strict';

/**
 * @ngdoc service
 * @name monApp.colorHandler
 * @description
 * # colorHandler
 * Service in the monApp.
 */
angular.module('monApp')
  .service('pushHandler', function($http) {
    this.registerPushToken = function(token) {
      $http.post('api/v1/user/pushtoken', token);
    };
  });
