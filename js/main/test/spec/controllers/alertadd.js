'use strict';

describe('Controller: AlertaddCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var AddalertsCtrl, scope, http;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope, $httpBackend) {
    scope = $rootScope.$new();
    http = $httpBackend;
    AddalertsCtrl = $controller('AlertaddCtrl', {
      $scope: scope
    });

    http.when('GET', 'api/v1/alerttype/listing/de').respond([1, 2]);
    http.when('GET', 'api/v1/urlgroup/listing').respond([3, 4]);
  }));

/*
  it('should set id and type on alert.url', function() {
    var event = { preventDefault: function() {}};
    scope.setUrlGroup({id: 1}, 'type', event);
    expect(scope.alert.url).toEqual({ type: 'type', id: 1 });
  });

  it('should add a keyword', function() {
    scope.alert.keyword = 'test';
    scope.addKeyword();
    expect(scope.alert.keywords.length).toBe(1);
  });

  it('should do nothing with undefined', function() {
    scope.alert.keyword = undefined;
    scope.addKeyword();
    expect(scope.alert.keywords.length).toBe(0);
  });

  it('should do nothing with empty strings', function() {
    scope.alert.keyword = '';
    scope.addKeyword();
    expect(scope.alert.keywords.length).toBe(0);
  });

  it('should remove a valid keyword', function() {
    scope.alert.keywords = ['test', 'test2', 'test3'];
    scope.removeKeyword(0);
    expect(scope.alert.keywords.length).toBe(2);
    expect(scope.alert.keywords).toEqual(['test2', 'test3']);
  });

  it('should return false on undefined object', function() {
    expect(scope.someSelected()).toBe(false);
  });

  it('should return true on valid object', function() {
    var obj = {
      a: { a1: false },
      b: { b1: false, b2: true }
    };
    scope.someSelected(obj);
    expect(scope.isSomeSelected).toBe(true);
  });

  it('should return false, nothing selected', function() {
    var obj = {
      a: { a1: false },
      b: { b1: false, b2: false }
    };
    scope.someSelected(obj);
    expect(scope.isSomeSelected).toBe(false);
  });

  it('should get two requests', function() {
    expect(scope.alertTypes).toEqual([]);
    expect(scope.urlGroups).toEqual({});
    http.expectGET('api/v1/alerttype/listing/de');
    http.expectGET('api/v1/urlgroup/listing');
    http.flush();
    expect(scope.alertTypes).toEqual([1, 2]);
    expect(scope.urlGroups).toEqual([3, 4]);
  });
  */
});
