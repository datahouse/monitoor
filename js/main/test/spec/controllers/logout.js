'use strict';

describe('Controller: LogoutCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var LogoutCtrl, scope, location, auth;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope, _auth_) {
    scope = $rootScope.$new();
    auth = _auth_;
    spyOn(auth, 'removeToken');

    LogoutCtrl = $controller('LogoutCtrl', {
      $scope: scope
    });
  }));

  it('should call auth removeToken', function () {
    expect(auth.removeToken).toHaveBeenCalled();
  });
});
