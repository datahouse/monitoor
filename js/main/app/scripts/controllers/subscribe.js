'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:SubscribeCtrl
 * @description
 * # SubscribeCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('SubscribeCtrl', function ($scope, $rootScope, $modal, $http, flash, instanceHelper) {
    $rootScope.headerTitle = 'subscribe.title';
    $scope.subscribes = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.enableTooltip = instanceHelper.isWeb();
    $scope.open = {};

    var subscribeGroup = function(id, urlId, index, urlIndex) {
      $http.post('api/v1/urlgroup/subscribe/' + id, { urlId: urlId })
      .success(function() {
        $scope.subscribes[index].subscribed = true;
        if (urlId != null) {
          $scope.subscribes[index].urls[urlIndex].subscribed = true;
        } else {
          $scope.subscribes[index].urls.map(function(item) {
            item.subscribed = true;
          });
        }
        $rootScope.$broadcast('urlGroupsChanged', 'fromSubscribeCtrlSubscribeGroup');
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });
    };

    var unsubscribeGroup = function(id, urlId, index, urlIndex) {
      var url = '';
      if (urlId != null) {
        url = '?url=' + urlId;
      }
      $http.delete('api/v1/urlgroup/unsubscribe/' + id + url)
      .success(function() {
        if (urlId != null) {
          $scope.subscribes[index].urls[urlIndex].subscribed = false;
          var filter = function(url) {
            return (url.subscribed);
          };

          if (!$scope.subscribes[index].urls.some(filter)) {
            $scope.subscribes[index].subscribed = false;
          }
        } else {
          $scope.subscribes[index].subscribed = false;
          $scope.subscribes[index].urls.map(function(item) {
            item.subscribed = false;
          })
        }
        $rootScope.$broadcast('urlGroupsChanged', 'fromSubscribeCtrlUnsubscribeGroup');
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });
    };

    $http.get('api/v1/urlgroup/subscriptions')
    .success(function(data) {
      $scope.subscribes = data.urlGroupItems;
    }).error(function(data) {
      flash.error = data.msg.join();
    });

    $scope.confirmPayment = function(hasSubscript, id, urlId, index, indexUrl, hasPrice, event) {
      event.preventDefault();
      event.stopPropagation();

      var urlHasSubscript = $scope.subscribes[index].urls.some(function(url) {
        return url.subscribed;
      });

      if (!hasSubscript && !urlHasSubscript && hasPrice) {
        var modalInstance = $modal.open({
          animation: true,
          templateUrl: 'subscribeConfirm.html',
          controller: 'DefaultconfirmCtrl'
        });

        modalInstance.result.then(
          function() {
            $scope.subscribeToggle(hasSubscript, id, urlId, index, indexUrl);
          }, function() {
            // cancel action
        });
      } else {
        $scope.subscribeToggle(hasSubscript, id, urlId, index, indexUrl);
      }
    };

    $scope.subscribeToggle = function(hasSubscript, id, urlId, index, indexUrl) {
      if (hasSubscript) {
        unsubscribeGroup(id, urlId, index, indexUrl);
      } else {
        subscribeGroup(id, urlId, index, indexUrl);
      }
    };

    $scope.toggleAccordion = function(id) {
      $scope.open[id] = !$scope.open[id];
    }
  });
