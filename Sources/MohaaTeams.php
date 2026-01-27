<?php
/**
 * MOHAA Teams Plugin
 * 
 * Create, join, and manage teams
 *
 * @package MohaaTeams
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaTeams_Actions(array &$actions): void
{
    $actions['mohaateams'] = ['MohaaTeams.php', 'MohaaTeams_Main'];
}

/**
 * Add profile areas
 */
function MohaaTeams_ProfileAreas(array &$profile_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaateams'] = [
        'label' => $txt['mohaa_my_teams'],
        'file' => 'MohaaTeams.php',
        'function' => 'MohaaTeams_ProfileTeams',
        'icon' => 'members',
    ];
}

/**
 * Main dispatcher
 */
function MohaaTeams_Main(): void
{
    global $context, $txt, $modSettings, $smcFunc, $db_prefix;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaTeams');
    
    $subActions = [
        'list' => 'MohaaTeams_List',
        'view' => 'MohaaTeams_View',
        'create' => 'MohaaTeams_Create',
        'edit' => 'MohaaTeams_Edit',
        'join' => 'MohaaTeams_Join',
        'leave' => 'MohaaTeams_Leave',
        'invite' => 'MohaaTeams_Invite',
        'accept' => 'MohaaTeams_AcceptInvite',
        'decline' => 'MohaaTeams_DeclineInvite',
        'manage' => 'MohaaTeams_Manage',
        'rankings' => 'MohaaTeams_Rankings',
        'retire' => 'MohaaTeams_Retire',
        'challenge' => 'MohaaTeams_Challenge',
        'challengesubmit' => 'MohaaTeams_ChallengeSubmit',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    // Auto-install tables if missing
    db_extend('packages');
    
    $check = $smcFunc['db_query']('', 'SHOW TABLES LIKE {string:table}', ['table' => $db_prefix . 'mohaa_teams']);
    $needsInstall = ($smcFunc['db_num_rows']($check) == 0);
    $smcFunc['db_free_result']($check);

    if ($needsInstall) {
        MohaaTeams_Install();
    }
    
    call_user_func($subActions[$sa]);
}

/**
 * Auto-install tables
 */
function MohaaTeams_Install(): void
{
    global $smcFunc, $db_prefix;

    $tables = [
        'mohaa_teams' => [
            'columns' => [
                ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
                ['name' => 'team_name', 'type' => 'varchar', 'size' => 255],
                ['name' => 'team_tag', 'type' => 'varchar', 'size' => 10],
                ['name' => 'description', 'type' => 'text', 'default' => ''],
                ['name' => 'logo_url', 'type' => 'varchar', 'size' => 255, 'default' => ''],
                ['name' => 'website', 'type' => 'varchar', 'size' => 255, 'default' => ''],
                ['name' => 'id_captain', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'founded_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'active'],
                ['name' => 'rating', 'type' => 'int', 'size' => 10, 'default' => 1000],
                ['name' => 'wins', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'losses', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'recruiting', 'type' => 'tinyint', 'size' => 4, 'unsigned' => true, 'default' => 0],
            ],
            'indexes' => [
                ['type' => 'primary', 'columns' => ['id_team']],
                ['type' => 'index', 'columns' => ['status']],
                ['type' => 'index', 'columns' => ['rating']],
                ['type' => 'unique', 'columns' => ['team_name']],
            ],
        ],
        'mohaa_team_members' => [
            'columns' => [
                ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'role', 'type' => 'varchar', 'size' => 20, 'default' => 'member'],
                ['name' => 'joined_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'active'],
            ],
            'indexes' => [
                ['type' => 'primary', 'columns' => ['id_team', 'id_member']],
                ['type' => 'index', 'columns' => ['id_member']],
            ],
        ],
        'mohaa_team_invites' => [
            'columns' => [
                ['name' => 'id_invite', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
                ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'id_inviter', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'invite_type', 'type' => 'varchar', 'size' => 20, 'default' => 'invite'],
                ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'],
                ['name' => 'created_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ],
            'indexes' => [
                ['type' => 'primary', 'columns' => ['id_invite']],
                ['type' => 'index', 'columns' => ['id_team']],
                ['type' => 'index', 'columns' => ['id_member']],
            ],
        ],
        'mohaa_team_matches' => [
            'columns' => [
                ['name' => 'id_match', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
                ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'id_opponent', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'match_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'result', 'type' => 'varchar', 'size' => 10, 'default' => 'win'],
                ['name' => 'map', 'type' => 'varchar', 'size' => 100, 'default' => ''],
                ['name' => 'score_us', 'type' => 'int', 'size' => 10, 'default' => 0],
                ['name' => 'score_them', 'type' => 'int', 'size' => 10, 'default' => 0],
            ],
            'indexes' => [
                ['type' => 'primary', 'columns' => ['id_match']],
                ['type' => 'index', 'columns' => ['id_team']],
                ['type' => 'index', 'columns' => ['match_date']],
            ],
        ],
        'mohaa_team_challenges' => [
            'columns' => [
                ['name' => 'id_challenge', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
                ['name' => 'id_team_challenger', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'id_team_target', 'type' => 'int', 'size' => 10, 'unsigned' => true],
                ['name' => 'challenge_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'match_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
                ['name' => 'game_mode', 'type' => 'varchar', 'size' => 50, 'default' => 'tdm'],
                ['name' => 'map', 'type' => 'varchar', 'size' => 100, 'default' => ''],
                ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'],
            ],
            'indexes' => [
                ['type' => 'primary', 'columns' => ['id_challenge']],
                ['type' => 'index', 'columns' => ['id_team_target']],
            ],
        ]
    ];

    foreach ($tables as $table => $data) {
        $smcFunc['db_create_table']('{db_prefix}' . $table, $data['columns'], $data['indexes']);
    }
}

/**
 * List all teams
 */
function MohaaTeams_List(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = $txt['mohaa_teams'];
    $context['sub_template'] = 'mohaa_teams_list';
    
    // Fetch teams
    $teams = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as captain_name,
            (SELECT COUNT(*) FROM {db_prefix}mohaa_team_members WHERE id_team = t.id_team AND status = {string:active}) as member_count
        FROM {db_prefix}mohaa_teams AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_captain = m.id_member
        WHERE t.status = {string:active}
        ORDER BY t.rating DESC, t.wins DESC',
        ['active' => 'active']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $teams[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_teams'] = $teams;
    global $user_info;
    $context['can_create_team'] = !$user_info['is_guest'] && !MohaaTeams_IsMemberOfAnyTeam($user_info['id']);
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams',
        'name' => $txt['mohaa_teams'],
    ];
}

/**
 * View team details
 */
function MohaaTeams_View(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaateams');
        return;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as captain_name
        FROM {db_prefix}mohaa_teams AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_captain = m.id_member
        WHERE t.id_team = {int:id}',
        ['id' => $id]
    );
    
    $team = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$team) {
        fatal_lang_error('mohaa_team_not_found', false);
        return;
    }
    
    $context['page_title'] = $team['team_name'];
    $context['sub_template'] = 'mohaa_team_view';
    
    // Get members
    $members = [];
    $request = $smcFunc['db_query']('', '
        SELECT tm.*, m.member_name, m.real_name, m.avatar
        FROM {db_prefix}mohaa_team_members AS tm
        LEFT JOIN {db_prefix}members AS m ON tm.id_member = m.id_member
        WHERE tm.id_team = {int:id} AND tm.status = {string:active}
        ORDER BY FIELD(tm.role, {string:captain}, {string:officer}, {string:member}, {string:substitute})',
        ['id' => $id, 'active' => 'active', 'captain' => 'captain', 'officer' => 'officer', 'member' => 'member', 'substitute' => 'substitute']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $members[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get match history
    $matches = [];
    $request = $smcFunc['db_query']('', '
        SELECT m.*, t.team_name as opponent_team_name
        FROM {db_prefix}mohaa_team_matches AS m
        LEFT JOIN {db_prefix}mohaa_teams AS t ON m.id_opponent = t.id_team
        WHERE m.id_team = {int:id}
        ORDER BY m.match_date DESC
        LIMIT 10',
        ['id' => $id]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $matches[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Check if current user is a member
    $myMembership = null;
    foreach ($members as $m) {
        if ($m['id_member'] == $user_info['id']) {
            $myMembership = $m;
            break;
        }
    }
    
    // Check for pending invite/request
    $hasPendingInvite = false;
    if (!$myMembership && !$user_info['is_guest']) {
        $request = $smcFunc['db_query']('', '
            SELECT id_invite FROM {db_prefix}mohaa_team_invites
            WHERE id_team = {int:id} AND id_member = {int:member} AND status = {string:pending}',
            ['id' => $id, 'member' => $user_info['id'], 'pending' => 'pending']
        );
        $hasPendingInvite = $smcFunc['db_num_rows']($request) > 0;
        $smcFunc['db_free_result']($request);
    }
    
    // Rule 3: Deep Stats from API
    global $sourcedir;
    require_once($sourcedir . '/MohaaPlayers.php');
    require_once($sourcedir . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Prepare batch requests
    $requests = [];
    $membersByGuid = [];
    foreach ($members as $m) {
        $guid = MohaaPlayers_GetLinkedGUID($m['id_member']);
        if ($guid) {
            $membersByGuid[$m['id_member']] = $guid;
            $requests['stats_' . $m['id_member']] = ['endpoint' => '/stats/player/' . urlencode($guid)];
            $requests['weapons_' . $m['id_member']] = ['endpoint' => '/stats/player/' . urlencode($guid) . '/weapons'];
        }
    }

    $results = !empty($requests) ? $api->getMultiple($requests) : [];
    
    $teamStats = [
        'total_kills' => 0,
        'total_deaths' => 0,
        'total_kd' => 0.0,
        'total_playtime' => 0,
        'total_matches' => 0,
        'active_members' => 0,
        'weapon_usage' => [], // [weapon_name => kills]
        'map_stats' => [], // [map_name => ['wins' => 0, 'losses' => 0, 'draws' => 0]]
        'activity_stats' => [], // [date => matches_count]
    ];
    
    // Process Match History for Stats
    foreach ($matches as $match) {
        // Map Stats
        $map = $match['map'] ?: 'Unknown';
        if (!isset($teamStats['map_stats'][$map])) {
            $teamStats['map_stats'][$map] = ['wins' => 0, 'losses' => 0, 'draws' => 0];
        }
        $teamStats['map_stats'][$map][$match['result'] . ($match['result'] == 'win' || $match['result'] == 'loss' ? 's' : 's')]++;

        // Activity Stats (Match Frequency)
        $date = date('Y-m-d', $match['match_date']);
        if (!isset($teamStats['activity_stats'][$date])) {
             $teamStats['activity_stats'][$date] = 0;
        }
        $teamStats['activity_stats'][$date]++;
    }
    // Sort Activity by date
    ksort($teamStats['activity_stats']);
    
    // Fetch Tournament History (Real)
    $tournaments = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.name, t.tournament_start as date, r.status, 
               CASE 
                   WHEN t.id_winner_team = {int:team_id} THEN "1st"
                   WHEN r.status = "disqualified" THEN "DQ"
                   ELSE "Part"
               END as placement,
               CASE 
                   WHEN t.id_winner_team = {int:team_id} THEN "🥇"
                   ELSE "🏅"
               END as badge
        FROM {db_prefix}mohaa_tournament_registrations AS r
        JOIN {db_prefix}mohaa_tournaments AS t ON t.id_tournament = r.id_tournament
        WHERE r.id_team = {int:team_id}
        ORDER BY t.tournament_start DESC',
        ['team_id' => $id]
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $tournaments[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Fetch Challenges (Upcoming Matches)
    $challenges = [];
     $request = $smcFunc['db_query']('', '
        SELECT c.*, 
               t1.team_name as challenger_name, t1.id_team as challenger_id,
               t2.team_name as target_name, t2.id_team as target_id
        FROM {db_prefix}mohaa_team_challenges AS c
        LEFT JOIN {db_prefix}mohaa_teams AS t1 ON c.id_team_challenger = t1.id_team
        LEFT JOIN {db_prefix}mohaa_teams AS t2 ON c.id_team_target = t2.id_team
        WHERE (c.id_team_challenger = {int:team_id} OR c.id_team_target = {int:team_id})
          AND c.status = {string:accepted}
        ORDER BY c.match_date ASC
        LIMIT 5',
        ['team_id' => $id, 'accepted' => 'accepted']
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $challenges[] = $row;
    }
    $smcFunc['db_free_result']($request);

    foreach ($members as &$m) {
        $guid = $membersByGuid[$m['id_member']] ?? null;
        $m['stats'] = null;
        $m['weapons'] = null;
        
        if ($guid) {
            // Process Stats
            $stats = $results['stats_' . $m['id_member']] ?? null;
            if ($stats) {
                $m['stats'] = $stats;
                $teamStats['total_kills'] += ($stats['kills'] ?? 0);
                $teamStats['total_deaths'] += ($stats['deaths'] ?? 0);
                $teamStats['total_playtime'] += ($stats['playtime'] ?? 0);
                $teamStats['total_matches'] += ($stats['matches_played'] ?? 0);
                $teamStats['active_members']++;
            }
            
            // Process Weapons
            $weapons = $results['weapons_' . $m['id_member']] ?? null;
            if ($weapons && is_array($weapons)) {
                $m['weapons'] = $weapons;
                foreach ($weapons as $w) {
                    $wName = $w['weapon_name'] ?? 'Unknown';
                    $wKills = $w['kills'] ?? 0;
                    if (!isset($teamStats['weapon_usage'][$wName])) {
                        $teamStats['weapon_usage'][$wName] = 0;
                    }
                    $teamStats['weapon_usage'][$wName] += $wKills;
                }
            }
        }
    }
    unset($m);
    
    // Sort weapon usage
    arsort($teamStats['weapon_usage']);
    $teamStats['weapon_usage'] = array_slice($teamStats['weapon_usage'], 0, 10); // Top 10

    if ($teamStats['total_deaths'] > 0) {
        $teamStats['total_kd'] = round($teamStats['total_kills'] / $teamStats['total_deaths'], 2);
    } else {
        $teamStats['total_kd'] = $teamStats['total_kills'];
    }

    $context['mohaa_team'] = [
        'info' => $team,
        'members' => $members,
        'matches' => $matches,
        'stats' => $teamStats,
        'stats' => $teamStats,
        'tournaments' => $tournaments,
        'challenges' => $challenges,
        'my_membership' => $myMembership,
        'has_pending' => $hasPendingInvite,
        'is_captain' => $myMembership && $myMembership['role'] === 'captain',
        'is_officer' => $myMembership && in_array($myMembership['role'], ['captain', 'officer']),
        'can_join' => !$myMembership && !$hasPendingInvite && !$user_info['is_guest'] && !MohaaTeams_IsMemberOfAnyTeam($user_info['id']),
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams',
        'name' => $txt['mohaa_teams'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams;sa=view;id=' . $id,
        'name' => $team['team_name'],
    ];
}

/**
 * Create a new team
 */
function MohaaTeams_Create(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_create_team'];
    $context['sub_template'] = 'mohaa_team_create';

    // Rule 1: Cannot create if already in a team
    if (MohaaTeams_IsMemberOfAnyTeam($user_info['id'])) {
        fatal_lang_error('mohaa_already_in_team', false);
        return;
    }
    
    // Handle form submission
    if (isset($_POST['create_team'])) {
        checkSession();
        
        $name = trim($_POST['team_name'] ?? '');
        $tag = trim($_POST['team_tag'] ?? '');
        $description = $_POST['description'] ?? '';
        $logo = filter_var($_POST['logo_url'] ?? '', FILTER_VALIDATE_URL) ?: '';
        
        if (empty($name)) {
            $context['mohaa_error'] = $txt['mohaa_team_name_required'];
        } elseif (strlen($name) < 3 || strlen($name) > 100) {
            $context['mohaa_error'] = $txt['mohaa_team_name_length'];
        } else {
            // Check name uniqueness
            $request = $smcFunc['db_query']('', '
                SELECT id_team FROM {db_prefix}mohaa_teams WHERE team_name = {string:name}',
                ['name' => $name]
            );
            
            if ($smcFunc['db_num_rows']($request) > 0) {
                $smcFunc['db_free_result']($request);
                $context['mohaa_error'] = $txt['mohaa_team_name_taken'];
            } else {
                $smcFunc['db_free_result']($request);
                
                // Insert team
                $smcFunc['db_insert']('insert',
                    '{db_prefix}mohaa_teams',
                    [
                        'team_name' => 'string',
                        'team_tag' => 'string',
                        'description' => 'string',
                        'logo_url' => 'string',
                        'id_captain' => 'int',
                        'founded_date' => 'int',
                        'status' => 'string',
                        'rating' => 'int',
                    ],
                    [
                        $name,
                        $tag,
                        $description,
                        $logo,
                        $user_info['id'],
                        time(),
                        'active',
                        1000,
                    ],
                    ['id_team']
                );
                
                $newId = $smcFunc['db_insert_id']('{db_prefix}mohaa_teams');
                
                // Add creator as captain
                $smcFunc['db_insert']('insert',
                    '{db_prefix}mohaa_team_members',
                    ['id_team' => 'int', 'id_member' => 'int', 'role' => 'string', 'joined_date' => 'int', 'status' => 'string'],
                    [$newId, $user_info['id'], 'captain', time(), 'active'],
                    []
                );
                
                redirectexit('action=mohaateams;sa=view;id=' . $newId);
            }
        }
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams;sa=create',
        'name' => $txt['mohaa_create_team'],
    ];
}

/**
 * Request to join a team
 */
function MohaaTeams_Join(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaateams');
        return;
    }

    // Rule 1: Cannot join if already in a team
    if (MohaaTeams_IsMemberOfAnyTeam($user_info['id'])) {
        fatal_lang_error('mohaa_already_in_team_join', false);
        return;
    }
    
    // Check not already member or pending
    $request = $smcFunc['db_query']('', '
        SELECT id_member FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:id} AND id_member = {int:member} AND status = {string:active}',
        ['id' => $id, 'member' => $user_info['id'], 'active' => 'active']
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        redirectexit('action=mohaateams;sa=view;id=' . $id);
        return;
    }
    $smcFunc['db_free_result']($request);
    
    // Create join request
    $smcFunc['db_insert']('replace',
        '{db_prefix}mohaa_team_invites',
        [
            'id_team' => 'int',
            'id_member' => 'int',
            'id_inviter' => 'int',
            'invite_type' => 'string',
            'status' => 'string',
            'created_date' => 'int',
        ],
        [
            $id,
            $user_info['id'],
            $user_info['id'],
            'request',
            'pending',
            time(),
        ],
        ['id_invite']
    );
    
    redirectexit('action=mohaateams;sa=view;id=' . $id);
}

/**
 * Leave a team
 */
function MohaaTeams_Leave(): void
{
    global $user_info, $smcFunc, $txt;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Check if captain (can't leave if captain)
    $request = $smcFunc['db_query']('', '
        SELECT role FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:id} AND id_member = {int:member} AND status = {string:active}',
        ['id' => $id, 'member' => $user_info['id'], 'active' => 'active']
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if ($row && $row['role'] === 'captain') {
        // Check if there are other members
        $requestCount = $smcFunc['db_query']('', '
            SELECT COUNT(*) AS total FROM {db_prefix}mohaa_team_members
            WHERE id_team = {int:id} AND status = {string:active}',
            ['id' => $id, 'active' => 'active']
        );
        $rowCount = $smcFunc['db_fetch_assoc']($requestCount);
        $smcFunc['db_free_result']($requestCount);
        
        if ($rowCount['total'] > 1) {
            fatal_lang_error('mohaa_captain_cannot_leave', false);
            return;
        }
    }
    
    // Update status
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_members
        SET status = {string:left}
        WHERE id_team = {int:id} AND id_member = {int:member}',
        ['id' => $id, 'member' => $user_info['id'], 'left' => 'left']
    );

    // Rule 2: Archive if empty
    MohaaTeams_CheckAndArchive($id);
    
    redirectexit('action=mohaateams;sa=view;id=' . $id);
}

/**
 * Invite a player to team
 */
function MohaaTeams_Invite(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Check permissions
    if (!MohaaTeams_IsOfficer($id, $user_info['id'])) {
        fatal_lang_error('mohaa_not_team_officer', false);
        return;
    }
    
    if (isset($_POST['invite_player'])) {
        checkSession();
        
        $playerId = (int)$_POST['player_id'];
        
        if ($playerId > 0) {
            $smcFunc['db_insert']('replace',
                '{db_prefix}mohaa_team_invites',
                [
                    'id_team' => 'int',
                    'id_member' => 'int',
                    'id_inviter' => 'int',
                    'invite_type' => 'string',
                    'status' => 'string',
                    'created_date' => 'int',
                ],
                [
                    $id,
                    $playerId,
                    $user_info['id'],
                    'invite',
                    'pending',
                    time(),
                ],
                ['id_invite']
            );
        }
        
        redirectexit('action=mohaateams;sa=manage;id=' . $id);
    }
}

/**
 * Accept a team invite
 */
function MohaaTeams_AcceptInvite(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $inviteId = isset($_GET['invite']) ? (int)$_GET['invite'] : 0;
    
    // Get invite
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_team_invites
        WHERE id_invite = {int:id} AND id_member = {int:member} AND status = {string:pending}',
        ['id' => $inviteId, 'member' => $user_info['id'], 'pending' => 'pending']
    );
    
    $invite = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$invite) {
        fatal_lang_error('mohaa_invite_not_found', false);
        return;
    }
    
    // Update invite status
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_invites SET status = {string:accepted} WHERE id_invite = {int:id}',
        ['id' => $inviteId, 'accepted' => 'accepted']
    );
    
    // Add to team
    $smcFunc['db_insert']('replace',
        '{db_prefix}mohaa_team_members',
        ['id_team' => 'int', 'id_member' => 'int', 'role' => 'string', 'joined_date' => 'int', 'status' => 'string'],
        [$invite['id_team'], $user_info['id'], 'member', time(), 'active'],
        []
    );
    
    redirectexit('action=mohaateams;sa=view;id=' . $invite['id_team']);
}

/**
 * Decline a team invite
 */
function MohaaTeams_DeclineInvite(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $inviteId = isset($_GET['invite']) ? (int)$_GET['invite'] : 0;
    
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_invites
        SET status = {string:declined}
        WHERE id_invite = {int:id} AND id_member = {int:member}',
        ['id' => $inviteId, 'member' => $user_info['id'], 'declined' => 'declined']
    );
    
    redirectexit('action=profile;area=mohaateams');
}

/**
 * Manage team
 */
function MohaaTeams_Manage(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$user_info['is_admin'] && !MohaaTeams_IsOfficer($id, $user_info['id'])) {
        fatal_lang_error('mohaa_not_team_officer', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_manage_team'];
    $context['sub_template'] = 'mohaa_team_manage';
    
    // Handle form submission / other actions
    if (isset($_POST['action'])) {
        checkSession();
        
        checkSession();
        
        switch ($_POST['action']) {
             case 'save_settings':
                $recruiting = !empty($_POST['recruiting']) ? 1 : 0;
                $description = $_POST['description'];
                $logo_url = $_POST['logo_url'];
                $website = $_POST['website'];
                
                $smcFunc['db_query']('', '
                    UPDATE {db_prefix}mohaa_teams
                    SET description = {string:desc}, logo_url = {string:logo}, website = {string:web}, recruiting = {int:rec}
                    WHERE id_team = {int:id}',
                    [
                        'id' => $id, 
                        'desc' => $description, 
                        'logo' => $logo_url, 
                        'web' => $website, 
                        'rec' => $recruiting
                    ]
                );
                // Redirect to avoid resubmission
                redirectexit('action=mohaateams;sa=manage;id=' . $id);
                break;

            case 'kick':
                $memberId = (int)$_POST['member_id'];
                if ($memberId > 0 && $memberId != $user_info['id']) {
                    $smcFunc['db_query']('', '
                        UPDATE {db_prefix}mohaa_team_members
                        SET status = {string:kicked}
                        WHERE id_team = {int:team} AND id_member = {int:member}',
                        ['team' => $id, 'member' => $memberId, 'kicked' => 'kicked']
                    );
                    
                    // Archive if empty (unlikely after kick unless sole member)
                    MohaaTeams_CheckAndArchive($id);
                }
                break;
                
            case 'reject_request':
                $inviteId = (int)$_POST['invite_id'];
                 $smcFunc['db_query']('', '
                    UPDATE {db_prefix}mohaa_team_invites SET status = {string:declined} WHERE id_invite = {int:id}',
                    ['id' => $inviteId, 'declined' => 'declined']
                );
                break;

             case 'respond_challenge':
                $challengeId = (int)$_POST['challenge_id'];
                $response = $_POST['response'] === 'accept' ? 'accepted' : 'declined';
                
                // Extra security check for ownership? (Assumed implicit by Manage page access)
                $smcFunc['db_query']('', '
                    UPDATE {db_prefix}mohaa_team_challenges
                    SET status = {string:status}
                    WHERE id_challenge = {int:id} AND id_team_target = {int:team_id}',
                    ['id' => $challengeId, 'team_id' => $id, 'status' => $response]
                );
                break;

                
            case 'promote':
                $memberId = (int)$_POST['member_id'];
                $role = $_POST['role'] ?? 'member';
                if (in_array($role, ['officer', 'member', 'substitute'])) {
                    $smcFunc['db_query']('', '
                        UPDATE {db_prefix}mohaa_team_members
                        SET role = {string:role}
                        WHERE id_team = {int:team} AND id_member = {int:member}',
                        ['team' => $id, 'member' => $memberId, 'role' => $role]
                    );
                }
                break;
                
            case 'approve_request':
                $inviteId = (int)$_POST['invite_id'];
                $request = $smcFunc['db_query']('', '
                    SELECT * FROM {db_prefix}mohaa_team_invites WHERE id_invite = {int:id} AND status = {string:pending}',
                    ['id' => $inviteId, 'pending' => 'pending']
                );
                $invite = $smcFunc['db_fetch_assoc']($request);
                $smcFunc['db_free_result']($request);
                
                if ($invite) {
                    $smcFunc['db_query']('', '
                        UPDATE {db_prefix}mohaa_team_invites SET status = {string:accepted} WHERE id_invite = {int:id}',
                        ['id' => $inviteId, 'accepted' => 'accepted']
                    );
                    
                    $smcFunc['db_insert']('replace',
                        '{db_prefix}mohaa_team_members',
                        ['id_team' => 'int', 'id_member' => 'int', 'role' => 'string', 'joined_date' => 'int', 'status' => 'string'],
                        [$invite['id_team'], $invite['id_member'], 'member', time(), 'active'],
                        []
                    );
                }
                break;
        }
        
        redirectexit('action=mohaateams;sa=manage;id=' . $id);
    }
    
    // Get team info
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_teams WHERE id_team = {int:id}',
        ['id' => $id]
    );
    $team = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    // Get members
    $members = [];
    $request = $smcFunc['db_query']('', '
        SELECT tm.*, m.member_name
        FROM {db_prefix}mohaa_team_members AS tm
        LEFT JOIN {db_prefix}members AS m ON tm.id_member = m.id_member
        WHERE tm.id_team = {int:id} AND tm.status = {string:active}',
        ['id' => $id, 'active' => 'active']
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $members[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get pending requests
    $requests = [];
    $request = $smcFunc['db_query']('', '
        SELECT i.*, m.member_name
        FROM {db_prefix}mohaa_team_invites AS i
        LEFT JOIN {db_prefix}members AS m ON i.id_member = m.id_member
        WHERE i.id_team = {int:id} AND i.invite_type = {string:request} AND i.status = {string:pending}',
        ['id' => $id, 'request' => 'request', 'pending' => 'pending']
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $requests[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_team_manage'] = [
        'team' => $team,
        'members' => $members,
        'requests' => $requests,
    ];
    
    // Fetch Team Challenges (for Management)
    $challenges = [];
     $request = $smcFunc['db_query']('', '
        SELECT c.*, 
               t1.team_name as challenger_name, t1.id_team as challenger_id,
               t2.team_name as target_name, t2.id_team as target_id
        FROM {db_prefix}mohaa_team_challenges AS c
        LEFT JOIN {db_prefix}mohaa_teams AS t1 ON c.id_team_challenger = t1.id_team
        LEFT JOIN {db_prefix}mohaa_teams AS t2 ON c.id_team_target = t2.id_team
        WHERE c.id_team_challenger = {int:team_id} OR c.id_team_target = {int:team_id}
        ORDER BY c.match_date ASC',
        ['team_id' => $id]
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $challenges[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_manage']['challenges'] = $challenges;
}

/**
 * Team rankings
 */
function MohaaTeams_Rankings(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = $txt['mohaa_team_rankings'];
    $context['sub_template'] = 'mohaa_team_rankings';
    
    $teams = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as captain_name,
            (SELECT COUNT(*) FROM {db_prefix}mohaa_team_members WHERE id_team = t.id_team AND status = {string:active}) as member_count
        FROM {db_prefix}mohaa_teams AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_captain = m.id_member
        WHERE t.status = {string:active}
        ORDER BY t.rating DESC, t.wins DESC
        LIMIT 100',
        ['active' => 'active']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $teams[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_teams_ranking'] = $teams;
}

/**
 * Profile teams tab
 */
function MohaaTeams_ProfileTeams(int $memID): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    loadTemplate('MohaaTeams');
    
    $context['page_title'] = $txt['mohaa_my_teams'];
    $context['sub_template'] = 'mohaa_profile_teams';
    
    // Get user's teams
    $teams = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, tm.role, tm.joined_date
        FROM {db_prefix}mohaa_team_members AS tm
        INNER JOIN {db_prefix}mohaa_teams AS t ON tm.id_team = t.id_team
        WHERE tm.id_member = {int:member} AND tm.status = {string:active}',
        ['member' => $memID, 'active' => 'active']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $teams[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get pending invites (only for own profile)
    $invites = [];
    if ($memID == $user_info['id']) {
        $request = $smcFunc['db_query']('', '
            SELECT i.*, t.team_name, m.member_name as inviter_name
            FROM {db_prefix}mohaa_team_invites AS i
            INNER JOIN {db_prefix}mohaa_teams AS t ON i.id_team = t.id_team
            LEFT JOIN {db_prefix}members AS m ON i.id_inviter = m.id_member
            WHERE i.id_member = {int:member} AND i.invite_type = {string:invite} AND i.status = {string:pending}',
            ['member' => $memID, 'invite' => 'invite', 'pending' => 'pending']
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $invites[] = $row;
        }
        $smcFunc['db_free_result']($request);
    }
    
    $context['mohaa_profile_teams'] = [
        'teams' => $teams,
        'invites' => $invites,
        'is_own' => $memID == $user_info['id'],
    ];
}

/**
 * Check if user is team officer
 */
function MohaaTeams_IsOfficer(int $teamId, int $memberId): bool
{
    global $smcFunc;
    
    if (allowedTo('admin_forum')) {
        return true;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT role FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:team} AND id_member = {int:member} AND status = {string:active}
        AND role IN ({string:captain}, {string:officer})',
        ['team' => $teamId, 'member' => $memberId, 'active' => 'active', 'captain' => 'captain', 'officer' => 'officer']
    );
    
    $isOfficer = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);
    
    return $isOfficer;
}

/**
 * Check if user is member of ANY active team
 */
function MohaaTeams_IsMemberOfAnyTeam(int $memberId): bool
{
    global $smcFunc;
    
    $request = $smcFunc['db_query']('', '
        SELECT id_team FROM {db_prefix}mohaa_team_members
        WHERE id_member = {int:member} AND status = {string:active}',
        ['member' => $memberId, 'active' => 'active']
    );
    
    $isMember = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);
    
    return $isMember;
}

/**
 * Check and archive team if empty
 */
function MohaaTeams_CheckAndArchive(int $teamId): void
{
    global $smcFunc;

    $request = $smcFunc['db_query']('', '
        SELECT COUNT(*) AS total FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:team} AND status = {string:active}',
        ['team' => $teamId, 'active' => 'active']
    );
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if (empty($row['total'])) {
        $smcFunc['db_query']('', '
            UPDATE {db_prefix}mohaa_teams
            SET status = {string:archived}
            WHERE id_team = {int:team}',
            ['team' => $teamId, 'archived' => 'archived']
        );
    }
}

/**
 * Retire (Archive) a team
 */
function MohaaTeams_Retire(): void
{
    global $smcFunc, $user_info;
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        fatal_lang_error('mohaa_team_not_found', false);
        return;
    }
    
    // Check ownership
    $request = $smcFunc['db_query']('', '
        SELECT id_team, id_captain, team_name 
        FROM {db_prefix}mohaa_teams
        WHERE id_team = {int:id} AND status = {string:active}',
        ['id' => $id, 'active' => 'active']
    );
    $team = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$team) {
        fatal_lang_error('mohaa_team_not_found', false);
        return;
    }
    
    if ($team['id_captain'] != $user_info['id'] && !allowedTo('admin_forum')) {
        fatal_lang_error('mohaa_not_team_captain', false);
        return;
    }
    
    // 1. Archive the team
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_teams
        SET status = {string:archived}
        WHERE id_team = {int:id}',
        ['id' => $id, 'archived' => 'archived']
    );
    
    // 2. Archive all members (release them)
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_members
        SET status = {string:archived}, left_date = {int:now}
        WHERE id_team = {int:id} AND status = {string:active}',
        ['id' => $id, 'archived' => 'archived', 'active' => 'active', 'now' => time()]
    );
    
    redirectexit('action=mohaateams');
}

/**
 * Challenge a team (Form)
 */
function MohaaTeams_Challenge(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;

    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }

    $targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Check if user is Captain/Officer of ANY team
    // We need to know which team the user is representing
    $myTeamId = 0;
    $request = $smcFunc['db_query']('', '
        SELECT id_team, team_name FROM {db_prefix}mohaa_teams 
        WHERE id_captain = {int:id} AND status = {string:active}',
        ['id' => $user_info['id'], 'active' => 'active']
    );
    if ($smcFunc['db_num_rows']($request) > 0) {
         $row = $smcFunc['db_fetch_assoc']($request);
         $myTeamId = $row['id_team'];
         $myTeamName = $row['team_name'];
    }
    $smcFunc['db_free_result']($request);

    if (empty($myTeamId)) {
        fatal_lang_error('mohaa_not_captain', false); // Only captains can challenge
        return;
    }

    if ($targetId == $myTeamId) {
        fatal_lang_error('mohaa_challenge_self', false);
        return;
    }

    // Get Target Team Info
    $request = $smcFunc['db_query']('', '
        SELECT team_name FROM {db_prefix}mohaa_teams WHERE id_team = {int:id}',
        ['id' => $targetId]
    );
    $targetTeam = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if (!$targetTeam) {
        fatal_lang_error('mohaa_team_not_found', false);
        return;
    }

    $context['page_title'] = 'Challenge Team: ' . $targetTeam['team_name'];
    $context['sub_template'] = 'mohaa_team_challenge';
    $context['mohaa_challenge'] = [
        'target_id' => $targetId,
        'target_name' => $targetTeam['team_name'],
        'my_team_id' => $myTeamId,
        'my_team_name' => $myTeamName,
    ];
}

/**
 * Submit Challenge
 */
function MohaaTeams_ChallengeSubmit(): void
{
    global $user_info, $smcFunc, $txt;

    checkSession();

    $targetId = (int)$_POST['target_id'];
    $myTeamId = (int)$_POST['my_team_id'];
    $gameMode = $_POST['game_mode'];
    $map = $_POST['map'];
    $matchDate = strtotime($_POST['match_date']);

    // Validate ownership again
    if (!MohaaTeams_IsOfficer($myTeamId, $user_info['id'])) {
         fatal_lang_error('mohaa_not_allowed', false);
         return;
    }

    if (empty($matchDate) || $matchDate < time()) {
        // Just default to +1 day if invalid
        $matchDate = time() + 86400;
    }

    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_team_challenges',
        [
            'id_team_challenger' => 'int',
            'id_team_target' => 'int',
            'match_date' => 'int',
            'game_mode' => 'string',
            'map' => 'string',
            'status' => 'string',
            'challenge_date' => 'int',
        ],
        [
            $myTeamId,
            $targetId,
            $matchDate,
            $gameMode,
            $map,
            'pending',
            time()
        ],
        ['id_challenge']
    );

    redirectexit('action=mohaateams;sa=view;id=' . $targetId);
}
