'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('MainCtrl', function ($scope, appType) {
    var pre = '';
    if (appType === 'app') { pre = '_app'; }

    location.href = 'index' + pre + '.de.html';

    $scope.awesomeThings = [
      'HTML5 Boilerplate',
      'AngularJS',
      'Karma'
    ];
  });
