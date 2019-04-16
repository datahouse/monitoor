'use strict';

describe('Service: mainNavigation', function () {

  // load the service's module
  beforeEach(module('monApp'));

  // instantiate service
  var mainNavigation;
  beforeEach(inject(function (_mainNavigation_) {
    mainNavigation = _mainNavigation_;
  }));

  it('should be an empty array', function () {
    expect(mainNavigation.getNavigation()).toEqual([]);
  });

  it('should get back the array', function () {
    var arr = ['a'];
    mainNavigation.setNavigation(arr);
    expect(mainNavigation.getNavigation()).toEqual(arr);
  });

  it('should compare with the array', function () {
    var arr = ['a'];
    mainNavigation.setNavigation(arr);
    expect(mainNavigation.compareNavigation(arr)).toBe(true);
  });

  it('should not compare with the array', function () {
    var arr = ['a'];
    mainNavigation.setNavigation(arr);
    expect(mainNavigation.compareNavigation(['b'])).toBe(false);
  });

});
