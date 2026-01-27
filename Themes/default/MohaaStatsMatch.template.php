<?php
/**
 * MOHAA Stats - Match Detail Template
 *
 * @package MohaaStats
 * @version 1.0.0
 */

/**
 * Match detail main template
 */
function template_mohaa_stats_match()
{
    global $context, $scripturl, $txt;

    $match = $context['mohaa_match'];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_match_detail'], ': ', $match['map_name'], '</h3>
    </div>';

    // Match Header
    echo '
    <div class="windowbg mohaa-match-header">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center;">
            <div>
                <div style="font-size: 0.8em; color: #666;">', $txt['mohaa_map'], '</div>
                <div style="font-size: 1.2em; font-weight: bold;">', $match['map_name'], '</div>
            </div>
            <div>
                <div style="font-size: 0.8em; color: #666;">', $txt['mohaa_mode'], '</div>
                <div style="font-size: 1.2em; font-weight: bold;">', ucfirst($match['game_mode']), '</div>
            </div>
            <div>
                <div style="font-size: 0.8em; color: #666;">', $txt['mohaa_duration'], '</div>
                <div style="font-size: 1.2em; font-weight: bold;">', format_duration($match['duration']), '</div>
            </div>
            <div>
                <div style="font-size: 0.8em; color: #666;">', $txt['mohaa_players'], '</div>
                <div style="font-size: 1.2em; font-weight: bold;">', count($match['players'] ?? []), '</div>
            </div>
            <div>
                <div style="font-size: 0.8em; color: #666;">', $txt['mohaa_date'], '</div>
                <div style="font-size: 1.2em;">', timeformat($match['ended_at']), '</div>
            </div>
        </div>';

    // Team scores for team matches
    if (!empty($match['team_match'])) {
        echo '
        <div class="mohaa-team-scores" style="display: flex; justify-content: center; gap: 30px; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.1);">
            <div class="team-allies" style="text-align: center;">
                <div style="color: #3b82f6; font-size: 2em; font-weight: bold;">', $match['allies_score'], '</div>
                <div>Allies</div>
            </div>
            <div style="font-size: 2em; color: #ccc;">vs</div>
            <div class="team-axis" style="text-align: center;">
                <div style="color: #ef4444; font-size: 2em; font-weight: bold;">', $match['axis_score'], '</div>
                <div>Axis</div>
            </div>
        </div>';
    }

    echo '
    </div>';

    // Tabs
    echo '
    <div class="mohaa-tabs">
        <button class="tab-button active" data-tab="scoreboard">', $txt['mohaa_scoreboard'], '</button>
        <button class="tab-button" data-tab="versus">⚔️ Versus</button>
        <button class="tab-button" data-tab="heatmap">', $txt['mohaa_heatmap'], '</button>
        <button class="tab-button" data-tab="timeline">', $txt['mohaa_timeline'], '</button>
        <button class="tab-button" data-tab="weapons">', $txt['mohaa_weapons'], '</button>
    </div>';

    // Scoreboard Tab
    echo '
    <div id="tab-scoreboard" class="mohaa-tab-content" style="display: block;">';
    
    template_match_scoreboard($match);
    
    echo '
    </div>';

    // Versus Tab
    echo '
    <div id="tab-versus" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>Versus Matrix (Who killed Who)</h4>
        <div id="chart-versus" style="min-height: 500px;"></div>
    </div>';

    // Heatmap Tab
    echo '
    <div id="tab-heatmap" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>', $txt['mohaa_heatmap'], '</h4>
        <div class="mohaa-heatmap-controls" style="margin-bottom: 15px;">
            <label>
                <input type="radio" name="heatmap_type" value="kills" checked onchange="updateHeatmap(this.value)">
                ', $txt['mohaa_kills'], '
            </label>
            <label style="margin-left: 15px;">
                <input type="radio" name="heatmap_type" value="deaths" onchange="updateHeatmap(this.value)">
                ', $txt['mohaa_deaths'], '
            </label>
        </div>
        <div id="match-heatmap" class="mohaa-heatmap-container"></div>
        
        <script>
            var heatmapData = ', json_encode($match['heatmap_data'] ?? []), ';
            var mapImage = "Themes/default/images/mohaastats/maps/', $match['map_name'], '.jpg";
            
            document.addEventListener("DOMContentLoaded", function() {
                updateHeatmap("kills");
            });
            
            function updateHeatmap(type) {
                MohaaStats.initHeatmap("match-heatmap", mapImage, heatmapData[type] || [], type);
            }
        </script>
    </div>';

    // Timeline Tab
    echo '
    <div id="tab-timeline" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>', $txt['mohaa_timeline'], '</h4>';
    
    template_match_timeline($match['timeline'] ?? []);
    
    echo '
    </div>';

    // Weapons Tab
    echo '
    <div id="tab-weapons" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>', $txt['mohaa_weapon_breakdown'], '</h4>';
    
    template_match_weapons($match['top_weapons'] ?? []);
    
    echo '
    </div>';

    // Inject Scripts for Versus
    $versusData = [];
    if (!empty($match['versus'])) {
        foreach ($match['versus'] as $killer => $victims) {
            $dataPoints = [];
            foreach ($victims as $v) {
                // Ensure unique x,y pair?
                 $dataPoints[] = ['x' => $v['opponent_name'], 'y' => $v['kills']];
            }
            $versusData[] = ['name' => $killer, 'data' => $dataPoints];
        }
    }

    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Helper for tabs
            document.querySelectorAll(".tab-button").forEach(b => {
                b.addEventListener("click", e => {
                    document.querySelectorAll(".mohaa-tab-content").forEach(c => c.style.display = "none");
                    document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
                    document.getElementById("tab-" + e.target.getAttribute("data-tab")).style.display = "block";
                    e.target.classList.add("active");
                    
                    // Trigger map resize if needed
                    window.dispatchEvent(new Event("resize"));
                });
            });

            // Versus Heatmap
            const versusData = ', json_encode($versusData), ';
            if(versusData.length > 0) {
                 const options = {
                    series: versusData,
                    chart: { type: "heatmap", height: 600, toolbar: {show:false}, background: "transparent" },
                    dataLabels: { enabled: true, style: { colors: ["#000"] } }, // Show numbers
                    colors: ["#008FFB"],
                    title: { text: "Killer (Y) vs Victim (X)", style: { color: "#fff" } },
                    theme: { mode: "dark" },
                    xaxis: { labels: { style: { colors: "#fff" } } },
                    yaxis: { labels: { style: { colors: "#fff" } } },
                    tooltip: { theme: "dark" },
                    plotOptions: { heatmap: { shadeIntensity: 0.5, colorScale: { ranges: [{from:0, to:0, color:"transparent"}] } } }
                };
                new ApexCharts(document.querySelector("#chart-versus"), options).render();
            } else {
                 document.querySelector("#chart-versus").innerHTML = "<p class=\'centertext\'>No versus data available.</p>";
            }

            // Existing Map Heatmap
            // ...
        });
    </script>';
}

/**
 * Match scoreboard template
 */
function template_match_scoreboard($match)
{
    global $scripturl, $txt;

    $players = $match['players'] ?? [];
    
    // Sort by score descending
    usort($players, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    if (!empty($match['team_match'])) {
        // Team-based scoreboard
        $allies = array_filter($players, fn($p) => ($p['team'] ?? '') === 'allies');
        $axis = array_filter($players, fn($p) => ($p['team'] ?? '') === 'axis');

        echo '
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div class="title_bar"><h4 class="titlebg" style="color: #3b82f6;">Allies</h4></div>';
        
        template_team_table($allies, $scripturl, $txt);
        
        echo '
            </div>
            <div>
                <div class="title_bar"><h4 class="titlebg" style="color: #ef4444;">Axis</h4></div>';
        
        template_team_table($axis, $scripturl, $txt);
        
        echo '
            </div>
        </div>';
    } else {
        // FFA scoreboard
        echo '
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <thead>
                    <tr class="title_bar">
                        <th>#</th>
                        <th>', $txt['mohaa_player'], '</th>
                        <th>', $txt['mohaa_score'], '</th>
                        <th>', $txt['mohaa_kills'], '</th>
                        <th>', $txt['mohaa_deaths'], '</th>
                        <th>', $txt['mohaa_kd'], '</th>
                        <th>', $txt['mohaa_headshots'], '</th>
                    </tr>
                </thead>
                <tbody>';

        $rank = 1;
        foreach ($players as $player) {
            $kd = $player['deaths'] > 0 ? round($player['kills'] / $player['deaths'], 2) : $player['kills'];
            $kdClass = $kd >= 1 ? 'style="color: #4ade80;"' : 'style="color: #f87171;"';
            
            echo '
                    <tr class="windowbg">
                        <td><strong>', $rank++, '</strong></td>
                        <td>
                            <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['player_id'], '">
                                ', $player['name'], '
                            </a>
                        </td>
                        <td>', number_format($player['score']), '</td>
                        <td>', number_format($player['kills']), '</td>
                        <td>', number_format($player['deaths']), '</td>
                        <td ', $kdClass, '>', $kd, '</td>
                        <td>', number_format($player['headshots'] ?? 0), '</td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>
        </div>';
    }
}

/**
 * Team table helper
 */
function template_team_table($players, $scripturl, $txt)
{
    echo '
    <table class="table_grid" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th>', $txt['mohaa_player'], '</th>
                <th>', $txt['mohaa_score'], '</th>
                <th>', $txt['mohaa_kills'], '</th>
                <th>', $txt['mohaa_deaths'], '</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($players as $player) {
        echo '
            <tr class="windowbg">
                <td>
                    <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['player_id'], '">
                        ', $player['name'], '
                    </a>
                </td>
                <td>', number_format($player['score']), '</td>
                <td>', number_format($player['kills']), '</td>
                <td>', number_format($player['deaths']), '</td>
            </tr>';
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Match timeline template
 */
function template_match_timeline($events)
{
    global $txt;

    if (empty($events)) {
        echo '<p class="centertext">', $txt['mohaa_no_data'], '</p>';
        return;
    }

    echo '
    <div class="mohaa-timeline">';

    foreach ($events as $event) {
        $type = $event['type'] ?? 'default';
        $actor = htmlspecialchars($event['actor'] ?? 'Unknown');
        $target = htmlspecialchars($event['target'] ?? '');
        $detail = htmlspecialchars($event['detail'] ?? '');
        
        // Construct Description
        $desc = match($type) {
            'kill' => "<strong>$actor</strong> killed <strong>$target</strong>" . ($detail ? " with $detail" : ""),
            'headshot' => "<strong>$actor</strong> headshotted <strong>$target</strong>" . ($detail ? " with $detail" : ""),
            'flag_capture' => "<strong>$actor</strong> captured the flag!",
            'match_start' => "Match Started on " . ($detail ?: "Map"),
            'match_end' => "Match Ended",
            default => "$actor - $type"
        };
        
        $icon = match($type) {
            'kill' => '💀',
            'headshot' => '🎯',
            'flag_capture' => '🚩',
            'match_start' => '🏁',
            'match_end' => '🏆',
            default => '•'
        };
        
        echo '
        <div class="timeline-event">
            <span class="event-time">', isset($event['timestamp']) ? gmdate('i:s', (int)$event['timestamp']) : '--:--', '</span>
            <span class="event-icon">', $icon, '</span>
            <span class="event-text">', $desc, '</span>
        </div>';
    }

    echo '
    </div>
    
    <style>
        .mohaa-timeline { max-height: 400px; overflow-y: auto; }
        .timeline-event { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .event-time { color: #666; font-family: monospace; min-width: 50px; }
        .event-icon { font-size: 1.2em; }
    </style>';
}

/**
 * Match weapon stats template
 */
function template_match_weapons($weapon_stats)
{
    global $txt;

    if (empty($weapon_stats)) {
        echo '<p class="centertext">', $txt['mohaa_no_data'], '</p>';
        return;
    }

    // Sort by kills
    usort($weapon_stats, fn($a, $b) => $b['kills'] - $a['kills']);

    echo '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="height: 300px;">
            <canvas id="matchWeaponChart"></canvas>
        </div>
        <div>
            <table class="table_grid">
                <thead>
                    <tr class="title_bar">
                        <th>', $txt['mohaa_weapon'], '</th>
                        <th>', $txt['mohaa_kills'], '</th>
                        <th>', $txt['mohaa_headshots'], '</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($weapon_stats as $weapon) {
        $hsPercent = $weapon['kills'] > 0 ? round(($weapon['headshots'] / $weapon['kills']) * 100) : 0;
        
        echo '
                    <tr class="windowbg">
                        <td><strong>', $weapon['name'], '</strong></td>
                        <td>', number_format($weapon['kills']), '</td>
                        <td>', number_format($weapon['headshots']), ' (', $hsPercent, '%)</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            MohaaStats.initWeaponChart("matchWeaponChart", ', json_encode(array_slice($weapon_stats, 0, 5)), ');
        });
    </script>';
}

/**
 * Format duration helper
 */
function format_duration($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%d:%02d', $minutes, $secs);
}
