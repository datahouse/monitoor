'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:MainnavigationCtrl
 * @description
 * # MainnavigationCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('MainnavigationCtrl', function (
    $scope, $routeParams, $location, $http,
    $translate, $filter, auth, flash, mainNavigation,
    instanceHelper
  ) {
    var _ = $filter('translate');
    var menuItems = [];

    var prepareNavigation = function(groups, links) {
      var preparedArray = [];
      preparedArray.push({ title: _('init.navMain.home'), type: 'section'});
      preparedArray.push({ title: _('init.navMain.dashboard'), type: 'item', url: '#/dashboard', icon: 'fa-home' });
      preparedArray.push({ title: _('init.navMain.myGroups'), type: 'section' });
      for (var i in groups) {
        var obj = {};
        obj.title = groups[i].title;
        obj.type = 'item';
        if (groups[i].subscribed) {
          obj.icon = 'fa-bookmark';
        }
        obj.url = '#/urlGroup/' + groups[i].id;
        obj.subitems = [];

        for (var j in groups[i].urls) {
          obj.subitems.push({
            title: groups[i].urls[j].title,
            type: 'item',
            url: '#/url/' + groups[i].urls[j].id
          });
        }
        preparedArray.push(obj);
      }

      preparedArray = $.merge(preparedArray, links);
      return encodeURIComponent(JSON.stringify(preparedArray));
    };

    var getData = function() {
      $http.get('api/v1/urlgroup/listing/' + $translate.use())
        .success(function(data) {
          menuItems = data.urlGroupItems;
          var favorite = {
            id: null,
            title: _('init.navMain.favorite'),
            url: 'favorite'
          };

          menuItems.unshift(favorite);

          if (!mainNavigation.compareNavigation(menuItems)) {
            mainNavigation.setNavigation(menuItems);
          }
          $scope.groups = mainNavigation.getNavigation();
          $scope.urls = data.urls;
          instanceHelper.sendMainNavigation($scope.groups, $scope.links, prepareNavigation);
      }).error(function(data) {
          flash.error = data.msg.join();
      }).then(function() {
        $scope.dataLoaded = true;
      });
    };
    $scope.groups = mainNavigation.getNavigation();
    $scope.urls = [];
    $scope.dataLoaded = false;

    $scope.$on('urlGroupsChanged', function() {
      getData();
    });

    $scope.links = instanceHelper.getMainNavigationStaticLinks(_);

    $scope.hideMainNavigation = true;
    $scope.hideMainNavigationToggle = function() {
      $scope.hideMainNavigation = !$scope.hideMainNavigation;
    };

    $scope.isActiveGroup = function(group) {
      return (
        (
          $location.path().indexOf('urlGroup/') > -1 &&
          $routeParams.id !== undefined &&
          parseInt($routeParams.id) === parseInt(group.id)
        ) ||
        (
          $location.path().indexOf('url/') > -1 &&
          isUrlInGroup(group.urls, parseInt($routeParams.id))
        ) ||
        (
          $location.path().indexOf('favorite') > -1 &&
            group.id === null
        )
      );
    };

    $scope.isActiveUrl = function(urlId) {
      return (
        $location.path().indexOf('url/') > -1 &&
        $routeParams.id !== undefined &&
        parseInt($routeParams.id) === parseInt(urlId)
      );
    };

    $scope.isActiveLink = function(curr) {
      curr = curr.substring(1);

      return (curr === $location.path() || $location.path().indexOf(curr) > -1);
    };

    $scope.isAuth = function(needAuth) {
      if (needAuth === null) { return true; }

      var isLoggedIn = auth.isLoggedIn();
      if (needAuth === undefined) { return isLoggedIn; }
      if (needAuth && isLoggedIn) { return true; }
      if (!needAuth && !isLoggedIn) { return true; }

      return false;
    };

    getData();

    var isUrlInGroup = function(urls, urlId) {
      for (var i in urls) {
        if (urls[i].id === urlId) {
          return true;
        }
      }

      return false;
    };
  });
