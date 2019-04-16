'use strict';

/**
 * @ngdoc service
 * @name monApp.instanceHelper
 * @description
 * # instanceHelper
 * Service in the monApp.
 */
angular.module('monApp')
  .service('instanceHelper', function(appType, $http, $location, $timeout) {
    var type = 'app';

    this.getMainNavigationDirective = function() {
      var obj = {
        restrict: 'E',
        scope: {},
        controller: 'MainnavigationCtrl'
      };

      if (appType === type) {
        obj.template = '';
      } else {
        obj.templateUrl = 'views/template/mainNavigation.html';
      }

      return obj;
    };

    this.getMainNavigationStaticLinks = function(_) {
      if (appType === type) {
        return [
          { title: _('init.navMain.alerts'), type: 'item', url: '#/urlGroups', icon: 'fa-bell' },
          { title: _('init.navMain.add'), type: 'section' },
          { title: _('init.navMain.addPageMobile'), type: 'item', url: '#/urls/add', icon: 'fa-plus' },
          { title: _('init.navMain.subscribe'), type: 'item', url: '#/subscribe', icon: 'fa-bookmark' },
          { title: _('init.navMain.settings'), type: 'section' },
          { title: _('init.navMain.language'), type: 'item', icon: 'fa-globe', subitems: [
            { title: _('init.navMain.language.german'), type: 'item', url: 'javascript:monitoor.setLanguage("de")' },
            { title: _('init.navMain.language.english'), type: 'item', url: 'javascript:monitoor.setLanguage("en")' }
          ]},
          { title: _('init.navMain.profile'), type: 'item', icon: 'fa-user', url: '#/profile' },
          { title: _('init.navMain.logout'), type: 'item', icon: 'fa-arrow-circle-right', url: '#/logout' },
          { title: _('init.navMain.about'), type: 'section' },
          { title: _('init.navMain.impressum'), type: 'item', url: '#/impressum' },
          { title: _('init.navMain.disclaimer'), type: 'item', url: '#/disclaimer' }
        ];
      } else {
        return [
          { name: 'init.navMain.alerts', href: '#/urlGroups', auth: true, icon: 'bell' },
          { name: 'init.navMain.addPage', href: '#/urls/add', auth: true, icon: 'plus' },
          { name: 'init.navMain.subscribe', href: '#/subscribe', auth: true, icon: 'bookmark' },
          { name: 'init.navMain.logout', href: '#/logout', auth: true }
        ];
      }
    };

    var cssClass = function(css, repl, subj) {
      if (appType === type) {
        return css.replace(repl, subj);
      } else {
        return css;
      }
    };

    this.getMainNavigationClass = function() {
      return cssClass('col-9-12 col-omega feed', '9-12', '1-1');
    };

    this.getStaticPageClass = function() {
      return cssClass('col-7-12 col-omega', '7-12', '1-1');
    };

    this.i18 = function() {
      if (appType !== type) {
        return '';
      }

      return '.app';
    };

    this.isWeb = function() {
      return (appType !== type);
    };

    this.isApp = function() {
      return (appType === type);
    };

    this.showImages = function() {
      return (appType !== type);
    };

    this.showGraph = function() {
      return (appType !== type);
    };

    this.showDashboardHead = function() {
      return (appType !== type);
    };

    this.showDashboardHead = function() {
      return (appType !== type);
    };

    this.showRootHeader = function() {
      return (appType !== type);
    };

    this.showRootFooter = function() {
      return (appType !== type);
    };

    this.sendMainNavigation = function(groups, links, func) {
      if (appType === type) {
        location.href = 'monitor://menu?data=' + func(groups, links);
      }
    };

    this.redirectLogin = function() {
      if (appType === type) {
        location.href = 'monitoor://login';
      }
    };

    this.redirectLogout = function() {
      if (appType === type) {
        location.href = 'monitoor://logout';
      }
    };
  });
