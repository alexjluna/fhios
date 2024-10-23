(function ($, Drupal, once, window) {

  Drupal.vlsuite_utility_classes_settings_form_apply_to_filter = null;

  Drupal.behaviors.vlsuite_utility_classes_settings_form = {
    attach(context) {
      once('vlsuite-utility-classes-settings-form-apply-to-filter', '.vlsuite-utility-classes-settings-form-apply-to-wrapper', context).forEach(applyToFilter);
    }
  };

  /**
   * Utility classes settings form apply to filter.
   */
  function applyToFilter(applyToFilter) {
    var apply_to_filter = Drupal.vlsuite_utility_classes_settings_form_apply_to_filter || calcApplyToFilterValue();
    if (apply_to_filter !== null) {
      applyToFilter.querySelectorAll('input[type="checkbox"]:not([value="' + apply_to_filter + '"])').forEach(function (checkbox) {
        checkbox.parentNode.style.display = 'none';
      });
    }

    function calcApplyToFilterValue() {
      var apply_to_filter_raw = new RegExp('[\?&]apply-to-filter=([^&#]*)').exec(window.location.href);
      Drupal.vlsuite_utility_classes_settings_form_apply_to_filter = apply_to_filter_raw !== null ? (decodeURIComponent(apply_to_filter_raw[1]) || null) : null;
      return Drupal.vlsuite_utility_classes_settings_form_apply_to_filter;
    }
  }
}(jQuery, Drupal, once, window));
