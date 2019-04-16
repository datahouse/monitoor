'use strict';

angular.module('monApp')
  .controller('UrladdCtrl', function(
    $scope, $rootScope, $http, $location, $timeout,
    $window, $translate, $routeParams, flash, urlFlag, instanceHelper,
    registerUrl
  ) {
    $rootScope.headerTitle = 'urls.add.title';
    $translate([
      'urls.add.defaultGroup',
    ]).then(function(_) {
      $scope._ = _;
    });

    $scope.data = {
      urls: [{ url: registerUrl.pullUrl(), title: '' }]
    };
    $scope.groups = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.showAdvancedOption = false;
    $scope.enableBookmarklet = instanceHelper.isWeb();
    $scope.rootUrl = window.ROOT_URL;
    if (instanceHelper.isWeb()) {
      var search = urlFlag.getLastSearch();

      if (search.url !== undefined) {
        $scope.data.urls[0] = { url:  search.url, title: '' };
      } else if ($routeParams.url !== undefined) {
        $scope.data.urls[0] = { url:  $routeParams.url, title: '' };
      }
    }

    $scope.refreshResults = function($select) {
      var search = $select.search;
      var list = angular.copy($select.items);
      var FLAG = -1;

      //remove last user input
      list = list.filter(function(item) {
        return item.id !== FLAG;
      });

      if (!search) {
        //use the predefined list
        $select.items = list;
      }
      else {
        //manually add user input and set selection
        var userInputItem = {
          id: FLAG,
          title: search
        };
        $select.items = [userInputItem].concat(list);
        $select.selected = userInputItem;
      }
    };

    $http.get('api/v1/frequency/listing/' + $translate.use())
      .success(function (data) {
        $scope.frequencies = data;
        $scope.data.frequency = preSelect($scope.frequencies, 'id');
        if ($scope.data.frequency === undefined) {
          for (var i in $scope.frequencies) {
            if ($scope.frequencies[i].id === 2) { // daily
              $scope.data.frequency = $scope.frequencies[i];
            }
          }
        }
      })
      .error(function (data) {
        flash.error = data.msg.join();
      });

      $http.get('api/v1/urlgroup/listing/' + $translate.use())
        .success(function(data) {
          $scope.groups = $scope.groups.concat(data.urlGroupItems);
          setDefaultGroup();
          $scope.data.urlGroupId = preSelect($scope.groups, 'id');
      }).error(function(data) {
          flash.error = data.msg.join();
      });

    $scope.back = function() {
      $window.history.back();
    };

    $scope.send = function () {
      var urlGroup, frequency;
      if ($scope.urlForm.$valid) {
        urlGroup = $scope.data.urlGroup;
        frequency = $scope.data.frequency;
        $scope.data.urlGroupId = $scope.data.urlGroup.id;
        $scope.data.urlGroupName = $scope.data.urlGroup.title;
        delete $scope.data.urlGroup;
        $scope.data.frequency = $scope.data.frequency.id;

        if ($scope.data.urls.length > 1) {
          delete $scope.data.urls[0].xpath;
        }

        $http.post('api/v1/url/add/' + $translate.use(), $scope.data)
          .success(function (data) {
            var urlGroupId = '';
            var redirectTo = '';
            var lastFlag = urlFlag.getLastFlag();
            if (data.urlGroupId !== undefined) { urlGroupId = data.urlGroupId; }

            redirectTo = '/urlGroups/' + urlGroupId;
            if (lastFlag.indexOf('dashboard') > -1 || lastFlag.indexOf('urlgroup') > -1 || lastFlag.indexOf('url') > -1) {
              redirectTo = lastFlag;
            }
            flash.success = 'urls.add.successFlashAdd';
            if (!data.alertExists) { redirectTo = '/alerts/add/' + urlGroupId; }
            $timeout(function() {
              $location.path(redirectTo);
            },0);
          })
          .error(function (data) {
            $scope.data.urlGroup = urlGroup;
            $scope.data.frequency = frequency;
            if (data.code !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    $scope.addUrlField = function() {
      $scope.data.urls.push({ url: '', title: ''});
    };

    $scope.removeUrlField = function(index) {
      if ($scope.data.urls.length > 1) {
        $scope.data.urls.splice(index, 1)
      }
    };

    $scope.showAddGroupField = function() {
      return $scope.data.urlGroupId === -2;
    };

    $scope.toggleAdvancedOption = function() {
      $scope.showAdvancedOption = !$scope.showAdvancedOption;
    };

    var preSelect = function(array, selector) {
      if (array.length === 1) {
        if (array[0][selector] !== undefined) {
          return array[0][selector];
        }
      }
    };

    var setDefaultGroup = function() {
      if ($scope.groups.length === 0) {
        $scope.groups.push({
          id: -1,
          title: $scope._['urls.add.defaultGroup']
        });
      }
    };
  });
