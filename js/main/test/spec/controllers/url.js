'use strict';

describe('Controller: UrlCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var UrlCtrl, scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    UrlCtrl = $controller('UrlCtrl', {
      $scope: scope,
      $routeParams: { id: 1 }
    });
  }));

  it('should not change rating', function() {
    var item = { rating: 2 };
    var rating = 3;
    scope.rate(item, 1, 2, rating);
    expect(item.rating).toEqual(2);
  });
});
