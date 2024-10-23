(function (drupalSettings, Drupal, once, window) {
  Drupal.behaviors.vlsuite_animations_previewer = {
    attach(context) {
      once('vlsuite-animations__previewer', '.vlsuite-animations', context).forEach(animationsPreviewer);
    }
  };

  /**
   * Animations previewer.
   */
  function animationsPreviewer(previewWrapper) {
    var previewerBox = previewWrapper.querySelector('.vlsuite-animations__previewer');
    var previewerOptions = previewWrapper.querySelectorAll('.vlsuite-animations__previewer-option').forEach(function (previewerOption) {
      previewerOption.addEventListener('input', function (event) {
        if (event.target.value) {
          previewerBox.classList.add(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
          previewerBox.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
          previewerBox.classList.add(...drupalSettings.vlsuite_animations_map['animations'][event.target.value].split(' '));
        }
      });
    });
    previewerBox.addEventListener('animationend', function (e) {
      e.target.classList.remove(...e.target.classList);
      e.target.classList.add('vlsuite-animations__previewer');
    });
    previewerBox.addEventListener('animationcancel', function (e) {
      e.target.classList.remove(...e.target.classList);
      e.target.classList.add('vlsuite-animations__previewer');
    });
  }
}(drupalSettings, Drupal, once, window));
