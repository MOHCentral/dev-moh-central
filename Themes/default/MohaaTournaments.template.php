<?php
/**
 * MOHAA Tournaments Templates
 *
 * @package MohaaTournaments
 * @version 1.0.0
 */

/**
 * List active tournaments
 */
function template_mohaa_tournaments_list()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">🏆 Active Tournaments</h3>
    </div>
    
    <div class="windowbg" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <p>Compete for glory, medals, and unique badges.</p>';

    if (!empty($context['can_create_tournament'])) {
        echo '
        <a href="', $scripturl, '?action=mohaatournaments;sa=create" class="button">Create Tournament</a>';
    }

    echo '
    </div>
    
    <div class="mohaa-tournaments-grid">';

    if (empty($context['mohaa_tournaments'])) {
        echo '<div class="windowbg centertext">No active tournaments at the moment.</div>';
    } else {
        foreach ($context['mohaa_tournaments'] as $t) {
            $statusColor = match($t['status']) {
                'open' => '#2ecc71', // green
                'active' => '#e67e22', // orange
                'completed' => '#95a5a6', // gray
                default => '#34495e'
            };
            
            echo '
            <div class="mohaa-tournament-card">
                <div class="t-header" style="border-left: 4px solid ', $statusColor, ';">
                    <h4><a href="', $scripturl, '?action=mohaatournaments;sa=view;id=', $t['id_tournament'], '">', htmlspecialchars($t['name']), '</a></h4>
                    <span class="t-status" style="background:', $statusColor, '">', ucfirst($t['status']), '</span>
                </div>
                <div class="t-body">
                    <p>', htmlspecialchars($t['description']), '</p>
                    <div class="t-meta">
                        <span>📅 ', timeformat($t['start_date']), '</span>
                        <span>👥 ', $t['team_count'], ' / ', $t['max_teams'], ' Teams</span>
                        <span>⚔️ ', ucfirst(str_replace('_', ' ', $t['format'])), '</span>
                    </div>
                </div>
            </div>';
        }
    }

    echo '
    </div>
    
    <style>
        .mohaa-tournaments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .mohaa-tournament-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.2s; }
        .mohaa-tournament-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .t-header { padding: 15px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; }
        .t-header h4 { margin: 0; font-size: 1.1em; }
        .t-header h4 a { color: #333; text-decoration: none; }
        .t-status { padding: 3px 8px; color: #fff; font-size: 0.75em; border-radius: 4px; text-transform: uppercase; font-weight: bold; }
        .t-body { padding: 15px; }
        .t-body p { margin: 0 0 15px; color: #666; font-size: 0.9em; height: 40px; overflow: hidden; }
        .t-meta { display: flex; flex-direction: column; gap: 5px; font-size: 0.85em; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
    </style>';
}

/**
 * Create Tournament Form
 */
function template_mohaa_tournament_create()
{
    global $context, $scripturl;
    
    echo '
    <div class="cat_bar"><h3 class="catbg">Create New Tournament</h3></div>
    <div class="windowbg">
        <form action="', $scripturl, '?action=mohaatournaments;sa=create" method="post">
            <dl class="settings">
                <dt><label>Tournament Name</label></dt>
                <dd><input type="text" name="name" size="40" required /></dd>
                
                <dt><label>Description</label></dt>
                <dd><textarea name="description" rows="3" cols="40"></textarea></dd>
                
                <dt><label>Format</label></dt>
                <dd>
                    <select name="format">
                        <option value="single_elim">Single Elimination</option>
                        <option value="double_elim">Double Elimination</option>
                        <option value="round_robin">Round Robin</option>
                    </select>
                </dd>
                
                <dt><label>Max Teams</label></dt>
                <dd><input type="number" name="max_teams" value="16" min="4" max="64" /></dd>
            </dl>
            <div style="text-align: right; margin-top: 10px;">
                <button type="submit" name="save" class="button">Create Tournament</button>
            </div>
        </form>
    </div>';
}

/**
 * View Tournament & Bracket
 */
function template_mohaa_tournament_view()
{
    global $context, $txt, $scripturl;
    
    $t = $context['mohaa_tournament']['info'];
    $parts = $context['mohaa_tournament']['participants'];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', htmlspecialchars($t['name']), '</h3>
    </div>
    
    <div class="mohaa-tabs windowbg">
        <button class="mohaa-tab active" onclick="openTab(event, \'tab-bracket\')">🏆 Bracket</button>
        <button class="mohaa-tab" onclick="openTab(event, \'tab-teams\')">👥 Teams (', count($parts), ')</button>
    </div>
    
    <!-- Teams Tab -->
    <div id="tab-teams" class="mohaa-tab-content" style="display: none;">
        <div class="windowbg">
            <div class="participants-grid">';
            
    foreach ($parts as $p) {
        echo '
        <div class="participant-card">
            <img src="', ($p['logo_url'] ?: ''), '" onerror="this.src=\'https://via.placeholder.com/40\';" class="p-logo" />
            <span class="p-name">', htmlspecialchars($p['team_name']), '</span>
            <span class="p-seed">Seed #', $p['seed'], '</span>
        </div>';
    }
    
    // Register Button
    if ($context['can_register']) {
        echo '
        <div class="participant-card join-card">
            <p>Your team is eligible!</p>
            <a href="', $scripturl, '?action=mohaatournaments;sa=register;id=', $t['id_tournament'], ';team=', $context['my_team_id'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">Register Team</a>
        </div>';
    } elseif ($t['status'] == 'open') {
        echo '<div class="participant-card disabled"><p>Registration open</p></div>';
    }
            
    echo '
            </div>
        </div>
    </div>
    
    <!-- Bracket Tab -->
    <div id="tab-bracket" class="mohaa-tab-content" style="display: block;">
        <div class="bracket-container">';
    
    // Check if we have actual match data
    $matches = $context['mohaa_tournament']['matches'] ?? [];
    
    if (empty($matches)) {
        echo '
            <div style="text-align: center; padding: 40px; width: 100%;">
                <div style="font-size: 3em; margin-bottom: 15px;">🏆</div>
                <h3>Bracket Not Yet Generated</h3>
                <p style="color: #666;">The tournament bracket will appear here once the tournament starts.</p>';
        
        if ($t['status'] == 'open') {
            echo '<p style="color: #888; font-size: 0.9em;">Current registrations: ', count($parts), '/', $t['max_teams'], ' teams</p>';
        }
        
        echo '
            </div>';
    } else {
        // Render actual bracket from database matches
        // Group matches by round
        $rounds = [];
        foreach ($matches as $match) {
            $roundNum = $match['round_number'] ?? 1;
            if (!isset($rounds[$roundNum])) {
                $rounds[$roundNum] = [];
            }
            $rounds[$roundNum][] = $match;
        }
        
        $roundNames = [1 => 'Round 1', 2 => 'Quarter Finals', 3 => 'Semi Finals', 4 => 'Finals'];
        $numRounds = count($rounds);
        
        foreach ($rounds as $roundNum => $roundMatches) {
            $roundName = $roundNames[$roundNum] ?? "Round $roundNum";
            if ($numRounds <= 2 && $roundNum == $numRounds) $roundName = 'Finals';
            elseif ($numRounds <= 3 && $roundNum == $numRounds - 1) $roundName = 'Semi Finals';
            elseif ($numRounds <= 4 && $roundNum == $numRounds - 2) $roundName = 'Quarter Finals';
            
            echo '
            <div class="bracket-round">
                <div class="round-header">', $roundName, '</div>';
            
            foreach ($roundMatches as $match) {
                $team1 = htmlspecialchars($match['team1_name'] ?? 'TBD');
                $team2 = htmlspecialchars($match['team2_name'] ?? 'TBD');
                $score1 = $match['team1_score'] ?? '-';
                $score2 = $match['team2_score'] ?? '-';
                
                $team1Class = '';
                $team2Class = '';
                if ($match['winner_id'] ?? 0) {
                    if ($match['winner_id'] == ($match['team1_id'] ?? 0)) {
                        $team1Class = 'winner';
                        $team2Class = 'loser';
                    } else {
                        $team1Class = 'loser';
                        $team2Class = 'winner';
                    }
                }
                
                echo '
                <div class="matchup">
                    <div class="b-team ', $team1Class, '">', $team1, ' <span class="score">', $score1, '</span></div>
                    <div class="b-team ', $team2Class, '">', $team2, ' <span class="score">', $score2, '</span></div>
                </div>';
            }
            
            echo '
            </div>';
        }
    }
    
    echo '
        </div>
    </div>
    
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
    </script>
    
    <style>
        .participants-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .participant-card { display: flex; align-items: center; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 6px; gap: 10px; }
        .p-logo { width: 30px; height: 30px; border-radius: 4px; object-fit: cover; background: #ccc; }
        .p-name { font-weight: bold; flex-grow: 1; }
        .p-seed { font-size: 0.8em; color: #999; }
        .join-card { justify-content: space-between; background: #e8f5e9; border-color: #a5d6a7; }
        
        /* Bracket CSS */
        .bracket-container { display: flex; gap: 40px; padding: 20px; overflow-x: auto; background: #f0f2f5; border-radius: 8px; }
        .bracket-round { display: flex; flex-direction: column; gap: 20px; min-width: 200px; }
        .round-header { text-align: center; font-weight: bold; color: #666; margin-bottom: 10px; text-transform: uppercase; font-size: 0.85em; }
        .matchup { background: #fff; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .b-team { padding: 8px 12px; display: flex; justify-content: space-between; font-size: 0.9em; }
        .b-team:first-child { border-bottom: 1px solid #eee; }
        .b-team.winner { background: #e8f5e9; font-weight: bold; }
        .b-team.loser { opacity: 0.7; }
        .b-team .score { font-weight: bold; }
        .matchup.placeholder { height: 72px; display: flex; flex-direction: column; justify-content: center; opacity: 0.5; }
    </style>';
}
