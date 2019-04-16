'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:UrlgroupCtrl
 * @description
 * # UrlgroupCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('UrlgroupsCtrl', function(
    $scope, $rootScope, $http, $location, $timeout,
    $routeParams, $translate, $modal, flash, instanceHelper
  ) {
    $rootScope.headerTitle = 'urlGroups.title';
    $scope.mainCss = instanceHelper.getMainNavigationClass();
    $scope.urls = [];
    $scope.groups = [];
    $scope.open = {};
    $scope.showAddGroup = false;
    $scope.editMode = {};
    $scope.edit = {};
    $scope.addNewGroup = false;
    $scope.data = {};
    $scope.dropDownOpen = {};
    $scope.enableTooltip = instanceHelper.isWeb();

    $scope.status = {
      isFirstDisabled: false
    };

    var draggedGroup = null;

    $scope.goto = function(link) {
      $location.path(link);
    };

    $scope.addGroup = function() {
      $scope.addNewGroup = true;
    };

    $scope.toggleEdit = function(groupId, event) {
      event.preventDefault();
      event.stopPropagation();
      if ($scope.editMode[groupId] === undefined) {
        $scope.editMode[groupId] = true;
      } else {
        $scope.editMode[groupId] = !$scope.editMode[groupId];
      }
    };

    $scope.toggleAdd = function() {
      $scope.showAddGroup = !$scope.showAddGroup;
    };

    $scope.closeAll = function() {
      for (var key in $scope.groups) {
        $scope.open[$scope.groups[key].id] = false;
      }
    };

    $scope.moved = function(group, index) {
      if (draggedGroup === group) { return; }
      group.splice(index, 1);
    };

    $scope.draggstart = function(group) {
      draggedGroup = group;
    };

    $scope.drop = function(group, item) {
      if (draggedGroup === group) { return; }
      var oldGroupId = null;
      var newGroupId = null;
      var urlId = item.id;

      if (draggedGroup !== null) {
        oldGroupId = draggedGroup.id;
        draggedGroup = null;
      }

      if (group !== null) {
        newGroupId = group.id;
      }

      $scope.moveGroupItem(oldGroupId, newGroupId, urlId);

      return item;
    };

    $scope.moveTo = function(oldGroupId, newGroupId, url) {
      var i = 0;

      // remove item from old group
      complete:
      for (i = 0; i < $scope.groups.length; ++i) {
        if ($scope.groups[i].id === oldGroupId) {
          for (var j = 0; j < $scope.groups[i].urls.length; ++j) {
            if ($scope.groups[i].urls[j].id === url.id) {
              $scope.groups[i].urls.splice(j, 1);
              break complete;
            }
          }
        }
      }

      // insert item into new group
      for (i = 0; i < $scope.groups.length; ++i) {
        if ($scope.groups[i].id === newGroupId) {
          $scope.groups[i].urls.push(url);
        }
      }

      $scope.moveGroupItem(oldGroupId, newGroupId, url.id);
    };

    $scope.moveGroupItem = function(oldGroupId, newGroupId, urlId) {
      var reqData = { oldGroupId: oldGroupId, newGroupId: newGroupId, urlId: urlId };
      $http.put('api/v1/urlgroup/put/' + $translate.use(), reqData)
        .success(function() {
          flash.success = 'urlGroups.successFlashSaveUrl';
        })
        .error(function(data) {
          if (status !== 400) {
            flash.error = data.msg.join();
          } else {
            $scope.error = data.msg;
          }
        });
    };

    $scope.confirmRemoveUrl = function(id, parentIndex, index, event) {
      event.preventDefault();
      event.stopPropagation();
      var modalInstance = $modal.open({
        animation: true,
        templateUrl: 'urlGroupsRemoveUrlConfirm.html',
        controller: 'DefaultconfirmCtrl',
      });

      modalInstance.result.then(
        function() {
          $scope.removeUrl(id, parentIndex, index);
        }, function() {
          // cancel action
      });
    };

    $scope.removeUrl = function (id, parentIndex, index) {
      $http.delete('api/v1/url/delete/' + id + '/' + $translate.use())
        .success(function () {
          $scope.groups[parentIndex].urls.splice(index, 1);
          $rootScope.$broadcast('urlGroupsChanged', 'fromUrlgroupsCtrlRemoveUrl');
          flash.success = 'urlGroups.successFlashRemoveUrl';
        })
        .error(function (data) {
          if (status !== 400) {
            flash.error = data.msg.join();
          } else {
            $scope.error = data.msg;
          }
        });
    };

    $scope.confirmRemoveGroup = function(group, index, event) {
      event.preventDefault();
      event.stopPropagation();
      var modalInstance = $modal.open({
        animation: true,
        templateUrl: 'urlGroupsRemoveGroupConfirm.html',
        controller: 'DefaultconfirmCtrl',
      });

      modalInstance.result.then(
        function() {
          $scope.removeGroup(group, index);
        }, function() {
          // cancel action
      });
    };

    $scope.removeGroup = function(group, index) {
      $http.delete('api/v1/urlgroup/delete/' + group.id + '/' + $translate.use())
        .success(function () {
          $scope.groups.splice(index, 1);
          for (var key in group.urls) {
            $scope.urls.push(group.urls[key]);
          }
          $rootScope.$broadcast('urlGroupsChanged', 'fromUrlgroupsCtrlRemoveGroup');
          flash.success = 'urlGroups.successFlashRemoveGroup';
        })
        .error(function (data) {
          if (status !== 400) {
            flash.error = data.msg.join();
          } else {
            $scope.error = data.msg;
          }
        });
    };

    $scope.editSend = function(groupId, index) {
      if (
        $scope.edit[groupId].title !== undefined &&
        $scope.edit[groupId].title.length > 0
      ) {
        $http.put('api/v1/urlgroup/update/' + groupId + '/' + $translate.use(), $scope.edit[groupId])
          .success(function () {
            $scope.editMode[groupId] = false;
            if ($scope.groups[index].id === groupId) {
              $scope.groups[index].title = $scope.edit[groupId].title;
            }
            $rootScope.$broadcast('urlGroupsChanged', 'fromUrlgroupsCtrleditSend');
            flash.success = 'urlGroups.successFlashSaveGroup';
          })
          .error(function (data) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    $scope.addSend = function() {
      if ($scope.title !== '') {
        $http.post('api/v1/urlgroup/add/' + $translate.use(), $scope.data)
          .success(function (data) {
            $scope.data = {};
            data.urls = [];
            $scope.groups.push(data);
            $scope.addNewGroup = false;
            $rootScope.$broadcast('urlGroupsChanged', 'fromUrlgroupsCtrlAddSend');
            flash.success = 'urlGroups.successFlashAddGroup';
          })
          .error(function (data) {
            if (status !== 400) {
              flash.error = data.msg.join();
            } else {
              $scope.error = data.msg;
            }
          });
      }
    };

    $http.get('api/v1/urlgroup/listing/' + $translate.use())
      .success(function(data) {
        $scope.urls = data.urls;
        $scope.groups = data.urlGroupItems;
        $scope.closeAll();
        if ($routeParams.urlGroupId !== undefined) {
          $scope.open[$routeParams.urlGroupId] = true;
        }

      })
      .error(function(data) {
        flash.error = data.msg.join();
      });
  });
