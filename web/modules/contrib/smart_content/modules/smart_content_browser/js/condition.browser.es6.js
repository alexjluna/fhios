/**
 * @file
 * Provides condition values for all browser conditions.
 */

(function (Drupal) {

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};

  Drupal.smartContent.plugin.Field['browser:language'] = function (condition) {
    return window.navigator.userLanguage || window.navigator.language;
  };

  Drupal.smartContent.plugin.Field['browser:platform_os'] = function (condition) {
    let platform = window.navigator.platform;
    let ua = window.navigator.userAgent;
    let os = '';
    if (platform === 'MacIntel' || platform === 'MacPPC') {
      os = 'macosx';
    }
    else if (platform === 'CrOS') {
      os = 'chromeos';
    }
    else if (platform === 'Win32' || platform === 'Win64') {
      os = 'windows';
    }
    else if (/Windows/i.test(ua)) {
      os = 'windows';
    }
    else if (/Android/i.test(ua) || /Linux armv7l/i.test(platform)) {
      os = 'android';
    }
    else if (/Linux/i.test(platform)) {
      os = 'linux';
    }
    // IE11 includes 'iPhone' in its userAgent, so we need to check for it.
    else if (/iPad|iPhone|iPod/i.test(ua) && !window.MSStream) {
      os = 'ios'
    }
    else if (/Nintendo/i.test(platform)) {
      os = 'nintendo';
    }
    else if (/PlayStation/i.test(platform)) {
      os = 'playstation';
    }
    return os;
  };

  Drupal.smartContent.plugin.Field['browser:mobile'] = function (condition) {
    return Boolean((typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1));
  };

  Drupal.smartContent.plugin.Field['browser:cookie_enabled'] = function (condition) {
    return navigator.cookieEnabled;
  };

  Drupal.smartContent.plugin.Field['browser:localstorage'] = function (condition) {
    return localStorage[condition.settings.key];
  };

  Drupal.smartContent.plugin.Field['browser:width'] = function (condition) {
    return Math.max(
      document.body.scrollWidth,
      document.documentElement.scrollWidth,
      document.body.offsetWidth,
      document.documentElement.offsetWidth,
      document.documentElement.clientWidth
    );
  };

  Drupal.smartContent.plugin.Field['browser:height'] = function (condition) {
    return Math.max(
      document.body.scrollHeight,
      document.documentElement.scrollHeight,
      document.body.offsetHeight,
      document.documentElement.offsetHeight,
      document.documentElement.clientHeight
    );
  };

  Drupal.smartContent.plugin.Field['browser:cookie'] = function (condition) {
    let v = document.cookie.match('(^|;) ?' + condition.field.settings.key + '=([^;]*)(;|$)');
    return v ? v[2] : null;
  };

})(Drupal);
