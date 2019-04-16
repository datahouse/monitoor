'use strict';

angular.module('monApp')
  .controller('AlerteditCtrl', function(
    $scope, $rootScope, $http, $routeParams, $location,
    $window, $timeout, $translate, $modal, flash, instanceHelper
  ) {
    $rootScope.headerTitle = 'alerts.edit.title';
    $scope.alertId = $routeParams.alertId;
    $scope.keywords = [''];
    $scope.urlGroup = {};
    $scope.hasSelected = false;
    $scope.isKeywordsAlert = false;
    $scope.thresholdSlider = 50;
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.enableTooltip = instanceHelper.isWeb();

    $scope.translateSlider = function(value) {
      $scope.thresholdSlider = value;
      return value + '%';
    };

    $http.get('api/v1/alert/get/' + $scope.alertId + '/' + $translate.use())
      .success(function(data) {
        var alertShaping = data.alertShapingList[0];
        $scope.urlGroup = data.urlGroup;
        $http.get('api/v1/alerttype/listing/' + $translate.use())
          .success(function(data) {
            $scope.availableAlertTypes = data;
            $http.get('api/v1/alertoption/listing/' + $translate.use())
              .success(function(data) {
                $scope.availableAlertOptions = data;
                handleServerResponse(alertShaping);
              })
              .error(function(data) {
                flash.error = data.msg.join();
              });
          })
          .error(function(data) {
            flash.error = data.msg.join();
          });
        if ($scope.urlGroup.title !== undefined) {
          $scope.urlGroupSelectedText = $scope.urlGroup.title;
        }
      })
      .error(function(data, status) {
        if (status !== 400) {
          flash.error = data.msg.join();
        } else {
          $scope.error = data.msg;
        }
      });

    $scope.back = function() {
      $window.history.back();
    };

    $scope.addKeyword = function() {
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

    $scope.alertTypeChanged = function() {
      if ($scope.selectedAlertType !== undefined) {
        $scope.availableCycle = $scope.selectedAlertType.cycle;
        $scope.selectedCycle = $scope.availableCycle[0];
      } else {
        $scope.availableCycle = [];
      }
    };

    $scope.alertOptionChanged = function() {
      checkIfIsSelectedAndIsKeywordAlert();
    };

    $scope.send = function() {
      if ($scope.alertForm.$valid) {
        $http.put(
          'api/v1/alert/update/' + $scope.alertId + '/' + $translate.use(), prepareServerRequest()
          )
          .success(function() {
            flash.success = 'alerts.edit.successFlash';
            $timeout(function() {
              $location.path('/urlGroups');
            }, 0);
          })
          .error(function(data, status) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    $scope.confirmRemoveAlert = function(id, event) {
      event.preventDefault();
      event.stopPropagation();
      var modalInstance = $modal.open({
        animation: true,
        templateUrl: 'alertEditConfirm.html',
        controller: 'DefaultconfirmCtrl',
      });

      modalInstance.result.then(
        function() {
          $scope.removeAlert(id);
        }, function() {
          // cancel action
      });
    };

    $scope.removeAlert = function(id) {
      $http.delete('api/v1/alert/delete/' + id + '/' + $translate.use())
        .success(function() {
          flash.success = 'alerts.edit.successFlashRemove';
          $timeout(function() {
            $location.path('/urlGroups');
          }, 0);
        })
        .error(function(data) {
          flash.error = data.msg.join();
        });
    };


    var handleServerResponse = function(alertShaping) {
      $scope.selectedAlertOption = alertShaping.alertOption;
      $scope.selectedAlertType = alertShaping.alertType;
      $scope.selectedCycle = alertShaping.alertType.cycleId;

      if (alertShaping.keywords.length === 0) {
        alertShaping.keywords.push('');
      } else {
        $scope.keywords = alertShaping.keywords;
      }
      if (
        alertShaping.alertThreshold !== undefined &&
        alertShaping.alertThreshold !== null
      ) {
        $scope.thresholdSlider = alertShaping.alertThreshold;
      }

      for (var alertTypeIterator in $scope.availableAlertTypes) {
        var alertType = $scope.availableAlertTypes[alertTypeIterator];
        if (alertType.id === alertShaping.alertType.id) {
          $scope.selectedAlertType = alertType;
          for (var alertCycleIterator in alertType.cycle) {
            var cycle = alertType.cycle[alertCycleIterator];
            if (cycle.id === alertShaping.alertType.cycleId) {
              $scope.alertTypeChanged();
              $scope.selectedCycle = cycle;
            }
          }
        }
        checkIfIsSelectedAndIsKeywordAlert();
      }
    };

    var prepareServerRequest = function() {
      var alert = {
        urlGroup: {id: $scope.urlGroup.id},
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
      return alert;
    };

    var checkIfIsSelectedAndIsKeywordAlert = function() {
      $scope.hasSelected = false;
      $scope.isKeywordsAlert = false;
      if ($scope.selectedAlertOption.id !== -1) {
        $scope.hasSelected = true;
        if ($scope.selectedAlertOption.id === 2) {
          $scope.isKeywordsAlert = true;
        }
      }
    };
  });
