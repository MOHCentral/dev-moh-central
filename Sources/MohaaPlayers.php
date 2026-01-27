<?php
/**
 * MOHAA Player Stats Plugin
 * 
 * Provides player statistics pages and profile integration.
 * Uses SMF member ID for identity linking.
 *
 * @package MohaaPlayers
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaPlayers_Actions(array &$actions): void
{
    $actions['mohaaplayer'] = ['MohaaPlayers.php', 'MohaaPlayers_ViewPlayer'];
    $actions['mohaaplayers'] = ['MohaaPlayers.php', 'MohaaPlayers_Main']; // Main entry with sub-actions
    $actions['mohaadashboard'] = ['MohaaPlayers.php', 'MohaaPlayers_Dashboard'];
    $actions['mohaawarroom'] = ['MohaaPlayers.php', 'MohaaPlayers_Dashboard']; // Alias for war room
    $actions['mohaacompare'] = ['MohaaPlayers.php', 'MohaaPlayers_Compare'];
    $actions['mohaaidentity'] = ['MohaaPlayers.php', 'MohaaPlayers_IdentityRedirect'];
    $actions['mohaalazyload'] = ['MohaaPlayers.php', 'MohaaPlayers_LazyLoadTab'];
    $actions['mohaadrilldown'] = ['MohaaPlayers.php', 'MohaaPlayers_DrillDown'];
}

/**
 * Main entry point for ?action=mohaaplayers with sub-actions
 */
function MohaaPlayers_Main(): void
{
    global $user_info;
    
    $sa = isset($_GET['sa']) ? $_GET['sa'] : '';
    
    switch ($sa) {
        case 'link':
            // Redirect to profile identity linking page
            if ($user_info['is_guest']) {
                redirectexit('action=login');
                return;
            }
            redirectexit('action=profile;area=mohaaidentity');
            break;
            
        case 'view':
            MohaaPlayers_ViewPlayer();
            break;
            
        case 'compare':
            MohaaPlayers_Compare();
            break;
            
        default:
            // Default: show dashboard
            MohaaPlayers_Dashboard();
            break;
    }
}

/**
 * AJAX endpoint for lazy loading tab data (Peak Performance, Signature, etc.)
 * Returns JSON data for the requested tab
 */
function MohaaPlayers_LazyLoadTab(): void
{
    global $user_info;
    
    // Must be logged in
    if ($user_info['is_guest']) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not_logged_in']);
        exit;
    }
    
    $tab = isset($_GET['tab']) ? $_GET['tab'] : '';
    $myGuid = MohaaPlayers_GetLinkedGUID($user_info['id']);
    
    if (empty($myGuid)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'no_linked_identity']);
        exit;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $result = [];
    
    switch ($tab) {
        case 'peak':
            // Fetch peak performance data
            $result = $api->get('/stats/player/' . urlencode($myGuid) . '/peak-performance');
            break;
            
        case 'signature':
            // Fetch combo metrics / signature data
            $result = $api->get('/stats/player/' . urlencode($myGuid) . '/combos');
            break;
            
        case 'drilldown':
            // Fetch drilldown data for specific stat
            $stat = isset($_GET['stat']) ? $_GET['stat'] : 'kd';
            $result = $api->get('/stats/player/' . urlencode($myGuid) . '/drilldown', ['stat' => $stat]);
            break;
            
        default:
            $result = ['error' => 'unknown_tab'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result ?? [], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * AJAX endpoint for drill-down stat analysis
 * Returns JSON breakdown of a stat by a specific dimension
 */
function MohaaPlayers_DrillDown(): void
{
    global $user_info;
    
    // Allow guest access for public profiles (use provided GUID)
    $stat = isset($_GET['stat']) ? trim($_GET['stat']) : '';
    $dimension = isset($_GET['dimension']) ? trim($_GET['dimension']) : '';
    $guid = isset($_GET['guid']) ? trim($_GET['guid']) : '';
    
    // If no GUID provided and user is logged in, use their linked GUID
    if (empty($guid) && !$user_info['is_guest']) {
        $guid = MohaaPlayers_GetLinkedGUID($user_info['id']);
    }
    
    if (empty($guid) || empty($stat) || empty($dimension)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'missing_parameters', 'breakdown' => []]);
        exit;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Call the drilldown API endpoint
    $result = $api->getPlayerDrilldown($guid, $stat, $dimension);
    
    // Normalize response
    if (!isset($result['breakdown']) || !is_array($result['breakdown'])) {
        $result = ['breakdown' => []];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Add profile areas for game stats
 */
function MohaaPlayers_ProfileAreas(array &$profile_areas): void
{
    global $txt, $modSettings;
    
    /*
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    */
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaastats'] = [
        'label' => $txt['mohaa_game_stats'],
        'file' => 'MohaaPlayers.php',
        'function' => 'MohaaPlayers_ProfileStats',
        'icon' => 'stats',
        'permission' => [
            'own' => 'profile_view_own',
            'any' => 'profile_view_any',
        ],
    ];
    
    $profile_areas['info']['areas']['mohaaidentity'] = [
        'label' => $txt['mohaa_link_identity'],
        'file' => 'MohaaPlayers.php',
        'function' => 'MohaaPlayers_ProfileIdentity',
        'icon' => 'members',
        'permission' => [
            'own' => 'profile_view_own',
            'any' => [],
        ],
    ];
}

/**
 * View a player's game stats page
 */
function MohaaPlayers_ViewPlayer(): void
{
    global $context, $txt, $scripturl;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayers');
    
    // Get player by GUID or by SMF member ID
    $guid = isset($_GET['guid']) ? $_GET['guid'] : '';
    $memberId = isset($_GET['member']) ? (int)$_GET['member'] : 0;
    
    if (empty($guid) && empty($memberId)) {
        fatal_lang_error('mohaa_player_not_specified', false);
        return;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // If member ID provided, get linked GUID
    if (!empty($memberId) && empty($guid)) {
        $guid = MohaaPlayers_GetLinkedGUID($memberId);
        if (empty($guid)) {
            fatal_lang_error('mohaa_no_linked_identity', false);
            return;
        }
    }
    
    // Fetch player data
    $playerResponse = $api->getPlayerStats($guid);
    
    // Unwrap the 'player' key from API response
    $player = $playerResponse['player'] ?? [];
    
    if (empty($player)) {
        fatal_lang_error('mohaa_player_not_found', false);
        return;
    }
    
    // Get linked SMF member if any
    $linkedMember = MohaaPlayers_GetLinkedMember($guid);
    
    $context['page_title'] = sprintf($txt['mohaa_player_title'], $player['name']);
    $context['sub_template'] = 'mohaa_player_full';
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaastats',
        'name' => $txt['mohaa_stats'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaplayer;guid=' . urlencode($guid),
        'name' => $player['name'],
    ];
    
    // Fetch deep stats for the profile page
    $deepStats = $api->getPlayerDeepStats($guid);
    
    $context['mohaa_player'] = [
        'guid' => $guid,
        'name' => $player['name'],
        'stats' => $player,
        'deep_stats' => $deepStats,
        'linked_member' => $linkedMember,
        'weapons' => $api->getPlayerWeapons($guid),
        'matches' => $api->getPlayerMatches($guid, 20),
        'achievements' => $api->getPlayerAchievements($guid),
        'performance' => $api->getPlayerPerformance($guid, 30), // Last 30 days
        'maps' => $api->getPlayerMapStats($guid),
    ];
}

/**
 * Player dashboard - shows ALL stats and THEIR stats
 */
function MohaaPlayers_Dashboard(): void
{
    global $context, $txt, $user_info, $scripturl;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaWarRoom');
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['page_title'] = $txt['mohaa_war_room'] ?? 'War Room';
    $context['sub_template'] = 'mohaa_war_room';
    
    // Get linked identity for current user
    $myGuid = MohaaPlayers_GetLinkedGUID($user_info['id']);
    
    // Build batch of requests for parallel fetching
    $requests = [
        'stats' => ['endpoint' => '/stats/global'],
        'leaderboard' => ['endpoint' => '/stats/leaderboard/global', 'params' => ['stat' => 'kills', 'limit' => 10]],
        'recent_matches' => ['endpoint' => '/stats/matches', 'params' => ['limit' => 10]],
    ];
    
    // Fetch all global data in parallel (3 requests in ~1 network round-trip)
    $results = $api->getMultiple($requests);
    
    // Global stats - live matches loaded async via JS for freshness
    $context['mohaa_global'] = [
        'stats' => $results['stats'],
        'leaderboard' => $results['leaderboard'],
        'live_matches' => null,
        'recent_matches' => $results['recent_matches'],
        'top_weapons' => [],
        'active_players' => [],
    ];
    
    // My stats (only if linked)
    if (!empty($myGuid)) {
        $playerRequests = [
            'player' => ['endpoint' => '/stats/player/' . urlencode($myGuid)],
            'weapons' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/weapons'],
            'matches' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/matches', 'params' => ['limit' => 10]],
            'achievements' => ['endpoint' => '/achievements/player/' . urlencode($myGuid)],
            'maps' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/maps'],
            'performance' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/performance'],
            'deep' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/deep'],
            // Advanced Stats - Peak Performance, Combos, Vehicles, Game Flow, World, Bots
            'peak' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/peak-performance'],
            'combos' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/combos'],
            'vehicles' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/vehicles'],
            'gameflow' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/game-flow'],
            'world' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/world'],
            'bots' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/bots'],

            'gametypes' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/gametypes'],
            'maps' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/maps'],
        ];
        
        $playerResults = $api->getMultiple($playerRequests);
        
        $context['mohaa_my'] = [
            'guid' => $myGuid,
            'player' => $playerResults['player'],
            'rank' => null,
            'weapons' => $playerResults['weapons'],
            'recent_matches' => $playerResults['matches'],
            'achievements' => $playerResults['achievements'],
            'gametypes' => $playerResults['gametypes'] ?? [],
            'maps' => $playerResults['maps'] ?? [],
            'performance' => [],
            'comparisons' => [],
        ];
        
        $context['mohaa_has_identity'] = true;
        
        // Build the War Room dashboard format
        // Unwrap the 'player' key from API response
        $playerData = $playerResults['player'] ?? [];
        $playerStats = $playerData['player'] ?? [];
        
        // Map API response keys to template-expected keys
        if (!empty($playerStats)) {
            // Transform total_* keys to match template expectations
            $playerStats['kills'] = $playerStats['total_kills'] ?? 0;
            $playerStats['deaths'] = $playerStats['total_deaths'] ?? 0;
            $playerStats['damage'] = $playerStats['total_damage'] ?? 0;
            $playerStats['headshots'] = $playerStats['total_headshots'] ?? 0;
            // matches_played and matches_won already match
        }
        
        $playerStats['weapons'] = $playerResults['weapons'] ?? [];
        $playerStats['maps'] = $playerResults['maps'] ?? [];
        $playerStats['recent_matches'] = $playerResults['matches'] ?? [];
        $playerStats['performance'] = $playerResults['performance'] ?? [];
        
        // Merge deep stats (flattened)
        $deep = $playerResults['deep'] ?? [];
        if (!empty($deep)) {
            if (isset($deep['combat'])) $playerStats = array_merge($playerStats, $deep['combat']);
            if (isset($deep['movement'])) $playerStats = array_merge($playerStats, $deep['movement']);
            if (isset($deep['accuracy'])) $playerStats = array_merge($playerStats, $deep['accuracy']);
            if (isset($deep['session'])) $playerStats = array_merge($playerStats, $deep['session']);
            if (isset($deep['rivals'])) $playerStats = array_merge($playerStats, $deep['rivals']);
            if (isset($deep['stance'])) $playerStats = array_merge($playerStats, $deep['stance']);
            if (isset($deep['interaction'])) $playerStats = array_merge($playerStats, $deep['interaction']);

            // Note: 'weapons' is handled separately
        }
        
        // Calculate FFA/Team Wins from Gametypes
        $gametypes = $playerResults['gametypes'] ?? [];
        $ffaWins = 0;
        $teamWins = 0;
        foreach ($gametypes as $gt) {
            if (isset($gt['gametype']) && ($gt['gametype'] == 'dm' || $gt['gametype'] == 'ffa')) {
                $ffaWins += ($gt['matches_won'] ?? 0);
            } else {
                $teamWins += ($gt['matches_won'] ?? 0);
            }
        }
        $playerStats['ffa_wins'] = $ffaWins;
        $playerStats['team_wins'] = $teamWins;
        
        $context['mohaa_dashboard'] = [
            'player_stats' => $playerStats,
            'member' => [
                'id_member' => $user_info['id'],
                'member_name' => $user_info['username'],
                'real_name' => $user_info['name'],
            ],
            'global' => $results['stats'] ?? [],
            // Expose server averages at top level for template usage
            'server_avg_accuracy' => $results['stats']['server_avg_accuracy'] ?? 0,
            'server_avg_kd' => $results['stats']['server_avg_kd'] ?? 0,
            // Advanced Stats - available for template use
            'peak_performance' => $playerResults['peak'] ?? [],
            'combo_metrics' => $playerResults['combos'] ?? [],
            'vehicle_stats' => $playerResults['vehicles'] ?? [],
            'game_flow' => $playerResults['gameflow'] ?? [],
            'world_stats' => $playerResults['world'] ?? [],
            'world_stats' => $playerResults['world'] ?? [],
            'bot_stats' => $playerResults['bots'] ?? [],
            'gametype_stats' => $playerResults['gametypes'] ?? [],
        ];
    } else {
        $context['mohaa_has_identity'] = false;
        
        // Provide empty dashboard for users without linked identity
        $context['mohaa_dashboard'] = [
            'player_stats' => [],
            'member' => [
                'id_member' => $user_info['id'],
                'member_name' => $user_info['username'],
                'real_name' => $user_info['name'],
            ],
            'global' => $results['stats'] ?? [],
        ];
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaadashboard',
        'name' => $txt['mohaa_war_room'] ?? 'War Room',
    ];
}

/**
 * Compare two players
 */
function MohaaPlayers_Compare(): void
{
    global $context, $txt, $scripturl;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayers');
    
    $guid1 = isset($_GET['p1']) ? $_GET['p1'] : '';
    $guid2 = isset($_GET['p2']) ? $_GET['p2'] : '';
    
    if (empty($guid1) || empty($guid2)) {
        $context['sub_template'] = 'mohaa_compare_select';
        return;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $player1Response = $api->getPlayerStats($guid1);
    $player2Response = $api->getPlayerStats($guid2);
    
    $player1 = $player1Response['player'] ?? [];
    $player2 = $player2Response['player'] ?? [];
    
    if (empty($player1) || empty($player2)) {
        fatal_lang_error('mohaa_player_not_found', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_compare_players'];
    $context['sub_template'] = 'mohaa_compare';
    
    $context['mohaa_compare'] = [
        'player1' => [
            'guid' => $guid1,
            'name' => $player1['name'],
            'stats' => $player1,
            'weapons' => $api->getPlayerWeapons($guid1),
        ],
        'player2' => [
            'guid' => $guid2,
            'name' => $player2['name'],
            'stats' => $player2,
            'weapons' => $api->getPlayerWeapons($guid2),
        ],
        'head_to_head' => $api->getHeadToHead($guid1, $guid2),
    ];
}

/**
 * Profile page: Game stats tab
 */
function MohaaPlayers_ProfileStats(int $memID): void
{
    global $context, $txt, $memberContext;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayers');
    
    loadMemberData($memID);
    loadMemberContext($memID);
    
    $context['sub_template'] = 'mohaa_profile_stats';
    
    // Get linked GUID for this member
    $guid = MohaaPlayers_GetLinkedGUID($memID);
    
    if (empty($guid)) {
        $context['mohaa_no_identity'] = true;
        return;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $player = $api->getPlayerStats($guid);
    
    // Set both for compatibility
    $context['mohaa_player'] = [
        'guid' => $guid,
        'name' => $player['name'] ?? 'Unknown',
        'stats' => $player,
        'rank' => $api->getPlayerRank($guid),
        'weapons' => $api->getPlayerWeapons($guid),
        'recent_matches' => $api->getPlayerMatches($guid, 5),
    ];
    
    // Also set what the template expects
    $context['mohaa_profile_stats'] = [
        'player' => $player,
        'guid' => $guid,
        'rank' => $api->getPlayerRank($guid),
        'weapons' => $api->getPlayerWeapons($guid),
        'recent_matches' => $api->getPlayerMatches($guid, 5),
    ];
}


/**
 * Profile page: Identity linking
 */
function MohaaPlayers_ProfileIdentity(int $memID): void
{
    global $context, $txt, $user_info, $scripturl, $smcFunc, $modSettings;
    
    // Only allow viewing own identity settings
    if ($memID != $user_info['id']) {
        redirectexit('action=profile;area=mohaastats;u=' . $memID);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaIdentity');
    
    $context['sub_template'] = 'mohaaidentity';
    $context['page_title'] = $txt['mohaa_link_identity'] ?? 'Link Game Identity';
    
    // Generate or get existing token for this user
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Handle regenerate token action
    if (isset($_POST['regenerate_token'])) {
        checkSession();  // POST session check
        $result = $api->initDeviceAuth($memID, true);  // Force regenerate
        if (!empty($result['user_code'])) {
            $_SESSION['mohaa_token'] = $result['user_code'];
            $_SESSION['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
        }
        redirectexit('action=profile;area=mohaaidentity');
    } elseif (isset($_POST['remove_trusted_ip']) && isset($_POST['ip_id'])) {
        checkSession();
        $api->deleteTrustedIP($memID, $_POST['ip_id']);
        redirectexit('action=profile;area=mohaaidentity');
    } elseif (isset($_POST['resolve_pending_ip']) && isset($_POST['approval_id']) && isset($_POST['action_type'])) {
        checkSession();
        $api->resolvePendingIP($memID, $_POST['approval_id'], $_POST['action_type']);
        redirectexit('action=profile;area=mohaaidentity');
    } elseif (isset($_GET['regenerate'])) {
        checkSession('get');  // GET session check
        $result = $api->initDeviceAuth($memID, true);  // Force regenerate
        if (!empty($result['user_code'])) {
            $_SESSION['mohaa_token'] = $result['user_code'];
            $_SESSION['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
        }
        redirectexit('action=profile;area=mohaaidentity');
    }
    
    // Get or generate token
    if (empty($_SESSION['mohaa_token']) || ($_SESSION['mohaa_token_expires'] ?? 0) < time()) {
        // Generate a new token
        $result = $api->initDeviceAuth($memID);
        if (!empty($result['user_code'])) {
            $_SESSION['mohaa_token'] = $result['user_code'];
            $_SESSION['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
        }
    }
    
    // Set context for template
    $context['mohaa_token'] = $_SESSION['mohaa_token'] ?? 'ERROR - Could not generate token';
    $context['mohaa_console_command'] = '/login ' . ($context['mohaa_token'] ?? '');
    
    // Get login history from API (stored in PostgreSQL)
    $context['mohaa_login_history'] = [];
    
    $historyData = $api->getLoginHistory($memID);
    if (!empty($historyData['history'])) {
        foreach ($historyData['history'] as $entry) {
            $context['mohaa_login_history'][] = [
                'date' => strtotime($entry['attempt_at'] ?? 'now'),
                'server' => $entry['server_name'] ?? 'Unknown Server',
                'ip' => $entry['player_ip'] ?? '-',
                'location' => '-', // Could add GeoIP lookup later
                'status' => $entry['success'] ? 'Success' : ('Failed: ' . ($entry['failure_reason'] ?? 'unknown')),
            ];
        }
    }
    
    // If no history, show placeholder
    if (empty($context['mohaa_login_history'])) {
        $context['mohaa_login_history'] = [
            [
                'date' => time(),
                'server' => 'No login history yet',
                'ip' => '-',
                'location' => '-',
                'status' => '-',
            ],
        ];
    }
    
    // Get trusted IPs from API
    $context['mohaa_trusted_ips'] = [];
    $trustedData = $api->getTrustedIPs($memID);
    if (!empty($trustedData['trusted_ips'])) {
        $context['mohaa_trusted_ips'] = $trustedData['trusted_ips'];
    }
    
    // Get pending IP approvals from API
    $context['mohaa_pending_ips'] = [];
    $pendingData = $api->getPendingIPApprovals($memID);
    if (!empty($pendingData['pending_ips'])) {
        $context['mohaa_pending_ips'] = $pendingData['pending_ips'];
    }
}

function MohaaPlayers_GetLinkedGUID(int $memberId): ?string
{
    global $smcFunc;
    
    $request = $smcFunc['db_query']('', '
        SELECT player_guid
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
        ORDER BY linked_date DESC
        LIMIT 1',
        [
            'member' => $memberId,
        ]
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    return $row ? $row['player_guid'] : null;
}

/**
 * Get SMF member linked to a GUID
 */
function MohaaPlayers_GetLinkedMember(string $guid): ?array
{
    global $smcFunc;
    
    $request = $smcFunc['db_query']('', '
        SELECT i.id_member, m.member_name, m.real_name, m.avatar
        FROM {db_prefix}mohaa_identities AS i
        LEFT JOIN {db_prefix}members AS m ON i.id_member = m.id_member
        WHERE i.player_guid = {string:guid}',
        [
            'guid' => $guid,
        ]
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    return $row ?: null;
}

/**
 * Get all identities for a member
 */
function MohaaPlayers_GetAllIdentities(int $memberId): array
{
    global $smcFunc;
    
    $identities = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT id_identity, player_guid, player_name, linked_date
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
        ORDER BY linked_date DESC',
        [
            'member' => $memberId,
        ]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $identities[] = $row;
    }
    
    $smcFunc['db_free_result']($request);
    
    return $identities;
}

/**
 * Unlink an identity
 */
function MohaaPlayers_UnlinkIdentity(int $memberId, int $identityId): void
{
    global $smcFunc;
    
    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}mohaa_identities
        WHERE id_identity = {int:identity}
            AND id_member = {int:member}',
        [
            'identity' => $identityId,
            'member' => $memberId,
        ]
    );
}

/**
 * Link an identity to a member (called by API when claim is verified)
 */
function MohaaPlayers_LinkIdentity(int $memberId, string $guid, string $playerName): int
{
    global $smcFunc;
    
    // Check if already linked
    $request = $smcFunc['db_query']('', '
        SELECT id_identity
        FROM {db_prefix}mohaa_identities
        WHERE player_guid = {string:guid}',
        [
            'guid' => $guid,
        ]
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        return 0; // Already linked
    }
    $smcFunc['db_free_result']($request);
    
    // Insert new identity
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_identities',
        [
            'id_member' => 'int',
            'player_guid' => 'string',
            'player_name' => 'string',
            'linked_date' => 'int',
        ],
        [
            $memberId,
            $guid,
            $playerName,
            time(),
        ],
        ['id_identity']
    );
    
    return $smcFunc['db_insert_id']('{db_prefix}mohaa_identities');
}
/**
 * Add menu button
 */
function MohaaPlayers_MenuButtons(array &$buttons): void
{
    global $txt, $scripturl, $modSettings;

    /*
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    */

    loadLanguage('MohaaStats');

    // Find the 'home' button to insert after
    $counter = 0;
    foreach ($buttons as $name => $button) {
        $counter++;
        if ($name == 'home') {
            break;
        }
    }

    $mohaa_button = [
        'mohaastats' => [
            'title' => $txt['mohaa_stats'],
            'href' => $scripturl . '?action=mohaadashboard',
            'show' => true,
            'sub_buttons' => [
                'dashboard' => [
                    'title' => $txt['mohaa_dashboard'],
                    'href' => $scripturl . '?action=mohaadashboard',
                    'show' => true,
                ],
                'leaderboards' => [
                    'title' => $txt['mohaa_leaderboards'],
                    'href' => $scripturl . '?action=mohaaleaderboard',
                    'show' => true,
                ],
                'matches' => [
                    'title' => $txt['mohaa_matches'],
                    'href' => $scripturl . '?action=mohaamatches',
                    'show' => true,
                ],
                'servers' => [
                    'title' => $txt['mohaa_servers'],
                    'href' => $scripturl . '?action=mohaaservers',
                    'show' => true,
                ],
                'achievements' => [
                    'title' => $txt['mohaa_achievements'],
                    'href' => $scripturl . '?action=mohaaachievements',
                    'show' => true,
                ],
                'tournaments' => [
                    'title' => $txt['mohaa_tournaments'],
                    'href' => $scripturl . '?action=mohaatournaments',
                    'show' => true,
                ],
            ],
            'is_last' => true,
        ],
    ];

    // Insert the button
    $buttons = array_merge(
        array_slice($buttons, 0, $counter, true),
        $mohaa_button,
        array_slice($buttons, $counter, null, true)
    );
}

/**
 * Redirect ?action=mohaaidentity to the profile area
 */
function MohaaPlayers_IdentityRedirect(): void
{
    global $user_info;
    
    // Must be logged in
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    // Redirect to the profile identity area
    redirectexit('action=profile;area=mohaaidentity');
}

