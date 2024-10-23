<?php

namespace Drupal\vlsuite_landing;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_landing node exist.
 */
class VLSuiteLandingUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_landing';
  const ENTITY_TYPE = 'node';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_landing';

}
