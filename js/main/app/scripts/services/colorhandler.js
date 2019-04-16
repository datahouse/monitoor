'use strict';

/**
 * @ngdoc service
 * @name monApp.colorHandler
 * @description
 * # colorHandler
 * Service in the monApp.
 */
angular.module('monApp')
  .service('colorHandler', function () {
    this.hex2rgb = function(hex) {
      var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
      } : null;
    };

    this.rgb2hex = function(r, g, b) {
      if (
        r !== undefined && r !== null &&
        g !== undefined && g !== null &&
        b !== undefined && b !== null &&
        r >= 0 && r <= 255 &&
        g >= 0 && g <= 255 &&
        b >= 0 && b <= 255
      ) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
      }

      return null;
    };

    this.getGradiantRange = function(numElements, begin, end) {
      var rgbBegin = this.hex2rgb(begin);
      var rgbEnd = this.hex2rgb(end);
      if (rgbBegin === null || rgbEnd === null) return [];
      return this.colorBlender(numElements, rgbBegin, rgbEnd);
    };

    this.colorBlender = function(numElements, begin, end) {
      var colors = [];
      if (numElements < 1) return [];
      else if (numElements < 2) {
        colors.push(this.rgb2hex(begin.r, begin.g, begin.b));

        return colors;
      } else if (numElements < 3) {
        colors.push(this.rgb2hex(begin.r, begin.g, begin.b));
        colors.push(this.rgb2hex(end.r, end.g, end.b));

        return colors;
      }

      --numElements;

      var rIndex = (end.r - begin.r) / numElements;
      var gIndex = (end.g - begin.g) / numElements;
      var bIndex = (end.b - begin.b) / numElements;

      colors.push(this.rgb2hex(begin.r, begin.g, begin.b));
      for (var i = 1; i < numElements; ++i) {
        colors.push(
          this.rgb2hex(
            Math.round(begin.r + rIndex * i),
            Math.round(begin.g + gIndex * i),
            Math.round(begin.b + bIndex * i)
          )
        );
      }
      colors.push(this.rgb2hex(end.r, end.g, end.b));

      return colors;
    };
  });
