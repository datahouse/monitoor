'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:FooternavigationCtrl
 * @description
 * # FooternavigationCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('FooternavigationCtrl', function ($scope, $location) {
    $scope.links = [
      { name: 'init.navFooter.impressum', href: '#/impressum' },
      { name: 'init.navFooter.disclaimer', href: '#/disclaimer' }
    ];

    var currentYear = new Date().getFullYear();
    $scope.copyRight = '© ' + currentYear;
    $scope.copyRight += ' Datahouse AG';

    $scope.isActive = function (curr) {
      curr = curr.substring(1);
      return (curr === $location.path() || $location.path().indexOf(curr) > -1);
    };
  });
