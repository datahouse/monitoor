'use strict';

describe('Controller: PasswordrecoveryCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var PasswordrecoveryCtrl, scope, http;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope, $httpBackend) {
    scope = $rootScope.$new();
    http = $httpBackend;
    PasswordrecoveryCtrl = $controller('PasswordrecoveryCtrl', {
      $scope: scope
    });
  }));

  /*
   it('should attach a list of awesomeThings to the scope', function () {
   //expect(scope.awesomeThings.length).toBe(3);
   });
   */
});
