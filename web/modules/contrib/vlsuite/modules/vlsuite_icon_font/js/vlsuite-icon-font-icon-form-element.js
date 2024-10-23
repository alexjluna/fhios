(function (Drupal, once, window) {
  Drupal.behaviors.vlsuite_icon_font_icon_form_element = {
    attach(context) {
      once('vlsuite-icon-font-icon-form-element', '.vlsuite-icon-font-icon-form-element', context).forEach(iconFormElement);
    }
  };

  /**
   * Icon form element.
   */
  function iconFormElement(iconPreviewer) {
    var input = iconPreviewer.querySelector('.vlsuite-icon-font-icon-form-element__input');
    var replacement = iconPreviewer.dataset.vlsuiteIconFontReplacement;
    var icon = iconPreviewer.querySelector('.vlsuite-icon-font-icon-form-element__icon');
    icon.dataset.originalClasses = icon.classList.value;
    if (input.value) {
      if (replacement === 'text') {
        icon.innerText = input.value;
      }
      else if (replacement === 'class') {
        icon.classList.add(input.value);
      }
    }
    input.addEventListener('change',function(e) {
      if (replacement === 'text') {
        icon.innerText = e.target.value;
      }
      else if (replacement === 'class') {
        icon.classList = icon.dataset.originalClasses;
        icon.classList.add(e.target.value);
      }
    }, false);
  }
}(Drupal, once, window));
