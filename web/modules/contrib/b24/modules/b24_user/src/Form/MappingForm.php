<?php

namespace Drupal\b24_user\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\b24\Service\RestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for configuring fields mapping for users.
 */
class MappingForm extends ConfigFormBase {

  /**
   * Drupal\b24\Service\RestManager definition.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected RestManager $b24RestManager;

  /**
   * Constructs a new MappingForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    RestManager $b24_rest_manager,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->b24RestManager = $b24_rest_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('b24.rest_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      "b24_user.mapping",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b24_user_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $token_link = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['profile', 'user'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
    ];

    $form['token_tree_top'] = $token_link;

    $b24_fields = $this->getFields($form, 'contact');

    $form['token_tree_bottom'] = $token_link;
    $form_state->addBuildInfo('ext_fields', $b24_fields);

    $this->getFields($form);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Return fields according to those provided by Bitrix24.
   *
   * @param array $form
   *   The form array.
   *
   * @return array|mixed
   *   The fields list.
   *
   * @todo Move the following to a separate place along to the same function in b24_commerce.
   */
  private function getFields(array &$form) {
    $config = $this->config("b24_user.mapping");
    $b24_fields = $this->b24RestManager->getFields('contact');
    // @todo The HONORIFIC (Обращение) field is documented as crm_status but returned by CRM as a string.
    foreach ($b24_fields as $b24_field_name => $b24_field_properties) {
      if (in_array($b24_field_properties['type'],
          [
            'string',
            'crm_multifield',
            'double',
          ]
        ) && !$b24_field_properties['isReadOnly']) {
        $form['contact'][$b24_field_name] = [
          '#type' => $b24_field_name == 'COMMENTS' ? 'textarea' : 'textfield',
          '#title' => $b24_field_properties['title'],
          '#default_value' => $config->get("$b24_field_name"),
          '#required' => $b24_field_properties['isRequired'],
          '#element_validate' => ['::tokenedFieldValidate'],
        ];
      }
    }

    return $b24_fields;
  }

  /**
   * Validation callback for tokenized field.
   */
  public function tokenedFieldValidate(&$element, FormStateInterface $form_state) {
    // @todo May be useful in future.
    /* if ($element['#value']) {
    $form_state->setError($element, $this->t('Error'));
    } */
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable("b24_user.mapping");
    foreach ($values as $name => $value) {
      $config->set($name, $value);
    }
    $config->save();

    $this->configFactory->getEditable('b24_user.field_types')->setData($form_state->getBuildInfo()['ext_fields'])->save();
  }

}
