<?php
/**
 * MOHAA AI Predictions Plugin
 * 
 * Machine learning-inspired performance predictions
 *
 * @package MohaaPredictions
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

require_once(__DIR__ . '/MohaaStats/MohaaPlayerPredictor.php');

/**
 * Register actions
 */
function MohaaPredictions_Actions(array &$actions): void
{
    $actions['mohaapredictions'] = ['MohaaPredictions.php', 'MohaaPredictions_Main'];
}

/**
 * Main dispatcher
 */
function MohaaPredictions_Main(): void
{
    global $context, $txt, $modSettings, $user_info;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'] ?? 'MOHAA Stats is disabled', false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPredictions');
    
    $context['page_title'] = $txt['mohaa_predictions'] ?? 'AI Predictions';
    $context['sub_template'] = 'mohaa_predictions';
    
    // Get player ID from URL or current user
    $playerId = isset($_GET['player']) ? (int)$_GET['player'] : $user_info['id'];
    
    if (empty($playerId)) {
        fatal_error($txt['mohaa_no_player'] ?? 'No player specified', false);
        return;
    }
    
    // Get player GUID from member ID
    $guid = MohaaPlayerPredictor::getGuidFromMemberId($playerId);
    
    if (empty($guid)) {
        $context['predictions'] = [];
        $context['error'] = $txt['mohaa_no_linked_account'] ?? 'This player has not linked their game account yet.';
        return;
    }
    
    // Generate predictions
    try {
        $predictor = new MohaaPlayerPredictor($guid);
        $predictions = $predictor->generateAllPredictions();
        
        $context['predictions'] = $predictions;
        $context['player_guid'] = $guid;
        $context['player_id'] = $playerId;
        $context['error'] = null;
        
    } catch (Exception $e) {
        $context['predictions'] = [];
        $context['error'] = $e->getMessage();
    }
}

?>
