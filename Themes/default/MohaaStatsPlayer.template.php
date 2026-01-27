<?php
/**
 * MOHAA Stats - Player Profile Template
 *
 * @package MohaaStats
 * @version 1.0.0
 */

/**
 * Player profile main template
 */
function template_mohaa_stats_player()
{
    global $context, $scripturl, $txt;

    $player = $context['mohaa_player'];
    $stats = $player['stats'] ?? [];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_player_profile'], '</h3>
    </div>';

    // Player Header
    $letter = strtoupper(substr($player['name'], 0, 1));
    $kd = $stats['deaths'] > 0 ? round($stats['kills'] / $stats['deaths'], 2) : $stats['kills'];
    $kdClass = $kd >= 1 ? 'positive' : 'negative';
    
    echo '
    <div class="windowbg mohaa-player-header">
        <div class="player-avatar">
            <span class="avatar-letter">', $letter, '</span>
        </div>
        <div class="player-info">
            <h3>', $player['name'], '</h3>
            <div class="player-meta">
                <span class="player-rank">#', $player['rank'] ?? 'N/A', '</span>';
    
    if (!empty($player['verified'])) {
        echo '<span class="verified-badge">✓ Verified</span>';
    }
    
    echo '
                <span>', $txt['mohaa_first_seen'], ': ', date('M j, Y', strtotime($player['first_seen'] ?? 'now')), '</span>
                <span>', $txt['mohaa_last_seen'], ': ', timeformat($player['last_seen'] ?? time()), '</span>
            </div>
        </div>
    </div>';

    // Stats Cards
    echo '
    <div class="mohaa-stat-cards">
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
            <div class="stat-value">', number_format($stats['headshots'] ?? 0), '</div>
            <div class="stat-label">', $txt['mohaa_headshots'], '</div>
        </div>
        <div class="mohaa-stat-card">
            <div class="stat-value">', number_format($stats['playtime_hours'] ?? 0), 'h</div>
            <div class="stat-label">', $txt['mohaa_playtime'], '</div>
        </div>
        <div class="mohaa-stat-card">
            <div class="stat-value">', number_format($stats['matches'] ?? 0), '</div>
            <div class="stat-label">', $txt['mohaa_matches'], '</div>
        </div>
    </div>';

    // Tabs
    echo '
    <div class="mohaa-tabs">
        <button class="tab-button active" data-tab="matches">', $txt['mohaa_matches'], '</button>
        <button class="tab-button" data-tab="weapons">', $txt['mohaa_weapons'], '</button>
        <button class="tab-button" data-tab="achievements">', $txt['mohaa_achievements'], '</button>
        <button class="tab-button" data-tab="graphs">', $txt['mohaa_performance'], '</button>
    </div>';

    // Match History Tab
    echo '
    <div id="tab-matches" class="mohaa-tab-content windowbg" style="display: block;">
        <h4>', $txt['mohaa_recent_matches'], '</h4>';
    
    template_player_match_history($player['matches'] ?? []);
    
    echo '
    </div>';

    // Weapons Tab
    echo '
    <div id="tab-weapons" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>', $txt['mohaa_weapon_stats'], '</h4>';
    
    template_player_weapons($player['weapons'] ?? []);
    
    echo '
    </div>';

    // Achievements Tab
    echo '
    <div id="tab-achievements" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>', $txt['mohaa_achievements'], '</h4>';
    
    template_player_achievements($player['achievements'] ?? []);
    
    echo '
    </div>';

    // Performance Tab
    echo '
    <div id="tab-graphs" class="mohaa-tab-content windowbg" style="display: none;">
        <h4>', $txt['mohaa_performance'], '</h4>';
    
    template_player_graphs($player['performance'] ?? []);
    
    echo '
    </div>';
}

/**
 * Player match history sub-template
 */
function template_player_match_history($matches)
{
    global $scripturl, $txt;

    if (empty($matches)) {
        echo '<p class="centertext">', $txt['mohaa_no_matches'], '</p>';
        return;
    }

    echo '
    <ul class="mohaa-match-history">';

    foreach ($matches as $match) {
        $kd = $match['kills'] - $match['deaths'];
        $kdClass = $kd >= 0 ? 'positive' : 'negative';
        $result = $match['won'] ? 'win' : 'loss';
        
        echo '
        <li class="match-item">
            <a href="', $scripturl, '?action=mohaastats;sa=match;id=', $match['id'], '">
                <span class="match-kd ', $kdClass, '">', ($kd >= 0 ? '+' : ''), $kd, '</span>
                <span class="match-details">
                    <span class="match-map">', $match['map_name'], '</span>
                    <span class="match-stats">', $match['kills'], 'K / ', $match['deaths'], 'D / ', $match['score'], ' pts</span>
                </span>';
        
        if (isset($match['won'])) {
            echo '<span class="match-result"><span class="', $result, '">', strtoupper($result), '</span></span>';
        }
        
        echo '
                <span class="match-time">', timeformat($match['ended_at']), '</span>
            </a>
        </li>';
    }

    echo '
    </ul>';
}

/**
 * Player weapons sub-template
 */
function template_player_weapons($weapons)
{
    global $txt;

    if (empty($weapons)) {
        echo '<p class="centertext">', $txt['mohaa_no_data'], '</p>';
        return;
    }

    // Chart container
    echo '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="height: 300px;">
            <canvas id="weaponChart"></canvas>
        </div>
        <div>
            <table class="table_grid">
                <thead>
                    <tr class="title_bar">
                        <th>', $txt['mohaa_weapon'], '</th>
                        <th>', $txt['mohaa_kills'], '</th>
                        <th>', $txt['mohaa_headshots'], '</th>
                        <th>', $txt['mohaa_accuracy'], '</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($weapons as $weapon) {
        $accuracy = $weapon['shots_fired'] > 0 
            ? round(($weapon['shots_hit'] / $weapon['shots_fired']) * 100, 1) 
            : 0;
        
        echo '
                    <tr class="windowbg">
                        <td><strong>', $weapon['name'], '</strong></td>
                        <td>', number_format($weapon['kills']), '</td>
                        <td>', number_format($weapon['headshots']), '</td>
                        <td>', $accuracy, '%</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            MohaaStats.initWeaponChart("weaponChart", ', json_encode(array_slice($weapons, 0, 5)), ');
        });
    </script>';
}

/**
 * Player achievements sub-template
 */
function template_player_achievements($achievements)
{
    global $txt;

    if (empty($achievements)) {
        echo '<p class="centertext">', $txt['mohaa_no_achievements'], '</p>';
        return;
    }

    echo '
    <div class="mohaa-achievements-grid">';

    foreach ($achievements as $achievement) {
        $class = $achievement['unlocked'] ? 'unlocked' : 'locked';
        
        echo '
        <div class="achievement-card ', $class, '">
            <div class="achievement-icon">', $achievement['icon'] ?? '🎖️', '</div>
            <div class="achievement-info">
                <h5>', $achievement['name'], '</h5>
                <p>', $achievement['description'], '</p>';
        
        if ($achievement['unlocked']) {
            echo '<span class="unlocked-date">Unlocked ', timeformat($achievement['unlocked_at']), '</span>';
        } else {
            $progress = $achievement['max_progress'] > 0 
                ? round(($achievement['progress'] / $achievement['max_progress']) * 100) 
                : 0;
            echo '
                <div class="progress-bar">
                    <div class="progress" style="width: ', $progress, '%;"></div>
                </div>
                <span class="progress-text">', $achievement['progress'], ' / ', $achievement['max_progress'], '</span>';
        }
        
        echo '
            </div>
        </div>';
    }

    echo '
    </div>';
}

/**
 * Player performance graphs sub-template
 */
function template_player_graphs($performance)
{
    global $txt;

    echo '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="mohaa-panel">
            <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_performance_chart'], '</h4></div>
            <div class="windowbg" style="height: 300px;">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
        <div class="mohaa-panel">
            <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_kd_trend'], '</h4></div>
            <div class="windowbg" style="height: 300px;">
                <canvas id="kdTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof MohaaStats !== "undefined") {
                MohaaStats.initPerformanceChart("performanceChart", ', json_encode([
                    'labels' => $performance['labels'] ?? [],
                    'kills' => $performance['kills'] ?? [],
                    'deaths' => $performance['deaths'] ?? []
                ]), ');
            }
        });
    </script>';
}

/**
 * Link identity template
 */
function template_mohaa_stats_link_identity()
{
    global $context, $scripturl, $txt;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_link_identity'], '</h3>
    </div>
    <div class="windowbg">';

    if (!empty($context['mohaa_identity_linked'])) {
        echo '
        <div class="successbox">', $txt['mohaa_identity_already_linked'], '</div>
        <p>', $txt['mohaa_linked_player'], ': <strong>', $context['mohaa_linked_name'], '</strong></p>
        <p>', $txt['mohaa_linked_since'], ': ', timeformat($context['mohaa_linked_at']), '</p>';
    } else {
        echo '
        <p>', $txt['mohaa_link_instructions'], '</p>
        <form action="', $scripturl, '?action=mohaastats;sa=generate_claim" method="post">
            <input type="submit" value="', $txt['mohaa_generate_claim'], '" class="button" />
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
        </form>';
    }

    if (!empty($context['mohaa_claim_code'])) {
        echo '
        <div class="mohaa-claim-box">
            <h4>', $txt['mohaa_your_claim_code'], '</h4>
            <code class="claim-code">', $context['mohaa_claim_code'], '</code>
            <p>', $txt['mohaa_claim_instructions'], '</p>
            <p class="expires">', $txt['mohaa_claim_expires'], ': ', timeformat($context['mohaa_claim_expires']), '</p>
        </div>';
    }

    echo '
    </div>';
}

/**
 * Generate game token template
 */
function template_mohaa_stats_token()
{
    global $context, $scripturl, $txt;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_game_token'], '</h3>
    </div>
    <div class="windowbg">';

    if (empty($context['mohaa_identity_linked'])) {
        echo '
        <div class="errorbox">', $txt['mohaa_must_link_first'], '</div>
        <p><a href="', $scripturl, '?action=mohaastats;sa=link">', $txt['mohaa_link_identity'], '</a></p>';
    } else {
        echo '
        <p>', $txt['mohaa_token_description'], '</p>
        <form action="', $scripturl, '?action=mohaastats;sa=generate_token" method="post">
            <input type="submit" value="', $txt['mohaa_generate_token'], '" class="button" />
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
        </form>';

        if (!empty($context['mohaa_token'])) {
            echo '
            <div class="mohaa-token-box">
                <h4>', $txt['mohaa_your_token'], '</h4>
                <code class="token">', $context['mohaa_token'], '</code>
                <p>', $txt['mohaa_token_instructions'], '</p>
                <p class="expires">', $txt['mohaa_token_expires'], ': ', timeformat($context['mohaa_token_expires']), '</p>
            </div>';
        }
    }

    echo '
    </div>';
}
