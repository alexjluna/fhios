<?php

namespace Drupal\b24\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\b24\Service\RestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure b24 settings for this site.
 */
class CredentialsForm extends ConfigFormBase {

  /**
   * The Bitrix24 REST manager service.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $restManager;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs a CredentialsForm object.
   */
  public function __construct(RestManager $rest_manager, RequestStack $request_stack, State $state) {
    $this->restManager = $rest_manager;
    $this->requestStack = $request_stack;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('b24.rest_manager'),
      $container->get('request_stack'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b24_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['b24.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['authorisation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authorization'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['authorisation']['site'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bitrix24 site'),
      '#required' => TRUE,
      '#description' => $this->t('Type in your Bitrix24 domain name'),
      '#default_value' => $this->config('b24.settings')->get('site'),
    ];

    if ($this->config('b24.settings')->get('site')) {
      $form['authorisation']['app'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Application data'),
      ];

      $site_name = $this->config('b24.settings')
        ->get('site') ? $this->config('b24.settings')
        ->get('site') : '[your_site_name]';

      $app_add_link = Link::fromTextAndUrl("https://{$site_name}/marketplace/local/edit/0/", Url::fromUri("https://{$site_name}/marketplace/local/edit/0/", ['attributes' => ['target' => '_blank']]))
        ->toString();
      $app_list_link = Link::fromTextAndUrl("https://{$site_name}/marketplace/local/list/", Url::fromUri("https://{$site_name}/marketplace/local/list/", ['attributes' => ['target' => '_blank']]))
        ->toString();

      $form['authorisation']['app']['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['messages'],
        ],
        '#value' => $this->t('You should create your application on your Bitrix24 portal: @link. Note: redirect_uri value must be @domain/b24/oauth', [
          '@link' => $app_add_link,
          '@domain' => $this->requestStack->getCurrentRequest()
            ->getSchemeAndHttpHost(),
        ]),
      ];

      $form['authorisation']['app']['client_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client ID'),
        '#description' => $this->t('Type in your Bitrix24 application client_id. If you have already created it you can find it on @link',
          ['@link' => $app_list_link]),
        '#default_value' => $this->config('b24.settings')->get('client_id'),
      ];

      $form['authorisation']['app']['client_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client secret code'),
        '#description' => $this->t('Type in your Bitrix24 application client_secret. If you have already created it you can find it on @link',
          ['@link' => $app_list_link]),
        '#default_value' => $this->config('b24.settings')->get('client_secret'),
      ];
    }

    if ($this->config('b24.settings')->get('client_secret')) {
      $token_request_link = $this->restManager->getAuthorizeUri();
      $form['authorisation']['app']['access_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access token'),
        '#description' => $this->t('Click «@link» to request for a new one.', [
          '@link' => Link::fromTextAndUrl($this->t('Get access token'),
            Url::fromUri($token_request_link,
              ['attributes' => ['target' => '_blank']]))->toString(),
        ]),
        '#default_value' => $this->state->get('b24_access_token'),
        '#attributes' => [
          'disabled' => 'disabled',
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('b24.settings');
    $config->set('site', $form_state->getValue('site'));

    // The latter two values (login+password) seem to be unneeded anymore.
    if ($form_state->getValue('login')) {
      $config->set('login', $form_state->getValue('login'));
    }
    if ($form_state->getValue('password')) {
      $config->set('password', $form_state->getValue('password'));
    }

    if ($form_state->getValue('client_id')) {
      $config->set('client_id', $form_state->getValue('client_id'));
    }
    if ($form_state->getValue('client_secret')) {
      $config->set('client_secret', $form_state->getValue('client_secret'));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
