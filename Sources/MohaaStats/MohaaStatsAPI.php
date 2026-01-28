<?php
/**
 * MOHAA Stats API Client - OPTIMIZED with parallel requests
 */

if (!defined('SMF'))
    die('No direct access...');

class MohaaStatsAPIClient
{
    private string $baseUrl;
    private string $serverToken;
    private int $cacheDuration;
    private int $timeout;
    
    public function __construct()
    {
        global $modSettings;
        // Priority: environment > modSettings > default
        // This allows Docker deployments to override database settings
        $envApiUrl = getenv('MOHAA_API_URL') ?: null;
        $this->baseUrl = $envApiUrl ?? $modSettings['mohaa_stats_api_url'] ?? 'http://localhost:8080';
        $this->serverToken = $modSettings['mohaa_stats_server_token'] ?? '';
        $this->cacheDuration = (int)($modSettings['mohaa_stats_cache_duration'] ?? 60);
        $this->timeout = (int)($modSettings['mohaa_stats_api_timeout'] ?? 5);
    }
    
    public function getMultiple(array $requests): array
    {
        $results = [];
        $handles = [];
        $mh = curl_multi_init();
        
        foreach ($requests as $key => $request) {
            $endpoint = $request['endpoint'];
            $params = $request['params'] ?? [];
            $url = $this->baseUrl . '/api/v1' . $endpoint;
            if (!empty($params)) $url .= '?' . http_build_query($params);
            
            $cacheKey = 'mohaa_api_' . md5($url);
            $cached = cache_get_data($cacheKey, $this->cacheDuration);
            if ($cached !== null) { $results[$key] = $cached; continue; }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Server-Token: ' . $this->serverToken],
            ]);
            $handles[$key] = ['handle' => $ch, 'url' => $url, 'cacheKey' => $cacheKey];
            curl_multi_add_handle($mh, $ch);
        }
        
        if (!empty($handles)) {
            $running = null;
            do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
            
            foreach ($handles as $key => $info) {
                $response = curl_multi_getcontent($info['handle']);
                $httpCode = curl_getinfo($info['handle'], CURLINFO_HTTP_CODE);
                if ($httpCode === 200 && $response !== false) {
                    $data = json_decode($response, true);
                    $results[$key] = $data;
                    cache_put_data($info['cacheKey'], $data, $this->cacheDuration);
                } else { $results[$key] = null; }
                curl_multi_remove_handle($mh, $info['handle']);
                curl_close($info['handle']);
            }
            curl_multi_close($mh);
        }
        return $results;
    }
    
    public function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . '/api/v1' . $endpoint;
        if (!empty($params)) $url .= '?' . http_build_query($params);
        $cacheKey = 'mohaa_api_' . md5($url);
        $cached = cache_get_data($cacheKey, $this->cacheDuration);
        if ($cached !== null) return $cached;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Server-Token: ' . $this->serverToken],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200 || $response === false) return null;
        $data = json_decode($response, true);
        cache_put_data($cacheKey, $data, $this->cacheDuration);
        return $data;
    }
    
    public function post(string $endpoint, array $data = []): ?array
    {
        $url = $this->baseUrl . '/api/v1' . $endpoint;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'X-Server-Token: ' . $this->serverToken],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode < 200 || $httpCode >= 300 || $response === false) return null;
        return json_decode($response, true);
    }
    
    public function clearCache(): void { clean_cache('mohaa_api_'); }
    public function getGlobalStats(): ?array { return $this->get('/stats/global'); }
    public function getLeaderboard(string $stat = 'kills', int $limit = 25, int $offset = 0, string $period = 'all'): ?array { return $this->get('/stats/leaderboard/global', ['stat'=>$stat,'limit'=>$limit,'offset'=>$offset,'period'=>$period]); }
    public function getLeaderboardCount(string $stat = 'kills', string $period = 'all'): int { $data = $this->get('/stats/leaderboard/global', ['stat'=>$stat,'period'=>$period,'count_only'=>true]); return $data['total'] ?? 0; }
    public function getPlayerStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid)); }
    public function resolvePlayerByName(string $name): ?array { return $this->get('/stats/player/name/' . urlencode($name)); }
    public function getPlayerDeepStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/deep'); }
    public function getPlayerWeapons(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/weapons'); }
    public function getPlayerMatches(string $guid, int $limit = 10, int $offset = 0): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/matches', ['limit'=>$limit,'offset'=>$offset]); }
    public function getPlayerAchievements(string $guid): ?array { return $this->get('/achievements/player/' . urlencode($guid)); }
    public function getRecentMatches(int $limit = 20, int $offset = 0): ?array { return $this->get('/stats/matches', ['limit'=>$limit,'offset'=>$offset]); }
    public function getMatchCount(): int { $data = $this->get('/stats/matches', ['count_only'=>true]); return $data['total'] ?? 0; }
    public function getMatchDetails(string $matchId): ?array { return $this->get('/stats/match/' . urlencode($matchId)); }
    public function getLiveMatches(): ?array { $orig = $this->cacheDuration; $this->cacheDuration = 10; $data = $this->get('/stats/live/matches'); $this->cacheDuration = $orig; return $data; }
    public function getMapStats(): ?array { return $this->get('/stats/maps'); }
    public function getMapDetails(string $mapId): ?array { return $this->get('/stats/map/' . urlencode($mapId)); }
    public function getMapHeatmap(string $mapId, string $type = 'kills'): ?array { return $this->get('/stats/map/' . urlencode($mapId) . '/heatmap', ['type'=>$type]); }
    public function getMapsList(): ?array { return $this->get('/stats/maps/list'); }
    public function getMapLeaderboard(string $mapId, int $limit = 25): ?array { return $this->get('/stats/leaderboard/map/' . urlencode($mapId), ['limit' => $limit]); }
    
    // Game Type Stats (derived from map prefixes dynamically)
    public function getGameTypeStats(): ?array { return $this->get('/stats/gametypes'); }
    public function getGameTypesList(): ?array { return $this->get('/stats/gametypes/list'); }
    public function getGameTypeDetails(string $gameType): ?array { return $this->get('/stats/gametype/' . urlencode($gameType)); }
    public function getGameTypeLeaderboard(string $gameType, int $limit = 25): ?array { return $this->get('/stats/leaderboard/gametype/' . urlencode($gameType), ['limit' => $limit]); }
    
    // Weapon Stats
    public function getGlobalWeaponStats(): ?array { return $this->get('/stats/weapons'); }
    public function getWeaponsList(): ?array { return $this->get('/stats/weapons/list'); }
    public function getWeaponDetails(string $weaponId): ?array { return $this->get('/stats/weapon/' . urlencode($weaponId)); }
    public function getWeaponStats(string $weaponId): ?array { return $this->getWeaponDetails($weaponId); } // Alias
    public function getWeaponLeaderboard(string $weaponId, int $limit = 25): ?array { return $this->get('/stats/leaderboard/weapon/' . urlencode($weaponId), ['limit' => $limit]); }
    
    public function initClaim(int $forumUserId): ?array { return $this->post('/auth/claim/init', ['forum_user_id'=>$forumUserId]); }
    public function initDeviceAuth(int $forumUserId, bool $regenerate = false): ?array { return $this->post('/auth/device', ['forum_user_id'=>$forumUserId, 'regenerate'=>$regenerate]); }

    // Server Stats
    public function getGlobalActivity(): ?array { return $this->get('/stats/global/activity'); }
    public function getMapPopularity(): ?array { return $this->get('/stats/maps/popularity'); }
    
    // Stubs
    public function getAchievements(): ?array { return $this->get('/achievements/'); }
    public function getAchievement(int $id): ?array { return $this->get('/achievements/' . $id); }
    public function getRecentAchievements(): ?array { return $this->get('/achievements/recent'); }
    
    // =========================================================================
    // SERVER TRACKING ENDPOINTS - Full API Implementation
    // =========================================================================
    
    /**
     * Get list of all servers with live status and rankings
     */
    public function getServerList(): ?array 
    { 
        return $this->get('/servers'); 
    }
    
    /**
     * Get aggregate stats across all servers
     */
    public function getServerGlobalStats(): ?array 
    { 
        $data = $this->get('/servers/stats');
        return $data ?? [
            'total_servers' => 0, 
            'online_servers' => 0, 
            'total_players_now' => 0,
            'total_kills_today' => 0,
            'total_matches_today' => 0,
            'peak_players_today' => 0
        ]; 
    }
    
    /**
     * Get server rankings list
     */
    public function getServerRankings(int $limit = 50): ?array 
    { 
        return $this->get('/servers/rankings', ['limit' => $limit]); 
    }
    
    /**
     * Get live servers (actually online right now)
     */
    public function getLiveServers(): ?array 
    { 
        $servers = $this->get('/servers');
        if ($servers === null) return [];
        return array_filter($servers, fn($s) => $s['is_online'] ?? false);
    }
    
    /**
     * Get comprehensive details for a specific server
     */
    public function getServerDetails(string $id): ?array 
    { 
        return $this->get('/servers/' . urlencode($id)); 
    }
    
    /**
     * Get real-time live status (players, map, match)
     */
    public function getServerLiveStatus(string $id): ?array 
    { 
        $orig = $this->cacheDuration;
        $this->cacheDuration = 10; // Short cache for live data
        $data = $this->get('/servers/' . urlencode($id) . '/live');
        $this->cacheDuration = $orig;
        return $data;
    }
    
    /**
     * Get current match info for a server (alias for live status)
     */
    public function getServerCurrentMatch(string $id): ?array 
    { 
        $live = $this->getServerLiveStatus($id);
        return $live['current_match'] ?? null;
    }
    
    /**
     * Get recent matches played on a server
     */
    public function getServerMatches(string $id, int $limit = 20): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/matches', ['limit' => $limit]); 
    }
    
    /**
     * Get top players for a specific server
     */
    public function getServerTopPlayers(string $id, int $limit = 25): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/top-players', ['limit' => $limit]); 
    }
    
    /**
     * Get map statistics for a server
     */
    public function getServerMapStats(string $id): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/maps'); 
    }
    
    /**
     * Get map rotation (alias for map stats)
     */
    public function getServerMapRotation(string $id): ?array 
    { 
        return $this->getServerMapStats($id);
    }
    
    /**
     * Get weapon statistics for a server
     */
    public function getServerWeaponStats(string $id): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/weapons'); 
    }
    
    /**
     * Get peak hours heatmap data (by day/hour)
     */
    public function getServerPeakHours(string $id, int $days = 30): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/peak-hours', ['days' => $days]); 
    }
    
    /**
     * Get player count history for charts
     */
    public function getServerPlayerHistory(string $id, int $hours = 24): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/player-history', ['hours' => $hours]); 
    }
    
    /**
     * Get activity timeline (kills, deaths, players by hour)
     */
    public function getServerActivityTimeline(string $id, int $days = 7): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/activity-timeline', ['days' => $days]); 
    }
    
    /**
     * Get historical player counts (for charts)
     */
    public function getHistoricalPlayerCounts(string $id, int $days = 7): ?array 
    { 
        return $this->getServerPlayerHistory($id, $days * 24);
    }
    
    /**
     * Get historical match counts
     */
    public function getHistoricalMatchCounts(string $id, int $days = 7): ?array 
    { 
        return $this->getServerActivityTimeline($id, $days);
    }
    
    /**
     * Get all servers history summary
     */
    public function getAllServersHistory(int $days = 7): ?array 
    { 
        // Get rankings which include 24h stats
        return $this->getServerRankings(100);
    }
    
    /**
     * Get server history (detail + timeline)
     */
    public function getServerHistory(string $id, int $days = 7): ?array 
    { 
        return $this->getServerActivityTimeline($id, $days);
    }
    
    /**
     * Get uptime history for a server
     */
    public function getServerUptimeHistory(string $id, int $days = 7): ?array 
    { 
        // Uptime is part of server details
        $details = $this->getServerDetails($id);
        return $details['uptime'] ?? null;
    }
    
    /**
     * Batch fetch multiple server endpoints in parallel
     */
    public function getServerDashboardData(string $id): array
    {
        return $this->getMultiple([
            'detail' => ['endpoint' => '/servers/' . urlencode($id)],
            'top_players' => ['endpoint' => '/servers/' . urlencode($id) . '/top-players', 'params' => ['limit' => 10]],
            'maps' => ['endpoint' => '/servers/' . urlencode($id) . '/maps'],
            'weapons' => ['endpoint' => '/servers/' . urlencode($id) . '/weapons'],
            'peak_hours' => ['endpoint' => '/servers/' . urlencode($id) . '/peak-hours'],
            'player_history' => ['endpoint' => '/servers/' . urlencode($id) . '/player-history', 'params' => ['hours' => 168]],
            'matches' => ['endpoint' => '/servers/' . urlencode($id) . '/matches', 'params' => ['limit' => 10]],
        ]);
    }
    
    // =========================================================================
    // SERVER FAVORITES
    // =========================================================================
    
    /**
     * Get user's favorite servers
     */
    public function getUserFavoriteServers(): ?array 
    { 
        return $this->get('/servers/favorites'); 
    }
    
    /**
     * Add server to favorites
     */
    public function addServerFavorite(string $id, string $nickname = ''): ?array 
    { 
        return $this->post('/servers/' . urlencode($id) . '/favorite', ['nickname' => $nickname]); 
    }
    
    /**
     * Remove server from favorites
     */
    public function removeServerFavorite(string $id): ?array 
    { 
        // Use DELETE method via POST with _method override
        return $this->post('/servers/' . urlencode($id) . '/favorite?_method=DELETE', []); 
    }
    
    /**
     * Check if server is favorited
     */
    public function isServerFavorite(string $id): bool 
    { 
        $result = $this->get('/servers/' . urlencode($id) . '/favorite');
        return $result['is_favorite'] ?? false;
    }
    
    // =========================================================================
    // HISTORICAL PLAYER DATA
    // =========================================================================
    
    /**
     * Get all players with historical data for a server
     */
    public function getServerAllPlayers(string $id, int $limit = 50, int $offset = 0): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/players', ['limit' => $limit, 'offset' => $offset]); 
    }
    
    // =========================================================================
    // MAP ROTATION ANALYSIS  
    // =========================================================================
    
    /**
     * Get detailed map rotation analysis
     */
    public function getServerMapRotationAnalysis(string $id, int $days = 30): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/map-rotation', ['days' => $days]); 
    }
    
    // =========================================================================
    // COUNTRY STATS
    // =========================================================================
    
    /**
     * Get player country distribution for a server
     */
    public function getServerCountryStats(string $id): ?array 
    { 
        return $this->get('/servers/' . urlencode($id) . '/countries'); 
    }
    
    /**
     * Get country flag emoji from country code
     */
    public static function getCountryFlag(string $countryCode): string
    {
        if (strlen($countryCode) !== 2) {
            return '🌐';
        }
        $countryCode = strtoupper($countryCode);
        $first = ord($countryCode[0]) - ord('A') + 0x1F1E6;
        $second = ord($countryCode[1]) - ord('A') + 0x1F1E6;
        return mb_chr($first) . mb_chr($second);
    }

    public function getActivePlayers(int $hours): ?array { return []; }
    public function getPlayerRank(string $guid): ?int { return null; }
    public function getPlayerPerformance(string $guid, int $days): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/performance'); }
    public function getPlayerHistory(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/performance'); }
    public function getPlayerPlaystyle(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/playstyle'); }
    public function getMatchReport(string $matchId): ?array { return $this->get('/stats/match/' . urlencode($matchId) . '/advanced'); }

    // War Room Enhanced endpoints
    public function getPlayerPeakPerformance(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/peak-performance'); }
    public function getPlayerComboMetrics(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/combos'); }
    public function getPlayerDrilldown(string $guid, string $stat = 'kills', string $dimension = 'weapon', int $limit = 10): ?array { 
        return $this->get('/stats/player/' . urlencode($guid) . '/drilldown', [
            'stat' => $stat,
            'dimension' => $dimension,
            'limit' => $limit
        ]); 
    }
    public function getPlayerVehicleStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/vehicles'); }
    public function getPlayerGameFlowStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/game-flow'); }
    public function getPlayerWorldStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/world'); }
    public function getPlayerBotStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/bots'); }
    public function getPlayerWarRoomData(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/war-room'); }
    
    // Enhanced Leaderboards
    public function getContextualLeaderboard(string $stat, string $dimension, string $value, int $limit = 25): ?array {
        return $this->get('/stats/leaderboard/contextual', [
            'stat' => $stat,
            'dimension' => $dimension,
            'value' => $value,
            'limit' => $limit
        ]);
    }
    public function getComboLeaderboard(string $metric, int $limit = 25): ?array {
        return $this->get('/stats/leaderboard/combos', ['metric' => $metric, 'limit' => $limit]);
    }
    public function getPeakPerformanceLeaderboard(string $dimension = 'evening', int $limit = 25): ?array {
        return $this->get('/stats/leaderboard/peak', ['dimension' => $dimension, 'limit' => $limit]);
    }
    public function getDrilldownOptions(string $stat = 'kd'): ?array { return $this->get('/stats/drilldown/options', ['stat' => $stat]); }

    public function getPlayerMapStats(string $guid): ?array { return []; }
    public function getPlayerComparisons(string $guid): ?array { return []; }
    public function getHeadToHead(string $guid1, string $guid2): ?array { return []; }
    public function getLeaderboardCards(): ?array { return $this->get('/stats/leaderboard/cards'); }

    // Achievement methods
    public function getPlayerAchievementProgress(int $memberId): ?array { return $this->get('/achievements/player/' . $memberId . '/progress'); }
    public function getPlayerAchievementStats(int $memberId): ?array { return $this->get('/achievements/player/' . $memberId . '/stats'); }
    public function getRecentAchievementUnlocks(int $limit = 20): ?array { return $this->get('/achievements/recent', ['limit' => $limit]); }
    public function getAchievementLeaderboard(string $sortBy = 'points', int $limit = 100): ?array { return $this->get('/achievements/leaderboard', ['sort' => $sortBy, 'limit' => $limit]); }
    public function getRarestAchievements(int $limit = 10): ?array { return $this->get('/achievements/rarest', ['limit' => $limit]); }

    // Auth/Identity methods
    public function getLoginHistory(int $memberId): ?array { return $this->get('/auth/login-history/' . $memberId); }
    public function getTrustedIPs(int $memberId): ?array { return $this->get('/auth/trusted-ips/' . $memberId); }
    public function getPendingIPApprovals(int $memberId): ?array { return $this->get('/auth/pending-approvals/' . $memberId); }
    public function deleteTrustedIP(int $memberId, int $ipId): ?array { return $this->post('/auth/trusted-ips/' . $memberId . '/delete', ['ip_id' => $ipId]); }
    public function resolvePendingIP(int $memberId, int $approvalId, string $action): ?array { return $this->post('/auth/pending-approvals/' . $memberId . '/resolve', ['approval_id' => $approvalId, 'action' => $action]); }
}

function MohaaStats_APIProxy(): void
{
    global $modSettings;
    if (empty($modSettings['mohaa_stats_enabled'])) { http_response_code(503); die(json_encode(['error'=>'disabled'])); }
    header('Content-Type: application/json');
    $endpoint = $_GET['endpoint'] ?? '';
    $api = new MohaaStatsAPIClient();
    $result = match($endpoint) {
        'global-stats' => $api->getGlobalStats(),
        'leaderboard' => $api->getLeaderboard($_GET['stat']??'kills', min(100,max(1,(int)($_GET['limit']??25))), max(0,(int)($_GET['offset']??0)), $_GET['period']??'all'),
        'player' => $api->getPlayerStats($_GET['guid']??''),
        'matches' => $api->getRecentMatches(min(50,max(1,(int)($_GET['limit']??20))), max(0,(int)($_GET['offset']??0))),
        'match' => $api->getMatchDetails($_GET['id']??''),
        'maps' => $api->getMapStats(),
        'live' => $api->getLiveMatches(),
        default => null,
    };
    if ($result === null) { http_response_code(500); die(json_encode(['error'=>'API failed'])); }
    echo json_encode($result);
    obExit(false);
}
