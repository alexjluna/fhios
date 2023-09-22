<?php

namespace Drupal\gabarro_migrate_booster\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Drupal\gabarro_migrate_booster\GabarroMigrateBooster;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Defines Drush commands for the gabarro_migrate_booster module.
 *
 * It's important to have a drush.services.yml file in the root of the module
 * to declare and manage these commands.
 *
 * @see http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 * @see http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GabarroMigrateBoosterCommands extends DrushCommands {

  /**
   * Resets the migrate booster and the implementation cache.
   *
   * @command migrate:booster:reset
   * @validate-module-enabled migrate_booster
   * @aliases mbr, migrate-booster-reset
   *
   * @return void
   *   No return value.
   */
  public function boosterReset() {
    GabarroMigrateBooster::reset();
  }

  /**
   * Enables the migrate booster and the implementation cache.
   *
   * @command migrate:booster:enable
   * @validate-module-enabled migrate_booster
   * @aliases mbe, migrate-booster-enable
   *
   * @return void
   *   No return value.
   */
  public function boosterEnable() {
    GabarroMigrateBooster::enable();
  }

  /**
   * Initializes the command by setting up the booster, if applicable.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input interface instance.
   * @param \Consolidation\AnnotatedCommand\AnnotationData $annotationData
   *   The annotation data.
   *
   * @return void
   *   No return value.
   *
   * @hook init *
   */
  public function initCommand(InputInterface $input, AnnotationData $annotationData) {
    if (!\Drupal::hasContainer()) {
      return;
    }
    GabarroMigrateBooster::bootDrush($input, $annotationData);
  }

}
