'use strict';

/**
 * @ngdoc service
 * @name monApp.socialShare
 * @description
 * # socialShare
 * Factory in the monApp.
 */
angular.module('monApp')
  .factory('socialShare', function($http, $timeout, $window, $translate, flash) {
    var elem;
    var myWindow;
    var media;
    var texts = {
      shareText: '',
      shareSubject: ''
    };

    texts.shareSubject = $translate.instant('init.shareSubject');
    texts.shareText = $translate.instant('init.shareText');

    var fb = function(link) {
      return 'https://www.facebook.com/sharer.php?u=' + encodeURIComponent(link);
    };

    var twitter = function(link) {
      return 'https://twitter.com/intent/tweet?text=' + texts.shareText + '&url=' + encodeURIComponent(link);
    };

    var whatsapp = function(link) {
      return 'whatsapp://send?text=' + texts.shareText + ' '  + encodeURIComponent(link);
    };

    var email = function(link) {
      return 'mailto:?subject=' + texts.shareSubject + '&body=' + texts.shareText + ' ' + encodeURIComponent(link) + '.';
    };

    var getTarget = function(media) {
      switch(media) {
        case 'fb':
        case 'twitter':
          return '_blank';
        case 'whatsapp':
        case 'email':
          return '_self';
        default:
          return '';
      }
    };

    var compile = function(elem, socialLink) {
      var $elem = angular.element(elem);
      var $scope = $elem.scope();
      $injector = $elem.injector();
      $injector.invoke(function($compile) {
        $compile($elem)($scope);
        $timeout(function() {
          myWindow.location = socialLink;
        }, 0);
      });
    };

    var onSuccess = function(response) {
      var url = $window.ROOT_URL + '#/share/';
      var link = url + response.data;
      var socialLink;
      var target;
      switch(media) {
        case 'fb':
          socialLink = fb(link);
          break;
        case 'twitter':
          socialLink = twitter(link);
          break;
        case 'whatsapp':
          socialLink = whatsapp(link);
          break;
        case 'email':
          socialLink = email(link);
          break;
        default:
          return;
      }

      elem.setAttribute('href', socialLink);
      elem.setAttribute('target', getTarget(media));
      elem.removeAttribute('ng-click');
      compile(elem, socialLink);
    };

    var onError = function() {
      flash.error = data.msg.join();
    };

    var getShareHash = function(changeId) {
      return $http.post('api/v1/change/share', {changeId: changeId}).then(onSuccess, onError);
    };

    return {
      share: function(changeId, _media, $event) {
        myWindow = $window.open('', getTarget(_media));
        elem = $event.currentTarget;
        if (elem.getAttribute('href') == '') {
          media = _media;
          getShareHash(changeId);
        }
      }
    };
  });
