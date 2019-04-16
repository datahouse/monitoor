'use strict';

angular.module('monApp')
  .directive('fieldMatch', function () {
    return {
      require: 'ngModel',
      restrict: 'A',
      scope: {
        fieldMatch: '='
      },
      link: function (scope, elem, attrs, ctrl) {
        scope.$watch(function () {
          return (ctrl.$pristine && angular.isUndefined(ctrl.$modelValue)) || scope.fieldMatch === ctrl.$modelValue;
        }, function (currentValue) {
          ctrl.$setValidity('match', currentValue);
        });
      }
    };
  });