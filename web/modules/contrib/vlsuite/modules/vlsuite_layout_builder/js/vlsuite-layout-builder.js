(function ($, Drupal, once, window) {

  Drupal.behaviors.vlsuite_layout_builder = {
    attach(context) {
      once('vlsuite-layout-builder-live-preview', '.layout-builder__live-preview__toggler__link', context).forEach(layoutBuilderLivePreview);
      once('vlsuite-layout-builder-edit-live-preview', 'form[data-layout-builder-target-highlight-id] [data-editor-for]', context).forEach(layoutBuilderEditLivePreview);
    }
  };

  /**
   * Layout builder edit live preview.
   */
  function layoutBuilderEditLivePreview(editorFor) {
    var editorElement = document.querySelector('#' + editorFor.getAttribute('data-editor-for'));
    var originalValue = editorElement.getAttribute('data-editor-value-original');
    var fieldClass = 'field--name-' + editorElement.getAttribute('name').split('settings[block_form][')[1].split(']')[0].replaceAll('_', '-');
    var uuid = editorElement.closest('[data-layout-builder-target-highlight-id]').getAttribute('data-layout-builder-target-highlight-id');
    var targetElement = document.querySelector('[data-layout-block-uuid="' + uuid + '"] .' + fieldClass);
    if (targetElement !== null && editorElement.hasAttribute('data-ckeditor5-id')) {
      $(editorElement).on('formUpdated', function (e) {
        targetElement.innerHTML = Drupal.CKEditor5Instances.get(editorElement.getAttribute('data-ckeditor5-id')).getData();
        $('div#vlsuite-modal').one('dialogclose', function () {
          targetElement.innerHTML = originalValue;
        });
      });
      var smallTag = document.createElement('small');
      smallTag.appendChild(document.createTextNode(Drupal.t('*Live edit preview active for this field.')));
      smallTag.classList.add('vlsuite-layout-builder-edit-live-preview-mark');
      editorElement.parentNode.appendChild(smallTag);
      $(window).one("dialog:beforecreate", function (e, dialog, $dialogElement, dialogSettings) {
        dialogSettings.dialogClass = "ui-dialog-fixed";
        dialogSettings.resizable = false;
        dialogSettings.autoResize = false;
        dialogSettings.draggable = true;
        dialogSettings.position = {my: "right top", at: "right-10 bottom", of: window};
      });
    }
  }

  /**
   * Layout builder live preview.
   */
  function layoutBuilderLivePreview(toggler) {
    toggler.addEventListener('click', function (e) {
      e.preventDefault();
      document.querySelector('.layout-builder').classList.toggle('live-preview-active');
    });
  }

}(jQuery, Drupal, once, window));
