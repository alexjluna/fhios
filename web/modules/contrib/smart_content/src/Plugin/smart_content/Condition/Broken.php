<?php

namespace Drupal\smart_content\Plugin\smart_content\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\ConditionConfigurableBase;

/**
 * Provides a 'broken' condition.
 *
 * Provides a 'broken' condition as a fallback for condition plugin
 * definitions that can cease existence.  Example: A plugin deriver from
 * a third-party API that removes a field definition from their API.
 *
 * Broken conditions automatically evaluate to false, and will remain until
 * manually removed in the UI.
 *
 * @SmartCondition(
 *   id = "broken",
 *   label = @Translation("Broken"),
 *   group = "hidden",
 *   weight = 0,
 * )
 */
class Broken extends ConditionConfigurableBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'container',
      '#markup' => $this->t('Plugin Missing/Broken (%plugin_id)', ['%plugin_id' => $this->getConfiguration()['plugin_id']]),
      '#attributes' => ['class' => ['condition-label']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return ['smart_content/condition.common'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    return parent::getAttachedSettings() + [
      'settings' => [
        'negate' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlSummary() {
    return [
      '#markup' => '<p>Broken</p>',
    ];
  }

}
