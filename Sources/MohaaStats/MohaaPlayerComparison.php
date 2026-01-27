<?php
/**
 * MOHAA Player Comparison System
 * 
 * Advanced head-to-head player comparison with radar charts, differential analysis,
 * and predictive modeling
 *
 * @package MohaaStats
 * @version 2.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

class MohaaPlayerComparison
{
    private $api;
    
    public function __construct()
    {
        require_once(__DIR__ . '/MohaaStatsAPI.php');
        $this->api = new MohaaStatsAPIClient();
    }
    
    /**
     * Compare two players
     */
    public function comparePlayers(string $guid1, string $guid2): array
    {
        // Fetch stats for both players
        $player1Data = $this->api->getPlayerStats($guid1);
        $player2Data = $this->api->getPlayerStats($guid2);
        
        if (!$player1Data || !isset($player1Data['player'])) {
            throw new Exception('Could not fetch data for player 1');
        }
        
        if (!$player2Data || !isset($player2Data['player'])) {
            throw new Exception('Could not fetch data for player 2');
        }
        
        $player1 = $player1Data['player'];
        $player2 = $player2Data['player'];
        
        return [
            'player1' => $player1,
            'player2' => $player2,
            'comparison' => $this->generateComparison($player1, $player2),
            'winner_analysis' => $this->analyzeWinner($player1, $player2),
        ];
    }
    
    private function generateComparison(array $p1, array $p2): array
    {
        $metrics = [
            'kills' => [$p1['kills'] ?? 0, $p2['kills'] ?? 0],
            'deaths' => [$p1['deaths'] ?? 0, $p2['deaths'] ?? 0],
            'kd_ratio' => [$p1['kd_ratio'] ?? 0, $p2['kd_ratio'] ?? 0],
            'accuracy' => [$p1['accuracy'] ?? 0, $p2['accuracy'] ?? 0],
            'headshots' => [$p1['headshots'] ?? 0, $p2['headshots'] ?? 0],
            'playtime_hours' => [($p1['playtime_minutes'] ?? 0) / 60, ($p2['playtime_minutes'] ?? 0) / 60],
        ];
        
        $comparison = [];
        foreach ($metrics as $key => $values) {
            $comparison[$key] = [
                'player1' => $values[0],
                'player2' => $values[1],
                'diff' => $values[0] - $values[1],
                'winner' => $values[0] > $values[1] ? 1 : ($values[0] < $values[1] ? 2 : 0),
            ];
        }
        
        return $comparison;
    }
    
    private function analyzeWinner(array $p1, array $p2): array
    {
        $p1Score = 0;
        $p2Score = 0;
        
        $metrics = ['kills', 'kd_ratio', 'accuracy', 'headshots', 'wins'];
        foreach ($metrics as $metric) {
            $v1 = $p1[$metric] ?? 0;
            $v2 = $p2[$metric] ?? 0;
            if ($v1 > $v2) $p1Score++;
            else if ($v2 > $v1) $p2Score++;
        }
        
        return [
            'player1_score' => $p1Score,
            'player2_score' => $p2Score,
            'winner' => $p1Score > $p2Score ? 1 : ($p2Score > $p1Score ? 2 : 0),
        ];
    }
}
