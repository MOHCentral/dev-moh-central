<?php
/**
 * MOHAA Dashboard Template
 * Shows ALL global stats and player's personal stats
 *
 * @package MohaaPlayers
 * @version 1.0.0
 */

/**
 * Main dashboard template
 */
function template_mohaa_dashboard()
{
    global $context, $txt, $scripturl, $user_info;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_my_dashboard'], '</h3>
    </div>';

    // Quick Stats Row
    echo '
    <div class="mohaa-dashboard-header windowbg">
        <div class="dashboard-welcome">
            <h2>', sprintf($txt['mohaa_welcome_player'], $user_info['name']), '</h2>';

    if ($context['mohaa_has_identity']) {
        echo '
            <p>', $txt['mohaa_viewing_as'], ': <strong>', $context['mohaa_my']['player']['name'], '</strong>
                <span class="verified-badge">✓ ', $txt['mohaa_linked'], '</span>
            </p>';
    } else {
        echo '
            <div class="noticebox">
                ', $txt['mohaa_no_identity_linked'], '
                <a href="', $scripturl, '?action=profile;area=mohaaidentity">', $txt['mohaa_link_now'], '</a>
            </div>';
    }

    echo '
        </div>
    </div>';

    // Two-column layout: My Stats | Global Stats
    echo '
    <div class="mohaa-dashboard-grid">';

    // ========================================================================
    // LEFT COLUMN: My Personal Stats
    // ========================================================================
    echo '
        <div class="mohaa-dashboard-column">';

    if ($context['mohaa_has_identity']) {
        template_dashboard_my_stats();
        template_dashboard_my_performance();
        template_dashboard_my_matches();
        template_dashboard_my_achievements();
    } else {
        template_dashboard_link_prompt();
    }

    echo '
        </div>';

    // ========================================================================
    // RIGHT COLUMN: Global Stats
    // ========================================================================
    echo '
        <div class="mohaa-dashboard-column">';

    template_dashboard_global_stats();
    template_dashboard_leaderboard();
    template_dashboard_live_matches();
    template_dashboard_recent_activity();

    echo '
        </div>
    </div>';

    // Full width: Weapon distribution
    template_dashboard_weapons();
}

/**
 * My personal stats card
 */
function template_dashboard_my_stats()
{
    global $context, $txt, $scripturl;

    $my = $context['mohaa_my'];
    $stats = $my['player'];
    $kd = ($stats['deaths'] ?? 0) > 0 ? round(($stats['kills'] ?? 0) / $stats['deaths'], 2) : ($stats['kills'] ?? 0);
    $kdClass = $kd >= 1 ? 'positive' : 'negative';

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">📊 ', $txt['mohaa_my_stats'], '</h4></div>
        <div class="windowbg">
            <div class="mohaa-stat-cards compact">
                <div class="mohaa-stat-card">
                    <div class="stat-value">#', $my['rank'] ?? 'N/A', '</div>
                    <div class="stat-label">', $txt['mohaa_global_rank'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($stats['kills'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_kills'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($stats['deaths'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_deaths'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value ', $kdClass, '">', $kd, '</div>
                    <div class="stat-label">', $txt['mohaa_kd'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($stats['matches_won'] ?? 0), '</div>
                    <div class="stat-label">WINS</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($stats['matches_played'] ?? 0), '</div>
                    <div class="stat-label">MATCHES</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($stats['headshots'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_headshots'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', round($stats['accuracy'] ?? 0, 1), '%</div>
                    <div class="stat-label">', $txt['mohaa_accuracy'], '</div>
                </div>
            </div>
            <div class="mohaa-view-all">
                <a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($my['guid']), '">', $txt['mohaa_view_full_profile'], ' →</a>
            </div>
        </div>
    </div>';
}

/**
 * My performance chart
 */
function template_dashboard_my_performance()
{
    global $context, $txt;

    $perf = $context['mohaa_my']['performance'] ?? [];

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">📈 ', $txt['mohaa_my_performance'], '</h4></div>
        <div class="windowbg">
            <div style="height: 200px;">
                <canvas id="myPerformanceChart"></canvas>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("myPerformanceChart");
        if (ctx) {
            var labels = ' . json_encode($perf['labels'] ?? []) . ';
            var kills = ' . json_encode($perf['kills'] ?? []) . ';
            var deaths = ' . json_encode($perf['deaths'] ?? []) . ';
            
            var options = {
                series: [{
                    name: "Kills",
                    data: kills
                }, {
                    name: "Deaths",
                    data: deaths
                }],
                chart: {
                    height: 200,
                    type: "area",
                    toolbar: { show: false },
                    zoom: { enabled: false }
                },
                dataLabels: { enabled: false },
                stroke: { curve: "smooth", width: 2 },
                xaxis: {
                    categories: labels,
                    labels: { show: false },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: { show: false },
                colors: ["#4ade80", "#f87171"],
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                grid: { show: false, padding: { left: 0, right: 0, top: 0, bottom: 0 } },
                tooltip: { theme: "dark" }
            };

            new ApexCharts(ctx, options).render();
        }
    });
    </script>';
}

/**
 * My recent matches
 */
function template_dashboard_my_matches()
{
    global $context, $txt, $scripturl;

    $matches = $context['mohaa_my']['recent_matches'] ?? [];

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">🎮 ', $txt['mohaa_my_recent_matches'], '</h4></div>
        <div class="windowbg">';

    if (empty($matches)) {
        echo '<p class="centertext">', $txt['mohaa_no_matches'], '</p>';
    } else {
        echo '<ul class="mohaa-match-history">';
        foreach (array_slice($matches, 0, 5) as $match) {
            $kd = ($match['kills'] ?? 0) - ($match['deaths'] ?? 0);
            $kdClass = $kd >= 0 ? 'positive' : 'negative';

            echo '
            <li class="match-item">
                <a href="', $scripturl, '?action=mohaastats;sa=match;id=', $match['id'], '">
                    <span class="match-kd ', $kdClass, '">', ($kd >= 0 ? '+' : ''), $kd, '</span>
                    <span class="match-details">
                        <span class="match-map">', $match['map_name'], '</span>
                        <span class="match-stats">', $match['kills'], 'K / ', $match['deaths'], 'D</span>
                    </span>
                    <span class="match-time">', timeformat($match['ended_at'] ?? time()), '</span>
                </a>
            </li>';
        }
        echo '</ul>';
    }

    echo '
        </div>
    </div>';
}

/**
 * My achievements
 */
function template_dashboard_my_achievements()
{
    global $context, $txt, $scripturl;

    $achievements = $context['mohaa_my']['achievements'] ?? [];
    $unlocked = array_filter($achievements, fn($a) => !empty($a['unlocked']));

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">🏆 ', $txt['mohaa_my_achievements'], '</h4></div>
        <div class="windowbg">
            <p>', sprintf($txt['mohaa_achievements_unlocked'], count($unlocked), count($achievements)), '</p>
            <div class="achievement-preview">';

    foreach (array_slice($unlocked, 0, 4) as $ach) {
        echo '
                <span class="achievement-mini" title="', htmlspecialchars($ach['name']), '">',
                    $ach['icon'] ?? '🎖️',
                '</span>';
    }

    echo '
            </div>
            <div class="mohaa-view-all">
                <a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($context['mohaa_my']['guid']), '#achievements">', $txt['mohaa_view_all'], ' →</a>
            </div>
        </div>
    </div>';
}

/**
 * Prompt to link identity
 */
function template_dashboard_link_prompt()
{
    global $txt, $scripturl;

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">🔗 ', $txt['mohaa_link_identity'], '</h4></div>
        <div class="windowbg">
            <div class="centertext" style="padding: 40px 20px;">
                <div style="font-size: 3em; margin-bottom: 20px;">🎮</div>
                <h3>', $txt['mohaa_no_identity_title'], '</h3>
                <p>', $txt['mohaa_no_identity_desc'], '</p>
                <a href="', $scripturl, '?action=profile;area=mohaaidentity" class="button">', $txt['mohaa_link_now'], '</a>
            </div>
        </div>
    </div>';
}

/**
 * Global stats overview
 */
function template_dashboard_global_stats()
{
    global $context, $txt;

    $global = $context['mohaa_global']['stats'] ?? [];

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">🌍 ', $txt['mohaa_global_stats'], '</h4></div>
        <div class="windowbg">
            <div class="mohaa-stat-cards compact">
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($global['total_kills'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_total_kills'], '</div>
                </div>
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($global['total_players'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_total_players'], '</div>
                </div>
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($global['total_matches'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_total_matches'], '</div>
                </div>
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($global['active_today'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_active_today'], '</div>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Top 10 leaderboard
 */
function template_dashboard_leaderboard()
{
    global $context, $txt, $scripturl;

    $players = $context['mohaa_global']['leaderboard'] ?? [];

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">🏅 ', $txt['mohaa_top_players'], '</h4></div>
        <div class="windowbg">
            <table class="mohaa-leaderboard-mini">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>', $txt['mohaa_player'], '</th>
                        <th>', $txt['mohaa_kills'], '</th>
                        <th>', $txt['mohaa_kd'], '</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($players, 0, 10) as $i => $player) {
        $rank = $i + 1;
        $kd = ($player['deaths'] ?? 0) > 0 ? round(($player['kills'] ?? 0) / $player['deaths'], 2) : ($player['kills'] ?? 0);

        echo '
                    <tr>
                        <td class="rank rank-', $rank, '">', $rank, '</td>
                        <td><a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid'] ?? $player['id'] ?? ''), '">', $player['name'], '</a></td>
                        <td>', number_format($player['kills'] ?? 0), '</td>
                        <td>', $kd, '</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
            <div class="mohaa-view-all">
                <a href="', $scripturl, '?action=mohaastats;sa=leaderboard">', $txt['mohaa_view_all'], ' →</a>
            </div>
        </div>
    </div>';
}

/**
 * Live matches widget
 */
function template_dashboard_live_matches()
{
    global $context, $txt, $scripturl;

    $matches = $context['mohaa_global']['live_matches'] ?? [];

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">🔴 ', $txt['mohaa_live_matches'], ' (', count($matches), ')</h4></div>
        <div class="windowbg" id="live-matches-container">';

    if (empty($matches)) {
        echo '<p class="centertext">', $txt['mohaa_no_live_matches'], '</p>';
    } else {
        foreach (array_slice($matches, 0, 3) as $match) {
            echo '
            <div class="mohaa-live-match">
                <div class="live-indicator"><span class="pulse"></span> LIVE</div>
                <div class="live-server">', htmlspecialchars($match['server_name'] ?? 'Server'), '</div>
                <div class="live-map">', $match['map_name'], '</div>
                <div class="live-players">', $match['player_count'], '/', $match['max_players'], ' ', $txt['mohaa_players'], '</div>
            </div>';
        }
    }

    echo '
        </div>
    </div>';
}

/**
 * Recent activity feed
 */
function template_dashboard_recent_activity()
{
    global $context, $txt, $scripturl;

    $matches = $context['mohaa_global']['recent_matches'] ?? [];

    echo '
    <div class="mohaa-panel">
        <div class="cat_bar"><h4 class="catbg">📋 ', $txt['mohaa_recent_activity'], '</h4></div>
        <div class="windowbg">
            <ul class="mohaa-match-list">';

    foreach (array_slice($matches, 0, 5) as $match) {
        echo '
                <li class="mohaa-match-item">
                    <a href="', $scripturl, '?action=mohaastats;sa=match;id=', $match['id'], '">
                        <span class="match-map">', $match['map_name'], '</span>
                        <span class="match-mode">', strtoupper($match['game_mode'] ?? 'DM'), '</span>
                        <span class="match-players">', $match['player_count'] ?? 0, ' players</span>
                        <span class="match-time">', timeformat($match['ended_at'] ?? time()), '</span>
                    </a>
                </li>';
    }

    echo '
            </ul>
        </div>
    </div>';
}

/**
 * Weapon distribution (full width)
 */
function template_dashboard_weapons()
{
    global $context, $txt;

    $weapons = $context['mohaa_global']['top_weapons'] ?? [];

    echo '
    <div class="mohaa-panel full-width">
        <div class="cat_bar"><h4 class="catbg">🔫 ', $txt['mohaa_weapon_distribution'], '</h4></div>
        <div class="windowbg">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <div style="height: 250px;">
                    <canvas id="globalWeaponChart"></canvas>
                </div>
                <div>
                    <table class="table_grid">
                        <thead>
                            <tr class="title_bar">
                                <th>', $txt['mohaa_weapon'], '</th>
                                <th>', $txt['mohaa_kills'], '</th>
                                <th>', $txt['mohaa_headshots'], '</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>';

    foreach (array_slice($weapons, 0, 8) as $weapon) {
        echo '
                            <tr class="windowbg">
                                <td><strong>', $weapon['name'], '</strong></td>
                                <td>', number_format($weapon['kills'] ?? 0), '</td>
                                <td>', number_format($weapon['headshots'] ?? 0), '</td>
                                <td>', round($weapon['percentage'] ?? 0, 1), '%</td>
                            </tr>';
    }

    echo '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("globalWeaponChart");
        if (ctx) {
            var labels = ' . json_encode(array_column(array_slice($weapons, 0, 5), 'name')) . ';
            var data = ' . json_encode(array_column(array_slice($weapons, 0, 5), 'kills')) . ';
            
            var options = {
                series: data.map(Number),
                labels: labels,
                chart: { type: "donut", height: 250 },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "70%",
                            labels: {
                                show: true,
                                name: { color: "#888" },
                                value: { color: "#fff" }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                legend: { position: "right", labels: { colors: "#888" } },
                colors: ["#4ea64e", "#8da745", "#c4b896", "#d4a574", "#6b7280"],
                stroke: { show: false },
                tooltip: { theme: "dark" }
            };
            
            new ApexCharts(ctx, options).render();
        }
    });
    </script>
    
    <style>
        .mohaa-dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .mohaa-dashboard-column { display: flex; flex-direction: column; gap: 20px; }
        .mohaa-panel.full-width { grid-column: 1 / -1; margin-top: 20px; }
        .mohaa-stat-cards.compact { grid-template-columns: repeat(3, 1fr); }
        .mohaa-stat-card.small { padding: 12px; }
        .mohaa-stat-card.small .stat-value { font-size: 1.5em; }
        .achievement-preview { display: flex; gap: 10px; margin: 15px 0; }
        .achievement-mini { font-size: 2em; }
        .dashboard-welcome { padding: 20px; }
        @media (max-width: 768px) { .mohaa-dashboard-grid { grid-template-columns: 1fr; } }
    </style>';
}
