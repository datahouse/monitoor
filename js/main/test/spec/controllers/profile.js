'use strict';

describe('Controller: ProfileCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var ProfileCtrl, scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    ProfileCtrl = $controller('ProfileCtrl', {
      $scope: scope
    });
  }));

  it('should toggle Pw to true', function () {
    scope.pwMode = false;
    scope.togglePw();
    expect(scope.pwMode).toBe(true);
  });

  it('should toggle Pw to false', function () {
    scope.pwMode = true;
    scope.togglePw();

    expect(scope.pwMode).toBe(false);
  });
});
