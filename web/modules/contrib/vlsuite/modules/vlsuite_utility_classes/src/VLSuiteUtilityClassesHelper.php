<?php

namespace Drupal\vlsuite_utility_classes;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;

/**
 * Helper "VLSuiteUtilityClassesHelper" service object.
 */
class VLSuiteUtilityClassesHelper {

  use StringTranslationTrait;

  const UTILITY_CLASSES_KEY = 'vlsuite_utility_class';
  const USE_ADVANCED_UTILITY_CLASSES_PERM = 'use advanced vlsuite utility classes';

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Utility apply to options.
   *
   * @var array
   */
  protected $utilityApplyToOptions;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current user has permission to use advanced vlsuite utility classes.
   *
   * @var bool
   */
  protected $useAdvancedUtilityClasses;

  /**
   * Constructs a "VLSuiteUtilityClassesHelper" object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler instance.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->useAdvancedUtilityClasses = $this->currentUser->hasPermission(self::USE_ADVANCED_UTILITY_CLASSES_PERM);
  }

  /**
   * Get utility apply to options.
   *
   * @return array
   *   Options.
   */
  public function getUtilityApplyToOptions() {
    if (empty($this->utilityApplyToOptions)) {
      $apply_to_options = [];
      $this->moduleHandler->alter('vlsuite_utility_classes_utility_apply_to_options', $apply_to_options);
      $this->utilityApplyToOptions = $apply_to_options;
    }
    return $this->utilityApplyToOptions;
  }

  /**
   * Get utilities map classes.
   *
   * @return array
   *   Utilities map by identifiers, with visual names, classes & no apply to.
   */
  public function getUtilitiesMapClasses() {
    $utilities = $this->configFactory->get('vlsuite_utility_classes.settings')->get('utilities');
    $utilities_map = [];
    $include_advanced = $this->useAdvancedUtilityClasses;
    foreach ($utilities as $utility_identifier => $utility) {
      if (($utility['advanced'] ?? FALSE) && !$include_advanced) {
        continue;
      }
      $utilities_map[$utility_identifier] = [
        'icon' => $utility['icon'] ?? '',
        'visual_name' => $utility['visual_name'],
        'values' => [],
      ];
      foreach ($utility['values'] as $key => $value) {
        $utilities_map[$utility_identifier]['values'][$key] = [
          'icon' => $value['icon'] ?? '',
          'visual_name' => $value['visual_name'],
          'classes' => $this->getUtilityKeyValueClasses($utility_identifier, $key),
        ];
      }
    }
    $column_widths = [];
    foreach ($this->configFactory->get('vlsuite_utility_classes.settings')->get('col_classes') as $column_widths_key_raw => $column_widths_classes) {
      [, $column_widths_key] = explode('col_', $column_widths_key_raw);
      $column_widths[$column_widths_key] = explode(' ', $column_widths_classes);
    }
    return $utilities_map + [
      'column_widths' => $column_widths,
      'column_widths_icon' => $this->configFactory->get('vlsuite_utility_classes.settings')->get('column_widths_icon'),
    ];
  }

  /**
   * Check utility apply to value is valid.
   *
   * @param string $apply_to
   *   Apply to.
   * @param string $identifier
   *   Identifier.
   * @param string $value
   *   Value.
   *
   * @return bool
   *   Valid or not.
   */
  public function checkUtilityApplyToValueIsValid($apply_to, $identifier, $value) {
    $utilities = $this->configFactory->get('vlsuite_utility_classes.settings')->get('utilities');
    return !empty($utilities[$identifier]['values'][$value]) &&
    !empty($utilities[$identifier]['apply_to'][$apply_to]) ?? FALSE;
  }

  /**
   * Get utility apply to list of utilities.
   *
   * @param string $apply_to
   *   Apply to.
   * @param bool $include_advanced
   *   Include advanced utility classes.
   *
   * @return array
   *   Utilities for passed apply to.
   */
  public function getUtilitiesApplyToList(string $apply_to, bool $include_advanced) {
    $utilities = $this->configFactory->get('vlsuite_utility_classes.settings')->get('utilities');
    $utilities_apply_to = [];
    foreach ($utilities as $utility_identifier => $utility) {
      if (!empty($utility['apply_to'][$apply_to])) {
        if (($utility['advanced'] ?? FALSE) && !$include_advanced) {
          continue;
        }
        unset($utility['apply_to']);
        $utilities_apply_to[$utility_identifier] = $utility;
      }
    }
    return $utilities_apply_to;
  }

  /**
   * Get utility key value classes.
   *
   * @param string $utility_key
   *   Utility key.
   * @param string $value
   *   Utility value.
   *
   * @return array
   *   Utility classes for passed value.
   */
  public function getUtilityKeyValueClasses(string $utility_key, string $value) {
    $utilities = $this->configFactory->get('vlsuite_utility_classes.settings')->get('utilities');
    $class_prefix = $utilities[$utility_key]['class_prefix'] ?? '';
    $class_value = !empty($utilities[$utility_key]['values'][$value]) ? $utilities[$utility_key]['values'][$value]['class_suffix'] ?? '' : NULL;

    // Defined by prefix.
    if ($class_value == 'defined-by-prefix') {
      $class_value = '';
    }

    return $class_value !== NULL ? array_filter(explode(' ', $class_prefix . $class_value)) : [];
  }

  /**
   * Get utilities apply to classes.
   *
   * @param string $apply_to
   *   Apply to.
   * @param array $utility_classes_config
   *   Utility classes config.
   *
   * @return array
   *   Utilities apply to classes.
   */
  public function getUtilitiesApplyToClasses($apply_to, array $utility_classes_config) {
    $apply_to_utilities = $this->getUtilitiesApplyToList($apply_to, TRUE);
    $apply_to_config = $utility_classes_config ?? [];
    $apply_to_classes = [];
    foreach (array_keys($apply_to_utilities) as $utility_key) {
      if (!empty($apply_to_config[$utility_key]) || (isset($apply_to_config[$utility_key]) && $apply_to_config[$utility_key] == '0')) {
        $utility_classes = $this->getUtilityKeyValueClasses($utility_key, $apply_to_config[$utility_key]);
        $apply_to_classes = array_merge($apply_to_classes, $utility_classes);
      }
    }
    return $apply_to_classes;
  }

  /**
   * Get container classes.
   *
   * @return array
   *   Classes.
   */
  public function getContainerClasses() {
    $container_classes = $this->configFactory->get('vlsuite_utility_classes.settings')->get('container_classes') ?? '';
    return explode(' ', $container_classes);
  }

  /**
   * Get list unstyled classes.
   *
   * @return array
   *   Classes.
   */
  public function getListUnstyledClasses() {
    $list_unstyled_classes = $this->configFactory->get('vlsuite_utility_classes.settings')->get('list_unstyled_classes') ?? '';
    return explode(' ', $list_unstyled_classes);
  }

  /**
   * Get row classes.
   *
   * @return array
   *   Classes.
   */
  public function getRowClasses() {
    $container_classes = $this->configFactory->get('vlsuite_utility_classes.settings')->get('row_classes') ?? '';
    return explode(' ', $container_classes);
  }

  /**
   * Get col variant keys.
   *
   * @return array
   *   List.
   */
  public static function getColPercentageOptions() {
    return ['100', '80', '75', '67', '60', '50', '40', '33', '25'];
  }

  /**
   * Get col option classes.
   *
   * @param string $col_option
   *   Option (percentage or auto).
   *
   * @return array
   *   Classes.
   */
  public function getColPercentageOptionClasses($col_option) {
    $col_classes = $this->configFactory->get('vlsuite_utility_classes.settings')->get('col_classes')['col_' . $col_option] ?? '';
    return explode(' ', $col_classes);
  }

  /**
   * Get utilities apply to list form element.
   *
   * @param array $apply_to_definitions
   *   Apply to definitions.
   * @param array $defaults
   *   Config for default values.
   *
   * @return array
   *   Form element.
   */
  public function getUtilitiesApplyToListFormElement(array $apply_to_definitions, array $defaults) {
    $include_advanced = $this->useAdvancedUtilityClasses;
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Appearance'),
      '#description' => $this->t('Use "Appearance" floating UI icons for live previewer UX.'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#attached' => ['library' => ['vlsuite_utility_classes/previewer']],
      '#attributes' => ['class' => ['vlsuite-utility-classes']],
    ];
    $element['previewer'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vlsuite-utility-classes__previewer']],
      'text' => ['#markup' => $this->t('New selection previewer')],
    ];
    $has_any = FALSE;
    $main_has_any_visible = FALSE;
    foreach ($apply_to_definitions as $utility_classes_apply_to_key => $definition) {
      $utilities = $this->getUtilitiesApplyToList($utility_classes_apply_to_key, TRUE);
      $element[$utility_classes_apply_to_key] = [
        '#type' => 'details',
        '#title' => $definition['form_element_title'],
        '#open' => FALSE,
        '#access' => !empty($utilities),
      ];
      $has_any = $has_any ? $has_any : !empty($utilities);
      $definition_has_any_visible = FALSE;
      foreach ($utilities as $utility_identifier => $utility) {
        $utility_options = [];
        $utility_suffix_map = [];
        foreach ($utility['values'] as $value_identifier => $value_values) {
          $utility_options[$value_identifier] = $value_values['visual_name'];
          $utility_suffix_map[$value_identifier] = $value_values['class_suffix'];
        }
        if (($utility['advanced'] ?? FALSE) && !$include_advanced) {
          $element[$utility_classes_apply_to_key][$utility_identifier] = [
            '#type' => 'hidden',
            '#title' => $utility['visual_name'],
            '#value' => $defaults[$utility_classes_apply_to_key][$utility_identifier] ?? NULL,
          ];
        }
        else {
          $definition_has_any_visible = $definition_has_any_visible ? $definition_has_any_visible : TRUE;
          $main_has_any_visible = $main_has_any_visible ? $main_has_any_visible : TRUE;
          $element[$utility_classes_apply_to_key][$utility_identifier] = [
            '#type' => 'select',
            '#empty_option' => $this->t('Default'),
            '#options' => $utility_options,
            '#title' => $utility['visual_name'],
            '#default_value' => $defaults[$utility_classes_apply_to_key][$utility_identifier] ?? NULL,
            '#attributes' => [
              'class' => ['vlsuite-utility-classes__previewer-option'],
              'data-class-prefix' => $utility['class_prefix'],
              'data-class-key' => $utility_identifier,
              'data-class-suffix-map' => Json::encode($utility_suffix_map),
            ],
          ];
        }
      }
      if (!$definition_has_any_visible) {
        $element[$utility_classes_apply_to_key]['#attributes']['class'][] = 'visually-hidden';
      }
    }
    if (!$has_any) {
      $element['#access'] = FALSE;
    }
    if (!$main_has_any_visible) {
      $element['#attributes']['class'][] = 'visually-hidden';
    }
    return $element;
  }

  /**
   * Get utilities apply to list form element when submit.
   *
   * @param array $vlsuite_utility_classes
   *   Form element result raw.
   *
   * @return array
   *   Form element result of used utilities.
   */
  public function getUtilitiesApplyToListFormElementSubmit(array $vlsuite_utility_classes) {
    $utility_classes_config = [];
    foreach ($vlsuite_utility_classes as $apply_to => $utilities) {
      $utility_classes_config[$apply_to] = array_filter($utilities, 'strlen');
    }
    $utility_classes_config = array_filter($utility_classes_config);
    return $utility_classes_config;
  }

  /**
   * Helper to apply utility classes to build array parents (mainly for block).
   *
   * @param array $build
   *   Build.
   * @param array $classes
   *   Classes array.
   * @param array $parents_group
   *   Parents of element where to apply classes.
   */
  protected function buildApplyUtilityClassParents(array &$build, array $classes, array $parents_group) {
    foreach ($parents_group as $parents) {
      $ref = &$build;
      foreach (explode(':', $parents) as $parent) {
        $new_restriction = (str_contains($parent, 'attributes') || str_contains($parent, 'class'));
        if (is_array($ref)) {
          if (!isset($ref[$parent]) && $new_restriction) {
            $ref[$parent] = [];
          }
          $ref = &$ref[$parent];
        }
        elseif (is_object($ref)) {
          if (!isset($ref->$parent) && $new_restriction) {
            $ref->$parent = [];
          }
          $ref = &$ref->$parent;
        }
      }
      if ($ref !== NULL) {
        $ref = array_merge($ref, $classes);
      }
    }
  }

  /**
   * Helper to apply utility classes to build array (mainly for block).
   *
   * @param array $apply_to_list
   *   Apply to list.
   * @param array $build
   *   Build.
   */
  public function buildApplyUtilityClasses(array $apply_to_list, array &$build) {
    $is_field = (!empty($build['#theme']) && $build['#theme'] == 'field' || !empty($build[0]['#theme']) && $build[0]['#theme'] == 'field');
    // @see https://git.drupalcode.org/project/drupal/-/commit/7598b15a28f370ae194153c183b158b13670703a
    $is_field_parent_preffix = $is_field && !empty($build[0]['#theme']) && $build[0]['#theme'] == 'field' ? '0:' : '';
    foreach ($apply_to_list as $apply_to => $utility_classes_config) {
      $classes = $this->getUtilitiesApplyToClasses($apply_to, $utility_classes_config) ?? NULL;
      $apply_to_array = explode(':', $apply_to);
      if (count($apply_to_array) == 4) {
        [, $bundle, $field, $item] = $apply_to_array;
      }
      elseif (count($apply_to_array) == 3) {
        [, $bundle, $field] = $apply_to_array;
        $item = NULL;
      }
      elseif (count($apply_to_array) == 2) {
        [, $bundle] = $apply_to_array;
        $item = NULL;
        $field = NULL;
      }
      else {
        $bundle = NULL;
        $field = NULL;
        $item = NULL;
      }

      $parents_group = [];
      if (!empty($item)) {
        $build_apply_to = !$is_field  && !empty($field) && !empty($build[$field]) ? $build[$field] : ($is_field_parent_preffix ? $build[0] : $build);
        foreach (Element::children($build_apply_to) as $delta) {
          // @see template_preprocess_field().
          $parents_group[] = (!$is_field ? $field . ':' : $is_field_parent_preffix) . $delta . ':#attributes:class';
          // @code $parents_group[] = (!$is_field ? $field . ':' : '') . $delta . ':_attributes:class'; @endcode
          $parents_group[] = (!$is_field ? $field . ':' : $is_field_parent_preffix) . $delta . ':#item_attributes:class';
          // Avoid generic entity render cache when modified per utilities.
          if (!empty($build[$field][$delta]['#cache']['bin']) && $build[$field][$delta]['#cache']['bin'] === 'render') {
            unset($build[$field][$delta]['#cache']['bin']);
            unset($build[$field][$delta]['#cache']['keys']);
          }
          if (!empty($build[$delta]['#cache']['bin']) && $build[$delta]['#cache']['bin'] === 'render') {
            unset($build[$delta]['#cache']['bin']);
            unset($build[$delta]['#cache']['keys']);
          }
          if (!empty($is_field_parent_preffix) && !empty($build[0][$delta]['#cache']['bin']) && $build[0][$delta]['#cache']['bin'] === 'render') {
            unset($build[0][$delta]['#cache']['bin']);
            unset($build[0][$delta]['#cache']['keys']);
          }
        }
      }
      elseif (!empty($field)) {
        $parents_group[] = (!$is_field ? $field . ':' : '') . '#attributes:class';
      }
      elseif (!empty($bundle)) {
        $parents_group[] = '#attributes:class';
      }
      if (!empty($classes) && !empty($parents_group)) {
        $this->buildApplyUtilityClassParents($build, $classes, $parents_group);
      }
    }
  }

  /**
   * Apply live previewer attributes.
   *
   * @param \Drupal\Core\Template\Attribute|array $attributes
   *   Attributes.
   * @param string $apply_to
   *   Apply to.
   * @param array $defaults
   *   Defaults.
   * @param array $apply_to_list_extra
   *   Apply to list extra.
   */
  public function applyLivePreviewerAttributes(Attribute|array &$attributes, string $apply_to, array $defaults, array $apply_to_list_extra = []) {
    $include_advanced = $this->useAdvancedUtilityClasses;
    $apply_to_list = $apply_to_list_extra + $this->getUtilitiesApplyToList($apply_to, $include_advanced);
    [, $apply_to_type] = explode(':', $apply_to);
    if ($attributes instanceof Attribute) {
      $attributes->setAttribute('data-vlsuite-utility-classes-live-previewer-apply-to', $apply_to);
      $attributes->setAttribute('data-vlsuite-utility-classes-live-previewer-type', $apply_to_type);
      $attributes->setAttribute('data-vlsuite-utility-classes-live-previewer-identifiers', implode(',', array_keys($apply_to_list)));
      $attributes->setAttribute('data-vlsuite-utility-classes-live-previewer-defaults', Json::encode($defaults));
    }
    else {
      $attributes['data-vlsuite-utility-classes-live-previewer-apply-to'] = $apply_to;
      $attributes['data-vlsuite-utility-classes-live-previewer-type'] = $apply_to_type;
      $attributes['data-vlsuite-utility-classes-live-previewer-identifiers'] = implode(',', array_keys($apply_to_list));
      $attributes['data-vlsuite-utility-classes-live-previewer-defaults'] = Json::encode($defaults);
    }
  }

  /**
   * Build live previewer.
   *
   * @param array $apply_to_list
   *   Apply to list.
   * @param array $build
   *   Build.
   * @param array $defaults
   *   Defaults.
   */
  public function buildLivePreviewer(array $apply_to_list, array &$build, array $defaults) {
    $include_advanced = $this->useAdvancedUtilityClasses;
    $is_field = (!empty($build['#theme']) && $build['#theme'] == 'field' || !empty($build[0]['#theme']) && $build[0]['#theme'] == 'field');
    // @see https://git.drupalcode.org/project/drupal/-/commit/7598b15a28f370ae194153c183b158b13670703a
    $is_field_parent_preffix = $is_field && !empty($build[0]['#theme']) && $build[0]['#theme'] == 'field' ? '0:' : '';
    foreach (array_keys($apply_to_list) as $apply_to) {
      $apply_to_array = explode(':', $apply_to);
      $apply_to_utilities = $this->getUtilitiesApplyToList($apply_to, $include_advanced);
      $apply_to_type = NULL;
      if (count($apply_to_array) == 4) {
        $apply_to_type = 'item';
        [, $bundle, $field, $item] = $apply_to_array;
      }
      elseif (count($apply_to_array) == 3) {
        $apply_to_type = $is_field ? 'block' : 'field';
        [, $bundle, $field] = $apply_to_array;
        $item = NULL;
      }
      elseif (count($apply_to_array) == 2) {
        $apply_to_type = 'block';
        [, $bundle] = $apply_to_array;
        $item = NULL;
        $field = NULL;
      }
      else {
        $apply_to_type = 'other';
        $bundle = NULL;
        $field = NULL;
        $item = NULL;
      }
      $parents_group = [];
      if (!empty($item)) {
        $build_apply_to = !$is_field  && !empty($field) && !empty($build[$field]) ? $build[$field] : (!empty($is_field_parent_preffix) ? $build[0] : $build);
        foreach (Element::children($build_apply_to) as $delta) {
          // @see template_preprocess_field().
          $parents_group[] = (!$is_field ? $field . ':' : $is_field_parent_preffix) . $delta . ':#attributes';
          $parents_group[] = (!$is_field ? $field . ':' : $is_field_parent_preffix) . $delta . ':#item_attributes';
        }
      }
      elseif (!empty($field)) {
        $parents_group[] = (!$is_field ? $field . ':' : '') . '#attributes';
      }
      elseif (!empty($bundle)) {
        $parents_group[] = '#attributes';
      }
      if (!empty($parents_group)) {
        $this->buildApplyUtilityClassPreviewParents($build, $parents_group, $apply_to, $apply_to_utilities, $defaults[$apply_to] ?? [], $apply_to_type);
      }
    }
  }

  /**
   * Build apply utility class preview parents.
   *
   * @param array $build
   *   Build.
   * @param array $parents_group
   *   Parents group.
   * @param string $apply_to
   *   Apply to.
   * @param array $utilities
   *   Utilities.
   * @param array $defaults
   *   Defaults.
   * @param string $apply_to_type
   *   Apply to type.
   */
  protected function buildApplyUtilityClassPreviewParents(array &$build, array $parents_group, string $apply_to, array $utilities, array $defaults, string $apply_to_type) {
    $attributes = [];
    $attributes['data-vlsuite-utility-classes-live-previewer-apply-to'] = $apply_to;
    $attributes['data-vlsuite-utility-classes-live-previewer-type'] = $apply_to_type;
    $attributes['data-vlsuite-utility-classes-live-previewer-identifiers'] = implode(',', array_keys($utilities));
    if ($apply_to_type == 'main_regions') {
      $attributes['data-vlsuite-utility-classes-group'] = 'main_regions';
    }
    if (!empty($defaults)) {
      $attributes['data-vlsuite-utility-classes-live-previewer-defaults'] = Json::encode($defaults);
    }
    foreach ($parents_group as $parents) {
      $ref = &$build;
      foreach (explode(':', $parents) as $parent) {
        $new_restriction = (str_contains($parent, 'attributes'));
        if (is_array($ref)) {
          if (!isset($ref[$parent]) && $new_restriction) {
            $ref[$parent] = [];
          }
          $ref = &$ref[$parent];
        }
        elseif (is_object($ref)) {
          if (!isset($ref->$parent) && $new_restriction) {
            $ref->$parent = [];
          }
          $ref = &$ref->$parent;
        }
      }
      if ($ref !== NULL) {
        if ($ref instanceof Attribute) {
          foreach ($attributes as $key => $value) {
            $ref->setAttribute($key, $value);
          }
        }
        else {
          $ref = array_merge($ref, $attributes);
        }
      }
    }
  }

}
