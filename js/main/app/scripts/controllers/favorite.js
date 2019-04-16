'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:FavoriteCtrl
 * @description
 * # FavoriteCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('FavoriteCtrl', function ($scope, $rootScope, $http, $translate, flash, instanceHelper, socialShare) {
    $rootScope.headerTitle = 'favorite.title';
    $scope.url = {};
    $scope.alerts = [];
    $scope.dataLoaded = false;
    $scope.toggleOldValue = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.enableTooltip = instanceHelper.isWeb();
    $scope.social = socialShare;
    $scope.enableMail = instanceHelper.isWeb();

    var offset = 0;
    var size = 10;
    var getListingUrl = function() {
      return 'api/v1/change/listing/'
        + $translate.use()
        + '?favorites=true&offset=' + offset + '&size=' + size + '&sort=-start_date';
    };

    var busy = true;
    $http.get(getListingUrl())
      .success(function(data) {
        $scope.alerts = data.changeItems;
        if (data.changeItems.length != 0) {
          busy = false;
        }
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });

    $scope.nextPage = function() {
      if (busy) { return; }
      busy = true;
      offset += size;
      $http.get(getListingUrl())
        .success(function(data) {
          $scope.alerts = $scope.alerts.concat(data.changeItems);
          if (data.changeItems.length != 0) {
            busy = false;
          }
        })
        .error(function(data) {
          flash.error = data.msg.join();
        });
    };

    $scope.rate = function(item, alertId, changeId, rating) {
      $http.post('api/v1/change/rating/' + $translate.use(), {
        alertId: alertId,
        changeId: changeId,
        rating: rating
      })
        .success(function () {
          item.rating = rating;
        })
        .error(function (data) {
          flash.error = data.msg.join();
        });
    };

    $scope.toggleFavorite = function(index, changeId) {
      var pin = 'pin';
      if ($scope.alerts[index].change.favorite) {
        pin = 'unpin';
      }
      $http.post('api/v1/change/' + pin + '/' + $translate.use(), {
        changeId: changeId
      })
        .success(function() {
          $scope.alerts[index].change.favorite = !$scope.alerts[index].change.favorite;

          if (pin == 'unpin') {
            $scope.alerts.splice(index, 1);
          }
        })
        .error(function(data) {
          flash.error = data.msg.join();
        });
    };

    $scope.toggleOld = function(index, event) {
      event.preventDefault();
      event.stopPropagation();

      $scope.toggleOldValue[index] = !$scope.toggleOldValue[index];
      return false;
    };
  });
