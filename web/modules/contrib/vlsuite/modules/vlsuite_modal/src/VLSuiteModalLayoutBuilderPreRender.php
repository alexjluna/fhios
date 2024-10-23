<?php

namespace Drupal\vlsuite_modal;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Layout builder pre-render class.
 */
class VLSuiteModalLayoutBuilderPreRender implements TrustedCallbackInterface {

  /**
   * Layout builder pre-render callback.
   *
   * @param array $element
   *   Render element.
   *
   * @return array
   *   Element modified.
   */
  public static function preRender(array $element) {
    $enabled = VLSuiteModalHelper::isLayoutBuilderEnabled();
    if (VLSuiteModalHelper::isLayoutBuilderEnabled() && VLSuiteModalHelper::isLayoutBuilderAdminInheritedEnabled()) {
      $element['#attached']['library'][] = VLSuiteModalHelper::ADMIN_INHERIT_LIBRARY_ATTACHED;
    }
    // Integration with section library & ensure contextual updated.
    if (!empty($element['layout_builder'][0]['add_template_to_library']) && $enabled) {
      $attributes = $element['layout_builder'][0]['add_template_to_library']['#url']->getOption('attributes');
      VLSuiteModalHelper::linkAttributesAlter($attributes);
      $element['layout_builder'][0]['add_template_to_library']['#url']->setOption('attributes', $attributes);
    }
    $hash = VLSuiteModalHelper::getConfigHash();
    foreach ($element['layout_builder'] as &$child_element) {
      // Section library changes this key?!
      $section_key = !empty($child_element[0]['#layout']) ? 0 : (!empty($child_element['layout-builder__section']['#layout']) ? 'layout-builder__section' : NULL);
      if (($section_key === 0 || !empty($section_key)) && $child_element[$section_key]['#layout'] instanceof LayoutDefinition) {
        foreach ($child_element[$section_key]['#layout']->getRegions() as $region => $info) {
          if (empty($child_element[$section_key][$region])) {
            continue;
          }
          foreach ($child_element[$section_key][$region] as &$section_child_element) {
            if (isset($section_child_element['#theme']) && $section_child_element['#theme'] === 'block') {
              $section_child_element['#contextual_links']['layout_builder_block']['metadata']['vlsuite_modal'] = $hash;
            }
          }
        }
      }
      if (!empty($child_element['choose_template_from_library']) && $enabled) {
        $attributes = $child_element['choose_template_from_library']['#url']->getOption('attributes');
        VLSuiteModalHelper::linkAttributesAlter($attributes);
        $child_element['choose_template_from_library']['#url']->setOption('attributes', $attributes);
      }
      if (!empty($child_element['add_to_library']) && $enabled) {
        $attributes = $child_element['add_to_library']['#url']->getOption('attributes');
        VLSuiteModalHelper::linkAttributesAlter($attributes);
        $child_element['add_to_library']['#url']->setOption('attributes', $attributes);
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRender',
    ];
  }

}
