'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('DashboardCtrl', function (
    $scope, $http, $rootScope, $timeout, $window,
    $translate, colorHandler, flash, d3Locale, appType,
    instanceHelper, socialShare
  ) {
    var offset = 0;
    var size = 5;
    var busy = false;
    var colorArray = [];
    var demoFlag = '';
    var urlChanged = 'api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date';
    var transformData = function(data) {
      $scope.dataActivity = [];
      var item = {};
      var num = data.length;
      var date = 0;

      for (var i in data) {
        item = {
          'id': data[i].id,
          'key': data[i].title,
          'values': []
        };

        if (data[i].values !== undefined) {
          // TODO do on server
          data[i].values = data[i].values.reverse();

          for (var j in data[i].values) {
            date = new Date(data[i].values[j].date).getTime();
            item.values.push([date, data[i].values[j].count]);
          }

          $scope.dataActivity.push(item);
        }
      }

      colorArray = colorHandler.getGradiantRange(num, '#81cbcb', '#e1ae1b');
    };

    var getChangedData = function(url, demo) {
      if (demo) {
        demoFlag = '&demo=true';
        $scope.isDemo = demo;
        url += demoFlag;
      }

      busy = true;
      return $http.get(url)
        .success(function(data) {
          $scope.alerts = data.changeItems;
          if (data.changeItems.length != 0) {
            busy = false;
          }
        })
        .error(function(data) {
          flash.error = data.msg.join();
        });
    };

    $rootScope.headerTitle = 'dashboard.title';
    $scope.alerts = [];
    $scope.dataLoaded = false;
    $scope.toggleOldValue = [];
    $scope.isDemo = false;
    $scope.quickUrlData = {};
    $scope.urlGroups = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.showGraph = instanceHelper.showGraph();
    $scope.showDashboardHead = instanceHelper.showGraph();
    $scope.enableTooltip = instanceHelper.isWeb();
    $scope.social = socialShare;
    $scope.enableMail = instanceHelper.isWeb();

    $scope.status = {
    isopen: false
  };

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

  $scope.setGroupIdQuickUrl = function(urlGroup) {
    $scope.quickUrlData.groupId = urlGroup.id;
    $scope.quickUrlData.group = urlGroup.title;
  };

  $scope.urlSend = function() {
    var data = {
      urls: [{ url: $scope.quickUrlData.url, title: '' }],
      frequency: 5, // stündlich
      urlGroupId: $scope.quickUrlData.group.id,
      urlGroupName: $scope.quickUrlData.group.title
    };

    $http.post('api/v1/url/add/' + $translate.use(), data)
    .success(function(data) {
      $scope.quickUrlData = {};
      $scope.quickUrl.$setPristine(true);

      if (!data.alertExists) {
        var alert = {
          urlGroup: { id: data.urlGroupId },
          alertShapingList: [
            {
              alertOption: {
                id: 1,
                title: 'Aktivität'
              },
              alertType: {
                cycleId: 1,
                id: 2
              }
            }
          ]
        };
        $http.post('api/v1/alert/add' + '/' + $translate.use(), alert)
          .success(function() {
            $rootScope.$broadcast('urlGroupsChanged', 'fromDashboardCtrlUrlSend');
            flash.success = 'dashboard.addUrl.successFlash';
          })
          .error(function (data, status) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      } else {
        flash.success = 'dashboard.addUrl.successFlash';
      }
    })
    .error(function (data) {
      if (status !== 400) {
        flash.error = data.msg.join();
      } else {
        $scope.error = data.msg;
      }
    });
  };

  $scope.toggleDropdown = function($event) {
    $event.preventDefault();
    $event.stopPropagation();
    $scope.status.isopen = !$scope.status.isopen;
  };

    $http.get('api/v1/urlgroup/listing/' + $translate.use())
    .success(function(data) {
      $scope.urlGroups = data.urlGroupItems;
      getChangedData(urlChanged, ($scope.urlGroups.length === 0)).then(function() {
        $scope.dataLoaded = true;
        if (appType === 'web') {
          $http.get('api/v1/report/change/' + $translate.use() + '?req=true' + demoFlag)
          .success(function(data) {
            transformData(data);
            $timeout(function() {
              $scope.optionsActivity.chart.noData = $translate.instant('dashboard.noData');
              $scope.optionsActivity.chart.yAxis.axisLabel = $translate.instant('dashboard.yAxis');
              $scope.apiActivity.updateWithOptions($scope.optionsActivity);
              $scope.configActivity.visible = true;
              $scope.apiActivity.refresh();
            }, 800);
          })
          .error(function(data) {
            flash.error = data.msg.join();
          });
        }
      });
    }).error(function(data) {
      flash.error = data.msg.join();
    });

  $scope.nextPage = function() {
      $('.nvtooltip').css('opacity', 0);
      if (busy) { return; }
      busy = true;
      offset += size;
      $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date' + demoFlag)
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

    $scope.configActivity = {
      visible: false
    };

    if (appType === 'web') {
      var locale = d3.locale(d3Locale[$translate.use()]);

      $rootScope.$on('$translateChangeSuccess', function () {
        locale = d3.locale(d3Locale[$translate.use()]);
        $scope.optionsActivity.chart.noData = $translate.instant('dashboard.noData');
        $scope.optionsActivity.chart.yAxis.axisLabel = $translate.instant('dashboard.yAxis');
        $scope.apiActivity.updateWithOptions($scope.optionsActivity);
      });

      $scope.optionsActivity = {
        chart: {
          type: 'stackedAreaChart',
          color: function(d, i) {
            return colorArray[i];
          },
          height: 450,
          noData: '',
          margin : {
            top: 25,
            right: 80,
            bottom: 25,
            left: 25
          },
          x: function(d){return d[0];},
          y: function(d){return d[1];},
          useVoronoi: false,
          clipEdge: false,
          transitionDuration: 500,
          legendPosition: 'bottom',
          useInteractiveGuideline: false,
          showControls: false,
          rightAlignYAxis: true,
          xAxis: {
            showMaxMin: false,
            tickValues: function() {
              var today = new Date();
              // 10 weeks = 70 days
              var range = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 70);
              return d3.time.month.range(
                range, today, 1
              );
            },
            tickFormat: function(d) {
              return locale.timeFormat('%b %Y')(new Date(d));
            }
          },
          yAxis: {
            axisLabel: '',
            tickFormat: function(d) {
              return d;
            }
          }
        }
      };
    }

    $scope.rate = function(item, alertId, changeId, rating) {
      if ($scope.isDemo) { return; }
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
