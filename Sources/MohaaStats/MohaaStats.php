<?php
/**
 * MOHAA Stats Core - Main Integration File
 * 
 * This is the core plugin that provides:
 * - API communication layer
 * - Caching infrastructure
 * - Admin configuration
 * - Base hooks for other MOHAA Stats plugins
 *
 * @package MohaaStats
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

// Load the API client class
require_once(__DIR__ . '/MohaaStatsAPI.php');

/**
 * Register actions (URL routes)
 */
function MohaaStats_Actions(array &$actions): void
{
    $actions['mohaastats'] = ['MohaaStats/MohaaStats.php', 'MohaaStats_Main'];
    $actions['mohaaapi'] = ['MohaaStats/MohaaStatsAPI.php', 'MohaaStats_APIProxy'];
}

/**
 * Add admin configuration areas
 */
function MohaaStats_AdminAreas(array &$admin_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $admin_areas['config']['areas']['mohaastats'] = [
        'label' => $txt['mohaa_stats_admin'],
        'file' => 'MohaaStats/MohaaStatsAdmin.php',
        'function' => 'MohaaStats_AdminConfig',
        'icon' => 'posts',
        'subsections' => [
            'general' => [$txt['mohaa_stats_general']],
            'api' => [$txt['mohaa_stats_api_settings']],
            'cache' => [$txt['mohaa_stats_cache']],
            'linking' => [$txt['mohaa_stats_linking']],
        ],
    ];
}

/**
 * Add menu buttons
 */
function MohaaStats_MenuButtons(array &$buttons): void
{
    global $txt, $scripturl, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    
    loadLanguage('MohaaStats');
    
    // Add main Stats menu item
    $buttons['mohaastats'] = [
        'title' => $txt['mohaa_stats'],
        'href' => $scripturl . '?action=mohaadashboard',
        'icon' => 'stats',
        'show' => true,
        'sub_buttons' => [
            'warroom' => [
                'title' => $txt['mohaa_war_room'] ?? 'War Room',
                'href' => $scripturl . '?action=mohaadashboard',
                'show' => true,
                'icon' => 'home',
            ],
            'leaderboard' => [
                'title' => $txt['mohaa_leaderboard'] ?? 'Leaderboards',
                'href' => $scripturl . '?action=mohaastats;sa=leaderboards',
                'show' => true,
                'icon' => 'trophy',
            ],
            'battles' => [
                'title' => $txt['mohaa_battles'] ?? 'Battles',
                'href' => $scripturl . '?action=mohaastats;sa=battles',
                'show' => true,
                'icon' => 'profile',
            ],
            'livematches' => [
                'title' => $txt['mohaa_live_matches'] ?? 'Live Matches',
                'href' => $scripturl . '?action=mohaastats;sa=live',
                'show' => true,
                'icon' => 'online',
            ],
            'servers' => [
                'title' => $txt['mohaa_servers'] ?? 'Servers',
                'href' => $scripturl . '?action=mohaaservers',
                'show' => true,
                'icon' => 'server',
            ],
            'maps' => [
                'title' => $txt['mohaa_maps'] ?? 'Maps',
                'href' => $scripturl . '?action=mohaastats;sa=maps',
                'show' => true,
                'icon' => 'map',
            ],
            'weapons' => [
                'title' => $txt['mohaa_weapons'] ?? 'Weapons',
                'href' => $scripturl . '?action=mohaastats;sa=weapons',
                'show' => true,
                'icon' => 'post',
            ],
            'gametypes' => [
                'title' => $txt['mohaa_gametypes'] ?? 'Game Types',
                'href' => $scripturl . '?action=mohaastats;sa=gametypes',
                'show' => true,
                'icon' => 'features',
            ],
            'achievements' => [
                'title' => $txt['mohaa_achievements'] ?? 'Achievements',
                'href' => $scripturl . '?action=mohaaachievements',
                'show' => true,
                'icon' => 'star',
            ],
            'tournaments' => [
                'title' => $txt['mohaa_tournaments'] ?? 'Tournaments',
                'href' => $scripturl . '?action=mohaatournaments',
                'show' => true,
                'icon' => 'package',
            ],
            'teams' => [
                'title' => $txt['mohaa_teams'] ?? 'Teams',
                'href' => $scripturl . '?action=mohaateams',
                'show' => true,
                'icon' => 'members',
            ],
            'comparison' => [
                'title' => $txt['mohaa_comparison'] ?? 'Player Comparison',
                'href' => $scripturl . '?action=mohaastats;sa=comparison',
                'show' => true,
                'icon' => 'search',
            ],
            'predictions' => [
                'title' => $txt['mohaa_predictions'] ?? 'AI Predictions',
                'href' => $scripturl . '?action=mohaastats;sa=predictions',
                'show' => true,
                'icon' => 'reports',
            ],
            'identity' => [
                'title' => $txt['mohaa_identity'] ?? 'Link Account',
                'href' => $scripturl . '?action=mohaaplayers;sa=link',
                'show' => true,
                'icon' => 'profile',
            ],
        ],
    ];
    
    // Reorder to put it after Home
    $temp = [];
    foreach ($buttons as $key => $button) {
        // Skip mohaastats since we'll add it right after home
        if ($key === 'mohaastats') continue;
        
        $temp[$key] = $button;
        if ($key === 'home') {
            $temp['mohaastats'] = $buttons['mohaastats'];
        }
    }
    $buttons = $temp;
}

/**
 * Load theme resources
 */
function MohaaStats_LoadTheme(): void
{
    global $modSettings, $context, $settings;
    
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    
    // Load CSS
    loadCSSFile('mohaa_stats.css', ['default_theme' => true, 'minimize' => true]);
    loadCSSFile('css/mohaa_dashboard.css', ['default_theme' => true, 'minimize' => true]);
    
    // Load JavaScript
    loadJavaScriptFile('mohaa_stats.js', ['default_theme' => true, 'minimize' => true]);
    
    // Add API base URL for JavaScript
    addInlineJavaScript('
        window.MohaaStats = window.MohaaStats || {};
        window.MohaaStats.apiUrl = ' . JavaScriptEscape($modSettings['mohaa_stats_api_url'] ?? '') . ';
        window.MohaaStats.cacheTime = ' . (int)($modSettings['mohaa_stats_cache_duration'] ?? 60) . ';
    ', true);
}

/**
 * Main stats page dispatcher
 */
function MohaaStats_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    
    // Load appropriate template based on sub-action
    $templateMap = [
        'main' => 'MohaaStats',
        'leaderboards' => 'MohaaStatsLeaderboard',
        'leaderboard' => 'MohaaStatsLeaderboard',
        'battles' => 'MohaaBattlesList',
        'battle' => 'MohaaBattleDetail',
        'weapons' => 'MohaaStatsLeaderboard',
        'weapon' => 'MohaaStatsLeaderboard',
        'player' => 'MohaaStatsPlayer',
        'matches' => 'MohaaStats',
        'match' => 'MohaaStatsMatch',
        'maps' => 'MohaaStatsLeaderboard',
        'map' => 'MohaaStatsLeaderboard',
        'gametypes' => 'MohaaStatsLeaderboard',
        'gametype' => 'MohaaStatsLeaderboard',
        'servers' => 'MohaaServerStats',
        'predictions' => 'MohaaPredictions',
        'comparison' => 'MohaaPlayerComparison',
        'live' => 'MohaaStats',
        'link' => 'MohaaStatsPlayer',
        'token' => 'MohaaStatsPlayer',
        'generate_claim' => 'MohaaStatsPlayer',
        'generate_token' => 'MohaaStatsPlayer',
    ];
    
    $subActions = [
        'main' => 'MohaaStats_MainPage',
        'leaderboards' => 'MohaaStats_Leaderboards',
        'leaderboard' => 'MohaaStats_Leaderboards',
        'battles' => 'MohaaStats_BattlesList',
        'battle' => 'MohaaStats_BattleDetail',
        'weapons' => 'MohaaStats_Weapons',
        'weapon' => 'MohaaStats_WeaponDetail',
        'player' => 'MohaaStats_Player',
        'matches' => 'MohaaStats_Matches',
        'match' => 'MohaaStats_MatchDetail',
        'maps' => 'MohaaStats_MapLeaderboard',
        'map' => 'MohaaStats_MapDetail',
        'gametypes' => 'MohaaStats_GameTypes',
        'gametype' => 'MohaaStats_GameTypeDetail',
        'servers' => 'MohaaStats_ServerDashboard',
        'predictions' => 'MohaaStats_Predictions',
        'comparison' => 'MohaaStats_Comparison',
        'live' => 'MohaaStats_Live',
        'link' => 'MohaaStats_LinkIdentity',
        'token' => 'MohaaStats_GenerateToken',
        'generate_claim' => 'MohaaStats_HandleClaim',
        'generate_token' => 'MohaaStats_HandleToken',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'main';
    
    // Load the right template
    loadTemplate($templateMap[$sa] ?? 'MohaaStats');
    
    call_user_func($subActions[$sa]);
}

/**
 * Dashboard/Main stats page
 */
function MohaaStats_MainPage(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_stats'];
    $context['sub_template'] = 'mohaa_stats_main';
    
    // Fetch dashboard data
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_stats'] = [
        'global' => $api->getGlobalStats(),
        'top_players' => $api->getLeaderboard('kills', 10),
        'recent_matches' => $api->getRecentMatches(5),
        'live_matches' => $api->getLiveMatches(),
    ];
}

function MohaaStats_Leaderboards(): void
{
    global $context, $txt, $scripturl;
    
    $api = new MohaaStatsAPIClient();

    // Default to Dashboard unless a specific stat/page is requested
    if (empty($_GET['stat']) && empty($_GET['start'])) {
        $context['page_title'] = $txt['mohaa_leaderboard_dashboard'] ?? 'Competitive Dashboard';
        $context['sub_template'] = 'mohaa_stats_dashboard';
        $context['mohaa_dashboard_cards'] = $api->getLeaderboardCards();
        return;
    }
    
    $context['page_title'] = $txt['mohaa_leaderboards'] ?? 'Leaderboards';
    $context['sub_template'] = 'mohaa_stats_leaderboard';
    
    $stat = isset($_GET['stat']) ? $_GET['stat'] : 'kills';
    $period = isset($_GET['period']) ? $_GET['period'] : 'all';
    $page = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $limit = 25;
    
    $api = new MohaaStatsAPIClient();
    
    // API returns {"players": [...], "total": N, "page": N}
    $apiResponse = $api->getLeaderboard($stat, $limit, $page, $period);
    
    $context['mohaa_leaderboard'] = [
        'stat' => $stat,
        'period' => $period,
        'players' => $apiResponse['players'] ?? [],
        'total' => $apiResponse['total'] ?? 0,
    ];
    
    // Pagination
    $context['page_index'] = constructPageIndex(
        $scripturl . '?action=mohaastats;sa=leaderboards;stat=' . $stat . ';period=' . $period,
        $page,
        $context['mohaa_leaderboard']['total'],
        $limit
    );
}

/**
 * Player stats page
 */
/**
 * Player stats page
 */
function MohaaStats_Player(): void
{
    global $context, $txt, $scripturl;
    
    $guid = $_GET['guid'] ?? $_GET['id'] ?? '';
    $memberId = (int)($_GET['u'] ?? $_GET['member'] ?? 0);
    $name = $_GET['name'] ?? '';

    $api = new MohaaStatsAPIClient();

    // If we have a name but no GUID, try to resolve it via API
    if (empty($guid) && !empty($name)) {
        $resolved = $api->resolvePlayerByName($name);
        if (!empty($resolved['guid'])) {
            $guid = $resolved['guid'];
        }
    }

    // If we have a member ID but no GUID, try to find the linked identity
    if (empty($guid) && !empty($memberId)) {
        $identities = MohaaStats_GetUserIdentities($memberId);
        if (!empty($identities)) {
            $guid = $identities[0]['player_guid'];
        }
    }
    
    if (empty($guid)) {
        redirectexit('action=mohaastats;sa=leaderboards');
        return;
    }
    
    
    // Parallel Fetching for Speed
    $requests = [
        'info' => ['endpoint' => '/stats/player/' . urlencode($guid)],
        'deep' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/deep'],
        'playstyle' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/playstyle'],
        'weapons' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/weapons'],
        'matches' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/matches', 'params' => ['limit' => 10]],
        'achievements' => ['endpoint' => '/achievements/player/' . urlencode($guid)],
        'peak_performance' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/peak-performance'],
        'combo_metrics' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/combos'],
    ];
    
    $results = $api->getMultiple($requests);
    $player = $results['info'];
    
    if (empty($player)) {
        fatal_lang_error('mohaa_player_not_found', false);
        return;
    }
    
    $context['page_title'] = sprintf($txt['mohaa_player_title'], $player['name']);
    $context['sub_template'] = 'mohaa_war_room'; // Use Unified War Room Template
    
    // Flatten Deep Stats for Template Compatibility
    $deep = $results['deep'] ?? [];
    $flatStats = array_merge(
        $player,
        $deep['combat'] ?? [],
        $deep['movement'] ?? [],
        $deep['accuracy'] ?? [],
        $deep['session'] ?? [],
        $deep['rivals'] ?? [],
        $deep['stance'] ?? []
    );
    
    // Add Structured Data
    $flatStats['weapons'] = $results['weapons'] ?? []; // Template handles array/object
    $flatStats['matches'] = $results['matches']['list'] ?? [];
    $flatStats['recent_matches'] = $results['matches']['list'] ?? [];
    $flatStats['playstyle'] = $results['playstyle'] ?? [];
    $flatStats['achievements'] = $results['achievements'] ?? [];
    
    $context['mohaa_dashboard'] = [
        'player_stats' => $flatStats,
        'peak_performance' => $results['peak_performance'] ?? [],
        'combo_metrics' => $results['combo_metrics'] ?? [],
        'member' => [
            'member_name' => $player['name'],
            'real_name' => $player['name'],
            'id' => 0 // External player
        ],
        'is_own' => MohaaStats_IsLinkedToUser($guid)
    ];
}

/**
 * Match list page
 */
function MohaaStats_Matches(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_matches'];
    $context['sub_template'] = 'mohaa_matches';
    
    $page = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $limit = 20;
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_matches'] = [
        'list' => $api->getRecentMatches($limit, $page),
        'total' => $api->getMatchCount(),
    ];
    
    $context['page_index'] = constructPageIndex(
        $scripturl . '?action=mohaastats;sa=matches',
        $page,
        $context['mohaa_matches']['total'],
        $limit
    );
}

/**
 * Match detail page
 */
function MohaaStats_MatchDetail(): void
{
    global $context, $txt;
    
    $matchId = isset($_GET['id']) ? $_GET['id'] : '';
    
    if (empty($matchId)) {
        redirectexit('action=mohaastats;sa=matches');
        return;
    }
    
    $api = new MohaaStatsAPIClient();
    $match = $api->getMatchReport($matchId);
    
    if (empty($match)) {
        fatal_lang_error('mohaa_match_not_found', false);
        return;
    }
    
    // Fetch visual heatmap data separately
    $match['heatmap_data'] = [
        'kills' => $api->getMatchHeatmap($matchId, 'kills'),
        'deaths' => $api->getMatchHeatmap($matchId, 'deaths')
    ];
    
    $context['page_title'] = sprintf($txt['mohaa_match_title'], $match['info']['map_name'] ?? 'Unknown');
    $context['sub_template'] = 'mohaa_match_detail';
    
    $context['mohaa_match'] = $match;
}

/**
 * Maps page
 */
function MohaaStats_Maps(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_maps'];
    $context['sub_template'] = 'mohaa_maps';
    
    $api = new MohaaStatsAPIClient();
    $context['mohaa_maps'] = $api->getMapStats();
}

/**
 * Map detail with heatmap
 */
function MohaaStats_MapDetail(): void
{
    global $context, $txt;
    
    $mapId = isset($_GET['id']) ? $_GET['id'] : '';
    
    if (empty($mapId)) {
        redirectexit('action=mohaastats;sa=maps');
        return;
    }
    
    $api = new MohaaStatsAPIClient();
    $map = $api->getMapDetails($mapId);
    
    if (empty($map)) {
        fatal_lang_error('mohaa_map_not_found', false);
        return;
    }
    
    $context['page_title'] = $map['name'];
    $context['sub_template'] = 'mohaa_map_detail';
    
    $context['mohaa_map'] = [
        'info' => $map,
        'heatmap_kills' => $api->getMapHeatmap($mapId, 'kills'),
        'heatmap_deaths' => $api->getMapHeatmap($mapId, 'deaths'),
    ];
}

/**
 * Live matches page
 */
function MohaaStats_Live(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_live'];
    $context['sub_template'] = 'mohaa_live';
    
    $api = new MohaaStatsAPIClient();
    $context['mohaa_live_matches'] = $api->getLiveMatches();
}

/**
 * Identity linking page
 */
function MohaaStats_LinkIdentity(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_link_identity'];
    $context['sub_template'] = 'mohaa_link_identity';
    
    // Get existing linked identities
    $context['mohaa_identities'] = MohaaStats_GetUserIdentities($user_info['id']);
    
    // Handle form submission
    if (isset($_POST['action_type'])) {
        checkSession();
        
        if ($_POST['action_type'] === 'generate_token') {
            $api = new MohaaStatsAPIClient();
            $result = $api->initDeviceAuth($user_info['id']);
            $context['mohaa_token'] = $result['user_code'] ?? null;
            $context['mohaa_token_expires'] = $result['expires_in'] ?? 600;
        }
        elseif ($_POST['action_type'] === 'generate_claim') {
            $api = new MohaaStatsAPIClient();
            $result = $api->initClaim($user_info['id']);
            $context['mohaa_claim_code'] = $result['code'] ?? null;
        }
        elseif ($_POST['action_type'] === 'unlink' && !empty($_POST['identity_id'])) {
            MohaaStats_UnlinkIdentity($user_info['id'], (int)$_POST['identity_id']);
            $context['mohaa_identities'] = MohaaStats_GetUserIdentities($user_info['id']);
        }
    }
}

/**
 * Check if a GUID is linked to current user
 */
function MohaaStats_IsLinkedToUser(string $guid): bool
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest'])
        return false;
    
    $request = $smcFunc['db_query']('', '
        SELECT id_identity
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
            AND player_guid = {string:guid}',
        [
            'member' => $user_info['id'],
            'guid' => $guid,
        ]
    );
    
    $found = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);
    
    return $found;
}

/**
 * Get all identities linked to a user
 */
function MohaaStats_GetUserIdentities(int $userId): array
{
    global $smcFunc;
    
    $identities = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT id_identity, player_guid, player_name, linked_date
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
        ORDER BY linked_date DESC',
        [
            'member' => $userId,
        ]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $identities[] = $row;
    }
    
    $smcFunc['db_free_result']($request);
    
    return $identities;
}

/**
 * Unlink an identity from a user
 */
function MohaaStats_UnlinkIdentity(int $userId, int $identityId): void
{
    global $smcFunc;
    
    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}mohaa_identities
        WHERE id_identity = {int:identity}
            AND id_member = {int:member}',
        [
            'identity' => $identityId,
            'member' => $userId,
        ]
    );
}

/**
 * Weapons statistics page
 */
function MohaaStats_Weapons(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_weapons'] ?? 'Weapons';
    $context['sub_template'] = 'mohaa_stats_weapons';
    
    $weapon = isset($_GET['weapon']) ? $_GET['weapon'] : '';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_weapon'] = $weapon;
    
    // Get all weapons simple list
    $context['mohaa_weapons_list'] = $api->getWeaponsList();
    
    // Get global weapon stats for listing/Popularity
    $context['mohaa_global_weapon_stats'] = $api->getGlobalWeaponStats();
    
    if (!empty($weapon)) {
        $context['mohaa_weapon_data'] = $api->getWeaponDetails($weapon);
        $context['mohaa_weapon_leaderboard'] = $api->getWeaponLeaderboard($weapon, 25);
    } else {
        $context['mohaa_weapon_data'] = [];
        $context['mohaa_weapon_leaderboard'] = [];
    }
}

/**
 * Single weapon detail page (Dedicated)
 */
function MohaaStats_WeaponDetail(): void
{
    global $context, $txt;
    
    $weapon = isset($_GET['weapon']) ? $_GET['weapon'] : '';
    
    if (empty($weapon)) {
        redirectexit('action=mohaastats;sa=weapons');
        return;
    }
    
    $context['page_title'] = ($txt['mohaa_weapon'] ?? 'Weapon') . ' - ' . ucfirst($weapon);
    $context['sub_template'] = 'mohaa_stats_weapon_detail';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_weapon'] = $weapon;
    $context['mohaa_weapon_data'] = $api->getWeaponDetails($weapon);
    $context['mohaa_weapon_leaderboard'] = $api->getWeaponLeaderboard($weapon, 25);
}

/**
 * Map leaderboard page
 */
function MohaaStats_MapLeaderboard(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_map_stats'];
    $context['sub_template'] = 'mohaa_stats_map_leaderboard';
    
    $map = isset($_GET['map']) ? $_GET['map'] : '';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_map'] = $map;
    
    // Get full maps stats (with matches/kills) for the overview
    $mapsStats = $api->getMapStats();
    if ($mapsStats !== null) {
        // Enrich with display names
        foreach ($mapsStats as &$m) {
            $m['display_name'] = formatMapDisplayName($m['name'] ?? '');
        }
        $context['mohaa_maps_list'] = $mapsStats;
    } else {
        $context['mohaa_maps_list'] = [];
    }
    
    if (!empty($map)) {
        $context['mohaa_map_data'] = $api->getMapDetails($map);
        $context['mohaa_map_leaderboard'] = $api->getMapLeaderboard($map, 25);
    } else {
        $context['mohaa_map_data'] = [];
        $context['mohaa_map_leaderboard'] = [];
    }
}

/**
 * Game Types statistics page
 */
function MohaaStats_GameTypes(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_gametypes'] ?? 'Game Types';
    $context['sub_template'] = 'mohaa_stats_gametypes';
    
    $gameType = isset($_GET['gametype']) ? $_GET['gametype'] : '';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_gametype'] = $gameType;
    
    // Get all game types with stats
    $gameTypesStats = $api->getGameTypeStats();
    if ($gameTypesStats !== null) {
        $context['mohaa_gametypes_list'] = $gameTypesStats;
    } else {
        $context['mohaa_gametypes_list'] = [];
    }
    
    if (!empty($gameType)) {
        $context['mohaa_gametype_data'] = $api->getGameTypeDetails($gameType);
        $context['mohaa_gametype_leaderboard'] = $api->getGameTypeLeaderboard($gameType, 25);
    } else {
        $context['mohaa_gametype_data'] = [];
        $context['mohaa_gametype_leaderboard'] = [];
    }
}

/**
 * Single game type detail page
 */
function MohaaStats_GameTypeDetail(): void
{
    global $context, $txt;
    
    $gameType = isset($_GET['gametype']) ? $_GET['gametype'] : '';
    
    if (empty($gameType)) {
        redirectexit('action=mohaastats;sa=gametypes');
        return;
    }
    
    $context['page_title'] = ($txt['mohaa_gametype'] ?? 'Game Type') . ' - ' . strtoupper($gameType);
    $context['sub_template'] = 'mohaa_stats_gametype_detail';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_gametype'] = $gameType;
    $context['mohaa_gametype_data'] = $api->getGameTypeDetails($gameType);
    $context['mohaa_gametype_leaderboard'] = $api->getGameTypeLeaderboard($gameType, 25);
}

/**
 * Format map name for display
 */
function formatMapDisplayName(string $name): string
{
    $display = $name;
    $prefixes = ['mp_', 'dm_', 'obj_', 'lib_'];
    foreach ($prefixes as $prefix) {
        if (strpos($display, $prefix) === 0) {
            $display = substr($display, strlen($prefix));
            break;
        }
    }
    return ucfirst($display);
}

/**
 * Generate token page
 */
function MohaaStats_GenerateToken(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_game_token'];
    $context['sub_template'] = 'mohaa_stats_token';
    
    // Check if identity is linked
    $context['mohaa_identity_linked'] = !empty(MohaaStats_GetUserIdentities($user_info['id']));
}

/**
 * Handle claim code generation
 */
function MohaaStats_HandleClaim(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('post');
    
    $api = new MohaaStatsAPIClient();
    $result = $api->initClaim($user_info['id']);
    
    $context['page_title'] = $txt['mohaa_link_identity'];
    $context['sub_template'] = 'mohaa_stats_link_identity';
    $context['mohaa_identity_linked'] = false;
    $context['mohaa_claim_code'] = $result['code'] ?? null;
    $context['mohaa_claim_expires'] = time() + ($result['expires_in'] ?? 600);
}

/**
 * Handle token generation
 */
function MohaaStats_HandleToken(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('post');
    
    $api = new MohaaStatsAPIClient();
    $result = $api->initDeviceAuth($user_info['id']);
    
    $context['page_title'] = $txt['mohaa_game_token'];
    $context['sub_template'] = 'mohaa_stats_token';
    $context['mohaa_identity_linked'] = !empty(MohaaStats_GetUserIdentities($user_info['id']));
    $context['mohaa_token'] = $result['user_code'] ?? null;
    $context['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
}

/**
 * Server Dashboard page
 */
function MohaaStats_ServerDashboard(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_servers'] ?? 'Server Dashboard';
    $context['sub_template'] = 'mohaa_server_stats';
    
    $api = new MohaaStatsAPIClient();
    
    // Fetch parallel
    $requests = [
        'activity' => ['endpoint' => '/stats/global/activity'],
        'maps' => ['endpoint' => '/stats/maps/popularity'],
        'recent_matches' => ['endpoint' => '/stats/matches', 'params' => ['limit' => 10]],
    ];
    
    $results = $api->getMultiple($requests);
    
    $context['mohaa_server_stats'] = [
        'activity' => $results['activity'] ?? [],
        'maps' => $results['maps'] ?? [],
        'recent_matches' => $results['recent_matches'] ?? [],
    ];
}

/**
 * Battles list page
 * TODO: Implement when API endpoints are ready
 */
function MohaaStats_BattlesList(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_battles'] ?? 'Battles & Matches';
    $context['sub_template'] = 'mohaa_battles_list';
    
    // Placeholder until API endpoints are ready
    $context['mohaa_battles'] = [
        'list' => [],
        'total' => 0,
        'map_filter' => '',
        'gametype_filter' => '',
    ];
    
    $context['page_index'] = '';
}

/**
 * Battle detail page
 * TODO: Implement when API endpoints are ready
 */
function MohaaStats_BattleDetail(): void
{
    global $context, $txt;
    
    $battleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $context['page_title'] = $txt['mohaa_battle_title'] ?? 'Battle Details';
    $context['sub_template'] = 'mohaa_battle_detail';
    
    // Placeholder until API endpoints are ready
    $context['battle'] = null;
    $context['rounds'] = [];
    $context['players'] = [];
    $context['current_round'] = null;
    $context['momentum'] = [];
    $context['weapons'] = [];
    $context['heatmap'] = [];
    $context['timeline'] = [];
}

/**
 * AI Predictions page
 */
function MohaaStats_Predictions(): void
{
    global $context, $txt, $user_info, $smcFunc;
    
    require_once(__DIR__ . '/MohaaPlayerPredictor.php');
    require_once(__DIR__ . '/MohaaStatsAPI.php');
    
    $context['page_title'] = $txt['mohaa_predictions'] ?? 'AI Predictions';
    $context['sub_template'] = 'mohaa_predictions';
    
    // Get player ID from URL or current user
    $playerId = isset($_GET['player']) ? (int)$_GET['player'] : $user_info['id'];
    
    if (empty($playerId)) {
        $context['prediction_error'] = 'No player specified';
        return;
    }
    
    // Get player GUID from SMF database
    $request = $smcFunc['db_query']('', '
        SELECT player_guid
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member_id}
        LIMIT 1',
        ['member_id' => $playerId]
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $guid = $row['player_guid'] ?? '';
    $smcFunc['db_free_result']($request);
    
    if (empty($guid)) {
        $context['prediction_error'] = 'This player has not linked their game account yet. Visit <a href="' . $scripturl . '?action=mohaaplayers;sa=link">Link Account</a> to connect.';
        return;
    }
    
    // Generate predictions
    try {
        $predictor = new MohaaPlayerPredictor();
        $predictions = $predictor->predictNextMatch($guid);
        
        if (isset($predictions['error'])) {
            // Player not found means no stats yet - show helpful message
            $context['prediction_error'] = 'No stats data available yet. Play some matches to generate predictions!<br><br>
                <strong>Note:</strong> The stats API needs to be populated with gameplay data. 
                Currently there are 1.4M events in the database, but the player stats aggregation endpoint needs to be implemented in the Go API.';
            return;
        }
        
        // Map prediction data to template variables
        $context['next_match'] = [
            'player_name' => $predictions['player_name'] ?? 'Unknown',
            'kd_prediction' => $predictions['predictions']['kd_ratio'] ?? ['value' => 1.0, 'range' => ['min' => 0.8, 'max' => 1.2], 'confidence' => 50],
            'accuracy_prediction' => $predictions['predictions']['accuracy'] ?? ['value' => 20, 'range' => ['min' => 18, 'max' => 22], 'confidence' => 50],
            'kpm_prediction' => $predictions['predictions']['kills_per_minute'] ?? ['value' => 1.0, 'range' => ['min' => 0.85, 'max' => 1.15], 'confidence' => 50],
            'factors' => $predictions['factors'] ?? [],
            'recommendations' => $predictions['recommendations'] ?? []
        ];
        
        // Performance forecast (7-day trend)
        $context['forecast'] = [
            'dates' => [],
            'predicted_kd' => [],
            'confidence_bands' => []
        ];
        
        // Generate 7-day forecast
        $baseKD = $predictions['predictions']['kd_ratio']['value'] ?? 1.0;
        $trend = $predictions['factors']['recent_trend']['impact'] ?? 0;
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('M j', strtotime("+$i days"));
            $context['forecast']['dates'][] = $date;
            
            // Simple linear trend projection
            $projectedKD = $baseKD + ($trend / 100) * $i * 0.1;
            $context['forecast']['predicted_kd'][] = round($projectedKD, 2);
            $context['forecast']['confidence_bands'][] = [
                'min' => round($projectedKD * 0.85, 2),
                'max' => round($projectedKD * 1.15, 2)
            ];
        }
        
        // Optimal playtime recommendations
        $context['optimal_time'] = [
            'best_hours' => ['20:00', '21:00', '22:00'], // Mock data
            'worst_hours' => ['06:00', '07:00', '08:00'],
            'current_performance_index' => 75,
            'peak_performance_index' => 100
        ];
        
        $context['player_guid'] = $guid;
        $context['player_id'] = $playerId;
        
    } catch (Exception $e) {
        $context['prediction_error'] = 'Error generating predictions: ' . $e->getMessage();
    }
}

/**
 * Player Comparison page
 */
function MohaaStats_Comparison(): void
{
    global $context, $txt, $smcFunc;
    
    require_once(__DIR__ . '/MohaaPlayerComparison.php');
    require_once(__DIR__ . '/MohaaStatsAPI.php');
    
    $context['page_title'] = $txt['mohaa_comparison'] ?? 'Player Comparison';
    $context['sub_template'] = 'mohaa_player_comparison';
    
    // Get player GUIDs from URL
    $player1 = isset($_GET['player1']) ? $_GET['player1'] : '';
    $player2 = isset($_GET['player2']) ? $_GET['player2'] : '';
    
    $context['player1_guid'] = $player1;
    $context['player2_guid'] = $player2;
    
    // If both players provided, do comparison
    if (!empty($player1) && !empty($player2)) {
        try {
            $comparison = new MohaaPlayerComparison();
            $result = $comparison->comparePlayers($player1, $player2);
            
            // Transform data for template
            $p1 = $result['player1'];
            $p2 = $result['player2'];
            
            $context['compared_players'] = [$p1, $p2];
            
            // Build charts data
            $context['comparison_charts'] = [
                'radar' => [
                    'categories' => ['Combat', 'Accuracy', 'Survival', 'Aggression', 'Experience', 'Efficiency'],
                    'series' => [
                        [
                            'name' => $p1['name'] ?? 'Player 1',
                            'data' => [
                                min(100, ($p1['kills'] ?? 0) / 10),
                                $p1['accuracy'] ?? 0,
                                min(100, ($p1['win_rate'] ?? 0)),
                                min(100, ($p1['kd_ratio'] ?? 0) * 20),
                                min(100, (($p1['playtime_minutes'] ?? 0) / 60)),
                                min(100, ($p1['kills_per_minute'] ?? 0) * 10)
                            ]
                        ],
                        [
                            'name' => $p2['name'] ?? 'Player 2',
                            'data' => [
                                min(100, ($p2['kills'] ?? 0) / 10),
                                $p2['accuracy'] ?? 0,
                                min(100, ($p2['win_rate'] ?? 0)),
                                min(100, ($p2['kd_ratio'] ?? 0) * 20),
                                min(100, (($p2['playtime_minutes'] ?? 0) / 60)),
                                min(100, ($p2['kills_per_minute'] ?? 0) * 10)
                            ]
                        ]
                    ]
                ],
                'bars' => [
                    'categories' => [$p1['name'] ?? 'Player 1', $p2['name'] ?? 'Player 2'],
                    'series' => [
                        ['name' => 'Kills', 'data' => [$p1['kills'] ?? 0, $p2['kills'] ?? 0]],
                        ['name' => 'Deaths', 'data' => [$p1['deaths'] ?? 0, $p2['deaths'] ?? 0]],
                        ['name' => 'K/D', 'data' => [($p1['kd_ratio'] ?? 0) * 100, ($p2['kd_ratio'] ?? 0) * 100]],
                        ['name' => 'Headshots', 'data' => [$p1['headshots'] ?? 0, $p2['headshots'] ?? 0]],
                        ['name' => 'Accuracy', 'data' => [$p1['accuracy'] ?? 0, $p2['accuracy'] ?? 0]],
                        ['name' => 'Win Rate', 'data' => [$p1['win_rate'] ?? 0, $p2['win_rate'] ?? 0]]
                    ]
                ]
            ];
            
            // Winner analysis
            $p1Score = 0;
            $p2Score = 0;
            $metrics = ['kills', 'kd_ratio', 'accuracy', 'headshots', 'win_rate'];
            foreach ($metrics as $metric) {
                $v1 = $p1[$metric] ?? 0;
                $v2 = $p2[$metric] ?? 0;
                if ($v1 > $v2) $p1Score++;
                else if ($v2 > $v1) $p2Score++;
            }
            
            $totalScore1 = round(($p1['kills'] ?? 0) / 100 + ($p1['kd_ratio'] ?? 0) * 10 + ($p1['accuracy'] ?? 0) / 2 + ($p1['win_rate'] ?? 0) / 2, 2);
            $totalScore2 = round(($p2['kills'] ?? 0) / 100 + ($p2['kd_ratio'] ?? 0) * 10 + ($p2['accuracy'] ?? 0) / 2 + ($p2['win_rate'] ?? 0) / 2, 2);
            
            $context['winner_analysis'] = [
                'winner' => [
                    'player' => $totalScore1 > $totalScore2 ? ($p1['name'] ?? 'Player 1') : ($p2['name'] ?? 'Player 2'),
                    'total_score' => max($totalScore1, $totalScore2)
                ],
                'rankings' => [
                    [
                        'player' => $p1['name'] ?? 'Player 1',
                        'total_score' => $totalScore1,
                        'guid' => $player1
                    ],
                    [
                        'player' => $p2['name'] ?? 'Player 2',
                        'total_score' => $totalScore2,
                        'guid' => $player2
                    ]
                ]
            ];
            
            // Sort rankings
            usort($context['winner_analysis']['rankings'], function($a, $b) {
                return $b['total_score'] <=> $a['total_score'];
            });
            
            // Differential stats (comparing against player 1 as baseline)
            $context['differential_stats'] = [
                $p2['name'] ?? 'Player 2' => [
                    'kills' => [
                        'base' => $p1['kills'] ?? 0,
                        'compare' => $p2['kills'] ?? 0,
                        'absolute_diff' => ($p2['kills'] ?? 0) - ($p1['kills'] ?? 0),
                        'percent_diff' => ($p1['kills'] ?? 0) > 0 ? ((($p2['kills'] ?? 0) - ($p1['kills'] ?? 0)) / ($p1['kills'] ?? 0)) * 100 : 0,
                        'better' => ($p2['kills'] ?? 0) > ($p1['kills'] ?? 0)
                    ],
                    'deaths' => [
                        'base' => $p1['deaths'] ?? 0,
                        'compare' => $p2['deaths'] ?? 0,
                        'absolute_diff' => ($p2['deaths'] ?? 0) - ($p1['deaths'] ?? 0),
                        'percent_diff' => ($p1['deaths'] ?? 0) > 0 ? ((($p2['deaths'] ?? 0) - ($p1['deaths'] ?? 0)) / ($p1['deaths'] ?? 0)) * 100 : 0,
                        'better' => ($p2['deaths'] ?? 0) < ($p1['deaths'] ?? 0)
                    ],
                    'kd_ratio' => [
                        'base' => $p1['kd_ratio'] ?? 0,
                        'compare' => $p2['kd_ratio'] ?? 0,
                        'absolute_diff' => ($p2['kd_ratio'] ?? 0) - ($p1['kd_ratio'] ?? 0),
                        'percent_diff' => ($p1['kd_ratio'] ?? 0) > 0 ? ((($p2['kd_ratio'] ?? 0) - ($p1['kd_ratio'] ?? 0)) / ($p1['kd_ratio'] ?? 0)) * 100 : 0,
                        'better' => ($p2['kd_ratio'] ?? 0) > ($p1['kd_ratio'] ?? 0)
                    ],
                    'accuracy' => [
                        'base' => $p1['accuracy'] ?? 0,
                        'compare' => $p2['accuracy'] ?? 0,
                        'absolute_diff' => ($p2['accuracy'] ?? 0) - ($p1['accuracy'] ?? 0),
                        'percent_diff' => ($p1['accuracy'] ?? 0) > 0 ? ((($p2['accuracy'] ?? 0) - ($p1['accuracy'] ?? 0)) / ($p1['accuracy'] ?? 0)) * 100 : 0,
                        'better' => ($p2['accuracy'] ?? 0) > ($p1['accuracy'] ?? 0)
                    ],
                    'headshots' => [
                        'base' => $p1['headshots'] ?? 0,
                        'compare' => $p2['headshots'] ?? 0,
                        'absolute_diff' => ($p2['headshots'] ?? 0) - ($p1['headshots'] ?? 0),
                        'percent_diff' => ($p1['headshots'] ?? 0) > 0 ? ((($p2['headshots'] ?? 0) - ($p1['headshots'] ?? 0)) / ($p1['headshots'] ?? 0)) * 100 : 0,
                        'better' => ($p2['headshots'] ?? 0) > ($p1['headshots'] ?? 0)
                    ],
                    'win_rate' => [
                        'base' => $p1['win_rate'] ?? 0,
                        'compare' => $p2['win_rate'] ?? 0,
                        'absolute_diff' => ($p2['win_rate'] ?? 0) - ($p1['win_rate'] ?? 0),
                        'percent_diff' => ($p1['win_rate'] ?? 0) > 0 ? ((($p2['win_rate'] ?? 0) - ($p1['win_rate'] ?? 0)) / ($p1['win_rate'] ?? 0)) * 100 : 0,
                        'better' => ($p2['win_rate'] ?? 0) > ($p1['win_rate'] ?? 0)
                    ]
                ]
            ];
            
            $context['comparison_error'] = null;
            
        } catch (Exception $e) {
            $context['compared_players'] = [];
            $context['comparison_error'] = $e->getMessage();
        }
    } else {
        // Show player selection form
        $context['compared_players'] = [];
        $context['comparison_error'] = 'Please select two players to compare.';
    }
    
    // Get list of linked players from SMF
    $context['available_players'] = [];
    $request = $smcFunc['db_query']('', '
        SELECT mi.player_guid, m.member_name, m.id_member
        FROM {db_prefix}mohaa_identities AS mi
        LEFT JOIN {db_prefix}members AS m ON mi.id_member = m.id_member
        ORDER BY m.member_name ASC
        LIMIT 100',
        []
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $context['available_players'][] = [
            'guid' => $row['player_guid'],
            'name' => $row['member_name'],
            'member_id' => $row['id_member'],
        ];
    }
    $smcFunc['db_free_result']($request);
}
