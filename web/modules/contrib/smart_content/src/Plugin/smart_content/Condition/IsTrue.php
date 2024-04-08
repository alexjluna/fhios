<?php

namespace Drupal\smart_content\Plugin\smart_content\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\ConditionConfigurableBase;

/**
 * Defines a 'is_true' condition.
 *
 * @SmartCondition(
 *   id = "is_true",
 *   label = @Translation("True"),
 *   group = "common",
 *   weight = 0,
 * )
 */
class IsTrue extends ConditionConfigurableBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['#attributes']['class'][] = 'condition';
    $form['label'] = [
      '#type' => 'container',
      '#markup' => $this->t('Is True'),
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
      '#markup' => 'Is True',
      '#prefix' => '<span class="condition-label">',
      '#suffix' => '</span> ',
    ];
  }

}
