'use strict';

describe('Controller: UrleditCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var EditurlCtrl, scope, http;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope, $httpBackend) {
    scope = $rootScope.$new();
    http = $httpBackend;
    EditurlCtrl = $controller('UrleditCtrl', {
      $scope: scope,
      $routeParams: { urlId: 1 }
    });
    http.when('GET', 'api/v1/url/get/1/de').respond({});
  }));
/*
  it('should request api', function () {
    http.expectGET('api/v1/url/get/1/de');
    http.flush();
  });
  */
});
