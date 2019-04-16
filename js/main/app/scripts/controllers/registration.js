'use strict';

angular.module('monApp')
  .controller('RegistrationCtrl', function(
    $scope, $rootScope, $http, $location,
    $timeout, $translate, $routeParams, flash, appType, instanceHelper
  ) {
    $rootScope.headerTitle = 'registration.title';
    $translate([
      'registration.successFlash'
    ]).then(function(_) {
      $scope.successFlash = _['registration.successFlash'];
    });

    $scope.data = {};
    $scope.prices = [];
    $scope.showImages = instanceHelper.showImages();
    $scope.mainCss = instanceHelper.getStaticPageClass();
    $scope.i18App = instanceHelper.i18();
    $scope.hidePricePlan = false;
    if (appType != 'web') {
        $scope.hidePricePlan = true;
    }

    $scope.getPrices = function () {
      $http.get('api/v1/pricing/listing/de')
        .success(function(data) {
          $scope.prices = $scope.cleanPrices(data);
          var priceId = Number($routeParams.priceId);
          for (var i in $scope.prices) {
            if (
              priceId !== undefined &&
              priceId === $scope.prices[i].id
            ) {
              $scope.data.pricingPlanId = $scope.prices[i];
              break;
            }
          }
        })
        .error(function (data, status) {
          if (status !== 400) {
            flash.error = data.msg.join();
          } else {
            $scope.error = data.msg;
          }
        });
    };

    $scope.getPrices();
    $scope.send = function () {
      if ($scope.registrationForm.$valid && $scope.isUnique) {
        $scope.data.pricingPlanId = $scope.data.pricingPlanId.id;
        $http.post('api/v1/register/add/' + $translate.use(), $scope.data)
          .success(function (data) {
            if (data) {
              flash.success = $scope.successFlash;
              $timeout(function () {
                $location.path('/');
              }, 0);
            }
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

    $scope.checkUniqueEmail = function () {
      var username = $scope.data.username;
      if (username !== undefined) {
        $http.post('api/v1/register/check/' + $translate.use(), { email: username })
          .success(function (data) {
            $scope.isUnique = data;
          })
          .error(function (data, status) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
              $scope.isUnique = false;
            }
          });
      } else {
        $scope.isUnique = true;
      }
    };

    // not every price is for the gui
    $scope.cleanPrices = function(prices) {
      for (var i in prices) {
        if (prices[i].text === 'Widget') {
          prices.splice(i, 1);
        }
      }

      return prices;
    }
  });
