<?php

namespace Drupal\vlsuite_collection_hero;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_collection_hero block exist.
 */
class VLSuiteCollectionHeroUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_collection_hero';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_collection_hero';

}
