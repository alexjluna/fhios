/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function ($, Drupal, CKEDITOR) {
  function getFocusedWidget(editor) {
    var widget = editor.widgets.focused;
    if (widget && widget.name === 'image') {
      return widget;
    }
    return null;
  }
  function linkCommandIntegrator(editor) {
    if (!editor.plugins.drupallink) {
      return;
    }
    CKEDITOR.plugins.drupallink.registerLinkableWidget('image');
    editor.getCommand('drupalunlink').on('exec', function (evt) {
      var widget = getFocusedWidget(editor);
      if (!widget || !widget.parts.link) {
        return;
      }
      widget.setData('link', null);
      this.refresh(editor, editor.elementPath());
      evt.cancel();
    });
    editor.getCommand('drupalunlink').on('refresh', function (evt) {
      var widget = getFocusedWidget(editor);
      if (!widget) {
        return;
      }
      this.setState(widget.data.link || widget.wrapper.getAscendant('a') ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED);
      evt.cancel();
    });
  }
  CKEDITOR.plugins.add('drupalimage', {
    requires: 'image2',
    icons: 'drupalimage',
    hidpi: true,
    beforeInit: function beforeInit(editor) {
      editor.on('widgetDefinition', function (event) {
        var widgetDefinition = event.data;
        if (widgetDefinition.name !== 'image') {
          return;
        }
        widgetDefinition.allowedContent = {
          img: {
            attributes: {
              '!src': true,
              '!alt': true,
              width: true,
              height: true
            },
            classes: {}
          }
        };
        widgetDefinition.requiredContent = new CKEDITOR.style({
          element: 'img',
          attributes: {
            src: '',
            alt: ''
          }
        });
        var requiredContent = widgetDefinition.requiredContent.getDefinition();
        requiredContent.attributes['data-entity-type'] = '';
        requiredContent.attributes['data-entity-uuid'] = '';
        widgetDefinition.requiredContent = new CKEDITOR.style(requiredContent);
        widgetDefinition.allowedContent.img.attributes['!data-entity-type'] = true;
        widgetDefinition.allowedContent.img.attributes['!data-entity-uuid'] = true;
        widgetDefinition.downcast = function (element) {
          element.attributes['data-entity-type'] = this.data['data-entity-type'];
          element.attributes['data-entity-uuid'] = this.data['data-entity-uuid'];
        };
        widgetDefinition.upcast = function (element, data) {
          if (element.name !== 'img') {
            return;
          }
          if (element.attributes['data-cke-realelement']) {
            return;
          }
          data['data-entity-type'] = element.attributes['data-entity-type'];
          data['data-entity-uuid'] = element.attributes['data-entity-uuid'];
          return element;
        };
        var originalGetClasses = widgetDefinition.getClasses;
        widgetDefinition.getClasses = function () {
          var classes = originalGetClasses.call(this);
          var captionedClasses = (this.editor.config.image2_captionedClass || '').split(/\s+/);
          if (captionedClasses.length && classes) {
            for (var i = 0; i < captionedClasses.length; i++) {
              if (captionedClasses[i] in classes) {
                delete classes[captionedClasses[i]];
              }
            }
          }
          return classes;
        };
        widgetDefinition._mapDataToDialog = {
          src: 'src',
          alt: 'alt',
          width: 'width',
          height: 'height',
          'data-entity-type': 'data-entity-type',
          'data-entity-uuid': 'data-entity-uuid'
        };
        widgetDefinition._dataToDialogValues = function (data) {
          var dialogValues = {};
          var map = widgetDefinition._mapDataToDialog;
          Object.keys(widgetDefinition._mapDataToDialog).forEach(function (key) {
            dialogValues[map[key]] = data[key];
          });
          return dialogValues;
        };
        widgetDefinition._dialogValuesToData = function (dialogReturnValues) {
          var data = {};
          var map = widgetDefinition._mapDataToDialog;
          Object.keys(widgetDefinition._mapDataToDialog).forEach(function (key) {
            if (dialogReturnValues.hasOwnProperty(map[key])) {
              data[key] = dialogReturnValues[map[key]];
            }
          });
          return data;
        };
        widgetDefinition._createDialogSaveCallback = function (editor, widget) {
          return function (dialogReturnValues) {
            var firstEdit = !widget.ready;
            if (!firstEdit) {
              widget.focus();
            }
            editor.fire('saveSnapshot');
            var container = widget.wrapper.getParent(true);
            var image = widget.parts.image;
            var data = widgetDefinition._dialogValuesToData(dialogReturnValues.attributes);
            widget.setData(data);
            widget = editor.widgets.getByElement(image);
            if (firstEdit) {
              editor.widgets.finalizeCreation(container);
            }
            setTimeout(function () {
              widget.focus();
              editor.fire('saveSnapshot');
            });
            return widget;
          };
        };
        var originalInit = widgetDefinition.init;
        widgetDefinition.init = function () {
          originalInit.call(this);
          if (this.parts.link) {
            this.setData('link', CKEDITOR.plugins.image2.getLinkAttributesParser()(editor, this.parts.link));
          }
        };
      });
      editor.widgets.on('instanceCreated', function (event) {
        var widget = event.data;
        if (widget.name !== 'image') {
          return;
        }
        widget.on('edit', function (event) {
          event.cancel();
          editor.execCommand('editdrupalimage', {
            existingValues: widget.definition._dataToDialogValues(widget.data),
            saveCallback: widget.definition._createDialogSaveCallback(editor, widget),
            dialogTitle: widget.data.src ? editor.config.drupalImage_dialogTitleEdit : editor.config.drupalImage_dialogTitleAdd
          });
        });
      });
      editor.addCommand('editdrupalimage', {
        allowedContent: 'img[alt,!src,width,height,!data-entity-type,!data-entity-uuid]',
        requiredContent: 'img[alt,src,data-entity-type,data-entity-uuid]',
        modes: {
          wysiwyg: 1
        },
        canUndo: true,
        exec: function exec(editor, data) {
          var dialogSettings = {
            title: data.dialogTitle,
            dialogClass: 'editor-image-dialog'
          };
          Drupal.ckeditor.openDialog(editor, Drupal.url("editor/dialog/image/".concat(editor.config.drupal.format)), data.existingValues, data.saveCallback, dialogSettings);
        }
      });
      if (editor.ui.addButton) {
        editor.ui.addButton('DrupalImage', {
          label: Drupal.t('Image'),
          command: 'image'
        });
      }
    },
    afterInit: function afterInit(editor) {
      linkCommandIntegrator(editor);
    }
  });
  CKEDITOR.plugins.image2.getLinkAttributesParser = function () {
    return CKEDITOR.plugins.drupallink.parseLinkAttributes;
  };
  CKEDITOR.plugins.image2.getLinkAttributesGetter = function () {
    return CKEDITOR.plugins.drupallink.getLinkAttributes;
  };
  CKEDITOR.plugins.drupalimage = {
    getFocusedWidget: getFocusedWidget
  };
})(jQuery, Drupal, CKEDITOR);