<?php

namespace Drupal\vlsuite_layout_builder;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Layout builder pre-render class.
 */
class VLSuiteLayoutBuilderPreRender implements TrustedCallbackInterface {

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
    $element['#attached']['library'][] = 'vlsuite_layout_builder/layout_builder';
    $element['#attached']['library'][] = 'vlsuite_utility_classes/live_previewer';
    $element['#attached']['drupalSettings']['vlsuite_utility_classes_map'] = \Drupal::service('vlsuite_utility_classes.helper')->getUtilitiesMapClasses();
    $element['#attached']['drupalSettings']['vlsuite_icon_font_map'] = \Drupal::service('vlsuite_icon_font.helper')->getIconFontMap();

    foreach ($element['layout_builder'] as &$child_element) {
      // Section library changes this key?!
      $section_key = !empty($child_element[0]['#layout']) ? 0 : (!empty($child_element['layout-builder__section']['#layout']) ? 'layout-builder__section' : NULL);
      if (($section_key === 0 || !empty($section_key)) && $child_element[$section_key]['#layout'] instanceof LayoutDefinition) {
        $child_element['section_main_actions'] = [];

        if (!empty($child_element['configure'])) {
          $child_element['section_main_actions']['configure'] = $child_element['configure'];
          unset($child_element['configure']);
        }
        if (!empty($child_element['add_to_library'])) {
          $child_element['section_main_actions']['add_to_library'] = $child_element['add_to_library'];
          unset($child_element['add_to_library']);
        }
        if (!empty($child_element['remove'])) {
          $child_element['section_main_actions']['remove'] = $child_element['remove'];
          unset($child_element['remove']);
        }
        if (!empty($child_element['section_main_actions'])) {
          $child_element['section_main_actions'] += [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['container', 'layout-builder__section__main-actions'],
            ],
            '#weight' => -1,
          ];
        }
        foreach ($child_element[$section_key]['#layout']->getRegions() as $region => $info) {
          if (empty($child_element[$section_key][$region])) {
            continue;
          }
          foreach ($child_element[$section_key][$region] as &$section_child_element) {
            if (isset($section_child_element['#theme']) && $section_child_element['#theme'] === 'block') {
              continue 2;
            }
          }
          $child_element[$section_key][$region]['#attributes']['class'][] = 'layout-builder__region--empty-region';
        }

        $child_element['#attributes']['data-vlsuite-utility-classes-live-previewer-apply-to-url'] = Url::fromRoute('vlsuite_utility_classes.apply_to', [
          'section_storage_type' => $element['#section_storage']->getStorageType(),
          'section_storage' => $element['#section_storage']->getStorageId(),
        ])->toString();
      }
    }
    $element['layout_builder']['live_preview'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => ['layout-builder__live-preview__toggler__link'],
        'title' => t('Live preview'),
      ],
      '#title' => t('Live preview'),
      '#url' => Url::fromUserInput('#live-preview'),
    ];
    $vlsuite_utility_classes_configure_url = Url::fromRoute('vlsuite_utility_classes.settings');
    if ($vlsuite_utility_classes_configure_url->access()) {
      $element['layout_builder']['#attributes']['data-vlsuite-utility-classes-configure-url'] = $vlsuite_utility_classes_configure_url->toString();
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
