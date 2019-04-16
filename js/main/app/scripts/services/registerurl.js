'use strict';

/**
 * @ngdoc service
 * @name monApp.registerUrl
 * @description
 * # registerUrl
 * Factory in the monApp.
 */
angular.module('monApp')
  .factory('registerUrl', function($location) {
    var url = '';

    var obj = {
      registerUrl: function(_url) {
        url = _url;
        $location.path('/urls/add');
      },
      pullUrl: function() {
        var tmp = url;
        url = '';
        return tmp;
      }
    };

    return obj;
  });
