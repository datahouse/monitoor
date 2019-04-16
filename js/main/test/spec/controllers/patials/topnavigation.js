'use strict';

describe('Controller: TopnavigationCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var TopnavigationCtrl,
    scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    TopnavigationCtrl = $controller('TopnavigationCtrl', {
      $scope: scope
    });
  }));

/*
  it('should attach a list of awesomeThings to the scope', function () {
    expect(scope.awesomeThings.length).toBe(3);
  });
  */
});
