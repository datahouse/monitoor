'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:UrlCtrl
 * @description
 * # UrlCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('UrlCtrl', function(
    $scope, $rootScope, $routeParams, $http, $window,
    $translate, flash, instanceHelper, socialShare
  ) {
    $rootScope.headerTitle = '';

    var offset = 0;
    var size = 5;
    var busy = false;
    var urlId = $routeParams.id;
    $scope.url = {};
    $scope.alerts = [];
    $scope.dataLoaded = false;
    $scope.toggleOldValue = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.enableTooltip = instanceHelper.isWeb();
    $scope.social = socialShare;
    $scope.enableMail = instanceHelper.isWeb();

    $http.get('api/v1/url/get/' + urlId + '/' + $translate.use())
      .success(function(data) {
        $scope.url = data;
        $rootScope.headerTitle = data.title;
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });

      busy = true;
      $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date&url_id=' + urlId)
        .success(function(data) {
          $scope.alerts = data.changeItems;
          $scope.dataLoaded = true;
          if (data.changeItems.length != 0) {
            busy = false;
          }
        })
        .error(function(data) {
          flash.error = data.msg.join();
        });

      $scope.nextPage = function() {
        $('.nvtooltip').css('opacity', 0);
        if (busy) { return; }
        busy = true;
        offset += size;
        $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date&url_id=' + urlId)
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

    $scope.routeTo = function(url) {
      $window.open(url, '_blank');
    };
  });
