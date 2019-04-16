'use strict';

describe('Service: Auth', function () {

  // load the service's module
  beforeEach(module('monApp'));

  var scope, auth, cookie;

  beforeEach(inject(function($rootScope, _auth_, $cookies) {
    scope = $rootScope.$new();
    auth = _auth_;
    cookie = $cookies;
  }));

  describe('testing methods', function() {
    it('should be null on default', function() {
      expect(auth.getToken()).toBeNull();
    });

    it('should be setted after setToken', function() {
      auth.setToken('testToken');
      expect(auth.getToken()).toEqual('testToken');
      expect(cookie.get('authToken')).toEqual('testToken');
    });

    it('should be false on default', function() {
      expect(auth.isLoggedIn()).toBe(false);
    });

    it('should be null after removeToken', function() {
      auth.removeToken();
      expect(auth.getToken()).toBeNull();
      expect(auth.isLoggedIn()).toBe(false);
    });
  });

});
