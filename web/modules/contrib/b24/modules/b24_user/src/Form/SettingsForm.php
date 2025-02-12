<?php

namespace Drupal\b24_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for configuring the module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'b24_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('b24_user.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable live export'),
      '#description' => $this->t('If enabled, all changes related to creating, updating or deleting Drupal users will be synchronized with Bitrix24 contacts.'),
      '#default_value' => $config->get('enabled'),
    ];
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    array_walk($roles, function (&$item) {
      $item = $item->label();
    });
    unset($roles['anonymous']);
    $form['exported_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exported roles'),
      '#description' => $this->t('Define roles of users you want to be exported to Bitrix24'),
      '#options' => $roles,
      '#default_value' => $config->get('exported_roles'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('b24_user.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('exported_roles', $form_state->getValue('exported_roles'))
      ->save();
  }

}
