<?php
/**
 * MOHAA Server Stats Templates - Enhanced Dashboard
 * 
 * GameTracker-style features + advanced analytics with ApexCharts
 * 
 * @package MohaaServers
 * @version 2.1.0
 */

/**
 * Get country flag emoji from 2-letter country code
 */
function getCountryFlagEmoji(string $countryCode): string
{
    if (strlen($countryCode) !== 2) {
        return '🌐';
    }
    $countryCode = strtoupper($countryCode);
    $first = ord($countryCode[0]) - ord('A') + 0x1F1E6;
    $second = ord($countryCode[1]) - ord('A') + 0x1F1E6;
    return mb_chr($first) . mb_chr($second);
}

/**
 * Server list template - Main Dashboard
 */
function template_mohaa_servers_list()
{
    global $context, $txt, $scripturl;

    $stats = $context['mohaa_server_stats'] ?? [];
    $servers = $context['mohaa_servers'] ?? [];
    $online = $context['mohaa_online_servers'] ?? [];
    $rankings = $context['mohaa_rankings'] ?? [];
    $favorites = $context['mohaa_favorites'] ?? [];

    // ApexCharts CDN
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>';

    echo '
    <div class="cat_bar">
        <h3 class="catbg">🎮 ', ($txt['mohaa_servers'] ?? 'Game Servers'), '</h3>
    </div>';

    // Navigation tabs
    echo '
    <div class="mohaa-server-nav windowbg">
        <a href="', $scripturl, '?action=mohaaservers" class="active">', ($txt['mohaa_overview'] ?? 'Overview'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=live">', ($txt['mohaa_live'] ?? 'Live Servers'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=rankings">', ($txt['mohaa_rankings'] ?? 'Rankings'), '</a>';
    if (!empty($context['user']['id'])) {
        echo '
        <a href="', $scripturl, '?action=mohaaservers;sa=favorites">', ($txt['mohaa_favorites'] ?? 'Favorites'), '</a>';
    }
    echo '
        <a href="', $scripturl, '?action=mohaaservers;sa=history">', ($txt['mohaa_history'] ?? 'History'), '</a>
    </div>';

    // Show favorites section if user has any
    if (!empty($favorites)) {
        echo '
    <div class="mohaa-widget mohaa-widget-favorites">
        <div class="widget-header">
            <span class="widget-icon">⭐</span>
            <h4>Your Favorite Servers</h4>
        </div>
        <div class="widget-body">
            <div class="favorites-grid">';
        foreach ($favorites as $fav) {
            $isOnlineNow = !empty($fav['is_online']);
            echo '
                <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($fav['id'] ?? ''), '" class="favorite-card ', ($isOnlineNow ? 'online' : 'offline'), '">
                    <span class="fav-status">', ($isOnlineNow ? '🟢' : '⚫'), '</span>
                    <span class="fav-name">', htmlspecialchars($fav['name'] ?? 'Unknown'), '</span>
                    <span class="fav-players">', ($fav['current_players'] ?? 0), '/', ($fav['max_players'] ?? 0), '</span>
                </a>';
        }
        echo '
            </div>
        </div>
    </div>';
    }

    // Global stats widgets row
    echo '
    <div class="mohaa-dashboard-grid">
        <div class="mohaa-widget mohaa-widget-stats">
            <div class="widget-header">
                <span class="widget-icon">📊</span>
                <h4>Global Server Stats</h4>
            </div>
            <div class="widget-body stats-grid">
                <div class="stat-widget">
                    <div class="stat-icon">🖥️</div>
                    <div class="stat-content">
                        <span class="stat-value">', count($online), '/', count($servers), '</span>
                        <span class="stat-label">Servers Online</span>
                    </div>
                </div>
                <div class="stat-widget highlight">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <span class="stat-value">', number_format($stats['total_players_now'] ?? 0), '</span>
                        <span class="stat-label">Players Now</span>
                    </div>
                </div>
                <div class="stat-widget">
                    <div class="stat-icon">💀</div>
                    <div class="stat-content">
                        <span class="stat-value">', number_format($stats['total_kills_today'] ?? 0), '</span>
                        <span class="stat-label">Kills Today</span>
                    </div>
                </div>
                <div class="stat-widget">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <span class="stat-value">', number_format($stats['total_matches_today'] ?? 0), '</span>
                        <span class="stat-label">Matches Today</span>
                    </div>
                </div>
                <div class="stat-widget">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <span class="stat-value">', number_format($stats['peak_players_today'] ?? 0), '</span>
                        <span class="stat-label">Peak Today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    // Main content: Live servers + Server rankings
    echo '
    <div class="mohaa-dashboard-row">
        <div class="mohaa-widget mohaa-widget-large">
            <div class="widget-header">
                <span class="widget-icon pulse-dot"></span>
                <h4>Live Servers</h4>
                <a href="', $scripturl, '?action=mohaaservers;sa=live" class="widget-link">View All →</a>
            </div>
            <div class="widget-body">';

    if (empty($online)) {
        echo '<div class="empty-state">No servers currently online</div>';
    } else {
        echo '<div class="live-servers-grid">';
        foreach (array_slice($online, 0, 6) as $server) {
            $playersPercent = ($server['max_players'] > 0) ? round(($server['current_players'] ?? 0) / $server['max_players'] * 100) : 0;
            echo '
            <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id'] ?? ''), '" class="live-server-card">
                <div class="server-rank">#', $server['rank'] ?? '-', '</div>
                <div class="server-info">
                    <div class="server-name">', htmlspecialchars($server['name'] ?? 'Unknown'), '</div>
                    <div class="server-address">', htmlspecialchars($server['display_name'] ?? $server['address'] . ':' . $server['port']), '</div>
                </div>
                <div class="server-map">', htmlspecialchars($server['current_map'] ?? 'Unknown'), '</div>
                <div class="server-players">
                    <div class="player-bar">
                        <div class="player-fill" style="width: ', $playersPercent, '%"></div>
                    </div>
                    <span>', $server['current_players'] ?? 0, '/', $server['max_players'] ?? 0, '</span>
                </div>
            </a>';
        }
        echo '</div>';
    }

    echo '
            </div>
        </div>

        <div class="mohaa-widget mohaa-widget-rankings">
            <div class="widget-header">
                <span class="widget-icon">🏆</span>
                <h4>Server Rankings</h4>
                <a href="', $scripturl, '?action=mohaaservers;sa=rankings" class="widget-link">Full Rankings →</a>
            </div>
            <div class="widget-body">
                <div class="rankings-list">';

    foreach (array_slice($rankings, 0, 10) as $rank) {
        $trendIcon = ($rank['trend'] ?? 0) > 0 ? '↑' : (($rank['trend'] ?? 0) < 0 ? '↓' : '-');
        $trendClass = ($rank['trend'] ?? 0) > 0 ? 'trend-up' : (($rank['trend'] ?? 0) < 0 ? 'trend-down' : '');
        echo '
                    <div class="ranking-item">
                        <div class="rank-position">', $rank['rank'] ?? '-', '</div>
                        <div class="rank-info">
                            <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($rank['server_id'] ?? ''), '">', htmlspecialchars($rank['name'] ?? 'Unknown'), '</a>
                            <div class="rank-stats">', number_format($rank['kills_24h'] ?? 0), ' kills • ', number_format($rank['players_24h'] ?? 0), ' players</div>
                        </div>
                        <div class="rank-trend ', $trendClass, '">', $trendIcon, '</div>
                    </div>';
    }

    echo '
                </div>
            </div>
        </div>
    </div>';

    // Full server list table
    echo '
    <div class="mohaa-widget mohaa-widget-full">
        <div class="widget-header">
            <span class="widget-icon">🖥️</span>
            <h4>All Servers</h4>
        </div>
        <div class="widget-body">
            <table class="mohaa-server-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Rank</th>
                        <th>Server</th>
                        <th>Map</th>
                        <th>Players</th>
                        <th>Gametype</th>
                        <th>24h Stats</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($servers as $server) {
        $isOnline = !empty($server['is_online']);
        $statusClass = $isOnline ? 'status-online' : 'status-offline';
        $statusDot = $isOnline ? '<span class="status-dot online"></span>' : '<span class="status-dot offline"></span>';

        echo '
                    <tr class="', $statusClass, '">
                        <td class="col-status">', $statusDot, '</td>
                        <td class="col-rank">#', $server['rank'] ?? '-', '</td>
                        <td class="col-server">
                            <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id'] ?? ''), '">
                                <strong>', htmlspecialchars($server['name'] ?? 'Unknown'), '</strong>
                            </a>
                            <span class="server-addr">', $server['address'] ?? '', ':', $server['port'] ?? '', '</span>
                        </td>
                        <td class="col-map">', htmlspecialchars($server['current_map'] ?? '-'), '</td>
                        <td class="col-players">
                            <div class="player-mini-bar">
                                <div class="bar-fill" style="width: ', ($server['max_players'] > 0 ? round(($server['current_players'] ?? 0) / $server['max_players'] * 100) : 0), '%"></div>
                            </div>
                            <span>', $server['current_players'] ?? 0, '/', $server['max_players'] ?? 0, '</span>
                        </td>
                        <td class="col-gametype">', ucfirst($server['gametype'] ?? 'dm'), '</td>
                        <td class="col-stats">
                            <span title="Avg players">', number_format($server['avg_players_24h'] ?? 0, 1), ' avg</span>
                            <span title="Peak">/', number_format($server['peak_players_24h'] ?? 0), ' peak</span>
                        </td>
                        <td class="col-actions">
                            <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id'] ?? ''), '" class="btn-view">View</a>
                        </td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>';

    // CSS Styles
    template_mohaa_servers_styles();
}

/**
 * Live servers template - Real-time view
 */
function template_mohaa_servers_live()
{
    global $context, $txt, $scripturl;

    $servers = $context['mohaa_live'] ?? [];

    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>';

    echo '
    <div class="cat_bar">
        <h3 class="catbg"><span class="pulse-dot"></span> ', ($txt['mohaa_live_servers'] ?? 'Live Servers'), '</h3>
    </div>';

    // Navigation
    echo '
    <div class="mohaa-server-nav windowbg">
        <a href="', $scripturl, '?action=mohaaservers">', ($txt['mohaa_overview'] ?? 'Overview'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=live" class="active">', ($txt['mohaa_live'] ?? 'Live Servers'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=rankings">', ($txt['mohaa_rankings'] ?? 'Rankings'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=history">', ($txt['mohaa_history'] ?? 'History'), '</a>
    </div>';

    if (empty($servers)) {
        echo '
        <div class="mohaa-widget">
            <div class="widget-body">
                <div class="empty-state">
                    <div class="empty-icon">🌙</div>
                    <h4>No servers currently online</h4>
                    <p>Check back later or browse server history.</p>
                </div>
            </div>
        </div>';
        template_mohaa_servers_styles();
        return;
    }

    echo '
    <div class="live-grid">';

    foreach ($servers as $server) {
        $playersPercent = ($server['max_players'] > 0) ? round(($server['current_players'] ?? 0) / $server['max_players'] * 100) : 0;
        
        echo '
        <div class="live-server-widget">
            <div class="server-header">
                <div class="live-badge"><span class="pulse-dot small"></span> LIVE</div>
                <h4>', htmlspecialchars($server['name'] ?? 'Unknown'), '</h4>
            </div>
            
            <div class="server-main">
                <div class="current-match">
                    <div class="map-display">
                        <span class="map-label">Current Map</span>
                        <span class="map-name">', htmlspecialchars($server['current_map'] ?? 'Unknown'), '</span>
                    </div>
                    <div class="player-display">
                        <div class="circular-progress" data-value="', $playersPercent, '">
                            <span>', $server['current_players'] ?? 0, '</span>
                        </div>
                        <span class="player-max">/ ', $server['max_players'] ?? 0, '</span>
                    </div>
                </div>';

        // Team scores if applicable
        if (!empty($server['team_scores'])) {
            echo '
                <div class="team-scores">
                    <div class="team allies">
                        <span class="team-name">Allies</span>
                        <span class="team-score">', $server['team_scores']['allies'] ?? 0, '</span>
                    </div>
                    <div class="score-divider">vs</div>
                    <div class="team axis">
                        <span class="team-name">Axis</span>
                        <span class="team-score">', $server['team_scores']['axis'] ?? 0, '</span>
                    </div>
                </div>';
        }

        // Player list
        if (!empty($server['players'])) {
            echo '
                <div class="player-list">
                    <table>
                        <thead>
                            <tr><th>Player</th><th>K</th><th>D</th><th>Score</th></tr>
                        </thead>
                        <tbody>';
            foreach (array_slice($server['players'], 0, 8) as $player) {
                echo '
                            <tr>
                                <td><a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid'] ?? ''), '">', htmlspecialchars($player['name'] ?? 'Unknown'), '</a></td>
                                <td>', $player['kills'] ?? 0, '</td>
                                <td>', $player['deaths'] ?? 0, '</td>
                                <td>', $player['score'] ?? 0, '</td>
                            </tr>';
            }
            echo '
                        </tbody>
                    </table>';
            if (count($server['players']) > 8) {
                echo '<div class="more-players">+', (count($server['players']) - 8), ' more players</div>';
            }
            echo '
                </div>';
        }

        echo '
            </div>
            
            <div class="server-footer">
                <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id'] ?? ''), '" class="btn-details">View Details</a>
            </div>
        </div>';
    }

    echo '
    </div>';

    template_mohaa_servers_styles();
}

/**
 * Server detail template - Comprehensive Dashboard
 */
function template_mohaa_server_detail()
{
    global $context, $txt, $scripturl;

    $server = $context['mohaa_server']['info'] ?? [];
    $topPlayers = $context['mohaa_server']['top_players'] ?? [];
    $maps = $context['mohaa_server']['maps'] ?? [];
    $weapons = $context['mohaa_server']['weapons'] ?? [];
    $peakHours = $context['mohaa_server']['peak_hours'] ?? [];
    $playerHistory = $context['mohaa_server']['player_history'] ?? [];
    $recentMatches = $context['mohaa_server']['recent_matches'] ?? [];
    $live = $context['mohaa_server']['live'] ?? [];

    $isOnline = !empty($server['is_online']) || !empty($live['is_online']);

    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>';

    // Server header
    echo '
    <div class="mohaa-server-header-banner ', ($isOnline ? 'online' : 'offline'), '">
        <div class="server-status">';
    if ($isOnline) {
        echo '<span class="status-badge online"><span class="pulse-dot small"></span> ONLINE</span>';
    } else {
        echo '<span class="status-badge offline">OFFLINE</span>';
    }
    echo '
        </div>
        <div class="server-title">';
    
    // Country flag if available
    if (!empty($server['country'])) {
        echo '<span class="country-flag" title="', htmlspecialchars($server['country_name'] ?? $server['country']), '">', getCountryFlagEmoji($server['country']), '</span> ';
    }
    
    echo '
            <h2>', htmlspecialchars($server['name'] ?? 'Unknown Server'), '</h2>
            <span class="server-address">', htmlspecialchars($server['display_name'] ?? ($server['address'] ?? '') . ':' . ($server['port'] ?? '')), '</span>
        </div>
        <div class="server-quick-stats">
            <div class="quick-stat">
                <span class="value">#', $server['rank'] ?? '-', '</span>
                <span class="label">World Rank</span>
            </div>
            <div class="quick-stat">
                <span class="value">', number_format($server['stats']['total_kills'] ?? 0), '</span>
                <span class="label">Total Kills</span>
            </div>
            <div class="quick-stat">
                <span class="value">', number_format($server['stats']['unique_players'] ?? 0), '</span>
                <span class="label">Unique Players</span>
            </div>
            <div class="quick-stat">
                <span class="value">', number_format($server['stats']['total_playtime_hours'] ?? 0, 0), 'h</span>
                <span class="label">Time Played</span>
            </div>
        </div>
        <div class="server-actions">';
    
    // Favorite button
    $isFavorite = $context['mohaa_is_favorite'] ?? false;
    if (!empty($context['user']['id'])) {
        echo '
            <button class="btn-favorite ', ($isFavorite ? 'active' : ''), '" onclick="toggleServerFavorite(\'', htmlspecialchars($server['id'] ?? ''), '\', this)">
                ', ($isFavorite ? '⭐' : '☆'), ' <span>', ($isFavorite ? 'Favorited' : 'Add to Favorites'), '</span>
            </button>';
    }
    echo '
        </div>
    </div>';

    // Current match (if live)
    if ($isOnline && !empty($live)) {
        echo '
    <div class="mohaa-widget mohaa-widget-current-match">
        <div class="widget-header">
            <span class="pulse-dot"></span>
            <h4>Current Match</h4>
        </div>
        <div class="widget-body">
            <div class="current-match-grid">
                <div class="match-info">
                    <div class="info-item">
                        <span class="label">Map</span>
                        <span class="value">', htmlspecialchars($live['current_map'] ?? 'Unknown'), '</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Gametype</span>
                        <span class="value">', ucfirst($live['gametype'] ?? 'dm'), '</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Players</span>
                        <span class="value">', $live['player_count'] ?? 0, '/', $live['max_players'] ?? 0, '</span>
                    </div>
                </div>';

        if (!empty($live['players'])) {
            echo '
                <div class="current-players-table">
                    <table>
                        <thead><tr><th>Player</th><th>Team</th><th>K</th><th>D</th><th>Score</th></tr></thead>
                        <tbody>';
            foreach ($live['players'] as $player) {
                echo '
                            <tr>
                                <td><a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid'] ?? ''), '">', htmlspecialchars($player['name'] ?? 'Unknown'), '</a></td>
                                <td>', ucfirst($player['team'] ?? '-'), '</td>
                                <td>', $player['kills'] ?? 0, '</td>
                                <td>', $player['deaths'] ?? 0, '</td>
                                <td>', $player['score'] ?? 0, '</td>
                            </tr>';
            }
            echo '
                        </tbody>
                    </table>
                </div>';
        }

        echo '
            </div>
        </div>
    </div>';
    }

    // Stats overview row
    echo '
    <div class="mohaa-dashboard-row stats-row">
        <div class="stat-box">
            <div class="stat-icon">💀</div>
            <div class="stat-content">
                <span class="value">', number_format($server['stats_24h']['kills'] ?? 0), '</span>
                <span class="label">Kills (24h)</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <span class="value">', number_format($server['stats_24h']['matches'] ?? 0), '</span>
                <span class="label">Matches (24h)</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <span class="value">', number_format($server['stats_24h']['unique_players'] ?? 0), '</span>
                <span class="label">Players (24h)</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">⏱️</div>
            <div class="stat-content">
                <span class="value">', number_format($server['uptime']['uptime_7d'] ?? 0, 1), '%</span>
                <span class="label">Uptime (7d)</span>
            </div>
        </div>
    </div>';

    // Player history chart
    echo '
    <div class="mohaa-widget mohaa-widget-chart">
        <div class="widget-header">
            <span class="widget-icon">📈</span>
            <h4>Player History (7 Days)</h4>
        </div>
        <div class="widget-body">
            <div id="playerHistoryChart" style="height: 250px;"></div>
        </div>
    </div>';

    // Peak hours heatmap
    echo '
    <div class="mohaa-widget mohaa-widget-heatmap">
        <div class="widget-header">
            <span class="widget-icon">🔥</span>
            <h4>Peak Hours</h4>
            <span class="peak-info">Best time: ', $peakHours['peak']['day'] ?? 'N/A', ' at ', sprintf('%02d:00', $peakHours['peak']['hour'] ?? 0), '</span>
        </div>
        <div class="widget-body">
            <div id="peakHoursHeatmap" style="height: 200px;"></div>
        </div>
    </div>';

    // Two column: Top Players + Map Stats
    echo '
    <div class="mohaa-dashboard-row two-col">
        <div class="mohaa-widget">
            <div class="widget-header">
                <span class="widget-icon">🏆</span>
                <h4>Top Players</h4>
            </div>
            <div class="widget-body">
                <table class="mohaa-leaderboard-table">
                    <thead><tr><th>#</th><th></th><th>Player</th><th>Kills</th><th>K/D</th><th>Time</th></tr></thead>
                    <tbody>';
    foreach (array_slice($topPlayers, 0, 15) as $player) {
        $countryFlag = !empty($player['country']) ? getCountryFlagEmoji($player['country']) : '';
        echo '
                        <tr>
                            <td class="rank">', $player['rank'] ?? '-', '</td>
                            <td class="country" title="', htmlspecialchars($player['country'] ?? ''), '">', $countryFlag, '</td>
                            <td class="player"><a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid'] ?? ''), '">', htmlspecialchars($player['name'] ?? 'Unknown'), '</a></td>
                            <td>', number_format($player['kills'] ?? 0), '</td>
                            <td>', number_format($player['kd_ratio'] ?? 0, 2), '</td>
                            <td>', number_format($player['time_played_hours'] ?? 0, 1), 'h</td>
                        </tr>';
    }
    echo '
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mohaa-widget">
            <div class="widget-header">
                <span class="widget-icon">🗺️</span>
                <h4>Map Statistics</h4>
            </div>
            <div class="widget-body">
                <div id="mapPieChart" style="height: 200px;"></div>
                <table class="mohaa-map-table">
                    <thead><tr><th>Map</th><th>Matches</th><th>Popularity</th></tr></thead>
                    <tbody>';
    foreach (array_slice($maps, 0, 8) as $map) {
        echo '
                        <tr>
                            <td>', htmlspecialchars($map['map_name'] ?? 'Unknown'), '</td>
                            <td>', number_format($map['matches'] ?? 0), '</td>
                            <td>
                                <div class="bar-mini"><div class="fill" style="width: ', number_format($map['popularity'] ?? 0), '%"></div></div>
                                <span>', number_format($map['popularity'] ?? 0, 1), '%</span>
                            </td>
                        </tr>';
    }
    echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

    // Weapons + Recent Matches
    echo '
    <div class="mohaa-dashboard-row two-col">
        <div class="mohaa-widget">
            <div class="widget-header">
                <span class="widget-icon">🔫</span>
                <h4>Weapon Statistics</h4>
            </div>
            <div class="widget-body">
                <div id="weaponBarChart" style="height: 250px;"></div>
            </div>
        </div>

        <div class="mohaa-widget">
            <div class="widget-header">
                <span class="widget-icon">📋</span>
                <h4>Recent Matches</h4>
            </div>
            <div class="widget-body">
                <div class="recent-matches-list">';
    foreach (array_slice($recentMatches, 0, 10) as $match) {
        echo '
                    <a href="', $scripturl, '?action=mohaastats;sa=match;id=', urlencode($match['match_id'] ?? ''), '" class="match-item">
                        <span class="match-map">', htmlspecialchars($match['map_name'] ?? 'Unknown'), '</span>
                        <span class="match-info">', $match['player_count'] ?? 0, ' players • ', $match['duration_mins'] ?? 0, ' mins</span>
                        <span class="match-kills">', number_format($match['total_kills'] ?? 0), ' kills</span>
                    </a>';
    }
    echo '
                </div>
            </div>
        </div>
    </div>';

    // JavaScript for charts
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Player History Line Chart
        var playerHistoryData = ', json_encode(array_column($playerHistory, 'players')), ';
        var playerHistoryLabels = ', json_encode(array_column($playerHistory, 'timestamp')), ';
        
        if (playerHistoryData.length > 0) {
            new ApexCharts(document.querySelector("#playerHistoryChart"), {
                series: [{
                    name: "Players",
                    data: playerHistoryData
                }],
                chart: { type: "area", height: 250, toolbar: { show: false } },
                colors: ["#4a5d23"],
                fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2 } },
                stroke: { curve: "smooth", width: 2 },
                xaxis: { 
                    categories: playerHistoryLabels,
                    labels: { show: false }
                },
                tooltip: { x: { format: "HH:mm" } }
            }).render();
        }

        // Peak Hours Heatmap
        var heatmapData = ', json_encode($peakHours['data'] ?? []), ';
        var days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        
        if (heatmapData.length > 0) {
            var heatmapSeries = [];
            for (var d = 0; d < 7; d++) {
                var dayData = [];
                for (var h = 0; h < 24; h++) {
                    dayData.push({ x: h + ":00", y: heatmapData[d] ? heatmapData[d][h] || 0 : 0 });
                }
                heatmapSeries.push({ name: days[d], data: dayData });
            }
            
            new ApexCharts(document.querySelector("#peakHoursHeatmap"), {
                series: heatmapSeries,
                chart: { type: "heatmap", height: 200, toolbar: { show: false } },
                colors: ["#4a5d23"],
                plotOptions: {
                    heatmap: {
                        shadeIntensity: 0.5,
                        colorScale: {
                            ranges: [
                                { from: 0, to: 0, color: "#f0f0f0", name: "Empty" },
                                { from: 1, to: 5, color: "#c6e48b", name: "Low" },
                                { from: 6, to: 15, color: "#7bc96f", name: "Medium" },
                                { from: 16, to: 30, color: "#239a3b", name: "High" },
                                { from: 31, to: 100, color: "#196127", name: "Peak" }
                            ]
                        }
                    }
                },
                dataLabels: { enabled: false }
            }).render();
        }

        // Map Pie Chart
        var mapData = ', json_encode(array_column(array_slice($maps, 0, 6), 'matches')), ';
        var mapLabels = ', json_encode(array_column(array_slice($maps, 0, 6), 'map_name')), ';
        
        if (mapData.length > 0) {
            new ApexCharts(document.querySelector("#mapPieChart"), {
                series: mapData,
                chart: { type: "donut", height: 200 },
                labels: mapLabels,
                colors: ["#4a5d23", "#6b8e23", "#8fbc8f", "#9acd32", "#556b2f", "#3cb371"],
                legend: { position: "right" }
            }).render();
        }

        // Weapon Bar Chart
        var weaponKills = ', json_encode(array_column(array_slice($weapons, 0, 10), 'kills')), ';
        var weaponNames = ', json_encode(array_column(array_slice($weapons, 0, 10), 'weapon_name')), ';
        
        if (weaponKills.length > 0) {
            new ApexCharts(document.querySelector("#weaponBarChart"), {
                series: [{ name: "Kills", data: weaponKills }],
                chart: { type: "bar", height: 250, toolbar: { show: false } },
                colors: ["#4a5d23"],
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                xaxis: { categories: weaponNames }
            }).render();
        }
    });
    </script>';

    template_mohaa_servers_styles();
}

/**
 * Server history template
 */
function template_mohaa_server_history()
{
    global $context, $txt, $scripturl;

    $history = $context['mohaa_history'] ?? [];
    $server = $context['mohaa_server'] ?? null;

    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>';

    echo '
    <div class="cat_bar">
        <h3 class="catbg">📊 ', ($txt['mohaa_server_history'] ?? 'Server History'), '</h3>
    </div>';

    // Navigation
    echo '
    <div class="mohaa-server-nav windowbg">
        <a href="', $scripturl, '?action=mohaaservers">', ($txt['mohaa_overview'] ?? 'Overview'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=live">', ($txt['mohaa_live'] ?? 'Live Servers'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=rankings">', ($txt['mohaa_rankings'] ?? 'Rankings'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=history" class="active">', ($txt['mohaa_history'] ?? 'History'), '</a>
    </div>';

    // Period selector
    echo '
    <div class="mohaa-widget mohaa-widget-filters">
        <div class="widget-body">
            <form method="get" class="filter-form">
                <input type="hidden" name="action" value="mohaaservers" />
                <input type="hidden" name="sa" value="history" />';
    if ($server) {
        echo '<input type="hidden" name="id" value="', htmlspecialchars($server['id'] ?? ''), '" />';
    }
    echo '
                <label>Period:
                    <select name="period" onchange="this.form.submit()">
                        <option value="day"', ($history['period'] ?? '') === 'day' ? ' selected' : '', '>Last 24 Hours</option>
                        <option value="week"', ($history['period'] ?? 'week') === 'week' ? ' selected' : '', '>Last 7 Days</option>
                        <option value="month"', ($history['period'] ?? '') === 'month' ? ' selected' : '', '>Last 30 Days</option>
                    </select>
                </label>
            </form>
        </div>
    </div>';

    // Timeline chart
    $timeline = $history['timeline'] ?? $history['player_history'] ?? [];
    echo '
    <div class="mohaa-widget mohaa-widget-chart">
        <div class="widget-header">
            <span class="widget-icon">📈</span>
            <h4>Activity Timeline</h4>
        </div>
        <div class="widget-body">
            <div id="activityTimeline" style="height: 300px;"></div>
        </div>
    </div>';

    // Peak hours heatmap
    if (!empty($history['peak_hours'])) {
        echo '
    <div class="mohaa-widget mohaa-widget-heatmap">
        <div class="widget-header">
            <span class="widget-icon">🔥</span>
            <h4>Activity Heatmap</h4>
        </div>
        <div class="widget-body">
            <div id="activityHeatmap" style="height: 200px;"></div>
        </div>
    </div>';
    }

    // JavaScript
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var timelineData = ', json_encode($timeline), ';
        
        if (timelineData && timelineData.length > 0) {
            var killsData = timelineData.map(function(p) { return p.kills || 0; });
            var playersData = timelineData.map(function(p) { return p.players || p.avg || 0; });
            var labels = timelineData.map(function(p) { return p.timestamp || ""; });
            
            new ApexCharts(document.querySelector("#activityTimeline"), {
                series: [
                    { name: "Kills", type: "column", data: killsData },
                    { name: "Players", type: "line", data: playersData }
                ],
                chart: { height: 300, type: "line", toolbar: { show: false } },
                colors: ["#4a5d23", "#ff7f50"],
                stroke: { width: [0, 3] },
                plotOptions: { bar: { columnWidth: "50%" } },
                xaxis: { categories: labels, labels: { show: false } },
                yaxis: [
                    { title: { text: "Kills" } },
                    { opposite: true, title: { text: "Players" } }
                ]
            }).render();
        }';

    if (!empty($history['peak_hours'])) {
        echo '
        var heatmapData = ', json_encode($history['peak_hours']['data'] ?? []), ';
        var days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        
        if (heatmapData.length > 0) {
            var heatmapSeries = [];
            for (var d = 0; d < 7; d++) {
                var dayData = [];
                for (var h = 0; h < 24; h++) {
                    dayData.push({ x: h + ":00", y: heatmapData[d] ? heatmapData[d][h] || 0 : 0 });
                }
                heatmapSeries.push({ name: days[d], data: dayData });
            }
            
            new ApexCharts(document.querySelector("#activityHeatmap"), {
                series: heatmapSeries,
                chart: { type: "heatmap", height: 200, toolbar: { show: false } },
                colors: ["#4a5d23"],
                dataLabels: { enabled: false }
            }).render();
        }';
    }

    echo '
    });
    </script>';

    template_mohaa_servers_styles();
}

/**
 * Server rankings template
 */
function template_mohaa_server_rankings()
{
    global $context, $txt, $scripturl;

    $rankings = $context['mohaa_rankings'] ?? [];
    $stats = $context['mohaa_server_stats'] ?? [];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">🏆 ', ($txt['mohaa_server_rankings'] ?? 'Server Rankings'), '</h3>
    </div>';

    // Navigation
    echo '
    <div class="mohaa-server-nav windowbg">
        <a href="', $scripturl, '?action=mohaaservers">', ($txt['mohaa_overview'] ?? 'Overview'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=live">', ($txt['mohaa_live'] ?? 'Live Servers'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=rankings" class="active">', ($txt['mohaa_rankings'] ?? 'Rankings'), '</a>
        <a href="', $scripturl, '?action=mohaaservers;sa=history">', ($txt['mohaa_history'] ?? 'History'), '</a>
    </div>';

    // Rankings table
    echo '
    <div class="mohaa-widget">
        <div class="widget-header">
            <span class="widget-icon">🏅</span>
            <h4>Top Servers by Activity (24h)</h4>
        </div>
        <div class="widget-body">
            <table class="mohaa-rankings-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Server</th>
                        <th>Kills (24h)</th>
                        <th>Players (24h)</th>
                        <th>Matches (24h)</th>
                        <th>Score</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($rankings as $server) {
        $trendIcon = ($server['trend'] ?? 0) > 0 ? '↑' : (($server['trend'] ?? 0) < 0 ? '↓' : '-');
        $trendClass = ($server['trend'] ?? 0) > 0 ? 'trend-up' : (($server['trend'] ?? 0) < 0 ? 'trend-down' : '');
        $rankClass = ($server['rank'] ?? 0) <= 3 ? 'top-' . ($server['rank'] ?? 0) : '';

        echo '
                    <tr class="', $rankClass, '">
                        <td class="rank-cell">', $server['rank'] ?? '-', '</td>
                        <td class="server-cell">
                            <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['server_id'] ?? ''), '">
                                ', htmlspecialchars($server['name'] ?? 'Unknown'), '
                            </a>
                        </td>
                        <td>', number_format($server['kills_24h'] ?? 0), '</td>
                        <td>', number_format($server['players_24h'] ?? 0), '</td>
                        <td>', number_format($server['matches_24h'] ?? 0), '</td>
                        <td class="score-cell">', number_format($server['score'] ?? 0), '</td>
                        <td class="trend-cell ', $trendClass, '">', $trendIcon, '</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>';

    template_mohaa_servers_styles();
}

/**
 * Shared CSS styles
 */
function template_mohaa_servers_styles()
{
    static $stylesLoaded = false;
    if ($stylesLoaded) return;
    $stylesLoaded = true;

    echo '
<style>
/* MOHAA Server Dashboard Styles */
.mohaa-server-nav { display: flex; gap: 5px; padding: 10px 15px; margin-bottom: 20px; }
.mohaa-server-nav a { padding: 8px 16px; border-radius: 4px; text-decoration: none; color: inherit; transition: all 0.2s; }
.mohaa-server-nav a:hover, .mohaa-server-nav a.active { background: #4a5d23; color: #fff; }

.mohaa-dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }
.mohaa-dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
.mohaa-dashboard-row.two-col { grid-template-columns: 1fr 1fr; }
.mohaa-dashboard-row.stats-row { display: flex; gap: 15px; flex-wrap: wrap; }

.mohaa-widget { background: var(--windowbg, #fff); border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
.mohaa-widget.mohaa-widget-full { grid-column: 1 / -1; }
.widget-header { display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-bottom: 1px solid rgba(0,0,0,0.1); background: rgba(0,0,0,0.02); }
.widget-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
.widget-header .widget-link { margin-left: auto; font-size: 12px; color: #4a5d23; }
.widget-body { padding: 15px; }
.widget-icon { font-size: 16px; }

.pulse-dot { display: inline-block; width: 10px; height: 10px; background: #e53935; border-radius: 50%; animation: pulse 1.5s infinite; }
.pulse-dot.small { width: 8px; height: 8px; }
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.1); } }

.stats-grid { display: flex; flex-wrap: wrap; gap: 15px; }
.stat-widget { display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: rgba(0,0,0,0.02); border-radius: 8px; flex: 1; min-width: 150px; }
.stat-widget.highlight { background: linear-gradient(135deg, #4a5d23, #6b8e23); color: #fff; }
.stat-widget .stat-icon { font-size: 24px; }
.stat-widget .stat-value { font-size: 24px; font-weight: 700; display: block; }
.stat-widget .stat-label { font-size: 12px; opacity: 0.8; }

.stat-box { display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: var(--windowbg, #fff); border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; }
.stat-box .stat-icon { font-size: 28px; }
.stat-box .value { font-size: 22px; font-weight: 700; display: block; }
.stat-box .label { font-size: 12px; opacity: 0.7; }

.live-servers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
.live-server-card { display: flex; align-items: center; gap: 12px; padding: 12px 15px; background: rgba(0,0,0,0.02); border-radius: 6px; text-decoration: none; color: inherit; transition: all 0.2s; border-left: 3px solid #4a5d23; }
.live-server-card:hover { background: rgba(74, 93, 35, 0.1); transform: translateX(3px); }
.live-server-card .server-rank { font-weight: 700; color: #4a5d23; min-width: 30px; }
.live-server-card .server-info { flex: 1; }
.live-server-card .server-name { font-weight: 600; display: block; }
.live-server-card .server-address { font-size: 11px; opacity: 0.6; }
.live-server-card .server-map { font-size: 13px; padding: 3px 8px; background: rgba(0,0,0,0.05); border-radius: 3px; }
.live-server-card .server-players { text-align: right; min-width: 60px; }
.player-bar { width: 50px; height: 4px; background: rgba(0,0,0,0.1); border-radius: 2px; overflow: hidden; margin-bottom: 3px; }
.player-bar .player-fill { height: 100%; background: #4a5d23; }

.rankings-list { display: flex; flex-direction: column; gap: 8px; }
.ranking-item { display: flex; align-items: center; gap: 12px; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 4px; }
.ranking-item .rank-position { font-weight: 700; font-size: 16px; color: #4a5d23; min-width: 24px; }
.ranking-item .rank-info { flex: 1; }
.ranking-item .rank-info a { font-weight: 600; }
.ranking-item .rank-stats { font-size: 11px; opacity: 0.6; }
.ranking-item .rank-trend { font-size: 14px; }
.trend-up { color: #4caf50; }
.trend-down { color: #f44336; }

.mohaa-server-table { width: 100%; border-collapse: collapse; }
.mohaa-server-table th, .mohaa-server-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
.mohaa-server-table th { font-weight: 600; font-size: 12px; text-transform: uppercase; opacity: 0.7; }
.mohaa-server-table .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.mohaa-server-table .status-dot.online { background: #4caf50; }
.mohaa-server-table .status-dot.offline { background: #9e9e9e; }
.mohaa-server-table .col-server a { font-weight: 600; }
.mohaa-server-table .server-addr { display: block; font-size: 11px; opacity: 0.6; }
.player-mini-bar { width: 40px; height: 3px; background: rgba(0,0,0,0.1); border-radius: 2px; display: inline-block; margin-right: 6px; vertical-align: middle; }
.player-mini-bar .bar-fill { height: 100%; background: #4a5d23; border-radius: 2px; }
.btn-view { padding: 4px 10px; background: #4a5d23; color: #fff; border-radius: 3px; font-size: 12px; text-decoration: none; }

.mohaa-server-header-banner { display: flex; align-items: center; gap: 20px; padding: 20px; background: linear-gradient(135deg, #2d3a14, #4a5d23); color: #fff; border-radius: 8px; margin-bottom: 20px; }
.mohaa-server-header-banner.offline { background: linear-gradient(135deg, #424242, #616161); }
.mohaa-server-header-banner .server-title h2 { margin: 0; font-size: 24px; }
.mohaa-server-header-banner .server-address { opacity: 0.8; font-size: 13px; }
.mohaa-server-header-banner .server-quick-stats { display: flex; gap: 20px; margin-left: auto; }
.mohaa-server-header-banner .quick-stat { text-align: center; }
.mohaa-server-header-banner .quick-stat .value { font-size: 24px; font-weight: 700; display: block; }
.mohaa-server-header-banner .quick-stat .label { font-size: 11px; opacity: 0.8; }
.status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.status-badge.online { background: rgba(76, 175, 80, 0.2); }
.status-badge.offline { background: rgba(158, 158, 158, 0.2); }

.mohaa-leaderboard-table { width: 100%; border-collapse: collapse; }
.mohaa-leaderboard-table th, .mohaa-leaderboard-table td { padding: 8px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
.mohaa-leaderboard-table .rank { font-weight: 700; color: #4a5d23; width: 30px; }
.mohaa-leaderboard-table .player a { font-weight: 500; }

.mohaa-map-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.mohaa-map-table td { padding: 6px 8px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.bar-mini { display: inline-block; width: 50px; height: 4px; background: rgba(0,0,0,0.1); border-radius: 2px; margin-right: 8px; vertical-align: middle; }
.bar-mini .fill { height: 100%; background: #4a5d23; border-radius: 2px; }

.recent-matches-list { display: flex; flex-direction: column; }
.match-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.05); text-decoration: none; color: inherit; transition: background 0.2s; }
.match-item:hover { background: rgba(0,0,0,0.02); }
.match-item .match-map { font-weight: 600; min-width: 100px; }
.match-item .match-info { flex: 1; font-size: 12px; opacity: 0.7; }
.match-item .match-kills { font-size: 12px; color: #4a5d23; }

.live-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
.live-server-widget { background: var(--windowbg, #fff); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
.live-server-widget .server-header { display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: linear-gradient(135deg, #2d3a14, #4a5d23); color: #fff; }
.live-server-widget .server-header h4 { margin: 0; font-size: 16px; }
.live-badge { display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; }
.live-server-widget .server-main { padding: 15px; }
.live-server-widget .current-match { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.live-server-widget .map-label { display: block; font-size: 11px; opacity: 0.6; }
.live-server-widget .map-name { font-size: 18px; font-weight: 600; }
.live-server-widget .player-display { display: flex; align-items: center; gap: 5px; }
.live-server-widget .circular-progress { width: 50px; height: 50px; background: conic-gradient(#4a5d23 calc(var(--value, 0) * 1%), #e0e0e0 0); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.live-server-widget .circular-progress span { background: var(--windowbg, #fff); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
.live-server-widget .team-scores { display: flex; justify-content: center; gap: 20px; padding: 10px; background: rgba(0,0,0,0.03); border-radius: 6px; margin-bottom: 15px; }
.live-server-widget .team { text-align: center; }
.live-server-widget .team.allies { color: #1976d2; }
.live-server-widget .team.axis { color: #d32f2f; }
.live-server-widget .team-name { font-size: 11px; text-transform: uppercase; }
.live-server-widget .team-score { font-size: 24px; font-weight: 700; }
.live-server-widget .player-list table { width: 100%; font-size: 13px; border-collapse: collapse; }
.live-server-widget .player-list th, .live-server-widget .player-list td { padding: 5px 8px; text-align: left; }
.live-server-widget .player-list th { border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 11px; opacity: 0.7; }
.live-server-widget .more-players { text-align: center; font-size: 12px; opacity: 0.6; padding: 5px; }
.live-server-widget .server-footer { padding: 10px 15px; border-top: 1px solid rgba(0,0,0,0.05); }
.btn-details { display: block; text-align: center; padding: 8px; background: #4a5d23; color: #fff; border-radius: 4px; text-decoration: none; }

.mohaa-rankings-table { width: 100%; border-collapse: collapse; }
.mohaa-rankings-table th, .mohaa-rankings-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
.mohaa-rankings-table .rank-cell { font-weight: 700; font-size: 18px; color: #4a5d23; width: 50px; }
.mohaa-rankings-table .server-cell a { font-weight: 600; }
.mohaa-rankings-table .score-cell { font-weight: 700; color: #4a5d23; }
.mohaa-rankings-table tr.top-1 .rank-cell { color: #ffd700; }
.mohaa-rankings-table tr.top-2 .rank-cell { color: #c0c0c0; }
.mohaa-rankings-table tr.top-3 .rank-cell { color: #cd7f32; }

.current-match-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
.current-match-grid .match-info { display: flex; flex-direction: column; gap: 10px; }
.current-match-grid .info-item .label { display: block; font-size: 11px; opacity: 0.6; }
.current-match-grid .info-item .value { font-size: 16px; font-weight: 600; }
.current-players-table table { width: 100%; border-collapse: collapse; }
.current-players-table th, .current-players-table td { padding: 8px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }

.empty-state { text-align: center; padding: 40px 20px; opacity: 0.7; }
.empty-state .empty-icon { font-size: 48px; margin-bottom: 15px; }
.empty-state h4 { margin: 0 0 10px; }

.filter-form { display: flex; gap: 15px; align-items: center; }
.filter-form select { padding: 6px 10px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.2); }

.peak-info { margin-left: auto; font-size: 12px; background: rgba(74, 93, 35, 0.1); padding: 4px 10px; border-radius: 4px; color: #4a5d23; }

/* Favorites */
.favorites-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.favorite-card { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: rgba(0,0,0,0.02); border-radius: 6px; text-decoration: none; color: inherit; border: 1px solid rgba(0,0,0,0.1); transition: all 0.2s; }
.favorite-card:hover { background: rgba(74,93,35,0.1); border-color: #4a5d23; }
.favorite-card.online { border-left: 3px solid #4caf50; }
.favorite-card.offline { border-left: 3px solid #9e9e9e; opacity: 0.7; }
.favorite-card .fav-name { font-weight: 600; }
.favorite-card .fav-players { font-size: 12px; opacity: 0.7; }

/* Favorite button */
.btn-favorite { display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; color: #fff; cursor: pointer; font-size: 14px; transition: all 0.2s; }
.btn-favorite:hover { background: rgba(255,255,255,0.3); }
.btn-favorite.active { background: #ffd700; color: #333; border-color: #ffd700; }

/* Country flags */
.country-flag { font-size: 20px; vertical-align: middle; }
.mohaa-leaderboard-table .country { width: 30px; font-size: 16px; }

/* Server actions */
.server-actions { margin-left: auto; }

@media (max-width: 900px) {
    .mohaa-dashboard-row { grid-template-columns: 1fr; }
    .mohaa-dashboard-row.two-col { grid-template-columns: 1fr; }
    .mohaa-server-header-banner { flex-direction: column; text-align: center; }
    .mohaa-server-header-banner .server-quick-stats { margin-left: 0; margin-top: 15px; }
    .server-actions { margin: 15px 0 0 0; }
}
</style>

<script>
// Toggle server favorite
function toggleServerFavorite(serverId, btn) {
    var isFavorite = btn.classList.contains("active");
    var method = isFavorite ? "DELETE" : "POST";
    
    fetch(smf_scripturl + "?action=mohaastats;sa=api;endpoint=servers/" + encodeURIComponent(serverId) + "/favorite", {
        method: method,
        credentials: "same-origin"
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success || data.is_favorite !== undefined) {
            btn.classList.toggle("active");
            var span = btn.querySelector("span");
            if (btn.classList.contains("active")) {
                btn.innerHTML = "⭐ <span>Favorited</span>";
            } else {
                btn.innerHTML = "☆ <span>Add to Favorites</span>";
            }
        }
    })
    .catch(function(err) { console.error("Favorite toggle failed:", err); });
}
</script>';
}
