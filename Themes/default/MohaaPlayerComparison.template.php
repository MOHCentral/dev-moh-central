<?php
/**
 * Player Comparison Template
 * Advanced head-to-head analysis with radar charts, differentials, winner analysis
 */

if (!defined('SMF'))
    die('No direct access...');

function template_mohaa_player_comparison()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons stats"></span> ', $txt['mohaa_compare_players'] ?? 'Player Comparison', '
        </h3>
    </div>';
    
    // Player Selection Form
    if (!empty($context['available_players']) && count($context['available_players']) > 0) {
        echo '
    <div class="windowbg" style="padding: 20px; margin-bottom: 20px;">
        <form action="', $scripturl, '" method="get" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <input type="hidden" name="action" value="mohaastats">
            <input type="hidden" name="sa" value="comparison">
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Player 1:</label>
                <select name="player1" class="input_select" required>
                    <option value="">-- Select Player --</option>';
        
        foreach ($context['available_players'] as $player) {
            $selected = !empty($context['player1_guid']) && $context['player1_guid'] === $player['guid'] ? ' selected' : '';
            echo '
                    <option value="', $player['guid'], '"', $selected, '>', htmlspecialchars($player['name']), '</option>';
        }
        
        echo '
                </select>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Player 2:</label>
                <select name="player2" class="input_select" required>
                    <option value="">-- Select Player --</option>';
        
        foreach ($context['available_players'] as $player) {
            $selected = !empty($context['player2_guid']) && $context['player2_guid'] === $player['guid'] ? ' selected' : '';
            echo '
                    <option value="', $player['guid'], '"', $selected, '>', htmlspecialchars($player['name']), '</option>';
        }
        
        echo '
                </select>
            </div>
            
            <div>
                <button type="submit" class="button" style="padding: 8px 20px;">
                    Compare Players
                </button>
            </div>
        </form>
    </div>';
    } else {
        echo '
    <div class="windowbg centertext" style="padding: 40px;">
        <p style="color: #888; font-size: 16px;">No players available for comparison. Players must link their accounts first.</p>
    </div>';
    }
    
    if (!empty($context['comparison_error'])) {
        echo '
    <div class="windowbg centertext" style="padding: 40px;">
        <p style="color: #e74c3c; font-size: 16px;">', $context['comparison_error'], '</p>
    </div>';
        return;
    }
    
    if (empty($context['compared_players'])) {
        return;
    }
    
    $players = $context['compared_players'];
    $charts = $context['comparison_charts'];
    $winner = $context['winner_analysis'];
    $differentials = $context['differential_stats'];
    
    // ApexCharts CDN
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <div class="windowbg" style="padding: 20px;">
        
        <!-- Winner Banner -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; margin-bottom: 30px; color: white; text-align: center;">
            <h2 style="margin: 0 0 10px 0; font-size: 28px;">🏆 Overall Winner</h2>
            <h3 style="margin: 0; font-size: 36px; font-weight: bold;">', htmlspecialchars($winner['winner']['player']), '</h3>
            <p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">Composite Score: ', $winner['winner']['total_score'], '/100</p>
        </div>
        
        <!-- Player Cards Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">';
    
    foreach ($players as $index => $player) {
        $rankColor = $index === 0 ? '#FFD700' : ($index === 1 ? '#C0C0C0' : '#CD7F32');
        echo '
            <div class="roundframe" style="text-align: center; padding: 20px; position: relative;">
                <div style="position: absolute; top: 10px; right: 10px; background: ', $rankColor, '; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    #', ($index + 1), '
                </div>
                <h4 style="margin: 0 0 15px 0; font-size: 20px; color: #2c3e50;">', htmlspecialchars($player['name']), '</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: left;">
                    <div>
                        <span style="font-size: 24px; font-weight: bold; color: #4caf50;">', number_format($player['kills'] ?? 0), '</span>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Kills</div>
                    </div>
                    <div>
                        <span style="font-size: 24px; font-weight: bold; color: #f44336;">', number_format($player['deaths'] ?? 0), '</span>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Deaths</div>
                    </div>
                    <div>
                        <span style="font-size: 24px; font-weight: bold; color: #2196f3;">', number_format($player['kd_ratio'] ?? 0, 2), '</span>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">K/D</div>
                    </div>
                    <div>
                        <span style="font-size: 24px; font-weight: bold; color: #ff9800;">', number_format($player['accuracy'] ?? 0, 1), '%</span>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Accuracy</div>
                    </div>
                </div>
            </div>';
    }
    
    echo '
        </div>
        
        <!-- Radar Chart - Overall Comparison -->
        <div class="roundframe" style="padding: 20px; margin-bottom: 30px;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50;">📊 Multi-Dimensional Analysis</h3>
            <div id="comparisonRadarChart"></div>
        </div>
        
        <!-- Stat Bars -->
        <div class="roundframe" style="padding: 20px; margin-bottom: 30px;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50;">📈 Key Stats Comparison</h3>
            <div id="comparisonBarsChart"></div>
        </div>
        
        <!-- Differential Analysis -->
        <div class="roundframe" style="padding: 20px; margin-bottom: 30px;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50;">🔍 Differential Analysis</h3>
            <p style="color: #666; margin-bottom: 20px;">Comparing all players against <strong>', htmlspecialchars($players[0]['name']), '</strong> (baseline)</p>
            
            <div style="overflow-x: auto;">
                <table class="table_grid" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Kills</th>
                            <th>Deaths</th>
                            <th>K/D</th>
                            <th>Accuracy</th>
                            <th>Headshots</th>
                            <th>Win Rate</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($differentials as $playerName => $diffs) {
        echo '
                        <tr>
                            <td><strong>', htmlspecialchars($playerName), '</strong></td>';
        
        foreach (['kills', 'deaths', 'kd_ratio', 'accuracy', 'headshots', 'win_rate'] as $stat) {
            if (isset($diffs[$stat])) {
                $diff = $diffs[$stat];
                $color = $diff['better'] ? '#2ecc71' : '#e74c3c';
                $arrow = $diff['better'] ? '▲' : '▼';
                
                echo '
                            <td style="color: ', $color, ';">
                                ', $arrow, ' ', number_format(abs($diff['percent_diff']), 1), '%
                                <br><span style="font-size: 10px; opacity: 0.7;">(', $diff['compare'], ' vs ', $diff['base'], ')</span>
                            </td>';
            } else {
                echo '<td>-</td>';
            }
        }
        
        echo '
                        </tr>';
    }
    
    echo '
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Rankings Table -->
        <div class="roundframe" style="padding: 20px;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50;">🏆 Final Rankings</h3>
            <table class="table_grid" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 60px;">Rank</th>
                        <th>Player</th>
                        <th style="width: 120px;">Composite Score</th>
                        <th style="width: 100px;"></th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($winner['rankings'] as $rank => $rankData) {
        $medal = $rank === 0 ? '🥇' : ($rank === 1 ? '🥈' : ($rank === 2 ? '🥉' : ''));
        echo '
                    <tr>
                        <td style="text-align: center; font-size: 24px;">', $medal, '</td>
                        <td><strong>', htmlspecialchars($rankData['player']), '</strong></td>
                        <td>
                            <div style="background: #e0e0e0; height: 24px; border-radius: 12px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: ', $rankData['total_score'], '%; transition: width 0.3s;"></div>
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: bold;">', $rankData['total_score'], '/100</td>
                    </tr>';
    }
    
    echo '
                </tbody>
            </table>
        </div>
        
    </div>
    
    <script>
    (function() {
        // Radar Chart
        var radarData = ', json_encode($charts['radar']), ';
        var radarOptions = {
            series: radarData.series,
            chart: {
                type: "radar",
                height: 450,
                toolbar: { show: false }
            },
            xaxis: {
                categories: radarData.categories
            },
            yaxis: {
                min: 0,
                max: 100,
                tickAmount: 5
            },
            colors: ["#3498db", "#e74c3c", "#2ecc71", "#f39c12"],
            stroke: {
                width: 2
            },
            fill: {
                opacity: 0.2
            },
            markers: {
                size: 4
            },
            legend: {
                position: "bottom",
                horizontalAlign: "center"
            }
        };
        new ApexCharts(document.querySelector("#comparisonRadarChart"), radarOptions).render();
        
        // Bar Chart
        var barData = ', json_encode($charts['bars']), ';
        var barOptions = {
            series: barData.series,
            chart: {
                type: "bar",
                height: 400,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "70%",
                    endingShape: "rounded"
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: barData.categories
            },
            colors: ["#3498db", "#e74c3c", "#2ecc71", "#f39c12", "#9b59b6", "#1abc9c"],
            legend: {
                position: "top"
            }
        };
        new ApexCharts(document.querySelector("#comparisonBarsChart"), barOptions).render();
    })();
    </script>';
}
?>
