'use strict';

describe('Controller: SubscribeCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var SubscribeCtrl,
    scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    SubscribeCtrl = $controller('SubscribeCtrl', {
      $scope: scope
    });
  }));

  /*
  it('should attach a list of awesomeThings to the scope', function () {
    expect(scope.awesomeThings.length).toBe(3);
  });
  */
});
