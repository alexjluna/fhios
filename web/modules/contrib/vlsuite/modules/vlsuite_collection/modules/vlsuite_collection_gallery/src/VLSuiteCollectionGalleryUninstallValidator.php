<?php

namespace Drupal\vlsuite_collection_gallery;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_collection_gallery block exist.
 */
class VLSuiteCollectionGalleryUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_collection_gallery';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_collection_gallery';

}
