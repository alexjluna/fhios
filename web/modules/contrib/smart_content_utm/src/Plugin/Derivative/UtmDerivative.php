<?php

namespace Drupal\smart_content_utm\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Deriver for UtmCondition.
 *
 * Provides a deriver for
 * Drupal\smart_content_utm\Plugin\smart_content\Condition\UtmCondition.
 * Definitions are based on properties available in utm strings and storage.
 */
class UtmDerivative extends DeriverBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'utm_source' => [
        'label' => $this->t('Source'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'utm_medium' => [
        'label' => $this->t('Medium'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'utm_campaign' => [
        'label' => $this->t('Campaign'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'utm_term' => [
        'label' => $this->t('Term'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'utm_content' => [
        'label' => $this->t('Content'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
    ];
    return $this->derivatives;
  }

}
