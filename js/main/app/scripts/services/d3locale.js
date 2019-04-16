'use strict';

/**
 * @ngdoc service
 * @name monApp.d3locale
 * @description
 * # d3locale
 * Service in the monApp.
 */
angular.module('monApp')
  .factory('d3Locale', function() {
    return {
      en: {
        'decimal': '.',
        'thousands': ',',
        'grouping': [3],
        'currency': ['$', ''],
        'dateTime': '%a %b %e %X %Y',
        'date': '%m/%d/%Y',
        'time': '%H:%M:%S',
        'periods': ['AM', 'PM'],
        'days': ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'shortDays': ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'months': ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'shortMonths': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      de: {
        'decimal': '.',
        'thousands': '\'',
        'grouping': [3],
        'currency': ['CHF', ''],
        'dateTime': '%a %b %e %X %Y',
        'date': '%d.%m.%Y',
        'time': '%H:%M:%S',
        'periods': ['AM', 'PM'],
        'days': ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
        'shortDays': ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
        'months': ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        'shortMonths': ['Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']
      }
    };
  });
