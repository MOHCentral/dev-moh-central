<?php
/**
 * MOHAA Stats Admin Configuration
 *
 * @package MohaaStats
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Main admin configuration handler
 */
function MohaaStats_AdminConfig(): void
{
    global $context, $txt, $scripturl;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaStatsAdmin');
    
    $subActions = [
        'general' => 'MohaaStats_AdminGeneral',
        'api' => 'MohaaStats_AdminAPI',
        'cache' => 'MohaaStats_AdminCache',
        'linking' => 'MohaaStats_AdminLinking',
    ];
    
    $context['sub_action'] = isset($_GET['area']) ? $_GET['area'] : 'general';
    if (!isset($subActions[$context['sub_action']]))
        $context['sub_action'] = 'general';
    
    // Tabs
    $context[$context['admin_menu_name']]['tab_data'] = [
        'title' => $txt['mohaa_stats_admin'],
        'description' => $txt['mohaa_stats_admin_desc'],
        'tabs' => [
            'general' => [],
            'api' => [],
            'cache' => [],
            'linking' => [],
        ],
    ];
    
    call_user_func($subActions[$context['sub_action']]);
}

/**
 * General settings
 */
function MohaaStats_AdminGeneral(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_stats_general'];
    $context['sub_template'] = 'show_settings';
    
    $config_vars = [
        ['check', 'mohaa_stats_enabled'],
        ['text', 'mohaa_stats_menu_title', 'size' => 40],
        ['check', 'mohaa_stats_show_in_profile'],
        ['check', 'mohaa_stats_allow_linking'],
        '',
        ['title', 'mohaa_stats_display_options'],
        ['int', 'mohaa_stats_leaderboard_limit', 'size' => 5],
        ['int', 'mohaa_stats_recent_matches_limit', 'size' => 5],
        ['check', 'mohaa_stats_show_heatmaps'],
        ['check', 'mohaa_stats_show_achievements'],
    ];
    
    if (isset($_GET['save'])) {
        checkSession();
        saveDBSettings($config_vars);
        redirectexit('action=admin;area=mohaastats;sa=general');
    }
    
    prepareDBSettingContext($config_vars);
    
    $context['post_url'] = $scripturl . '?action=admin;area=mohaastats;sa=general;save';
}

/**
 * API connection settings
 */
function MohaaStats_AdminAPI(): void
{
    global $context, $txt, $scripturl, $modSettings;
    
    $context['page_title'] = $txt['mohaa_stats_api_settings'];
    $context['sub_template'] = 'mohaa_admin_api';
    
    $config_vars = [
        ['text', 'mohaa_stats_api_url', 'size' => 60],
        ['password', 'mohaa_stats_server_token', 'size' => 60],
        ['int', 'mohaa_stats_api_timeout', 'size' => 5, 'postinput' => $txt['mohaa_seconds']],
        '',
        ['title', 'mohaa_stats_rate_limiting'],
        ['int', 'mohaa_stats_rate_limit', 'size' => 5, 'postinput' => $txt['mohaa_per_minute']],
    ];
    
    if (isset($_GET['save'])) {
        checkSession();
        saveDBSettings($config_vars);
        redirectexit('action=admin;area=mohaastats;sa=api');
    }
    
    // Test connection button result
    if (isset($_GET['test'])) {
        checkSession('get');
        
        $api = new MohaaStatsAPIClient();
        $result = $api->getGlobalStats();
        
        $context['mohaa_api_test'] = $result !== null;
    }
    
    prepareDBSettingContext($config_vars);
    
    $context['post_url'] = $scripturl . '?action=admin;area=mohaastats;sa=api;save';
    $context['test_url'] = $scripturl . '?action=admin;area=mohaastats;sa=api;test;' . $context['session_var'] . '=' . $context['session_id'];
}

/**
 * Cache settings
 */
function MohaaStats_AdminCache(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_stats_cache'];
    $context['sub_template'] = 'mohaa_admin_cache';
    
    $config_vars = [
        ['int', 'mohaa_stats_cache_duration', 'size' => 5, 'postinput' => $txt['mohaa_seconds']],
        ['int', 'mohaa_stats_live_cache_duration', 'size' => 5, 'postinput' => $txt['mohaa_seconds']],
        ['check', 'mohaa_stats_cache_leaderboards'],
        ['check', 'mohaa_stats_cache_player_stats'],
    ];
    
    if (isset($_GET['save'])) {
        checkSession();
        saveDBSettings($config_vars);
        redirectexit('action=admin;area=mohaastats;sa=cache');
    }
    
    // Clear cache button
    if (isset($_GET['clear'])) {
        checkSession('get');
        
        $api = new MohaaStatsAPIClient();
        $api->clearCache();
        
        $context['mohaa_cache_cleared'] = true;
    }
    
    prepareDBSettingContext($config_vars);
    
    $context['post_url'] = $scripturl . '?action=admin;area=mohaastats;sa=cache;save';
    $context['clear_url'] = $scripturl . '?action=admin;area=mohaastats;sa=cache;clear;' . $context['session_var'] . '=' . $context['session_id'];
}

/**
 * Identity linking settings
 */
function MohaaStats_AdminLinking(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = $txt['mohaa_stats_linking'];
    $context['sub_template'] = 'mohaa_admin_linking';
    
    $config_vars = [
        ['check', 'mohaa_stats_allow_linking'],
        ['int', 'mohaa_stats_max_identities', 'size' => 3],
        ['int', 'mohaa_stats_claim_expiry', 'size' => 5, 'postinput' => $txt['mohaa_minutes']],
        ['int', 'mohaa_stats_token_expiry', 'size' => 5, 'postinput' => $txt['mohaa_minutes']],
    ];
    
    if (isset($_GET['save'])) {
        checkSession();
        saveDBSettings($config_vars);
        redirectexit('action=admin;area=mohaastats;sa=linking');
    }
    
    prepareDBSettingContext($config_vars);
    
    // Get linked identities statistics
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(DISTINCT id_member) AS members, COUNT(*) AS identities
        FROM {db_prefix}mohaa_identities',
        []
    );
    $context['mohaa_linking_stats'] = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    $context['post_url'] = $scripturl . '?action=admin;area=mohaastats;sa=linking;save';
}
