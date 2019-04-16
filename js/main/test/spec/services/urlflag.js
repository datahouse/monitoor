'use strict';

describe('Service: urlFlag', function () {

  // load the service's module
  beforeEach(module('monApp'));

  // instantiate service
  var urlFlag, defaultFlag;
  beforeEach(inject(function (_urlFlag_) {
    urlFlag = _urlFlag_;
    defaultFlag = '/dashboard';
  }));

  it('should be dashboard link on first get', function () {
    expect(urlFlag.getFlag()).toEqual(defaultFlag);
  });

  it('should be empty on first pop', function () {
    expect(urlFlag.popFlag()).toEqual(defaultFlag);
  });

  it('should ignore root url', function() {
    urlFlag.setFlag('/');
    expect(urlFlag.getFlag()).toEqual(defaultFlag);
  });

  it('should be true after set valid string with / prefix', function () {
    urlFlag.setFlag('/test');
    expect(urlFlag.getFlag()).toEqual('/test');
  });

  it('should be equal get / pop and set', function () {
    var value = '/test';
    urlFlag.setFlag(value);
    expect(urlFlag.getFlag()).toEqual(value);
    expect(urlFlag.popFlag()).toEqual(value);
  });

  it('should be add slash if forgotten in setFlag', function () {
    var value = 'test';
    urlFlag.setFlag(value);
    expect(urlFlag.getFlag()).toEqual('/' + value);
  });

  it('should be default: /dashboard after popFlag', function () {
    var value = '/test';
    urlFlag.setFlag(value);
    urlFlag.popFlag();
    expect(urlFlag.getFlag()).toEqual(defaultFlag);
  });
});
