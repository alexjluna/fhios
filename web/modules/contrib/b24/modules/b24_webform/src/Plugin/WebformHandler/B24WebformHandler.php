<?php

namespace Drupal\b24_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\b24\Service\RestManager;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "b24_webform_handler",
 *   label = @Translation("Bitrix24 webform handler"),
 *   category = @Translation("Bitrix24"),
 *   description = @Translation("Sends submission data to bitrix24."),
 *   cardinality =
 *   \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results =
 *   \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
final class B24WebformHandler extends WebformHandlerBase {

  /**
   * The bitrix24 rest manager.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected RestManager $b24RestManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->b24RestManager = $container->get('b24.rest_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $webform = $this->webform;

    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fields mapping'),
      '#states' => [
        'visible' => [
          '[name="settings[mapping]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $this->getFields($form['mapping'], $webform);

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['mapping'] = $form_state->getValue('mapping');
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $webform = $webform_submission->getWebform();
    $state = $webform->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if ($state !== 'completed') {
      return;
    }

    $mapping = $this->configuration['mapping'];
    if (!$mapping) {
      return;
    }
    $values = $webform_submission->getData();
    $fields = [];
    $field_definitions = $this->b24RestManager->getFields('lead');
    foreach ($mapping as $ext_field => $int_field) {
      if (strstr($ext_field, '_custom')) {
        continue;
      }
      if ($int_field && $int_field !== 'custom') {
        $value = $values[$int_field];
      }
      else {
        $override = $mapping["{$ext_field}_custom"] ?? '';
        $value = \Drupal::service('token')
          ->replace($override,
            ['webform' => $webform, 'webform_submission' => $webform_submission]);
      }

      if ($field_definitions[$ext_field]['type'] == 'crm_multifield') {
        $value = [['VALUE' => $value, 'VALUE_TYPE' => 'HOME']];
        if (!empty($existing_entity[$ext_field])) {
          // @todo Support multiple drupal fields.
          $value[0]['ID'] = reset($existing_entity[$ext_field])['ID'];
        }
      }
      $fields[$ext_field] = $value;
    }

    $this->b24RestManager->addLead($fields);
  }

  /**
   * Return configuration fields according to those provided by Bitrix24.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The currently processed webform.
   *
   * @return array|mixed
   *   An array of Bitrix 24 fields.
   */
  private function getFields(array &$form, Webform $webform) {
    $config = $this->configuration;
    $b24_fields = $this->b24RestManager->getFields('lead');

    $elements = $webform->getElementsDecodedAndFlattened();
    $restricted_types = [
      'webform_actions',
    ];
    $options = [];
    foreach ($elements as $element_id => $element) {
      if (!isset($element['#type']) || in_array($element['#type'],
          $restricted_types) || !isset($element['#title'])) {
        continue;
      }
      $options[$element_id] = $element['#title'];
    }
    $options['custom'] = $this->t('Custom...');
    $token_link = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['site', 'webform', 'webform_submission'],
      '#show_restricted' => TRUE,
      '#global_types' => TRUE,
    ];
    $token_link = $this->renderer->render($token_link);
    foreach ($b24_fields as $b24_field_name => $b24_field_properties) {
      if (in_array($b24_field_properties['type'],
          [
            'string',
            'crm_multifield',
            'double',
          ]
        ) && !$b24_field_properties['isReadOnly']) {
        $form[$b24_field_name] = [
          '#type' => 'select',
          '#options' => $options,
          '#title' => $b24_field_properties['title'],
          '#default_value' => $config['mapping'][$b24_field_name] ?? '',
          '#empty_option' => $this->t('- None -'),
        ];
        if ($b24_field_properties['isRequired']) {
          $form[$b24_field_name]['#label_attributes']['class'][] = 'form-required';
        }

        $form["{$b24_field_name}_custom"] = [
          '#type' => 'textarea',
          '#title' => $this->t('@name custom value',
            ['@name' => $b24_field_properties['title']]),
          '#description' => $token_link,
          '#description_display' => 'after',
          '#states' => [
            'visible' => [
              '[name="settings[mapping][' . $b24_field_name . ']"]' => [
                'value' => 'custom',
              ],
            ],
          ],
          '#default_value' => $config['mapping']["{$b24_field_name}_custom"] ?? '',
        ];
      }
    }

    return $b24_fields;
  }

}
