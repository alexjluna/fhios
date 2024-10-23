<?php

namespace Drupal\vlsuite_media_document;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_document media exist.
 */
class VLSuiteMediaDocumentUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_media_document';
  const ENTITY_TYPE = 'media';
  const BUNDLE_KEY = 'bundle';
  const BUNDLE = 'vlsuite_document';

}
