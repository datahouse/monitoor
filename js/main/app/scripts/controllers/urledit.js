'use strict';

angular.module('monApp')
  .controller('UrleditCtrl', function(
    $scope, $rootScope, $http, $location, $window,
    $routeParams, $timeout, $translate, flash, instanceHelper
  ) {
    $rootScope.headerTitle = 'urls.edit.title';
    $translate([
      'urls.edit.defaultGroup'
    ]).then(function(_) {
      $scope._ = _;
    });
    var urlId = $routeParams.urlId;
    $scope.url = {};
    $scope.groups = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.showAdvancedOption = false;

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

    $http.get('api/v1/url/get/' + urlId + '/' + $translate.use())
      .success(function(data) {
        $scope.url = data;

        if ($scope.url.xpath !== '' && $scope.url.xpath !== '//body') {
          $scope.showAdvancedOption = true;
        }

        $http.get('api/v1/frequency/listing/' + $translate.use())
          .success(function (data) {
            $scope.frequencies = data;
            for (var i in $scope.frequencies) {
              if ($scope.frequencies[i].id === $scope.url.frequency) {
                $scope.url.frequency = $scope.frequencies[i];
              }
            }
          })
          .error(function (data) {
            flash.error = data.msg.join();
          });

          $http.get('api/v1/urlgroup/listing/' + $translate.use())
            .success(function(data) {
              $scope.groups = data.urlGroupItems;
              setSelected();
              setDefaultGroup();
          }).error(function(data) {
              flash.error = data.msg.join();
          });
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });

    $scope.send = function () {
      $scope.url.urlGroupId = $scope.url.urlGroup.id;
      $scope.url.urlGroupName = $scope.url.urlGroup.title;
      delete $scope.url.urlGroup;
      $scope.url.frequency = $scope.url.frequency.id;

      if ($scope.urlForm.$valid) {
        $http.put('api/v1/url/update/' + urlId + '/' + $translate.use(), $scope.url)
          .success(function() {
            flash.success = 'urls.edit.successFlashSave';
            $timeout(function() {
              $location.path('/urlGroups/' + $scope.url.urlGroupId);
            },0);
          })
          .error(function(data) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    $scope.back = function() {
      $window.history.back();
    };

    $scope.toggleAdvancedOption = function() {
      $scope.showAdvancedOption = !$scope.showAdvancedOption;
    };

    var setSelected = function() {
      for (var i in $scope.groups) {
        if ($scope.url.urlGroupId === $scope.groups[i].id) {
          $scope.url.urlGroup = $scope.groups[i];
          break;
        }
      }
    };

    var setDefaultGroup = function() {
      if ($scope.groups.length === 0) {
        $scope.groups.push({
          id: -1,
          title: $scope._['urls.edit.defaultGroup']
        });
      }
    };
  });
