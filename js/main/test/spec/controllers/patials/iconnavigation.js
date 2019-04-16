'use strict';

describe('Controller: IconnavigationCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var IconnavigationCtrl,
    scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    IconnavigationCtrl = $controller('IconnavigationCtrl', {
      $scope: scope
    });
  }));
});
