<?php
/**
 * MOHAA Achievements - Enhanced Templates
 * 
 * Advanced achievement displays with:
 * - Tiered visual badges with glow effects
 * - Rarity indicators based on unlock percentage
 * - Animated unlock notifications
 * - Advanced leaderboards (points, rarity, first unlocks)
 * - Category explorer with drill-down
 * - Achievement comparison between players
 *
 * @package MohaaAchievements
 * @version 2.0.0
 */

/**
 * Enhanced Achievement Leaderboards - Multiple Rankings
 */
function template_mohaa_achievements_leaderboard_enhanced()
{
    global $context, $txt, $scripturl;

    $data = $context['mohaa_achievement_leaderboard'] ?? [];
    $activeTab = $context['leaderboard_tab'] ?? 'points';

    echo '
    <div class="mohaa-achievement-leaderboards">
        <div class="leaderboard-header">
            <h2>🏆 Achievement Hall of Fame</h2>
            <p class="subtitle">The most decorated soldiers in the war</p>
        </div>
        
        <div class="leaderboard-tabs">
            <a href="', $scripturl, '?action=mohaaachievements;sa=leaderboard;tab=points" 
               class="tab ', $activeTab === 'points' ? 'active' : '', '">
                <span class="tab-icon">⭐</span> Most Points
            </a>
            <a href="', $scripturl, '?action=mohaaachievements;sa=leaderboard;tab=count" 
               class="tab ', $activeTab === 'count' ? 'active' : '', '">
                <span class="tab-icon">🎖️</span> Most Achievements
            </a>
            <a href="', $scripturl, '?action=mohaaachievements;sa=leaderboard;tab=rarest" 
               class="tab ', $activeTab === 'rarest' ? 'active' : '', '">
                <span class="tab-icon">💎</span> Rarest Collectors
            </a>
            <a href="', $scripturl, '?action=mohaaachievements;sa=leaderboard;tab=first" 
               class="tab ', $activeTab === 'first' ? 'active' : '', '">
                <span class="tab-icon">🥇</span> First Unlocks
            </a>
            <a href="', $scripturl, '?action=mohaaachievements;sa=leaderboard;tab=perfect" 
               class="tab ', $activeTab === 'perfect' ? 'active' : '', '">
                <span class="tab-icon">💯</span> Completionists
            </a>
        </div>';

    switch ($activeTab) {
        case 'count':
            template_leaderboard_count($data['count'] ?? []);
            break;
        case 'rarest':
            template_leaderboard_rarest($data['rarest'] ?? []);
            break;
        case 'first':
            template_leaderboard_first_unlocks($data['first'] ?? []);
            break;
        case 'perfect':
            template_leaderboard_completionists($data['completionists'] ?? []);
            break;
        default:
            template_leaderboard_points($data['points'] ?? []);
    }

    echo '
    </div>';

    template_leaderboard_enhanced_styles();
}

/**
 * Points leaderboard
 */
function template_leaderboard_points($players)
{
    global $scripturl;

    echo '
        <div class="leaderboard-content">
            <div class="leaderboard-podium">';

    // Top 3 podium
    foreach (array_slice($players, 0, 3) as $i => $player) {
        $position = $i + 1;
        $positionClass = ['first', 'second', 'third'][$i];
        $medal = ['🥇', '🥈', '🥉'][$i];

        echo '
                <div class="podium-slot ', $positionClass, '">
                    <div class="podium-medal">', $medal, '</div>
                    <div class="podium-avatar">', strtoupper(substr($player['member_name'] ?? 'U', 0, 1)), '</div>
                    <a href="', $scripturl, '?action=profile;u=', $player['id_member'], '" class="podium-name">
                        ', htmlspecialchars($player['real_name'] ?: $player['member_name']), '
                    </a>
                    <div class="podium-points">', number_format($player['total_points']), ' pts</div>
                    <div class="podium-count">', $player['achievement_count'], ' achievements</div>
                    <div class="podium-tier">';
        template_player_tier_badge($player['total_points']);
        echo '
                    </div>
                </div>';
    }

    echo '
            </div>
            
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th class="player">Player</th>
                        <th class="points">Points</th>
                        <th class="count">Achievements</th>
                        <th class="tier">Tier</th>
                        <th class="featured">Featured Badges</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($players, 3) as $i => $player) {
        $rank = $i + 4;

        echo '
                    <tr>
                        <td class="rank">', $rank, '</td>
                        <td class="player">
                            <a href="', $scripturl, '?action=profile;u=', $player['id_member'], '">
                                ', htmlspecialchars($player['real_name'] ?: $player['member_name']), '
                            </a>
                        </td>
                        <td class="points gold">', number_format($player['total_points']), '</td>
                        <td class="count">', $player['achievement_count'], '</td>
                        <td class="tier">';
        template_player_tier_badge($player['total_points']);
        echo '
                        </td>
                        <td class="featured">';
        if (!empty($player['featured_achievements'])) {
            template_mohaa_achievement_badges_mini($player['featured_achievements'], 3);
        }
        echo '
                        </td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>';
}

/**
 * Achievement count leaderboard
 */
function template_leaderboard_count($players)
{
    global $scripturl;

    echo '
        <div class="leaderboard-content">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th class="player">Player</th>
                        <th class="count">Achievements</th>
                        <th class="percent">Completion</th>
                        <th class="progress">Progress</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($players as $i => $player) {
        $rank = $i + 1;
        $rankClass = $rank <= 3 ? 'rank-' . $rank : '';
        $totalAchievements = 540; // Total available
        $percent = round(($player['achievement_count'] / $totalAchievements) * 100, 1);

        echo '
                    <tr class="', $rankClass, '">
                        <td class="rank">', $rank, '</td>
                        <td class="player">
                            <a href="', $scripturl, '?action=profile;u=', $player['id_member'], '">
                                ', htmlspecialchars($player['real_name'] ?: $player['member_name']), '
                            </a>
                        </td>
                        <td class="count"><strong>', $player['achievement_count'], '</strong> / ', $totalAchievements, '</td>
                        <td class="percent">', $percent, '%</td>
                        <td class="progress">
                            <div class="progress-bar-leaderboard">
                                <div class="progress-fill" style="width:', $percent, '%"></div>
                            </div>
                        </td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>';
}

/**
 * Rarest collectors - players with most rare achievements
 */
function template_leaderboard_rarest($players)
{
    global $scripturl;

    echo '
        <div class="leaderboard-content">
            <div class="rarity-explanation">
                <h4>💎 Rarity Scoring</h4>
                <p>Points awarded based on achievement rarity: Legendary (×100) • Epic (×50) • Rare (×25) • Uncommon (×10) • Common (×1)</p>
            </div>
            
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th class="player">Player</th>
                        <th class="rarity-score">Rarity Score</th>
                        <th class="legendary">🌟 Legendary</th>
                        <th class="epic">💜 Epic</th>
                        <th class="rare">💙 Rare</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($players as $i => $player) {
        $rank = $i + 1;
        $rankClass = $rank <= 3 ? 'rank-' . $rank : '';

        echo '
                    <tr class="', $rankClass, '">
                        <td class="rank">', $rank, '</td>
                        <td class="player">
                            <a href="', $scripturl, '?action=profile;u=', $player['id_member'], '">
                                ', htmlspecialchars($player['real_name'] ?: $player['member_name']), '
                            </a>
                        </td>
                        <td class="rarity-score"><strong>', number_format($player['rarity_score']), '</strong></td>
                        <td class="legendary">', $player['legendary_count'] ?? 0, '</td>
                        <td class="epic">', $player['epic_count'] ?? 0, '</td>
                        <td class="rare">', $player['rare_count'] ?? 0, '</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>';
}

/**
 * First unlocks - who unlocked achievements first
 */
function template_leaderboard_first_unlocks($achievements)
{
    global $scripturl;

    echo '
        <div class="leaderboard-content">
            <div class="first-unlock-grid">';

    foreach ($achievements as $ach) {
        echo '
                <div class="first-unlock-card tier-', $ach['tier'], '">
                    <div class="card-achievement">
                        <span class="ach-icon">', template_achievement_badge_icon($ach['icon'] ?? 'medal_bronze'), '</span>
                        <div class="ach-info">
                            <h4>', htmlspecialchars($ach['name']), '</h4>
                            <p>', htmlspecialchars($ach['description']), '</p>
                        </div>
                    </div>
                    <div class="card-first">
                        <span class="first-label">🥇 First to unlock:</span>
                        <a href="', $scripturl, '?action=profile;u=', $ach['first_member_id'], '" class="first-player">
                            ', htmlspecialchars($ach['first_member_name']), '
                        </a>
                        <span class="first-date">', timeformat($ach['first_unlock_date'], '%b %d, %Y'), '</span>
                    </div>
                    <div class="card-stats">
                        <span class="total-unlocks">', $ach['total_unlocks'], ' total unlocks</span>
                        <span class="unlock-rate">', number_format($ach['unlock_percent'], 1), '% of players</span>
                    </div>
                </div>';
    }

    echo '
            </div>
        </div>';
}

/**
 * Completionists - players with 100% in categories
 */
function template_leaderboard_completionists($players)
{
    global $scripturl;

    $categories = [
        'basic' => ['name' => 'Basic Training', 'icon' => '🎖️'],
        'weapon' => ['name' => 'Weapon Specialist', 'icon' => '🔫'],
        'tactical' => ['name' => 'Tactical & Skill', 'icon' => '🎯'],
        'humiliation' => ['name' => 'Humiliation', 'icon' => '☕'],
        'shame' => ['name' => 'Hall of Shame', 'icon' => '💀'],
        'map' => ['name' => 'Map Mastery', 'icon' => '🗺️'],
        'dedication' => ['name' => 'Dedication', 'icon' => '⏰'],
        'secret' => ['name' => 'Secret', 'icon' => '🔮'],
    ];

    echo '
        <div class="leaderboard-content">
            <table class="leaderboard-table completionist">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th class="player">Player</th>';

    foreach ($categories as $cat => $info) {
        echo '<th class="cat-', $cat, '" title="', $info['name'], '">', $info['icon'], '</th>';
    }

    echo '
                        <th class="total">Total</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($players as $i => $player) {
        $rank = $i + 1;
        $rankClass = $rank <= 3 ? 'rank-' . $rank : '';

        echo '
                    <tr class="', $rankClass, '">
                        <td class="rank">', $rank, '</td>
                        <td class="player">
                            <a href="', $scripturl, '?action=profile;u=', $player['id_member'], '">
                                ', htmlspecialchars($player['real_name'] ?: $player['member_name']), '
                            </a>
                        </td>';

        foreach ($categories as $cat => $info) {
            $complete = ($player['category_progress'][$cat] ?? 0) >= 100;
            echo '<td class="cat-check">', $complete ? '✅' : '⬜', '</td>';
        }

        $completedCats = count(array_filter($player['category_progress'] ?? [], fn($p) => $p >= 100));
        echo '
                        <td class="total"><strong>', $completedCats, '</strong>/', count($categories), '</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>';
}

/**
 * Player tier badge based on points
 */
function template_player_tier_badge($points)
{
    $tiers = [
        ['min' => 50000, 'name' => 'Immortal', 'icon' => '♾️', 'color' => '#ff4444'],
        ['min' => 25000, 'name' => 'Legend', 'icon' => '👑', 'color' => '#ffd700'],
        ['min' => 15000, 'name' => 'Champion', 'icon' => '🏆', 'color' => '#f97316'],
        ['min' => 10000, 'name' => 'Grandmaster', 'icon' => '💎', 'color' => '#a855f7'],
        ['min' => 5000, 'name' => 'Master', 'icon' => '⭐', 'color' => '#ef4444'],
        ['min' => 2500, 'name' => 'Diamond', 'icon' => '💠', 'color' => '#22d3ee'],
        ['min' => 1000, 'name' => 'Platinum', 'icon' => '🔷', 'color' => '#e5e4e2'],
        ['min' => 500, 'name' => 'Gold', 'icon' => '🥇', 'color' => '#ffd700'],
        ['min' => 250, 'name' => 'Silver', 'icon' => '🥈', 'color' => '#c0c0c0'],
        ['min' => 0, 'name' => 'Bronze', 'icon' => '🥉', 'color' => '#cd7f32'],
    ];

    $playerTier = $tiers[count($tiers) - 1];
    foreach ($tiers as $t) {
        if ($points >= $t['min']) {
            $playerTier = $t;
            break;
        }
    }

    echo '<span class="player-tier" style="color:', $playerTier['color'], ';">',
        $playerTier['icon'], ' ', $playerTier['name'],
        '</span>';
}

/**
 * Rarest achievements showcase
 */
function template_mohaa_rarest_achievements()
{
    global $context, $txt, $scripturl;

    $achievements = $context['mohaa_rarest'] ?? [];

    echo '
    <div class="mohaa-rarest-showcase">
        <div class="showcase-header">
            <h2>💎 Rarest Achievements</h2>
            <p class="subtitle">The most exclusive badges - do you have what it takes?</p>
        </div>
        
        <div class="rarest-grid">';

    foreach ($achievements as $ach) {
        $rarityClass = 'common';
        if ($ach['unlock_percent'] <= 0.1) $rarityClass = 'legendary';
        elseif ($ach['unlock_percent'] <= 1) $rarityClass = 'epic';
        elseif ($ach['unlock_percent'] <= 5) $rarityClass = 'rare';
        elseif ($ach['unlock_percent'] <= 20) $rarityClass = 'uncommon';

        echo '
            <div class="rarest-card ', $rarityClass, ' tier-', $ach['tier'], '">
                <div class="card-glow"></div>
                <div class="card-rarity">';
        template_achievement_rarity($ach['unlock_percent']);
        echo '
                </div>
                <div class="card-icon">', template_achievement_badge_icon($ach['icon'] ?? 'medal_bronze'), '</div>
                <h3>', htmlspecialchars($ach['name']), '</h3>
                <p>', htmlspecialchars($ach['description']), '</p>
                <div class="card-stats">
                    <span class="unlock-count">', $ach['total_unlocks'], ' unlocks</span>
                    <span class="points">+', $ach['points'], ' pts</span>
                </div>
                <a href="', $scripturl, '?action=mohaaachievements;sa=achievement;id=', $ach['id_achievement'], '" class="card-link">
                    View Details →
                </a>
            </div>';
    }

    echo '
        </div>
    </div>';

    template_rarest_styles();
}

/**
 * Leaderboard enhanced styles
 */
function template_leaderboard_enhanced_styles()
{
    echo '
    <style>
        /* ============================================
           ENHANCED LEADERBOARDS
           ============================================ */
        
        .mohaa-achievement-leaderboards {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 16px;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        .leaderboard-header {
            text-align: center;
            padding: 40px 20px 20px;
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
        }
        
        .leaderboard-header h2 {
            margin: 0;
            font-size: 2.5em;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .leaderboard-header .subtitle {
            color: #888;
            margin-top: 5px;
        }
        
        /* Tabs */
        .leaderboard-tabs {
            display: flex;
            flex-wrap: wrap;
            background: #0d1b2a;
            border-bottom: 2px solid #333;
        }
        
        .leaderboard-tabs .tab {
            flex: 1;
            padding: 15px 10px;
            text-align: center;
            color: #888;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            min-width: 120px;
        }
        
        .leaderboard-tabs .tab:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }
        
        .leaderboard-tabs .tab.active {
            color: #ffd700;
            border-bottom-color: #ffd700;
            background: rgba(255,215,0,0.1);
        }
        
        .leaderboard-tabs .tab-icon {
            display: block;
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        
        /* Content */
        .leaderboard-content {
            padding: 30px;
        }
        
        /* Podium */
        .leaderboard-podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 40px;
            min-height: 280px;
        }
        
        .podium-slot {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            position: relative;
            transition: transform 0.3s;
        }
        
        .podium-slot:hover {
            transform: translateY(-5px);
        }
        
        .podium-slot.first {
            order: 2;
            background: linear-gradient(180deg, rgba(255,215,0,0.2) 0%, rgba(255,215,0,0.05) 100%);
            border: 2px solid rgba(255,215,0,0.5);
            min-height: 250px;
        }
        
        .podium-slot.second {
            order: 1;
            background: linear-gradient(180deg, rgba(192,192,192,0.2) 0%, rgba(192,192,192,0.05) 100%);
            border: 2px solid rgba(192,192,192,0.5);
            min-height: 200px;
        }
        
        .podium-slot.third {
            order: 3;
            background: linear-gradient(180deg, rgba(205,127,50,0.2) 0%, rgba(205,127,50,0.05) 100%);
            border: 2px solid rgba(205,127,50,0.5);
            min-height: 170px;
        }
        
        .podium-medal {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .podium-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
            margin: 0 auto 10px;
            border: 3px solid #ffd700;
        }
        
        .podium-slot.second .podium-avatar { border-color: #c0c0c0; }
        .podium-slot.third .podium-avatar { border-color: #cd7f32; }
        
        .podium-name {
            display: block;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            margin-bottom: 5px;
        }
        
        .podium-points {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffd700;
        }
        
        .podium-count {
            font-size: 0.85em;
            color: #888;
            margin-top: 5px;
        }
        
        .podium-tier {
            margin-top: 10px;
        }
        
        /* Table */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leaderboard-table th {
            padding: 15px;
            text-align: left;
            background: #0d1b2a;
            color: #ffd700;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #333;
        }
        
        .leaderboard-table td {
            padding: 15px;
            border-bottom: 1px solid #333;
        }
        
        .leaderboard-table tr:hover td {
            background: rgba(255,255,255,0.03);
        }
        
        .leaderboard-table .rank-1 td { background: rgba(255,215,0,0.1); }
        .leaderboard-table .rank-2 td { background: rgba(192,192,192,0.1); }
        .leaderboard-table .rank-3 td { background: rgba(205,127,50,0.1); }
        
        .leaderboard-table .rank { font-weight: bold; color: #888; }
        .leaderboard-table .rank-1 .rank { color: #ffd700; }
        .leaderboard-table .rank-2 .rank { color: #c0c0c0; }
        .leaderboard-table .rank-3 .rank { color: #cd7f32; }
        
        .leaderboard-table .player a { color: #4ecdc4; text-decoration: none; }
        .leaderboard-table .player a:hover { text-decoration: underline; }
        
        .leaderboard-table .points.gold { color: #ffd700; font-weight: bold; }
        
        /* Progress bar in leaderboard */
        .progress-bar-leaderboard {
            height: 8px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
            min-width: 100px;
        }
        
        .progress-bar-leaderboard .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a5d23, #6b8e23);
            border-radius: 4px;
        }
        
        /* Player tier badge */
        .player-tier {
            font-weight: bold;
            font-size: 0.9em;
        }
        
        /* Rarity explanation */
        .rarity-explanation {
            background: rgba(168,85,247,0.1);
            border: 1px solid rgba(168,85,247,0.3);
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .rarity-explanation h4 {
            margin: 0 0 5px;
            color: #a855f7;
        }
        
        .rarity-explanation p {
            margin: 0;
            color: #888;
            font-size: 0.9em;
        }
        
        /* First unlock grid */
        .first-unlock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .first-unlock-card {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #333;
        }
        
        .first-unlock-card .card-achievement {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .first-unlock-card .ach-icon {
            font-size: 2em;
        }
        
        .first-unlock-card .ach-info h4 {
            margin: 0;
            color: #fff;
        }
        
        .first-unlock-card .ach-info p {
            margin: 5px 0 0;
            color: #888;
            font-size: 0.85em;
        }
        
        .first-unlock-card .card-first {
            background: rgba(255,215,0,0.1);
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        
        .first-unlock-card .first-label {
            font-size: 0.8em;
            color: #888;
        }
        
        .first-unlock-card .first-player {
            display: block;
            font-weight: bold;
            color: #ffd700;
            text-decoration: none;
        }
        
        .first-unlock-card .first-date {
            font-size: 0.8em;
            color: #666;
        }
        
        .first-unlock-card .card-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            color: #888;
        }
        
        /* Completionist table */
        .completionist th, .completionist td {
            text-align: center;
        }
        
        .completionist .player { text-align: left; }
        
        .cat-check {
            font-size: 1.2em;
        }
        
        @media (max-width: 768px) {
            .leaderboard-podium {
                flex-direction: column;
                align-items: center;
            }
            
            .podium-slot {
                order: unset !important;
                min-height: auto !important;
                width: 100%;
                max-width: 300px;
            }
            
            .leaderboard-tabs .tab {
                min-width: 80px;
                padding: 10px 5px;
                font-size: 0.85em;
            }
            
            .leaderboard-tabs .tab-icon {
                font-size: 1.2em;
            }
        }
    </style>';
}

/**
 * Rarest achievements styles
 */
function template_rarest_styles()
{
    echo '
    <style>
        .mohaa-rarest-showcase {
            background: linear-gradient(180deg, #1a1a2e 0%, #0f0f1a 100%);
            border-radius: 16px;
            padding: 40px;
            color: #e0e0e0;
        }
        
        .showcase-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .showcase-header h2 {
            margin: 0;
            font-size: 2em;
            color: #b9f2ff;
            text-shadow: 0 0 20px rgba(185,242,255,0.5);
        }
        
        .rarest-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .rarest-card {
            position: relative;
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 25px;
            border: 2px solid #333;
            text-align: center;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .rarest-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .rarest-card .card-glow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        
        .rarest-card.legendary .card-glow { background: linear-gradient(90deg, #ffd700, #ffec8b, #ffd700); animation: shimmer 2s infinite; }
        .rarest-card.epic .card-glow { background: #a855f7; }
        .rarest-card.rare .card-glow { background: #2196f3; }
        .rarest-card.uncommon .card-glow { background: #4caf50; }
        
        @keyframes shimmer {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .rarest-card .card-rarity {
            margin-bottom: 15px;
        }
        
        .rarest-card .card-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .rarest-card h3 {
            margin: 0 0 10px;
            color: #fff;
        }
        
        .rarest-card p {
            margin: 0 0 15px;
            color: #888;
            font-size: 0.9em;
        }
        
        .rarest-card .card-stats {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px solid #333;
            font-size: 0.85em;
            color: #888;
        }
        
        .rarest-card .card-link {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            color: #4ecdc4;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .rarest-card .card-link:hover {
            background: rgba(78,205,196,0.2);
        }
    </style>';
}
