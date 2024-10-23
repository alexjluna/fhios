<?php

namespace Drupal\vlsuite_block_image;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_image block exist.
 */
class VLSuiteBlockImageUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_image';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_image';

}
