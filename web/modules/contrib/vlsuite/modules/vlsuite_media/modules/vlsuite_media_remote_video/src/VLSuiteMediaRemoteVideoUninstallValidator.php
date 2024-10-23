<?php

namespace Drupal\vlsuite_media_remote_video;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_media_remote_video media exist.
 */
class VLSuiteMediaRemoteVideoUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_media_remote_video';
  const ENTITY_TYPE = 'media';
  const BUNDLE_KEY = 'bundle';
  const BUNDLE = 'vlsuite_remote_video';

}
