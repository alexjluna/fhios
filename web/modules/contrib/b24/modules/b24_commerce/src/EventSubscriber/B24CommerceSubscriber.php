<?php

namespace Drupal\b24_commerce\EventSubscriber;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\b24\Service\RestManager;
use Drupal\b24_commerce\Event\B24CommerceEvent;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * B24_commerce event subscriber.
 */
class B24CommerceSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Bitrix24 rest manager service.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $restManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The user account interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs event subscriber.
   */
  public function __construct(
    MessengerInterface $messenger,
    RestManager $b24_rest_manager,
    ConfigFactory $config_factory,
    Token $token,
    Connection $connection,
    AccountInterface $account,
    EventDispatcherInterface $event_dispatcher,
    ModuleHandler $module_handler,
  ) {
    $this->messenger = $messenger;
    $this->restManager = $b24_rest_manager;
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->database = $connection;
    $this->currentUser = $account;
    $this->dispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [
      OrderEvents::ORDER_INSERT => 'onOrderInsert',
      OrderEvents::ORDER_UPDATE => 'onOrderUpdate',
      // Next one seems has been defined not so long ago so we are not able to
      // get it as a constant.
      // @see https://www.drupal.org/project/commerce/issues/2856586
      'commerce_order.order.paid' => 'onOrderPaid',
      OrderEvents::ORDER_ASSIGN => 'onOrderAssign',
      OrderEvents::ORDER_DELETE => 'onOrderDelete',
      'state_machine.post_transition' => 'onGenericTransition',
    ];

    return $events;
  }

  /**
   * Reacts on any transition defined in the system.
   *
   * In classic CRM mode we have to react on transition events from the module
   * settings to convert leads into another entities like deals and contacts.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onGenericTransition(WorkflowTransitionEvent $event) {
    if ($event->getEntity()->getEntityType()->id() == 'commerce_order') {
      if ($this->configFactory->get('b24_commerce.settings')->get('crm_mode') == 'simple') {
        return;
      }
      $order = $event->getEntity();
      $transition_current = $event->getTransition()->getId();
      if ($this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_contact")) {
        $transtition_set = $this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_contact_step");
        if ($transition_current == $transtition_set) {
          $this->convertToContact($order);
        }
      }

      if ($this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_deal")) {
        $transtition_set = $this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_deal_step");
        if ($transition_current == $transtition_set) {
          $this->convertToDeal($order);
        }
      }
    }
  }

  /**
   * Converts a commerce order to Bitrix24 "deal" entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return bool|int|null
   *   The result of export.
   */
  public function convertToDeal(OrderInterface $order) {
    $fields = $this->getValues($order, NULL, 'deal');
    if (!$fields) {
      return NULL;
    }
    $reference = $this->getReference($order);
    if (!empty($reference['ext_id'])) {
      $fields['LEAD_ID'] = $reference['ext_id'];
    }
    $fields['CONTACT_ID'] = $this->convertToContact($order);
    $context = [
      'op' => 'insert',
      'entity_name' => 'deal',
      'order' => $order,
      'mode' => $this->configFactory->get('b24_commerce.settings')->get('crm_mode'),
    ];
    $this->moduleHandler->alter('b24_commerce_data', $fields, $context);
    $deal = $this->restManager->addDeal($fields);
    $this->database->insert('b24_reference')
      ->fields([
        'entity_id' => $order->id(),
        'bundle' => 'commerce_order',
        'ext_id' => $deal,
        'ext_type' => 'deal',
      ])->execute();

    $event = new B24CommerceEvent($order, 'deal', $deal);
    $this->dispatcher->dispatch($event, B24CommerceEvent::ENTITY_INSERT);
    $this->updateProducts($order, 'deal', $deal);
    return $deal ?? NULL;
  }

  /**
   * The function executed on commerce order insert event.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The commerce order insert event.
   */
  public function onOrderInsert(OrderEvent $event) {
    $order = $event->getOrder();
    $fields = $this->getValues($order);
    if (!$fields) {
      return FALSE;
    }
    $context = [
      'op' => 'insert',
      'entity_name' => 'lead',
      'order' => $order,
      'mode' => $this->configFactory->get('b24_commerce.settings')->get('crm_mode'),
    ];
    $this->moduleHandler->alter('b24_commerce_data', $fields, $context);
    $ext_id = $this->restManager->addLead($fields);
    $event = new B24CommerceEvent($order, 'lead', $ext_id);
    $this->dispatcher->dispatch($event, B24CommerceEvent::ENTITY_INSERT);
    if ($ext_id) {
      $hash = $this->getHash($fields);
      $this->database->insert('b24_reference')
        ->fields([
          'entity_id' => $order->id(),
          'bundle' => 'commerce_order',
          'ext_id' => $ext_id,
          'ext_type' => 'lead',
          'hash' => $hash,
        ])->execute();
    }
    $this->updateProducts($order);
  }

  /**
   * The function executed on commerce order update event.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The commerce order event.
   */
  public function onOrderUpdate(OrderEvent $event) {
    $order = $event->getOrder();
    $this->updateOrder($order);

    // @todo Move $mode to class variable.
    // @todo Attention - duplicated code!
    $mode = $this->configFactory->get('b24_commerce.settings')->get('crm_mode');
    if ($mode == 'classic' && method_exists($order, 'isPaid') && $order->isPaid() && !$order->original->isPaid()) {
      if ($this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_contact")) {
        $transition_set = $this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_contact_step");
        if ($transition_set == 'paid') {
          $this->convertToContact($order);
        }
      }

      if ($this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_deal")) {
        $transition_set = $this->configFactory->get('b24_commerce.settings')->get("{$order->bundle()}.convert_deal_step");
        if ($transition_set == 'paid') {
          $this->convertToDeal($order);
        }
      }
    }
  }

  /**
   * The function executed on commerce order paid event.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The commerce order paid event.
   */
  public function onOrderPaid(OrderEvent $event) {
    // @todo Create logic or remove.
    $event->getOrder();
  }

  /**
   * The function executed on commerce order assign event.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The commerce order assign event.
   */
  public function onOrderAssign(OrderAssignEvent $event) {
    $mail = $this->currentUser->getEmail();
    $order = $event->getOrder();
    $order->setEmail($mail);
    $this->updateOrder($order);
  }

  /**
   * Callback executed on commerce order delete event.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The commerce order event.
   */
  public function onOrderDelete(OrderEvent $event) {
    $order = $event->getOrder();
    $reference = $this->getReference($order);
    if (!empty($reference['ext_id'])) {
      $this->restManager->get('crm.lead.delete', ['id' => $reference['ext_id']]);
      $this->database->delete('b24_reference')
        ->condition('bundle', 'commerce_order')
        ->condition('ext_type', 'lead')
        ->condition('entity_id', $order->id())
        ->execute();
    }
  }

  /**
   * Collects field values for a chosen Bitrix24 entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Drupal commerce order.
   * @param null|int $id
   *   Bitrix24 entity id.
   * @param string $entity
   *   Bitrix24 entity type id.
   *
   * @return array
   *   An array of fields.
   */
  public function getValues(OrderInterface $order, ?int $id = NULL, $entity = 'lead') {
    $config = $this->configFactory->get("b24_commerce.mapping.{$order->bundle()}");
    if (!$config->get($entity)) {
      return [];
    }
    $fields = array_filter($config->get($entity));
    $profile = $order->getBillingProfile();
    $user = $order->getCustomer();
    $field_definitions = $this->configFactory->get("b24_commerce.field_types")->get();
    if ($id) {
      $method_name = 'get' . ucfirst($entity);
      $existing_entity = $this->restManager->{$method_name}($id);
    }
    foreach ($fields as $field_name => &$value) {
      $bubbleable_metadata = new BubbleableMetadata();
      $value = nl2br($this->token->replace($value,
        ['commerce_order' => $order, 'profile' => $profile, 'user' => $user],
        ['clear' => TRUE], $bubbleable_metadata));
      if ($field_definitions[$field_name]['isRequired'] && !$value) {
        $value = $this->t('- Not set -');
      }
      /*
       * In Bitrix24 each item of a multiple field is a separate entity.
       * Updating multiple field items without mentioning id causes adding
       * another item. So we need to get all the related ids previously.
       */
      if ($field_definitions[$field_name]['type'] == 'crm_multifield') {
        $value = [['VALUE' => $value, 'VALUE_TYPE' => 'HOME']];
        if (!empty($existing_entity[$field_name])) {
          // @todo Support multiple drupal fields.
          $value[0]['ID'] = reset($existing_entity[$field_name])['ID'];
        }
      }
    }
    return $fields;
  }

  /**
   * Generates hash string.
   *
   * @param array $fields
   *   An array of fields to generate hash on.
   *
   * @return string
   *   The hash string.
   */
  protected function getHash(array $fields) {
    $string = serialize($fields);
    return Crypt::hashBase64($string);
  }

  /**
   * Updates an existing Bitrix24 order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return bool
   *   Result of update.
   */
  public function updateOrder(OrderInterface $order) {
    $mode = $this->configFactory->get('b24_commerce.settings')->get('crm_mode');
    $reference = $this->getReference($order);
    $update = FALSE;
    if (!empty($reference['ext_id'])) {
      $fields = $this->getValues($order, $reference['ext_id']);
      if (!$fields) {
        return FALSE;
      }
      $hash = $this->getHash($fields);
      if ($hash !== $reference['hash']) {
        if ($mode == 'classic') {
          $context = [
            'op' => 'update',
            'entity_name' => 'lead',
            'order' => $order,
            'mode' => $this->configFactory->get('b24_commerce.settings')->get('crm_mode'),
          ];
          $this->moduleHandler->alter('b24_commerce_data', $fields, $context);
          $update = $this->restManager->updateLead($reference['ext_id'], $fields);
        }
        else {
          // Get contacts created by this lead.
          $search = $this->restManager->get('crm.contact.list', ['filter' => ['LEAD_ID' => $reference['ext_id']]]);
          $contact = reset($search['result']);
          $context = [
            'op' => 'update',
            'entity_name' => 'contact',
            'order' => $order,
            'mode' => $this->configFactory->get('b24_commerce.settings')->get('crm_mode'),
          ];
          $this->moduleHandler->alter('b24_commerce_data', $fields, $context);
          $update = $this->restManager->updateContact($contact['ID'], $fields);
        }
        $this->database->update('b24_reference')
          ->fields([
            'hash' => $hash,
          ])
          ->condition('bundle', 'commerce_order')
          ->condition('ext_type', 'lead')
          ->condition('entity_id', $order->id())
          ->execute();
      }
    }
    $this->updateProducts($order);
    $event = new B24CommerceEvent($order, 'lead', $update);
    $this->dispatcher->dispatch($event, B24CommerceEvent::ENTITY_UPDATE);
    return $update;
  }

  /**
   * Looks for existing reference for this order n Bitrix24 database.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return array
   *   The reference found.
   */
  public function getReference(OrderInterface $order) {
    $reference = $this->database->select('b24_reference', 'b')
      ->fields('b', ['ext_id', 'hash'])
      ->condition('bundle', 'commerce_order')
      ->condition('ext_type', 'lead')
      ->condition('entity_id', $order->id())
      ->execute()->fetchAssoc();

    return $reference;
  }

  /**
   * Generates an array of bought products to be exported to Bitrix24.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return array
   *   An array of product rows.
   */
  public function getProductRows(OrderInterface $order) {
    $order_items = $order->getItems();
    $product_rows = [];
    foreach ($order_items as $order_item) {
      /** @var \Drupal\commerce_order\Entity\OrderItem $order_item */
      if (empty($order_item) || !$order_item->getPurchasedEntity()) {
        continue;
      }
      $product_rows[$order_item->getPurchasedEntity()->id()] = [
        'quantity' => $order_item->getQuantity(),
        'price' => $order_item->getPurchasedEntity()->getPrice()->getNumber(),
      ];
    }

    $rows = [];
    $product_ids = array_keys($product_rows);
    if ($product_ids) {
      $ext_products = $this->restManager->getList('product', ['filter' => ['XML_ID' => $product_ids]]);
      foreach ($ext_products as $ext_product) {
        $rows[] = [
          'PRODUCT_ID' => $ext_product['ID'],
          'PRICE' => $product_rows[$ext_product['XML_ID']]['price'],
          'QUANTITY' => $product_rows[$ext_product['XML_ID']]['quantity'],
        ];
      }
    }
    return $rows;
  }

  /**
   * Updates products for deal/lead.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   The commerce order.
   * @param string $entity
   *   The bitrix24 entity type id.
   * @param int $entity_id
   *   The bitrix24 entity id.
   *
   * @return array|false|mixed
   *   The result of update.
   */
  public function updateProducts(Order $order, $entity = 'lead', $entity_id = NULL) {
    $mode = $this->configFactory->get('b24_commerce.settings')->get('crm_mode');

    $rows = $this->getProductRows($order);
    $reference = $this->getReference($order);
    if (!$reference) {
      return FALSE;
    }
    if ($mode == 'classic') {
      if ($entity == 'lead') {
        $result = $this->restManager->setLeadProducts($reference['ext_id'], $rows);
      }
      elseif ($entity == 'deal' && $entity_id) {
        $result = $this->restManager->setDealProducts($entity_id, $rows);
      }
    }
    else {
      $search = $this->restManager->get('crm.deal.list', ['filter' => ['LEAD_ID' => $reference['ext_id']]]);
      $deal = reset($search['result']);
      $result = $this->restManager->setDealProducts($deal['ID'], $rows);
    }
    return $result ?? FALSE;
  }

  /**
   * Looks for existing Bitrix24 contact or creates a news one.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return int|null
   *   The contact id.
   */
  public function convertToContact(OrderInterface $order) {
    if ($this->moduleHandler->moduleExists('b24_user')) {
      $user_enabled = $this->configFactory->getEditable('b24_user.settings')->get('enabled');
      $this->configFactory->getEditable('b24_user.settings')->set('enabled', 0);
    }
    $fields = $this->getValues($order);
    if (!$fields) {
      return NULL;
    }
    // We seek for existing contacts and create a new one if no results found.
    // @todo Make the unique field configurable.
    // @todo Use more than one filtering field.
    $value = $fields['EMAIL'][0]['VALUE'] ?? FALSE;
    if ($value) {
      $search = $this->restManager->get('crm.contact.list', ['filter' => ['EMAIL' => $value]]);
    }
    if (!empty($search['result'])) {
      $contact = reset($search['result']);
      $contact_id = $contact['ID'];
    }
    else {
      $reference = $this->getReference($order);
      if ($reference) {
        $fields['LEAD_ID'] = $reference['ext_id'];
      }
      $context = [
        'op' => 'insert',
        'entity_name' => 'contact',
        'order' => $order,
        'mode' => $this->configFactory->get('b24_commerce.settings')->get('crm_mode'),
      ];
      $this->moduleHandler->alter('b24_commerce_data', $fields, $context);
      $contact_id = $this->restManager->addContact($fields);
      $event = new B24CommerceEvent($order, 'contact', $contact_id);
      $this->dispatcher->dispatch($event, B24CommerceEvent::ENTITY_INSERT);
    }
    if ($this->moduleHandler->moduleExists('b24_user')) {
      $this->configFactory->getEditable('b24_user.settings')->set('enabled', $user_enabled);
    }
    return $contact_id ?? NULL;
  }

}
