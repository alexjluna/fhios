(function (Drupal, drupalSettings, once, window) {
  function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
  function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
  function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
  function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
  function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
  function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
  function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
  function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
  function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
  function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
  Drupal.behaviors.vlsuite_utility_classes_live_previewer = {
    attach: function attach(context) {
      once('vlsuite-utility-classes-live-previewer', '[data-vlsuite-utility-classes-live-previewer-apply-to]', context).forEach(Drupal.vlsuite_utility_classes_live_previewer);
    }
  };
  Drupal.vlsuite_utility_classes_live_previewer = function (element) {
    if (_typeof(window.FloatingUIDOM) !== 'object') {
      return;
    }
    var skip = false;
    var main = document.createElement('div');
    main.classList.add('vlsuite-utility-classes-live-previewer');
    var applyTo = element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo;
    var type = element.dataset.vlsuiteUtilityClassesLivePreviewerType;
    var togglerLink = document.createElement('a');
    togglerLink.href = '#';
    togglerLink.title = Drupal.t('Appearance') + ': ' + Drupal.vlsuite_utility_classes_live_previewer_type_title_map[type];
    togglerLink.classList.add('vlsuite-utility-classes-live-previewer__toggler');
    togglerLink.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      main.classList.toggle('active');
      if (main.classList.contains('active')) {
        Drupal.vlsuite_utility_classes_type_element_setDefaults(element, type);
      }
      Drupal.vlsuite_utility_classes_live_previewer_type_position_refresh(element, overflow, main, arrow, type);
    });
    main.appendChild(togglerLink);
    main.classList.add('vlsuite-utility-classes-live-previewer--' + type);
    var overflow = document.createElement('div');
    overflow.classList.add('vlsuite-utility-classes-live-previewer__overflow');
    var arrow = document.createElement('div');
    arrow.classList.add('vlsuite-utility-classes-live-previewer__arrow');
    var utilityGroupTitle = document.createElement('h2');
    utilityGroupTitle.appendChild(document.createTextNode(togglerLink.title));
    main.appendChild(utilityGroupTitle);
    var uuid = element.closest('[data-layout-block-uuid]') ? element.closest('[data-layout-block-uuid]').dataset.layoutBlockUuid : '_none';
    var delta = (element.closest('[data-layout-delta]') ? element.closest('[data-layout-delta]').dataset.layoutDelta : null) || (element.querySelector('[data-layout-delta]') ? element.querySelector('[data-layout-delta]').dataset.layoutDelta : null) || (element.parentNode.querySelector('[data-layout-delta]') ? element.parentNode.querySelector('[data-layout-delta]').dataset.layoutDelta : '_none');
    var linkArgumentsBase = [element.closest('[data-vlsuite-utility-classes-live-previewer-apply-to-url]').dataset.vlsuiteUtilityClassesLivePreviewerApplyToUrl, delta, uuid, applyTo];
    Drupal.vlsuite_utility_classes_type_element_setDefaults(element, type);
    var defaults = element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
    var configureLink = element.closest('[data-vlsuite-utility-classes-configure-url]');
    element.dataset.vlsuiteUtilityClassesLivePreviewerIdentifiers.split(',').forEach(function (currentIdentifier) {
      if (currentIdentifier === '') {
        if (configureLink) {
          var emptyElement = document.createElement('span');
          emptyElement.classList.add('vlsuite-utility-classes-live-previewer__empty');
          emptyElement.appendChild(document.createTextNode(Drupal.t('Any enabled, configure using link below')));
          overflow.appendChild(emptyElement);
        } else {
          skip = true;
        }
        return;
      }
      var utilityList = document.createElement('ul');
      var utilityListTitle = document.createElement('h3');
      var isColumnWidths = currentIdentifier.includes(':column_widths');
      var defaultApplies = !isColumnWidths;
      var iconReplacement = drupalSettings.vlsuite_icon_font_map['replacement'];
      var icon = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].icon;
      if (isColumnWidths) {
        icon = drupalSettings.vlsuite_utility_classes_map['column_widths_icon'];
      }
      if (icon) {
        var _iconElement$classLis;
        var iconElement = document.createElement('span');
        (_iconElement$classLis = iconElement.classList).add.apply(_iconElement$classLis, _toConsumableArray(drupalSettings.vlsuite_icon_font_map['main_classes'].split(' ')));
        if (iconReplacement === 'text') {
          iconElement.appendChild(document.createTextNode(icon));
        } else if (iconReplacement === 'class') {
          iconElement.classList.add(icon);
        }
        utilityListTitle.appendChild(iconElement);
      }
      utilityListTitle.appendChild(document.createTextNode(drupalSettings.vlsuite_utility_classes_map[currentIdentifier].visual_name));
      if (defaultApplies) {
        var utilityListItem = document.createElement('li');
        utilityListItem.classList.add('vlsuite-utility-classes-live-previewer__default');
        var utilityDefaultLink = document.createElement('a');
        utilityDefaultLink.appendChild(document.createTextNode(Drupal.t('Default')));
        var linkDefaultArguments = linkArgumentsBase.slice();
        linkDefaultArguments.push(currentIdentifier, '_none');
        utilityDefaultLink.href = linkDefaultArguments.join('/');
        utilityDefaultLink.addEventListener("mouseover", function (e) {
          e.stopPropagation();
          Drupal.vlsuite_utility_classes_live_previewer_type_element_apply_classes(element, type, currentIdentifier, '');
        }, false);
        utilityDefaultLink.addEventListener("mouseout", function (e) {
          e.stopPropagation();
          Drupal.vlsuite_utility_classes_type_element_restore(element, type, currentIdentifier);
        }, false);
        if (defaults === false || defaults[currentIdentifier] === undefined) {
          utilityDefaultLink.classList.add('active');
        }
        utilityDefaultLink.addEventListener("click", function (e) {
          e.preventDefault();
          Drupal.ajax({
            url: e.target.href,
            progress: {
              type: 'fullscreen'
            }
          }).execute();
        }, false);
        utilityListItem.appendChild(utilityDefaultLink);
        utilityList.appendChild(utilityListItem);
      }
      Object.keys(drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values).forEach(function (currentValue) {
        var utilityListItem = document.createElement('li');
        var link_title = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values[currentValue].visual_name;
        if (link_title === undefined) {
          link_title = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values[currentValue];
        }
        var utilityValueLink = document.createElement('a');
        var icon = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values[currentValue].icon;
        if (icon) {
          var _iconElement$classLis2;
          var iconElement = document.createElement('span');
          (_iconElement$classLis2 = iconElement.classList).add.apply(_iconElement$classLis2, _toConsumableArray(drupalSettings.vlsuite_icon_font_map['main_classes'].split(' ')));
          if (iconReplacement === 'text') {
            iconElement.appendChild(document.createTextNode(icon));
          } else if (iconReplacement === 'class') {
            iconElement.classList.add(icon);
          }
          utilityValueLink.appendChild(iconElement);
        }
        utilityValueLink.appendChild(document.createTextNode(link_title));
        if (defaults && defaults[currentIdentifier] === currentValue) {
          utilityValueLink.classList.add('active');
        }
        var linkOptionArguments = linkArgumentsBase.slice();
        linkOptionArguments.push(currentIdentifier, currentValue);
        utilityValueLink.href = linkOptionArguments.join('/');
        utilityValueLink.addEventListener("mouseover", function (e) {
          e.stopPropagation();
          Drupal.vlsuite_utility_classes_live_previewer_type_element_apply_classes(element, type, currentIdentifier, currentValue);
        }, false);
        utilityValueLink.addEventListener("mouseout", function (e) {
          e.stopPropagation();
          Drupal.vlsuite_utility_classes_type_element_restore(element, type, currentIdentifier);
        }, false);
        utilityValueLink.addEventListener("click", function (e) {
          e.preventDefault();
          Drupal.ajax({
            url: e.target.href,
            progress: {
              type: 'fullscreen'
            }
          }).execute();
        }, false);
        utilityListItem.appendChild(utilityValueLink);
        utilityList.appendChild(utilityListItem);
      });
      overflow.appendChild(utilityListTitle);
      overflow.appendChild(utilityList);
    });
    if (skip) {
      return;
    }
    main.appendChild(arrow);
    main.appendChild(overflow);
    element.closest('[data-vlsuite-utility-classes-live-previewer-apply-to-url]').addEventListener('mouseenter', function (e) {
      e.stopPropagation();
      main.classList.add('vlsuite-visible');
      if (main.matches(':hover')) {
        main.classList.add('active');
      }
      Drupal.vlsuite_utility_classes_live_previewer_type_position_refresh(element, overflow, main, arrow, type);
    });
    element.closest('[data-vlsuite-utility-classes-live-previewer-apply-to-url]').addEventListener('mouseleave', function (e) {
      e.stopPropagation();
      if (main.matches(':hover') === false) {
        main.classList.remove('active');
        main.classList.remove('vlsuite-visible');
      }
    });
    if (configureLink) {
      var configLink = document.createElement('a');
      configLink.href = element.closest('[data-vlsuite-utility-classes-configure-url]').dataset.vlsuiteUtilityClassesConfigureUrl + '?' + 'apply-to-filter=' + encodeURIComponent(applyTo) + '&destination=' + encodeURIComponent(window.location.pathname);
      configLink.title = Drupal.t('Configure (apply to scope utilities)');
      configLink.classList.add('vlsuite-utility-classes-live-previewer__configure');
      overflow.appendChild(configLink);
    }
    element.closest('.layout-builder').appendChild(main);
  };
  Drupal.vlsuite_utility_classes_live_previewer_type_title_map = {
    'section': Drupal.t('Section'),
    'region_top': Drupal.t('Region top'),
    'region_bottom': Drupal.t('Region bottom'),
    'main_regions': Drupal.t('Main regions'),
    'media_bg': Drupal.t('Media background'),
    'row': Drupal.t('All regions'),
    'block': Drupal.t('Block'),
    'field': Drupal.t('Field'),
    'item': Drupal.t('Item')
  };
  Drupal.vlsuite_utility_classes_live_previewer_type_position_map = {
    'section': ['top', 'top-start'],
    'region_top': ['right', 'bottom-end'],
    'region_bottom': ['right', 'bottom-end'],
    'main_regions': ['top'],
    'media_bg': ['top-start', 'top-end'],
    'row': ['left', 'bottom-start'],
    'block': ['bottom'],
    'field': ['top-start', 'top-end'],
    'item': ['top-end', 'bottom-end']
  };
  Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope = {
    'section': 'self',
    'region_top': 'region',
    'region_bottom': 'region',
    'main_regions': 'section',
    'media_bg': 'self',
    'row': 'self',
    'block': 'self',
    'field': 'block',
    'item': 'block'
  };
  Drupal.vlsuite_utility_classes_live_previewer_type_position_refresh = function (element, mainWrapperOverflow, mainWrapper, mainWrapperArrow, type) {
    window.FloatingUIDOM.computePosition(element, mainWrapper, {
      placement: Drupal.vlsuite_utility_classes_live_previewer_type_position_map[type][0],
      middleware: [window.FloatingUIDOM.offset(6), window.FloatingUIDOM.flip({
        crossAxis: false,
        fallbackPlacements: Drupal.vlsuite_utility_classes_live_previewer_type_position_map[type]
      }), window.FloatingUIDOM.size({
        apply: function apply(_ref) {
          var availableHeight = _ref.availableHeight;
          Object.assign(mainWrapperOverflow.style, {
            maxHeight: "".concat(availableHeight - 70, "px")
          });
        },
        padding: 10
      }), window.FloatingUIDOM.arrow({
        element: mainWrapperArrow,
        padding: 7
      })]
    }).then(function (_ref2) {
      var x = _ref2.x,
        y = _ref2.y,
        placement = _ref2.placement,
        middlewareData = _ref2.middlewareData;
      Object.assign(mainWrapper.style, {
        top: "".concat(y, "px"),
        left: "".concat(x, "px")
      });
      var _middlewareData$arrow = middlewareData.arrow,
        arrowX = _middlewareData$arrow.x,
        arrowY = _middlewareData$arrow.y;
      var staticSide = {
        top: 'bottom',
        right: 'left',
        bottom: 'top',
        left: 'right'
      }[placement.split('-')[0]];
      Object.assign(mainWrapperArrow.style, _defineProperty({
        left: arrowX !== null ? "".concat(arrowX, "px") : '',
        top: arrowY !== null ? "".concat(arrowY, "px") : '',
        right: '',
        bottom: ''
      }, staticSide, "".concat(-mainWrapperArrow.offsetWidth / 2, "px")));
    });
  };
  Drupal.vlsuite_utility_classes_type_element_setDefaults = function (element, type) {
    element.dataset.originalClasses = element.classList.value;
    if (type === 'main_regions' || Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'region') {
      element.querySelector('[data-region]').dataset.originalClasses = element.querySelector('[data-region]').classList.value;
    }
  };
  Drupal.vlsuite_utility_classes_type_element_restore = function (element, type, identifier) {
    var isColumnWidths = identifier.includes(':column_widths');
    if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'self') {
      element.classList = element.dataset.originalClasses;
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'block') {
      element.closest('[data-layout-block-uuid]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach(function (groupelement, index) {
        groupelement.classList = groupelement.dataset.originalClasses;
      });
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'region') {
      element.querySelector('[data-region]').classList = element.querySelector('[data-region]').dataset.originalClasses;
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'section') {
      element.closest('[data-layout-delta]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach(function (groupelement, index) {
        if (type === 'main_regions' && !isColumnWidths) {
          groupelement.querySelector('[data-region]').classList = groupelement.querySelector('[data-region]').dataset.originalClasses;
        } else {
          groupelement.classList = groupelement.dataset.originalClasses;
        }
      });
    }
  };
  Drupal.vlsuite_utility_classes_live_previewer_type_element_apply_classes = function (element, type, identifier, value) {
    var isColumnWidths = identifier.includes(':column_widths');
    var classes;
    if (isColumnWidths) {
      classes = {};
      var auto_classes = [];
      if (value.includes('auto')) {
        auto_classes = drupalSettings.vlsuite_utility_classes_map['column_widths']['auto'];
      }
      value.split('-').forEach(function (columnWidthValue, olumnWidthIndex) {
        if (columnWidthValue !== 'auto') {
          classes[columnWidthValue] = drupalSettings.vlsuite_utility_classes_map['column_widths'][columnWidthValue].concat(auto_classes);
        }
      });
    } else {
      classes = value.length ? drupalSettings.vlsuite_utility_classes_map[identifier].values[value].classes : null;
    }
    if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'self') {
      var defaults = element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
      if (defaults && defaults[identifier] !== undefined) {
        var _element$classList;
        (_element$classList = element.classList).remove.apply(_element$classList, _toConsumableArray(drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes));
      }
      if (classes !== null) {
        var _element$classList2;
        (_element$classList2 = element.classList).add.apply(_element$classList2, _toConsumableArray(classes));
      }
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'block') {
      element.closest('[data-layout-block-uuid]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach(function (groupelement, index) {
        var defaults = groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
        if (defaults && defaults[identifier] !== undefined) {
          var _groupelement$classLi;
          (_groupelement$classLi = groupelement.classList).remove.apply(_groupelement$classLi, _toConsumableArray(drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes));
        }
        if (classes !== null) {
          var _groupelement$classLi2;
          (_groupelement$classLi2 = groupelement.classList).add.apply(_groupelement$classLi2, _toConsumableArray(classes));
        }
      });
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'region') {
      var defaults = element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
      if (defaults && defaults[identifier] !== undefined) {
        var _element$querySelecto;
        (_element$querySelecto = element.querySelector('[data-region]').classList).remove.apply(_element$querySelecto, _toConsumableArray(drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes));
      }
      if (classes !== null) {
        var _element$querySelecto2;
        (_element$querySelecto2 = element.querySelector('[data-region]').classList).add.apply(_element$querySelecto2, _toConsumableArray(classes));
      }
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'section') {
      element.closest('[data-layout-delta]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach(function (groupelement, index) {
        var defaults = groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
        if (defaults && defaults[identifier] !== undefined) {
          if (isColumnWidths) {
            var classesDefault = {};
            defaults[identifier].split('-').forEach(function (columnWidthValue, olumnWidthIndex) {
              var _groupelement$classLi3;
              classesDefault[columnWidthValue] = drupalSettings.vlsuite_utility_classes_map['column_widths'][columnWidthValue];
              (_groupelement$classLi3 = groupelement.classList).remove.apply(_groupelement$classLi3, _toConsumableArray(classesDefault[columnWidthValue]));
            });
          } else if (type === 'main_regions') {
            var _groupelement$querySe;
            (_groupelement$querySe = groupelement.querySelector('[data-region]').classList).remove.apply(_groupelement$querySe, _toConsumableArray(drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes));
          } else {
            var _groupelement$classLi4;
            (_groupelement$classLi4 = groupelement.classList).remove.apply(_groupelement$classLi4, _toConsumableArray(drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes));
          }
        }
        if (classes !== null) {
          if (isColumnWidths) {
            value.split('-').forEach(function (columnWidthValue, olumnWidthIndex) {
              if (olumnWidthIndex === index) {
                var _groupelement$classLi5;
                (_groupelement$classLi5 = groupelement.classList).add.apply(_groupelement$classLi5, _toConsumableArray(classes[columnWidthValue]));
              }
            });
          } else if (type === 'main_regions') {
            var _groupelement$querySe2;
            (_groupelement$querySe2 = groupelement.querySelector('[data-region]').classList).add.apply(_groupelement$querySe2, _toConsumableArray(classes));
          } else {
            var _groupelement$classLi6;
            (_groupelement$classLi6 = groupelement.classList).add.apply(_groupelement$classLi6, _toConsumableArray(classes));
          }
        }
      });
    }
  };
})(Drupal, drupalSettings, once, window);
