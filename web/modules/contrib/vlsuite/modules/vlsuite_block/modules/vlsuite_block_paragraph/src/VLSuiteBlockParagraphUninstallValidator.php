<?php

namespace Drupal\vlsuite_block_paragraph;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_paragraph block exist.
 */
class VLSuiteBlockParagraphUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_paragraph';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_paragraph';

}
