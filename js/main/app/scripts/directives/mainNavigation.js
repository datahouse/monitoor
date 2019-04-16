'use strict';

angular.module('monApp')
  .directive('monMainNavigation', function(instanceHelper) {
    return instanceHelper.getMainNavigationDirective();
  });
