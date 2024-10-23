<?php

namespace Drupal\vlsuite_block_cta;

use Drupal\vlsuite\VLSuiteUninstallValidator;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any vlsuite_cta block exist.
 */
class VLSuiteBlockCtaUninstallValidator extends VLSuiteUninstallValidator {

  const MODULE = 'vlsuite_block_cta';
  const ENTITY_TYPE = 'block_content';
  const BUNDLE_KEY = 'type';
  const BUNDLE = 'vlsuite_cta';

}
