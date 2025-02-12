<?php

namespace Drupal\b24\Service;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\b24\Event\B24Event;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The RestManager service.
 *
 * $statuses = [
 * 'NEW' => 'Unassigned',
 * 'ASSIGNED' => 'Responsible Assigned',
 * 'DETAILS' => 'Waiting for Details',
 * 'CANNOT_CONTACT' => 'Cannot Contact',
 * 'IN_PROCESS' => 'In Progress',
 * 'ON_HOLD' => 'On Hold',
 * 'CONVERTED' => 'Converted',
 * 'JUNK' => 'Junk Lead',
 */
class RestManager {

  const AUTH_URI = '/oauth/authorize';

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The module default configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $defaultConfig;

  /**
   * The editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $configEditable;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The system state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The system time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected Time $time;

  /**
   * Constructs a restmanager object.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    RequestStack $request_stack,
    State $state,
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client,
    ModuleHandler $module_handler,
    EventDispatcherInterface $event_dispatcher,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxy $current_user,
    LinkGeneratorInterface $link_generator,
    Time $time,
  ) {
    $this->config = $config->get('b24.settings');
    $this->defaultConfig = $config->get('b24.default_settings');
    $this->configEditable = $config->getEditable('b24.settings');
    $this->requestStack = $request_stack;
    $this->state = $state;
    $this->logger = $logger_factory->get('b24');
    $this->httpClient = $http_client;
    $this->moduleHandler = $module_handler;
    $this->eventDispatcher = $event_dispatcher;
    $this->database = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
    $this->time = $time;
  }

  /**
   * Returns an authorize uri string.
   *
   * @return string
   *   The authorize uri string.
   */
  public function getAuthorizeUri() {
    $uri = 'https://' . $this->config->get('site') . self::AUTH_URI;
    $params = [
      'client_id' => $this->config->get('client_id'),
      'response_type' => 'code',
      'redirect_uri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/b24/oauth',
    ];
    $query = UrlHelper::buildQuery($params);
    return implode('?', [$uri, $query]);
  }

  /**
   * Refreshes an access token.
   *
   * @return mixed|bool
   *   The response data.
   */
  public function refreshAccessToken() {
    if (!$site = $this->config->get('site')) {
      return FALSE;
    }

    // Do not refresh new tokens (received less than 0.5 hours ago).
    $expires = $this->state->get('b24_token_expires');
    $current_time = $this->time->getCurrentTime();
    if (($expires - $current_time) > 1800) {
      return TRUE;
    }

    $uri = 'https://' . $site . '/oauth/token/';
    $scopes = ['crm'];
    $params = [
      'grant_type' => 'refresh_token',
      'client_id' => $this->config->get('client_id'),
      'client_secret' => $this->config->get('client_secret'),
      'scope' => implode(',', $scopes),
      'refresh_token' => $this->state->get('b24_refresh_token'),
      'redirect_uri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/b24/oauth',
    ];
    $query = UrlHelper::buildQuery($params);
    $uri = implode('?', [$uri, $query]);
    try {
      $response = $this->httpClient->get($uri,
        ['headers' => ['Accept' => 'text/plain']]);
      $data = $response->getBody();
      if (empty($data)) {
        $this->logger->error('Bitrix24 token refresh error: response data is empty.');
      }
      else {
        $data = Json::decode($data->__toString());
        if (!empty($data['access_token'])) {
          $token = $data['access_token'];
          $this->state->set('b24_access_token', $token);
          $this->state->set('b24_refresh_token', $data['refresh_token']);
          $this->state->set('b24_token_expires', $data['expires']);
          return $data;
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Bitrix24 token refresh error: @error.', ['@error' => $e->getMessage()]);
    }
    catch (ConnectException $e) {
      $this->logger->error('Bitrix24 connect error: @error.', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Base function for querying Bitrix24 REST API.
   *
   * @param string $method
   *   The REST method name.
   * @param array $params
   *   The array of additional params.
   *
   * @return bool|mixed
   *   Result of the operation.
   */
  public function get($method, array $params = []) {
    if (!$site = $this->config->get('site')) {
      return FALSE;
    }

    $uri = 'https://' . $site . "/rest/$method/";
    $params['auth'] = $this->state->get('b24_access_token');

    try {
      // Try to avoid expired access token error.
      $this->refreshAccessToken();
      $request = $this->httpClient->post($uri, ['form_params' => $params]);
      return Json::decode($request->getBody());
    }
    catch (ClientException $e) {
      $message = Json::decode($e->getResponse()->getBody()->getContents());
      if ($message) {
        $this->logger->error('Bitrix24 method «@error_name» error: @error_message.',
          [
            '@error_name' => $method,
            '@error_message' => $message['error_description'],
          ]);
      }
      return FALSE;
    }
  }

  /**
   * Base function to create a Bitrix24 entity.
   *
   * @param string $b24_entity_name
   *   The Bitrix24 entity type id.
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return int|bool
   *   Result of the operation.
   */
  public function addEntity(string $b24_entity_name, array $fields = [], array $params = []) {
    $context = [
      'entity_name' => $b24_entity_name,
      'op' => 'insert',
    ];
    $this->moduleHandler->alter('b24_push', $fields, $context);
    if ($assignee_id = $this->defaultConfig->get('assignee')) {
      $assignee_id = ($assignee_id !== 'custom') ? $assignee_id : $this->defaultConfig->get('user_id');
      $fields['ASSIGNED_BY_ID'] = $assignee_id;
    }
    $response = $this->get("crm.{$b24_entity_name}.add",
      ['fields' => $fields, 'params' => $params]);
    $ext_id = FALSE;
    if (!empty($response['result'])) {
      $ext_id = $response['result'];
      $link = 'https://' . $this->config->get('site') . "/crm/{$b24_entity_name}/details/{$ext_id}/";
      $this->moduleHandler->invokeAll("b24_{$b24_entity_name}_insert", $response);
      $this->logger->info('A new @entity added to Bitrix24 CRM with id=@id',
        [
          '@entity' => $b24_entity_name,
          '@id' => $ext_id,
          'link' => $this->linkGenerator->generate('view', Url::fromUri($link, ['attributes' => ['target' => '_blank']])),
        ]
      );
      $event = new B24Event($b24_entity_name, 'insert', $response);
      $this->eventDispatcher->dispatch($event, B24Event::ENTITY_INSERT);
    }
    return $ext_id;
  }

  /**
   * Retrieves a Bitrix24 lead.
   *
   * @param int $id
   *   The Bitrix24 lead id.
   *
   * @return array|mixed
   *   The list of leads found.
   */
  public function getLead($id) {
    $response = $this->get('crm.lead.get', ['id' => $id]);
    return $response['result'] ?? [];
  }

  /**
   * Creates a Bitrix24 lead.
   *
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function addLead(array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    if (!array_key_exists('SOURCE_ID', $fields)) {
      $fields['SOURCE_ID'] = 'WEB';
    }

    $fields['OPENED'] = 'Y';
    if ($uid = $this->currentUser->id()) {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $contact = $this->getReference($user_storage->load($uid), 'contact');
      if ($contact) {
        $fields['CONTACT_ID'] = $contact['ext_id'];
      }
    }
    return $this->addEntity('lead', $fields, $params);
  }

  /**
   * Updates a Bitrix24 lead.
   *
   * @param int $id
   *   The Bitrix 24 lead id.
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function updateLead($id, array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    return $this->updateEntity('lead', $id, $fields, $params);
  }

  /**
   * Updates a Bitrix24 entity.
   *
   * @param string $b24_entity_name
   *   The Bitrix24 entity type id.
   * @param int $id
   *   The Bitrix 24 entity id.
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function updateEntity($b24_entity_name, $id, array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    $context = [
      'entity_name' => $b24_entity_name,
      'op' => 'update',
    ];
    $this->moduleHandler->alter('b24_push', $fields, $context);
    $response = $this->get("crm.{$b24_entity_name}.update",
      ['id' => $id, 'fields' => $fields, 'params' => $params]);
    $event = new B24Event($b24_entity_name, 'update', $response);
    $this->eventDispatcher->dispatch($event, B24Event::ENTITY_UPDATE);
    return $response;
  }

  /**
   * Retrieves a Bitrix24 deal.
   *
   * @param int $id
   *   The Bitrix24 deal id.
   *
   * @return array|mixed
   *   The list of deals found.
   */
  public function getDeal($id) {
    $response = $this->get('crm.deal.get', ['id' => $id]);
    return $response['result'] ?? [];
  }

  /**
   * Creates a Bitrix24 deal.
   *
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function addDeal(array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    return $this->addEntity('deal', $fields, $params);
  }

  /**
   * Updates a Bitrix24 deal.
   *
   * @param int $id
   *   The Bitrix 24 deal id.
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function updateDeal($id, array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    $response = $this->get('crm.deal.update',
      ['id' => $id, 'fields' => $fields, 'params' => $params]);
    return $response['result'] ?? FALSE;
  }

  /**
   * Creates a Bitrix24 contact.
   *
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function addContact(array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    return $this->addEntity('contact', $fields, $params);
  }

  /**
   * Updates a Bitrix24 contact.
   *
   * @param int $id
   *   The Bitrix 24 contact id.
   * @param array $fields
   *   The array of field values.
   * @param array $params
   *   The array of additional params.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function updateContact($id, array $fields = [], array $params = ['REGISTER_SONET_EVENT' => 'Y']) {
    $response = $this->get('crm.contact.update',
      ['id' => $id, 'fields' => $fields, 'params' => $params]);
    return $response['result'] ?? FALSE;
  }

  /**
   * Deletes a Bitrix24 contact.
   *
   * @param int $id
   *   The Bitrix 24 contact id.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function deleteContact($id) {
    $result = $this->deleteEntity($id, 'contact');
    if ($result) {
      $this->deleteReference('contact', $id);
    }
    return $result;
  }

  /**
   * Retrieves a Bitrix24 contact.
   *
   * @param int $id
   *   The Bitrix 24 contact id.
   *
   * @return array|mixed
   *   The list of contacts found.
   */
  public function getContact($id) {
    $response = $this->get('crm.contact.get', ['id' => $id]);
    return $response['result'] ?? [];
  }

  /**
   * Returns a list of field for a given Bitrix24 entity type.
   *
   * @param string $entity_name
   *   The Bitrix24 entity type id.
   *
   * @return array|mixed
   *   The list of fields.
   */
  public function getFields($entity_name) {
    $response = $this->get("crm.$entity_name.fields", []);
    return $response['result'] ?? [];
  }

  /**
   * Retrieves a list of entities from Bitrix24.
   *
   * @param string $entity_name
   *   The Bitrix24 entity type id.
   * @param array $params
   *   The condition params.
   *
   * @return array
   *   The array of found entities.
   */
  public function getList($entity_name, array $params = []) {
    $results = [];
    $response = $this->get("crm.$entity_name.list", $params);
    if (!$response) {
      $this->logger->error('Bitrix24 error: response data is empty.');
      return $results;
    }
    $results = array_merge($results, $response['result']);
    if (!empty($response['next'])) {
      $params['start'] = $response['next'];
      $results = array_merge($results, $this->getList($entity_name, $params));
    }
    return $results ?? [];
  }

  /**
   * Sets the array of products for a lead.
   *
   * @param int $id
   *   The lead id.
   * @param array $items
   *   The product items.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function setLeadProducts($id, array $items) {
    return $this->setProductRows($id, 'lead', $items);
  }

  /**
   * Sets the array of products for a deal.
   *
   * @param int $id
   *   The deal id.
   * @param array $items
   *   The product items.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function setDealProducts($id, array $items) {
    return $this->setProductRows($id, 'deal', $items);
  }

  /**
   * Sets the array of products.
   *
   * @param int $id
   *   The entity id.
   * @param string $entity_name
   *   The entity name.
   * @param array $items
   *   The product items.
   *
   * @return array|mixed
   *   Result of the operation.
   */
  public function setProductRows($id, $entity_name, array $items) {
    $params = [
      'id' => $id,
      'rows' => $items,
    ];
    $existing = $this->get("crm.$entity_name.productrows.get", ['id' => $id]);
    if ($existing['result']) {
      $response = $this->get("crm.$entity_name.productrows.set", $params);
      return $response['result'] ?? [];
    }
    else {
      return [];
    }
  }

  /**
   * Gets the reference between Drupal and Bitrix24 entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity earlier exported to Bitrix24.
   * @param string $ext_type
   *   Bitrix24 entity machine_name.
   *
   * @return mixed
   *   The reference.
   */
  public function getReference(EntityInterface $entity, string $ext_type) {
    $reference = $this->database->select('b24_reference', 'b')
      ->fields('b', ['ext_id', 'hash'])
      ->condition('bundle', $entity->bundle())
      ->condition('ext_type', $ext_type)
      ->condition('entity_id', $entity->id())
      ->execute()->fetchAssoc();

    return $reference;
  }

  /**
   * Deletes  reference between Drupal and Bitrix24 entity.
   *
   * @param string $ext_type
   *   The Bitrix24 entity type id.
   * @param int $id
   *   The Bitrix24 entity id.
   */
  public function deleteReference($ext_type, $id) {
    $this->database->delete('b24_reference')
      ->condition('ext_type', $ext_type)
      ->condition('ext_id', $id)
      ->execute();
  }

  /**
   * Updates a hash string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity earlier exported to Bitrix24.
   * @param string $ext_type
   *   Bitrix24 entity machine_name.
   * @param string $hash
   *   Base64 hash of fields in current state.
   */
  public function updateHash(EntityInterface $entity, string $ext_type, string $hash) {
    $this->database->update('b24_reference')
      ->fields([
        'hash' => $hash,
      ])
      ->condition('bundle', $entity->bundle())
      ->condition('ext_type', $ext_type)
      ->condition('entity_id', $entity->id())
      ->execute();
  }

  /**
   * Returns a hash string.
   */
  public function getHash(array $fields) {
    $string = serialize($fields);
    return Crypt::hashBase64($string);
  }

  /**
   * Retrieves a Bitrix24 entity id by a correspondent Drupal entity id.
   *
   * @param int $id
   *   The Drupal entity id.
   * @param string $entity_name
   *   The Bitrix24 entity name.
   *
   * @return false|mixed|void
   *   The Bitrix24 entity id.
   */
  public function getId($id, $entity_name) {
    $list = $this->getList($entity_name, ['filter' => ['XML_ID' => $id]]);
    if ($list) {
      return $list[0]['ID'] ?? FALSE;
    }
  }

  /**
   * Deletes a Bitrix 24 entity of a given type.
   *
   * @param int $id
   *   The Bitrix24 entity id.
   * @param string $b24_entity_name
   *   The Bitrix24 entity type id.
   * @param bool $external
   *   If an id should be retrieved from B24.
   *
   * @return false|mixed
   *   Result of the operation.
   */
  public function deleteEntity($id, $b24_entity_name, $external = FALSE) {
    if ($external) {
      $id = $this->getId($id, $b24_entity_name);
      if (!$id) {
        return FALSE;
      }
    }
    $response = $this->get("crm.{$b24_entity_name}.delete", ['id' => $id]);
    $event = new B24Event($b24_entity_name, 'delete', $response);
    $this->eventDispatcher->dispatch($event, B24Event::ENTITY_DELETE);
    $result = $response['result'] ?? FALSE;
    return $result;
  }

  /**
   * Deletes a Bitrix24 product entity.
   *
   * @param int $id
   *   The Bitrix24 product id.
   * @param bool $external
   *   If an id should be retrieved from B24.
   *
   * @return false|mixed
   *   Result of the operation.
   */
  public function deleteProduct(int $id, bool $external = FALSE) {
    return $this->deleteEntity($id, 'product', $external);
  }

}
