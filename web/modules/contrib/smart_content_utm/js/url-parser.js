/**
 * @file
 */

(function (Drupal, options) {

  "use strict";

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.utm = Drupal.smartContent.utm || {};

  var utmParamKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

  Drupal.behaviors.urlParser = {
    attach: function () {
      Drupal.smartContent.utm.init();
    }
  };

  Drupal.smartContent.utm.init = function () {
    Drupal.smartContent.utm['_initialized'] = Drupal.smartContent.utm['_initialized'] || false;
    // Check if has been initialized yet.
    if (!Drupal.smartContent.utm['_initialized']) {
      Drupal.smartContent.utm['_initialized'] = true;
      Drupal.smartContent.utm.mapValues();
    }
  }

  Drupal.smartContent.utm.mapValues = function () {
    var queryString = window.location.search;
    var queryParams = new URLSearchParams(queryString);
    var values = {};
    for (var i = 0; i < utmParamKeys.length; i++) {
      var key = utmParamKeys[i];
      if (queryParams.has(key)) {
        values[key] = queryParams.get(key);
      }
      else {
        values[key] = Drupal.smartContent.utm.getValue(key);
      }
    }
    Drupal.smartContent.storage.setValue('utm', values, options.expireDays * 24 * 60 * 60 * 1000);
  }

  Drupal.smartContent.utm.getValue = function (key) {
    Drupal.smartContent.utm.init();
    var values = Drupal.smartContent.storage.getValue('utm') || {};
    if (values.hasOwnProperty(key)) {
      return values[key];
    }
    return null;
  }


})(Drupal, {expireDays: 30});
