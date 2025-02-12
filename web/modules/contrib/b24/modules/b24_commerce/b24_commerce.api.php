<?php

/**
 * @file
 * Hooks related to b24_commerce module.
 */

// phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows to alter data before export to Bitrix24.
 *
 * @param array $fields
 *   An associative array of fields to send to Bitrix24 REST API endpoint.
 * @param array $context
 *   An associative array containing:
 *     op: operation name ('insert', 'update', 'delete')
 *     entity_name: Bitrix24 entity name i.e. 'lead', 'deal' and so on
 *     order: a \Drupal\commerce_order\Entity\Order object,
 *     mode: current Bitrix24 CRM mode ('simple' or 'classic').
 */
function hook_b24_commerce_data_alter(array &$fields, array $context) {

}

/**
 * @} End of "addtogroup hooks".
 */
