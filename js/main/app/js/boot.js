;(function() {
  'use strict';

  var pre = '';
  if (window.GLOBAL_APPTYPE === 'app') { pre = '_app'; }
  if (location.hash === '' || location.hash.indexOf('/') === -1) {
    location.href = 'willkommen' + pre + location.hash;
  }
  if (location.hash == '#/prices' || location.hash == '#/contact') {
    location.href = 'willkommen' + pre + '#' + location.hash.substr(2);
  }
  if (location.hash == '#/product') {
    location.href = 'willkommen' + pre + '#' + location.hash.substr(2) + 's';
  }
})();
