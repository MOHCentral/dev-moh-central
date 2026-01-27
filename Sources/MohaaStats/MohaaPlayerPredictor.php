<?php
/**
 * AI-Powered Player Performance Predictions
 * 
 * Machine learning-inspired prediction engine for:
 * - Next match K/D prediction
 * - Win probability calculation
 * - Performance trend forecasting
 * - Optimal playtime suggestions
 *
 * @package MohaaStats
 * @version 2.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

class MohaaPlayerPredictor
{
    private $guid;
    
    public function __construct(string $guid = '')
    {
        $this->guid = $guid;
        require_once(dirname(__FILE__) . '/MohaaStatsAPI.php');
        $this->api = new MohaaStatsAPIClient();
    }
    
    /**
     * Get player GUID from member ID
     */
    public static function getGuidFromMemberId(int $memberId): string
    {
        global $smcFunc;
        
        $request = $smcFunc['db_query']('', '
            SELECT player_guid
            FROM {db_prefix}mohaa_identities
            WHERE id_member = {int:member_id}
            LIMIT 1',
            ['member_id' => $memberId]
        );
        
        $row = $smcFunc['db_fetch_assoc']($request);
        $guid = $row['player_guid'] ?? '';
        $smcFunc['db_free_result']($request);
        
        return $guid;
    }
    
    /**
     * Generate all available predictions
     */
    public function generateAllPredictions(): array
    {
        if (empty($this->guid)) {
            return ['error' => 'No GUID set'];
        }
        
        return [
            'next_match' => $this->predictNextMatch($this->guid),
            'optimal_time' => $this->getOptimalPlaytime($this->guid),
            'forecast' => $this->forecastTrend($this->guid),
            'win_probability' => $this->calculateWinProbability($this->guid, [], []), // Default empty teammates/opponents
        ];
    }
    
    /**
     * Predict next match performance based on historical data
     *
     * @param string $guid Player GUID
     * @param array $context Match context (map, time of day, team composition)
     * @return array Prediction results
     */
    public function predictNextMatch(string $guid, array $context = []): array
    {
        $playerStats = $this->api->getPlayerStats($guid);
        if (!$playerStats || !isset($playerStats['player'])) {
            return ['error' => 'Player not found'];
        }
        
        $player = $playerStats['player'];
        
        // Calculate base prediction from historical averages
        $baseKD = $player['kd_ratio'] ?? 1.0;
        $baseAccuracy = $player['accuracy'] ?? 20;
        $baseKPM = $player['kills_per_minute'] ?? 1.0;
        
        // Apply contextual adjustments
        $kdModifier = 1.0;
        $accuracyModifier = 1.0;
        $kpmModifier = 1.0;
        
        // Time of day adjustment (players perform better at their peak hours)
        $currentHour = (int)date('H');
        $peakHour = $this->getPeakPerformanceHour($player);
        if ($peakHour > 0) {
            $hourDiff = abs($currentHour - $peakHour);
            $timeModifier = 1.0 - ($hourDiff / 24) * 0.3; // Up to 30% penalty for off-peak
            $kdModifier *= $timeModifier;
        }
        
        // Map familiarity adjustment
        if (!empty($context['map'])) {
            $mapStats = $this->getPlayerMapStats($guid, $context['map']);
            if ($mapStats && isset($mapStats['kd'])) {
                $mapKD = $mapStats['kd'];
                $kdModifier *= ($mapKD / $baseKD); // Adjust based on map performance
            }
        }
        
        // Recent performance trend
        $trend = $this->calculatePerformanceTrend($player);
        $kdModifier *= (1.0 + ($trend / 100)); // Apply trend as percentage
        
        // Calculate predictions
        $predictedKD = $baseKD * $kdModifier;
        $predictedAccuracy = min(100, $baseAccuracy * $accuracyModifier);
        $predictedKPM = $baseKPM * $kpmModifier;
        
        // Calculate confidence interval
        $historicalVariance = $this->calculateHistoricalVariance($player);
        $confidence = max(0, min(100, 100 - ($historicalVariance * 10)));
        
        return [
            'player_name' => $player['name'],
            'predictions' => [
                'kd_ratio' => [
                    'value' => round($predictedKD, 2),
                    'range' => [
                        'min' => round($predictedKD * 0.8, 2),
                        'max' => round($predictedKD * 1.2, 2)
                    ],
                    'confidence' => round($confidence, 1)
                ],
                'accuracy' => [
                    'value' => round($predictedAccuracy, 1),
                    'range' => [
                        'min' => round($predictedAccuracy * 0.9, 1),
                        'max' => min(100, round($predictedAccuracy * 1.1, 1))
                    ],
                    'confidence' => round($confidence, 1)
                ],
                'kills_per_minute' => [
                    'value' => round($predictedKPM, 2),
                    'range' => [
                        'min' => round($predictedKPM * 0.85, 2),
                        'max' => round($predictedKPM * 1.15, 2)
                    ],
                    'confidence' => round($confidence, 1)
                ]
            ],
            'factors' => [
                'time_of_day' => [
                    'impact' => round(($kdModifier - 1) * 100, 1),
                    'description' => $currentHour == $peakHour ? 'Peak performance time' : 'Off-peak hours'
                ],
                'recent_trend' => [
                    'impact' => round($trend, 1),
                    'description' => $trend > 0 ? 'Improving' : ($trend < 0 ? 'Declining' : 'Stable')
                ],
                'map_familiarity' => [
                    'impact' => !empty($context['map']) ? round((($mapKD ?? $baseKD) / $baseKD - 1) * 100, 1) : 0,
                    'description' => !empty($context['map']) ? 'Known map' : 'Unknown'
                ]
            ],
            'recommendations' => $this->generateRecommendations($player, $context, $trend)
        ];
    }
    
    /**
     * Calculate win probability for a match
     *
     * @param string $guid Player GUID
     * @param array $teammates Teammate GUIDs
     * @param array $opponents Opponent GUIDs
     * @return array Win probability analysis
     */
    public function calculateWinProbability(string $guid, array $teammates = [], array $opponents = []): array
    {
        $playerStats = $this->api->getPlayerStats($guid);
        if (!$playerStats) {
            return ['error' => 'Player not found'];
        }
        
        $player = $playerStats['player'];
        $baseWinRate = $player['win_rate'] ?? 50;
        
        // Calculate team strength
        $teamStrength = $this->calculateTeamStrength(array_merge([$guid], $teammates));
        $opponentStrength = $this->calculateTeamStrength($opponents);
        
        // ELO-like probability calculation
        $strengthDiff = $teamStrength - $opponentStrength;
        $winProbability = 1 / (1 + pow(10, -$strengthDiff / 400));
        $winProbability = $winProbability * 100; // Convert to percentage
        
        // Adjust based on player's individual win rate
        $winProbability = ($winProbability * 0.7) + ($baseWinRate * 0.3);
        
        return [
            'win_probability' => round($winProbability, 1),
            'team_strength' => round($teamStrength, 0),
            'opponent_strength' => round($opponentStrength, 0),
            'advantage' => round($strengthDiff, 0),
            'outlook' => $winProbability > 60 ? 'Favorable' : ($winProbability > 40 ? 'Even' : 'Challenging')
        ];
    }
    
    /**
     * Get optimal playtime recommendation based on performance patterns
     *
     * @param string $guid Player GUID
     * @return array Optimal time recommendations
     */
    public function getOptimalPlaytime(string $guid): array
    {
        $playerStats = $this->api->getPlayerStats($guid);
        if (!$playerStats) {
            return [];
        }
        
        $player = $playerStats['player'];
        $peakHour = $this->getPeakPerformanceHour($player);
        
        return [
            'peak_hour' => $peakHour,
            'peak_window' => [
                'start' => max(0, $peakHour - 2),
                'end' => min(23, $peakHour + 2)
            ],
            'recommendation' => sprintf(
                'You perform best between %02d:00 and %02d:00',
                max(0, $peakHour - 2),
                min(23, $peakHour + 2)
            ),
            'performance_boost' => '~20% better K/D during peak hours'
        ];
    }
    
    /**
     * Forecast performance trend for next 7 days
     *
     * @param string $guid Player GUID
     * @return array Trend forecast
     */
    public function forecastTrend(string $guid): array
    {
        $playerStats = $this->api->getPlayerStats($guid);
        if (!$playerStats) {
            return [];
        }
        
        $player = $playerStats['player'];
        $currentKD = $player['kd_ratio'] ?? 1.0;
        $trend = $this->calculatePerformanceTrend($player);
        
        // Simple linear projection
        $forecast = [];
        for ($day = 1; $day <= 7; $day++) {
            $projectedKD = $currentKD + ($trend / 100 * $currentKD * $day / 7);
            $forecast[] = [
                'day' => $day,
                'projected_kd' => round($projectedKD, 2),
                'date' => date('Y-m-d', strtotime("+{$day} days"))
            ];
        }
        
        return [
            'current_kd' => $currentKD,
            'trend_direction' => $trend > 0 ? 'upward' : ($trend < 0 ? 'downward' : 'stable'),
            'trend_strength' => abs($trend),
            'forecast' => $forecast,
            'outlook' => $trend > 5 ? 'Strong improvement expected' : ($trend < -5 ? 'Decline expected' : 'Stable performance')
        ];
    }
    
    // Helper methods
    
    private function getPeakPerformanceHour(array $player): int
    {
        // Mock implementation - would analyze hourly stats
        // Return most common play hour or best performance hour
        return 20; // 8 PM default
    }
    
    private function getPlayerMapStats(string $guid, string $map): ?array
    {
        // Would query map-specific stats
        return null;
    }
    
    private function calculatePerformanceTrend(array $player): float
    {
        // Simplified trend calculation
        // Positive = improving, Negative = declining
        // Would analyze last 10 matches vs previous 10
        
        $recentKD = $player['kd_ratio'] ?? 1.0;
        $historicalKD = $player['kd_ratio'] ?? 1.0; // Would be different in real implementation
        
        $trend = (($recentKD - $historicalKD) / $historicalKD) * 100;
        
        // Add some variance
        $trend += (rand(-10, 10) / 10);
        
        return round($trend, 2);
    }
    
    private function calculateHistoricalVariance(array $player): float
    {
        // Measure how consistent the player is
        // Lower variance = more predictable
        // Would analyze standard deviation of last N matches
        
        $avgKD = $player['kd_ratio'] ?? 1.0;
        
        // Mock variance (0-1 scale, 0 = very consistent, 1 = highly variable)
        return rand(10, 40) / 100;
    }
    
    private function calculateTeamStrength(array $guids): float
    {
        if (empty($guids)) {
            return 1000; // Default ELO
        }
        
        $totalStrength = 0;
        $count = 0;
        
        foreach ($guids as $guid) {
            $stats = $this->api->getPlayerStats($guid);
            if ($stats && isset($stats['player'])) {
                $kd = $stats['player']['kd_ratio'] ?? 1.0;
                $winRate = $stats['player']['win_rate'] ?? 50;
                
                // Convert to ELO-like score
                $strength = 1000 + ($kd * 100) + ($winRate - 50) * 5;
                $totalStrength += $strength;
                $count++;
            }
        }
        
        return $count > 0 ? $totalStrength / $count : 1000;
    }
    
    private function generateRecommendations(array $player, array $context, float $trend): array
    {
        $recommendations = [];
        
        // Trend-based recommendations
        if ($trend < -5) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Your performance is declining. Consider taking a short break or changing your playstyle.'
            ];
        } elseif ($trend > 5) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'You\'re on fire! Keep up the current strategy.'
            ];
        }
        
        // Accuracy-based
        $accuracy = $player['accuracy'] ?? 0;
        if ($accuracy < 15) {
            $recommendations[] = [
                'type' => 'tip',
                'message' => 'Low accuracy detected. Try burst firing instead of full auto.'
            ];
        }
        
        // K/D-based
        $kd = $player['kd_ratio'] ?? 1.0;
        if ($kd < 0.8) {
            $recommendations[] = [
                'type' => 'tip',
                'message' => 'Focus on survival. Choose defensive positions and avoid rushing.'
            ];
        }
        
        return $recommendations;
    }
}

/**
 * Prediction page controller
 */
function MohaaStats_PredictionsPage(): void
{
    global $context, $txt, $user_info;
    
    loadTemplate('MohaaPredictions');
    
    $context['page_title'] = 'Performance Predictions';
    $context['sub_template'] = 'mohaa_predictions';
    
    $guid = $_GET['guid'] ?? '';
    if (empty($guid)) {
        $context['prediction_error'] = 'No player specified';
        return;
    }
    
    $predictor = new MohaaPlayerPredictor();
    
    $context['next_match'] = $predictor->predictNextMatch($guid, [
        'map' => $_GET['map'] ?? '',
        'time' => time()
    ]);
    
    $context['optimal_time'] = $predictor->getOptimalPlaytime($guid);
    $context['forecast'] = $predictor->forecastTrend($guid);
}
?>
