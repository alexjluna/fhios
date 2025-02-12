<?php

namespace Drupal\b24_user\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\b24_commerce\Event\B24CommerceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class B24UserEventSubscriber implements EventSubscriberInterface {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new B24UserEventSubscriber object.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events['b24_commerce.entity.insert'] = ['onEntityInsert'];

    return $events;
  }

  /**
   * Called whenever the b24_commerce.entity.insert event is dispatched.
   *
   * If a contact has been created by b24_commerce module by converting from a
   * lead we add it to our reference table to notify b24_user module.
   *
   * @param \Drupal\b24_commerce\Event\B24CommerceEvent $event
   *   The B24CommerceEvent event.
   */
  public function onEntityInsert(B24CommerceEvent $event) {
    switch ($event->getName()) {
      case 'contact':
        if ($ext_id = $event->getResponse()) {
          $uid = $event->getOrder()->getCustomerId();
          $this->database->insert('b24_reference')
            ->fields([
              'entity_id' => $uid,
              'bundle' => 'user',
              'ext_id' => $ext_id,
              'ext_type' => 'contact',
              'hash' => '',
            ])->execute();
        }
        break;
    }
  }

}
