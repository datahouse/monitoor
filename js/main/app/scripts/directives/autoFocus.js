'use strict';

angular.module('monApp')
  .directive('autoFocus', function($timeout) {
    return {
      scope : {
        trigger : '@focus'
      },
      link : function(scope, element) {
        scope.$watch('trigger', function(value) {
          $timeout(function() {
            element[0].focus();
          });
        });
      }
    };
  });
