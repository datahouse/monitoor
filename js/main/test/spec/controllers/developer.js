'use strict';

describe('Controller: DeveloperCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var DeveloperCtrl,
    scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    DeveloperCtrl = $controller('DeveloperCtrl', {
      $scope: scope
    });
  }));

/*
  it('should attach a list of awesomeThings to the scope', function () {
    expect(scope.awesomeThings.length).toBe(3);
  });
  */
});
