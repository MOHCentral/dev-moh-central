<?php
/**
 * MOHAA Enhanced Stats Dashboard - War Room
 * Hybrid Design: Modern Grid Layout + SMF Integration
 *
 * @package MohaaPlayers
 * @version 2.3.0
 */

// Helper function to format playtime (hours and minutes)
if (!function_exists('format_playtime')) {
    function format_playtime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        return $minutes . 'm';
    }
}

/**
 * Enhanced Player Dashboard - The War Room
 */
function template_mohaa_war_room()
{
    global $context, $txt, $scripturl, $user_info;

    $data = $context['mohaa_dashboard'];
    $player = $data['player_stats'] ?? [];
    $member = $data['member'] ?? [];

    // Inject Modern CSS for Dashboard
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --mohaa-accent: #4a6b8a; /* Soft blue-grey */
            --mohaa-success: #4caf50;
            --mohaa-warning: #ff9800;
            --mohaa-danger: #f44336;
            --mohaa-card-bg: rgba(255,255,255,0.05); /* Slight tint for cards */
        }
        
        .mohaa-dashboard-container {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin-bottom: 20px;
        }

        /* Stats Grid System */
        .mohaa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Dashboard Card Style */
        .stat-card {
            background: var(--mohaa-card-bg); /* Fallback or override if windowbg is not enough */
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--mohaa-accent);
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: inherit; /* Inherit from theme */
            opacity: 0.9;
        }
        
        /* Header Profile */
        .profile-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(0,0,0,0.1), rgba(0,0,0,0));
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .rank-icon { font-size: 3.5em; line-height: 1; }
        
        .profile-info h1 { margin: 0; font-size: 2em; line-height: 1.2; }
        .profile-meta { opacity: 0.7; font-size: 0.9em; }
        .tag-badge { 
            background: var(--mohaa-accent); 
            color: #fff; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-weight: bold;
        }
        
        .header-stats {
            display: flex;
            gap: 15px;
            margin-left: auto;
        }
        
        .mini-stat {
            text-align: center;
            padding: 10px 15px;
            border-radius: 6px;
            background: rgba(0,0,0,0.2); /* Darker for contrast */
            min-width: 80px;
        }
        
        .mini-stat .value { display: block; font-size: 1.4em; font-weight: bold; }
        .mini-stat .label { font-size: 0.7em; text-transform: uppercase; opacity: 0.8; }
        
        /* Tabs */
        .mohaa-tabs {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid var(--mohaa-accent);
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .mohaa-tab {
            padding: 12px 20px;
            background: rgba(0,0,0,0.1);
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .mohaa-tab:hover { background: rgba(0,0,0,0.2); text-decoration: none; }
        .mohaa-tab.active {
            background: var(--mohaa-accent);
            color: #fff;
        }

        /* Component Specifics */
        .gauge-svg { width: 100%; height: auto; max-height: 150px; }
        
        .weapon-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .weapon-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .map-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .streak-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .streak-item { text-align: center; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 6px; }
        
        /* Clean Tables */
        .clean-table { width: 100%; border-collapse: collapse; }
        .clean-table th { text-align: left; padding: 12px; border-bottom: 2px solid rgba(0,0,0,0.1); opacity: 0.7; }
        .clean-table td { padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .clean-table tr:hover td { background: rgba(0,0,0,0.02); }
    </style>
    
    <div class="mohaa-dashboard-container">
        <!-- Header -->
        <div class="windowbg profile-header">
            <div class="rank-icon">
                ', template_war_room_rank_icon($player['kills'] ?? 0), '
            </div>
            <div class="profile-info">
                <h1>', htmlspecialchars($member['real_name'] ?? $member['member_name'] ?? 'Soldier'), '</h1>
                <div class="profile-meta">
                    <span class="tag-badge">', htmlspecialchars($player['clan_tag'] ?? 'N/A'), '</span>
                    <span>ELO: <strong>', number_format($player['elo'] ?? 0), '</strong></span>
                </div>
            </div>
            
            <div class="header-stats">
                <div class="mini-stat">
                    <span class="value">'.number_format($player['kills'] ?? 0).'</span>
                    <span class="label">Kills</span>
                </div>
                <div class="mini-stat">
                    <span class="value">'.number_format($player['deaths'] ?? 0).'</span>
                    <span class="label">Deaths</span>
                </div>
                <div class="mini-stat">
                    <span class="value" style="color: '.(($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1) >= 1 ? 'var(--mohaa-success)' : 'var(--mohaa-danger)').'">
                        '.number_format(($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1), 2).'
                    </span>
                    <span class="label">K/D</span>
                </div>
                <div class="mini-stat separator" style="border-left: 1px solid rgba(255,255,255,0.1); margin: 0 10px; padding-left: 10px;"></div>
                <div class="mini-stat">
                    <span class="value" style="color: var(--mohaa-success);">'.number_format($player['matches_won'] ?? 0).'</span>
                    <span class="label">Wins</span>
                </div>
                <div class="mini-stat">
                    <span class="value" style="color: var(--mohaa-danger);">'.number_format(($player['matches_played'] ?? 0) - ($player['matches_won'] ?? 0)).'</span>
                    <span class="label">Losses</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mohaa-tabs">
            <a href="#" onclick="showTab(\'peak\'); return false;" class="mohaa-tab">⚡ Peak</a>
            <a href="#" onclick="showTab(\'combat\'); return false;" class="mohaa-tab active">⚔️ Combat</a>
            <a href="#" onclick="showTab(\'signature\'); return false;" class="mohaa-tab">🎯 Signature</a>
            <a href="#" onclick="showTab(\'weapons\'); return false;" class="mohaa-tab">🔫 Armoury</a>
            <a href="#" onclick="showTab(\'movement\'); return false;" class="mohaa-tab">🏃 Movement</a>
            <a href="#" onclick="showTab(\'gameflow\'); return false;" class="mohaa-tab">🎮 Game</a>
            <a href="#" onclick="showTab(\'gametypes\'); return false;" class="mohaa-tab">🕹️ Game Types</a>
            <a href="#" onclick="showTab(\'interaction\'); return false;" class="mohaa-tab">🗣️ Interaction</a>
            <a href="#" onclick="showTab(\'maps\'); return false;" class="mohaa-tab">🗺️ Maps</a>
            <a href="#" onclick="showTab(\'matches\'); return false;" class="mohaa-tab">📊 Matches</a>
            <a href="#" onclick="showTab(\'achievements\'); return false;" class="mohaa-tab">🏆 Medals</a>
        </div>
        
        <!-- ======================= PEAK PERFORMANCE TAB ======================= -->
        <div id="tab-peak" class="tab-content" style="display: none;">
            ', template_war_room_peak_performance_content($data), '
        </div>
        
        <!-- ======================= SIGNATURE MOVES TAB ======================= -->
        <div id="tab-signature" class="tab-content" style="display: none;">
            ', template_war_room_signature_content($data), '
        </div>
        
        <!-- ======================= COMBAT TAB ======================= -->
        <div id="tab-combat" class="tab-content" style="display: block;">
            <div class="mohaa-grid">
                <!-- Performance Trend (Wide) -->
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>Performance Trend (Last 20 Matches)</h3>
                    <div id="chart-performance" style="min-height: 250px;"></div>
                </div>

                <!-- K/D Gauge -->
                <div class="windowbg stat-card">
                    <h3>Performance Gauge</h3>
                    <div style="text-align: center; flex: 1; display: flex; align-items: center; justify-content: center;">
                        ', template_war_room_kdr_gauge_content($player), '
                    </div>
                </div>
                
                <!-- Hit Silhouette -->
                <div class="windowbg stat-card">
                    <h3>Hit Distribution</h3>
                    ', template_war_room_silhouette_content($player), '
                </div>
                
                <!-- Kill Streaks -->
                <div class="windowbg stat-card">
                    <h3>Kill Streaks</h3>
                    ', template_war_room_streaks_content($player), '
                </div>
                
                <!-- Accuracy -->
                <div class="windowbg stat-card">
                    <h3>Accuracy</h3>
                    ', template_war_room_accuracy_content($player, $data), '
                </div>
                
                <!-- Damage Dealt vs Taken -->
                <div class="windowbg stat-card">
                    <h3>Damage Output</h3>
                    ', template_war_room_damage_content($player), '
                </div>

                <!-- Grenade Stats -->
                <div class="windowbg stat-card" style="grid-column: span 2;">
                    <h3>💣 Grenade Efficiency</h3>
                    ', template_war_room_grenade_content($player), '
                </div>
                
                <!-- Special Stats -->
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>Special Achievements</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        ', template_war_room_special_stats_content($player), '
                    </div>
                </div>
                
                <!-- Skill Spider (Radar Chart) -->
                <div class="windowbg stat-card">
                    <h3>🕷️ Skill Profile</h3>
                    <div id="chart-skill-spider" style="min-height: 280px;"></div>
                </div>
                
                <!-- Recent Achievements (Horizontal Scroll) -->
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>🏆 Recent Achievements</h3>
                    <div class="achievements-scroll" style="display: flex; gap: 15px; overflow-x: auto; padding: 10px 0;">
                        ';
                        
                $achievements = $player['achievements'] ?? [];
                if (empty($achievements)) {
                    echo '<div style="opacity: 0.6; padding: 20px; text-align: center; width: 100%;">No achievements yet. Keep playing!</div>';
                } else {
                    foreach (array_slice($achievements, 0, 10) as $ach) {
                        $tierIcon = match($ach['tier'] ?? 1) {
                            1 => '🟫', 2 => '⬜', 3 => '🟨', 4 => '💎', 5 => '💠',
                            default => '🏅'
                        };
                        echo '
                        <div style="min-width: 120px; text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px; flex-shrink: 0;">
                            <div style="font-size: 2em;">', $tierIcon, '</div>
                            <div style="font-weight: bold; margin-top: 5px; font-size: 0.9em;">', htmlspecialchars($ach['name'] ?? 'Unknown'), '</div>
                            <div style="font-size: 0.7em; opacity: 0.7;">', htmlspecialchars($ach['description'] ?? ''), '</div>
                        </div>';
                    }
                }
                echo '
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <a href="', $scripturl, '?action=medals" style="font-size: 0.9em;">View All Medals →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= WEAPONS TAB ======================= -->
        <div id="tab-weapons" class="tab-content" style="display: none;">
            <div class="windowbg stat-card" style="margin-bottom: 20px;">
                <h3>Weapon Usage Distribution</h3>
                <div id="chart-weapons" style="min-height: 300px;"></div>
            </div>
            <div class="windowbg stat-card">
                <h3>Weapon Mastery</h3>
                ', template_war_room_weapons_content($player['weapons'] ?? []), '
            </div>
        </div>
        
        <!-- ======================= MOVEMENT TAB ======================= -->
        <div id="tab-movement" class="tab-content" style="display: none;">
            <div class="mohaa-grid">
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>🏃 Distance Stats</h3>
                    ', template_war_room_distance_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>🧍 Stance Analysis</h3>
                    ', template_war_room_stance_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>🦘 Jump Stats</h3>
                    ', template_war_room_jumps_content($player), '
                </div>
            </div>
        </div>
        
        <!-- ======================= GAME FLOW TAB ======================= -->
        <div id="tab-gameflow" class="tab-content" style="display: none;">
            <div class="mohaa-grid">
                <div class="windowbg stat-card">
                    <h3>🏆 Win/Loss Record</h3>
                    ', template_war_room_winloss_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>🔄 Rounds & Games</h3>
                    ', template_war_room_rounds_content($player), '
                </div>
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>🎯 Objectives</h3>
                    ', template_war_room_objectives_content($player), '
                </div>
                
                <!-- Vehicle Stats Section -->
                ', template_war_room_vehicle_section($data), '
                
                <!-- Bot Stats Section -->
                ', template_war_room_bot_section($data), '
            </div>
        </div>
        
        <!-- ======================= INTERACTION TAB ======================= -->
        <div id="tab-interaction" class="tab-content" style="display: none;">
            ', template_war_room_interaction_content($player), '
            
            <!-- World Interaction Stats -->
            ', template_war_room_world_section($data), '
        </div>

        <!-- ======================= GAMETYPES TAB ======================= -->
        <div id="tab-gametypes" class="tab-content" style="display: none;">
            <div class="windowbg stat-card">
                <h3>Game Type Performance</h3>
                <div style="overflow-x: auto;">
                    <table class="clean-table" style="width: 100%; text-align: center;">
                        <thead>
                            <tr>
                                <th style="text-align: left;">Game Type</th>
                                <th>Matches</th>
                                <th>Wins</th>
                                <th>Losses</th>
                                <th>Win %</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>';
                        
                $gametypes = $data['gametype_stats'] ?? [];
                if (empty($gametypes)) {
                    echo '<tr><td colspan="6" style="padding: 20px; opacity: 0.6;">No game type data available yet.</td></tr>';
                } else {
                    foreach ($gametypes as $gt) {
                        $winRate = $gt['win_rate'] ?? 0;
                        $barColor = $winRate > 60 ? 'var(--mohaa-success)' : ($winRate > 40 ? 'var(--mohaa-warning)' : 'var(--mohaa-danger)');
                        
                        echo '
                        <tr>
                            <td style="text-align: left; font-weight: bold;">', htmlspecialchars(strtoupper($gt['gametype'])), '</td>
                            <td>', number_format($gt['matches_played']), '</td>
                            <td style="color: var(--mohaa-success);">', number_format($gt['matches_won']), '</td>
                            <td style="color: var(--mohaa-danger);">', number_format($gt['matches_lost']), '</td>
                            <td>', number_format($winRate, 1), '%</td>
                            <td style="width: 30%;">
                                <div style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                    <div style="width: ', $winRate, '%; height: 100%; background: ', $barColor, ';"></div>
                                </div>
                            </td>
                        </tr>';
                    }
                }
                echo '
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="mohaa-grid">
                <div class="windowbg stat-card">
                    <h3>Win Distribution</h3>
                    <div id="chart-gametype-wins" style="min-height: 250px;"></div>
                </div>
                <!-- Add more charts if needed -->
            </div>
        </div>

        
        <!-- ======================= MAPS TAB ======================= -->
        <div id="tab-maps" class="tab-content" style="display: none;">
            <div class="windowbg stat-card">
                <h3>Map Performance</h3>
                ', template_war_room_maps_content($player['maps'] ?? [], $player), '
            </div>
        </div>
        
         <!-- ======================= ACHIEVEMENTS TAB ======================= -->
        <div id="tab-achievements" class="tab-content" style="display: none;">
             <div class="windowbg stat-card">
                <h3>Unlocked Achievements</h3>
                ', template_war_room_achievements_content($data['mohaa_my']['achievements'] ?? []), '
            </div>
        </div>
        
         <!-- ======================= MATCHES TAB ======================= -->
        <div id="tab-matches" class="tab-content" style="display: none;">
             <div class="windowbg stat-card">
                <h3>Recent Match History</h3>
                <div style="overflow-x: auto;">
                    ', template_war_room_matches_content($player['recent_matches'] ?? []), '
                </div>
            </div>
        </div>

    </div>

    <!-- Pass Data to JS -->
    <script>
        window.mohaaData = ' . json_encode($context['mohaa_dashboard'], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE) . ';
        
        document.addEventListener("DOMContentLoaded", function() {
            try {
                initWarRoomCharts();
            } catch (e) {
                console.error("Error initializing charts:", e);
            }
        });
        
        function initWarRoomCharts() {
            const data = window.mohaaData || {};
            if (!data) {
                console.warn("No mohaaData available");
                return;
            }
            const player = data.player_stats || {};
            const perf = player.performance || []; // Expecting array of {kd: float, played_at: timestamp}
            
            // 1. Performance Trend (Area Chart)
            const perfCtx = document.querySelector("#chart-performance");
            if (perfCtx && perf.length > 0) {
                const options = {
                    series: [{
                        name: "K/D Ratio",
                        data: perf.map(m => parseFloat(m.kd).toFixed(2))
                    }],
                    chart: {
                        type: "area",
                        height: 250,
                        toolbar: { show: false },
                        background: "transparent",
                        animations: { enabled: true, easing: "easeinout", speed: 800 }
                    },
                    colors: ["#4a6b8a"],
                    fill: {
                        type: "gradient",
                        gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1, stops: [0, 90, 100] }
                    },
                    dataLabels: { enabled: false },
                    stroke: { curve: "smooth", width: 2 },
                    xaxis: {
                        categories: perf.map(m => new Date(m.played_at * 1000).toLocaleDateString()),
                        labels: { style: { colors: "#888", fontSize: "10px" } }
                    },
                    yaxis: {
                        labels: { style: { colors: "#888" } },
                        title: { text: "K/D Ratio", style: { color: "#888" } }
                    },
                    theme: { mode: "dark" },
                    grid: { borderColor: "#444", strokeDashArray: 4 },
                    tooltip: { theme: "dark" }
                };
                new ApexCharts(perfCtx, options).render();
            } else if (perfCtx) {
                perfCtx.innerHTML = "<p class=\'centertext\' style=\'padding-top: 80px; opacity: 0.6;\'>Play more matches to see your trend!</p>";
            }
            
            // 2. Weapon Distribution (Donut)
            const weapCtx = document.querySelector("#chart-weapons");
            const weapons = player.weapons || {}; // Object: name -> stats
            // Convert object to array for sorting
            const weaponArr = Array.isArray(weapons) ? weapons : Object.entries(weapons)
                .map(([k, v]) => ({name: k, kills: v.kills}))
                .filter(w => w.kills > 0)
                .sort((a, b) => b.kills - a.kills)
                .slice(0, 8); // Top 8
                
            if (weapCtx && weaponArr.length > 0) {
                const options = {
                    series: weaponArr.map(w => parseInt(w.kills)),
                    labels: weaponArr.map(w => w.name),
                    chart: { type: "donut", height: 300, background: "transparent" },
                    plotOptions: { 
                        pie: { 
                            donut: { 
                                size: "70%",
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: "Total Kills",
                                        color: "#fff",
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        }
                                    }
                                }
                            } 
                        } 
                    },
                    stroke: { show: false },
                    theme: { mode: "dark", palette: "palette2" }, // Using palette2 for variety
                    legend: { position: "bottom", labels: { colors: "#fff" } },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(weapCtx, options).render();
            } else if (weapCtx) {
                 weapCtx.innerHTML = "<p class=\'centertext\' style=\'padding-top: 100px; opacity: 0.6;\'>No weapon data recorded yet.</p>";
            }
            
            // 3. Map Analysis (Radar)
            const mapCtx = document.querySelector("#chart-maps");
            const maps = player.maps || {};
            // Filter maps with data
            const mapArr = Array.isArray(maps) ? maps : Object.entries(maps)
                .map(([name, stats]) => ({
                    name: name.split("/").pop().replace(/^obj_|dm_/, ""), 
                    winRate: ((stats.wins || 0) / Math.max(1, (stats.matches || stats.kills/10))) * 100, // Approx matches if missing
                    kills: stats.kills || 0
                }))
                .filter(m => m.kills > 5) // Lower threshold
                .sort((a, b) => b.kills - a.kills)
                .slice(0, 6);
                
            if (mapCtx && mapArr.length > 0) {
                const options = {
                    series: [{
                        name: "Win Rate %",
                        data: mapArr.map(m => Math.min(100, parseFloat(m.winRate).toFixed(1)))
                    }],
                    chart: { type: "radar", height: 350, background: "transparent", toolbar: { show: false } },
                    xaxis: { 
                        categories: mapArr.map(m => m.name), 
                        labels: { 
                            style: { 
                                colors: ["#fff", "#fff", "#fff", "#fff", "#fff", "#fff"],
                                fontSize: "11px"
                            } 
                        } 
                    },
                    stroke: { width: 2, colors: ["#4caf50"] },
                    fill: { opacity: 0.2, colors: ["#4caf50"] },
                    markers: { size: 4, colors: ["#fff"], strokeColors: "#4caf50", strokeWidth: 2 },
                    theme: { mode: "dark" },
                    yaxis: { max: 100, tickAmount: 4, labels: { style: { colors: "#888" } } },
                    tooltip: { theme: "dark" }
                };
                new ApexCharts(mapCtx, options).render();
            } else if (mapCtx) {
                 mapCtx.innerHTML = "<p class=\'centertext\' style=\'padding-top: 100px; opacity: 0.6;\'>Not enough map data yet.</p>";
            }
            
            // 4. Skill Spider (Radar) - NEW
            const spiderCtx = document.querySelector("#chart-skill-spider");
            if (spiderCtx) {
                // Calculate skill values from player stats (0-100 scale)
                const accuracy = Math.min(100, (player.accuracy || 0) * 2.5); // 40% acc = 100 score
                const aggression = Math.min(100, ((player.kills || 0) / Math.max(1, player.playtime_hours || 1)) * 10); // Kills per hour
                const survival = Math.min(100, (player.kd_ratio || 1) * 30); // 3.0 KD = 90
                const movement = Math.min(100, ((player.distance_km || 0) / Math.max(1, player.playtime_hours || 1)) * 5); // KM per hour
                const clutch = Math.min(100, ((player.clutch_wins || 0) / Math.max(1, (player.clutch_total || 1))) * 100); // Clutch win %
                
                const options = {
                    series: [{
                        name: "You",
                        data: [accuracy, aggression, survival, movement, clutch]
                    }],
                    chart: { 
                        type: "radar", 
                        height: 280, 
                        background: "transparent", 
                        toolbar: { show: false }
                    },
                    xaxis: { 
                        categories: ["Accuracy", "Aggression", "Survival", "Movement", "Clutch"],
                        labels: { 
                            style: { 
                                colors: ["#4caf50", "#ff9800", "#2196f3", "#9c27b0", "#f44336"],
                                fontSize: "12px",
                                fontWeight: "bold"
                            } 
                        }
                    },
                    stroke: { width: 2, colors: ["#4a6b8a"] },
                    fill: { opacity: 0.3, colors: ["#4a6b8a"] },
                    markers: { size: 4, colors: ["#fff"], strokeColors: "#4a6b8a", strokeWidth: 2 },
                    theme: { mode: "dark" },
                    yaxis: { max: 100, tickAmount: 4, labels: { style: { colors: "#888" } } },
                    tooltip: { theme: "dark", y: { formatter: (val) => Math.round(val) + "%" } }
                };
                new ApexCharts(spiderCtx, options).render();
            }
        }
        
        function showTab(tabName) {
            try {
                // Hide all tabs
                var content = document.getElementsByClassName("tab-content");
                for (var i = 0; i < content.length; i++) {
                    content[i].style.display = "none";
                }
                // Show selected
                var targetTab = document.getElementById("tab-" + tabName);
                if (targetTab) {
                    targetTab.style.display = "block";
                } else {
                    console.error("Tab not found: tab-" + tabName);
                    return;
                }
                
                // Update buttons
                var buttons = document.getElementsByClassName("mohaa-tab");
                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].classList.remove("active");
                    var onclick = buttons[i].getAttribute("onclick");
                    if (onclick && onclick.indexOf(tabName) !== -1) {
                        buttons[i].classList.add("active");
                    }
                }
                
                // Lazy load tab content if needed
                lazyLoadTab(tabName);
            } catch (e) {
                console.error("Error switching tab:", e);
            }
        }
        
        // Lazy loading state
        var loadedTabs = {};
        
        // Lazy load function for Peak and Signature tabs
        function lazyLoadTab(tabName) {
            // Only lazy load specific tabs
            if (tabName !== "peak" && tabName !== "signature") {
                return;
            }
            
            // Skip if already loaded
            if (loadedTabs[tabName]) {
                return;
            }
            
            var tabEl = document.getElementById("tab-" + tabName);
            if (!tabEl) return;
            
            // Mark as loading
            loadedTabs[tabName] = "loading";
            
            // Build the AJAX URL
            var url = "' . $scripturl . '?action=mohaalazyload;tab=" + tabName;
            
            fetch(url)
                .then(function(response) {
                    if (!response.ok) {
                        console.error("Fetch failed status:", response.status, response.statusText);
                        throw new Error("Network error: " + response.status);
                    }
                    return response.text().then(function(text) {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error("JSON Parse Error:", e, "Response text:", text);
                            throw new Error("Invalid JSON");
                        }
                    });
                })
                .then(function(data) {
                    console.log("Tab data loaded:", tabName, data);
                    loadedTabs[tabName] = "loaded";
                    renderLazyTab(tabName, data, tabEl);
                })
                .catch(function(err) {
                    console.error("Failed to load tab:", tabName, err);
                    loadedTabs[tabName] = false; // Allow retry
                    tabEl.innerHTML = "<div style=\"text-align: center; padding: 40px; color: #f44336;\"><div style=\"font-size: 2em;\">⚠️</div><div>Failed to load data. <a href=\"javascript:void(0)\" onclick=\"loadedTabs[\'" + tabName + "\']=false;showTab(\'" + tabName + "\')\">Retry</a></div></div>";
                });
        }
        
        // Render lazy-loaded tab content
        function renderLazyTab(tabName, data, tabEl) {
            if (tabName === "peak") {
                renderPeakTab(data, tabEl);
            } else if (tabName === "signature") {
                renderSignatureTab(data, tabEl);
            }
        }
        
        // Render Peak Performance tab
        function renderPeakTab(data, container) {
            var peak = data.peak_performance || data || {};
            var bestConditions = peak.best_conditions || {};
            
            if (!peak || Object.keys(peak).length === 0) {
                container.innerHTML = "<div style=\"text-align: center; padding: 40px; opacity: 0.7;\"><div style=\"font-size: 3em; margin-bottom: 15px;\">📊</div><div>Peak performance analysis requires more match data.</div></div>";
                return;
            }
            
            var html = "<div class=\"mohaa-grid\">";
            
            // Best Conditions Summary
            html += "<div class=\"windowbg stat-card\" style=\"grid-column: 1 / -1; background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));\">";
            html += "<h3 style=\"text-align: center; margin-bottom: 20px;\">⚡ Your Optimal Conditions</h3>";
            html += "<div style=\"display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;\">";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">🕐</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #4caf50;\">" + (bestConditions.best_hour_label || "Evening") + "</div>";
            html += "<div style=\"opacity: 0.7;\">Best Time</div></div>";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">📅</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #2196f3;\">" + (bestConditions.best_day || "Weekend") + "</div>";
            html += "<div style=\"opacity: 0.7;\">Best Day</div></div>";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">🗺️</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #ff9800;\">" + (bestConditions.best_map || "Unknown") + "</div>";
            html += "<div style=\"opacity: 0.7;\">Best Map</div></div>";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">⏱️</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #9c27b0;\">" + (bestConditions.optimal_session_mins || 45) + " min</div>";
            html += "<div style=\"opacity: 0.7;\">Optimal Session</div></div>";
            
            html += "</div></div>"; // End grid, end card
            
            html += "</div>"; // End mohaa-grid
            
            container.innerHTML = html;
        }
        
        // Render Signature Moves tab
        function renderSignatureTab(data, container) {
            var combo = data.combo_metrics || data || {};
            var signature = combo.signature || {};
            var playStyle = signature.play_style || "Soldier";
            
            if (!combo || Object.keys(combo).length === 0) {
                container.innerHTML = "<div style=\"text-align: center; padding: 40px; opacity: 0.7;\"><div style=\"font-size: 3em; margin-bottom: 15px;\">🎯</div><div>Signature analysis requires more gameplay data.</div></div>";
                return;
            }
            
            var icons = {
                "Rusher": "🏃", "Sniper": "🎯", "Tactician": "🧠", "Support": "🛡️",
                "Objective": "🎖️", "Lone Wolf": "🐺", "Aggressor": "⚔️", "All-Rounder": "⭐"
            };
            var icon = icons[playStyle] || "🎖️";
            
            var html = "<div class=\"mohaa-grid\">";
            
            // Play Style Hero Section
            html += "<div class=\"windowbg stat-card\" style=\"grid-column: 1 / -1; text-align: center; background: linear-gradient(135deg, rgba(156, 39, 176, 0.1), rgba(33, 150, 243, 0.1));\">";
            html += "<div style=\"font-size: 5em; margin: 20px 0;\">" + icon + "</div>";
            html += "<div style=\"font-size: 2em; font-weight: bold; text-transform: uppercase;\">" + playStyle + "</div>";
            html += "</div>";
            
            // Stats cards
            var moveCombat = combo.movement_combat || {};
            html += "<div class=\"windowbg stat-card\">";
            html += "<h3>🏃 Movement + Combat</h3>";
            html += renderProgressBar("Run & Gun Index", moveCombat.run_gun_index || 0, "#ff9800");
            html += renderProgressBar("Bunny Hop Efficiency", moveCombat.bunny_hop_efficiency || 0, "#4caf50");
            html += "</div>";
            
            html += "<div class=\"windowbg stat-card\">";
            html += "<h3>🎯 Signature Metrics</h3>";
            html += renderProgressBar("Clutch Rate", signature.clutch_rate || 0, "#f44336");
            html += renderProgressBar("First Blood Rate", signature.first_blood_rate || 0, "#ff5722");
            html += "</div>";
            
            html += "</div>"; // End grid
            
            container.innerHTML = html;
        }
        
        function renderProgressBar(label, value, color) {
            var pct = Math.min(100, Math.max(0, value));
            return "<div style=\"margin-bottom: 12px;\">" +
                "<div style=\"display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;\">" +
                "<span>" + label + "</span><strong style=\"color: " + color + ";\">" + value.toFixed(1) + "</strong></div>" +
                "<div style=\"height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;\">" +
                "<div style=\"width: " + pct + "%; height: 100%; background: " + color + "; border-radius: 4px;\"></div>" +
                "</div></div>";
        }
        
        // Make showTab available globally
        window.showTab = showTab;
        
        // =============================================================================
        // DRILL-DOWN SYSTEM - Click any stat for deeper breakdown
        // =============================================================================
        var drilldownModal = null;
        
        function initDrilldown() {
            // Attach click handlers to all drilldown-stat elements
            document.querySelectorAll(".drilldown-stat").forEach(function(el) {
                el.addEventListener("click", function(e) {
                    e.preventDefault();
                    var stat = this.getAttribute("data-stat");
                    var dimension = this.getAttribute("data-dimension");
                    if (stat && dimension) {
                        openDrilldown(stat, dimension);
                    }
                });
            });
        }
        
        function openDrilldown(stat, dimension) {
            // Create modal if not exists
            if (!drilldownModal) {
                drilldownModal = document.createElement("div");
                drilldownModal.id = "drilldown-modal";
                drilldownModal.innerHTML = "<div class=\"drilldown-overlay\" onclick=\"closeDrilldown()\"></div>" +
                    "<div class=\"drilldown-content\">" +
                    "<div class=\"drilldown-header\">" +
                    "<h3 id=\"drilldown-title\">Loading...</h3>" +
                    "<button onclick=\"closeDrilldown()\" class=\"drilldown-close\">&times;</button>" +
                    "</div>" +
                    "<div id=\"drilldown-body\" style=\"padding: 20px; max-height: 60vh; overflow-y: auto;\"></div>" +
                    "</div>";
                document.body.appendChild(drilldownModal);
            }
            
            // Show modal
            drilldownModal.style.display = "block";
            document.getElementById("drilldown-title").textContent = "Loading " + stat + " by " + dimension + "...";
            document.getElementById("drilldown-body").innerHTML = "<div style=\"text-align: center; padding: 40px;\"><div class=\"loading-spinner\"></div><div>Analyzing data...</div></div>";
            
            // Fetch drilldown data
            var playerGuid = "' . ($data['player_stats']['guid'] ?? '') . '";
            var url = "' . $scripturl . '?action=mohaadrilldown;stat=" + encodeURIComponent(stat) + ";dimension=" + encodeURIComponent(dimension) + ";guid=" + encodeURIComponent(playerGuid);
            
            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    renderDrilldownData(stat, dimension, data);
                })
                .catch(function(err) {
                    console.error("Drilldown error:", err);
                    document.getElementById("drilldown-body").innerHTML = "<div style=\"text-align: center; padding: 40px; color: #f44336;\">Failed to load data. Please try again.</div>";
                });
        }
        
        function renderDrilldownData(stat, dimension, data) {
            var title = stat.replace(/_/g, " ").toUpperCase() + " by " + dimension.toUpperCase();
            document.getElementById("drilldown-title").textContent = title;
            
            var breakdown = data.breakdown || [];
            if (breakdown.length === 0) {
                document.getElementById("drilldown-body").innerHTML = "<div style=\"text-align: center; padding: 40px; opacity: 0.7;\">No data available for this breakdown.</div>";
                return;
            }
            
            var html = "<table class=\"clean-table\" style=\"width: 100%;\">";
            html += "<thead><tr><th style=\"text-align: left;\">" + dimension.charAt(0).toUpperCase() + dimension.slice(1) + "</th><th style=\"text-align: right;\">Count</th><th style=\"text-align: right;\">%</th></tr></thead>";
            html += "<tbody>";
            
            var total = breakdown.reduce(function(sum, item) { return sum + (item.value || 0); }, 0);
            
            breakdown.forEach(function(item) {
                var pct = total > 0 ? (item.value / total * 100).toFixed(1) : 0;
                html += "<tr>";
                html += "<td style=\"font-weight: bold;\">" + (item.label || "Unknown") + "</td>";
                html += "<td style=\"text-align: right;\">" + (item.value || 0).toLocaleString() + "</td>";
                html += "<td style=\"text-align: right; color: #4caf50;\">" + pct + "%</td>";
                html += "</tr>";
            });
            
            html += "</tbody></table>";
            
            // Add chart if more than 3 items
            if (breakdown.length > 2 && breakdown.length <= 20) {
                html += "<div id=\"drilldown-chart\" style=\"margin-top: 20px; min-height: 250px;\"></div>";
                
                setTimeout(function() {
                    var chartEl = document.getElementById("drilldown-chart");
                    if (chartEl && typeof ApexCharts !== "undefined") {
                        var labels = breakdown.map(function(item) { return item.label || "Unknown"; });
                        var values = breakdown.map(function(item) { return item.value || 0; });
                        
                        new ApexCharts(chartEl, {
                            chart: { type: "bar", height: 250, toolbar: { show: false }, background: "transparent" },
                            series: [{ name: stat, data: values }],
                            xaxis: { categories: labels, labels: { style: { colors: "#888" } } },
                            colors: ["#4caf50"],
                            theme: { mode: "dark" },
                            plotOptions: { bar: { borderRadius: 4, horizontal: labels.length > 8 } }
                        }).render();
                    }
                }, 100);
            }
            
            document.getElementById("drilldown-body").innerHTML = html;
        }
        
        function closeDrilldown() {
            if (drilldownModal) {
                drilldownModal.style.display = "none";
            }
        }
        
        // Initialize drilldown on page load
        document.addEventListener("DOMContentLoaded", initDrilldown);
        window.closeDrilldown = closeDrilldown;
    </script>
    
    <style>
        /* Drill-down Modal Styles */
        #drilldown-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
        }
        .drilldown-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
        }
        .drilldown-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--mohaa-card-bg, #1e2a3a);
            border-radius: 12px;
            min-width: 400px;
            max-width: 90%;
            max-height: 80vh;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .drilldown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .drilldown-header h3 {
            margin: 0;
            color: #fff;
        }
        .drilldown-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5em;
            cursor: pointer;
            opacity: 0.7;
        }
        .drilldown-close:hover {
            opacity: 1;
        }
        .drilldown-stat {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .drilldown-stat:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #4caf50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    ';
}

// =========================================================================
// HELPER FUNCTIONS (Pure Content Generation, Minimal Styling)
// =========================================================================

function template_war_room_kdr_gauge_content($player) {
    $kdr = ($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1);
    $percent = min(100, ($kdr / 5) * 100);
    $offset = 251.2 - (251.2 * $percent / 100);
    
    return '
    <div style="position: relative; width: 220px;">
        <svg viewBox="0 0 200 120" class="gauge-svg">
            <path d="M20,100 A80,80 0 0,1 180,100" fill="none" class="gauge-bg" stroke="rgba(128,128,128,0.2)" stroke-width="15" stroke-linecap="round"/>
            <path d="M20,100 A80,80 0 0,1 180,100" fill="none" stroke="var(--mohaa-accent)" stroke-width="15" stroke-linecap="round" stroke-dasharray="251.2" stroke-dashoffset="'.$offset.'"/>
            <text x="100" y="85" font-size="2.2em" font-weight="bold" text-anchor="middle" fill="currentColor">'.number_format($kdr, 2).'</text>
            <text x="100" y="105" font-size="0.8em" text-anchor="middle" opacity="0.6" fill="currentColor">Ratio</text>
        </svg>
    </div>';
}

function template_war_room_silhouette_content($player) {
    $kills = max(1, $player['kills'] ?? 1);
    
    // Calculation functions
    $calcPct = function($val, $total) {
        return $total > 0 ? round(($val / $total) * 100, 1) : 0;
    };
    
    // Outgoing (Hits Dealt) - Use actual API data only, no fabrication
    $head = $player['headshots'] ?? 0;
    $torso = $player['torso_kills'] ?? 0; 
    $limbs = $player['limb_kills'] ?? 0;
    
    // Calculate percentages from actual hitbox data only
    // If no hitloc tracking, values stay at 0 (shows "No Data" rather than fake percentages)
    $hitboxTotal = $head + $torso + $limbs;
    
    $outHeadPct = $calcPct($head, $hitboxTotal);
    $outTorsoPct = $calcPct($torso, $hitboxTotal);
    $outLimbPct = $calcPct($limbs, $hitboxTotal);

    // Incoming (Hits Taken) - extracting from 'deaths' or using placeholders if specific hitloc data missing
    $deaths = max(1, $player['deaths'] ?? 1);
    // Note: API might not provide 'headshots_received' yet, simulating or checking standard fields
    // If fields exist use them, else estimate or show 0
    $inHead = $player['headshots_received'] ?? 0; // Hypothetical field
    $inTorso = $player['torso_deaths'] ?? 0;
    $inLimbs = $player['limb_deaths'] ?? 0;
    
    // If no specific death data, calculate 'unknown' remainder
    $knownDeaths = $inHead + $inTorso + $inLimbs;
    if ($knownDeaths == 0) {
        // Fallback/Placeholder if data missing: Estimate standard distribution or show empty
        // Showing empty/grey for accuracy if data isn't tracked yet
        $inHeadPct = 0; $inTorsoPct = 0; $inLimbPct = 0;
    } else {
        $inHeadPct = $calcPct($inHead, $deaths);
        $inTorsoPct = $calcPct($inTorso, $deaths);
        $inLimbPct = $calcPct($inLimbs, $deaths);
    }
    
    $renderMan = function($title, $hPct, $tPct, $lPct) {
        // Opacity based on percentage (min 0.2 for visibility)
        $hOp = max(0.1, min(1, $hPct/100 * 2)); // Amplify for visibility
        $tOp = max(0.1, min(1, $tPct/100 * 1.5));
        $lOp = max(0.1, min(1, $lPct/100 * 1.5));
        
        return '
        <div style="text-align: center; flex: 1;">
            <h4 style="margin: 0 0 10px 0; font-size: 0.9em; text-transform: uppercase; color: var(--mohaa-accent);">'.$title.'</h4>
            <div style="display: flex; gap: 15px; justify-content: center; align-items: center;">
                
                <!-- Improved Silhouette SVG -->
                <svg viewBox="0 0 140 220" style="width: 100px; height: 160px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));">
                    <!-- Head -->
                    <circle cx="70" cy="25" r="18" fill="#f44336" fill-opacity="'.$hOp.'" />
                    
                    <!-- Torso -->
                    <rect x="50" y="48" width="40" height="70" rx="4" fill="#ff9800" fill-opacity="'.$tOp.'" />
                    
                    <!-- Arms -->
                    <rect x="25" y="50" width="18" height="60" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                    <rect x="97" y="50" width="18" height="60" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                    
                    <!-- Legs -->
                    <rect x="50" y="122" width="18" height="80" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                    <rect x="72" y="122" width="18" height="80" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                </svg>
                
                <div style="text-align: left; font-size: 0.85em; width: 80px;">
                    <div style="margin-bottom: 5px;"><strong style="color: #f44336;">Head</strong><br>'.$hPct.'%</div>
                    <div style="margin-bottom: 5px;"><strong style="color: #ff9800;">Torso</strong><br>'.$tPct.'%</div>
                    <div><strong style="color: #2196f3;">Limbs</strong><br>'.$lPct.'%</div>
                </div>
            </div>
        </div>';
    };

    return '
    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px;">' . 
        $renderMan('HITS DEALT', $outHeadPct, $outTorsoPct, $outLimbPct) . 
        '<div style="width: 1px; background: rgba(0,0,0,0.1);"></div>' .
        $renderMan('HITS TAKEN', $inHeadPct, $inTorsoPct, $inLimbPct) . 
    '</div>';
}

function template_war_room_streaks_content($player) {
    return '
    <div class="streak-grid">
        <div class="streak-item">
            <div style="font-size: 1.8em; font-weight: bold; color: var(--mohaa-warning);">'.($player['best_killstreak'] ?? 0).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Best Streak</div>
        </div>
        <div class="streak-item">
            <div style="font-size: 1.8em; font-weight: bold;">'.($player['streaks_5'] ?? 0).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Rampages (5+)</div>
        </div>
        <div class="streak-item" style="grid-column: span 2;">
            <div style="font-size: 1.2em; font-weight: bold;">'.($player['streaks_10'] ?? 0).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Dominations (10+)</div>
        </div>
    </div>';
}

function template_war_room_accuracy_content($player, $data) {
    $accuracy = $player['accuracy'] ?? 0;
    $serverAvg = $data['server_avg_accuracy'] ?? 0;
    
    return '
    <div style="text-align: center; padding: 15px;">
        <div style="font-size: 2.5em; font-weight: bold; color: var(--mohaa-success);">'.number_format($accuracy, 1).'%</div>
        <div style="font-size: 0.9em; opacity: 0.7; margin-bottom: 15px;">Target Hit Rate</div>
        
        <div style="background: rgba(0,0,0,0.1); border-radius: 4px; padding: 10px; font-size: 0.9em;">
            Server Avg: <strong>'.number_format($serverAvg, 1).'%</strong>
        </div>
    </div>';
}

function template_war_room_damage_content($player) {
    $dealt = $player['damage_dealt'] ?? 0;
    $taken = $player['damage_taken'] ?? 0;
    $max = max($dealt, $taken, 1);
    
    $dealtPct = ($dealt / $max) * 100;
    $takenPct = ($taken / $max) * 100;
    
    return '
    <div style="padding: 15px;">
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: bold; color: var(--mohaa-success);">Damage Dealt</span>
                <span>'.number_format($dealt).'</span>
            </div>
            <div style="background: rgba(0,0,0,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
                <div style="background: var(--mohaa-success); height: 100%; width: '.$dealtPct.'%;"></div>
            </div>
        </div>
        <div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: bold; color: var(--mohaa-danger);">Damage Taken</span>
                <span>'.number_format($taken).'</span>
            </div>
            <div style="background: rgba(0,0,0,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
                <div style="background: var(--mohaa-danger); height: 100%; width: '.$takenPct.'%;"></div>
            </div>
        </div>
        <div style="margin-top: 15px; font-size: 0.85em; text-align: center; opacity: 0.7;">
            Net: <span style="font-weight: bold; color: '.($dealt >= $taken ? 'var(--mohaa-success)' : 'var(--mohaa-danger)').'">'.($dealt - $taken > 0 ? '+' : '').number_format($dealt - $taken).'</span>
        </div>
    </div>';
}

function template_war_room_special_stats_content($player) {
    $specials = [
        '🚗 Roadkills' => $player['roadkills'] ?? 0,
        '🔨 Bash Kills' => $player['bash_kills'] ?? 0,
        '🤕 Team Kills' => $player['team_kills'] ?? 0,
        '☠️ Suicides' => $player['suicides'] ?? 0,
        '🥜 Nutshots' => $player['nutshots'] ?? 0,
        '🗡️ Backstabs' => $player['backstabs'] ?? 0,
    ];
    
    $html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">';
    foreach ($specials as $label => $val) {
        $html .= '
        <div style="display: flex; flex-direction: column; align-items: center; padding: 15px; background: rgba(0,0,0,0.15); border-radius: 8px;">
            <div style="font-size: 1.5em; font-weight: bold; margin-bottom: 5px;">'.number_format($val).'</div>
            <div style="font-size: 0.85em; opacity: 0.7;">'.$label.'</div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}

function template_war_room_grenade_content($player) {
    $thrown = $player['grenades_thrown'] ?? 0;
    $kills = $player['grenade_kills'] ?? 0;
    $efficiency = $thrown > 0 ? ($kills / $thrown) * 100 : 0;
    
    return '
    <div style="display: flex; align-items: center; justify-content: space-around; padding: 15px; background: rgba(255, 87, 34, 0.1); border-radius: 8px; border: 1px solid rgba(255, 87, 34, 0.2);">
        <div style="text-align: center;">
            <div style="font-size: 2em;">💣</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.5em; font-weight: bold; color: #ff5722;">'.number_format($thrown).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Thrown</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.5em; font-weight: bold; color: #ff5722;">'.number_format($kills).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Kills</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.5em; font-weight: bold; color: #ff5722;">'.number_format($efficiency, 1).'%</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Efficiency</div>
        </div>
    </div>';
}

function template_war_room_weapons_content($weapons) {
    if (empty($weapons)) return '<p style="padding: 20px; text-align: center; opacity: 0.6;">No weapon data recorded yet.</p>';
    
    $html = '<div class="weapon-list-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">';
    foreach ($weapons as $name => $stats) {
        $icon = template_war_room_weapon_icon($name);
        $kills = $stats['kills'] ?? 0;
        $acc = $stats['accuracy'] ?? 0;
        $headshots = $stats['headshots'] ?? 0;
        $damage = $stats['damage'] ?? 0;
        $shots = $stats['shots'] ?? 0;
        
        // Mastery Logic
        if ($kills >= 1000) { $tier = 'legend'; $color = '#00bcd4'; $badge = '💠'; $rank = 'Legend'; }
        elseif ($kills >= 500) { $tier = 'master'; $color = '#e91e63'; $badge = '💎'; $rank = 'Master'; }
        elseif ($kills >= 200) { $tier = 'expert'; $color = '#ffd700'; $badge = '🥇'; $rank = 'Expert'; }
        elseif ($kills >= 50) { $tier = 'regular'; $color = '#c0c0c0'; $badge = '🥈'; $rank = 'Soldier'; }
        else { $tier = 'rookie'; $color = '#cd7f32'; $badge = '🥉'; $rank = 'Rookie'; }
        
        $progress = min(100, ($kills / ($tier == 'legend' ? 2000 : ($tier == 'master' ? 1000 : ($tier == 'expert' ? 500 : ($tier == 'regular' ? 200 : 50))))) * 100);
        $hsPct = $kills > 0 ? ($headshots / $kills) * 100 : 0;
        
        $html .= '
        <div class="weapon-card" style="background: rgba(255,255,255,0.05); border: 1px solid '.$color.'; border-left-width: 4px; border-radius: 6px; padding: 15px;">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="font-size: 2em; margin-right: 15px;">'.$icon.'</div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: bold; font-size: 1.1em;">'.htmlspecialchars($name).'</span>
                        <span style="font-size: 0.8em; padding: 2px 8px; border-radius: 10px; background: '.$color.'22; color: '.$color.'; border: 1px solid '.$color.'44;">'.$badge.' '.$rank.'</span>
                    </div>
                    <div style="height: 4px; background: rgba(0,0,0,0.2); margin-top: 5px; border-radius: 2px; overflow: hidden;">
                        <div style="height: 100%; background: '.$color.'; width: '.$progress.'%;"></div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85em;">
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">Kills</opacity>
                    <b>'.number_format($kills).'</b>
                </div>
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">Accuracy</opacity>
                    <b style="color: '.($acc > 30 ? 'var(--mohaa-success)' : '').'">'.number_format($acc, 1).'%</b>
                </div>
                '.($headshots > 0 ? '
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">Headshots</opacity>
                    <b>'.number_format($headshots).'</b>
                </div>
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">HS Rate</opacity>
                    <b>'.number_format($hsPct, 1).'%</b>
                </div>' : '').'
                '.($damage > 0 ? '
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between; grid-column: 1 / -1;">
                    <opacity style="opacity: 0.7;">Damage</opacity>
                    <b>'.number_format($damage).'</b>
                </div>' : '').'
            </div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}

function template_war_room_movement_content($player) {
    return '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: center;">
        <div class="streak-item">
            <div style="font-size: 2em; margin-bottom: 5px;">🏃</div>
            <strong>'.number_format(($player['distance_traveled'] ?? 0) / 1000, 1).' km</strong>
            <div style="font-size: 0.8em; opacity: 0.7;">Traveled</div>
        </div>
        <div class="streak-item">
            <div style="font-size: 2em; margin-bottom: 5px;">🐇</div>
            <strong>'.number_format($player['jumps'] ?? 0).'</strong>
            <div style="font-size: 0.8em; opacity: 0.7;">Jumps</div>
        </div>
    </div>';
}

function template_war_room_stance_content($player) {
    // 1. Get raw counts (support both naming conventions just in case)
    $stand = $player['kills_while_standing'] ?? $player['standing_kills'] ?? 0;
    $crouch = $player['kills_while_crouching'] ?? $player['crouching_kills'] ?? 0;
    $prone = $player['kills_while_prone'] ?? $player['prone_kills'] ?? 0;
    
    // 2. Calculate Total
    $total = $stand + $crouch + $prone;
    if ($total == 0) $total = 1; // Avoid div by zero
    
    // 3. Calculate Percentages
    $standPct = round(($stand / $total) * 100, 1);
    $crouchPct = round(($crouch / $total) * 100, 1);
    $pronePct = round(($prone / $total) * 100, 1);
    
    return '
    <div style="padding: 10px;">
        <div style="margin-bottom: 12px; cursor: pointer;" class="drilldown-stat" data-stat="kills_while_standing" data-dimension="map" title="Click for details">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>Standing</span>
                <div>
                    <strong>'.number_format($stand).'</strong>
                    <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">('.$standPct.'%)</span>
                </div>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.$standPct.'%; height: 100%; background: #2196f3; border-radius: 4px;"></div>
            </div>
        </div>
         <div style="margin-bottom: 12px; cursor: pointer;" class="drilldown-stat" data-stat="kills_while_crouching" data-dimension="map" title="Click for details">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>Crouching</span>
                <div>
                    <strong>'.number_format($crouch).'</strong>
                    <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">('.$crouchPct.'%)</span>
                </div>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.$crouchPct.'%; height: 100%; background: #4caf50; border-radius: 4px;"></div>
            </div>
        </div>
         <div style="cursor: pointer;" class="drilldown-stat" data-stat="kills_while_prone" data-dimension="map" title="Click for details">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>Prone</span>
                <div>
                    <strong>'.number_format($prone).'</strong>
                    <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">('.$pronePct.'%)</span>
                </div>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.$pronePct.'%; height: 100%; background: #ff9800; border-radius: 4px;"></div>
            </div>
        </div>
    </div>';
}

function template_war_room_rivals_content($player) {
    return '
    <div style="display: grid; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border-left: 3px solid #f44336; background: rgba(244, 67, 54, 0.05);">
            <div style="font-size: 1.5em;">😡</div>
            <div>
                <div style="font-size: 0.8em; color: #f44336; font-weight: bold;">NEMESIS</div>
                <div style="font-weight: bold;">'.htmlspecialchars($player['nemesis_name'] ?? 'None').'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Result: '.($player['nemesis_kills'] ?? 0).' deaths</div>
            </div>
        </div>
         <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border-left: 3px solid #4caf50; background: rgba(76, 175, 80, 0.05);">
             <div style="font-size: 1.5em;">😈</div>
            <div>
                <div style="font-size: 0.8em; color: #4caf50; font-weight: bold;">VICTIM</div>
                <div style="font-weight: bold;">'.htmlspecialchars($player['victim_name'] ?? 'None').'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Result: '.($player['victim_kills'] ?? 0).' kills</div>
            </div>
        </div>
    </div>';
}

function template_war_room_maps_content($maps, $player) {
    if (empty($maps)) {
        return '<div class="alert">No map data available yet. Play some matches!</div>';
    }

    $rows = '';
    $chartLabels = [];
    $chartDataWins = [];
    $chartDataLosses = [];
    
    foreach ($maps as $m) {
        $played = $m['matches_played'] ?? 0;
        if ($played <= 0) continue;
        
        $wins = $m['matches_won'] ?? 0;
        $losses = $played - $wins;
        $winRate = ($wins / $played) * 100;
        $kills = $m['kills'] ?? 0;
        $deaths = $m['deaths'] ?? 0;
        $kd = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
        
        $chartLabels[] = $m['map_name'];
        $chartDataWins[] = $wins;
        $chartDataLosses[] = $losses;
        
        $rows .= '
        <tr>
            <td style="text-align: left; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="font-weight: bold; color: #fff;">'.htmlspecialchars($m['map_name']).'</span>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">'.number_format($played).'</td>
            <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #4caf50;">'.number_format($wins).'</span>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="flex-grow: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                        <div style="width: '.min(100, max(0, $winRate)).'%; height: 100%; background: '.($winRate >= 50 ? '#4caf50' : '#f44336').';"></div>
                    </div>
                    <span style="font-size: 0.85em; width: 40px; text-align: right;">'.number_format($winRate, 0).'%</span>
                </div>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">'.number_format($kills).'</td>
            <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">'.number_format($kd, 2).'</td>
        </tr>';
    }
    
    return '
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <div>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="text-align: left; color: rgba(255,255,255,0.5); font-size: 0.85em; text-transform: uppercase;">
                        <th style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">Map</th>
                        <th style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">Matches</th>
                        <th style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">Wins</th>
                        <th style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">Win Rate</th>
                        <th style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">Kills</th>
                        <th style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">K/D</th>
                    </tr>
                </thead>
                <tbody>
                    '.$rows.'
                </tbody>
            </table>
        </div>
        <div>
            <div id="chart-map-wins" style="min-height: 250px;"></div>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var options = {
                        series: [{
                            name: "Wins",
                            data: '.json_encode($chartDataWins).'
                        }, {
                            name: "Losses",
                            data: '.json_encode($chartDataLosses).'
                        }],
                        chart: {
                            type: "bar",
                            height: 250,
                            stacked: true,
                            toolbar: { show: false },
                            background: "transparent"
                        },
                        colors: ["#4caf50", "#f44336"],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                dataLabels: {
                                    total: {
                                        enabled: true,
                                        offsetX: 0,
                                        style: {
                                            fontSize: "13px",
                                            fontWeight: 900
                                        }
                                    }
                                }
                            },
                        },
                        stroke: { width: 1, colors: ["#fff"] },
                        xaxis: {
                            categories: '.json_encode($chartLabels).',
                            labels: { style: { colors: "#aab7c4" } }
                        },
                        yaxis: {
                            labels: { style: { colors: "#aab7c4" } }
                        },
                        theme: { mode: "dark" },
                        legend: { position: "top", labels: { colors: "#aab7c4" } }
                    };
                    var chart = new ApexCharts(document.querySelector("#chart-map-wins"), options);
                    chart.render();
                });
            </script>
        </div>
    </div>';
}


function template_war_room_matches_content($matches) {
    if (empty($matches)) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">No matches played recently.</p>';
    
    $html = '<table class="clean-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Map</th>
                        <th>Result</th>
                        <th>Kills</th>
                        <th>Deaths</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>';
                
    foreach ($matches as $m) {
        $res = $m['result'] ?? 'draw';
        $resClass = $res === 'win' ? 'color: var(--mohaa-success);' : ($res === 'loss' ? 'color: var(--mohaa-danger);' : '');
        
        $html .= '
        <tr>
            <td>'.timeformat($m['date'] ?? time(), '%b %d').'</td>
            <td>'.htmlspecialchars($m['map'] ?? 'Unknown').'</td>
            <td style="font-weight: bold; '.$resClass.'">'.strtoupper($res).'</td>
            <td>'.($m['kills'] ?? 0).'</td>
            <td>'.($m['deaths'] ?? 0).'</td>
            <td>'.($m['score'] ?? 0).'</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}



function template_war_room_achievements_content($achievements) {
    // API returns {unlocked: [], progress: []}
    $unlocked = $achievements['unlocked'] ?? [];
    
    if (empty($unlocked)) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">No achievements unlocked yet.</p>';
    
    $html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
    
    foreach ($unlocked as $ach) {
        // Icon mapping or default
        $icon = "🎖️"; // Default
        
        $html .= '
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="font-size: 2em;">'.$icon.'</div>
            <div>
                <div style="font-weight: bold; font-size: 0.9em;">'.htmlspecialchars($ach['name'] ?? 'Achievement').'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">'.timeformat($ach['unlocked_date'] ?? time(), '%b %d, %Y').'</div>
            </div>
        </div>';
    }
    
    $html .= '</div>
    <div style="margin-top: 20px; text-align: center;">
        <a href="' . $GLOBALS['scripturl'] . '?action=mohaaachievements" class="button">View Full Medal Case</a>
    </div>';
    return $html;
}

function template_war_room_playstyle_content($playstyle) {
    if (empty($playstyle) || empty($playstyle['style'])) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">Analysis requires more data.</p>';
    
    // Icon mapping
    $iconMap = [
        'running' => '🏃',
        'crosshair' => '🎯',
        'rifle' => '🎖️',
        'recruit' => '👶',
    ];
    $iconChar = $iconMap[$playstyle['icon'] ?? 'rifle'] ?? '🎖️';
    
    return '
    <div style="text-align: center; padding: 10px;">
        <div style="font-size: 3em; margin-bottom: 5px;">'.$iconChar.'</div>
        <div style="font-size: 1.5em; font-weight: bold; color: #fff;">'.($playstyle['style'] ?? 'Soldier').'</div>
        <div style="font-size: 0.9em; opacity: 0.7; max-width: 250px; margin: 10px auto;">'.($playstyle['description'] ?? '').'</div>
        <div style="margin-top: 10px; font-size: 0.8em; opacity: 0.5;">Confidence: '.number_format($playstyle['confidence'] ?? 0).'%</div>
    </div>';
}

function template_war_room_rank_icon(int $kills): string
{
    if ($kills >= 100000) return '👑';
    if ($kills >= 50000) return '🏆';
    if ($kills >= 10000) return '💎';
    if ($kills >= 5000) return '🥇';
    if ($kills >= 1000) return '🥈';
    if ($kills >= 100) return '🥉';
    return '🎖️';
}

function template_war_room_weapon_icon(string $weapon): string
{
    $icons = [
        'Thompson' => '🔫', 'MP40' => '🔫', 'Kar98k' => '🎯',
        'Springfield' => '🎯', 'M1 Garand' => '🔫', 'BAR' => '🔫',
        'StG44' => '🔫', 'Grenade' => '💣', 'Knife' => '🔪', 'Pistol' => '🔫',
    ];
    return $icons[$weapon] ?? '🔫';
}

/**
 * Distance breakdown stats
 */
function template_war_room_distance_content($player): string
{
    $walked = ($player['distance_walked'] ?? 0) / 1000;
    $sprinted = ($player['distance_sprinted'] ?? 0) / 1000;
    $swam = ($player['distance_swam'] ?? 0) / 1000;
    $driven = ($player['distance_driven'] ?? 0) / 1000;
    $total = $walked + $sprinted + $swam + $driven;
    
    return '
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
        <div style="text-align: center; padding: 20px; background: rgba(33, 150, 243, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #2196f3;">'.number_format($walked, 1).'</div>
            <div style="opacity: 0.7;">km Walked</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(255, 152, 0, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($sprinted, 1).'</div>
            <div style="opacity: 0.7;">km Sprinted</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(0, 188, 212, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #00bcd4;">'.number_format($swam, 1).'</div>
            <div style="opacity: 0.7;">km Swam</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(156, 39, 176, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #9c27b0;">'.number_format($driven, 1).'</div>
            <div style="opacity: 0.7;">km Driven</div>
        </div>
    </div>
    <div style="text-align: center; font-size: 1.5em; font-weight: bold;">Total: '.number_format($total, 1).' km</div>';
}

/**
 * Jump stats with badge
 */
function template_war_room_jumps_content($player): string
{
    $jumps = $player['jumps'] ?? 0;
    $matches = max(1, $player['matches_played'] ?? 1);
    $badge = $jumps > 1000 ? '<div style="margin-top: 15px; padding: 10px; background: rgba(76, 175, 80, 0.2); border-radius: 8px; font-weight: bold;">🐰 Bunny Hopper!</div>' : '';
    
    return '
    <div style="text-align: center; padding: 30px;">
        <div style="font-size: 4em; font-weight: bold; color: #4caf50;">'.number_format($jumps).'</div>
        <div style="font-size: 1.2em; opacity: 0.8;">Total Jumps</div>
        '.$badge.'
        <div style="margin-top: 20px; font-size: 0.9em; opacity: 0.7;">Avg per match: '.number_format($jumps / $matches, 1).'</div>
    </div>';
}

/**
 * Win/Loss record
 */
function template_war_room_winloss_content($player): string
{
    $wins = $player['wins'] ?? 0;
    $losses = $player['losses'] ?? 0;
    $ffaWins = $player['ffa_wins'] ?? 0;
    $teamWins = $player['team_wins'] ?? 0;
    $total = max(1, $wins + $losses);
    $winRate = ($wins / $total) * 100;
    
    return '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
        <div style="text-align: center; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px;">
            <div style="font-size: 1.8em; font-weight: bold; color: #4caf50;">'.number_format($wins).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Total Wins</div>
            <div style="font-size: 0.7em; opacity: 0.5;">'.number_format($winRate, 1).'% Rate</div>
        </div>
        <div style="text-align: center; padding: 15px; background: rgba(244, 67, 54, 0.1); border-radius: 8px;">
            <div style="font-size: 1.8em; font-weight: bold; color: #f44336;">'.number_format($losses).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Losses</div>
        </div>
        <div style="text-align: center; padding: 10px; background: rgba(255, 255, 255, 0.05); border-radius: 8px;">
            <div style="font-size: 1.2em; font-weight: bold; color: #2196f3;">'.number_format($teamWins).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Team Wins</div>
        </div>
        <div style="text-align: center; padding: 10px; background: rgba(255, 152, 0, 0.05); border-radius: 8px;">
            <div style="font-size: 1.2em; font-weight: bold; color: #ff9800;">'.number_format($ffaWins).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">FFA Wins</div>
        </div>
    </div>';
}


/**
 * Rounds and games breakdown
 */
function template_war_room_rounds_content($player): string
{
    $games = $player['games_played'] ?? 0;
    $rounds = $player['rounds'] ?? 0;
    $playtime = $player['playtime_seconds'] ?? 0;
    $avgLength = format_playtime($playtime / max(1, $games));
    $roundsPerGame = number_format($rounds / max(1, $games), 1);
    
    return '
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <span>🎮 Games Played</span>
            <strong>'.number_format($games).'</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <span>🔁 Rounds Played</span>
            <strong>'.number_format($rounds).'</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <span>⏱️ Avg Game Length</span>
            <strong>'.$avgLength.'</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 15px 0;">
            <span>🎯 Rounds per Game</span>
            <strong>'.$roundsPerGame.'</strong>
        </div>
    </div>';
}

/**
 * Objectives breakdown
 */
function template_war_room_objectives_content($player): string
{
    $objectives = $player['objectives_completed'] ?? 0;
    $flags = $player['flags_captured'] ?? 0;
    $planted = $player['bombs_planted'] ?? 0;
    $defused = $player['bombs_defused'] ?? 0;
    
    return '
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; padding: 20px;">
        <div style="text-align: center; padding: 20px; background: rgba(33, 150, 243, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #2196f3;">'.number_format($objectives).'</div>
            <div style="opacity: 0.7;">Objectives Done</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(76, 175, 80, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #4caf50;">'.number_format($flags).'</div>
            <div style="opacity: 0.7;">Flags Captured</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(255, 152, 0, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($planted).'</div>
            <div style="opacity: 0.7;">Bombs Planted</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(156, 39, 176, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #9c27b0;">'.number_format($defused).'</div>
            <div style="opacity: 0.7;">Bombs Defused</div>
        </div>
    </div>';
}

function template_war_room_interaction_content($player) {
    $chat = $player['chat_messages'] ?? 0;
    $vUses = $player['vehicle_uses'] ?? 0;
    $tUses = $player['turret_uses'] ?? 0;
    
    // Pickups list
    $pickupsHtml = '';
    $pickups = $player['pickups'] ?? [];
    // Ensure array if flattened poorly
    if (!is_array($pickups)) $pickups = [];
    
    // Pickups comes as array of [item_name, count] structs from Go via PHP conversion
    // Go: []PickupStat{ItemName, Count}. PHP: [['item_name'=>..., 'count'=>...]]
    
    foreach ($pickups as $p) {
        $name = is_array($p) ? ($p['item_name'] ?? 'Item') : 'Item';
        $count = is_array($p) ? ($p['count'] ?? 0) : 0;
        
        $pickupsHtml .= '<div style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.05); background: rgba(0,0,0,0.02); margin-bottom: 5px; border-radius: 4px;">
            <span>'.htmlspecialchars($name).'</span>
            <strong>'.number_format($count).'</strong>
        </div>';
    }
    if (empty($pickupsHtml)) $pickupsHtml = '<div style="opacity: 0.6; padding: 20px;">No pickups recorded.</div>';

    return '
    <div class="mohaa-grid">
        <div class="windowbg stat-card">
            <h3>💬 Chat Stats</h3>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 2.5em; font-weight: bold; color: #9c27b0;">'.number_format($chat).'</div>
                <div style="opacity: 0.7;">Messages Sent</div>
            </div>
        </div>
        
        <div class="windowbg stat-card">
            <h3>🚙 Vehicle & Turret</h3>
            <div style="display: flex; justify-content: space-around; padding: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($vUses).'</div>
                    <div style="opacity: 0.7;">Vehicles</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #607d8b;">'.number_format($tUses).'</div>
                    <div style="opacity: 0.7;">Turrets</div>
                </div>
            </div>
        </div>
        
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📦 Top Pickups</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                '.$pickupsHtml.'
            </div>
        </div>
    </div>';
}

function template_war_room_playstyle_icon($style) {
    $icons = [
        'Rusher' => '🏃',
        'Sniper' => '🎯',
        'Tactician' => '🧠',
        'Support' => '🛡️',
        'Objective' => '🎖️',
        'Lone Wolf' => '🐺',
        'Team Player' => '🤝',
        'Aggressor' => '⚔️',
        'Defender' => '🏰',
        'All-Rounder' => '⭐',
    ];
    return $icons[$style] ?? '🎖️';
}

function template_war_room_playstyle_desc($style) {
    $descs = [
        'Rusher' => 'You lead the charge with aggressive, fast-paced gameplay. High mobility, high risk, high reward.',
        'Sniper' => 'Patient and precise. You pick your shots carefully and dominate from range.',
        'Tactician' => 'Strategic mind. You read the battlefield and position yourself for maximum advantage.',
        'Support' => 'The backbone of the team. You enable others to succeed while holding the line.',
        'Objective' => 'Mission-focused warrior. Wins matter more than kills in your book.',
        'Lone Wolf' => 'Self-reliant and deadly. You work best when carving your own path.',
        'Team Player' => 'Force multiplier. Your presence elevates everyone around you.',
        'Aggressor' => 'Relentless pressure. You keep enemies on their heels with constant aggression.',
        'Defender' => 'Immovable object. You excel at holding positions and denying ground.',
        'All-Rounder' => 'Versatile and adaptable. You excel in any situation thrown at you.',
    ];
    return $descs[$style] ?? 'A skilled warrior with a unique combat style.';
}

function template_war_room_move_combat_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $runGun = $data['run_gun_index'] ?? 0;
    $bunnyHop = $data['bunny_hop_efficiency'] ?? 0;
    $slideKills = $data['slide_kill_rate'] ?? 0;
    $airKills = $data['air_kill_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Run & Gun Index', $runGun, '#ff9800', '% of kills while moving').'
        '.template_war_room_progress_bar('Bunny Hop Efficiency', $bunnyHop, '#4caf50', '% accuracy while jumping').'
        '.template_war_room_progress_bar('Slide Kill Rate', $slideKills, '#2196f3', '% of kills while sliding').'
        '.template_war_room_progress_bar('Air Kill Rate', $airKills, '#9c27b0', '% of kills mid-air').'
    </div>';
}

function template_war_room_signature_metrics_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $clutch = $data['clutch_rate'] ?? 0;
    $firstBlood = $data['first_blood_rate'] ?? 0;
    $multiKill = $data['multi_kill_rate'] ?? 0;
    $revenge = $data['revenge_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Clutch Rate', $clutch, '#f44336', '% of 1vX situations won').'
        '.template_war_room_progress_bar('First Blood Rate', $firstBlood, '#ff5722', '% of rounds with first kill').'
        '.template_war_room_progress_bar('Multi-Kill Rate', $multiKill, '#e91e63', '% of kills in multi-kills').'
        '.template_war_room_progress_bar('Revenge Rate', $revenge, '#673ab7', '% of deaths avenged').'
    </div>';
}

function template_war_room_health_obj_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $objFocus = $data['objective_focus'] ?? 0;
    $clutchPlant = $data['clutch_plant_rate'] ?? 0;
    $lowHealth = $data['low_health_kill_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Objective Focus', $objFocus, '#4caf50', '% of rounds with obj action').'
        '.template_war_room_progress_bar('Clutch Plant Rate', $clutchPlant, '#ff9800', '% of last-second plants').'
        '.template_war_room_progress_bar('Low Health Kills', $lowHealth, '#f44336', '% of kills below 25 HP').'
        
        <div style="margin-top: 15px; padding: 10px; background: rgba(76, 175, 80, 0.1); border-radius: 6px; text-align: center;">
            <div style="font-size: 0.8em; opacity: 0.7;">Average HP at Kill</div>
            <div style="font-size: 1.5em; font-weight: bold; color: #4caf50;">'.($data['avg_hp_at_kill'] ?? 0).'</div>
        </div>
    </div>';
}

function template_war_room_economy_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $scavenger = $data['scavenger_score'] ?? 0;
    $pickupKill = $data['pickup_to_kill_ratio'] ?? 0;
    $survival = $data['survival_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Scavenger Score', $scavenger, '#ff9800', '% efficiency of pickups').'
        '.template_war_room_progress_bar('Survival Rate', $survival, '#4caf50', '% of rounds survived').'
        
        <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="padding: 15px; background: rgba(255, 152, 0, 0.1); border-radius: 6px; text-align: center;">
                <div style="font-size: 0.8em; opacity: 0.7;">Pickup/Kill Ratio</div>
                <div style="font-size: 1.3em; font-weight: bold; color: #ff9800;">'.number_format($pickupKill, 2).'</div>
            </div>
            <div style="padding: 15px; background: rgba(33, 150, 243, 0.1); border-radius: 6px; text-align: center;">
                <div style="font-size: 0.8em; opacity: 0.7;">Avg Lifespan</div>
                <div style="font-size: 1.3em; font-weight: bold; color: #2196f3;">'.($data['avg_lifespan_secs'] ?? 0).'s</div>
            </div>
        </div>
    </div>';
}

function template_war_room_progress_bar($label, $value, $color, $subtext = '') {
    $pct = min(100, max(0, $value));
    return '
    <div style="margin-bottom: 12px;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
            <span>'.$label.'</span>
            <strong style="color: '.$color.'">'.number_format($value, 1).'</strong>
        </div>
        <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="width: '.$pct.'%; height: 100%; background: '.$color.'; border-radius: 4px; transition: width 0.3s ease;"></div>
        </div>
        '.($subtext ? '<div style="font-size: 0.75em; opacity: 0.6; margin-top: 2px;">'.$subtext.'</div>' : '').'
    </div>';
}

// =============================================================================
// PEAK PERFORMANCE TAB - "When" Analysis
// =============================================================================
function template_war_room_peak_performance_content($data) {
    $peak = $data['peak_performance'] ?? [];
    
    if (empty($peak)) {
        return '
        <div class="mohaa-grid">
            <div class="windowbg stat-card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                <div style="font-size: 3em; margin-bottom: 15px;">📊</div>
                <div style="font-size: 1.2em; opacity: 0.7;">Peak performance analysis requires more match data.</div>
                <div style="margin-top: 10px; opacity: 0.5;">Play more matches to unlock time-based insights!</div>
            </div>
        </div>';
    }
    
    $bestHour = $peak['best_hour'] ?? [];
    $bestDay = $peak['best_day'] ?? [];
    $bestMap = $peak['best_map'] ?? [];
    $bestWeapon = $peak['best_weapon'] ?? [];
    $hourlyBreakdown = $peak['hourly_breakdown'] ?? [];
    $dailyBreakdown = $peak['daily_breakdown'] ?? [];
    
    $mostAccurateAt = $peak['most_accurate_at'] ?? 'N/A';
    $mostWinsAt = $peak['most_wins_at'] ?? 'N/A';
    $mostLossesAt = $peak['most_losses_at'] ?? 'N/A';
    
    $output = '<div class="mohaa-grid">';
    
    // Hero Summary Card
    $output .= '
    <div class="windowbg stat-card" style="grid-column: 1 / -1; background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));">
        <h3 style="text-align: center; margin-bottom: 20px;">⚡ Your Optimal Combat Conditions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; text-align: center;">
            <div class="drilldown-stat" data-stat="kills" data-dimension="hour" style="cursor: pointer;">
                <div style="font-size: 2em; margin-bottom: 5px;">🕐</div>
                <div style="font-size: 1.6em; font-weight: bold; color: #4caf50;">'.(isset($bestHour['hour']) ? sprintf('%02d:00', $bestHour['hour']) : 'N/A').'</div>
                <div style="opacity: 0.7;">Best Hour</div>
                <div style="font-size: 0.8em; color: #4caf50;">K/D: '.number_format($bestHour['kd_ratio'] ?? 0, 2).'</div>
            </div>
            
            <div class="drilldown-stat" data-stat="kills" data-dimension="day" style="cursor: pointer;">
                <div style="font-size: 2em; margin-bottom: 5px;">📅</div>
                <div style="font-size: 1.6em; font-weight: bold; color: #2196f3;">'.htmlspecialchars($bestDay['day_of_week'] ?? 'Weekend').'</div>
                <div style="opacity: 0.7;">Best Day</div>
                <div style="font-size: 0.8em; color: #2196f3;">K/D: '.number_format($bestDay['kd_ratio'] ?? 0, 2).'</div>
            </div>
            
            <div class="drilldown-stat" data-stat="kills" data-dimension="map" style="cursor: pointer;">
                <div style="font-size: 2em; margin-bottom: 5px;">🗺️</div>
                <div style="font-size: 1.6em; font-weight: bold; color: #ff9800;">'.htmlspecialchars($bestMap['map_name'] ?? 'Unknown').'</div>
                <div style="opacity: 0.7;">Best Map</div>
                <div style="font-size: 0.8em; color: #ff9800;">'.number_format($bestMap['kills'] ?? 0).' kills</div>
            </div>
            
            <div class="drilldown-stat" data-stat="kills" data-dimension="weapon" style="cursor: pointer;">
                <div style="font-size: 2em; margin-bottom: 5px;">🔫</div>
                <div style="font-size: 1.6em; font-weight: bold; color: #9c27b0;">'.htmlspecialchars($bestWeapon['weapon_name'] ?? 'Unknown').'</div>
                <div style="opacity: 0.7;">Signature Weapon</div>
                <div style="font-size: 0.8em; color: #9c27b0;">'.number_format($bestWeapon['kills'] ?? 0).' kills</div>
            </div>
        </div>
    </div>';
    
    // "When" Analysis Cards
    $output .= '
    <div class="windowbg stat-card">
        <h3>🎯 When Most Accurate</h3>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 3em; font-weight: bold; color: #4caf50;">'.$mostAccurateAt.'</div>
            <div style="opacity: 0.7; margin-top: 5px;">Your aim is sharpest at this hour</div>
        </div>
    </div>
    
    <div class="windowbg stat-card">
        <h3>🏆 When Most Wins</h3>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 3em; font-weight: bold; color: #2196f3;">'.$mostWinsAt.'</div>
            <div style="opacity: 0.7; margin-top: 5px;">Peak victory hour</div>
        </div>
    </div>
    
    <div class="windowbg stat-card">
        <h3>💀 When Most Losses</h3>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 3em; font-weight: bold; color: #f44336;">'.$mostLossesAt.'</div>
            <div style="opacity: 0.7; margin-top: 5px;">Avoid playing at this hour!</div>
        </div>
    </div>';
    
    // Hourly Performance Chart
    if (!empty($hourlyBreakdown)) {
        $hours = [];
        $kds = [];
        foreach ($hourlyBreakdown as $h) {
            $hours[] = sprintf('%02d:00', $h['hour'] ?? 0);
            $kds[] = $h['kd_ratio'] ?? 0;
        }
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📈 Hourly K/D Performance</h3>
            <div id="chart-hourly-performance" style="min-height: 280px;"></div>
            <script>
                (function() {
                    var hourlyChart = new ApexCharts(document.getElementById("chart-hourly-performance"), {
                        chart: { type: "bar", height: 280, toolbar: { show: false }, background: "transparent" },
                        series: [{ name: "K/D", data: '.json_encode($kds).' }],
                        xaxis: { categories: '.json_encode($hours).', labels: { style: { colors: "#888" } } },
                        colors: ["#4caf50"],
                        theme: { mode: "dark" },
                        plotOptions: { bar: { borderRadius: 4, columnWidth: "60%" } },
                        yaxis: { labels: { style: { colors: "#888" }, formatter: function(val) { return val.toFixed(2); } } },
                        tooltip: { theme: "dark" }
                    });
                    hourlyChart.render();
                })();
            </script>
        </div>';
    }
    
    // Daily Performance
    if (!empty($dailyBreakdown)) {
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📊 Performance by Day of Week</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; padding: 15px 0;">
        ';
        foreach ($dailyBreakdown as $day) {
            $dayName = $day['day_of_week'] ?? 'Unknown';
            $kd = $day['kd_ratio'] ?? 0;
            $kills = $day['kills'] ?? 0;
            $color = $kd >= 1.5 ? '#4caf50' : ($kd >= 1 ? '#ff9800' : '#f44336');
            
            $output .= '
            <div class="drilldown-stat" data-stat="kills" data-dimension="day" style="cursor: pointer; text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-weight: bold;">'.htmlspecialchars($dayName).'</div>
                <div style="font-size: 1.5em; font-weight: bold; color: '.$color.';">'.number_format($kd, 2).'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">'.number_format($kills).' kills</div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    $output .= '</div>'; // End grid
    return $output;
}

// =============================================================================
// SIGNATURE TAB - Combo Metrics & Playstyle
// =============================================================================
function template_war_room_signature_content($data) {
    $combo = $data['combo_metrics'] ?? [];
    
    if (empty($combo)) {
        return '
        <div class="mohaa-grid">
            <div class="windowbg stat-card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                <div style="font-size: 3em; margin-bottom: 15px;">🎯</div>
                <div style="font-size: 1.2em; opacity: 0.7;">Signature analysis requires more gameplay data.</div>
                <div style="margin-top: 10px; opacity: 0.5;">Keep fragging to unlock your unique playstyle profile!</div>
            </div>
        </div>';
    }
    
    $weaponOnMap = $combo['weapon_on_map'] ?? [];
    $victimPatterns = $combo['victim_patterns'] ?? [];
    $killerPatterns = $combo['killer_patterns'] ?? [];
    $distanceByWeapon = $combo['distance_by_weapon'] ?? [];
    $hitlocByWeapon = $combo['hitloc_by_weapon'] ?? [];
    
    $output = '<div class="mohaa-grid">';
    
    // Weapon Mastery by Map
    if (!empty($weaponOnMap)) {
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>🗺️ Best Weapon Per Map</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        foreach (array_slice($weaponOnMap, 0, 6) as $wm) {
            $mapName = $wm['map_name'] ?? 'Unknown';
            $weaponName = $wm['weapon_name'] ?? 'Unknown';
            $kills = $wm['kills'] ?? 0;
            
            $output .= '
            <div class="drilldown-stat" data-stat="kills" data-dimension="map" style="cursor: pointer; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 0.9em; opacity: 0.7; margin-bottom: 5px;">'.htmlspecialchars($mapName).'</div>
                <div style="font-size: 1.3em; font-weight: bold; color: #ff9800;">'.htmlspecialchars($weaponName).'</div>
                <div style="font-size: 0.85em; color: #4caf50;">'.number_format($kills).' kills</div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    // Victim Patterns (Who you dominate)
    if (!empty($victimPatterns)) {
        $output .= '
        <div class="windowbg stat-card">
            <h3>😈 Favorite Victims</h3>
            <div style="max-height: 300px; overflow-y: auto;">';
        
        foreach (array_slice($victimPatterns, 0, 5) as $v) {
            $victimName = $v['victim_name'] ?? 'Unknown';
            $kills = $v['kills'] ?? 0;
            $deaths = $v['deaths_to'] ?? 0;
            $ratio = $v['ratio'] ?? ($deaths > 0 ? $kills / $deaths : $kills);
            $weapon = $v['favorite_weapon'] ?? '';
            
            $color = $ratio >= 2 ? '#4caf50' : ($ratio >= 1 ? '#ff9800' : '#f44336');
            
            $output .= '
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.1);">
                <div>
                    <div style="font-weight: bold;">'.htmlspecialchars($victimName).'</div>
                    <div style="font-size: 0.8em; opacity: 0.7;">with '.htmlspecialchars($weapon).'</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: bold; color: '.$color.';">'.number_format($kills).' / '.number_format($deaths).'</div>
                    <div style="font-size: 0.8em; color: '.$color.';">'.number_format($ratio, 1).'x</div>
                </div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    // Killer Patterns (Who dominates you)
    if (!empty($killerPatterns)) {
        $output .= '
        <div class="windowbg stat-card">
            <h3>💀 Nemeses</h3>
            <div style="max-height: 300px; overflow-y: auto;">';
        
        foreach (array_slice($killerPatterns, 0, 5) as $k) {
            $killerName = $k['killer_name'] ?? 'Unknown';
            $deaths = $k['deaths_to'] ?? 0;
            $kills = $k['kills_against'] ?? 0;
            $weapon = $k['most_used_weapon'] ?? '';
            
            $output .= '
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.1);">
                <div>
                    <div style="font-weight: bold; color: #f44336;">'.htmlspecialchars($killerName).'</div>
                    <div style="font-size: 0.8em; opacity: 0.7;">uses '.htmlspecialchars($weapon).'</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: bold;">You: '.number_format($kills).'</div>
                    <div style="font-size: 0.8em; color: #f44336;">Them: '.number_format($deaths).'</div>
                </div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    // Distance by Weapon
    if (!empty($distanceByWeapon)) {
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📏 Engagement Distance by Weapon</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">';
        
        foreach (array_slice($distanceByWeapon, 0, 6) as $dw) {
            $weaponName = $dw['weapon_name'] ?? 'Unknown';
            $avgDist = $dw['avg_distance'] ?? 0;
            $maxDist = $dw['max_distance'] ?? 0;
            
            $distanceLabel = $avgDist > 100 ? 'Long Range' : ($avgDist > 30 ? 'Mid Range' : 'Close Range');
            $distColor = $avgDist > 100 ? '#2196f3' : ($avgDist > 30 ? '#ff9800' : '#f44336');
            
            $output .= '
            <div style="text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-weight: bold;">'.htmlspecialchars($weaponName).'</div>
                <div style="font-size: 1.5em; font-weight: bold; color: '.$distColor.';">'.number_format($avgDist, 0).'m</div>
                <div style="font-size: 0.8em; opacity: 0.7;">'.$distanceLabel.'</div>
                <div style="font-size: 0.75em; opacity: 0.5;">Max: '.number_format($maxDist, 0).'m</div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    // Accuracy Profile by Weapon (Hitloc distribution)
    if (!empty($hitlocByWeapon)) {
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>🎯 Accuracy Profile by Weapon</h3>
            <table class="clean-table">
                <thead>
                    <tr>
                        <th>Weapon</th>
                        <th>Head %</th>
                        <th>Torso %</th>
                        <th>Limb %</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach (array_slice($hitlocByWeapon, 0, 8) as $hw) {
            $weaponName = $hw['weapon_name'] ?? 'Unknown';
            $headPct = $hw['head_pct'] ?? 0;
            $torsoPct = $hw['torso_pct'] ?? 0;
            $limbPct = $hw['limb_pct'] ?? 0;
            
            $output .= '
                    <tr>
                        <td><strong>'.htmlspecialchars($weaponName).'</strong></td>
                        <td style="color: #f44336;">'.number_format($headPct, 1).'%</td>
                        <td style="color: #ff9800;">'.number_format($torsoPct, 1).'%</td>
                        <td style="color: #2196f3;">'.number_format($limbPct, 1).'%</td>
                    </tr>';
        }
        $output .= '</tbody></table></div>';
    }
    
    $output .= '</div>'; // End grid
    return $output;
}

// =============================================================================
// VEHICLE STATS SECTION
// =============================================================================
function template_war_room_vehicle_section($data) {
    $vehicles = $data['vehicle_stats'] ?? [];
    
    if (empty($vehicles)) {
        return '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>🚗 Vehicle & Turret Stats</h3>
            <div style="text-align: center; padding: 30px; opacity: 0.7;">
                <div style="font-size: 2em; margin-bottom: 10px;">🚙</div>
                <div>No vehicle data available yet.</div>
            </div>
        </div>';
    }
    
    $vehicleKills = $vehicles['vehicle_kills'] ?? 0;
    $vehicleDeaths = $vehicles['vehicle_deaths'] ?? 0;
    $roadkills = $vehicles['roadkills'] ?? 0;
    $turretKills = $vehicles['turret_kills'] ?? 0;
    $turretDeaths = $vehicles['turret_deaths'] ?? 0;
    $vehicleTypes = $vehicles['by_vehicle_type'] ?? [];
    $turretTypes = $vehicles['by_turret_type'] ?? [];
    
    $output = '
    <div class="windowbg stat-card">
        <h3>🚗 Vehicle Combat</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center;">
            <div class="drilldown-stat" data-stat="vehicle_kills" data-dimension="vehicle" style="cursor: pointer; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #4caf50;">'.number_format($vehicleKills).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Vehicle Kills</div>
            </div>
            <div style="padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #f44336;">'.number_format($vehicleDeaths).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Vehicle Deaths</div>
            </div>
            <div style="padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($roadkills).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Roadkills</div>
            </div>
        </div>
    </div>
    
    <div class="windowbg stat-card">
        <h3>🔫 Turret Stats</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; text-align: center;">
            <div class="drilldown-stat" data-stat="turret_kills" data-dimension="turret" style="cursor: pointer; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #4caf50;">'.number_format($turretKills).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Turret Kills</div>
            </div>
            <div style="padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #f44336;">'.number_format($turretDeaths).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Turret Deaths</div>
            </div>
        </div>
    </div>';
    
    // Vehicle breakdown
    if (!empty($vehicleTypes)) {
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>🚙 Kills by Vehicle Type</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">';
        
        foreach ($vehicleTypes as $v) {
            $output .= '
            <div style="text-align: center; padding: 12px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-weight: bold;">'.htmlspecialchars($v['vehicle_name'] ?? 'Unknown').'</div>
                <div style="font-size: 1.5em; font-weight: bold; color: #4caf50;">'.number_format($v['kills'] ?? 0).'</div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    return $output;
}

// =============================================================================
// BOT STATS SECTION
// =============================================================================
function template_war_room_bot_section($data) {
    $bots = $data['bot_stats'] ?? [];
    
    if (empty($bots) || (($bots['bot_kills'] ?? 0) == 0 && ($bots['bot_deaths'] ?? 0) == 0)) {
        return ''; // Don't show empty bot section
    }
    
    $botKills = $bots['bot_kills'] ?? 0;
    $botDeaths = $bots['bot_deaths'] ?? 0;
    $botKd = $botDeaths > 0 ? $botKills / $botDeaths : $botKills;
    $botNames = $bots['top_bot_victims'] ?? [];
    
    $output = '
    <div class="windowbg stat-card" style="grid-column: 1 / -1;">
        <h3>🤖 Bot Performance</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div style="text-align: center; padding: 20px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #4caf50;">'.number_format($botKills).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Bot Kills</div>
            </div>
            <div style="text-align: center; padding: 20px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #f44336;">'.number_format($botDeaths).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Deaths to Bots</div>
            </div>
            <div style="text-align: center; padding: 20px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 2em; font-weight: bold; color: #2196f3;">'.number_format($botKd, 2).'</div>
                <div style="font-size: 0.9em; opacity: 0.7;">Bot K/D</div>
            </div>
        </div>';
    
    if (!empty($botNames)) {
        $output .= '
        <div style="margin-top: 15px;">
            <h4 style="margin: 0 0 10px 0; font-size: 0.9em; opacity: 0.7;">Most Killed Bots</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">';
        foreach (array_slice($botNames, 0, 5) as $bot) {
            $output .= '<span style="padding: 5px 10px; background: rgba(0,0,0,0.1); border-radius: 4px; font-size: 0.9em;">'.htmlspecialchars($bot['name'] ?? 'Bot').' ('.number_format($bot['kills'] ?? 0).')</span>';
        }
        $output .= '</div></div>';
    }
    
    $output .= '</div>';
    return $output;
}

// =============================================================================
// WORLD INTERACTION SECTION
// =============================================================================
function template_war_room_world_section($data) {
    $world = $data['world_stats'] ?? [];
    
    if (empty($world)) {
        return ''; // Don't show empty section
    }
    
    $ladderClimbs = $world['ladder_climbs'] ?? 0;
    $doorsOpened = $world['doors_opened'] ?? 0;
    $fallDamage = $world['fall_damage_taken'] ?? 0;
    $fallDeaths = $world['fall_deaths'] ?? 0;
    $chatMessages = $world['chat_messages'] ?? 0;
    $teamMessages = $world['team_messages'] ?? 0;
    $pickups = $world['item_pickups'] ?? [];
    
    $output = '
    <div class="mohaa-grid" style="margin-top: 20px;">
        <div class="windowbg stat-card">
            <h3>🌍 World Interactions</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">';
    
    if ($ladderClimbs > 0) {
        $output .= '
            <div style="text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 1.5em;">🪜</div>
                <div style="font-size: 1.5em; font-weight: bold;">'.number_format($ladderClimbs).'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Ladders Climbed</div>
            </div>';
    }
    
    if ($doorsOpened > 0) {
        $output .= '
            <div style="text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 1.5em;">🚪</div>
                <div style="font-size: 1.5em; font-weight: bold;">'.number_format($doorsOpened).'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Doors Opened</div>
            </div>';
    }
    
    if ($fallDamage > 0 || $fallDeaths > 0) {
        $output .= '
            <div style="text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 1.5em;">⬇️</div>
                <div style="font-size: 1.5em; font-weight: bold; color: #f44336;">'.number_format($fallDamage).'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Fall Damage ('.$fallDeaths.' deaths)</div>
            </div>';
    }
    
    $output .= '
            </div>
        </div>
        
        <div class="windowbg stat-card">
            <h3>💬 Communication</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; text-align: center;">
                <div style="padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                    <div style="font-size: 1.5em;">💬</div>
                    <div style="font-size: 1.5em; font-weight: bold;">'.number_format($chatMessages).'</div>
                    <div style="font-size: 0.8em; opacity: 0.7;">Chat Messages</div>
                </div>
                <div style="padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                    <div style="font-size: 1.5em;">📢</div>
                    <div style="font-size: 1.5em; font-weight: bold;">'.number_format($teamMessages).'</div>
                    <div style="font-size: 0.8em; opacity: 0.7;">Team Messages</div>
                </div>
            </div>
        </div>';
    
    // Item pickups
    if (!empty($pickups)) {
        $output .= '
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📦 Item Pickups</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px;">';
        
        foreach ($pickups as $item) {
            $itemIcon = match(strtolower($item['item_type'] ?? '')) {
                'health', 'medkit' => '❤️',
                'ammo' => '🎯',
                'armor' => '🛡️',
                'weapon' => '🔫',
                'grenade' => '💣',
                default => '📦'
            };
            
            $output .= '
            <div style="text-align: center; padding: 10px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <div style="font-size: 1.3em;">'.$itemIcon.'</div>
                <div style="font-size: 1.2em; font-weight: bold;">'.number_format($item['count'] ?? 0).'</div>
                <div style="font-size: 0.75em; opacity: 0.7;">'.htmlspecialchars(ucfirst($item['item_type'] ?? 'Unknown')).'</div>
            </div>';
        }
        $output .= '</div></div>';
    }
    
    $output .= '</div>';
    return $output;
}