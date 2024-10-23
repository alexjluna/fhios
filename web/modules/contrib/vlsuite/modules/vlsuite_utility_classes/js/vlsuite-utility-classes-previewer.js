(function ($, Drupal, once, window) {
  Drupal.behaviors.vlsuite_utility_classes_previewer = {
    attach(context) {
      once('vlsuite-utility-classes__previewer', '.vlsuite-utility-classes .vlsuite-utility-classes__previewer', context).forEach(utilityClassesPreviewer);
    }
  };

  /**
   * Utility classes previewer.
   */
  function utilityClassesPreviewer(previewBox) {
    var appliedKeysClasses = {};
    $('.vlsuite-utility-classes .vlsuite-utility-classes__previewer-option').change(function (e) {
      var classesSuffixMap = $(event.target).data('class-suffix-map');
      var classesString = String(classesSuffixMap[event.target.value]);
      var classesToApply = $(event.target).data('class-prefix') + classesString;
      var classesKey = $(event.target).data('class-key');
      if (classesString.length) {
        if (appliedKeysClasses[classesKey] !== undefined) {
          $(previewBox).removeClass(appliedKeysClasses[classesKey]);
        }
        appliedKeysClasses[classesKey] = classesToApply;
        $(previewBox).addClass(classesToApply);
      }
      else {
        $(previewBox).removeClass(appliedKeysClasses[classesKey]);
        delete appliedKeysClasses[classesKey];
      }
    });
  }
}(jQuery, Drupal, once, window));
