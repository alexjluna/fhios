<?php

namespace Drupal\vlsuite_collection_card;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_collection_card block exist.
 */
class VLSuiteCollectionCardUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_collection_card';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_collection_card';

}
