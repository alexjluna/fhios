(function (Drupal) {

  // Initialize objects.
  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};

  Drupal.smartContent.plugin.Field['utm:utm_source'] = function (condition) {
    return Drupal.smartContent.utm.getValue('utm_source');
  };
  Drupal.smartContent.plugin.Field['utm:utm_medium'] = function (condition) {
    return Drupal.smartContent.utm.getValue('utm_medium');
  };
  Drupal.smartContent.plugin.Field['utm:utm_campaign'] = function (condition) {
    return Drupal.smartContent.utm.getValue('utm_campaign');
  };
  Drupal.smartContent.plugin.Field['utm:utm_term'] = function (condition) {
    return Drupal.smartContent.utm.getValue('utm_term');
  };
  Drupal.smartContent.plugin.Field['utm:utm_content'] = function (condition) {
    return Drupal.smartContent.utm.getValue('utm_content');
  };

})(Drupal);
