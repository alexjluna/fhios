<?php

namespace Drupal\b24\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the Bitrix24 entity event.
 */
class B24Event extends Event {

  /**
   * Name of the event fired after adding a new Bitrix24 entity.
   *
   * @Event
   */
  const ENTITY_INSERT = 'b24.entity.insert';

  /**
   * Name of the event fired after updating a Bitrix24 entity.
   *
   * @Event
   */
  const ENTITY_UPDATE = 'b24.entity.update';

  /**
   * Name of the event fired after deleting a Bitrix24 entity.
   *
   * @Event
   */
  const ENTITY_DELETE = 'b24.entity.delete';

  /**
   * The Bitrix24 entity name.
   *
   * @var string
   */
  protected $entityName;

  /**
   * The Bitrix24 entity operation type (insert/update/delete).
   *
   * @var string
   */
  protected $operationName;

  /**
   * The Bitrix24 response.
   *
   * @var mixed
   */
  protected $response;

  /**
   * Constructs a new B24Event.
   *
   * @param string $entity_name
   *   The Bitrix24 entity name.
   * @param string $operation_name
   *   The Bitrix24 entity operation type (insert/update/delete).
   * @param mixed $response
   *   The Bitrix24 response.
   */
  public function __construct($entity_name, $operation_name, $response) {
    $this->entityName = $entity_name;
    $this->response = $response;
    $this->operationName = $operation_name;
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
   * Returns a current operation name.
   *
   * @return string
   *   The operation name.
   */
  public function getOperationName() {
    return $this->operationName;
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
