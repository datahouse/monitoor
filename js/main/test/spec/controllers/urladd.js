'use strict';

describe('Controller: UrladdCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var AddurlCtrl, scope, http;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope, $httpBackend) {
    scope = $rootScope.$new();
    http = $httpBackend;
    AddurlCtrl = $controller('UrladdCtrl', {
      $scope: scope
    });
    http.when('GET', 'api/v1/frequency/listing/de').respond({});
  }));
/*
  it('should request api', function () {
    http.expectGET('api/v1/frequency/listing/de');
    http.flush();
  });
  */
});
