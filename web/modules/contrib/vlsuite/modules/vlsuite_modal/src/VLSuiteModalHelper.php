<?php

namespace Drupal\vlsuite_modal;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\layout_builder\Form\AddBlockForm;
use Drupal\layout_builder\Form\ConfigureSectionForm;
use Drupal\layout_builder\Form\DefaultsEntityForm;
use Drupal\layout_builder\Form\MoveBlockForm;
use Drupal\layout_builder\Form\OverridesEntityForm;
use Drupal\layout_builder\Form\RemoveBlockForm;
use Drupal\layout_builder\Form\RemoveSectionForm;
use Drupal\layout_builder\Form\UpdateBlockForm;
use Drupal\section_library\Form\AddSectionToLibraryForm;
use Drupal\section_library\Form\AddTemplateToLibraryForm;

/**
 * Helper "VLSuiteModalHelper" service object.
 */
class VLSuiteModalHelper {

  const ADMIN_INHERIT_LIBRARY_NAME = 'admin_inherited';
  const ADMIN_INHERIT_LIBRARY_ATTACHED = 'vlsuite_modal/' . self::ADMIN_INHERIT_LIBRARY_NAME;
  const DIALOG_TARGET = 'vlsuite-modal';
  const SETTINGS_CONFIG_NAME = 'vlsuite_modal.settings';
  const LAYOUT_BUILDER_ADMIN_INHERITED_REQUEST = 'vlsuite_modal_layout_builder_admin_inherited_request';

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a "VLSuiteModalHelper" object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeInitializationInterface $theme_initialization, ThemeManagerInterface $theme_manager, RouteMatchInterface $route_match) {
    $this->configFactory = $config_factory;
    $this->themeInitialization = $theme_initialization;
    $this->themeManager = $theme_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * Get config hash.
   *
   * @return string
   *   Hash.
   */
  public static function getConfigHash() {
    return hash('sha256', serialize(self::getSettingsConfig()->getRawData()));
  }

  /**
   * Is layout builder enabled.
   *
   * @return bool
   *   Result.
   */
  public static function isLayoutBuilderEnabled(): bool {
    return (bool) self::getSettingsConfig()->get('layout_builder_enabled');
  }

  /**
   * Is layout builder admin inherited enabled.
   *
   * @return bool
   *   Result.
   */
  public static function isLayoutBuilderAdminInheritedEnabled(): bool {
    return (bool) self::getSettingsConfig()->get('layout_builder_admin_inherited');
  }

  /**
   * Link attributes alter.
   *
   * @param array $attributes
   *   Link attributes.
   */
  public static function linkAttributesAlter(array &$attributes) {
    $config = self::getSettingsConfig();
    unset($attributes['data-dialog-renderer']);
    $attributes['data-dialog-type'] = 'dialog';
    $attributes['data-dialog-options'] = Json::encode([
      'width' => $config->get('modal_width'),
      'height' => $config->get('modal_height'),
      'target' => self::DIALOG_TARGET,
      'autoResize' => $config->get('modal_autoresize'),
      'modal' => TRUE,
    ]);
  }

  /**
   * Alter layout builder main form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function alterLayoutBuilderMainForm(array &$form, FormStateInterface $form_state): void {
    if ($this->isLayoutBuilderMainForm($form_state->getFormObject())) {
      if (!empty($form['revision_information'])) {
        $form['revision_information']['#type'] = 'fieldset';
      }
    }
  }

  /**
   * Alter layout builder modal form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function alterLayoutBuilderModalForm(array &$form, FormStateInterface $form_state): void {
    if ($this->isLayoutBuilderModalForm($form_state->getFormObject()) && self::isLayoutBuilderEnabled()) {
      // Define options type to get modal actions working.
      $form['actions']['#type'] = 'actions';
      foreach (Element::children($form['actions']) as $action_key) {
        if (!empty($form['actions'][$action_key]['#ajax'])) {
          $form['actions'][$action_key]['#ajax']['disable-refocus'] = TRUE;
        }
      }
    }
  }

  /**
   * Layout builder contextual links alter.
   *
   * @param array $links
   *   Links.
   */
  public function layoutBuilderContextualLinksAlter(array &$links) {
    if (self::isLayoutBuilderEnabled()) {
      foreach (array_keys($links) as $key) {
        if (str_contains($key, 'layout_builder')) {
          self::linkAttributesAlter($links[$key]['localized_options']['attributes']);
        }
      }
    }
  }

  /**
   * Layout builder Link alter.
   *
   * @param array $variables
   *   Link variables.
   */
  public function layoutBuilderLinkAlter(array &$variables) {
    if ($this->layoutBuilderlinkAlterShouldApply($variables)) {
      self::linkAttributesAlter($variables['options']['attributes']);
    }
  }

  /**
   * Get admin inherited libraries.
   *
   * @return array
   *   Admin inherited libraries.
   */
  public function getAdminInheritedLibraries(): array {
    // @see VLSuiteModalThemeNegotiator::applies().
    return [
      self::ADMIN_INHERIT_LIBRARY_NAME => [
        'dependencies' => $this->getAdminTheme()->getLibraries(),
      ],
    ];
  }

  /**
   * Check is layout builder route name.
   *
   * @param mixed $route_name
   *   Route name.
   *
   * @return bool
   *   Result.
   */
  public static function layoutBuilderLayoutBuilderRouteNameCheck($route_name): bool {
    return !empty($route_name) &&
      (str_contains($route_name, 'layout_builder') ||
      str_contains($route_name, 'section_library') ||
      $route_name == 'vlsuite_utility_classes.apply_to' ||
      $route_name == 'vlsuite_layout_builder.duplicate_block');
  }

  /**
   * Check if is layout builder main form.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   Form object.
   *
   * @return bool
   *   Result.
   */
  protected function isLayoutBuilderMainForm(FormInterface $form_object): bool {
    return $form_object instanceof OverridesEntityForm ||
      $form_object instanceof DefaultsEntityForm;
  }

  /**
   * Check if is layout builder modal form.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   Form object.
   *
   * @return bool
   *   Result.
   */
  protected function isLayoutBuilderModalForm(FormInterface $form_object): bool {
    return $form_object instanceof MoveBlockForm ||
      $form_object instanceof ConfigureSectionForm ||
      $form_object instanceof AddBlockForm ||
      $form_object instanceof UpdateBlockForm ||
      $form_object instanceof RemoveSectionForm ||
      $form_object instanceof RemoveBlockForm ||
      $form_object instanceof AddSectionToLibraryForm ||
      $form_object instanceof AddTemplateToLibraryForm;
  }

  /**
   * Get settings config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected static function getSettingsConfig() {
    return \Drupal::config(self::SETTINGS_CONFIG_NAME);
  }

  /**
   * Get admin theme.
   *
   * @return \Drupal\Core\Theme\ActiveTheme
   *   Admin theme.
   */
  protected function getAdminTheme() {
    $admin_theme_name = $this->configFactory->get('system.theme')->get('admin');
    return $this->themeInitialization->getActiveThemeByName($admin_theme_name);
  }

  /**
   * Layout builder check if link alter should apply.
   *
   * @param array $variables
   *   Link variables.
   *
   * @return bool
   *   Result
   */
  protected function layoutBuilderlinkAlterShouldApply(array $variables): bool {
    if (!self::isLayoutBuilderEnabled()) {
      return FALSE;
    }

    $route_name = $this->routeMatch->getRouteName();
    // Only change links on layout builder routes.
    if (!self::layoutBuilderLayoutBuilderRouteNameCheck($route_name)) {
      return FALSE;
    }

    // Only change links that open in a dialog.
    if (empty($variables['options']['attributes']['data-dialog-type'])) {
      return FALSE;
    }

    /** @var Drupal\Core\Url $url */
    $url = $variables['url'];

    if (!$url || !$url->isRouted()) {
      return FALSE;
    }

    if (!isset($variables['options']['attributes']['class']) || !in_array('use-ajax', $variables['options']['attributes']['class'], TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

}
