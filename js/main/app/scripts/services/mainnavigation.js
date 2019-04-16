'use strict';

/**
 * @ngdoc service
 * @name monApp.mainNavigation
 * @description
 * # mainNavigation
 * Factory in the monApp.
 */
angular.module('monApp')
  .factory('mainNavigation', function () {
    var nav = [];

    return {
      getNavigation: function() {
        return nav;
      },
      setNavigation: function(_nav_) {
        nav = _nav_;
      },
      compareNavigation: function(_nav_) {
        return angular.equals(nav, _nav_);
      }
    };
  });
