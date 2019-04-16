'use strict';

describe('Service: colorHandler', function () {

  // load the service's module
  beforeEach(module('monApp'));

  // instantiate service
  var colorHandler;
  beforeEach(inject(function (_colorHandler_) {
    colorHandler = _colorHandler_;
  }));

  it('should get right rgb from valid hex', function () {
    expect(colorHandler.hex2rgb('#81CBCB')).toEqual({ r: 129, g: 203, b: 203 });
    expect(colorHandler.hex2rgb('81CBCB')).toEqual({ r: 129, g: 203, b: 203 });
    expect(colorHandler.hex2rgb('#81cbcb')).toEqual({ r: 129, g: 203, b: 203 });
  });

  it('should get null from invalid hex value', function () {
    expect(colorHandler.hex2rgb('#81hbcb')).toBe(null);
    // is valid hex code but the function wants 6 inits
    expect(colorHandler.hex2rgb('#83b')).toBe(null);
    expect(colorHandler.hex2rgb('#81cbcba')).toBe(null);
  });

  it('should get right hex from valid rgb', function() {
    expect(colorHandler.rgb2hex(225, 174, 27)).toEqual('#e1ae1b');
  });

  it ('should get null on invalid rgb values', function() {
    expect(colorHandler.rgb2hex(400, -1, 37)).toBe(null);
    expect(colorHandler.rgb2hex(null, 1, 37)).toBe(null);
    expect(colorHandler.rgb2hex(null, undefined, 37)).toBe(null);
  });

  it('should get the same with reversing', function () {
    expect(colorHandler.hex2rgb('#81CBCB')).toEqual({ r: 129, g: 203, b: 203 });
    expect(colorHandler.rgb2hex(129, 203, 203)).toEqual('#81cbcb');
  });

  it('should get an array of gradiants in hex from hex range', function() {
    expect(colorHandler.getGradiantRange(7, '#81cbcb', '#e1ae1b')).toEqual([ "#81cbcb", "#91c6ae", "#a1c190", "#b1bd73", "#c1b856", "#d1b338", "#e1ae1b" ]);
  });

  it('should get the same color back', function() {
    expect(colorHandler.getGradiantRange(2, '#81cbcb', '#e1ae1b')).toEqual(['#81cbcb', '#e1ae1b']);
  });

  it('should get the first color back', function() {
    expect(colorHandler.getGradiantRange(1, '#81cbcb', '#e1ae1b')).toEqual(['#81cbcb']);
  });

  it('should get an empty array when fail', function() {
    expect(colorHandler.getGradiantRange(7, '#siasd', '#81cbcb')).toEqual([]);
    expect(colorHandler.getGradiantRange(7, '#81cbcb', null)).toEqual([]);
    expect(colorHandler.getGradiantRange(0, '#81cbcb', '#e1ae1b')).toEqual([]);
  });
});
