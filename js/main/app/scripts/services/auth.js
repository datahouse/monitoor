'use strict';

angular.module('monApp')
  .factory('auth', function(
    $http, $rootScope, $cookies, $location, $timeout, flash, instanceHelper
  ) {
    var auth = {};

    var token_ = null;
    var token = null;
    var prefix = '';
    var loggedIn = false;

    auth.checkTokenFromCookie = function () {
      if (token === null) {
        if ($cookies.get('authToken') !== undefined) {
          this.setToken($cookies.get('authToken'));
        }
      }
    };

    auth.checkLoggedIn = function() {
      this.checkTokenFromCookie();
      $http.defaults.headers.common['auth-token'] = token_;
      var page = '/product';
      if ($location.url() !== undefined && $location.url() !== '' && $location.url() !== '/') {
        page = $location.url();
      }
      var promise = $http.get('api/v1/user/validation/?page=' + page)
        .success(function (data) {
          if (data.token !== null) {
            this.setToken(data.token.id);
            loggedIn = true;
          } else {
            this.removeToken();
          }
        }.bind(this))
        .error(function (data, status) {
          if (data.msg !== undefined) {
            flash.error = data.msg.join();
          }

          if (status === 401) {
            loggedIn = false;
            this.removeToken();
            instanceHelper.redirectLogout();
            $timeout(function() {
              if (instanceHelper.isWeb()) {
                $location.url($location.path());
              }
              $location.path('/login');
            }, 0);
          } else {
            $timeout(function() {
              $location.path('/home');
            }, 0);
          }

        }.bind(this));

      return promise;
    };

    auth.setToken = function(_token_) {
      token_ = prefix + _token_;
      token = _token_;
      var expireDate = new Date();
      expireDate.setDate(expireDate.getDate() + 30);
      $cookies.put('authToken', token, { expires: expireDate });
      $http.defaults.headers.common['auth-token'] = token_;
    };

    auth.getToken = function() {
      if (token === null) {
        if ($cookies.get('authToken') !== undefined) {
          return $cookies.get('authToken');
        }
      }

      return token;
    };

    auth.removeToken = function() {
      token = null;
      token_ = null;
      loggedIn = false;
      $http.defaults.headers.common['auth-token'] = '';
      delete $http.defaults.headers.common['auth-token'];
      $cookies.remove('authToken');
    };

    auth.isLoggedIn = function() {
      return loggedIn;
    };

    return auth;
  });
