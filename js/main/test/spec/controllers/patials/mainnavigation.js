'use strict';

describe('Controller: MainnavigationCtrl', function() {

  // load the controller's module
  beforeEach(module('monApp'));

  var MainnavigationctrlCtrl, scope, location, auth;

  // Initialize the controller and a mock scope
  beforeEach(inject(function($controller, $rootScope, $location, _auth_) {
    scope = $rootScope.$new();
    location = $location;
    auth = _auth_;
    MainnavigationctrlCtrl = $controller('MainnavigationCtrl', {
      $scope: scope,
      $routeParams: { id: 3 }
    });
  }));

  describe('testing isActive', function() {
    it('should be true regular link', function() {
      spyOn(location, 'path').and.returnValue('/home');
      expect(scope.isActiveLink('#/home')).toBe(true);
    });

     it('should be true regular sub link', function() {
     spyOn(location, 'path').and.returnValue('/home/a');
     expect(scope.isActiveLink('#/home')).toBe(true);
     });

     it('should be false', function() {
     spyOn(location, 'path').and.returnValue('/login');
       expect(scope.isActiveLink('#/home')).toBe(false);
     });
/*
     it('should match url id', function() {
       expect(scope.isActiveUrl(3)).toBe(true);
     });
*/
     it('should not match', function() {
       expect(scope.isActiveUrl(1)).toBe(false);
     });

     describe('testing isActiveUrl with undefined routeparams', function() {
       beforeEach(inject(function($controller, $rootScope, $location, _auth_) {
         scope = $rootScope.$new();
         location = $location;
         auth = _auth_;
         MainnavigationctrlCtrl = $controller('MainnavigationCtrl', {
           $scope: scope,
           $routeParams: {}
         });
       }));

       it('should not match if no route params id exists', function() {
         expect(scope.isActiveUrl(3)).toBe(false);
       });
     });
/*
     it('should be true url in group', function() {
       spyOn(location, 'path').and.returnValue('/urlgroup/3');
       var group = {
         id: 3,
         urls: [
           { id: 1 },
           { id: 2 },
           { id: 3 },
           { id: 4 },
           { id: 5 }
         ]
       };

       expect(scope.isActiveGroup(group)).toBe(true);
     });
*/
     it('should be true url in group over url', function() {
       spyOn(location, 'path').and.returnValue('/url/3');
       var group = {
         id: 3,
         urls: [
           { id: 1 },
           { id: 2 },
           { id: 3 },
           { id: 4 },
           { id: 5 }
         ]
       };

       expect(scope.isActiveGroup(group)).toBe(true);
     });

     describe('testing isActiveGroup with undefined routeParams', function() {
       beforeEach(inject(function($controller, $rootScope, $location, _auth_) {
         scope = $rootScope.$new();
         location = $location;
         auth = _auth_;
         MainnavigationctrlCtrl = $controller('MainnavigationCtrl', {
           $scope: scope,
           $routeParams: {}
         });
       }));

       it('should not be true no group no url', function() {
         spyOn(location, 'path').and.returnValue('/url/3');
         var group = {
           id: 3,
           urls: [
             { id: 1 },
             { id: 2 },
             { id: 3 },
             { id: 4 },
             { id: 5 }
           ]
         };

         expect(scope.isActiveGroup(group)).toBe(false);
       });
     });
  });

  describe('testing isAuth', function() {
    it('should be true if param is null', function() {
      expect(scope.isAuth(null)).toBe(true);
    });

    it('should be false if param is undefined and isLoggedIn is false', function() {
      spyOn(auth, 'isLoggedIn').and.returnValue(false);
      expect(scope.isAuth()).toBe(false);
    });

    it('should be true if param is undefined and isLoggedIn is true', function() {
      spyOn(auth, 'isLoggedIn').and.returnValue(true);
      expect(scope.isAuth()).toBe(true);
    });

    it('should be true if param is true and isLoggedIn is true', function() {
      spyOn(auth, 'isLoggedIn').and.returnValue(true);
      expect(scope.isAuth(true)).toBe(true);
    });

    it('should be false if param is false and isLoggedIn is true', function() {
      spyOn(auth, 'isLoggedIn').and.returnValue(true);
      expect(scope.isAuth(false)).toBe(false);
    });

    it('should be false if param is true and isLoggedIn is false', function() {
      spyOn(auth, 'isLoggedIn').and.returnValue(false);
      expect(scope.isAuth(true)).toBe(false);
    });

    it('should be true if param is false and isLoggedIn is false', function() {
      spyOn(auth, 'isLoggedIn').and.returnValue(false);
      expect(scope.isAuth(false)).toBe(true);
    });
  });
});
