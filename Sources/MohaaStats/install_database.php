<?php
/**
 * MOHAA Stats - Database Installation
 *
 * @package MohaaStats
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

$tables = [];

// Identity linking table
$tables[] = [
    'name' => 'mohaa_identities',
    'columns' => [
        [
            'name' => 'id_identity',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'auto' => true,
        ],
        [
            'name' => 'id_member',
            'type' => 'mediumint',
            'size' => 8,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'player_guid',
            'type' => 'varchar',
            'size' => 64,
            'default' => '',
        ],
        [
            'name' => 'player_name',
            'type' => 'varchar',
            'size' => 255,
            'default' => '',
        ],
        [
            'name' => 'linked_date',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'verified',
            'type' => 'tinyint',
            'size' => 1,
            'unsigned' => true,
            'default' => 0,
        ],
    ],
    'indexes' => [
        [
            'type' => 'primary',
            'columns' => ['id_identity'],
        ],
        [
            'type' => 'index',
            'columns' => ['id_member'],
        ],
        [
            'type' => 'unique',
            'columns' => ['player_guid'],
        ],
    ],
];

// Pending claim codes
$tables[] = [
    'name' => 'mohaa_claim_codes',
    'columns' => [
        [
            'name' => 'id_claim',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'auto' => true,
        ],
        [
            'name' => 'id_member',
            'type' => 'mediumint',
            'size' => 8,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'claim_code',
            'type' => 'varchar',
            'size' => 16,
            'default' => '',
        ],
        [
            'name' => 'created_at',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'expires_at',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'used',
            'type' => 'tinyint',
            'size' => 1,
            'unsigned' => true,
            'default' => 0,
        ],
    ],
    'indexes' => [
        [
            'type' => 'primary',
            'columns' => ['id_claim'],
        ],
        [
            'type' => 'unique',
            'columns' => ['claim_code'],
        ],
        [
            'type' => 'index',
            'columns' => ['id_member'],
        ],
    ],
];

// Device auth tokens (for game client login)
$tables[] = [
    'name' => 'mohaa_device_tokens',
    'columns' => [
        [
            'name' => 'id_token',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'auto' => true,
        ],
        [
            'name' => 'id_member',
            'type' => 'mediumint',
            'size' => 8,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'user_code',
            'type' => 'varchar',
            'size' => 16,
            'default' => '',
        ],
        [
            'name' => 'device_code',
            'type' => 'varchar',
            'size' => 64,
            'default' => '',
        ],
        [
            'name' => 'created_at',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'expires_at',
            'type' => 'int',
            'size' => 10,
            'unsigned' => true,
            'default' => 0,
        ],
        [
            'name' => 'verified',
            'type' => 'tinyint',
            'size' => 1,
            'unsigned' => true,
            'default' => 0,
        ],
    ],
    'indexes' => [
        [
            'type' => 'primary',
            'columns' => ['id_token'],
        ],
        [
            'type' => 'unique',
            'columns' => ['user_code'],
        ],
        [
            'type' => 'index',
            'columns' => ['device_code'],
        ],
    ],
];

// Create tables
foreach ($tables as $table) {
    $smcFunc['db_create_table'](
        '{db_prefix}' . $table['name'],
        $table['columns'],
        $table['indexes'],
        [],
        'ignore'
    );
}

// Default settings
$settings = [
    'mohaa_stats_enabled' => 1,
    'mohaa_stats_api_url' => 'http://localhost:8080',
    'mohaa_stats_server_token' => '',
    'mohaa_stats_api_timeout' => 10,
    'mohaa_stats_cache_duration' => 60,
    'mohaa_stats_live_cache_duration' => 10,
    'mohaa_stats_rate_limit' => 100,
    'mohaa_stats_leaderboard_limit' => 25,
    'mohaa_stats_recent_matches_limit' => 10,
    'mohaa_stats_show_heatmaps' => 1,
    'mohaa_stats_show_achievements' => 1,
    'mohaa_stats_show_in_profile' => 1,
    'mohaa_stats_allow_linking' => 1,
    'mohaa_stats_max_identities' => 3,
    'mohaa_stats_claim_expiry' => 10,
    'mohaa_stats_token_expiry' => 10,
];

updateSettings($settings);
