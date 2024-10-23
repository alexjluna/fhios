<?php

namespace Drupal\vlsuite_block_icon;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_icon block exist.
 */
class VLSuiteBlockIconUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_icon';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_icon';

}
