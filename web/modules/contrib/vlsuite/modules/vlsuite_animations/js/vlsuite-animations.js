(function (Drupal, drupalSettings, once, window) {
  Drupal.behaviors.vlsuite_animations = {
    attach(context) {
      once('vlsuite-animations', 'div[data-vlsuite-animations]', context).forEach(animations);
    }
  };

  /**
   * Animations.
   */
  function animations(initElement) {
    var config = JSON.parse(initElement.dataset.vlsuiteAnimations);
    if (!config || typeof window.IntersectionObserver !== 'function') {
      return;
    }
    if ((config.entrance_down || config.entrance_up || config.exit_down || config.exit_up || config.entrance) && !config.infinite) {
      var observer_callback = function (entries) {
        entries.forEach(function (entry) {
          var collision = entry.boundingClientRect.y < (entry.rootBounds && entry.rootBounds.y ? entry.rootBounds : 0) ? 'up' : 'down';
          if (entry.target.classList.contains(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '))) {
            return;
          }
          if (entry.isIntersecting) {
            // Entrance.
            if (entry.intersectionRatio >= drupalSettings.vlsuite_animations_map['threshold']) {
              if (entry.target.classList.contains(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '))) {
                return;
              }
              // Entrance.
              if (config.entrance && drupalSettings.vlsuite_animations_map['animations'][config.entrance]) {
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['animations'][config.entrance].split(' '));
              }
              // Entrance down collision.
              else if (collision === 'down' && config.entrance_down && drupalSettings.vlsuite_animations_map['animations'][config.entrance_down]) {
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['animations'][config.entrance_down].split(' '));
              }
              // Entrance up collision.
              else if (collision === 'up' && config.entrance_up && drupalSettings.vlsuite_animations_map['animations'][config.entrance_up]) {
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['animations'][config.entrance_up].split(' '));
              }
            }
            // Exit.
            else {
              if (!entry.target.classList.contains(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '))) {
                return;
              }
              // Entrance.
              if (config.entrance) {
                entry.target.classList.remove(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '));
                return;
              }
              // Exit down.
              if (collision === 'down' && config.exit_down && drupalSettings.vlsuite_animations_map['animations'][config.exit_down]) {
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
                entry.target.classList.remove(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['animations'][config.exit_down].split(' '));
              }
              // Exit up.
              else if (collision === 'up' && config.exit_up && drupalSettings.vlsuite_animations_map['animations'][config.exit_up]) {
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
                entry.target.classList.remove(...drupalSettings.vlsuite_animations_map['is_shown_classes'].split(' '));
                entry.target.classList.add(...drupalSettings.vlsuite_animations_map['animations'][config.exit_up].split(' '));
              }
            }
          }
        });
      };
      var observer_options = {
        root: null,
        threshold: [0, drupalSettings.vlsuite_animations_map['threshold']],
        rootMargin: drupalSettings.vlsuite_animations_map['root_margin']
      };
      var observer = new window.IntersectionObserver(observer_callback, observer_options);
      observer.observe(initElement);
    }
    if (config.infinite && drupalSettings.vlsuite_animations_map['animations'][config.infinite]) {
      initElement.classList.add(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
      initElement.classList.add(...drupalSettings.vlsuite_animations_map['infinite_classes'].split(' '));
      initElement.classList.add(...drupalSettings.vlsuite_animations_map['animations'][config.infinite].split(' '));
    }
    function clean(element) {
      element.classList.remove(...drupalSettings.vlsuite_animations_map['is_playing_classes'].split(' '));
      element.classList.remove(...drupalSettings.vlsuite_animations_map['main_classes'].split(' '));
      if (config.entrance && drupalSettings.vlsuite_animations_map['animations'][config.entrance]) {
        element.classList.remove(...drupalSettings.vlsuite_animations_map['animations'][config.entrance].split(' '));
      }
      if (config.entrance_down && drupalSettings.vlsuite_animations_map['animations'][config.entrance_down]) {
        element.classList.remove(...drupalSettings.vlsuite_animations_map['animations'][config.entrance_down].split(' '));
      }
      if (config.entrance_up && drupalSettings.vlsuite_animations_map['animations'][config.entrance_up]) {
        element.classList.remove(...drupalSettings.vlsuite_animations_map['animations'][config.entrance_up].split(' '));
      }
      if (config.exit_down && drupalSettings.vlsuite_animations_map['animations'][config.exit_down]) {
        element.classList.remove(...drupalSettings.vlsuite_animations_map['animations'][config.exit_down].split(' '));
      }
      if (config.exit_up && drupalSettings.vlsuite_animations_map['animations'][config.exit_up]) {
        element.classList.remove(...drupalSettings.vlsuite_animations_map['animations'][config.exit_up].split(' '));
      }
    }
    initElement.addEventListener('animationend', function (e) {
      clean(e.target);
    });
    initElement.addEventListener('animationcancel', function (e) {
      clean(e.target);
    });
  }
}(Drupal, drupalSettings, once, window));
