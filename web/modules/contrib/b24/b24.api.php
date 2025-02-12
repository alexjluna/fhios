<?php

/**
 * @file
 * Hooks related to b24 module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters fields values before pushing them to Bitrix24.
 *
 * @param array $fields
 *   An associative array of fields to send to Bitrix24 REST API endpoint.
 * @param array $context
 *   An associative array containing:
 *     op: operation name ('insert', 'update', 'delete')
 *     entity_name: Bitrix24 entity name i.e. 'lead', 'deal' and so on.
 */
function hook_b24_push_alter(array &$fields, array $context) {

}

/**
 * @} End of "addtogroup hooks".
 */
