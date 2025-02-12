<?php

namespace Drupal\b24\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for b24 routes.
 */
class Auth extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $configEditable;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The controller constructor.
   */
  public function __construct(
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory,
    State $state,
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client,
  ) {
    $this->requestStack = $request_stack;
    $this->configEditable = $config_factory->getEditable('b24.settings');
    $this->config = $config_factory->get('b24.settings');
    $this->state = $state;
    $this->loggerFactory = $logger_factory;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('logger.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => ' ',
    ];

    // Get an auth code.
    $code = $this->requestStack->getCurrentRequest()->get('code');
    // Request for an access token.
    $uri = 'https://' . $this->config->get('site') . '/oauth/token/';
    $scopes = ['crm'];
    $params = [
      'grant_type' => 'authorization_code',
      'client_id' => $this->config->get('client_id'),
      'client_secret' => $this->config->get('client_secret'),
      'scope' => implode(',', $scopes),
      'code' => $code,
    ];
    $query = UrlHelper::buildQuery($params);
    $uri = implode('?', [$uri, $query]);
    try {
      $response = $this->httpClient->get($uri,
        ['headers' => ['Accept' => 'text/plain']]);
      $data = $response->getBody();
      if (empty($data)) {
        $build['content']['#value'] = $this->t('Response data is empty.');
        $build['content']['#attributes']['class'] = [
          'messages',
          'messages--error',
        ];
      }
      else {
        $data = json_decode($data->__toString());
        $build['content']['#value'] = $this->t('New access token successfully received.');
        $build['content']['#attributes']['class'] = [
          'messages',
          'messages--status',
        ];
        if (!empty($data->access_token)) {
          $token = $data->access_token;
          $this->state->set('b24_access_token', $token);
          $this->state->set('b24_refresh_token', $data->refresh_token);
          $this->loggerFactory->get('b24')->info('New Bitrix24 access token received.');
        }
      }
    }
    catch (RequestException $e) {
      $build['content']['#value'] = $e->getMessage();
      $build['content']['#attributes']['class'] = [
        'messages',
        'messages--error',
      ];
    }

    return $build;
  }

}
