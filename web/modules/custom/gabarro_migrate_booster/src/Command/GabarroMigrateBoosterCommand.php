<?php

/**
 * @file
 * Load required namespaces.
 */

use Drupal\gabarro_migrate_booster\GabarroMigrateBooster;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Attaches a listener to the Symfony Console's COMMAND event.
 *
 * This event is dispatched right before a console command is executed.
 * The provided anonymous function will be triggered, calling the
 * GabarroMigrateBooster::bootDrupal() method.
 *
 * Note: The use of $GLOBALS['dispatcher'] is unconventional in Drupal 9.
 * Typically, you'd inject dependencies using Drupal's service container.
 */
$GLOBALS['dispatcher']->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
  // This method call is likely preparing Drupal with the necessary
  // configurations or states needed for the Migrate Booster feature.
  GabarroMigrateBooster::bootDrupal();
});
