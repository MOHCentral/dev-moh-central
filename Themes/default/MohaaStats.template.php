<?php
/**
 * MOHAA Stats - Main Templates
 *
 * @package MohaaStats
 * @version 1.0.0
 */

/**
 * Main dashboard template - The War Room
 */
function template_mohaa_stats_main()
{
    global $context, $txt, $scripturl, $user_info;
    
    // Load achievement widget template
    if (function_exists('template_mohaa_achievement_widget') === false) {
        loadTemplate('MohaaAchievementsWidget');
    }
    
    echo '
    <div class="mohaa-war-room">
        <div class="war-room-header">
            <div class="header-title">
                <h1>⚔️ THE WAR ROOM</h1>
                <span class="header-subtitle">Medal of Honor Allied Assault - Live Combat Statistics</span>
            </div>
            <div class="header-actions">';
    
    if (!$user_info['is_guest']) {
        echo '
                <a href="', $scripturl, '?action=mohaastats;sa=link" class="button">🔗 Link Identity</a>';
    }
    
    echo '
            </div>
        </div>
        
        <div class="mohaa-stats-grid">';
    
    // Global stats cards - Enhanced with icons and animations
    if (!empty($context['mohaa_stats']['global'])) {
        $stats = $context['mohaa_stats']['global'];
        
        echo '
            <div class="mohaa-stat-cards animated">
                <div class="mohaa-stat-card kills">
                    <div class="card-icon">💀</div>
                    <div class="card-content">
                        <div class="stat-value counter" data-target="', $stats['total_kills'] ?? 0, '">', number_format($stats['total_kills'] ?? 0), '</div>
                        <div class="stat-label">', $txt['mohaa_kills'], '</div>
                    </div>
                </div>
                <div class="mohaa-stat-card players">
                    <div class="card-icon">👥</div>
                    <div class="card-content">
                        <div class="stat-value counter" data-target="', $stats['total_players'] ?? 0, '">', number_format($stats['total_players'] ?? 0), '</div>
                        <div class="stat-label">Soldiers</div>
                    </div>
                </div>
                <div class="mohaa-stat-card matches">
                    <div class="card-icon">🎮</div>
                    <div class="card-content">
                        <div class="stat-value counter" data-target="', $stats['total_matches'] ?? 0, '">', number_format($stats['total_matches'] ?? 0), '</div>
                        <div class="stat-label">', $txt['mohaa_matches_played'], '</div>
                    </div>
                </div>
                <div class="mohaa-stat-card headshots">
                    <div class="card-icon">🎯</div>
                    <div class="card-content">
                        <div class="stat-value counter" data-target="', $stats['total_headshots'] ?? 0, '">', number_format($stats['total_headshots'] ?? 0), '</div>
                        <div class="stat-label">', $txt['mohaa_headshots'], '</div>
                    </div>
                </div>
                <div class="mohaa-stat-card achievements">
                    <div class="card-icon">🏆</div>
                    <div class="card-content">
                        <div class="stat-value counter" data-target="', $stats['total_achievements_unlocked'] ?? 0, '">', number_format($stats['total_achievements_unlocked'] ?? 0), '</div>
                        <div class="stat-label">Achievements Unlocked</div>
                    </div>
                </div>
            </div>';
    }
    
    echo '
            <div class="mohaa-main-content">
                <div class="mohaa-left-column">';
    
    // Top Players
    echo '
                    <div class="mohaa-panel">
                        <h3 class="category_header">', $txt['mohaa_leaderboards'], '</h3>
                        <div class="windowbg">';
    
    if (!empty($context['mohaa_stats']['top_players']['players'])) {
        echo '
                            <table class="mohaa-leaderboard-mini">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Player</th>
                                        <th>', $txt['mohaa_kills'], '</th>
                                    </tr>
                                </thead>
                                <tbody>';
        
        foreach ($context['mohaa_stats']['top_players']['players'] as $i => $player) {
            $rankClass = $i < 3 ? ' rank-' . ($i + 1) : '';
            echo '
                                    <tr>
                                        <td class="rank', $rankClass, '">#', $i + 1, '</td>
                                        <td><a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid']), '">', htmlspecialchars($player['name']), '</a></td>
                                        <td>', number_format($player['kills'] ?? 0), '</td>
                                    </tr>';
        }
        
        echo '
                                </tbody>
                            </table>
                            <div class="mohaa-view-all">
                                <a href="', $scripturl, '?action=mohaastats;sa=leaderboards">', $txt['mohaa_leaderboards'], ' &rarr;</a>
                            </div>';
    } else {
        echo '<p class="centertext">', $txt['mohaa_api_error'], '</p>';
    }
    
    echo '
                        </div>
                    </div>';
    
    // Recent Matches
    echo '
                    <div class="mohaa-panel">
                        <h3 class="category_header">', $txt['mohaa_matches'], '</h3>
                        <div class="windowbg">';
    
    if (!empty($context['mohaa_stats']['recent_matches'])) {
        echo '
                            <ul class="mohaa-match-list">';
        
        foreach ($context['mohaa_stats']['recent_matches'] as $match) {
            echo '
                                <li class="mohaa-match-item">
                                    <a href="', $scripturl, '?action=mohaastats;sa=match;id=', urlencode($match['id']), '">
                                        <span class="match-map">', htmlspecialchars($match['map_name']), '</span>
                                        <span class="match-mode">', htmlspecialchars($match['game_mode']), '</span>
                                        <span class="match-players">', $match['player_count'], ' players</span>
                                        <span class="match-time">', timeformat($match['end_time']), '</span>
                                    </a>
                                </li>';
        }
        
        echo '
                            </ul>
                            <div class="mohaa-view-all">
                                <a href="', $scripturl, '?action=mohaastats;sa=matches">', $txt['mohaa_matches'], ' &rarr;</a>
                            </div>';
    } else {
        echo '<p class="centertext">', $txt['mohaa_api_error'], '</p>';
    }
    
    echo '
                        </div>
                    </div>
                </div>';
    
    // Right column - Live matches AND Achievement Widget
    echo '
                <div class="mohaa-right-column">';
    
    // Achievement Widget - Link to achievements system
    if (!$user_info['is_guest'] && !empty($context['mohaa_stats']['achievement_widget'])) {
        echo '
                    <div class="mohaa-panel">';
        template_mohaa_achievement_widget();
        echo '
                    </div>';
    } else {
        // Show global achievement stats for guests
        echo '
                    <div class="mohaa-panel">
                        <h3 class="category_header">🏆 Achievements</h3>
                        <div class="windowbg">
                            <div class="achievement-promo">
                                <div class="promo-icon">🎖️</div>
                                <p>Over <strong>540+</strong> achievements to unlock!</p>
                                <p class="promo-tiers">10 Tiers: Bronze → Immortal</p>
                                <a href="', $scripturl, '?action=mohaachievements" class="button">Explore Achievements</a>
                            </div>
                        </div>
                    </div>';
    }
    
    // Live Matches Panel
    echo '
                    <div class="mohaa-panel">
                        <h3 class="category_header">🔴 ', $txt['mohaa_live'], '</h3>
                        <div class="windowbg" id="mohaa-live-matches">';
    
    template_mohaa_live_matches_content();
    
    echo '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh live matches every 15 seconds
        setInterval(function() {
            fetch("', $scripturl, '?action=mohaaapi;endpoint=live")
                .then(r => r.json())
                .then(data => {
                    // Update live matches panel
                    document.getElementById("mohaa-live-matches").innerHTML = 
                        data.length ? data.map(m => `<div class="live-match">...</div>`).join("") : "', $txt['mohaa_no_live_matches'], '";
                });
        }, 15000);
    </script>';
}

/**
 * Live matches content (can be used standalone or in dashboard)
 */
function template_mohaa_live_matches_content()
{
    global $context, $txt, $scripturl;
    
    if (empty($context['mohaa_stats']['live_matches'])) {
        echo '<p class="centertext">', $txt['mohaa_no_live_matches'], '</p>';
        return;
    }
    
    foreach ($context['mohaa_stats']['live_matches'] as $match) {
        echo '
            <div class="mohaa-live-match">
                <div class="live-indicator"><span class="pulse"></span> LIVE</div>
                <div class="live-server">', htmlspecialchars($match['server_name']), '</div>
                <div class="live-map">', htmlspecialchars($match['map_name']), '</div>
                <div class="live-players">', $match['player_count'], '/', $match['max_players'], ' ', $txt['mohaa_players_online'], '</div>';
        
        if (!empty($match['team_match'])) {
            echo '
                <div class="live-score">
                    <span class="team-allies">', $match['allies_score'], '</span>
                    <span class="vs">vs</span>
                    <span class="team-axis">', $match['axis_score'], '</span>
                </div>';
        }
        
        echo '
            </div>';
    }
}

/**
 * Live matches page (standalone page for mohaastats;sa=live)
 */
function template_mohaa_live()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="mohaa-live-page">
        <h2 class="category_header">', $txt['mohaa_live'] ?? 'Live Matches', '</h2>';
    
    if (empty($context['mohaa_live_matches'])) {
        echo '
        <div class="windowbg centertext">
            <p>', $txt['mohaa_no_live_matches'] ?? 'No live matches at the moment.', '</p>
            <p><a href="', $scripturl, '?action=mohaaservers">', $txt['mohaa_browse_servers'] ?? 'Browse Servers', '</a></p>
        </div>';
    } else {
        echo '
        <div class="windowbg">';
        
        foreach ($context['mohaa_live_matches'] as $match) {
            echo '
            <div class="mohaa-live-match">
                <div class="live-indicator"><span class="pulse"></span> LIVE</div>
                <div class="live-server">', htmlspecialchars($match['server_name'] ?? 'Unknown Server'), '</div>
                <div class="live-map">', htmlspecialchars($match['map_name'] ?? 'Unknown Map'), '</div>
                <div class="live-players">', $match['player_count'] ?? 0, '/', $match['max_players'] ?? 0, ' ', $txt['mohaa_players_online'] ?? 'Players', '</div>';
            
            if (!empty($match['team_match'])) {
                echo '
                <div class="live-score">
                    <span class="team-allies">', $match['allies_score'] ?? 0, '</span>
                    <span class="vs">vs</span>
                    <span class="team-axis">', $match['axis_score'] ?? 0, '</span>
                </div>';
            }
            
            echo '
            </div>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>
    
    <style>
        .mohaa-live-page { margin: 1em 0; }
        .mohaa-live-match { 
            display: flex; 
            align-items: center; 
            gap: 1em; 
            padding: 1em; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .mohaa-live-match:last-child { border-bottom: none; }
        .live-indicator { 
            color: #f44336; 
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .pulse {
            width: 10px;
            height: 10px;
            background: #f44336;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .live-server { font-weight: bold; flex: 1; }
        .live-map { color: #aaa; }
        .live-players { color: #4caf50; }
        .live-score { display: flex; gap: 0.5em; font-size: 1.2em; }
        .team-allies { color: #4caf50; }
        .team-axis { color: #f44336; }
        .vs { color: #888; }
    </style>
    
    <script>
        // Auto-refresh live matches every 15 seconds
        setTimeout(function() {
            location.reload();
        }, 15000);
    </script>';
}

/**
 * Leaderboards template
 */
function template_mohaa_leaderboards()
{
    global $context, $txt, $scripturl;
    
    $currentStat = $context['mohaa_leaderboard']['stat'];
    $currentPeriod = $context['mohaa_leaderboard']['period'];
    
    echo '
    <div class="mohaa-leaderboards">
        <h2 class="category_header">', $txt['mohaa_leaderboards'], '</h2>
        
        <div class="windowbg mohaa-filters">
            <form action="', $scripturl, '?action=mohaastats;sa=leaderboards" method="get">
                <input type="hidden" name="action" value="mohaastats">
                <input type="hidden" name="sa" value="leaderboards">
                
                <label>', $txt['mohaa_leaderboard_stat'], ':
                    <select name="stat" onchange="this.form.submit()">
                        <option value="kills"', $currentStat === 'kills' ? ' selected' : '', '>', $txt['mohaa_stat_kills'], '</option>
                        <option value="kd"', $currentStat === 'kd' ? ' selected' : '', '>', $txt['mohaa_stat_kd'], '</option>
                        <option value="score"', $currentStat === 'score' ? ' selected' : '', '>', $txt['mohaa_stat_score'], '</option>
                        <option value="headshots"', $currentStat === 'headshots' ? ' selected' : '', '>', $txt['mohaa_stat_headshots'], '</option>
                        <option value="accuracy"', $currentStat === 'accuracy' ? ' selected' : '', '>', $txt['mohaa_stat_accuracy'], '</option>
                    </select>
                </label>
                
                <label>', $txt['mohaa_leaderboard_period'], ':
                    <select name="period" onchange="this.form.submit()">
                        <option value="all"', $currentPeriod === 'all' ? ' selected' : '', '>', $txt['mohaa_period_all'], '</option>
                        <option value="month"', $currentPeriod === 'month' ? ' selected' : '', '>', $txt['mohaa_period_month'], '</option>
                        <option value="week"', $currentPeriod === 'week' ? ' selected' : '', '>', $txt['mohaa_period_week'], '</option>
                        <option value="day"', $currentPeriod === 'day' ? ' selected' : '', '>', $txt['mohaa_period_day'], '</option>
                    </select>
                </label>
            </form>
        </div>
        
        <div class="windowbg">';
    
    $players = $context['mohaa_leaderboard']['players']['players'] ?? [];

    if (!empty($players)) {
        echo '
            <table class="mohaa-leaderboard table_grid">
                <thead>
                    <tr class="title_bar">
                        <th class="rank">', $txt['mohaa_rank'], '</th>
                        <th class="player">Player</th>
                        <th class="stat">', $txt['mohaa_' . $currentStat] ?? ucfirst($currentStat), '</th>
                        <th class="kills">', $txt['mohaa_kills'], '</th>
                        <th class="deaths">', $txt['mohaa_deaths'], '</th>
                        <th class="kd">', $txt['mohaa_kd_ratio'], '</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($players as $i => $player) {
            $rank = $i + 1;
            $rankClass = $rank <= 3 ? ' rank-' . $rank : '';
            
            echo '
                    <tr class="windowbg">
                        <td class="rank', $rankClass, '">#', $rank, '</td>
                        <td class="player">
                            <a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid']), '">', htmlspecialchars($player['name']), '</a>
                        </td>
                        <td class="stat">', mohaa_format_stat($player['value'] ?? 0, $currentStat), '</td>
                        <td class="kills">', number_format($player['kills'] ?? 0), '</td>
                        <td class="deaths">', number_format($player['deaths'] ?? 0), '</td>
                        <td class="kd">', number_format($player['kd'] ?? 0, 2), '</td>
                    </tr>';
        }
        
        echo '
                </tbody>
            </table>';
        
        // Pagination
        if (!empty($context['page_index'])) {
            echo '
            <div class="pagesection">
                <div class="pagelinks">', $context['page_index'], '</div>
            </div>';
        }
    } else {
        echo '<p class="centertext">', $txt['mohaa_api_error'], '</p>';
    }
    
    echo '
        </div>
    </div>';
}

/**
 * Player profile template
 */
function template_mohaa_player()
{
    global $context, $txt, $scripturl;
    
    $player = $context['mohaa_player']['info'];
    
    echo '
    <div class="mohaa-player-profile">
        <h2 class="category_header">', htmlspecialchars($player['name']), '</h2>
        
        <div class="mohaa-player-header windowbg">
            <div class="player-avatar">
                <span class="avatar-letter">', strtoupper(substr($player['name'], 0, 1)), '</span>
            </div>
            <div class="player-info">
                <h3>', htmlspecialchars($player['name']), '</h3>
                <div class="player-meta">
                    <span class="player-rank">', $txt['mohaa_rank'], ' #', $player['rank'] ?? 'N/A', '</span>';
    
    if (!empty($player['verified'])) {
        echo '
                    <span class="verified-badge">', $txt['mohaa_verified_player'], '</span>';
    }
    
    echo '
                    <span class="last-seen">', $txt['mohaa_last_seen'], ': ', timeformat($player['last_active'] ?? time()), '</span>
                </div>
            </div>
        </div>
        
        <div class="mohaa-stat-cards">
            <div class="mohaa-stat-card">
                <div class="stat-value">', number_format($player['kills'] ?? 0), '</div>
                <div class="stat-label">', $txt['mohaa_kills'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', number_format($player['deaths'] ?? 0), '</div>
                <div class="stat-label">', $txt['mohaa_deaths'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value kd-', ($player['kd'] ?? 0) >= 1 ? 'positive' : 'negative', '">', number_format($player['kd'] ?? 0, 2), '</div>
                <div class="stat-label">', $txt['mohaa_kd_ratio'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', number_format($player['headshots'] ?? 0), '</div>
                <div class="stat-label">', $txt['mohaa_headshots'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', number_format($player['accuracy'] ?? 0, 1), '%</div>
                <div class="stat-label">', $txt['mohaa_accuracy'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', number_format($player['matches'] ?? 0), '</div>
                <div class="stat-label">', $txt['mohaa_matches_played'], '</div>
            </div>
        </div>';
    
    // Tabs
    echo '
        <div class="mohaa-tabs">
            <button class="tab-button active" data-tab="overview">', $txt['mohaa_player_overview'], '</button>
            <button class="tab-button" data-tab="weapons">', $txt['mohaa_player_weapons'], '</button>
            <button class="tab-button" data-tab="matches">', $txt['mohaa_player_matches'], '</button>
            <button class="tab-button" data-tab="achievements">', $txt['mohaa_player_achievements'], '</button>
        </div>';
    
    // Tab content - Overview
    echo '
        <div class="mohaa-tab-content" id="tab-overview">
            <div class="windowbg">
                <h4>Performance Chart</h4>
                <div id="player-performance-chart"></div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var matches = ' . json_encode(array_reverse($context['mohaa_player']['matches'] ?? [])) . ';
                if (matches.length > 0) {
                    var labels = matches.map(m => m.map_name);
                    var kds = matches.map(m => parseFloat(m.kd).toFixed(2));
                    var kills = matches.map(m => parseInt(m.kills));

                    var options = {
                        series: [{
                            name: "K/D Ratio",
                            type: "line",
                            data: kds
                        }, {
                            name: "Kills",
                            type: "column",
                            data: kills
                        }],
                        chart: {
                            height: 300,
                            type: "line",
                            toolbar: { show: false }
                        },
                        stroke: { width: [3, 0], curve: "smooth" },
                        plotOptions: { bar: { borderRadius: 4, columnWidth: "40%" } },
                        dataLabels: { enabled: false },
                        labels: labels,
                        xaxis: { labels: { show: false } }, // Hide map names if too many
                        colors: ["#FFA000", "#1976D2"],
                        yaxis: [{
                            title: { text: "K/D Ratio", style: { color: "#FFA000" } },
                            labels: { style: { colors: "#FFA000" } },
                            min: 0
                        }, {
                            opposite: true,
                            title: { text: "Kills", style: { color: "#1976D2" } },
                            labels: { style: { colors: "#1976D2" } }
                        }],
                        grid: { borderColor: "#f1f1f1" },
                        tooltip: { theme: "light" }
                    };

                    new ApexCharts(document.querySelector("#player-performance-chart"), options).render();
                } else {
                    document.getElementById("player-performance-chart").innerHTML = "<p class=\'centertext\'>Not enough data for performance chart.</p>";
                }
            });
            </script>
            </div>
        </div>';
    
    // Tab content - Weapons
    echo '
        <div class="mohaa-tab-content" id="tab-weapons" style="display:none;">
            <div class="windowbg">';
    
    if (!empty($context['mohaa_player']['weapons'])) {
        echo '
                <table class="mohaa-weapons-table table_grid">
                    <thead>
                        <tr class="title_bar">
                            <th>Weapon</th>
                            <th>', $txt['mohaa_kills'], '</th>
                            <th>', $txt['mohaa_headshots'], '</th>
                            <th>', $txt['mohaa_accuracy'], '</th>
                            <th>HS Rate</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($context['mohaa_player']['weapons'] as $weapon) {
            echo '
                        <tr class="windowbg">
                            <td>', htmlspecialchars($weapon['name']), '</td>
                            <td>', number_format($weapon['kills']), '</td>
                            <td>', number_format($weapon['headshots']), '</td>
                            <td>', number_format($weapon['accuracy'], 1), '%</td>
                            <td>', number_format($weapon['hs_rate'] ?? 0, 1), '%</td>
                        </tr>';
        }
        
        echo '
                    </tbody>
                </table>';
    }
    
    echo '
            </div>
        </div>';
    
    // Tab content - Matches
    echo '
        <div class="mohaa-tab-content" id="tab-matches" style="display:none;">
            <div class="windowbg">';
    
    if (!empty($context['mohaa_player']['matches'])) {
        echo '
                <ul class="mohaa-match-history">';
        
        foreach ($context['mohaa_player']['matches'] as $match) {
            $kdClass = ($match['kd'] ?? 0) >= 1 ? 'positive' : 'negative';
            
            echo '
                    <li class="match-item">
                        <a href="', $scripturl, '?action=mohaastats;sa=match;id=', urlencode($match['match_id']), '">
                            <div class="match-kd ', $kdClass, '">', number_format($match['kd'] ?? 0, 1), '</div>
                            <div class="match-details">
                                <span class="match-map">', htmlspecialchars($match['map_name']), '</span>
                                <span class="match-stats">', $match['kills'], 'K / ', $match['deaths'], 'D</span>
                            </div>
                            <div class="match-result">';
            
            if (isset($match['is_win'])) {
                echo $match['is_win'] ? '<span class="win">WIN</span>' : '<span class="loss">LOSS</span>';
            }
            
            echo '
                            </div>
                            <div class="match-time">', timeformat($match['played_at']), '</div>
                        </a>
                    </li>';
        }
        
        echo '
                </ul>';
    }
    
    echo '
            </div>
        </div>';
    
    // Tab content - Achievements
    echo '
        <div class="mohaa-tab-content" id="tab-achievements" style="display:none;">
            <div class="windowbg mohaa-achievements-grid">';
    
    if (!empty($context['mohaa_player']['achievements'])) {
        foreach ($context['mohaa_player']['achievements'] as $achievement) {
            $unlockedClass = $achievement['unlocked'] ? 'unlocked' : 'locked';
            
            echo '
                <div class="achievement-card ', $unlockedClass, '">
                    <div class="achievement-icon">', $achievement['unlocked'] ? '🏆' : '🔒', '</div>
                    <div class="achievement-info">
                        <h5>', htmlspecialchars($achievement['name']), '</h5>
                        <p>', htmlspecialchars($achievement['description']), '</p>';
            
            if ($achievement['unlocked']) {
                echo '<span class="unlocked-date">', timeformat($achievement['unlocked_at']), '</span>';
            } elseif (!empty($achievement['progress'])) {
                echo '<div class="progress-bar"><div class="progress" style="width:', $achievement['progress'], '%"></div></div>';
            }
            
            echo '
                    </div>
                </div>';
        }
    }
    
    echo '
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll(".tab-button").forEach(btn => {
            btn.addEventListener("click", function() {
                document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
                document.querySelectorAll(".mohaa-tab-content").forEach(c => c.style.display = "none");
                this.classList.add("active");
                document.getElementById("tab-" + this.dataset.tab).style.display = "block";
            });
        });
    </script>';
}

/**
 * Link identity template
 */
function template_mohaa_link_identity()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="mohaa-link-identity">
        <h2 class="category_header">', $txt['mohaa_link_identity'], '</h2>
        
        <div class="windowbg">
            <h3>', $txt['mohaa_linked_identities'], '</h3>';
    
    if (!empty($context['mohaa_identities'])) {
        echo '
            <table class="table_grid">
                <thead>
                    <tr class="title_bar">
                        <th>Player Name</th>
                        <th>GUID</th>
                        <th>Linked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($context['mohaa_identities'] as $identity) {
            echo '
                    <tr class="windowbg">
                        <td>', htmlspecialchars($identity['player_name']), '</td>
                        <td><code>', htmlspecialchars($identity['player_guid']), '</code></td>
                        <td>', timeformat($identity['linked_date']), '</td>
                        <td>
                            <form action="', $scripturl, '?action=mohaastats;sa=link" method="post" style="display:inline;">
                                <input type="hidden" name="action_type" value="unlink">
                                <input type="hidden" name="identity_id" value="', $identity['id_identity'], '">
                                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                                <button type="submit" class="button" onclick="return confirm(\'', $txt['mohaa_unlink_confirm'], '\')">', $txt['mohaa_unlink'], '</button>
                            </form>
                        </td>
                    </tr>';
        }
        
        echo '
                </tbody>
            </table>';
    } else {
        echo '<p>', $txt['mohaa_no_identities'], '</p>';
    }
    
    echo '
        </div>
        
        <div class="windowbg">
            <h3>', $txt['mohaa_generate_token'], '</h3>
            <p>Generate a token to authenticate from the game client.</p>';
    
    if (!empty($context['mohaa_token'])) {
        echo '
            <div class="mohaa-token-box">
                <p>', $txt['mohaa_token_instructions'], '</p>
                <code class="token">login ', htmlspecialchars($context['mohaa_token']), '</code>
                <p class="expires">', $txt['mohaa_token_expires'], ': ', $context['mohaa_token_expires'], 's</p>
            </div>';
    } else {
        echo '
            <form action="', $scripturl, '?action=mohaastats;sa=link" method="post">
                <input type="hidden" name="action_type" value="generate_token">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                <button type="submit" class="button">', $txt['mohaa_generate_token'], '</button>
            </form>';
    }
    
    echo '
        </div>
        
        <div class="windowbg">
            <h3>', $txt['mohaa_generate_claim'], '</h3>
            <p>Generate a claim code to permanently link your game identity.</p>';
    
    if (!empty($context['mohaa_claim_code'])) {
        echo '
            <div class="mohaa-claim-box">
                <p>', $txt['mohaa_claim_instructions'], '</p>
                <code class="claim-code">claim ', htmlspecialchars($context['mohaa_claim_code']), '</code>
            </div>';
    } else {
        echo '
            <form action="', $scripturl, '?action=mohaastats;sa=link" method="post">
                <input type="hidden" name="action_type" value="generate_claim">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                <button type="submit" class="button">', $txt['mohaa_generate_claim'], '</button>
            </form>';
    }
    
    echo '
        </div>
    </div>';
}

/**
 * Helper: Format stat value based on type
 */
function mohaa_format_stat($value, $type)
{
    switch ($type) {
        case 'kd':
        case 'accuracy':
            return number_format($value, 2);
        case 'playtime':
            return floor($value / 3600) . 'h';
        default:
            return number_format($value);
    }
}


