<?php
/**
 * MOHAA Stats - Database Uninstallation
 *
 * @package MohaaStats
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

// Remove tables
$smcFunc['db_drop_table']('{db_prefix}mohaa_identities');
$smcFunc['db_drop_table']('{db_prefix}mohaa_claim_codes');
$smcFunc['db_drop_table']('{db_prefix}mohaa_device_tokens');

// Remove settings
$smcFunc['db_query']('', '
    DELETE FROM {db_prefix}settings
    WHERE variable LIKE {string:pattern}',
    [
        'pattern' => 'mohaa_stats_%',
    ]
);
