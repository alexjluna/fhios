<?php

namespace Drupal\b24\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\b24\Service\RestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure b24 settings for this site.
 */
class DefaultSettingsForm extends ConfigFormBase {

  /**
   * The Bitrix24 REST manager service.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $restManager;

  /**
   * Constructs a DefaultSettingsForm object.
   */
  public function __construct(RestManager $rest_manager) {
    $this->restManager = $rest_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('b24.rest_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b24_default_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['b24.default_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $response = $this->restManager->get('user.get',
      ['filter' => ['ACTIVE' => 1, 'USER_TYPE' => 'employee']]);

    $users = [
      'custom' => $this->t('- Set user ID manually -'),
    ];

    if ($response) {
      foreach ($response['result'] as $user) {
        $name = ($user['NAME'] || $user['LAST_NAME']) ? implode(' ',
          [$user['NAME'], $user['LAST_NAME']]) : $user['EMAIL'];
        $users[$user['ID']] = $name;
      }
    }
    else {
      $this->messenger()->addWarning($this->t('Bitrix24 returned empty result, check if your app has proper permission to access users.'));
    }

    $form['assignee_user'] = [
      '#type' => 'details',
      '#title' => $this->t('Assignee user'),
      '#description' => $this->t('A Bitrix24 user new entities will be assigned to.'),
      '#open' => TRUE,
    ];

    $form['assignee_user']['assignee'] = [
      '#type' => 'select',
      '#title' => $this->t('Assignee'),
      '#description' => $this->t('Choose a user from the list.'),
      '#default_value' => $this->config('b24.default_settings')->get('assignee'),
      '#options' => $users,
      '#empty_option' => $this->t('- None -'),
    ];

    $form['assignee_user']['user_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Bitrix24 user ID'),
      '#min' => 1,
      '#default_value' => $this->config('b24.default_settings')->get('user_id'),
      '#description' => $this->t('In case if your app does not have permission to get users list, set Bitrix24 user ID manually. Note that existence of this user can not be checked.'),
      '#states' => [
        'visible' => [
          '[name="assignee"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('assignee') == 'custom') {
      $user_id = $form_state->getValue('user_id');
      if ($user_id == '') {
        $form_state->setErrorByName('user_id', $this->t('@name field is required.', ['@name' => '"Bitrix24 user ID"']));
      }
      if ($user_id <= 0) {
        $form_state->setErrorByName('user_id', $this->t('@name field must be positive integer.', ['@name' => '"Bitrix24 user ID"']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('b24.default_settings');
    $config->set('assignee', $form_state->getValue('assignee'));
    $config->set('user_id', $form_state->getValue('user_id'));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
