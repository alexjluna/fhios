<?php

namespace Drupal\vlsuite_block_webform;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_webform block exist.
 */
class VLSuiteBlockWebformUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_webform';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_webform';

}
