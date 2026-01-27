<?php
/**
 * MOHAA Achievements Widget Templates
 * 
 * Compact achievement displays for War Room dashboard and profiles
 * These widgets provide drill-down links to the full achievements system
 *
 * @package MohaaAchievements
 * @version 2.0.0
 */

/**
 * War Room Achievement Showcase Widget
 * Shows featured achievements, recent unlocks, and progress for dashboard
 */
function template_mohaa_achievement_widget()
{
    global $context, $txt, $scripturl;

    $data = $context['mohaa_stats']['achievement_widget'] ?? $context['mohaa_achievement_widget'] ?? [];

    echo '
    <div class="mohaa-achievement-widget">
        <div class="widget-header">
            <h3>🎖️ Achievement Progress</h3>
            <a href="', $scripturl, '?action=mohaaachievements" class="view-all">View All →</a>
        </div>
        
        <div class="widget-progress-ring">
            <svg viewBox="0 0 100 100" class="progress-ring">
                <circle class="ring-bg" cx="50" cy="50" r="40"/>
                <circle class="ring-fill" cx="50" cy="50" r="40" 
                    stroke-dasharray="', ($data['completion_percent'] ?? 0) * 2.51, ' 251.2"/>
            </svg>
            <div class="ring-center">
                <span class="ring-value">', $data['unlocked'] ?? 0, '</span>
                <span class="ring-total">/ ', $data['total'] ?? 0, '</span>
            </div>
        </div>
        
        <div class="widget-stats">
            <div class="stat">
                <span class="stat-value gold">', number_format($data['total_points'] ?? 0), '</span>
                <span class="stat-label">Points</span>
            </div>
            <div class="stat">
                <span class="stat-value">', $data['completion_percent'] ?? 0, '%</span>
                <span class="stat-label">Complete</span>
            </div>
        </div>';

    // Next achievement to unlock (closest to completion)
    if (!empty($data['next_unlock'])) {
        $next = $data['next_unlock'];
        echo '
        <div class="widget-next-unlock">
            <h4>📌 Almost There!</h4>
            <div class="next-achievement">
                <span class="next-icon">', template_achievement_badge_icon($next['icon'] ?? 'medal_bronze'), '</span>
                <div class="next-info">
                    <span class="next-name">', htmlspecialchars($next['name']), '</span>
                    <div class="next-progress">
                        <div class="progress-bar-mini">
                            <div class="progress-fill" style="width:', $next['progress_percent'], '%;"></div>
                        </div>
                        <span class="progress-text">', number_format($next['current_progress']), '/', number_format($next['requirement_value']), '</span>
                    </div>
                </div>
            </div>
        </div>';
    }

    // Recent unlocks
    if (!empty($data['recent_unlocks'])) {
        echo '
        <div class="widget-recent">
            <h4>🔓 Recent Unlocks</h4>
            <div class="recent-list">';

        foreach (array_slice($data['recent_unlocks'], 0, 3) as $ach) {
            echo '
                <div class="recent-item tier-', $ach['tier'], '">
                    <span class="recent-icon">', template_achievement_badge_icon($ach['icon'] ?? 'medal_bronze'), '</span>
                    <div class="recent-info">
                        <span class="recent-name">', htmlspecialchars($ach['name']), '</span>
                        <span class="recent-date">', timeformat($ach['unlocked_date'], '%b %d'), '</span>
                    </div>
                    <span class="recent-points">+', $ach['points'], '</span>
                </div>';
        }

        echo '
            </div>
        </div>';
    }

    echo '
    </div>';

    template_achievement_widget_styles();
}

/**
 * Mini achievement badge display for leaderboards/player cards
 */
function template_mohaa_achievement_badges_mini($achievements, $max = 5)
{
    global $scripturl;

    if (empty($achievements)) {
        echo '<span class="no-badges">No badges yet</span>';
        return;
    }

    echo '<div class="achievement-badges-mini">';
    
    foreach (array_slice($achievements, 0, $max) as $ach) {
        echo '
                <a href="', $scripturl, '?action=mohaaachievements;sa=achievement;id=', $ach['id_achievement'], '" 
               class="badge-mini tier-', $ach['tier'], '" 
               title="', htmlspecialchars($ach['name']), ' - ', htmlspecialchars($ach['description']), '">
                ', template_achievement_badge_icon($ach['icon'] ?? 'medal_bronze'), '
            </a>';
    }

    if (count($achievements) > $max) {
        echo '<span class="badge-more">+', (count($achievements) - $max), '</span>';
    }

    echo '</div>';
}

/**
 * Achievement tier badge with glow effects
 */
function template_achievement_tier_badge($tier, $size = 'medium')
{
    $tierInfo = [
        1 => ['name' => 'Bronze', 'color' => '#cd7f32', 'glow' => 'rgba(205,127,50,0.5)'],
        2 => ['name' => 'Silver', 'color' => '#c0c0c0', 'glow' => 'rgba(192,192,192,0.5)'],
        3 => ['name' => 'Gold', 'color' => '#ffd700', 'glow' => 'rgba(255,215,0,0.5)'],
        4 => ['name' => 'Platinum', 'color' => '#e5e4e2', 'glow' => 'rgba(229,228,226,0.5)'],
        5 => ['name' => 'Diamond', 'color' => '#b9f2ff', 'glow' => 'rgba(185,242,255,0.5)'],
        6 => ['name' => 'Master', 'color' => '#ff4444', 'glow' => 'rgba(255,68,68,0.5)'],
        7 => ['name' => 'Grandmaster', 'color' => '#a855f7', 'glow' => 'rgba(168,85,247,0.5)'],
        8 => ['name' => 'Champion', 'color' => '#f97316', 'glow' => 'rgba(249,115,22,0.5)'],
    ];

    $t = $tierInfo[$tier] ?? $tierInfo[1];

    echo '
        <span class="tier-badge tier-', $tier, ' size-', $size, '" 
              style="--tier-color:', $t['color'], '; --tier-glow:', $t['glow'], ';">
            ', $t['name'], '
        </span>';
}

/**
 * Achievement badge icon with proper emoji mapping
 */
function template_achievement_badge_icon($icon)
{
    $icons = [
        // Medals
        'medal_bronze' => '🥉', 'medal_silver' => '🥈', 'medal_gold' => '🥇',
        'medal_platinum' => '💎', 'medal_diamond' => '💠',
        'trophy_gold' => '🏆', 'trophy_platinum' => '👑',
        
        // Combat
        'headshot' => '🎯', 'headshot_gold' => '💀', 'precision' => '⊕',
        'longshot' => '📏', 'wallbang' => '💥', 'clutch' => '🔥',
        'surgeon' => '🔪', 'torso' => '🎯', 'full_scan' => '📡',
        
        // Streaks
        'streak_10' => '⚡', 'streak_15' => '⚡⚡', 'streak_25' => '👹',
        'multi_2' => '2️⃣', 'multi_3' => '3️⃣', 'multi_4' => '4️⃣', 'multi_5' => '☠️',
        'onfire' => '🔥', 'killionaire' => '💯',
        
        // Humiliation
        'teabag' => '☕', 'nutshot' => '🥜', 'groin' => '🥜',
        'backstab' => '🗡️', 'backstab2' => '🗡️',
        'airshot' => '✈️', 'airjordan' => '🏀',
        'denied' => '🚫', 'camper' => '⛺',
        'prone' => '🐍', 'snake' => '🐍',
        'jump' => '🐰', 'rabbit' => '🐇',
        'blind' => '😎', 'lowHP' => '❤️‍🩹',
        
        // Shame
        'shame_deaths' => '💀', 'shame_fall' => '⬇️', 'shame_drown' => '🌊',
        'shame_tk' => '🤡', 'shame_quit' => '🚪', 'shame_dominated' => '😭',
        'shame_reload' => '🔄', 'shame_spawn' => '⏱️',
        'kenny' => '💀', 'bot' => '🤖', 'teamkill' => '🔴',
        'cliff' => '🏔️', 'lemming' => '🐁', 'suicide' => '💣',
        'decoy' => '🎯', 'click' => '🔫',
        
        // Maps
        'map_tourist' => '🗺️', 'map_traveler' => '🌍', 'traveler' => '🌍',
        'door' => '🚪', 'door_gold' => '🚪✨',
        'window' => '🪟', 'ladder' => '🪜',
        'distance' => '🏃', 'marathon' => '🏃‍♂️',
        'sewer' => '🐀', 'hometurf' => '🏠', 'cartographer' => '🗺️',
        
        // Time
        'time_bronze' => '⏰', 'time_silver' => '⏰', 'time_gold' => '⏰',
        'time_platinum' => '⏰', 'time_diamond' => '⏰',
        'nightowl' => '🦉', 'weekend' => '📅', 'earlybird' => '🐦',
        'overtime' => '⏱️',
        
        // Dedication
        'founder' => '⭐', 'early_adopter' => '🌟',
        
        // Secret
        'secret_pacifist' => '☮️', 'secret_perfect' => '💯',
        'secret_revenge' => '😈', 'secret_comeback' => '🔄',
        
        // Weapons
        'weapon_thompson' => '🔫', 'weapon_kar98' => '🎯',
        'weapon_garand' => '🔫', 'weapon_mp40' => '🔫',
        'weapon_bar' => '🔫', 'weapon_stg44' => '🔫',
        'weapon_springfield' => '🎯',
        'grenade' => '💣', 'pistol' => '🔫', 'knife' => '🔪',
        'rocket_sniper' => '🚀', 'kobe' => '🏀', 'martyr' => '👻',
        'lumberjack' => '🪓', 'oneclip' => '📎', 'spray' => '💨',
        'quickdraw' => '⚡', 'hoarder' => '📦', 'trigger' => '🎯',
        
        // Movement
        'statue' => '🗿', 'crouch' => '🐅', 'bunny' => '🐰',
        'slide' => '🛝', 'prone' => '🐍', 'vertical' => '📈',
        
        // Objectives
        'ninja' => '🥷', 'buzzer' => '⏰', 'postal' => '📬',
        'driver' => '🚗', 'gatecrasher' => '💥', 'laststand' => '🏆',
        'objective' => '🎯',
        
        // Situational
        'janitor' => '🧹', 'shield' => '🛡️', 'carry' => '💪',
        'avenger' => '⚔️', 'bodyguard' => '🦸', 'ragequit' => '🚪',
        'comeback' => '📈', 'untouchable' => '✨', 'doubledown' => '✌️',
        'overkill' => '💥', 'glass' => '🪟', 'immortal' => '♾️',
        'respawn' => '🔄', 'spawntrap' => '⚠️',
        
        // Physics
        'newton' => '🍎', 'telefrag' => '📍', 'crushed' => '🪨',
        'wallbang' => '💥', 'collateral' => '📌', 'ricochet' => '↩️',
        'gravity' => '⬇️', 'bankshot' => '🎱', 'trajectory' => '📐',
        
        // Hardcore
        'noscope360' => '🎯', 'pistol' => '🔫', 'ironman' => '🦸',
        'aimbot' => '🤖', 'wall' => '🧱', 'sniperelite' => '🎯',
        'onemanarrmy' => '💪', 'flawless' => '✨',
        
        // Misc
        'phantom' => '👻', 'helmet' => '⛑️', 'ankle_biter' => '🦵',
        'pacifist' => '☮️', 'objector' => '🏳️', 'camping' => '⛺', 'tourist' => '📸',
        'rightangle' => '📐', 'faceto' => '👊',
    ];

    return $icons[$icon] ?? '🎖️';
}

/**
 * Rarity indicator based on unlock percentage
 */
function template_achievement_rarity($unlockPercent)
{
    if ($unlockPercent <= 0.1) {
        echo '<span class="rarity legendary">🌟 Legendary (', number_format($unlockPercent, 2), '%)</span>';
    } elseif ($unlockPercent <= 1) {
        echo '<span class="rarity epic">💜 Epic (', number_format($unlockPercent, 1), '%)</span>';
    } elseif ($unlockPercent <= 5) {
        echo '<span class="rarity rare">💙 Rare (', number_format($unlockPercent, 1), '%)</span>';
    } elseif ($unlockPercent <= 20) {
        echo '<span class="rarity uncommon">💚 Uncommon (', number_format($unlockPercent, 0), '%)</span>';
    } else {
        echo '<span class="rarity common">⬜ Common (', number_format($unlockPercent, 0), '%)</span>';
    }
}

/**
 * Achievement widget styles
 */
function template_achievement_widget_styles()
{
    echo '
    <style>
        /* ============================================
           ACHIEVEMENT WIDGET - War Room Integration
           ============================================ */
        
        .mohaa-achievement-widget {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 20px;
            color: #e0e0e0;
            border: 1px solid #333;
        }
        
        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .widget-header h3 {
            margin: 0;
            font-size: 1.1em;
            color: #ffd700;
        }
        
        .widget-header .view-all {
            font-size: 0.85em;
            color: #4ecdc4;
            text-decoration: none;
        }
        
        .widget-header .view-all:hover {
            text-decoration: underline;
        }
        
        /* Progress Ring */
        .widget-progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }
        
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .ring-bg {
            fill: none;
            stroke: #333;
            stroke-width: 8;
        }
        
        .ring-fill {
            fill: none;
            stroke: url(#ring-gradient);
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dasharray 0.5s ease;
        }
        
        .ring-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .ring-value {
            display: block;
            font-size: 1.8em;
            font-weight: bold;
            color: #fff;
        }
        
        .ring-total {
            font-size: 0.9em;
            color: #888;
        }
        
        /* Stats */
        .widget-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            padding: 15px 0;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }
        
        .widget-stats .stat {
            text-align: center;
        }
        
        .widget-stats .stat-value {
            display: block;
            font-size: 1.4em;
            font-weight: bold;
            color: #fff;
        }
        
        .widget-stats .stat-value.gold {
            color: #ffd700;
        }
        
        .widget-stats .stat-label {
            font-size: 0.8em;
            color: #888;
        }
        
        /* Next Unlock */
        .widget-next-unlock {
            margin-bottom: 20px;
        }
        
        .widget-next-unlock h4 {
            margin: 0 0 10px;
            font-size: 0.9em;
            color: #ff9800;
        }
        
        .next-achievement {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,152,0,0.1);
            border-radius: 8px;
            border: 1px solid rgba(255,152,0,0.3);
        }
        
        .next-icon {
            font-size: 2em;
            filter: grayscale(0.5);
        }
        
        .next-info {
            flex: 1;
        }
        
        .next-name {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .progress-bar-mini {
            height: 6px;
            background: #333;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        
        .progress-bar-mini .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff9800, #ff5722);
            border-radius: 3px;
        }
        
        .progress-text {
            font-size: 0.75em;
            color: #888;
        }
        
        /* Recent Unlocks */
        .widget-recent h4 {
            margin: 0 0 10px;
            font-size: 0.9em;
            color: #4ade80;
        }
        
        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .recent-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: rgba(74,222,128,0.05);
            border-radius: 6px;
            border-left: 3px solid #4ade80;
        }
        
        .recent-item.tier-1 { border-left-color: #cd7f32; }
        .recent-item.tier-2 { border-left-color: #c0c0c0; }
        .recent-item.tier-3 { border-left-color: #ffd700; }
        .recent-item.tier-4 { border-left-color: #e5e4e2; }
        .recent-item.tier-5 { border-left-color: #b9f2ff; }
        .recent-item.tier-6 { border-left-color: #ff4444; }
        .recent-item.tier-7 { border-left-color: #a855f7; }
        .recent-item.tier-8 { border-left-color: #f97316; }
        
        .recent-icon {
            font-size: 1.5em;
        }
        
        .recent-info {
            flex: 1;
        }
        
        .recent-name {
            display: block;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .recent-date {
            font-size: 0.75em;
            color: #888;
        }
        
        .recent-points {
            color: #ffd700;
            font-weight: bold;
            font-size: 0.85em;
        }
        
        /* Mini Badges */
        .achievement-badges-mini {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        
        .badge-mini {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 0.9em;
            background: #333;
            border: 2px solid;
            text-decoration: none;
            transition: transform 0.2s;
        }
        
        .badge-mini:hover {
            transform: scale(1.2);
        }
        
        .badge-mini.tier-1 { border-color: #cd7f32; }
        .badge-mini.tier-2 { border-color: #c0c0c0; }
        .badge-mini.tier-3 { border-color: #ffd700; }
        .badge-mini.tier-4 { border-color: #e5e4e2; }
        .badge-mini.tier-5 { border-color: #b9f2ff; }
        .badge-mini.tier-6 { border-color: #ff4444; }
        .badge-mini.tier-7 { border-color: #a855f7; }
        .badge-mini.tier-8 { border-color: #f97316; }
        
        .badge-more {
            font-size: 0.75em;
            color: #888;
            padding: 4px 8px;
        }
        
        /* Tier Badges */
        .tier-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--tier-color);
            color: #000;
            box-shadow: 0 0 8px var(--tier-glow);
        }
        
        .tier-badge.size-small { padding: 1px 6px; font-size: 0.6em; }
        .tier-badge.size-large { padding: 4px 12px; font-size: 0.85em; }
        
        /* Rarity */
        .rarity {
            font-size: 0.75em;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .rarity.legendary { background: rgba(255,215,0,0.2); color: #ffd700; }
        .rarity.epic { background: rgba(168,85,247,0.2); color: #a855f7; }
        .rarity.rare { background: rgba(33,150,243,0.2); color: #2196f3; }
        .rarity.uncommon { background: rgba(76,175,80,0.2); color: #4caf50; }
        .rarity.common { background: rgba(158,158,158,0.2); color: #9e9e9e; }
        
        .no-badges {
            color: #666;
            font-size: 0.85em;
            font-style: italic;
        }
    </style>
    
    <svg style="position:absolute;width:0;height:0;">
        <defs>
            <linearGradient id="ring-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#4a5d23"/>
                <stop offset="100%" stop-color="#6b8e23"/>
            </linearGradient>
        </defs>
    </svg>';
}
