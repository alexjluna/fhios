<?php

namespace Drupal\b24_contact\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\b24\Service\RestManager;
use Drupal\contact\Entity\ContactForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure b24_contact settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\b24\Service\RestManager definition.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected RestManager $b24RestManager;

  /**
   * The HTML renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected EntityFieldManager $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    RestManager $b24_rest_manager,
    RendererInterface $renderer,
    EntityFieldManager $entity_field_manager,
    EntityTypeManager $entity_type_manager,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->b24RestManager = $b24_rest_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('b24.rest_manager'),
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b24_contact_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['b24_contact.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('b24_contact.settings');

    $form['forms'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Contact form submissions exporting rules'),
    ];

    /** @var \Drupal\contact\Entity\ContactForm[] $contact_forms */
    $contact_forms = $this->entityTypeManager->getStorage('contact_form')
      ->loadMultiple();

    foreach ($contact_forms as $contact_form_id => $contact_form) {

      $contact_form_settings = $config->get($contact_form_id);

      $form[$contact_form_id] = [
        '#type' => 'details',
        '#group' => 'forms',
        '#tree' => TRUE,
        '#title' => $contact_form->label(),
      ];

      $form[$contact_form_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable export'),
        '#description' => $this->t('Export submissions of this form to Bitrix24 leads'),
        '#default_value' => $contact_form_settings['status'] ?? 0,
      ];

      $form[$contact_form_id]['mapping'] = [
        '#type' => 'details',
        '#title' => $this->t('Fields mapping'),
        '#states' => [
          'visible' => [
            '[name="' . $contact_form_id . '[status]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $this->getFields($form[$contact_form_id]['mapping'], $contact_form);
    }

    $form['#attached']['library'] = [
      'b24/vertical-tabs',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Return configuration fields according to those provided by Bitrix24.
   *
   * @param array $form
   *   The form array.
   * @param Drupal\contact\Entity\ContactForm $contact_form
   *   The currently processed webform.
   *
   * @return array|mixed
   *   An array of Bitrix 24 fields.
   */
  private function getFields(array &$form, ContactForm $contact_form) {
    $config = $this->config("b24_contact.settings")->get($contact_form->id());
    $b24_fields = $this->b24RestManager->getFields('lead');
    $elements = $this->entityFieldManager->getFieldDefinitions('contact_message', $contact_form->id());

    $restricted_types = [
      'uuid',
      'language',
      'entity_reference',
    ];
    $options = [];
    foreach ($elements as $element_id => $element) {
      if (in_array($element->getType(), $restricted_types)) {
        continue;
      }
      $options[$element_id] = $element->getLabel();
    }
    $options['custom'] = $this->t('Custom...');

    // @todo Create tokens for submission values.
    $token_link = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['site'],
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
          '#title' => $this->t('@name custom value', ['@name' => $b24_field_properties['title']]),
          '#description' => $token_link,
          '#states' => [
            'visible' => [
              '[name="' . $contact_form->id() . '[mapping][' . $b24_field_name . ']"]' => [
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

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state->cleanValues();
    $values = $form_state->getValues();
    foreach ($values as $form_id => $form_values) {
      if ($form_values['status']) {
        foreach ($form_values['mapping'] as $key => $value) {
          if (!$value && isset($form[$form_id]['mapping'][$key]['#label_attributes']['class']) && in_array('form-required', $form[$form_id]['mapping'][$key]['#label_attributes']['class'])) {
            $form_state->setErrorByName("$form_id][mapping][$key", $this->t('@name field is required.', ['@name' => '«' . $form[$form_id]['mapping'][$key]['#title'] . '»']));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->cleanValues();
    $config = $this->config('b24_contact.settings');
    foreach ($form_state->getValues() as $webform_id => $webform_settings) {
      $config->set($webform_id, $webform_settings);
    }
    $config->save();
  }

}
