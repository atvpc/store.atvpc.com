require([], function() {
  "use strict";

  var u;
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  u = (("https:" === document.location.protocol) ? "https" : "http") + "://store.atvpc.com/matomo/";
  _paq.push(['setTrackerUrl', u + 'matomo.php']);
  _paq.push(['setSiteId', '1']);
  return _paq;
});
