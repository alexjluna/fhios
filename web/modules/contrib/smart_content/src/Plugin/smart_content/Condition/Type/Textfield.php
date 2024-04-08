<?php

namespace Drupal\smart_content\Plugin\smart_content\Condition\Type;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\smart_content\Condition\Type\ConditionTypeBase;

/**
 * Provides a 'textfield' ConditionType.
 *
 * @SmartConditionType(
 *  id = "textfield",
 *  label = @Translation("Textfield"),
 * )
 */
class Textfield extends ConditionTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'condition-textfield';
    $form['#tree'] = TRUE;
    $form['op'] = [
      '#type' => 'select',
      '#options' => $this->getOperators(),
      '#default_value' => isset($this->configuration['op']) ? $this->configuration['op'] : $this->defaultFieldConfiguration()['op'],
      '#attributes' => ['class' => ['condition-op']],
    ];
    $form['value'] = [
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['value']) ? $this->configuration['value'] : $this->defaultFieldConfiguration()['value'],
      '#attributes' => ['class' => ['condition-value']],
      // @todo: make configurable
      '#size' => 20,
    ];

    $form['#process'][] = [$this, 'buildWidget'];
    return $form;
  }

  /**
   * Process callback for accessing parents.
   */
  public function buildWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#parents'])) {
      $parents = $element['#parents'];
      $first_item = array_shift($parents);

      array_walk($parents, function (&$value, $i) {
        $value = '[' . $value . ']';
      });

      $parent_string = $first_item . implode('', $parents) . '[op]';

      $element['value']['#states'] = [
        'invisible' => [
          'select[name="' . $parent_string . '"]' => ['value' => 'empty'],
        ],
        'required' => [
          'select[name="' . $parent_string . '"]' => [
            ['value' => 'equals'],
            ['value' => 'contains'],
            ['value' => 'starts_with'],
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // When using SubformStateInterface, $values may be empty, in which case
    // we load values off complete form state and retrieve values from nested
    // array.
    if (empty($values) && $form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getValues();
      $values = NestedArray::getValue($values, $form['#parents']);
    }
    if ($values['op'] != 'empty' && $values['value'] === '') {
      $form_state->setError($form['value'], $this->t('Value field is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFieldConfiguration() {
    return [
      'op' => 'equals',
      'value' => '',
    ];
  }

  /**
   * Returns a list of operators.
   */
  public function getOperators() {
    return [
      'equals' => $this->t('Equals'),
      'contains' => $this->t('Contains'),
      'starts_with' => $this->t('Starts with'),
      'empty' => $this->t('Is empty'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return ['smart_content/condition_type.standard'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    return $this->getConfiguration() + $this->defaultFieldConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlSummary() {
    $configuration = $this->getConfiguration();
    $operator = strtolower($this->getOperators()[$configuration['op']]);
    $output = [
      '#type' => 'markup',
      'op' => [
        '#markup' => "{$operator}",
        '#prefix' => '<span class="condition-type-op">',
        '#suffix' => '</span> ',
      ],
    ];
    if ($configuration['op'] !== 'empty') {
      $output['textfield'] = [
        '#markup' => "{$configuration['value']}",
        '#prefix' => '<span class="condition-type-value">"',
        '#suffix' => '"</span>',
      ];
    }
    return $output;
  }

}
