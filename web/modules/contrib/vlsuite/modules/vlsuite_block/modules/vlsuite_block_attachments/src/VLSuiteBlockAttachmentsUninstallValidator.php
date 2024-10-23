<?php

namespace Drupal\vlsuite_block_attachments;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_attachments block exist.
 */
class VLSuiteBlockAttachmentsUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_attachments';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_attachments';

}
