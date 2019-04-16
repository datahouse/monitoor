'use strict';

/**
 * @ngdoc service
 * @name monApp.urlFlag
 * @description
 * # urlFlag
 * Factory in the monApp.
 */
angular.module('monApp')
  .factory('urlFlag', function () {
    var defaultFlag = '/dashboard';
    var flag = defaultFlag;
    var lastFlag = defaultFlag;
    var defaultSearch = {};
    var search = defaultSearch;
    var lastSearch = defaultSearch;
    var blackList = [
      '/login',
      '/passwordRecovery',
      '/passwordReset',
      '/logout',
      '/product',
      '/prices',
      '/developer',
      '/contact',
      '/registration',
      '/impressum',
      '/disclaimer'
    ];

    return {
      getFlag: function() {
        return flag;
      },
      getLastFlag: function() {
        return lastFlag;
      },
      setFlag: function(_flag, _search) {
        if (_flag === undefined || _flag === null || _flag === '') {
          _flag = defaultFlag;
        } else if (_flag.charAt(0) !== '/') {
          _flag = '/' + _flag;
        // ignore root
        } else if (_flag === '/') { return; }

        for (var i in blackList) {
          if (_flag.indexOf(blackList[i]) !== -1) { return; }
        }

        lastSearch = search;
        search = _search;
        lastFlag = flag;
        flag = _flag;
      },
      popFlag: function() {
        var tmp = flag;
        flag = defaultFlag;
        return tmp;
      },
      getSearch: function() {
        return search;
      },
      getLastSearch:function() {
        return lastSearch;
      },
      popSearch: function() {
        var tmp = search;
        search = defaultSearch;
        return tmp;
      }
    };
  });
