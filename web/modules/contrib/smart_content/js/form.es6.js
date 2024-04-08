/**
 * @file
 * Form behaviors for smart content.
 */

(function ($) {
  Drupal.behaviors.form = {
    attach: function (context, settings) {
      // todo: Make sure this doesn't break when multiple forms on same page.
      let checkbox = $('.segment-additional-settings-container [class^=smart-variations-default-]', context);
      checkbox.change(function () {
        if ($(this).is(':checked')) {
          $(checkbox).each(function () {
            if (!$(this).is(':checked')) {
              $(this).attr('disabled', true);
            }
          });
        }
        else {
          $(checkbox).each(function () {
            $(this).attr('disabled', false);
          });
        }
      });
    }
  };
})(jQuery);
