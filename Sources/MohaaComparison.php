<?php
/**
 * MOHAA Player Comparison Plugin
 * 
 * Side-by-side player performance comparison with radar charts
 *
 * @package MohaaComparison
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

require_once(__DIR__ . '/MohaaStats/MohaaPlayerComparison.php');

/**
 * Register actions
 */
function MohaaComparison_Actions(array &$actions): void
{
    $actions['mohaacomparison'] = ['MohaaComparison.php', 'MohaaComparison_Main'];
}

/**
 * Main dispatcher
 */
function MohaaComparison_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'] ?? 'MOHAA Stats is disabled', false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayerComparison');
    
    $context['page_title'] = $txt['mohaa_comparison'] ?? 'Player Comparison';
    $context['sub_template'] = 'mohaa_player_comparison';
    
    // Get player GUIDs from URL
    $player1 = isset($_GET['player1']) ? $_GET['player1'] : '';
    $player2 = isset($_GET['player2']) ? $_GET['player2'] : '';
    
    $context['player1_guid'] = $player1;
    $context['player2_guid'] = $player2;
    
    // If both players provided, do comparison
    if (!empty($player1) && !empty($player2)) {
        try {
            $comparison = new MohaaPlayerComparison();
            $result = $comparison->comparePlayers($player1, $player2);
            
            $context['comparison'] = $result;
            $context['error'] = null;
            
        } catch (Exception $e) {
            $context['comparison'] = null;
            $context['error'] = $e->getMessage();
        }
    } else {
        $context['comparison'] = null;
        $context['error'] = null;
    }
    
    // Get list of all players for dropdown
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    try {
        $players = $api->makeRequest('GET', '/players/list?limit=100');
        $context['available_players'] = $players['players'] ?? [];
    } catch (Exception $e) {
        $context['available_players'] = [];
    }
}

?>
