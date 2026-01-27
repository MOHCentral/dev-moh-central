<?php
/**
 * MOHAA Achievements & Medals Plugin
 * 
 * Comprehensive achievement system with tiered badges and medals
 *
 * @package MohaaAchievements
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaAchievements_Actions(array &$actions): void
{
    $actions['mohaaachievements'] = ['MohaaAchievements.php', 'MohaaAchievements_Main'];
}

/**
 * Add profile areas
 */
function MohaaAchievements_ProfileAreas(array &$profile_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaabadges'] = [
        'label' => $txt['mohaa_medals_badges'] ?? 'Medals & Badges',
        'file' => 'MohaaAchievements.php',
        'function' => 'MohaaAchievements_ProfileMedals',
        'icon' => 'members',
    ];
}

/**
 * Main dispatcher
 */
function MohaaAchievements_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaAchievements');
    
    $subActions = [
        'list' => 'MohaaAchievements_List',
        'view' => 'MohaaAchievements_View',
        'achievement' => 'MohaaAchievements_AchievementLeaderboard',
        'leaderboard' => 'MohaaAchievements_LeaderboardEnhanced',
        'recent' => 'MohaaAchievements_Recent',
        'rarest' => 'MohaaAchievements_Rarest',
        'category' => 'MohaaAchievements_Category',
        'compare' => 'MohaaAchievements_Compare',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    // Load enhanced template for specific actions
    if (in_array($sa, ['leaderboard', 'rarest'])) {
        loadTemplate('MohaaAchievementsEnhanced');
    }
    
    call_user_func($subActions[$sa]);
}

/**
 * List all achievements with pagination and search
 */
function MohaaAchievements_List(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $context['page_title'] = $txt['mohaa_achievements'] ?? 'Achievements';
    $context['sub_template'] = 'mohaa_achievements_list';
    
    // Pagination and search parameters
    $perPage = 24; // Show 24 achievements per page (4x6 grid)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $categoryFilter = isset($_GET['cat']) ? $_GET['cat'] : '';
    $tierFilter = isset($_GET['tier']) ? (int)$_GET['tier'] : 0;
    $showUnlocked = isset($_GET['unlocked']) ? $_GET['unlocked'] : '';
    
    $context['achievements_search'] = $search;
    $context['achievements_category'] = $categoryFilter;
    $context['achievements_tier'] = $tierFilter;
    $context['achievements_unlocked_filter'] = $showUnlocked;
    $context['achievements_page'] = $page;
    $context['achievements_per_page'] = $perPage;
    
    // Fetch achievements from API
    require_once(dirname(__FILE__) . '/MohaaStats/MohaaStatsAPI.php');
    $apiClient = new MohaaStatsAPIClient();
    
    // Get all achievements
    $allAchievements = [];
    $achievementsData = $apiClient->getAchievements();
    if ($achievementsData && isset($achievementsData['achievements'])) {
        $allAchievements = $achievementsData['achievements'];
    }
    
    // Get player progress if logged in
    $playerProgress = [];
    if (!empty($user_info['id'])) {
        // Get player GUID from SMF database
        $request = $smcFunc['db_query']('', '
            SELECT player_guid
            FROM {db_prefix}mohaa_identities
            WHERE id_member = {int:member_id}
            LIMIT 1',
            ['member_id' => $user_info['id']]
        );
        
        $row = $smcFunc['db_fetch_assoc']($request);
        $guid = $row['player_guid'] ?? '';
        $smcFunc['db_free_result']($request);
        
        if (!empty($guid)) {
            $progressResult = $apiClient->getPlayerAchievements($guid);
            if ($progressResult && isset($progressResult['achievements'])) {
                foreach ($progressResult['achievements'] as $ach) {
                    $playerProgress[$ach['achievement_code'] ?? $ach['code']] = $ach;
                }
            }
        }
    }
    
    // Apply filters
    $filteredAchievements = $allAchievements;
    if (!empty($categoryFilter)) {
        $filteredAchievements = array_filter($filteredAchievements, function($ach) use ($categoryFilter) {
            return strcasecmp($ach['category'], $categoryFilter) === 0;
        });
    }
    
    // Build WHERE clause for compatibility (legacy)
    $whereClause = '1=1';
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= ' AND (name LIKE {string:search} OR description LIKE {string:search})';
        $params['search'] = '%' . $search . '%';
    }
    
    if (!empty($categoryFilter)) {
        $whereClause .= ' AND category = {string:category}';
        $params['category'] = $categoryFilter;
    }
    
    if ($tierFilter > 0) {
        $whereClause .= ' AND tier = {int:tier}';
        $params['tier'] = $tierFilter;
    }
    
    // Get total count for pagination
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(*) FROM {db_prefix}mohaa_achievement_defs WHERE ' . $whereClause,
        $params
    );
    list($totalAchievements) = $smcFunc['db_fetch_row']($request);
    $smcFunc['db_free_result']($request);
    
    $context['achievements_total'] = (int)$totalAchievements;
    $context['achievements_total_pages'] = max(1, ceil($totalAchievements / $perPage));
    
    // Clamp page to valid range
    if ($page > $context['achievements_total_pages']) {
        $page = $context['achievements_total_pages'];
        $context['achievements_page'] = $page;
    }
    
    $offset = ($page - 1) * $perPage;
    $params['offset'] = $offset;
    $params['limit'] = $perPage;
    
    // Get all achievement definitions from DATABASE (not API - DB has 85+ achievements)
    $achievements = [];
    $request = $smcFunc['db_query']('', '
        SELECT id_achievement, code, name, description, category, tier, icon, 
               requirement_type, requirement_value, points, is_hidden, sort_order
        FROM {db_prefix}mohaa_achievement_defs
        WHERE ' . $whereClause . '
        ORDER BY category, tier, sort_order
        LIMIT {int:offset}, {int:limit}',
        $params
    );
    
    // Also get all categories for the filter dropdown
    $catRequest = $smcFunc['db_query']('', '
        SELECT DISTINCT category FROM {db_prefix}mohaa_achievement_defs ORDER BY category'
    );
    $context['achievements_categories'] = [];
    while ($row = $smcFunc['db_fetch_assoc']($catRequest)) {
        $context['achievements_categories'][] = $row['category'];
    }
    $smcFunc['db_free_result']($catRequest);
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $achievements[$row['id_achievement']] = [
            'id_achievement' => $row['id_achievement'],
            'code' => $row['code'],
            'name' => $row['name'],
            'description' => $row['description'],
            'tier' => (int)$row['tier'],
            'category' => $row['category'],
            'target' => (int)$row['requirement_value'],
            'requirement_value' => (int)$row['requirement_value'],
            'metric' => $row['requirement_type'],
            'icon' => $row['icon'],
            'points' => (int)$row['points'],
            'is_hidden' => (int)$row['is_hidden'],
        ];
    }
    $smcFunc['db_free_result']($request);
    
    // Get user's unlocked achievements and progress from DATABASE
    $unlocked = [];
    $progress = [];
    
    if (!$user_info['is_guest']) {
        // Get unlocked achievements for this user
        $request = $smcFunc['db_query']('', '
            SELECT pa.id_achievement, pa.unlocked_date
            FROM {db_prefix}mohaa_player_achievements pa
            WHERE pa.id_member = {int:member}',
            ['member' => $user_info['id']]
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $unlocked[$row['id_achievement']] = [
                'id_achievement' => $row['id_achievement'],
                'unlocked_date' => $row['unlocked_date'],
            ];
        }
        $smcFunc['db_free_result']($request);
        
        // Get progress for in-progress achievements
        $request = $smcFunc['db_query']('', '
            SELECT id_achievement, current_progress
            FROM {db_prefix}mohaa_achievement_progress
            WHERE id_member = {int:member}',
            ['member' => $user_info['id']]
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $progress[$row['id_achievement']] = (int)$row['current_progress'];
        }
        $smcFunc['db_free_result']($request);
    }
    
    // Group by category - dynamically from API data
    $categoryStyles = [
        1 => 'bronze',
        2 => 'silver', 
        3 => 'gold',
        4 => 'patch',
        5 => 'rusty',
    ];
    
    $grouped = [];
    
    foreach ($achievements as $id => $ach) {
        $cat = $ach['category'] ?? 'Other';
        $tier = $ach['tier'] ?? 1;
        
        $ach['is_unlocked'] = isset($unlocked[$id]);
        $ach['unlocked_date'] = $unlocked[$id]['unlocked_date'] ?? null;
        $ach['current_progress'] = $progress[$id] ?? 0;
        $ach['progress_percent'] = min(100, round(($ach['current_progress'] / max(1, $ach['target'] ?? 1)) * 100));
        
        // Apply unlocked filter if set
        if ($showUnlocked === 'yes' && !$ach['is_unlocked']) {
            continue;
        }
        if ($showUnlocked === 'no' && $ach['is_unlocked']) {
            continue;
        }
        
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [
                'info' => [
                    'name' => $cat,
                    'tier' => $tier,
                    'style' => $categoryStyles[$tier] ?? 'bronze',
                ],
                'achievements' => [],
            ];
        }
        
        $grouped[$cat]['achievements'][] = $ach;
    }
    
    // Get total stats (from all achievements, not just current page)
    $statsRequest = $smcFunc['db_query']('', '
        SELECT COUNT(*) as total, SUM(points) as total_points
        FROM {db_prefix}mohaa_achievement_defs WHERE is_hidden = 0'
    );
    $stats = $smcFunc['db_fetch_assoc']($statsRequest);
    $smcFunc['db_free_result']($statsRequest);
    
    $totalAllAchievements = (int)($stats['total'] ?? 0);
    $totalPoints = (int)($stats['total_points'] ?? 0);
    $unlockedCount = count($unlocked);
    
    // Calculate earned points
    $earnedPoints = 0;
    if (!empty($unlocked)) {
        $earnedRequest = $smcFunc['db_query']('', '
            SELECT SUM(ad.points) as earned
            FROM {db_prefix}mohaa_player_achievements pa
            JOIN {db_prefix}mohaa_achievement_defs ad ON ad.id_achievement = pa.id_achievement
            WHERE pa.id_member = {int:member}',
            ['member' => $user_info['id']]
        );
        $earnedRow = $smcFunc['db_fetch_assoc']($earnedRequest);
        $earnedPoints = (int)($earnedRow['earned'] ?? 0);
        $smcFunc['db_free_result']($earnedRequest);
    }
    
    $context['mohaa_achievements'] = [
        'categories' => $grouped,
        'total' => $totalAllAchievements,
        'unlocked' => $unlockedCount,
        'total_points' => $totalPoints,
        'earned_points' => $earnedPoints,
        'completion_percent' => round(($unlockedCount / max(1, $totalAllAchievements)) * 100),
        'filtered_total' => $context['achievements_total'],
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements',
        'name' => $txt['mohaa_achievements'] ?? 'Achievements',
    ];
}

/**
 * View single achievement details
 */
function MohaaAchievements_View(): void
{
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaaachievements');
        return;
    }
    
    redirectexit('action=mohaaachievements;sa=achievement;id=' . $id);
}

/**
 * Achievement-specific leaderboard
 */
function MohaaAchievements_AchievementLeaderboard(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaaachievements');
        return;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT *
        FROM {db_prefix}mohaa_achievement_defs
        WHERE id_achievement = {int:id}
        LIMIT 1',
        ['id' => $id]
    );
    $achievement = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$achievement) {
        fatal_lang_error('mohaa_achievement_not_found', false);
        return;
    }
    
    $context['page_title'] = $achievement['name'] . ' Leaderboard';
    $context['sub_template'] = 'mohaa_achievement_leaderboard';
    
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(DISTINCT id_member)
        FROM {db_prefix}mohaa_player_achievements'
    );
    list($totalPlayers) = $smcFunc['db_fetch_row']($request);
    $smcFunc['db_free_result']($request);
    if ($totalPlayers < 1) $totalPlayers = 1;
    
    $unlockers = [];
    $request = $smcFunc['db_query']('', '
        SELECT pa.id_member, m.member_name, m.real_name, pa.unlocked_date
        FROM {db_prefix}mohaa_player_achievements pa
        JOIN {db_prefix}members m ON m.id_member = pa.id_member
        WHERE pa.id_achievement = {int:id}
        ORDER BY pa.unlocked_date ASC',
        ['id' => $id]
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $unlockers[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $totalUnlocks = count($unlockers);
    $unlockPercent = round(($totalUnlocks / $totalPlayers) * 100, 2);
    
    $context['mohaa_achievement_leaderboard'] = [
        'info' => $achievement,
        'unlockers' => $unlockers,
        'total_unlocks' => $totalUnlocks,
        'unlock_percent' => $unlockPercent,
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements',
        'name' => $txt['mohaa_achievements'] ?? 'Achievements',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements;sa=achievement;id=' . $id,
        'name' => $achievement['name'],
    ];
}

/**
 * Achievement leaderboard
 */
function MohaaAchievements_Leaderboard(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = 'Achievement Leaderboard';
    $context['sub_template'] = 'mohaa_achievements_leaderboard';
    
    // Initialize API
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $players = $api->getAchievementLeaderboard() ?? [];
    // Mock mapping if API returns different structure or empty
    if (empty($players)) $players = [];
    
    $context['mohaa_leaderboard'] = $players;
}

/**
 * Enhanced Achievement Leaderboard with multiple tabs
 */
function MohaaAchievements_LeaderboardEnhanced(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = 'Achievement Hall of Fame';
    $context['sub_template'] = 'mohaa_achievements_leaderboard_enhanced';
    
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'points';
    $context['leaderboard_tab'] = $tab;
    
    // Initialize API
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_achievement_leaderboard'] = [];
    
    switch ($tab) {
        case 'count':
            $context['mohaa_achievement_leaderboard']['count'] = MohaaAchievements_GetCountLeaderboard($smcFunc);
            break;
        case 'rarest':
            $context['mohaa_achievement_leaderboard']['rarest'] = MohaaAchievements_GetRarityLeaderboard($smcFunc);
            break;
        case 'first':
            $context['mohaa_achievement_leaderboard']['first'] = MohaaAchievements_GetFirstUnlocks($smcFunc);
            break;
        case 'perfect':
            $context['mohaa_achievement_leaderboard']['completionists'] = MohaaAchievements_GetCompletionists($smcFunc);
            break;
        default:
            $context['mohaa_achievement_leaderboard']['points'] = MohaaAchievements_GetPointsLeaderboard($smcFunc);
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements',
        'name' => $txt['mohaa_achievements'] ?? 'Achievements',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements;sa=leaderboard',
        'name' => 'Leaderboard',
    ];
}

/**
 * Get points-based leaderboard
 */
function MohaaAchievements_GetPointsLeaderboard($smcFunc, $limit = 50): array
{
    $players = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT 
            m.id_member, m.member_name, m.real_name,
            COUNT(pa.id_unlock) AS achievement_count,
            COALESCE(SUM(ad.points), 0) AS total_points
        FROM {db_prefix}mohaa_player_achievements pa
        JOIN {db_prefix}members m ON m.id_member = pa.id_member
        JOIN {db_prefix}mohaa_achievement_defs ad ON ad.id_achievement = pa.id_achievement
        GROUP BY m.id_member
        ORDER BY total_points DESC, achievement_count DESC
        LIMIT {int:limit}',
        ['limit' => $limit]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $players[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get featured achievements for top players
    foreach ($players as &$player) {
        $request = $smcFunc['db_query']('', '
            SELECT ad.id_achievement, ad.name, ad.icon, ad.tier, ad.points
            FROM {db_prefix}mohaa_player_achievements pa
            JOIN {db_prefix}mohaa_achievement_defs ad ON ad.id_achievement = pa.id_achievement
            WHERE pa.id_member = {int:member}
            ORDER BY ad.tier DESC, ad.points DESC
            LIMIT 5',
            ['member' => $player['id_member']]
        );
        
        $player['featured_achievements'] = [];
        while ($ach = $smcFunc['db_fetch_assoc']($request)) {
            $player['featured_achievements'][] = $ach;
        }
        $smcFunc['db_free_result']($request);
    }
    
    return $players;
}

/**
 * Get count-based leaderboard
 */
function MohaaAchievements_GetCountLeaderboard($smcFunc, $limit = 50): array
{
    $players = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT 
            m.id_member, m.member_name, m.real_name,
            COUNT(pa.id_unlock) AS achievement_count
        FROM {db_prefix}mohaa_player_achievements pa
        JOIN {db_prefix}members m ON m.id_member = pa.id_member
        GROUP BY m.id_member
        ORDER BY achievement_count DESC
        LIMIT {int:limit}',
        ['limit' => $limit]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $players[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    return $players;
}

/**
 * Get rarity-weighted leaderboard
 */
function MohaaAchievements_GetRarityLeaderboard($smcFunc, $limit = 50): array
{
    // First, calculate unlock percentages for each achievement
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(DISTINCT id_member) FROM {db_prefix}mohaa_player_achievements'
    );
    list($totalPlayers) = $smcFunc['db_fetch_row']($request);
    $smcFunc['db_free_result']($request);
    
    if ($totalPlayers < 1) $totalPlayers = 1;
    
    // Calculate rarity scores per player
    $players = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT 
            m.id_member, m.member_name, m.real_name,
            SUM(
                CASE 
                    WHEN (unlock_counts.unlock_pct) <= 0.1 THEN 100
                    WHEN (unlock_counts.unlock_pct) <= 1 THEN 50
                    WHEN (unlock_counts.unlock_pct) <= 5 THEN 25
                    WHEN (unlock_counts.unlock_pct) <= 20 THEN 10
                    ELSE 1
                END
            ) AS rarity_score,
            SUM(CASE WHEN unlock_counts.unlock_pct <= 0.1 THEN 1 ELSE 0 END) AS legendary_count,
            SUM(CASE WHEN unlock_counts.unlock_pct > 0.1 AND unlock_counts.unlock_pct <= 1 THEN 1 ELSE 0 END) AS epic_count,
            SUM(CASE WHEN unlock_counts.unlock_pct > 1 AND unlock_counts.unlock_pct <= 5 THEN 1 ELSE 0 END) AS rare_count
        FROM {db_prefix}mohaa_player_achievements pa
        JOIN {db_prefix}members m ON m.id_member = pa.id_member
        JOIN (
            SELECT 
                id_achievement,
                (COUNT(*) * 100.0 / {int:total_players}) AS unlock_pct
            FROM {db_prefix}mohaa_player_achievements
            GROUP BY id_achievement
        ) unlock_counts ON unlock_counts.id_achievement = pa.id_achievement
        GROUP BY m.id_member
        ORDER BY rarity_score DESC
        LIMIT {int:limit}',
        ['total_players' => $totalPlayers, 'limit' => $limit]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $players[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    return $players;
}

/**
 * Get first unlocks for each achievement
 */
function MohaaAchievements_GetFirstUnlocks($smcFunc, $limit = 30): array
{
    $achievements = [];
    
    // Get total players who have any achievement
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(DISTINCT id_member) FROM {db_prefix}mohaa_player_achievements'
    );
    list($totalPlayers) = $smcFunc['db_fetch_row']($request);
    $smcFunc['db_free_result']($request);
    if ($totalPlayers < 1) $totalPlayers = 1;
    
    $request = $smcFunc['db_query']('', '
        SELECT 
            ad.id_achievement, ad.name, ad.description, ad.icon, ad.tier, ad.points,
            first_unlock.id_member AS first_member_id,
            m.member_name AS first_member_name,
            first_unlock.unlocked_date AS first_unlock_date,
            unlock_stats.total_unlocks,
            (unlock_stats.total_unlocks * 100.0 / {int:total_players}) AS unlock_percent
        FROM {db_prefix}mohaa_achievement_defs ad
        JOIN (
            SELECT id_achievement, id_member, unlocked_date,
                   ROW_NUMBER() OVER (PARTITION BY id_achievement ORDER BY unlocked_date ASC) AS rn
            FROM {db_prefix}mohaa_player_achievements
        ) first_unlock ON first_unlock.id_achievement = ad.id_achievement AND first_unlock.rn = 1
        JOIN {db_prefix}members m ON m.id_member = first_unlock.id_member
        JOIN (
            SELECT id_achievement, COUNT(*) AS total_unlocks
            FROM {db_prefix}mohaa_player_achievements
            GROUP BY id_achievement
        ) unlock_stats ON unlock_stats.id_achievement = ad.id_achievement
        ORDER BY ad.tier DESC, ad.points DESC
        LIMIT {int:limit}',
        ['total_players' => $totalPlayers, 'limit' => $limit]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $achievements[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    return $achievements;
}

/**
 * Get completionists - players who completed categories
 */
function MohaaAchievements_GetCompletionists($smcFunc, $limit = 50): array
{
    $players = [];
    $categories = ['basic', 'weapon', 'tactical', 'humiliation', 'shame', 'map', 'dedication', 'secret'];
    
    // Get total achievements per category
    $categoryTotals = [];
    $request = $smcFunc['db_query']('', '
        SELECT category, COUNT(*) AS total
        FROM {db_prefix}mohaa_achievement_defs
        GROUP BY category'
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $categoryTotals[$row['category']] = (int)$row['total'];
    }
    $smcFunc['db_free_result']($request);
    
    // Get players with most category completions
    $request = $smcFunc['db_query']('', '
        SELECT DISTINCT pa.id_member, m.member_name, m.real_name
        FROM {db_prefix}mohaa_player_achievements pa
        JOIN {db_prefix}members m ON m.id_member = pa.id_member
        ORDER BY (
            SELECT COUNT(*) FROM {db_prefix}mohaa_player_achievements pa2 WHERE pa2.id_member = pa.id_member
        ) DESC
        LIMIT {int:limit}',
        ['limit' => $limit]
    );
    
    $memberIds = [];
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $memberIds[] = $row['id_member'];
        $players[$row['id_member']] = [
            'id_member' => $row['id_member'],
            'member_name' => $row['member_name'],
            'real_name' => $row['real_name'],
            'category_progress' => [],
        ];
    }
    $smcFunc['db_free_result']($request);
    
    if (empty($memberIds)) {
        return [];
    }
    
    // Get category progress for each player
    $request = $smcFunc['db_query']('', '
        SELECT pa.id_member, ad.category, COUNT(*) AS completed
        FROM {db_prefix}mohaa_player_achievements pa
        JOIN {db_prefix}mohaa_achievement_defs ad ON ad.id_achievement = pa.id_achievement
        WHERE pa.id_member IN ({array_int:members})
        GROUP BY pa.id_member, ad.category',
        ['members' => $memberIds]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $total = $categoryTotals[$row['category']] ?? 1;
        $percent = round(($row['completed'] / $total) * 100);
        $players[$row['id_member']]['category_progress'][$row['category']] = $percent;
    }
    $smcFunc['db_free_result']($request);
    
    // Sort by number of completed categories, then by total progress
    usort($players, function($a, $b) {
        $aComplete = count(array_filter($a['category_progress'], fn($p) => $p >= 100));
        $bComplete = count(array_filter($b['category_progress'], fn($p) => $p >= 100));
        if ($aComplete !== $bComplete) return $bComplete - $aComplete;
        return array_sum($b['category_progress']) - array_sum($a['category_progress']);
    });
    
    return array_values($players);
}

/**
 * Rarest achievements page
 */
function MohaaAchievements_Rarest(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = 'Rarest Achievements';
    $context['sub_template'] = 'mohaa_rarest_achievements';
    
    // Get total players
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(DISTINCT id_member) FROM {db_prefix}mohaa_player_achievements'
    );
    list($totalPlayers) = $smcFunc['db_fetch_row']($request);
    $smcFunc['db_free_result']($request);
    if ($totalPlayers < 1) $totalPlayers = 1;
    
    // Get achievements with lowest unlock rates
    $request = $smcFunc['db_query']('', '
        SELECT 
            ad.id_achievement, ad.name, ad.description, ad.icon, ad.tier, ad.points,
            COALESCE(unlock_stats.total_unlocks, 0) AS total_unlocks,
            COALESCE((unlock_stats.total_unlocks * 100.0 / {int:total_players}), 0) AS unlock_percent
        FROM {db_prefix}mohaa_achievement_defs ad
        LEFT JOIN (
            SELECT id_achievement, COUNT(*) AS total_unlocks
            FROM {db_prefix}mohaa_player_achievements
            GROUP BY id_achievement
        ) unlock_stats ON unlock_stats.id_achievement = ad.id_achievement
        WHERE ad.is_hidden = 0
        ORDER BY unlock_percent ASC, ad.tier DESC
        LIMIT 20',
        ['total_players' => $totalPlayers]
    );
    
    $context['mohaa_rarest'] = [];
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $context['mohaa_rarest'][] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements',
        'name' => $txt['mohaa_achievements'] ?? 'Achievements',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaachievements;sa=rarest',
        'name' => 'Rarest',
    ];
}

/**
 * Category explorer
 */
function MohaaAchievements_Category(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $category = isset($_GET['cat']) ? $_GET['cat'] : 'basic';
    $validCategories = ['basic', 'weapon', 'tactical', 'humiliation', 'shame', 'map', 'dedication', 'secret', 'hitbox', 'movement', 'objective', 'physics', 'hardcore', 'troll', 'situational'];
    
    if (!in_array($category, $validCategories)) {
        $category = 'basic';
    }
    
    $context['page_title'] = ucfirst($category) . ' Achievements';
    $context['sub_template'] = 'mohaa_achievements_category';
    $context['current_category'] = $category;
    
    // Get achievements in this category
    $request = $smcFunc['db_query']('', '
        SELECT ad.*, COALESCE(unlock_count.total, 0) AS total_unlocks
        FROM {db_prefix}mohaa_achievement_defs ad
        LEFT JOIN (
            SELECT id_achievement, COUNT(*) AS total
            FROM {db_prefix}mohaa_player_achievements
            GROUP BY id_achievement
        ) unlock_count ON unlock_count.id_achievement = ad.id_achievement
        WHERE ad.category = {string:category}
        ORDER BY ad.tier ASC, ad.sort_order ASC',
        ['category' => $category]
    );
    
    $context['mohaa_category_achievements'] = [];
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $context['mohaa_category_achievements'][] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get user progress if logged in
    $context['mohaa_user_progress'] = [];
    if (!$user_info['is_guest']) {
        $request = $smcFunc['db_query']('', '
            SELECT pa.id_achievement, pa.unlocked_date, ap.current_progress
            FROM {db_prefix}mohaa_player_achievements pa
            LEFT JOIN {db_prefix}mohaa_achievement_progress ap 
                ON ap.id_member = pa.id_member AND ap.id_achievement = pa.id_achievement
            WHERE pa.id_member = {int:member}',
            ['member' => $user_info['id']]
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $context['mohaa_user_progress'][$row['id_achievement']] = $row;
        }
        $smcFunc['db_free_result']($request);
    }
}

/**
 * Compare achievements between players
 */
function MohaaAchievements_Compare(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $player1 = isset($_GET['p1']) ? (int)$_GET['p1'] : 0;
    $player2 = isset($_GET['p2']) ? (int)$_GET['p2'] : 0;
    
    if (empty($player1) || empty($player2)) {
        redirectexit('action=mohaaachievements;sa=leaderboard');
        return;
    }
    
    $context['page_title'] = 'Achievement Comparison';
    $context['sub_template'] = 'mohaa_achievements_compare';
    
    // Get player info
    $request = $smcFunc['db_query']('', '
        SELECT id_member, member_name, real_name
        FROM {db_prefix}members
        WHERE id_member IN ({array_int:members})',
        ['members' => [$player1, $player2]]
    );
    
    $context['compare_players'] = [];
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $context['compare_players'][$row['id_member']] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get achievements for both players
    $request = $smcFunc['db_query']('', '
        SELECT ad.*, 
               pa1.unlocked_date AS p1_unlocked,
               pa2.unlocked_date AS p2_unlocked
        FROM {db_prefix}mohaa_achievement_defs ad
        LEFT JOIN {db_prefix}mohaa_player_achievements pa1 
            ON pa1.id_achievement = ad.id_achievement AND pa1.id_member = {int:p1}
        LEFT JOIN {db_prefix}mohaa_player_achievements pa2 
            ON pa2.id_achievement = ad.id_achievement AND pa2.id_member = {int:p2}
        WHERE ad.is_hidden = 0 OR pa1.id_unlock IS NOT NULL OR pa2.id_unlock IS NOT NULL
        ORDER BY ad.category, ad.tier, ad.sort_order',
        ['p1' => $player1, 'p2' => $player2]
    );
    
    $context['compare_achievements'] = [];
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $context['compare_achievements'][] = $row;
    }
    $smcFunc['db_free_result']($request);
}

/**
 * Recent achievements
 */
function MohaaAchievements_Recent(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = 'Recent Achievements';
    $context['sub_template'] = 'mohaa_achievements_recent';
    
    // Initialize API
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $recent = $api->getRecentAchievements() ?? [];
    
    $context['mohaa_recent'] = $recent;
}

/**
 * Profile medals page
 */



/**
 * Profile medals page
 */
function MohaaAchievements_ProfileMedals(int $memID): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    loadTemplate('MohaaAchievements');
    
    $context['page_title'] = 'Medals & Badges';
    $context['sub_template'] = 'mohaa_profile_medals';
    
    // Get member info
    $request = $smcFunc['db_query']('', '
        SELECT member_name, real_name FROM {db_prefix}members WHERE id_member = {int:id}',
        ['id' => $memID]
    );
    $member = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    // Get GUID
    $request = $smcFunc['db_query']('', '
        SELECT player_guid FROM {db_prefix}mohaa_identities 
        WHERE id_member = {int:id}',
        ['id' => $memID]
    );
    $identity = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    $achievements = [];
    $totalPoints = 0;
    
    if ($identity && !empty($identity['player_guid'])) {
        // Initialize API
        require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
        $api = new MohaaStatsAPIClient();
        
        $playerData = $api->getPlayerAchievements($identity['player_guid']);
        if (!empty($playerData['unlocked'])) {
            $achievements = $playerData['unlocked'];
            
            // Calculate points and get definitions if needed
            // API returns full achievement objects in 'unlocked'
            foreach ($achievements as $a) {
                $totalPoints += ($a['points'] ?? 0);
            }
        }
    }
    
    // Get featured (highest tier) achievements
    // Sort by tier desc, date desc
    usort($achievements, function($a, $b) {
        if (($b['tier'] ?? 0) != ($a['tier'] ?? 0)) {
            return ($b['tier'] ?? 0) <=> ($a['tier'] ?? 0);
        }
        return ($b['unlocked_date'] ?? 0) <=> ($a['unlocked_date'] ?? 0);
    });
    
    $featured = array_slice($achievements, 0, 5);
    
    $context['mohaa_profile_medals'] = [
        'member_id' => $memID,
        'member_name' => $member['real_name'] ?: $member['member_name'],
        'achievements' => $achievements,
        'featured' => $featured,
        'total_points' => $totalPoints,
        'count' => count($achievements),
        'is_own' => $memID == $user_info['id'],
    ];
}

/**
 * Award an achievement to a player (called by API/backend)
 */
function MohaaAchievements_Award(int $memberId, string $achievementCode, ?string $matchId = null): bool
{
    global $smcFunc;
    
    // Get achievement by code
    $request = $smcFunc['db_query']('', '
        SELECT id_achievement FROM {db_prefix}mohaa_achievement_defs
        WHERE code = {string:code}',
        ['code' => $achievementCode]
    );
    
    $achievement = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$achievement) {
        return false;
    }
    
    // Check if already unlocked
    $request = $smcFunc['db_query']('', '
        SELECT id_unlock FROM {db_prefix}mohaa_player_achievements
        WHERE id_member = {int:member} AND id_achievement = {int:achievement}',
        ['member' => $memberId, 'achievement' => $achievement['id_achievement']]
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        return false; // Already unlocked
    }
    $smcFunc['db_free_result']($request);
    
    // Award the achievement
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_player_achievements',
        [
            'id_member' => 'int',
            'id_achievement' => 'int',
            'unlocked_date' => 'int',
            'match_id' => 'string',
        ],
        [
            $memberId,
            $achievement['id_achievement'],
            time(),
            $matchId ?? '',
        ],
        ['id_unlock']
    );
    
    return true;
}

/**
 * Update achievement progress
 */
function MohaaAchievements_UpdateProgress(int $memberId, int $achievementId, int $progress): void
{
    global $smcFunc;
    
    $smcFunc['db_query']('', '
        INSERT INTO {db_prefix}mohaa_achievement_progress (id_member, id_achievement, current_progress, last_updated)
        VALUES ({int:member}, {int:achievement}, {int:progress}, {int:now})
        ON DUPLICATE KEY UPDATE current_progress = {int:progress}, last_updated = {int:now}',
        [
            'member' => $memberId,
            'achievement' => $achievementId,
            'progress' => $progress,
            'now' => time(),
        ]
    );
}
