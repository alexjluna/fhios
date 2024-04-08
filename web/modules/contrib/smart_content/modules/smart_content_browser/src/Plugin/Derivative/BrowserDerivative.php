<?php

namespace Drupal\smart_content_browser\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Deriver for BrowserCondition.
 *
 * Provides a deriver for
 * Drupal\smart_content_browser\Plugin\smart_content\Condition\BrowserCondition.
 * Definitions are based on properties available in JS from user's browser.
 */
class BrowserDerivative extends DeriverBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'language' => [
        'label' => $this->t('Language'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'mobile' => [
        'label' => $this->t('Mobile'),
        'type' => 'boolean',
        'weight' => -5,
      ] + $base_plugin_definition,
      'platform_os' => [
        'label' => $this->t('Operating System'),
        'type' => 'select',
        'options_callback' => [get_class($this), 'getOsOptions'],
      ] + $base_plugin_definition,
      'cookie' => [
        'label' => $this->t('Cookie'),
        'type' => 'key_value',
        'unique' => TRUE,
      ] + $base_plugin_definition,
      'cookie_enabled' => [
        'label' => $this->t('Cookie Enabled'),
        'type' => 'boolean',
      ] + $base_plugin_definition,
      'localstorage' => [
        'label' => $this->t('localStorage'),
        'type' => 'key_value',
        'unique' => TRUE,
      ] + $base_plugin_definition,
      'width' => [
        'label' => $this->t('Width'),
        'type' => 'number',
        'format_options' => [
          'suffix' => 'px',
        ],
      ] + $base_plugin_definition,
      'height' => [
        'label' => $this->t('Height'),
        'type' => 'number',
        'format_options' => [
          'suffix' => 'px',
        ],
      ] + $base_plugin_definition,
    ];
    return $this->derivatives;
  }

  /**
   * Returns list of 'Operating Systems' for select element.
   *
   * @return array
   *   Array of Operation Systems.
   */
  public static function getOsOptions() {
    return [
      'android' => t('Android'),
      'chromeos' => t('ChromeOS'),
      'ios' => t('iOS'),
      'linux' => t('Linux'),
      'macosx' => t('Mac OS X'),
      'nintendo' => t('Nintendo'),
      'playstation' => t('PlayStation'),
      'windows' => t('Windows'),
    ];
  }

}
