'use strict';

describe('Controller: UrlgroupsCtrl', function () {

  // load the controller's module
  beforeEach(module('monApp'));

  var UrlgroupCtrl, scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    UrlgroupCtrl = $controller('UrlgroupsCtrl', {
      $scope: scope
    });
  }));

  it('should toggle add to false', function () {
    scope.showAddGroup = true;
    scope.toggleAdd();
    expect(scope.showAddGroup).toBe(false);
  });

  it('should toggle add to true', function () {
    scope.showAddGroup = false;
    scope.toggleAdd();
    expect(scope.showAddGroup).toBe(true);
  });
/*
  it('should open all', function() {
    scope.open = { first: false, 1: false, 2: false };
    scope.groups = [{ id: 1 }, { id: 2 }];
    scope.openAll();
    expect(scope.open).toEqual({ first: true, 1: true, 2: true });
  });

  it('should close all', function() {
    scope.open = { first: true, 1: false, 2: true };
    scope.groups = [{ id: 1 }, { id: 2 }];
    scope.closeAll();
    expect(scope.open).toEqual({ first: false, 1: false, 2: false });
  });

  it('should close all except first', function() {
    scope.open = { first: false, 1: true, 2: true };
    scope.groups = [{ id: 1 }, { id: 2 }];
    scope.closeAll(true);
    expect(scope.open).toEqual({ first: true, 1: false, 2: false });
  });
*/
  it('should toggle edit to true because not exists', function() {
    scope.editMode = {};
    var event = { stopPropagation: function() {}};
    scope.toggleEdit(1, event);
    expect(scope.editMode[1]).toBe(true);
  });

  it('should toggle edit to true', function() {
    scope.editMode = { 1: false };
    var event = { stopPropagation: function() {}};
    scope.toggleEdit(1, event);
    expect(scope.editMode[1]).toBe(true);
  });

  it('should toggle edit to false', function() {
    scope.editMode = { 1: true };
    var event = { stopPropagation: function() {}};
    scope.toggleEdit(1, event);
    expect(scope.editMode[1]).toBe(false);
  });

  it('should moved an remove from array', function() {
    scope.group = [1, 2, 3, 4];
    scope.moved(scope.group, 2);
    expect(scope.group).toEqual([1, 2, 4]);
  });

  it('should not moved when dragged', function() {
    scope.group = [1, 2, 3, 4];
    scope.draggstart(scope.group);
    scope.moved(scope.group, 2);
    expect(scope.group).toEqual([1, 2, 3, 4]);
  });

  it('should not drop when dragged', function() {
    scope.group = [1, 2, 3, 4];
    scope.draggstart(scope.group);
    expect(scope.drop(scope.group, { id: 1 })).toBe(undefined);
  });
});
