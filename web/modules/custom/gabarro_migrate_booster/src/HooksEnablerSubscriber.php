<?php

namespace Drupal\gabarro_migrate_booster;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to the 'kernel.request' event to ensure hooks are enabled.
 *
 * This subscriber listens to the kernel request event bootstrap.
 * process. Its primary purpose is to ensure that certain migration-related.
 * are enabled when a request is processed, but not during Drush.
 */
class HooksEnablerSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['ensureHooksEnabled', 20];
    return $events;
  }

  /**
   * Triggers on 'kernel.request' event which occurs when Drupal.
   */
  public function ensureHooksEnabled() {
    GabarroMigrateBooster::bootDrupal();

  }

}
