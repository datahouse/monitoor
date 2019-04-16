'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:UrlgroupCtrl
 * @description
 * # UrlgroupCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('UrlgroupCtrl', function(
    $scope, $rootScope, $routeParams, $timeout, $http,
    $window, $translate, colorHandler, flash, d3Locale, instanceHelper, socialShare
  ) {
    $rootScope.headerTitle = '';
    $scope.data = [];
    $scope.dataPie = [];
    $scope.toggleOldValue = [];
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.showGraph = instanceHelper.showGraph();
    $scope.enableTooltip = instanceHelper.isWeb();
    $scope.social = socialShare;
    $scope.enableMail = instanceHelper.isWeb();

    var offset = 0;
    var size = 5;
    var busy = false;
    var urlGroupId = $routeParams.id;
    var colorArray = ['#81cbcb'];
    var pieColorArray = [];
    var keywordFilter = null;

    var transformData = function(data) {
      $scope.data = [];
      var item = {
        'id': data.id,
        'key': data.title,
        'bar': true,
        'values': []
      };

      if (data.values !== undefined) {
        data.values = data.values.reverse();

        for (var j in data.values) {
          item.values.push([new Date(data.values[j].date).getTime(), data.values[j].count]);
        }

        $scope.data.push(item);
      }
    };

    var removeKeyword = function(e) {
      if (keywordFilter !== null) {
        offset = 0;
        $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date&url_group_id=' + urlGroupId)
          .success(function(data) {
            $scope.alerts = data.changeItems;
          })
          .error(function(data) {
            flash.error = data.msg.join();
          }).then(function() {
            $scope.dataLoaded = true;
        });
      }

      keywordFilter = null;
    };

    var getDataByKeyword = function(e) {
      var slices = d3.selectAll('.nv-slice');
      slices.classed('active', false);
      var slice = d3.select(slices[0][e.index]);
      var nvd3Svg = d3.select('.nvd3-svg').node().getBoundingClientRect();
      var radius = Math.min(nvd3Svg.width, nvd3Svg.height) / 2;
      var outerRadius = radius - radius / 5;
      var innerRadius = 0.35 * radius; // 0.35 donat ratio
      var arc = d3.svg.arc().innerRadius(innerRadius).outerRadius(outerRadius);
      var arcOver = d3.svg.arc().innerRadius(innerRadius).outerRadius(outerRadius + 10);
      slices.classed('active', false).select('path').transition().duration(500).attr('d', arc);

      var filterString = '';
      offset = 0;
      if (keywordFilter === e.data.key) {
        keywordFilter = null;
        filterString = '';
      } else {
        keywordFilter = e.data.key;
        filterString = '&keyword=' + keywordFilter;
        slice.classed('active', true)
          .select('path')
          .transition()
          .duration(500)
          .attr('d', arcOver);
      }

      $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date&url_group_id=' + urlGroupId + filterString)
        .success(function(data) {
          $scope.alerts = data.changeItems;
        })
        .error(function(data) {
          flash.error = data.msg.join();
        }).then(function() {
          $scope.dataLoaded = true;
      });
    };

    $scope.urlGroup = {};
    $scope.alerts = [];
    $scope.dataLoaded = false;

    var locale = d3.locale(d3Locale[$translate.use()]);

    $rootScope.$on('$translateChangeSuccess', function () {
      locale = d3.locale(d3Locale[$translate.use()]);
      $scope.options.chart.noData = $translate.instant('urlGroup.noData');
      $scope.options.chart.yAxis.axisLabel = $translate.instant('urlGroup.yAxis');
      $scope.api.updateWithOptions($scope.options);
    });

    $scope.options = {
      chart: {
        type: 'historicalBarChart',
        noData: '',
        rightAlignYAxis: true,
        height: 250,
        color: function(d, i) {
          return colorArray[i];
        },
        margin : {
          top: 25,
          right: 80,
          bottom: 25,
          left: 25
        },
        x: function(d) { return d[0]; },
        y: function(d) { return d[1]; },
        showValues: true,
        transitionDuration: 500,
        useInteractiveGuideline: true,
        xAxis: {
          axisLabel: '',
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
          },
          showMaxMin: false
        },
        yAxis: {
          axisLabel: '',
          tickValues: function(d) {
            var min = 0, max = 0;
            for (var i in d[0].values) {
              if (d[0].values[i][1] > max) {
                max = d[0].values[i][1];
              }
              if (d[0].values[i][1] < min) {
                min = d[0].values[i][1];
              }
            }

            var increment = parseInt((max - min));
            for (var k = 1;increment / k > 10; k *= 10) {}
            if (increment / k > 5) { k *= 2; }

            var ticks = [];
            for (var j = min; j < max; j += k) {
              ticks.push(j);
            }

            return ticks;
          },
          tickFormat: function(d) {
            return d;
          }
        }
      }
    };

    $scope.config = {
      visible: false
    };

    $http.get('api/v1/urlgroup/get/' + urlGroupId + '/' + $translate.use())
      .success(function(data) {
        $scope.urlGroup = data;
        $rootScope.headerTitle = $scope.urlGroup.title;
      })
      .error(function(data) {
        flash.error = data.msg.join();
      });

      busy = true;
      $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date&url_group_id=' + urlGroupId)
        .success(function(data) {
          $scope.alerts = data.changeItems;
          if (data.changeItems.length != 0) {
            busy = false;
          }
        })
        .error(function(data) {
          flash.error = data.msg.join();
        }).then(function() {
          $scope.dataLoaded = true;
          $http.get('api/v1/report/change/' + $translate.use() + '?urlGroupId=' + urlGroupId)
          .success(function(data) {
            transformData(data);
            $timeout(function() {
              $scope.options.chart.noData = $translate.instant('urlGroup.noData');
              $scope.options.chart.yAxis.axisLabel = $translate.instant('urlGroup.yAxis');
              $scope.api.updateWithOptions($scope.options);
              $scope.config.visible = true;
              $scope.api.refresh();
            }, 800);
          })
          .error(function(data) {
            flash.error = data.msg.join();
          });

          $http.get('api/v1/report/keywords/' + $translate.use() + '?urlGroupId=' + urlGroupId)
          .success(function (data) {
            $scope.hasPieData = (data.length > 0);
            pieColorArray = colorHandler.getGradiantRange(data.length, '#81cbcb', '#e1ae1b');
            $timeout(function() {
            $scope.configPie.visible = true;
            $scope.dataPie = data;
            $scope.api.refresh();
          }, 800);
        })
        .error(function () {
          $scope.hasPieData = false;
          return [];
        });
      });

      $scope.nextPage = function() {
        $('.nvtooltip').css('opacity', 0);
        if (busy) { return; }
        busy = true;
        offset += size;
        var filterString = '';
        if (keywordFilter !== null) {
          filterString = '&keyword=' + keywordFilter;
        }
        $http.get('api/v1/change/listing/' + $translate.use() + '?offset=' + offset + '&size=' + size + '&sort=-start_date&url_group_id=' + urlGroupId + filterString)
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

      $scope.rate = function (item, alertId, changeId, rating) {
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

    $scope.optionsPie = {
      chart: {
        type: 'pieChart',
        height: 300,
        x: function(d){return d.key;},
        y: function(d){return d.y;},
        valueFormat: d3.format('.0d'),
        showLabels: false,
        labelType: 'value',
        transitionDuration: 500,
        donut: true,
        donutRatio: 0.35,
        growOnHover: false,
        labelThreshold: 0.01,
        noData: 'Keine Daten vorhanden',
        color: function(d, i) {
          return pieColorArray[i];
        },
        pie: {
          dispatch:{
            elementClick: getDataByKeyword
          }
        },
        legend: {
          dispatch:{
            legendClick: removeKeyword,
            legendDblclick: removeKeyword
          },
          margin: {
            top: 5,
            right: 35,
            bottom: 5,
            left: 5
          }
        }
      }
    };

    $scope.configPie = {
      visible: false
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
