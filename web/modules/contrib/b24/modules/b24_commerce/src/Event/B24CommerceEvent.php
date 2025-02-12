<?php

namespace Drupal\b24_commerce\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the order assign event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class B24CommerceEvent extends Event {

  /**
   * Name of the event fired after adding a new Bitrix24 entity.
   *
   * @Event
   */
  const ENTITY_INSERT = 'b24_commerce.entity.insert';

  /**
   * Name of the event fired after updating a Bitrix24 entity.
   *
   * @Event
   */
  const ENTITY_UPDATE = 'b24_commerce.entity.update';

  /**
   * Name of the event fired after deleting a Bitrix24 entity.
   *
   * @Event
   */
  const ENTITY_DELETE = 'b24_commerce.entity.delete';

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The Bitrix24 entity name.
   *
   * @var string
   */
  protected $entityName;

  /**
   * The Bitrix24 response.
   *
   * @var mixed
   */
  protected $response;

  /**
   * Constructs a new OrderAssignEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $entity_name
   *   The Bitrix24 entity name.
   * @param mixed $response
   *   The Bitrix24 response.
   */
  public function __construct(OrderInterface $order, $entity_name, $response) {
    $this->order = $order;
    $this->entityName = $entity_name;
    $this->response = $response;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the Bitrix 24 entity name.
   *
   * @return string
   *   The Bitrix24 entity name.
   */
  public function getName() {
    return $this->entityName;
  }

  /**
   * Gets the Bitrix24 response.
   *
   * @return array
   *   The Bitrix 24 response.
   */
  public function getResponse() {
    return $this->response;
  }

}
