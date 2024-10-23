<?php

namespace Drupal\vlsuite_block_local_video;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_local_video block exist.
 */
class VLSuiteBlockLocalVideoUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_local_video';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_local_video';

}
