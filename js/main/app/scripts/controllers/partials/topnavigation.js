'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:TopnavigationCtrl
 * @description
 * # TopnavigationCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('TopnavigationCtrl', function ($scope, $location, $timeout, auth) {
    $scope.links = [
      { name: 'init.navTop.dashboard', href: '#/dashboard', auth: true },
      //{ name: 'init.navTop.product', href: '#/product', auth: null },
      { name: 'init.navTop.registration', href: '#/registration', auth: false },
      { name: 'init.navTop.login', href: '#/login', auth: false },
      //{ name: 'init.navTop.price', href: '#/prices', auth: null },
      { name: 'init.navTop.developer', href: '#/developer', auth: null }
      //{ name: 'init.navTop.contact', href: '#/contact', auth: null }
    ];

    $scope.hideTopNavigation = true;
    $scope.hideTopNavigationToggle = function() {
      $scope.hideTopNavigation = !$scope.hideTopNavigation;
    };

    $scope.isActive = function (curr) {
      curr = curr.substring(1);
      return (curr === $location.path() || $location.path().indexOf(curr) > -1);
    };

    $scope.showNavigation = function() {
      var additionalLinks = [
        // footer
        '/impressum', '/disclaimer',
        // non menu pages
        '/passwordRecovery', '/passwordReset',
        '/registration'
      ];

      for (var i = 0; i < $scope.links.length; ++i) {
        if (
          $location.path().indexOf($scope.links[i].href.substring(1)) > -1 &&
          $location.path() !== '/dashboard'
        ) { return true; }
      }

      for (var j = 0; j < additionalLinks.length; ++j) {
        if (additionalLinks[j].indexOf($location.path())  > -1) { return true; }
      }

      return false;
    };

    $scope.isAuth = function(needAuth) {
      if (needAuth === null) { return true; }

      var isLoggedIn = auth.isLoggedIn();
      if (needAuth === undefined) { return isLoggedIn; }
      if (needAuth && isLoggedIn) { return true; }
      if (!needAuth && !isLoggedIn) { return true; }

      return false;
    };
  });
