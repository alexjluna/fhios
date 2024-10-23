<?php

namespace Drupal\vlsuite_media_image;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_image media exist.
 */
class VLSuiteMediaImageUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_media_image';
  const ENTITY_TYPE = 'media';
  const BUNDLE_KEY = 'bundle';
  const BUNDLE = 'vlsuite_image';

}
