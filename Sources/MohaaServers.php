<?php
/**
 * MOHAA Server Stats Plugin - Enhanced Dashboard
 * 
 * Server browser, live server status, server statistics with widgets
 * GameTracker-style features + advanced analytics
 *
 * @package MohaaServers
 * @version 2.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaServers_Actions(array &$actions): void
{
    $actions['mohaaservers'] = ['MohaaServers.php', 'MohaaServers_Main'];
}

/**
 * Main server stats dispatcher
 */
function MohaaServers_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'] ?? 'Stats system disabled', false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaServers');
    
    $subActions = [
        'list' => 'MohaaServers_List',
        'live' => 'MohaaServers_Live',
        'server' => 'MohaaServers_Detail',
        'history' => 'MohaaServers_History',
        'rankings' => 'MohaaServers_Rankings',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    call_user_func($subActions[$sa]);
}

/**
 * Server list page - all registered servers with live status
 * Enhanced with widgets and rankings
 */
function MohaaServers_List(): void
{
    global $context, $txt, $scripturl, $sourcedir;
    
    $context['page_title'] = $txt['mohaa_servers'] ?? 'MOHAA Servers';
    $context['sub_template'] = 'mohaa_servers_list';
    
    require_once($sourcedir . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Fetch all data in parallel for dashboard
    $data = $api->getMultiple([
        'servers' => ['endpoint' => '/servers'],
        'stats' => ['endpoint' => '/servers/stats'],
        'rankings' => ['endpoint' => '/servers/rankings', 'params' => ['limit' => 10]],
    ]);
    
    $context['mohaa_servers'] = $data['servers'] ?? [];
    $context['mohaa_server_stats'] = $data['stats'] ?? [
        'total_servers' => 0,
        'online_servers' => 0,
        'total_players_now' => 0,
        'total_kills_today' => 0,
        'total_matches_today' => 0,
        'peak_players_today' => 0,
    ];
    $context['mohaa_rankings'] = $data['rankings'] ?? [];
    
    // Separate online and offline for display
    $context['mohaa_online_servers'] = array_filter($context['mohaa_servers'], fn($s) => $s['is_online'] ?? false);
    $context['mohaa_offline_servers'] = array_filter($context['mohaa_servers'], fn($s) => !($s['is_online'] ?? false));
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'] ?? 'Servers',
    ];
}

/**
 * Live servers page - currently active with real-time data
 */
function MohaaServers_Live(): void
{
    global $context, $txt, $scripturl, $sourcedir;
    
    $context['page_title'] = $txt['mohaa_live_servers'] ?? 'Live Servers';
    $context['sub_template'] = 'mohaa_servers_live';
    
    require_once($sourcedir . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_live'] = $api->getLiveServers() ?? [];
    
    // Get live status for each server
    foreach ($context['mohaa_live'] as &$server) {
        $liveData = $api->getServerLiveStatus($server['id'] ?? '');
        if ($liveData) {
            $server = array_merge($server, $liveData);
        }
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'] ?? 'Servers',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers;sa=live',
        'name' => $txt['mohaa_live_servers'] ?? 'Live Servers',
    ];
}

/**
 * Server detail page - comprehensive dashboard
 */
function MohaaServers_Detail(): void
{
    global $context, $txt, $scripturl, $sourcedir;
    
    $serverId = isset($_GET['id']) ? $_GET['id'] : '';
    
    if (empty($serverId)) {
        redirectexit('action=mohaaservers');
        return;
    }
    
    require_once($sourcedir . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Use batch fetch for all dashboard data
    $data = $api->getServerDashboardData($serverId);
    
    $server = $data['detail'];
    
    if (empty($server)) {
        fatal_lang_error('mohaa_server_not_found', false);
        return;
    }
    
    $context['page_title'] = $server['name'] ?? $server['display_name'] ?? 'Server';
    $context['sub_template'] = 'mohaa_server_detail';
    
    $context['mohaa_server'] = [
        'info' => $server,
        'top_players' => $data['top_players'] ?? [],
        'maps' => $data['maps'] ?? [],
        'weapons' => $data['weapons'] ?? [],
        'peak_hours' => $data['peak_hours'] ?? [],
        'player_history' => $data['player_history'] ?? [],
        'recent_matches' => $data['matches'] ?? [],
    ];
    
    // Additional live status
    $context['mohaa_server']['live'] = $api->getServerLiveStatus($serverId);
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'] ?? 'Servers',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers;sa=server;id=' . urlencode($serverId),
        'name' => $server['name'] ?? 'Server',
    ];
}

/**
 * Server history page - past activity with charts
 */
function MohaaServers_History(): void
{
    global $context, $txt, $scripturl, $sourcedir;
    
    $serverId = isset($_GET['id']) ? $_GET['id'] : '';
    $period = isset($_GET['period']) ? $_GET['period'] : 'week';
    
    $context['page_title'] = $txt['mohaa_server_history'] ?? 'Server History';
    $context['sub_template'] = 'mohaa_server_history';
    
    require_once($sourcedir . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $days = match($period) {
        'day' => 1,
        'week' => 7,
        'month' => 30,
        'year' => 365,
        default => 7,
    };
    
    if (!empty($serverId)) {
        $context['mohaa_server'] = $api->getServerDetails($serverId);
        $context['mohaa_history'] = [
            'period' => $period,
            'timeline' => $api->getServerActivityTimeline($serverId, $days),
            'player_history' => $api->getServerPlayerHistory($serverId, $days * 24),
            'peak_hours' => $api->getServerPeakHours($serverId, $days),
        ];
    } else {
        $context['mohaa_history'] = [
            'period' => $period,
            'servers' => $api->getAllServersHistory($days),
        ];
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'] ?? 'Servers',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers;sa=history',
        'name' => $txt['mohaa_server_history'] ?? 'History',
    ];
}

/**
 * Server rankings page - ranked by activity
 */
function MohaaServers_Rankings(): void
{
    global $context, $txt, $scripturl, $sourcedir;
    
    $context['page_title'] = $txt['mohaa_server_rankings'] ?? 'Server Rankings';
    $context['sub_template'] = 'mohaa_server_rankings';
    
    require_once($sourcedir . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_rankings'] = $api->getServerRankings(100) ?? [];
    $context['mohaa_server_stats'] = $api->getServerGlobalStats();
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'] ?? 'Servers',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers;sa=rankings',
        'name' => $txt['mohaa_server_rankings'] ?? 'Rankings',
    ];
}
