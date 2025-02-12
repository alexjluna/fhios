/**
 * @file
 * b24_commerce settings form behaviors.
 */

(function ($, window, Drupal) {
  /**
   * Provides the summary information for the b24 default settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the condition summaries.
   */
  Drupal.behaviors.b24CommerceSettingsSummary = {
    attach: function () {
      $('.vertical-tabs__pane').each(function _() {
        $(this).drupalSetSummary(function __(context) {
          if ($(context).find('input[name*="[convert_"]:checked').length) {
            return '<span style="color: green">' + Drupal.t('Convertations enabled') + '</span>';
          }
          return '';
        });
      });
    },
  };
})(jQuery, window, Drupal);
