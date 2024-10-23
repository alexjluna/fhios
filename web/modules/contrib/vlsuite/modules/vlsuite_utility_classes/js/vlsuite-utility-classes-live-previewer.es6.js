(function (Drupal, drupalSettings, once, window) {

  Drupal.behaviors.vlsuite_utility_classes_live_previewer = {
    attach(context) {
      once('vlsuite-utility-classes-live-previewer', '[data-vlsuite-utility-classes-live-previewer-apply-to]', context).forEach(Drupal.vlsuite_utility_classes_live_previewer);
    }
  };

  Drupal.vlsuite_utility_classes_live_previewer = (element) => {
    if (typeof window.FloatingUIDOM !== 'object') {
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
    var linkArgumentsBase = [
      element.closest('[data-vlsuite-utility-classes-live-previewer-apply-to-url]').dataset.vlsuiteUtilityClassesLivePreviewerApplyToUrl,
      delta,
      uuid,
      applyTo
    ];
    Drupal.vlsuite_utility_classes_type_element_setDefaults(element, type);
    var defaults = element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
    var configureLink = element.closest('[data-vlsuite-utility-classes-configure-url]');
    element.dataset.vlsuiteUtilityClassesLivePreviewerIdentifiers.split(',').forEach((currentIdentifier) => {
      if (currentIdentifier === '') {
        if (configureLink) {
          var emptyElement = document.createElement('span');
          emptyElement.classList.add('vlsuite-utility-classes-live-previewer__empty');
          emptyElement.appendChild(document.createTextNode(Drupal.t('Any enabled, configure using link below')));
          overflow.appendChild(emptyElement);
        }
        else {
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
        var iconElement = document.createElement('span');
        iconElement.classList.add(...drupalSettings.vlsuite_icon_font_map['main_classes'].split(' '));
        if (iconReplacement === 'text') {
          iconElement.appendChild(document.createTextNode(icon));
        }
        else if (iconReplacement === 'class') {
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
        utilityDefaultLink.addEventListener("click", (e) => {
          e.preventDefault();
          Drupal.ajax({url: e.target.href, progress: { type: 'fullscreen' }}).execute();
        }, false);
        utilityListItem.appendChild(utilityDefaultLink);
        utilityList.appendChild(utilityListItem);
      }

      Object.keys(drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values).forEach((currentValue) => {
        var utilityListItem = document.createElement('li');

        var link_title = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values[currentValue].visual_name;
        if (link_title === undefined) {
          link_title = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values[currentValue];
        }
        var utilityValueLink = document.createElement('a');
        var icon = drupalSettings.vlsuite_utility_classes_map[currentIdentifier].values[currentValue].icon;
        if (icon) {
          var iconElement = document.createElement('span');
          iconElement.classList.add(...drupalSettings.vlsuite_icon_font_map['main_classes'].split(' '));
          if (iconReplacement === 'text') {
            iconElement.appendChild(document.createTextNode(icon));
          }
          else if (iconReplacement === 'class') {
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
        utilityValueLink.addEventListener("mouseover", (e) => {
          e.stopPropagation();
          Drupal.vlsuite_utility_classes_live_previewer_type_element_apply_classes(element, type, currentIdentifier, currentValue);
        }, false);
        utilityValueLink.addEventListener("mouseout", (e) => {
          e.stopPropagation();
          Drupal.vlsuite_utility_classes_type_element_restore(element, type, currentIdentifier);
        }, false);
        utilityValueLink.addEventListener("click", (e) => {
          e.preventDefault();
          Drupal.ajax({url: e.target.href, progress: { type: 'fullscreen' }}).execute();
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

    element.closest('[data-vlsuite-utility-classes-live-previewer-apply-to-url]').addEventListener('mouseenter', (e) => {
      e.stopPropagation();
      main.classList.add('vlsuite-visible');
      if (main.matches(':hover')) {
        main.classList.add('active');
      }
      Drupal.vlsuite_utility_classes_live_previewer_type_position_refresh(element, overflow, main, arrow, type);
    });
    element.closest('[data-vlsuite-utility-classes-live-previewer-apply-to-url]').addEventListener('mouseleave', (e) => {
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

  Drupal.vlsuite_utility_classes_live_previewer_type_position_refresh = (element, mainWrapperOverflow, mainWrapper, mainWrapperArrow, type) => {
    window.FloatingUIDOM.computePosition(element, mainWrapper, {
      placement: Drupal.vlsuite_utility_classes_live_previewer_type_position_map[type][0],
      middleware: [
        window.FloatingUIDOM.offset(6),
        window.FloatingUIDOM.flip({
          crossAxis: false,
          fallbackPlacements: Drupal.vlsuite_utility_classes_live_previewer_type_position_map[type]
        }),
        window.FloatingUIDOM.size({
          apply( { availableHeight }) {
            Object.assign(mainWrapperOverflow.style, {
              maxHeight: `${availableHeight - 70}px`
            });
          },
          padding: 10
        }),
        window.FloatingUIDOM.arrow({element: mainWrapperArrow, padding: 7})
      ]
    }).then(({ x, y, placement, middlewareData }) => {
      Object.assign(mainWrapper.style, {
        top: `${y}px`,
        left: `${x}px`
      });
      const {x: arrowX, y: arrowY} = middlewareData.arrow;
      const staticSide = {
        top: 'bottom',
        right: 'left',
        bottom: 'top',
        left: 'right'
      }[placement.split('-')[0]];
      Object.assign(mainWrapperArrow.style, {
        left: arrowX !== null ? `${arrowX}px` : '',
        top: arrowY !== null ? `${arrowY}px` : '',
        right: '',
        bottom: '',
        [staticSide]: `${-mainWrapperArrow.offsetWidth / 2}px`
      });
    });
  };

  Drupal.vlsuite_utility_classes_type_element_setDefaults = (element, type) => {
    element.dataset.originalClasses = element.classList.value;
    if (type === 'main_regions' || Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'region') {
      element.querySelector('[data-region]').dataset.originalClasses = element.querySelector('[data-region]').classList.value;
    }
  };

  Drupal.vlsuite_utility_classes_type_element_restore = (element, type, identifier) => {
    var isColumnWidths = identifier.includes(':column_widths');
    if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'self') {
      element.classList = element.dataset.originalClasses;
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'block') {
      element.closest('[data-layout-block-uuid]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach((groupelement, index) => {
        groupelement.classList = groupelement.dataset.originalClasses;
      });
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'region') {
      element.querySelector('[data-region]').classList = element.querySelector('[data-region]').dataset.originalClasses;
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'section') {
      element.closest('[data-layout-delta]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach((groupelement, index) => {
        if (type === 'main_regions' && !isColumnWidths) {
          groupelement.querySelector('[data-region]').classList = groupelement.querySelector('[data-region]').dataset.originalClasses;
        } else {
          groupelement.classList = groupelement.dataset.originalClasses;
        }
      });
    }
  };

  Drupal.vlsuite_utility_classes_live_previewer_type_element_apply_classes = (element, type, identifier, value) => {
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
        element.classList.remove(...drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes);
      }
      if (classes !== null) {
        element.classList.add(...classes);
      }
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'block') {
      element.closest('[data-layout-block-uuid]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach((groupelement, index) => {
        var defaults = groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
        if (defaults && defaults[identifier] !== undefined) {
          groupelement.classList.remove(...drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes);
        }
        if (classes !== null) {
          groupelement.classList.add(...classes);
        }
      });
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'region') {
      var defaults = element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(element.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
      if (defaults && defaults[identifier] !== undefined) {
        element.querySelector('[data-region]').classList.remove(...drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes);
      }
      if (classes !== null) {
        element.querySelector('[data-region]').classList.add(...classes);
      }
    } else if (Drupal.vlsuite_utility_classes_live_previewer_type_apply_scope[type] === 'section') {
      element.closest('[data-layout-delta]').querySelectorAll('[data-vlsuite-utility-classes-live-previewer-apply-to="' + element.dataset.vlsuiteUtilityClassesLivePreviewerApplyTo + '"]').forEach((groupelement, index) => {
        var defaults = groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults !== undefined ? JSON.parse(groupelement.dataset.vlsuiteUtilityClassesLivePreviewerDefaults) : false;
        if (defaults && defaults[identifier] !== undefined) {
          if (isColumnWidths) {
            var classesDefault = {};
            defaults[identifier].split('-').forEach(function (columnWidthValue, olumnWidthIndex) {
              classesDefault[columnWidthValue] = drupalSettings.vlsuite_utility_classes_map['column_widths'][columnWidthValue];
              groupelement.classList.remove(...classesDefault[columnWidthValue]);
            });
          } else if (type === 'main_regions') {
            groupelement.querySelector('[data-region]').classList.remove(...drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes);
          } else {
            groupelement.classList.remove(...drupalSettings.vlsuite_utility_classes_map[identifier].values[defaults[identifier]].classes);
          }
        }
        if (classes !== null) {
          if (isColumnWidths) {
            value.split('-').forEach(function (columnWidthValue, olumnWidthIndex) {
              if (olumnWidthIndex === index) {
                groupelement.classList.add(...classes[columnWidthValue]);
              }
            });
          } else if (type === 'main_regions') {
            groupelement.querySelector('[data-region]').classList.add(...classes);
          } else {
            groupelement.classList.add(...classes);
          }
        }
      });
    }
  };

}(Drupal, drupalSettings, once, window));
