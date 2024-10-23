<?php

namespace Drupal\vlsuite_block_text;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_text block exist.
 */
class VLSuiteBlockTextUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_text';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_text';

}
