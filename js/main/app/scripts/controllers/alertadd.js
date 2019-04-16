'use strict';

angular.module('monApp')
  .controller('AlertaddCtrl', function(
    $scope, $rootScope, $http, $routeParams,
    $location, $window, $timeout, $translate, flash,
    instanceHelper
  ) {
    $rootScope.headerTitle = 'alerts.add.title';
    $scope.keywords = [''];
    $scope.urlGroup = {};
    $scope.hasSelected = false;
    $scope.thresholdSlider = 50;
    $scope.mainCss = instanceHelper.getMainNavigationClass();

    $scope.translateSlider = function(value) {
      $scope.thresholdSlider = value;
      return value + '%';
    };

    $http.get('api/v1/alerttype/listing/' + $translate.use())
      .success(function (data) {
        $scope.availableAlertTypes = data;
      })
      .error(function (data) {
        flash.error = data.msg.join();
      });

    $http.get('api/v1/alertoption/listing/' + $translate.use())
      .success(function(data) {
        $scope.availableAlertOptions = data;
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });

    $http.get('api/v1/urlgroup/get/' + $routeParams.urlGroupId + '/' + $translate.use())
      .success(function(data) {
        $scope.urlGroup = data;
      })
      .error(function(data) {
        flash.error = data.msg.join();
      }
    );

    $scope.back = function() {
      $window.history.back();
    };

    $scope.addKeyword = function () {
      $scope.keywords.push('');
    };

    $scope.isKeywordsValid = function() {
      for(var i in $scope.keywords) {
        if ($scope.keywords !== undefined && $scope.keywords !== null && $scope.keywords[i] !== '') {
          return true;
        }
      }

      return false;
    };

    $scope.alertTypeChanged = function () {
      if ($scope.selectedAlertType !== undefined) {
        $scope.availableCycle = $scope.selectedAlertType.cycle;
        $scope.selectedCycle = $scope.availableCycle[0];
      } else {
        $scope.availableCycle = [];
      }
    };

    $scope.alertOptionChanged = function () {
      $scope.hasSelected = false;
      if ($scope.selectedAlertOption.id !== -1) {
        $scope.hasSelected = true;
      }
    };


    $scope.send = function () {
      if ($scope.alertForm.$valid) {
        $http.post('api/v1/alert/add' + '/' + $translate.use(), prepareServerRequest())
          .success(function () {
            flash.success = 'alerts.add.successFlash';
            $timeout(function() {
              $location.path('/urlGroups');
            }, 0);
          })
          .error(function (data, status) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    var prepareServerRequest = function() {
      return {
        urlGroup: { id: $routeParams.urlGroupId },
        alertShapingList: [{
          alertType: {
            cycleId: $scope.selectedCycle.id,
            id: $scope.selectedAlertType.id
          },
          keywords: $scope.keywords,
          alertThreshold: $scope.thresholdSlider,
          alertOption: $scope.selectedAlertOption
        }]
      };
    };
  });
