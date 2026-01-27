<?php
/**
 * MOHAA Teams Templates
 *
 * @package MohaaTeams
 * @version 1.0.0
 */

/**
 * Team list template
 */
function template_mohaa_teams_list()
{
    global $context, $txt, $scripturl, $user_info;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_teams'], '</h3>
    </div>
    
    <div class="windowbg" style="display: flex; justify-content: space-between; align-items: center;">
        <p>', $txt['mohaa_teams_intro'], '</p>';

    if (!empty($context['can_create_team'])) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=create" class="button">', $txt['mohaa_create_team'], '</a>';
    }

    echo '
    </div>
    
    <div class="mohaa-teams-grid">';

    foreach ($context['mohaa_teams'] as $rank => $team) {
        echo '
        <div class="mohaa-team-card windowbg">
            <div class="team-rank">#', ($rank + 1), '</div>
            <div class="team-header">
                <div class="team-logo">';
        
        if (!empty($team['logo_url'])) {
            echo '<img src="', htmlspecialchars($team['logo_url']), '" alt="', htmlspecialchars($team['team_name']), '" />';
        } else {
            echo '<div class="default-logo">', strtoupper(substr($team['team_name'], 0, 2)), '</div>';
        }

        echo '
                </div>
                <div class="team-info">
                    <h4>
                        <a href="', $scripturl, '?action=mohaateams;sa=view;id=', $team['id_team'], '">';

        if (!empty($team['team_tag'])) {
            echo '[', htmlspecialchars($team['team_tag']), '] ';
        }

        echo htmlspecialchars($team['team_name']), '</a>
                    </h4>
                    <span class="team-captain">Captain: ', htmlspecialchars($team['captain_name']), '</span>
                </div>
            </div>
            
            ' . (!empty($team['recruiting']) ? '<div class="recruiting-badge">👋 Recruiting</div>' : '') . '

            <div class="team-stats">
                <div class="stat">
                    <span class="value">', $team['rating'], '</span>
                    <span class="label">Rating</span>
                </div>
                <div class="stat">
                    <span class="value">', $team['wins'], '-', $team['losses'], '</span>
                    <span class="label">W/L</span>
                </div>
                <div class="stat">
                    <span class="value">', $team['member_count'], '</span>
                    <span class="label">Members</span>
                </div>
            </div>
        </div>';
    }

    if (empty($context['mohaa_teams'])) {
        echo '
        <div class="windowbg">
            <p class="centertext">', $txt['mohaa_no_teams'], '</p>
        </div>';
    }

    echo '
    </div>
    
    <style>
        .mohaa-teams-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 15px; }
        .mohaa-team-card { padding: 20px; border-radius: 8px; position: relative; }
        .team-rank { position: absolute; top: 10px; right: 10px; background: #4a5d23; color: white; padding: 4px 10px; border-radius: 4px; font-weight: bold; }
        .team-header { display: flex; gap: 15px; margin-bottom: 15px; }
        .team-logo img, .default-logo { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
        .default-logo { background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.5em; }
        .team-info h4 { margin: 0 0 5px; }
        .team-captain { font-size: 0.9em; color: #666; }
        .team-stats { display: flex; justify-content: space-around; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); }
        .team-stats .stat { text-align: center; }
        .team-stats .value { display: block; font-size: 1.2em; font-weight: bold; color: #4a5d23; }
        .team-stats .label { font-size: 0.8em; color: #666; }
        
        .recruiting-badge {
            display: inline-block;
            background: rgba(46, 125, 50, 0.9);
            color: #fff;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 12px;
            margin-top: 10px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: recruitPulse 2s infinite;
        }
        
        @keyframes recruitPulse {
            0% { box-shadow: 0 0 0 0 rgba(46, 125, 50, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(46, 125, 50, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 125, 50, 0); }
        }
    </style>';
}

/**
 * Team view template
 */
function template_mohaa_team_view()
{
    global $context, $txt, $scripturl;

    $team = $context['mohaa_team']['info'];
    $members = $context['mohaa_team']['members'];
    $matches = $context['mohaa_team']['matches'];

    // Team header
    echo '
    <div class="mohaa-team-header windowbg">
        <div class="team-logo-large">';

    if (!empty($team['logo_url'])) {
        echo '<img src="', htmlspecialchars($team['logo_url']), '" alt="', htmlspecialchars($team['team_name']), '" />';
    } else {
        echo '<div class="default-logo-large">', strtoupper(substr($team['team_name'], 0, 2)), '</div>';
    }

    echo '
        </div>
        <div class="team-details">
            <h2>';

    if (!empty($team['team_tag'])) {
        echo '<span class="team-tag">[', htmlspecialchars($team['team_tag']), ']</span> ';
    }

    echo htmlspecialchars($team['team_name']), '</h2>
            <p class="description">', nl2br(htmlspecialchars($team['description'] ?: $txt['mohaa_no_description'])), '</p>
            <div class="team-meta">
                <span>👑 Captain: <a href="', $scripturl, '?action=mohaastats;sa=player;u=', $team['id_captain'], '">', htmlspecialchars($team['captain_name']), '</a></span>
                <span>📅 Founded: ', timeformat($team['founded_date']), '</span>
            </div>
        </div>
        </div>
        <div class="team-stats-large">
            <div class="stat">
                <div class="value">', $team['rating'], '</div>
                <div class="label">Rating</div>
            </div>
            <div class="stat">
                <div class="value">', $team['wins'], '</div>
                <div class="label">Wins</div>
            </div>
            <div class="stat">
                <div class="value">', $team['losses'], '</div>
                <div class="label">Losses</div>
            </div>
            <div class="stat">
                <div class="value">', number_format($context['mohaa_team']['stats']['total_kills']), '</div>
                <div class="label">Total Kills</div>
            </div>
            <div class="stat">
                <div class="value">', $context['mohaa_team']['stats']['total_kd'], '</div>
                <div class="label">Team K/D</div>
            </div>
            <div class="stat full-width">
                <div class="value">', format_playtime($context['mohaa_team']['stats']['total_playtime']), '</div>
                <div class="label">Total Playtime</div>
            </div>
        </div>
    </div>';

    // Action buttons
    echo '
    <div class="windowbg" style="text-align: right;">';

    if ($context['mohaa_team']['can_join']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=join;id=', $team['id_team'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">', $txt['mohaa_request_join'], '</a>';
    } elseif ($context['mohaa_team']['has_pending']) {
        echo '<span class="infobox">', $txt['mohaa_request_pending'], '</span>';
    }

    if ($context['mohaa_team']['my_membership'] && !$context['mohaa_team']['is_captain']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=leave;id=', $team['id_team'], ';', $context['session_var'], '=', $context['session_id'], '" class="button" onclick="return confirm(\'', $txt['mohaa_leave_confirm'], '\');">', $txt['mohaa_leave_team'], '</a>';
    }

    if ($context['mohaa_team']['is_officer']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=manage;id=', $team['id_team'], '" class="button">', $txt['mohaa_manage'], '</a>';
    }

    if ($context['mohaa_team']['is_captain']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=retire;id=', $team['id_team'], ';', $context['session_var'], '=', $context['session_id'], '" class="button" style="background-color: #d32f2f; color: white;" onclick="return confirm(\'Are you sure you want to RETIRE this team? This will archive the team and remove all members.\');">Retire Team</a>';
    } 
    // Challenge Button (Visible to Captains of OTHER teams)
    elseif (!$context['mohaa_team']['my_membership'] && !$user_info['is_guest']) {
        // Simple check: show button, backend validates if user is actually a captain of another team
         echo '
        <a href="', $scripturl, '?action=mohaateams;sa=challenge;id=', $team['id_team'], '" class="button" style="background-color: #e67e22; color: white;">⚔️ Challenge Team</a>';
    }

    echo '
    </div>';

    // --- TAB NAVIGATION ---
    echo '
    <div class="mohaa-tabs windowbg">
        <button class="mohaa-tab active" onclick="openTab(event, \'tab-dashboard\')">📊 Dashboard</button>
        <button class="mohaa-tab" onclick="openTab(event, \'tab-roster\')">👥 Roster (' . count($members) . ')</button>
        <button class="mohaa-tab" onclick="openTab(event, \'tab-matches\')">⚔️ Matches</button>
    </div>';

    // --- DASHBOARD TAB ---
    echo '<div id="tab-dashboard" class="mohaa-tab-content" style="display: block;">
        <div class="mohaa-dashboard-grid">
            <!-- Left Column: Stats Cards -->
            <div class="dashboard-col-left">
                <div class="cat_bar"><h4 class="catbg">Performance Overview</h4></div>
                <div class="windowbg stats-overview">
                    <div class="stat-row">
                        <div class="stat-item">
                            <span class="label">Matches</span>
                            <span class="value">', $team['wins'] + $team['losses'] + $team['draws'], '</span>
                        </div>
                        <div class="stat-item">
                            <span class="label">Win Rate</span>
                            <span class="value">', ($team['wins'] + $team['losses'] > 0 ? round(($team['wins'] / ($team['wins'] + $team['losses'])) * 100) . '%' : 'N/A'), '</span>
                        </div>
                    </div>
                </div>
                
                 <div class="cat_bar"><h4 class="catbg">Team Composition</h4></div>
                 <div class="windowbg">
                    <div id="weaponChart"></div>
                 </div>
            </div>

            <!-- Right Column: Graphs -->
            <div class="dashboard-col-right">
                <div class="cat_bar"><h4 class="catbg">Win / Loss Ratio</h4></div>
                <div class="windowbg chart-container">
                    <div id="wlChart"></div>
                </div>

                <div class="cat_bar"><h4 class="catbg">Map Dominance</h4></div>
                <div class="windowbg chart-container">
                    <div id="mapChart"></div>
                </div>

                <div class="cat_bar"><h4 class="catbg">Activity (30 Days)</h4></div>
                <div class="windowbg chart-container">
                    <div id="activityChart"></div>
                </div>
            </div>
        </div>

        <div class="mohaa-dashboard-sections" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
             <!-- Tournament History -->
             <div>
                <div class="cat_bar"><h4 class="catbg">🏆 Tournament History</h4></div>
                <div class="windowbg">
                ' . (!empty($context['mohaa_team']['tournaments']) ? '
                    <ul class="tournament-list">
                    ' . implode('', array_map(function($t) {
                        return '<li><span class="t-badge">'.$t['badge'].'</span> <strong>'.$t['placement'].'</strong> - '.$t['name'].' <span class="t-date">('.date('M Y', $t['date']).')</span></li>';
                    }, $context['mohaa_team']['tournaments'])) . '
                    </ul>' : '<p class="centertext">No tournament participation yet.</p>') . '
                </div>
             </div>

             <!-- Upcoming Matches -->
             <div>
                <div class="cat_bar"><h4 class="catbg">📅 Upcoming Matches</h4></div>
                <div class="windowbg">
                ' . (!empty($context['mohaa_team']['challenges']) ? '
                    <ul class="upcoming-matches">
                    ' . implode('', array_map(function($c) use ($team) {
                         $opponent = ($c['target_id'] == $team['id_team']) ? $c['challenger_name'] : $c['target_name'];
                         $map = $c['map'] ?: 'TBA';
                         return '<li><strong>vs ' . htmlspecialchars($opponent) . '</strong><br><span class="smalltext">'.date('d M H:i', $c['match_date']).' @ '.$map.'</span></li>';
                    }, $context['mohaa_team']['challenges'])) . '
                    </ul>' : '<p class="centertext">No upcoming fixtures scheduled.</p>') . '
                </div>
             </div>
        </div>
    </div>';

    // --- ROSTER TAB ---
    echo '<div id="tab-roster" class="mohaa-tab-content" style="display: none;">
        <div class="cat_bar"><h4 class="catbg">Active Roster</h4></div>
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <thead>
                    <tr class="title_bar">
                        <th>Player</th>
                        <th>Role</th>
                        <th>K/D</th>
                        <th>Kills</th>
                        <th>Playtime</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($members as $m) {
        $kd = !empty($m['stats']) ? ($m['stats']['deaths'] > 0 ? round($m['stats']['kills'] / $m['stats']['deaths'], 2) : $m['stats']['kills']) : '-';
        $kills = !empty($m['stats']) ? number_format($m['stats']['kills']) : '-';
        $pt = !empty($m['stats']) ? format_playtime($m['stats']['playtime']) : '-';
        
        $roleIcon = match($m['role']) { 'captain' => '👑', 'officer' => '⭐', 'substitute' => '🔄', default => '🎮' };

        echo '
                    <tr class="windowbg">
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                '. (!empty($m['avatar']) ? '<img src="'.$m['avatar'].'" style="width:30px; height:30px; border-radius:50%;">' : '<div class="default-avatar-small">'.strtoupper(substr($m['member_name'], 0, 1)).'</div>') .'
                                <a href="', $scripturl, '?action=mohaastats;sa=player;u=', $m['id_member'], '">', htmlspecialchars($m['real_name'] ?: $m['member_name']), '</a>
                            </div>
                        </td>
                        <td>', $roleIcon, ' ', ucfirst($m['role']), '</td>
                        <td><strong>', $kd, '</strong></td>
                        <td>', $kills, '</td>
                        <td>', $pt, '</td>
                        <td>', timeformat($m['joined_date']), '</td>
                    </tr>';
    }
    echo '      </tbody>
            </table>
        </div>
    </div>';

    // --- MATCHES TAB ---
    echo '<div id="tab-matches" class="mohaa-tab-content" style="display: none;">';
    if (!empty($matches)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">Match History</h4></div>
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>Result</th>
                    <th>Opponent</th>
                    <th>Score</th>
                    <th>Map</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($matches as $match) {
            $resultClass = match($match['result']) { 'win' => 'result-win', 'loss' => 'result-loss', default => 'result-draw' };
            echo '
                <tr class="windowbg">
                    <td><span class="match-result ', $resultClass, '">', strtoupper($match['result']), '</span></td>
                    <td>', (!empty($match['id_opponent']) ? '<a href="'.$scripturl.'?action=mohaateams;sa=view;id='.$match['id_opponent'].'">'.htmlspecialchars($match['opponent_team_name']).'</a>' : htmlspecialchars($match['opponent_name'] ?: 'Unknown')), '</td>
                    <td><strong>', $match['team_score'], ' - ', $match['opponent_score'], '</strong></td>
                    <td>', htmlspecialchars($match['map'] ?: '-'), '</td>
                    <td>', timeformat($match['match_date']), '</td>
                </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="windowbg"><p class="centertext">No matches recorded yet.</p></div>';
    }
    echo '</div>';

    // --- APEX CHARTS ---
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("mohaa-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("mohaa-tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        const weaponData = ' . json_encode($context['mohaa_team']['stats']['weapon_usage'] ?? []) . ';
        const mapData = ' . json_encode($context['mohaa_team']['stats']['map_stats'] ?? []) . ';
        const activityData = ' . json_encode($context['mohaa_team']['stats']['activity_stats'] ?? []) . ';
        const wins = ' . $team['wins'] . ';
        const losses = ' . $team['losses'] . ';
        const draws = ' . $team['draws'] . ';

        // Weapon Chart (Donut)
        if (Object.keys(weaponData).length > 0) {
            new ApexCharts(document.querySelector("#weaponChart"), {
                series: Object.values(weaponData),
                labels: Object.keys(weaponData),
                chart: { type: "donut", height: 250 },
                colors: ["#3498db", "#e74c3c", "#f1c40f", "#2ecc71", "#9b59b6", "#1abc9c"],
                plotOptions: {
                    pie: { donut: { size: "70%" } }
                },
                legend: { position: "right" },
                tooltip: { theme: "dark" }
            }).render();
        }

        // Win/Loss Chart (Pie)
        new ApexCharts(document.querySelector("#wlChart"), {
            series: [wins, losses, draws],
            labels: ["Wins", "Losses", "Draws"],
            chart: { type: "pie", height: 250 },
            colors: ["#2ecc71", "#e74c3c", "#95a5a6"],
            legend: { position: "bottom" },
            tooltip: { theme: "dark" }
        }).render();

        // Map Dominance Chart (Bar)
        if (Object.keys(mapData).length > 0) {
            const mapLabels = Object.keys(mapData);
            const mapWins = mapLabels.map(m => mapData[m].wins);
            
            new ApexCharts(document.querySelector("#mapChart"), {
                series: [{ name: "Wins", data: mapWins }],
                chart: { type: "bar", height: 250, toolbar: {show:false} },
                plotOptions: {
                    bar: { horizontal: true, borderRadius: 4 }
                },
                xaxis: { categories: mapLabels },
                colors: ["#2ecc71"],
                tooltip: { theme: "dark" }
            }).render();
        }

        // Activity Chart (Area)
        if (Object.keys(activityData).length > 0) {
            new ApexCharts(document.querySelector("#activityChart"), {
                series: [{ name: "Matches", data: Object.values(activityData) }],
                chart: { type: "area", height: 200, toolbar: {show:false} },
                xaxis: { 
                    categories: Object.keys(activityData),
                    labels: { show: false }
                },
                colors: ["#3498db"],
                dataLabels: { enabled: false },
                stroke: { curve: "smooth", width: 2 },
                fill: {
                     type: "gradient",
                     gradient: {
                         shadeIntensity: 1,
                         opacityFrom: 0.7,
                         opacityTo: 0.1,
                         stops: [0, 90, 100]
                     }
                },
                grid: { show: false },
                tooltip: { theme: "dark" }
            }).render();
        }
    </script>';

    echo '
    <style>
        .mohaa-team-header { display: grid; grid-template-columns: 120px 1fr 200px; gap: 25px; align-items: start; padding: 25px; }
        .team-logo-large img, .default-logo-large { width: 120px; height: 120px; border-radius: 12px; }
        .default-logo-large { background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-size: 3em; font-weight: bold; }
        .team-details h2 { margin: 0 0 10px; }
        .team-tag { color: #4a5d23; }
        .team-meta span { display: block; margin-top: 5px; color: #666; }
        .team-meta span { display: block; margin-top: 5px; color: #666; }
        .team-stats-large { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .team-stats-large .stat { background: #f5f5f5; padding: 15px; text-align: center; border-radius: 8px; }
        .team-stats-large .stat.full-width { grid-column: span 2; }
        .team-stats-large .value { font-size: 1.8em; font-weight: bold; color: #4a5d23; }
        .team-stats-large .label { color: #666; font-size: 0.9em; }
        .mohaa-roster { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .roster-member { display: flex; gap: 12px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
        .member-avatar img, .default-avatar { width: 45px; height: 45px; border-radius: 50%; }
        .default-avatar { background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .member-info a { display: block; font-weight: bold; }
        .member-info .role { font-size: 0.85em; color: #666; }
        .match-result { padding: 3px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
        .result-win { background: #4ade80; color: #166534; }
        .result-loss { background: #f87171; color: #991b1b; }
        .result-draw { background: #fbbf24; color: #92400e; }
        
        /* Tabs */
        .mohaa-tabs { overflow: hidden; display:flex; gap: 5px; padding: 10px; border-bottom: 3px solid #4a5d23; }
        .mohaa-tabs button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 10px 20px; transition: 0.3s; font-size: 16px; border-radius: 5px 5px 0 0; font-weight: bold; color: #555; }
        .mohaa-tabs button:hover { background-color: #e0e0e0; }
        .mohaa-tabs button.active { background-color: #4a5d23; color: white; }
        
        /* Dashboard */
        .mohaa-tab-content { display: none; padding-top: 20px; animation: fadeEffect 0.5s; }
        .mohaa-dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 800px) { .mohaa-dashboard-grid { grid-template-columns: 1fr; } }
        
        .stat-row { display: flex; justify-content: space-around; padding: 10px; }
        .stat-item { text-align: center; }
        .stat-item .value { display: block; font-size: 2em; font-weight: bold; color: #4a5d23; }
        .stat-item .label { font-size: 0.9em; color: #666; }
        
        .default-avatar-small { width: 30px; height: 30px; border-radius: 50%; background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8em; font-weight: bold; }
        @keyframes fadeEffect { from {opacity: 0;} to {opacity: 1;} }
    </style>';
}

/**
 * Create team template
 */
function template_mohaa_team_create()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_create_team'], '</h3>
    </div>';

    if (!empty($context['mohaa_error'])) {
        echo '<div class="errorbox">', $context['mohaa_error'], '</div>';
    }

    echo '
    <form action="', $scripturl, '?action=mohaateams;sa=create" method="post">
        <div class="windowbg">
            <dl class="settings">
                <dt><label for="team_name">', $txt['mohaa_team_name'], ' <span class="required">*</span></label></dt>
                <dd><input type="text" name="team_name" id="team_name" size="50" maxlength="100" required /></dd>
                
                <dt><label for="team_tag">', $txt['mohaa_team_tag'], '</label></dt>
                <dd><input type="text" name="team_tag" id="team_tag" size="10" maxlength="10" placeholder="e.g. ABC" /></dd>
                
                <dt><label for="description">', $txt['mohaa_description'], '</label></dt>
                <dd><textarea name="description" id="description" rows="4" cols="50"></textarea></dd>
                
                <dt><label for="recruiting">Recruiting Players?</label></dt>
                <dd>
                    <input type="checkbox" name="recruiting" id="recruiting" value="1" />
                    <span class="smalltext">Check this if your team is actively looking for new members.</span>
                </dd>

                <dt><label for="logo_url">', $txt['mohaa_logo_url'], '</label></dt>
                <dd><input type="url" name="logo_url" id="logo_url" size="50" placeholder="https://..." /></dd>
            </dl>
        </div>
        <div class="windowbg" style="text-align: right;">
            <input type="submit" name="create_team" value="', $txt['mohaa_create'], '" class="button" />
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
        </div>
    </form>';
}

/**
 * Team manage template
 */
function template_mohaa_team_manage()
{
    global $context, $txt, $scripturl;

    $team = $context['mohaa_team_manage']['team'];
    $members = $context['mohaa_team_manage']['members'];
    $requests = $context['mohaa_team_manage']['requests'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_manage'], ': ', htmlspecialchars($team['team_name']), '</h3>
    </div>
    
    <!-- Team Settings Form -->
    <div class="cat_bar"><h4 class="catbg">⚙️ Team Settings</h4></div>
    <div class="windowbg">
        <form action="', $scripturl, '?action=mohaateams;sa=manage;id=', $team['id_team'], '" method="post">
            <dl class="settings">
                <dt><label for="description">Tag & Description:</label></dt>
                <dd>
                    <input type="text" name="team_tag" value="', htmlspecialchars($team['team_tag']), '" size="10" placeholder="Tag" />
                    <textarea name="description" rows="2" style="width: 100%; margin-top: 5px;" placeholder="Team Description">', htmlspecialchars($team['description']), '</textarea>
                </dd>
                
                <dt><label for="logo_url">Logo URL:</label></dt>
                <dd>
                    <input type="url" name="logo_url" value="', htmlspecialchars($team['logo_url']), '" style="width: 100%;" placeholder="https://..." />
                </dd>
                
                <dt><label for="recruiting">Recruitment Status:</label></dt>
                <dd>
                    <label class="toggle-switch">
                        <input type="checkbox" name="recruiting" value="1" ', (!empty($team['recruiting']) ? 'checked' : ''), '>
                         <span style="font-weight: bold; color: ', (!empty($team['recruiting']) ? '#2ecc71' : '#7f8c8d'), ';">
                            ', (!empty($team['recruiting']) ? '✅ actively recruiting' : '🚫 not recruiting'), '
                        </span>
                    </label>
                </dd>
            </dl>
            <div style="text-align: right; margin-top: 10px;">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                <input type="hidden" name="action" value="save_settings" />
                <button type="submit" class="button">💾 Save Changes</button>
            </div>
        </form>
    </div>';

    // Pending join requests
    if (!empty($requests)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_join_requests'], ' (', count($requests), ')</h4></div>
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <tbody>';

        foreach ($requests as $req) {
            echo '
                    <tr class="windowbg">
                        <td><a href="', $scripturl, '?action=mohaastats;sa=player;u=', $req['id_member'], '">', htmlspecialchars($req['member_name']), '</a></td>
                        <td>', timeformat($req['created_date']), '</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                                <input type="hidden" name="invite_id" value="', $req['id_invite'], '" />
                                <button type="submit" name="action" value="approve_request" class="button">', $txt['mohaa_approve'], '</button>
                                <button type="submit" name="action" value="reject_request" class="button button_red" style="background:#d32f2f;">', $txt['mohaa_decline'], '</button>
                            </form>
                        </td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>
        </div>';
    }

    // MATCH CHALLENGES (Incoming / Active)
    if (!empty($context['mohaa_manage']['challenges'])) {
        echo '
        <div class="cat_bar"><h4 class="catbg">⚔️ Match Challenges</h4></div>
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <thead>
                    <tr class="title_bar">
                        <th>Date</th>
                        <th>Opponent</th>
                        <th>Map/Mode</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($context['mohaa_manage']['challenges'] as $c) {
            $isChallenger = ($c['id_team_challenger'] == $team['id_team']);
            $opponent = $isChallenger ? $c['target_name'] : $c['challenger_name'];
            
            echo '
            <tr class="windowbg">
                <td>', date('d M Y H:i', $c['match_date']), '</td>
                <td>', htmlspecialchars($opponent), '</td>
                <td>', htmlspecialchars($c['game_mode']), ' @ ', htmlspecialchars($c['map']), '</td>
                <td>', ucfirst($c['status']), '</td>
                <td>';
            
            if (!$isChallenger && $c['status'] == 'pending') {
                echo '
                <form method="post" style="display:inline;">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                    <input type="hidden" name="challenge_id" value="', $c['id_challenge'], '" />
                    <input type="hidden" name="action" value="respond_challenge" />
                    <button type="submit" name="response" value="accept" class="button" style="background:#4ade80;">Accept</button>
                    <button type="submit" name="response" value="decline" class="button" style="background:#f87171;">Decline</button>
                </form>';
            } else {
                echo '-';
            }
            
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    // Current members
    echo '
    <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_members'], '</h4></div>
    <div class="windowbg">
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>', $txt['mohaa_player'], '</th>
                    <th>', $txt['mohaa_role'], '</th>
                    <th>', $txt['mohaa_joined'], '</th>
                    <th>', $txt['mohaa_actions'], '</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($members as $m) {
        echo '
                <tr class="windowbg">
                    <td><a href="', $scripturl, '?action=mohaastats;sa=player;u=', $m['id_member'], '">', htmlspecialchars($m['member_name']), '</a></td>
                    <td>', ucfirst($m['role']), '</td>
                    <td>', timeformat($m['joined_date']), '</td>
                    <td>';

        if ($m['role'] !== 'captain') {
            echo '
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                            <input type="hidden" name="member_id" value="', $m['id_member'], '" />
                            <select name="role" onchange="this.form.action.value=\'promote\'; this.form.submit();">
                                <option value="member" ', $m['role'] === 'member' ? 'selected' : '', '>Member</option>
                                <option value="officer" ', $m['role'] === 'officer' ? 'selected' : '', '>Officer</option>
                                <option value="substitute" ', $m['role'] === 'substitute' ? 'selected' : '', '>Substitute</option>
                            </select>
                            <input type="hidden" name="action" value="" />
                            <button type="submit" name="action" value="kick" class="button" onclick="return confirm(\'', $txt['mohaa_kick_confirm'], '\');">', $txt['mohaa_kick'], '</button>
                        </form>';
        }

        echo '
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
    echo '
    <div class="windowbg">
        <h4 style="margin-top: 0;">Invite Player</h4>
        <form action="', $scripturl, '?action=mohaateams;sa=invite;id=', $team['id_team'], '" method="post">
            <dl class="settings">
                <dt><label for="player_id">Member ID:</label></dt>
                <dd>
                    <input type="number" name="player_id" id="player_id" required />
                    <span class="smalltext">Enter the SMF Member ID of the player you want to invite.</span>
                </dd>
            </dl>
            <input type="submit" name="invite_player" value="Send Invite" class="button" />
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
        </form>
    </div>';
}

/**
 * Challenge Team Form
 */
function template_mohaa_team_challenge()
{
    global $context, $txt, $scripturl;

    $targetName = $context['mohaa_challenge']['target_name'];
    $myTeamName = $context['mohaa_challenge']['my_team_name'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">⚔️ Challenge Team</h3>
    </div>
    
    <div class="windowbg">
        <p>You are challenging <strong>', htmlspecialchars($targetName), '</strong> as Captain of <strong>', htmlspecialchars($myTeamName), '</strong>.</p>
        
        <form action="', $scripturl, '?action=mohaateams;sa=challengesubmit" method="post">
            <dl class="settings">
                <dt><label>Proposed Date/Time:</label></dt>
                <dd>
                    <input type="datetime-local" name="match_date" required />
                    <span class="smalltext">Server time.</span>
                </dd>

                <dt><label>Game Mode:</label></dt>
                <dd>
                    <select name="game_mode">
                        <option value="tdm">Team Deathmatch (TDM)</option>
                        <option value="obj">Objective (OBJ)</option>
                        <option value="ft">Freeze Tag</option>
                    </select>
                </dd>

                <dt><label>Map:</label></dt>
                <dd>
                    <input type="text" name="map" placeholder="e.g. v2_rocket" required />
                </dd>
            </dl>
            
            <div style="text-align: right; margin-top: 15px;">
                <input type="hidden" name="target_id" value="', $context['mohaa_challenge']['target_id'], '" />
                <input type="hidden" name="my_team_id" value="', $context['mohaa_challenge']['my_team_id'], '" />
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                <button type="submit" class="button" style="background-color: #e67e22; color: white; font-size: 1.1em;">🚀 Send Challenge</button>
            </div>
        </form>
    </div>';
}

/**
 * Helper: Format playtime (seconds to human readable)
 */
function format_playtime($seconds)
{
    if (!$seconds) return '0h 0m';
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    return $hours . 'h ' . $minutes . 'm';
}

/**
 * Team rankings template
 */
function template_mohaa_team_rankings()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_team_rankings'], '</h3>
    </div>
    
    <table class="table_grid" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th>#</th>
                <th>', $txt['mohaa_team'], '</th>
                <th>', $txt['mohaa_rating'], '</th>
                <th>W/L</th>
                <th>', $txt['mohaa_members'], '</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($context['mohaa_teams_ranking'] as $rank => $team) {
        echo '
            <tr class="windowbg">
                <td><strong>', ($rank + 1), '</strong></td>
                <td>
                    <a href="', $scripturl, '?action=mohaateams;sa=view;id=', $team['id_team'], '">';

        if (!empty($team['team_tag'])) {
            echo '[', htmlspecialchars($team['team_tag']), '] ';
        }

        echo htmlspecialchars($team['team_name']), '</a>
                </td>
                <td><strong>', $team['rating'], '</strong></td>
                <td>', $team['wins'], '-', $team['losses'], '</td>
                <td>', $team['member_count'], '</td>
            </tr>';
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Profile teams template
 */
function template_mohaa_profile_teams()
{
    global $context, $txt, $scripturl;

    $teams = $context['mohaa_profile_teams']['teams'];
    $invites = $context['mohaa_profile_teams']['invites'];
    $isOwn = $context['mohaa_profile_teams']['is_own'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_my_teams'], '</h3>
    </div>';

    // Pending invites
    if ($isOwn && !empty($invites)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">📨 ', $txt['mohaa_team_invites'], '</h4></div>
        <div class="windowbg">';

        foreach ($invites as $inv) {
            echo '
            <div class="invite-card" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                <div>
                    <strong><a href="', $scripturl, '?action=mohaateams;sa=view;id=', $inv['id_team'], '">', htmlspecialchars($inv['team_name']), '</a></strong>
                    <div style="color: #666;">Invited by ', htmlspecialchars($inv['inviter_name']), ' on ', timeformat($inv['created_date']), '</div>
                </div>
                <div>
                    <a href="', $scripturl, '?action=mohaateams;sa=accept;invite=', $inv['id_invite'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">', $txt['mohaa_accept'], '</a>
                    <a href="', $scripturl, '?action=mohaateams;sa=decline;invite=', $inv['id_invite'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">', $txt['mohaa_decline'], '</a>
                </div>
            </div>';
        }

        echo '
        </div>';
    }

    // Teams
    if (!empty($teams)) {
        echo '
        <div class="windowbg">
            <div class="mohaa-teams-grid">';

        foreach ($teams as $team) {
            echo '
                <div class="mohaa-team-card windowbg2">
                    <h4><a href="', $scripturl, '?action=mohaateams;sa=view;id=', $team['id_team'], '">';

            if (!empty($team['team_tag'])) {
                echo '[', htmlspecialchars($team['team_tag']), '] ';
            }

            echo htmlspecialchars($team['team_name']), '</a></h4>
                    <div>Role: <strong>', ucfirst($team['role']), '</strong></div>
                    <div>Rating: <strong>', $team['rating'], '</strong></div>
                    <div>Record: ', $team['wins'], '-', $team['losses'], '-', $team['draws'], '</div>
                    <div style="color: #666;">Joined ', timeformat($team['joined_date']), '</div>
                </div>';
        }

        echo '
            </div>
        </div>';
    } else {
        echo '
        <div class="windowbg">
            <p class="centertext">', $isOwn ? $txt['mohaa_no_teams_member'] : $txt['mohaa_user_no_teams'], '</p>
        </div>';
    }

    // Create team button
    if ($isOwn) {
        echo '
        <div class="windowbg" style="text-align: center;">
            <a href="', $scripturl, '?action=mohaateams;sa=create" class="button">', $txt['mohaa_create_team'], '</a>
        </div>';
    }
}
