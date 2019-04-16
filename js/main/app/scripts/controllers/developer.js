'use strict';

/**
 * @ngdoc function
 * @name monApp.controller:DeveloperCtrl
 * @description
 * # DeveloperCtrl
 * Controller of the monApp
 */
angular.module('monApp')
  .controller('DeveloperCtrl', function(
    $scope, $rootScope, $routeParams, instanceHelper
  ) {
    $rootScope.headerTitle = 'developer.title';
    $scope.api = $routeParams.api;
    $scope.showIntro = false;
    $scope.showApi = false;
    $scope.showImages = instanceHelper.showImages();
    $scope.mainCss = instanceHelper.getStaticPageClass();

    if ($scope.api === undefined) {
      $scope.showIntro = true;
      return;
    }

    $scope.showApi = true;
    $scope.links = [
      { name: 'Overview', href: 'overview' },
      { name: 'alert/add', href: 'alert_add' },
      { name: 'alert/delete', href: 'alert_delete' },
      { name: 'alert/get', href: 'alert_get' },
      { name: 'alert/update', href: 'alert_update' },
      { name: 'change/listing', href: 'change_listing' },
      { name: 'url/add', href: 'url_add' },
      { name: 'url/delete', href: 'url_delete' },
      { name: 'url/get', href: 'url_get' },
      { name: 'url/update', href: 'url_update' },
      { name: 'urlgroup/add', href: 'urlgroup_add' },
      { name: 'urlgroup/delete', href: 'urlgroup_delete' },
      { name: 'urlgroup/get', href: 'urlgroup_get' },
      { name: 'urlgroup/listing', href: 'urlgroup_listing' },
      { name: 'urlgroup/put', href: 'urlgroup_put' },
      { name: 'urlgroup/update', href: 'urlgroup_update' },
      { name: 'user/login', href: 'user_login' }
    ];

    $scope.isActive = function(name) {
      return (name === $scope.api);
    };

    $scope.hideSubNavigation = true;
    $scope.hideSubNavigationToggle = function() {
      $scope.hideSubNavigation = !$scope.hideSubNavigation;
    };

    $scope.urlAddResponseExample = {'urlId': 380,'urlGroupId': 4};
    $scope.urlGetResponseExample = {'id':1,'title':'Example','url':'http://www.monitoor.ch','urlGroupId':2,'frequency':1,'xpath':'//body'};
    $scope.urlAddRequestExample = {'urlGroupId':3,'frequency':2,'urls': [{'url':'http://www.monitoor.ch','title':'Example','xpath':'//body'}]};
    $scope.urlUpdateRequestExample = {'id':1,'title':'Example','url':'http://www.monitoor.ch','urlGroupId':3,'frequency':2,'xpath':'//body'};
    $scope.errorExample = {'code': 401,'msg': ['Unauthorized']};
    $scope.userLoginResponseExample = {'token':{'id': 'xy10...EDW'}};
    $scope.userLoginRequestExample =  {'username': 'example@monitoor.ch','password': 'myPassword'};
    $scope.alertAddRequestExample =  {'urlGroup': {'id': '1'  },'alertShapingList': [{'alertType': {'id': 2,'cycleId': 1},'keywords': ['keyword1','keyword2'],'alertOption': {'id': 2}}]};
    $scope.alertGetResponseExample = {'id':1,'urlGroup':{'id':4,'title':'Example Group'},'alertShapingList':[{'alertType':{'id':2,'cycleId':1},'keywords':['keyword1','keyword2'],'alertOption':{'id':2,'title':'Keywords'}}]};
    $scope.alertUpdateRequestExample = {'urlGroup': {'id': '1'  },'alertShapingList': [{'alertType': {'id': 2,'cycleId': 1},'keywords': ['keyword1','keyword2'],'alertOption': {'id': 2}}]};
    $scope.urlgroupAddRequestExample =  {'title': 'Example'};
    $scope.urlgroupAddResponseExample =  {'id':'1','title': 'Example'};
    $scope.urlgroupGetResponseExample = {'id': 1, 'title': 'Example group', 'alertId': 1, 'urls': [{'id': 3,'title': 'first url','url': 'http://www.example1.com'},{'id': 4,'title': 'second url','url': 'http://www.example2.com'}]};
    $scope.urlgroupUpdateRequestExample = {'title': 'Example'};
    $scope.urlgroupListingResponseExample = {'count': 2,'urlGroupItems': [{'id': 1, 'title': 'Example group', 'alertId': 1, 'urls': [{'id': 3,'title': 'first url','url': 'http://www.example1.com'},{'id': 4,'title': 'second url','url': 'http://www.example2.com'}]}]};
    $scope.urlgroupPutRequestExample =    {'oldGroupId':1,'newGroupId':2,'urlId':5};
    $scope.changeListingResponseExample = {'count':25,'changeItems':[{'url':{'id':1,'title':'Example','url':'http://www.example.com/example.html'},'urlGroup':{'id':2,'title':'Example'},'alert':{'id':3},'change':{'changeDate':'27.07.201512:58:05','newDoc':{'id':1601},'oldDoc':{'id':1600},'diff':'- removed content + added content','diffHtml':'','matchedKeywords': ['keyword1','keyword2']},'rating':5},{'url':{'id':1,'title':'Example','url':'http://www.example.com/example.html'},'urlGroup':{'id':2,'title':'Example'},'alert':{'id':3},'change':{'changeDate':'20.07.201512:58:05','newDoc':{'id':1501},'oldDoc':{'id':1500},'diff':'- removed content + added content','diffHtml':'', matchedKeywords: []},'rating':5}]};
  });
