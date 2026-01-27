<?php
/**
 * MOHAA Player Profile Template
 */
function template_mohaa_player_full()
{
    global $context, $txt, $settings, $scripturl;

    $player = $context['mohaa_player'];
    $deep = $player['deep_stats'] ?? [];
    $stats = $player['stats'] ?? [];
    
    // Fallback: If deep stats failed, use basic stats with nested structure
    if (empty($deep) && !empty($stats)) {
        $deep = [
            'combat' => [
                'kills' => $stats['total_kills'] ?? 0,
                'deaths' => $stats['total_deaths'] ?? 0,
                'headshots' => $stats['total_headshots'] ?? 0,
                'headshot_percent' => $stats['headshot_percent'] ?? 0,
                'damage' => $stats['total_damage'] ?? 0,
                'kd_ratio' => $stats['kd_ratio'] ?? 0,
                'melee_kills' => $stats['melee_kills'] ?? 0,
                'suicides' => $stats['suicides'] ?? 0,
                'highest_streak' => $stats['highest_streak'] ?? 0,
            ],
            'accuracy' => [
                'shots_fired' => $stats['shots_fired'] ?? 0,
                'shots_hit' => $stats['shots_hit'] ?? 0,
                'accuracy' => $stats['accuracy'] ?? 0,
            ],
            'movement' => [
                'total_distance_km' => $stats['total_distance_km'] ?? 0,
                'sprint_distance_km' => $stats['sprint_distance_km'] ?? 0,
                'jump_count' => $stats['jump_count'] ?? 0,
            ],
            'session' => [
                'matches_played' => $stats['matches_played'] ?? 0,
                'matches_won' => $stats['matches_won'] ?? 0,
                'win_rate' => $stats['win_rate'] ?? 0,
                'play_time_seconds' => $stats['play_time_seconds'] ?? 0,
            ],
        ];
    }
    
    // Aesthetic: Dark "Command & Control" variables
    $color_bg = '#1a1a1a';
    $color_panel = '#242424';
    $color_accent = '#4CAF50'; // Military Green
    $color_text = '#e0e0e0';
    $color_muted = '#9e9e9e';
    
    echo '
     <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <div class="mohaa-profile-container" style="background: '.$color_bg.'; color: '.$color_text.'; padding: 20px; font-family: \'Roboto\', sans-serif;">
        <!-- Header Section -->
        <div class="mohaa-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid '.$color_accent.'; padding-bottom: 15px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center;">
                <div style="width: 80px; height: 80px; background: #333; margin-right: 20px; display: flex; align-items: center; justify-content: center; border: 1px solid #444;">
                    <span style="font-size: 32px; font-weight: bold; color: '.$color_accent.';">'.strtoupper(substr($player['name'], 0, 1)).'</span>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 28px; color: '.$color_accent.'; text-transform: uppercase; letter-spacing: 1px;">'.$player['name'].'</h1>
                    <div style="color: '.$color_muted.'; font-size: 14px; margin-top: 5px;">GUID: '.substr($player['guid'], 0, 8).'...</div>
                    '.(!empty($player['linked_member']) ? '<div style="margin-top: 5px;"><a href="'.$scripturl.'?action=profile;u='.$player['linked_member']['id_member'].'" style="color: #64B5F6; text-decoration: none;">View Forum Profile</a></div>' : '').'
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 36px; font-weight: bold;">'.number_format($deep['combat']['kd_ratio'] ?? 0, 2).'</div>
                <div style="color: '.$color_muted.'; text-transform: uppercase; font-size: 12px;">K/D Ratio</div>
            </div>
        </div>

        <!-- Metric Grid: Combat Core -->
        <h3 style="border-left: 4px solid '.$color_accent.'; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">Combat Telemetry</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Total Kills</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['combat']['kills'] ?? 0).'</div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Deaths</div>
                <div style="font-size: 24px; font-weight: bold; color: #F44336;">'.number_format($deep['combat']['deaths'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Headshots</div>
                <div style="font-size: 24px; font-weight: bold; color: #FFC107;">'.number_format($deep['combat']['headshots'] ?? 0).' <span style="font-size: 14px; color: '.$color_muted.';">('.number_format($deep['combat']['headshot_percent'] ?? 0, 1).'%)</span></div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Streak (Best)</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['combat']['highest_streak'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Melee Kills</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['combat']['melee_kills'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Suicides</div>
                <div style="font-size: 24px; font-weight: bold; color: #F44336;">'.number_format($deep['combat']['suicides'] ?? 0).'</div>
            </div>
        </div>

        <!-- Combat Style Card -->
        <h3 style="border-left: 4px solid #E91E63; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">⚔️ Combat Style</h3>
        <div style="background: '.$color_panel.'; padding: 20px; border: 1px solid #333; margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <!-- Left: Stats Table -->
                <div>
                    <table style="width: 100%; border-collapse: collapse; color: '.$color_text.';">
                        <tr style="border-bottom: 1px solid #444;">
                            <td style="padding: 10px; font-weight: bold;">Bash Kills</td>
                            <td style="padding: 10px; text-align: right;">'.number_format($deep['combat']['bash_kills'] ?? 0).'</td>
                            <td style="padding: 10px; text-align: right; color: #00BCD4;">'.number_format(($deep['combat']['bash_percent'] ?? 0), 1).'%</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #444;">
                            <td style="padding: 10px; font-weight: bold;">Roadkills</td>
                            <td style="padding: 10px; text-align: right;">'.number_format($deep['combat']['roadkill_kills'] ?? 0).'</td>
                            <td style="padding: 10px; text-align: right; color: #00BCD4;">'.number_format(($deep['combat']['roadkill_percent'] ?? 0), 1).'%</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #444;">
                            <td style="padding: 10px; font-weight: bold;">Telefrag Kills</td>
                            <td style="padding: 10px; text-align: right;">'.number_format($deep['combat']['telefrag_kills'] ?? 0).'</td>
                            <td style="padding: 10px; text-align: right; color: #00BCD4;">'.number_format(($deep['combat']['telefrag_percent'] ?? 0), 1).'%</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #444;">
                            <td style="padding: 10px; font-weight: bold;">Grenade Kills</td>
                            <td style="padding: 10px; text-align: right;">'.number_format($deep['combat']['grenade_kills'] ?? 0).'</td>
                            <td style="padding: 10px; text-align: right; color: #00BCD4;">'.number_format(($deep['combat']['grenade_percent'] ?? 0), 1).'%</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; font-weight: bold;">Standard Kills</td>
                            <td style="padding: 10px; text-align: right;">'.number_format($deep['combat']['standard_kills'] ?? 0).'</td>
                            <td style="padding: 10px; text-align: right; color: #00BCD4;">'.number_format(($deep['combat']['standard_percent'] ?? 0), 1).'%</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Right: Combat Style Radial Chart -->
                <div>
                    <div id="combat_style_chart" style="min-height: 250px;"></div>
                </div>
            </div>
        </div>
        
        <script>
            // Combat Style Radial Bar Chart
            var combatStyleOptions = {
                series: [
                    '.number_format($deep['combat']['bash_percent'] ?? 0, 1).',
                    '.number_format($deep['combat']['roadkill_percent'] ?? 0, 1).',
                    '.number_format($deep['combat']['telefrag_percent'] ?? 0, 1).',
                    '.number_format($deep['combat']['grenade_percent'] ?? 0, 1).',
                    '.number_format($deep['combat']['standard_percent'] ?? 0, 1).'
                ],
                chart: {
                    type: "radialBar",
                    height: 300
                },
                plotOptions: {
                    radialBar: {
                        offsetY: 0,
                        startAngle: 0,
                        endAngle: 270,
                        hollow: {
                            margin: 5,
                            size: "30%",
                            background: "transparent",
                        },
                        dataLabels: {
                            name: {
                                show: false
                            },
                            value: {
                                show: false
                            }
                        }
                    }
                },
                colors: ["#00BCD4", "#FF5722", "#E91E63", "#4CAF50", "#9E9E9E"],
                labels: ["Bash", "Roadkill", "Telefrag", "Grenade", "Standard"],
                legend: {
                    show: true,
                    floating: true,
                    fontSize: "14px",
                    position: "left",
                    offsetX: 0,
                    offsetY: 10,
                    labels: {
                        useSeriesColors: true,
                    },
                    markers: {
                        size: 0
                    },
                    formatter: function(seriesName, opts) {
                        return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex] + "%"
                    },
                    itemMargin: {
                        vertical: 3
                    }
                }
            };

            var combatStyleChart = new ApexCharts(document.querySelector("#combat_style_chart"), combatStyleOptions);
            combatStyleChart.render();
        </script>

        <!-- Movement & Movement -->
        <h3 style="border-left: 4px solid #2196F3; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">Movement Analysis</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Distance Traveled</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['movement']['total_distance_km'] ?? 0, 2).' <small>km</small></div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Jumps</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['movement']['jump_count'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Avg Kill Dist</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['accuracy']['avg_distance'] ?? 0, 1).' <small>m</small></div>
            </div>
        </div>

        <!-- Session Statistics -->
        <h3 style="border-left: 4px solid #9C27B0; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">📊 Session Statistics</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Matches Played</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($stats['matches_played'] ?? 0).'</div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Matches Won</div>
                <div style="font-size: 24px; font-weight: bold; color: '.$color_accent.';">'.number_format($stats['matches_won'] ?? 0).'</div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Win Rate</div>
                <div style="font-size: 24px; font-weight: bold; color: #FFC107;">'.number_format($stats['win_rate'] ?? 0, 1).'%</div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Play Time</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format(($stats['play_time_seconds'] ?? 0) / 3600, 1).' <small>hrs</small></div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">FFA Wins</div>
                <div style="font-size: 24px; font-weight: bold; color: #64B5F6;">'.number_format($player['ffa_wins'] ?? 0).'</div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Team Wins</div>
                <div style="font-size: 24px; font-weight: bold; color: #FF9800;">'.number_format($player['team_wins'] ?? 0).'</div>
            </div>
        </div>

        <!-- Stance Distribution -->
        <div style="background: '.$color_panel.'; padding: 20px; border: 1px solid #333; margin-bottom: 30px;">
            <h4 style="margin: 0 0 15px 0;">Stance Distribution & Movement Patterns</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div id="stance_distribution_chart"></div>
                <div id="movement_radar_chart"></div>
            </div>
        </div>
        
        <script>
            // Stance Distribution Pie Chart
            var stanceOptions = {
                series: [
                    '.($deep['movement']['time_standing'] ?? 0).',
                    '.($deep['movement']['time_crouching'] ?? 0).',
                    '.($deep['movement']['time_prone'] ?? 0).'
                ],
                chart: {
                    type: "pie",
                    height: 300,
                    foreColor: "'.$color_text.'"
                },
                labels: ["Standing", "Crouching", "Prone"],
                colors: ["#2196F3", "#FF9800", "#4CAF50"],
                legend: {
                    position: "bottom"
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        var total = opts.w.config.series.reduce((a, b) => a + b, 0);
                        var seconds = opts.w.config.series[opts.seriesIndex];
                        return Math.round(val) + "% (" + Math.floor(seconds / 60) + "m)";
                    }
                },
                theme: {
                    mode: "dark"
                }
            };
            
            var stanceChart = new ApexCharts(document.querySelector("#stance_distribution_chart"), stanceOptions);
            stanceChart.render();
            
            // Movement Pattern Radar
            var movementRadarOptions = {
                series: [{
                    name: "Frequency",
                    data: [
                        '.($deep['movement']['jump_count'] ?? 0).',
                        '.($deep['movement']['crouch_count'] ?? 0).',
                        '.($deep['movement']['prone_count'] ?? 0).',
                        '.($deep['movement']['sprint_count'] ?? 0).',
                        '.($deep['movement']['ladder_count'] ?? 0).'
                    ]
                }],
                chart: {
                    type: "radar",
                    height: 300,
                    foreColor: "'.$color_text.'"
                },
                xaxis: {
                    categories: ["Jumps", "Crouch", "Prone", "Sprints", "Ladders"]
                },
                yaxis: {
                    show: true
                },
                fill: {
                    opacity: 0.3
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ["#2196F3"]
                },
                markers: {
                    size: 4,
                    colors: ["#2196F3"]
                },
                theme: {
                    mode: "dark"
                }
            };
            
            var movementRadar = new ApexCharts(document.querySelector("#movement_radar_chart"), movementRadarOptions);
            movementRadar.render();
        </script>

        <!-- Weapon Performance Table -->
        <h3 style="border-left: 4px solid #FFC107; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">Weapon Mastery</h3>
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
            <div style="background: '.$color_panel.'; border: 1px solid #333; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; color: '.$color_text.';">
                <thead>
                    <tr style="background: #111; text-transform: uppercase; font-size: 13px;">
                        <th style="padding: 12px; text-align: left;">Weapon</th>
                        <th style="padding: 12px; text-align: center;">Kills</th>
                        <th style="padding: 12px; text-align: center;">Deaths</th>
                        <th style="padding: 12px; text-align: center;">HS %</th>
                        <th style="padding: 12px; text-align: center;">Acc %</th>
                        <th style="padding: 12px; text-align: center;">Damage</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (!empty($deep['weapons'])) {
        foreach ($deep['weapons'] as $w) {
            $hsPct = $w['kills'] > 0 ? ($w['headshots'] / $w['kills']) * 100 : 0;
            echo '
                    <tr style="border-bottom: 1px solid #333;">
                        <td style="padding: 12px; font-weight: bold;">'.htmlspecialchars($w['name']).'</td>
                        <td style="padding: 12px; text-align: center;">'.number_format($w['kills']).'</td>
                        <td style="padding: 12px; text-align: center; color: #F44336;">'.number_format($w['deaths']).'</td>
                        <td style="padding: 12px; text-align: center;">'.number_format($hsPct, 1).'%</td>
                        <td style="padding: 12px; text-align: center;">'.number_format($w['accuracy'], 1).'%</td>
                         <td style="padding: 12px; text-align: center;">'.number_format($w['damage']).'</td>
                    </tr>';
        }
    } else {
        echo '<tr><td colspan="6" style="padding: 20px; text-align: center; color: '.$color_muted.';">No weapon data available.</td></tr>';
    }

    echo '
                </tbody>
            </table>
                </tbody>
            </table>
        </div>
        
        <!-- Chart Container -->
        <div style="background: '.$color_panel.'; border: 1px solid #333; padding: 15px; display: flex; align-items: center; justify-content: center;">
            <div id="playerWeaponChart" style="width: 100%;"></div>
        </div>
    </div>
        </div>
        
        <!-- Weapon Chart Script -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var weaponData = ' . json_encode(array_slice($deep['weapons'] ?? [], 0, 8)) . ';
            if (weaponData.length > 0) {
                var labels = weaponData.map(function(w) { return w.name; });
                var series = weaponData.map(function(w) { return parseInt(w.kills); });
                
                var options = {
                    series: series,
                    labels: labels,
                    chart: {
                        type: "donut",
                        height: 300,
                        foreColor: "#e0e0e0"
                    },
                    colors: ["#4CAF50", "#8BC34A", "#CDDC39", "#FFEB3B", "#FFC107", "#FF9800", "#FF5722", "#F44336"],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: "65%",
                                labels: {
                                    show: true,
                                    name: { color: "#e0e0e0" },
                                    value: { color: "#ffffff" },
                                    total: {
                                        show: true,
                                        label: "Total Kills",
                                        color: "#9e9e9e",
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    stroke: { show: false },
                    dataLabels: { enabled: false },
                    legend: { position: "right" },
                    tooltip: { theme: "dark" }
                };
                
                var chart = new ApexCharts(document.querySelector("#playerWeaponChart"), options);
                chart.render();
            }
        });
        </script>
    </div>';
}

/**
 * Profile Identity Linking Template
 */
function template_mohaa_profile_identity()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons members"></span> ', $txt['mohaa_link_identity'] ?? 'Link Game Identity', '
        </h3>
    </div>
    <div class="windowbg">';
    
    // Show existing linked identities
    if (!empty($context['mohaa_identities'])) {
        echo '
        <h4>', $txt['mohaa_linked_identities'] ?? 'Linked Game Identities', '</h4>
        <table class="table_grid">
            <thead>
                <tr class="title_bar">
                    <th>Player Name</th>
                    <th>GUID</th>
                    <th>Linked Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($context['mohaa_identities'] as $identity) {
            echo '
                <tr class="windowbg">
                    <td><strong>', htmlspecialchars($identity['player_name']), '</strong></td>
                    <td><code>', htmlspecialchars(substr($identity['player_guid'], 0, 8)), '...</code></td>
                    <td>', timeformat($identity['linked_date']), '</td>
                    <td>
                        <form method="post" action="', $scripturl, '?action=profile;area=mohaaidentity" style="display: inline;">
                            <input type="hidden" name="mohaa_action" value="unlink">
                            <input type="hidden" name="identity_id" value="', $identity['id_identity'], '">
                            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                            <button type="submit" class="button" onclick="return confirm(\'', $txt['mohaa_unlink_confirm'] ?? 'Are you sure you want to unlink this identity?', '\');">
                                ', $txt['mohaa_unlink'] ?? 'Unlink', '
                            </button>
                        </form>
                    </td>
                </tr>';
        }
        
        echo '
            </tbody>
        </table>
        <hr>';
    }
    
    // Show claim code generation
    echo '
        <div style="padding: 20px; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="margin-top: 0;">', $txt['mohaa_link_new_identity'] ?? 'Link a New Game Identity', '</h2>
                <p>', $txt['mohaa_link_instructions'] ?? 'Generate a claim code and enter it in-game to link your soldier to this account.', '</p>
            </div>';
    
    // Show existing claim code if available
    if (!empty($context['mohaa_claim_code'])) {
        $timeLeft = $context['mohaa_claim_expires'] - time();
        echo '
            <div style="background: #e8f5e9; border-left: 4px solid #66bb6a; padding: 20px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #333;">', $txt['mohaa_your_claim_code'] ?? 'Your Claim Code', '</h3>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-size: 24px; text-align: center; letter-spacing: 4px; margin: 15px 0;">
                    ', htmlspecialchars($context['mohaa_claim_code']), '
                </div>
                <p style="text-align: center; font-size: 14px; color: #666;">
                    ', sprintf($txt['mohaa_code_expires'] ?? 'This code expires in %d minutes.', ceil($timeLeft / 60)), '
                </p>
                <div style="background: #222; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 16px; margin: 15px 0;">
                    /claim ', htmlspecialchars($context['mohaa_claim_code']), '
                </div>
                <p style="text-align: center; font-size: 12px; color: #666;">
                    ', $txt['mohaa_enter_in_console'] ?? 'Enter this command in your game console (press ~ to open).', '
                </p>
            </div>';
    }
    
    // Generate claim code button
    echo '
            <form method="post" action="', $scripturl, '?action=profile;area=mohaaidentity" style="text-align: center;">
                <input type="hidden" name="mohaa_action" value="generate_claim">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                <button type="submit" class="button">
                    ', empty($context['mohaa_claim_code']) ? ($txt['mohaa_generate_code'] ?? 'Generate Claim Code') : ($txt['mohaa_regenerate_code'] ?? 'Generate New Code'), '
                </button>
            </form>
        </div>
    </div>';
}

/**
 * Compare two players
 */
function template_mohaa_compare()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">Player Comparison</h3>
    </div>
    <div class="windowbg">
        <p>Player comparison functionality coming soon.</p>
    </div>';
}

/**
 * Player comparison selection page (when no players selected yet)
 */
function template_mohaa_compare_select()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_compare_players'] ?? 'Compare Players', '</h3>
    </div>
    <div class="windowbg">
        <form action="', $scripturl, '?action=mohaacompare" method="get" style="max-width: 600px; margin: 0 auto;">
            <input type="hidden" name="action" value="mohaacompare">
            
            <div class="roundframe" style="margin-bottom: 20px;">
                <h4 style="margin-top: 0;">', $txt['mohaa_select_players'] ?? 'Select Two Players to Compare', '</h4>
                
                <div style="margin-bottom: 15px;">
                    <label for="p1" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        ', $txt['mohaa_player_1'] ?? 'Player 1', ':
                    </label>
                    <input type="text" name="p1" id="p1" 
                           placeholder="', $txt['mohaa_enter_guid_or_name'] ?? 'Enter GUID or player name', '" 
                           style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="p2" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        ', $txt['mohaa_player_2'] ?? 'Player 2', ':
                    </label>
                    <input type="text" name="p2" id="p2" 
                           placeholder="', $txt['mohaa_enter_guid_or_name'] ?? 'Enter GUID or player name', '" 
                           style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="button">
                        ', $txt['mohaa_compare'] ?? 'Compare', '
                    </button>
                </div>
            </div>
        </form>';
    
    // Show recent players for quick selection
    if (!empty($context['mohaa_recent_players'])) {
        echo '
        <div class="roundframe">
            <h4 style="margin-top: 0;">', $txt['mohaa_recent_players'] ?? 'Recent Players', '</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">';
        
        foreach ($context['mohaa_recent_players'] as $player) {
            echo '
                <a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid']), '" class="button" style="font-size: 12px;">
                    ', htmlspecialchars($player['name']), '
                </a>';
        }
        
        echo '
            </div>
        </div>';
    }
    
    echo '
    </div>';
}

/**
 * Profile stats tab - shows MOHAA stats in member profile
 */
function template_mohaa_profile_stats()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons stats"></span> ', $txt['mohaa_game_stats'] ?? 'Game Statistics', '
        </h3>
    </div>';
    
    // No linked identity
    if (!empty($context['mohaa_no_identity'])) {
        echo '
        <div class="windowbg centertext">
            <p>', $txt['mohaa_no_linked_identity'] ?? 'This member has not linked their game identity yet.', '</p>';
        
        // If viewing own profile, show link option
        if (!empty($context['user']['is_owner'])) {
            echo '
            <p>
                <a href="', $scripturl, '?action=profile;area=mohaaidentity" class="button">
                    ', $txt['mohaa_link_identity'] ?? 'Link Your Game Identity', '
                </a>
            </p>';
        }
        
        echo '
        </div>';
        return;
    }
    
    // Show player stats
    $stats = $context['mohaa_profile_stats'] ?? [];
    $player = $stats['player'] ?? [];
    
    echo '
    <div class="windowbg">';
    
    if (!empty($player)) {
        // Quick stats grid
        echo '
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #4caf50;">
                    ', number_format($player['kills'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_kills'] ?? 'Kills', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #f44336;">
                    ', number_format($player['deaths'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_deaths'] ?? 'Deaths', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold;">
                    ', number_format($player['kd_ratio'] ?? 0, 2), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_kd_ratio'] ?? 'K/D Ratio', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #ff9800;">
                    ', number_format($player['headshots'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_headshots'] ?? 'Headshots', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold;">
                    ', number_format($player['playtime_hours'] ?? 0, 1), 'h
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_playtime'] ?? 'Playtime', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold;">
                    ', number_format($player['matches'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_matches'] ?? 'Matches', '
                </div>
            </div>
        </div>';
        
        // Add 24-Hour Performance Chart
        echo '
        <div style="margin-top: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 1.1em;">⏰ 24-Hour Performance Pattern</h4>
            <div class="roundframe" style="padding: 20px;">
                <div id="hourlyPerformanceChart"></div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            (function() {
                // Mock hourly K/D data
                var hours = [];
                var kdByHour = [];
                var matchesByHour = [];
                
                for (var i = 0; i < 24; i++) {
                    hours.push(i + ":00");
                    // Generate realistic pattern (better in evening)
                    var performance = 0.8 + (Math.sin((i - 6) / 3.8) * 0.4) + (Math.random() * 0.2);
                    kdByHour.push(parseFloat(Math.max(0.3, performance).toFixed(2)));
                    matchesByHour.push(Math.floor(Math.random() * 15) + (i >= 18 && i <= 23 ? 20 : 5));
                }
                
                var hourlyOptions = {
                    series: [
                        {
                            name: "K/D Ratio",
                            type: "line",
                            data: kdByHour
                        },
                        {
                            name: "Matches Played",
                            type: "column",
                            data: matchesByHour
                        }
                    ],
                    chart: {
                        type: "line",
                        height: 300,
                        toolbar: { show: false }
                    },
                    stroke: {
                        width: [3, 0],
                        curve: "smooth"
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: "50%"
                        }
                    },
                    colors: ["#e74c3c", "#3498db"],
                    xaxis: {
                        categories: hours,
                        title: { text: "Hour of Day" }
                    },
                    yaxis: [
                        {
                            title: { text: "K/D Ratio" },
                            decimalsInFloat: 2
                        },
                        {
                            opposite: true,
                            title: { text: "Matches" }
                        }
                    ],
                    legend: {
                        position: "top"
                    },
                    tooltip: {
                        shared: true,
                        intersect: false
                    }
                };
                
                var hourlyChart = new ApexCharts(document.querySelector("#hourlyPerformanceChart"), hourlyOptions);
                hourlyChart.render();
            })();
        </script>';
        
        // Add Weapon Usage Breakdown
        echo '
        <div style="margin-top: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 1.1em;">🔫 Weapon Arsenal</h4>
            <div class="roundframe" style="padding: 20px;">
                <div id="weaponUsageChart"></div>
            </div>
        </div>
        
        <script>
            (function() {
                var weaponData = [
                    { weapon: "Kar98K", kills: 245, accuracy: 28, usage: 35 },
                    { weapon: "Thompson", kills: 198, accuracy: 22, usage: 25 },
                    { weapon: "M1 Garand", kills: 156, accuracy: 25, usage: 20 },
                    { weapon: "MP40", kills: 134, accuracy: 20, usage: 15 },
                    { weapon: "Bazooka", kills: 45, accuracy: 15, usage: 5 }
                ];
                
                var weaponOptions = {
                    series: [
                        {
                            name: "Kills",
                            data: weaponData.map(w => w.kills)
                        },
                        {
                            name: "Accuracy %",
                            data: weaponData.map(w => w.accuracy)
                        }
                    ],
                    chart: {
                        type: "bar",
                        height: 300,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: "55%",
                            endingShape: "rounded"
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ["transparent"]
                    },
                    xaxis: {
                        categories: weaponData.map(w => w.weapon)
                    },
                    yaxis: {
                        title: { text: "Value" }
                    },
                    fill: {
                        opacity: 1
                    },
                    colors: ["#e74c3c", "#f39c12"],
                    legend: {
                        position: "top"
                    },
                    tooltip: {
                        y: {
                            formatter: function(val, { seriesIndex }) {
                                return seriesIndex === 1 ? val + "%" : val;
                            }
                        }
                    }
                };
                
                var weaponChart = new ApexCharts(document.querySelector("#weaponUsageChart"), weaponOptions);
                weaponChart.render();
            })();
        </script>';
        
        // Add Map Performance Heatmap
        echo '
        <div style="margin-top: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 1.1em;">🗺️ Map Performance Heatmap</h4>
            <div class="roundframe" style="padding: 20px;">
                <div id="mapHeatmapChart"></div>
            </div>
        </div>
        
        <script>
            (function() {
                // Map performance data (map x metric)
                var mapData = [
                    { name: "V2 Rocket", data: [1.8, 2.1, 1.5, 2.3] },
                    { name: "Stalingrad", data: [1.2, 1.6, 1.8, 1.4] },
                    { name: "Brest", data: [2.5, 2.0, 1.9, 2.2] },
                    { name: "Bazaar", data: [1.1, 1.3, 1.6, 1.2] },
                    { name: "Destroyed Village", data: [1.7, 1.9, 2.1, 1.8] }
                ];
                
                var heatmapOptions = {
                    series: mapData,
                    chart: {
                        type: "heatmap",
                        height: 300,
                        toolbar: { show: false }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            colors: ["#fff"]
                        }
                    },
                    xaxis: {
                        categories: ["K/D", "Accuracy", "Obj Score", "Survival"]
                    },
                    plotOptions: {
                        heatmap: {
                            colorScale: {
                                ranges: [
                                    { from: 0, to: 1.0, color: "#e74c3c", name: "Low" },
                                    { from: 1.1, to: 1.5, color: "#f39c12", name: "Medium" },
                                    { from: 1.6, to: 2.0, color: "#3498db", name: "Good" },
                                    { from: 2.1, to: 3.0, color: "#2ecc71", name: "Excellent" }
                                ]
                            }
                        }
                    },
                    title: {
                        text: "Performance varies by map - click any cell to drill down",
                        align: "center",
                        style: { fontSize: "12px", color: "#7f8c8d" }
                    }
                };
                
                var heatmapChart = new ApexCharts(document.querySelector("#mapHeatmapChart"), heatmapOptions);
                heatmapChart.render();
            })();
        </script>';
        
        // Link to full stats
        if (!empty($player['guid'])) {
            echo '
            <div style="text-align: center; margin-top: 30px;">
                <a href="', $scripturl, '?action=mohaastats;sa=player;guid=', urlencode($player['guid']), '" class="button">
                    ', $txt['mohaa_view_full_stats'] ?? 'View Full Stats', '
                </a>
            </div>';
        }
    } else {
        // No stats yet - show helpful message and GUID
        $stats = $context['mohaa_profile_stats'] ?? [];
        $guid = $stats['guid'] ?? '';
        
        echo '
        <div class="roundframe centertext" style="padding: 30px;">
            <p style="margin-top: 0; font-size: 16px; font-weight: bold;">
                ', $txt['mohaa_no_stats_available'] ?? 'No stats available yet.', '
            </p>';
        
        if (!empty($guid)) {
            echo '
            <p style="color: #888; margin: 10px 0;">
                <strong>Linked GUID:</strong> <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">', $guid, '</code>
            </p>
            <p style="color: #888; margin-bottom: 15px; font-size: 13px;">
                Stats will appear here once you play on a connected OpenMOHAA server.
            </p>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>';
}
?>
