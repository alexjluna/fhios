<?php

namespace Drupal\vlsuite_media_icon;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_icon media exist.
 */
class VLSuiteMediaIconUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_media_icon';
  const ENTITY_TYPE = 'media';
  const BUNDLE_KEY = 'bundle';
  const BUNDLE = 'vlsuite_icon';

}
