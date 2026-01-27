<?php
/**
 * AI Performance Predictions Template
 * 
 * Advanced visualizations for:
 * - Next match predictions
 * - Performance forecasting
 * - Optimal playtime recommendations
 * - Win probability analysis
 *
 * @package MohaaStats
 * @version 2.0.0
 */

function template_mohaa_predictions()
{
    global $context, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons stats floatleft"></span>
            ', $context['page_title'], '
        </h3>
    </div>
    
    <div class="windowbg">
        <div class="content">';
    
    if (isset($context['prediction_error'])) {
        echo '
            <div class="errorbox">', $context['prediction_error'], '</div>';
        return;
    }
    
    // Prediction Dashboard
    echo '
            <div class="prediction_dashboard">
                <div class="prediction_grid">';
    
    // Next Match Prediction Card
    if (isset($context['next_match'])) {
        template_next_match_prediction($context['next_match']);
    }
    
    // Performance Forecast
    if (isset($context['forecast'])) {
        template_performance_forecast($context['forecast']);
    }
    
    // Optimal Playtime
    if (isset($context['optimal_time'])) {
        template_optimal_playtime($context['optimal_time']);
    }
    
    echo '
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .prediction_dashboard {
            padding: 20px;
        }
        
        .prediction_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .prediction_card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 24px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        
        .prediction_card:hover {
            transform: translateY(-5px);
        }
        
        .prediction_card h4 {
            margin: 0 0 20px 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .prediction_card .icon {
            font-size: 24px;
        }
        
        .metric_big {
            font-size: 48px;
            font-weight: 700;
            margin: 20px 0;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .metric_range {
            text-align: center;
            opacity: 0.9;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .confidence_bar {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .confidence_fill {
            background: linear-gradient(90deg, #4ade80, #22c55e);
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }
        
        .factors_list {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 15px;
        }
        
        .factor_item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .factor_impact {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,0.2);
        }
        
        .factor_impact.positive {
            background: rgba(74, 222, 128, 0.3);
        }
        
        .factor_impact.negative {
            background: rgba(248, 113, 113, 0.3);
        }
        
        .recommendations_box {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            border-left: 4px solid #fbbf24;
        }
        
        .recommendation_item {
            padding: 8px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .recommendation_item:before {
            content: "💡 ";
            margin-right: 8px;
        }
        
        .chart_container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .time_badge {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(251, 191, 36, 0.3);
            border-radius: 20px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .outlook_badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .outlook_badge.favorable {
            background: linear-gradient(135deg, #4ade80, #22c55e);
        }
        
        .outlook_badge.even {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }
        
        .outlook_badge.challenging {
            background: linear-gradient(135deg, #f87171, #ef4444);
        }
    </style>';
}

function template_next_match_prediction($prediction)
{
    if (!isset($prediction['predictions'])) return;
    
    $kd = $prediction['predictions']['kd_ratio'];
    $accuracy = $prediction['predictions']['accuracy'];
    
    echo '
    <div class="prediction_card">
        <h4>
            <span class="icon">🎯</span>
            Next Match Prediction
        </h4>
        
        <div class="metric_big">
            K/D: ', $kd['value'], '
        </div>
        
        <div class="metric_range">
            Range: ', $kd['range']['min'], ' - ', $kd['range']['max'], '
        </div>
        
        <div class="confidence_bar">
            <div class="confidence_fill" style="width: ', $kd['confidence'], '%"></div>
        </div>
        
        <div style="text-align: center; font-size: 13px; opacity: 0.9;">
            ', round($kd['confidence']), '% Confidence
        </div>
        
        <div class="factors_list">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">
                Impact Factors
            </div>';
    
    foreach ($prediction['factors'] as $name => $factor) {
        $impact = $factor['impact'];
        $impactClass = $impact > 0 ? 'positive' : ($impact < 0 ? 'negative' : '');
        $impactSign = $impact > 0 ? '+' : '';
        
        echo '
            <div class="factor_item">
                <span>', ucwords(str_replace('_', ' ', $name)), '</span>
                <span class="factor_impact ', $impactClass, '">
                    ', $impactSign, $impact, '%
                </span>
            </div>';
    }
    
    echo '
        </div>';
    
    if (!empty($prediction['recommendations'])) {
        echo '
        <div class="recommendations_box">';
        foreach ($prediction['recommendations'] as $rec) {
            echo '
            <div class="recommendation_item">', $rec['message'], '</div>';
        }
        echo '
        </div>';
    }
    
    echo '
    </div>';
}

function template_performance_forecast($forecast)
{
    if (!isset($forecast['forecast'])) return;
    
    // Prepare data for ApexCharts
    $dates = [];
    $projectedKD = [];
    
    foreach ($forecast['forecast'] as $day) {
        $dates[] = $day['date'];
        $projectedKD[] = $day['projected_kd'];
    }
    
    $chartId = 'forecast_chart_' . mt_rand();
    
    echo '
    <div class="prediction_card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <h4>
            <span class="icon">📈</span>
            7-Day Performance Forecast
        </h4>
        
        <div class="outlook_badge ', strtolower($forecast['trend_direction']), '">
            ', $forecast['outlook'], '
        </div>
        
        <div class="chart_container">
            <div id="', $chartId, '"></div>
        </div>
        
        <div class="metric_range" style="color: white;">
            Current K/D: <strong>', $forecast['current_kd'], '</strong> &nbsp;|&nbsp; 
            Trend: <strong>', $forecast['trend_direction'], '</strong>
        </div>
    </div>
    
    <script>
    (function() {
        const options = {
            series: [{
                name: "Projected K/D",
                data: ', json_encode($projectedKD), '
            }],
            chart: {
                type: "area",
                height: 250,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    speed: 800
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: "smooth",
                width: 3
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                }
            },
            xaxis: {
                categories: ', json_encode($dates), ',
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: "11px"
                    }
                }
            },
            yaxis: {
                title: {
                    text: "K/D Ratio"
                },
                decimalsInFloat: 2
            },
            colors: ["#667eea"],
            grid: {
                borderColor: "#e7e7e7"
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2);
                    }
                }
            }
        };
        
        const chart = new ApexCharts(document.getElementById("', $chartId, '"), options);
        chart.render();
    })();
    </script>';
}

function template_optimal_playtime($optimal)
{
    if (!isset($optimal['peak_hour'])) return;
    
    $peakHour = $optimal['peak_hour'];
    $startHour = $optimal['peak_window']['start'];
    $endHour = $optimal['peak_window']['end'];
    
    // Create 24-hour performance heatmap data
    $hourlyPerformance = [];
    for ($h = 0; $h < 24; $h++) {
        $distance = abs($h - $peakHour);
        $performance = max(20, 100 - ($distance * 8)); // Peak at optimal hour
        $hourlyPerformance[] = round($performance);
    }
    
    $chartId = 'playtime_chart_' . mt_rand();
    
    echo '
    <div class="prediction_card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
        <h4>
            <span class="icon">⏰</span>
            Optimal Playtime
        </h4>
        
        <div class="time_badge">
            Peak Hour: ', sprintf('%02d:00', $peakHour), '
        </div>
        
        <div style="text-align: center; margin: 15px 0; font-size: 16px;">
            ', $optimal['recommendation'], '
        </div>
        
        <div class="chart_container">
            <div id="', $chartId, '"></div>
        </div>
        
        <div class="metric_range" style="color: white;">
            ', $optimal['performance_boost'], '
        </div>
    </div>
    
    <script>
    (function() {
        const options = {
            series: [{
                name: "Performance",
                data: ', json_encode($hourlyPerformance), '
            }],
            chart: {
                type: "heatmap",
                height: 200,
                toolbar: { show: false }
            },
            plotOptions: {
                heatmap: {
                    shadeIntensity: 0.5,
                    colorScale: {
                        ranges: [{
                            from: 0,
                            to: 40,
                            color: "#ef4444",
                            name: "Low"
                        }, {
                            from: 41,
                            to: 70,
                            color: "#fbbf24",
                            name: "Medium"
                        }, {
                            from: 71,
                            to: 100,
                            color: "#22c55e",
                            name: "Peak"
                        }]
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: Array.from({length: 24}, (_, i) => i + ":00"),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: "9px"
                    }
                }
            },
            yaxis: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + "% performance";
                    }
                }
            }
        };
        
        const chart = new ApexCharts(document.getElementById("', $chartId, '"), options);
        chart.render();
    })();
    </script>';
}

?>
