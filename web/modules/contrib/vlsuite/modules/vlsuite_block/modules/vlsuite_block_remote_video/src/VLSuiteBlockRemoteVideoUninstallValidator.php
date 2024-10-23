<?php

namespace Drupal\vlsuite_block_remote_video;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_remote_video block exist.
 */
class VLSuiteBlockRemoteVideoUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_remote_video';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_remote_video';

}
