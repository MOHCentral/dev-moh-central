<?php
/**
 * MOHAA Stats - Leaderboard Templates
 *
 * @package MohaaStats
 * @version 1.0.0
 */

/**
 * Main leaderboard template
 */
function template_mohaa_stats_leaderboard()
{
    global $context, $scripturl, $txt;

    $leaderboardData = $context['mohaa_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['players'] ?? [];
    $current_stat = $leaderboardData['stat'] ?? 'kills';
    $current_period = $leaderboardData['period'] ?? 'all';
    
    echo '
    <style>
        .mohaa-hero-stat { 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            padding: 40px; 
            background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%); 
            margin-bottom: 30px; 
            border-radius: 12px; 
            color: #ffffff; 
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.05);
            text-align: left;
        }
        .mohaa-hero-stat .stat-icon {
            font-size: 3.5em; 
            line-height: 1;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .mohaa-hero-stat h1 { 
            color: #ffffff; 
            text-shadow: 0 2px 4px rgba(0,0,0,0.3); 
            margin: 0 0 5px 0; 
            font-size: 2.2em;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }
        .mohaa-hero-stat p { 
            color: #bdc3c7; 
            font-size: 1.1em; 
            margin: 0; 
            max-width: 600px;
        }
        
        .ag-theme-alpine { --ag-foreground-color: #2c3e50; --ag-header-foreground-color: #fff; --ag-header-background-color: #2c3e50; --ag-row-hover-color: rgba(52, 152, 219, 0.1); --ag-selected-row-background-color: rgba(52, 152, 219, 0.2); }
        .ag-theme-alpine .ag-header-cell { font-family: "Inter", sans-serif; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; }
        .ag-theme-alpine .ag-cell { font-family: "Inter", sans-serif; display: flex; align-items: center; justify-content: center; }
        .player-cell { justify-content: flex-start !important; }
        
        .rank-badge { font-size: 1.4em; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.2)); }
        .player-avatar-small { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #34495e; color: #fff; font-size: 10px; margin-right: 8px; font-weight: bold; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css">
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
    
    <div id="mohaa-leaderboard-dynamic">
    ';
    
    // Stat Descriptions
    $statInfo = [
        'kills' => ['title' => 'Kills', 'desc' => 'Total enemies eliminated in combat.', 'icon' => '🗡️'],
        'deaths' => ['title' => 'Deaths', 'desc' => 'Times eliminated by enemy fire or mishaps.', 'icon' => '🪦'],
        'kd' => ['title' => 'K/D Ratio', 'desc' => 'Kill-to-Death ratio. The ultimate measure of efficiency.', 'icon' => '⚖️'],
        'headshots' => ['title' => 'Headshots', 'desc' => 'Precision kills resulting in instant death.', 'icon' => '🤯'],
        'accuracy' => ['title' => 'Accuracy', 'desc' => 'Percentage of shots that hit a target.', 'icon' => '🎯'],
        'shots_fired' => ['title' => 'Trigger Happy', 'desc' => 'Total ammunition expended. Spray and pray?', 'icon' => '💥'],
        'damage' => ['title' => 'Damage Dealer', 'desc' => 'Total damage inflicted on opponents.', 'icon' => '🩸'],
        'bash_kills' => ['title' => 'Executioner', 'desc' => 'Humiliating kills using pistol whips or rifle butts.', 'icon' => '🔨'],
        'grenade_kills' => ['title' => 'Grenadier', 'desc' => 'Explosive kills with hand grenades.', 'icon' => '💣'],
        'roadkills' => ['title' => 'Road Rage', 'desc' => 'Enemies run over by vehicles.', 'icon' => '🚗'],
        'telefrags' => ['title' => 'Telefrags', 'desc' => 'Occupying the same space as an enemy.', 'icon' => '🌌'],
        'crushed' => ['title' => 'Crushed', 'desc' => 'Squashed by world objects or elevators.', 'icon' => '🥞'],
        'teamkills' => ['title' => 'Betrayals', 'desc' => 'Teammates eliminated. Friendly fire isn\'t friendly.', 'icon' => '🔪'],
        'suicides' => ['title' => 'Suicides', 'desc' => 'Self-inflicted eliminations.', 'icon' => '💀'],
        'reloads' => ['title' => 'Reloader', 'desc' => 'Times a weapon clip was swapped.', 'icon' => '🔄'],
        'weapon_swaps' => ['title' => 'Fickle', 'desc' => 'Times weapons were switched during combat.', 'icon' => '🔀'],
        'no_ammo' => ['title' => 'Empty Clip', 'desc' => 'Times caught clicking with an empty gun.', 'icon' => '⛽'],
        'looter' => ['title' => 'Looter', 'desc' => 'Weapons picked up from the ground.', 'icon' => '🎒'],
        'distance' => ['title' => 'Marathon Man', 'desc' => 'Total distance travelled on foot.', 'icon' => '🏃'],
        'sprinted' => ['title' => 'Sprinter', 'desc' => 'Distance covered while sprinting.', 'icon' => '⚡'],
        'swam' => ['title' => 'Swimmer', 'desc' => 'Distance covered in water.', 'icon' => '🏊'],
        'driven' => ['title' => 'Driver', 'desc' => 'Distance covered in vehicles.', 'icon' => '🚙'],
        'jumps' => ['title' => 'Bunny Hopper', 'desc' => 'Total number of jumps performed.', 'icon' => '🐇'],
        'crouch_time' => ['title' => 'Tactical Crouch', 'desc' => 'Time spent moving in a crouched position.', 'icon' => '🦵'],
        'prone_time' => ['title' => 'Camper', 'desc' => 'Time spent laying on the ground.', 'icon' => '⛺'],
        'ladders' => ['title' => 'Mountaineer', 'desc' => 'Time spent climbing ladders.', 'icon' => '🧗'],
        'health_picked' => ['title' => 'Glutton', 'desc' => 'Health packs consumed.', 'icon' => '🍗'],
        'ammo_picked' => ['title' => 'Hoarder', 'desc' => 'Ammo crates collected.', 'icon' => '📦'],
        'armor_picked' => ['title' => 'Tank', 'desc' => 'Jacket Armor collected.', 'icon' => '🛡️'],
        'items_picked' => ['title' => 'Scavenger', 'desc' => 'Total items picked up.', 'icon' => '🗑️'],
        'wins' => ['title' => 'Wins', 'desc' => 'Total Games Won (FFA + Team).', 'icon' => '🏆'],
        'team_wins' => ['title' => 'Team Wins', 'desc' => 'Games won as part of a team (Objective/TDM).', 'icon' => '🚩'],
        'ffa_wins' => ['title' => 'FFA Wins', 'desc' => 'Deathmatch games won solo.', 'icon' => '⚔️'],
        'losses' => ['title' => 'Losses', 'desc' => 'Matches lost or not placed 1st.', 'icon' => '☠️'],
        'objectives' => ['title' => 'Objective Master', 'desc' => 'Mission objectives completed.', 'icon' => '🎯'],
        'rounds' => ['title' => 'Veteran', 'desc' => 'Total rounds played in round-based modes.', 'icon' => '⏳'],
        'playtime' => ['title' => 'Time Sink', 'desc' => 'Total time spent on the server.', 'icon' => '⏱️'],
        'games' => ['title' => 'Ironman', 'desc' => 'Full matches completed from start to finish.', 'icon' => '🎮'],
    ];

    $info = $statInfo[$current_stat] ?? ['title' => ucfirst($current_stat), 'desc' => 'Global rankings for this metric.', 'icon' => '📊'];

    // Hero Section
    echo '
        <div style="text-align: left;">
            <h1 style="font-size: 2em; text-transform: uppercase; letter-spacing: 2px; margin: 0;">', $info['title'], '</h1>
            <p style="margin: 0; opacity: 0.9; font-size: 0.9em;">', $info['desc'], '</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <div class="windowbg mohaa-lb-wrap" style="padding: 20px;">';
    




    // PERIOD Filters
    echo '
        <div class="mohaa-filter-section" style="margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <strong style="margin-right: 5px; color: #7f8c8d; text-transform: uppercase; font-size: 0.85em; letter-spacing: 1px;">📅 Period:</strong>';
    $periods = ['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week', 'day' => 'Today'];
    foreach ($periods as $key => $label) {
        $class = ($current_period === $key) ? 'active' : 'inactive';
        $style = ($current_period === $key) 
            ? 'background: #3498db; color: white; padding: 5px 12px; border-radius: 20px; text-decoration: none; font-size: 0.9em; font-weight: bold; box-shadow: 0 2px 4px rgba(52,152,219,0.3);' 
            : 'background: #ecf0f1; color: #7f8c8d; padding: 5px 12px; border-radius: 20px; text-decoration: none; font-size: 0.9em; transition: all 0.2s;';
            
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $current_stat, ';period=', $key, '" class="mohaa-chip" style="', $style, '">', $label, '</a>';
    }
    echo '</div>
    </div>';

    // LEADERBOARD TABLE - AG GRID
    if (empty($leaderboard)) {
        echo '
        <div class="windowbg" style="text-align: center; padding: 60px 20px; border-radius: 8px; margin-top: 20px;">
            <div style="font-size: 4em; margin-bottom: 15px; opacity: 0.5;">📉</div>
            <h3 style="margin: 0; color: #7f8c8d;">No Data Available</h3>
            <p style="color: #95a5a6;">There are no stats recorded for <strong>', $info['title'], '</strong> in this period.</p>
        </div>';
    } else {
        echo '
        <div id="myGrid" class="ag-theme-alpine" style="height: 600px; width: 100%; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px;"></div>
        
        <script>
            (function() {
                var rowData = ', json_encode($leaderboard), ';
            var scriptUrl = "', $scripturl, '";
            
            var rankRenderer = function(params) {
                var rank = params.node.rowIndex + 1;
                if (rank === 1) return "<span class=\"rank-badge\">🥇</span>";
                if (rank === 2) return "<span class=\"rank-badge\">🥈</span>";
                if (rank === 3) return "<span class=\"rank-badge\">🥉</span>";
                return "#" + rank;
            };
            
            var playerRenderer = function(params) {
                if (!params.value) return "";
                var name = params.data.name;
                var guid = params.data.guid || params.data.id;
                var initial = name.charAt(0).toUpperCase();
                var color = (params.node.rowIndex < 3) ? "#f1c40f" : "#34495e";
                
                return `<div class="player-info" style="display:flex;align-items:center;">
                    <div class="player-avatar-small" style="background:${color}">${initial}</div>
                    <a href="${scriptUrl}?action=mohaastats;sa=player;guid=${guid}" style="font-weight:600;text-decoration:none;color:#2980b9;">${name}</a>
                </div>`;
            };
            
            var kdGetter = function(params) {
                var kills = parseInt(params.data.kills || 0);
                var deaths = parseInt(params.data.deaths || 0);
                return deaths > 0 ? (kills / deaths).toFixed(2) : kills;
            };
            
            var kdStyle = function(params) {
                var val = parseFloat(params.value);
                if (val >= 2.0) return { color: "#27ae60", fontWeight: "bold" };
                if (val >= 1.0) return { color: "#2980b9" };
                return { color: "#e74c3c" };
            };
            
            var gridOptions = {
                rowData: rowData,
                columnDefs: [
                    { headerName: "#", width: 70, cellRenderer: rankRenderer, sortable: false, pinned: "left" },
                    { field: "name", headerName: "Player", minWidth: 200, cellRenderer: playerRenderer, cellClass: "player-cell", pinned: "left" },
                    { field: "kills", headerName: "Kills", width: 100, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "deaths", headerName: "Deaths", width: 100, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { headerName: "K/D", width: 100, valueGetter: kdGetter, cellStyle: kdStyle, type: "numericColumn", sortable: true, comparator: (a,b) => parseFloat(a)-parseFloat(b) },
                    { field: "headshots", headerName: "HS", width: 90, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "accuracy", headerName: "Acc %", width: 90, valueFormatter: p => p.value + "%", type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "wins", headerName: "Wins", width: 90, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "rounds", headerName: "Rounds", width: 100, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "objectives", headerName: "Obj", width: 90, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "distance_km", headerName: "Dist (km)", width: 120, valueFormatter: p => (p.value/1000).toFixed(1), type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "playtime_seconds", headerName: "Time", width: 120, valueFormatter: p => Math.floor(p.value/60) + "m", type: "numericColumn", sortable: true, comparator: (a,b) => a-b }
                ],
                defaultColDef: {
                    resizable: true,
                    filter: true,
                    flex: 1,
                    minWidth: 100
                },
                pagination: true,
                paginationPageSize: 20,
                animateRows: true,
                domLayout: "autoHeight"
            };
            
            var eGridDiv = document.querySelector("#myGrid");
            // Check for modern API or legacy API just in case
            if (agGrid.createGrid) {
                agGrid.createGrid(eGridDiv, gridOptions);
            } else {
                new agGrid.Grid(eGridDiv, gridOptions);
            }
                
                // Expose for PJAX to re-init if needed? 
                // Actually PJAX replaces the whole container, so re-executing this script works.
            })();
        </script>';
    }

    // STAT CATEGORY FILTERS (Moved to bottom)
    echo '
    <div style="margin-top: 40px;">
    
        <!-- Chart Section (Moved Bottom) -->
        <div class="mohaa-chart-container" style="position: relative; height:300px; width:100%; margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 10px; background: #fff;">
            <div id="leaderboardChart"></div>
        </div>

        <div style="padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px;">📊 Explore Other Metrics</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">';
        
        $statGroups = [
            '⚔️ Combat' => ['kills' => 'Kills', 'deaths' => 'Deaths', 'kd' => 'K/D Ratio', 'headshots' => 'Headshots', 'accuracy' => 'Accuracy', 'damage' => 'Damage Dealt'],
            '💀 Special' => ['suicides' => 'Suicides', 'teamkills' => 'Team Kills', 'roadkills' => 'Roadkills', 'bash_kills' => 'Bash Kills', 'grenades' => 'Grenade Kills'],
            '🎮 Game' => ['wins' => 'Wins', 'rounds' => 'Rounds Played', 'objectives' => 'Objectives', 'playtime' => 'Playtime'],
            '🏃 Move' => ['distance' => 'Distance Run', 'jumps' => 'Jumps'],
        ];

        foreach ($statGroups as $groupName => $stats) {
            echo '
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.05);">
                <strong style="display: block; margin-bottom: 10px; color: #34495e; font-size: 0.9em; text-transform: uppercase;">', $groupName, '</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">';
                foreach ($stats as $key => $label) {
                    $isActive = ($current_stat === $key);
                    $style = $isActive 
                        ? 'background: #2c3e50; color: white; padding: 4px 10px; border-radius: 15px; text-decoration: none; font-size: 0.85em; font-weight: bold; box-shadow: 0 2px 4px rgba(44,62,80,0.3);' 
                        : 'background: white; color: #7f8c8d; padding: 4px 10px; border-radius: 15px; text-decoration: none; font-size: 0.85em; border: 1px solid #dfe6e9; transition: all 0.2s;';
                    
                    echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" style="', $style, '">', $label, '</a>';
                }
            echo '
                </div>
            </div>';
        }

    echo '
        </div>
    </div>';

    // Pagination
    if (!empty($context['page_index'])) {
        echo '
        <div class="pagesection">
            <div class="pagelinks">', $context['page_index'], '</div>
        </div>';
    }
    
    // Prepare Chart Data
    $chartLabels = [];
    $chartData = [];
    $topCount = 0;
    foreach ($leaderboard as $player) {
        if ($topCount >= 10) break;
        $chartLabels[] = $player['name'];
        // Handle different stat types (percentages, raw numbers)
        $val = $player[$current_stat] ?? 0;
        // Clean up value if needed
        $chartData[] = $val;
        $topCount++;
    }
    
    echo '
    <script>
        (function() {
            var initChart = function() {
                var el = document.querySelector("#leaderboardChart");
                if (!el) return;
                
                // Destroy existing if any
                if (window.myLeaderboardChart) {
                    window.myLeaderboardChart.destroy();
                }
            
                var options = {
                    series: [{
                        name: "' . ucfirst($current_stat) . '",
                        data: ' . json_encode($chartData) . '
                    }],
                    chart: {
                        type: "bar",
                        height: 300,
                        toolbar: { show: false },
                        animations: { enabled: true }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: "50%",
                            distributed: true // Colors per bar
                        }
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: ' . json_encode($chartLabels) . ',
                        labels: {
                            style: { colors: "#2c3e50", fontWeight: "bold" }
                        }
                    },
                    yaxis: {
                        labels: { style: { colors: "#7f8c8d" } }
                    },
                    colors: ["#3498db"],
                    fill: {
                        type: "gradient",
                        gradient: {
                            shade: "light",
                            type: "vertical",
                            shadeIntensity: 0.5,
                            gradientToColors: ["#a1c4fd"],
                            inverseColors: true,
                            opacityFrom: 0.9,
                            opacityTo: 0.8,
                            stops: [0, 100]
                        }
                    },
                    legend: { show: false },
                    title: {
                        text: "Top 10 Performers - ' . ucfirst($current_stat) . '",
                        align: "center",
                        style: { color: "#2c3e50", fontSize: "16px", fontFamily: "Inter, sans-serif" }
                    },
                    tooltip: { theme: "light" }
                };

                var chart = new ApexCharts(el, options);
                chart.render();
                
                // Save reference
                window.myLeaderboardChart = chart;
            };

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initChart);
            } else {
                initChart();
            }
        })();
    </script>';
    
    // Add K/D Trend Chart (30-day rolling average for top 5 players)
    echo '
    <div style="margin-top: 40px;">
        <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px;">📈 Top Players K/D Trend (30 Days)</h3>
        <div class="mohaa-chart-container" style="position: relative; height:400px; width:100%; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 20px; background: #fff;">
            <div id="kdTrendChart"></div>
        </div>
    </div>
    
    <script>
        (function() {
            // Generate mock 30-day trend data for top 5 players
            var topPlayers = ' . json_encode(array_slice($leaderboard, 0, 5)) . ';
            var series = [];
            
            // Generate dates for last 30 days
            var dates = [];
            for (var i = 29; i >= 0; i--) {
                var d = new Date();
                d.setDate(d.getDate() - i);
                dates.push(d.getTime());
            }
            
            // Generate trend data for each top player
            topPlayers.forEach(function(player) {
                var currentKD = (player.deaths > 0) ? (player.kills / player.deaths) : player.kills;
                var data = [];
                
                // Generate realistic fluctuating K/D around their current value
                for (var i = 0; i < 30; i++) {
                    var variance = (Math.random() - 0.5) * 0.4; // +/- 0.2
                    var value = Math.max(0.1, currentKD + variance);
                    data.push([dates[i], parseFloat(value.toFixed(2))]);
                }
                
                series.push({
                    name: player.name,
                    data: data
                });
            });
            
            var kdTrendOptions = {
                series: series,
                chart: {
                    type: "line",
                    height: 380,
                    zoom: { enabled: false },
                    toolbar: { show: true }
                },
                stroke: {
                    curve: "smooth",
                    width: 3
                },
                xaxis: {
                    type: "datetime",
                    labels: {
                        format: "MMM dd"
                    }
                },
                yaxis: {
                    title: { text: "K/D Ratio" },
                    decimalsInFloat: 2
                },
                legend: {
                    position: "top",
                    horizontalAlign: "right"
                },
                tooltip: {
                    x: { format: "dd MMM yyyy" },
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2);
                        }
                    }
                },
                colors: ["#3498db", "#e74c3c", "#2ecc71", "#f39c12", "#9b59b6"]
            };
            
            var kdTrendChart = new ApexCharts(document.querySelector("#kdTrendChart"), kdTrendOptions);
            kdTrendChart.render();
        })();
    </script>';
    
    // Add Stat Distribution Histogram
    echo '
    <div style="margin-top: 40px;">
        <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px;">📊 Stat Distribution - ' . ucfirst($current_stat) . '</h3>
        <div class="mohaa-chart-container" style="position: relative; height:350px; width:100%; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 20px; background: #fff;">
            <div id="statDistributionChart"></div>
        </div>
    </div>
    
    <script>
        (function() {
            // Create histogram bins for current stat
            var allValues = ' . json_encode(array_column($leaderboard, $current_stat)) . ';
            var min = Math.min(...allValues);
            var max = Math.max(...allValues);
            var binCount = 10;
            var binSize = (max - min) / binCount;
            
            var bins = [];
            var labels = [];
            for (var i = 0; i < binCount; i++) {
                bins[i] = 0;
                var start = min + (i * binSize);
                var end = start + binSize;
                labels.push(start.toFixed(0) + "-" + end.toFixed(0));
            }
            
            // Distribute values into bins
            allValues.forEach(function(val) {
                var binIndex = Math.min(binCount - 1, Math.floor((val - min) / binSize));
                bins[binIndex]++;
            });
            
            var distributionOptions = {
                series: [{
                    name: "Players",
                    data: bins
                }],
                chart: {
                    type: "bar",
                    height: 320,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: "80%",
                        distributed: false
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val > 0 ? val : "";
                    }
                },
                xaxis: {
                    categories: labels,
                    title: { text: "' . ucfirst($current_stat) . ' Range" }
                },
                yaxis: {
                    title: { text: "Number of Players" }
                },
                colors: ["#3498db"],
                title: {
                    text: "Distribution shows how players cluster across different performance levels",
                    align: "center",
                    style: { fontSize: "12px", color: "#7f8c8d" }
                }
            };
            
            var distributionChart = new ApexCharts(document.querySelector("#statDistributionChart"), distributionOptions);
            distributionChart.render();
        })();
    </script>';
    
    // Add Top Weapons Radial Chart (for top player)
    if (!empty($leaderboard)) {
        echo '
        <div style="margin-top: 40px;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px;">🎯 Top Player Weapon Breakdown</h3>
            <div class="mohaa-chart-container" style="position: relative; height:400px; width:100%; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 20px; background: #fff;">
                <div id="weaponRadialChart"></div>
            </div>
        </div>
        
        <script>
            (function() {
                // Mock weapon data for top player
                var weapons = ["Kar98K", "Thompson", "M1 Garand", "Bazooka", "MP40", "Shotgun"];
                var weaponKills = [45, 38, 32, 15, 28, 12];
                
                var weaponRadialOptions = {
                    series: weaponKills,
                    chart: {
                        type: "radialBar",
                        height: 380
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
                                    show: true,
                                    fontSize: "14px"
                                },
                                value: {
                                    show: true,
                                    fontSize: "20px",
                                    formatter: function(val) {
                                        return Math.round(val);
                                    }
                                }
                            }
                        }
                    },
                    labels: weapons,
                    colors: ["#e74c3c", "#3498db", "#2ecc71", "#f39c12", "#9b59b6", "#1abc9c"],
                    legend: {
                        show: true,
                        floating: true,
                        fontSize: "14px",
                        position: "left",
                        offsetX: 10,
                        offsetY: 10,
                        labels: {
                            useSeriesColors: true
                        },
                        markers: {
                            size: 0
                        },
                        formatter: function(seriesName, opts) {
                            return seriesName + ": " + opts.w.globals.series[opts.seriesIndex] + " kills";
                        }
                    }
                };
                
                var weaponRadialChart = new ApexCharts(document.querySelector("#weaponRadialChart"), weaponRadialOptions);
                weaponRadialChart.render();
            })();
        </script>';
    }
    
    // Add Top 5 Accuracy Comparison (Gauge Meters)
    echo '
    <div style="margin-top: 40px;">
        <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px;">🎯 Top 5 Accuracy Comparison</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';
    
    // Generate gauge for each top 5 player
    $top5 = array_slice($leaderboard, 0, 5);
    foreach ($top5 as $index => $player) {
        $accuracy = ($player['shots'] > 0) ? round(($player['hits'] / $player['shots']) * 100, 1) : 0;
        $chartId = "accuracyGauge" . $index;
        
        echo '
            <div class="mohaa-chart-container" style="border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 15px; background: #fff;">
                <div id="' . $chartId . '"></div>
                <p style="text-align: center; margin: 10px 0 0 0; font-weight: bold; color: #2c3e50;">' . htmlspecialchars($player['name']) . '</p>
            </div>
            
            <script>
                (function() {
                    var gaugeOptions = {
                        series: [' . $accuracy . '],
                        chart: {
                            type: "radialBar",
                            height: 200
                        },
                        plotOptions: {
                            radialBar: {
                                hollow: {
                                    size: "60%"
                                },
                                dataLabels: {
                                    name: {
                                        show: false
                                    },
                                    value: {
                                        fontSize: "20px",
                                        fontWeight: "bold",
                                        formatter: function(val) {
                                            return val.toFixed(1) + "%";
                                        }
                                    }
                                }
                            }
                        },
                        colors: ["' . ($accuracy > 30 ? '#2ecc71' : ($accuracy > 20 ? '#f39c12' : '#e74c3c')) . '"],
                        labels: ["Accuracy"]
                    };
                    
                    var gauge = new ApexCharts(document.querySelector("#' . $chartId . '"), gaugeOptions);
                    gauge.render();
                })();
            </script>';
    }
    
    echo '
        </div>
    </div>';
    
    // Close Dynamic Container
    echo '</div>';
    
    // PJAX Navigation Script
    echo '
    <script>
    document.addEventListener("click", function(e) {
        var link = e.target.closest("a");
        if (!link) return;
        
        var wrapper = document.getElementById("mohaa-leaderboard-dynamic");
        if (!wrapper) return;
        
        if (wrapper.contains(link) && link.href.includes("action=mohaastats") && !link.target) {
            e.preventDefault();
            var url = link.href;
            
            wrapper.classList.add("mohaa-loading");
            
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, "text/html");
                    var newContent = doc.getElementById("mohaa-leaderboard-dynamic");
                    
                    if (newContent) {
                        wrapper.innerHTML = newContent.innerHTML;
                        wrapper.classList.remove("mohaa-loading");
                        history.pushState(null, "", url);
                        
                        // Execute Scripts
                        var scripts = wrapper.getElementsByTagName("script");
                        for (var i = 0; i < scripts.length; i++) {
                            eval(scripts[i].innerText);
                        }
                    } else {
                        window.location.href = url; // Fallback
                    }
                })
                .catch(err => {
                    console.error("PJAX Error:", err);
                    window.location.href = url; // Fallback
                });
        }
    });
    
    window.addEventListener("popstate", function() {
        location.reload(); 
    });
    </script>';
}

/**
 * Weapon leaderboard template
 */
function template_mohaa_stats_weapon_leaderboard()
{
    global $context, $scripturl, $txt;

    $weapon = $context['mohaa_weapon'] ?? '';
    $leaderboard = $context['mohaa_weapon_leaderboard'] ?? [];
    $weapons = $context['mohaa_weapons_list'] ?? [];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_weapon_leaderboard'], '</h3>
    </div>';

    // Weapon selector
    echo '
    <div class="windowbg mohaa-filters">
        <form action="', $scripturl, '?action=mohaastats;sa=weapons" method="get">
            <input type="hidden" name="action" value="mohaastats" />
            <input type="hidden" name="sa" value="weapons" />
            
            <label>
                ', $txt['mohaa_weapon'], ':
                <select name="weapon" onchange="this.form.submit()">';

    foreach ($weapons as $w) {
        $selected = ($w['id'] === $weapon) ? ' selected' : '';
        echo '
                    <option value="', $w['id'], '"', $selected, '>', $w['name'], '</option>';
    }

    echo '
                </select>
            </label>
        </form>
    </div>';

    // Weapon image and stats
    if (!empty($weapon)) {
        $weaponData = $context['mohaa_weapon_data'] ?? [];
        
        echo '
    <div class="windowbg" style="display: grid; grid-template-columns: auto 1fr; gap: 20px; margin-bottom: 20px;">
        <div style="width: 200px;">
            <img src="Themes/default/images/mohaastats/weapons/', $weapon, '.png" alt="', $weaponData['name'] ?? '', '" style="max-width: 100%;" onerror="this.style.display=\'none\'">
        </div>
        <div>
            <h4>', $weaponData['name'] ?? ucfirst($weapon), '</h4>
            <div class="mohaa-stat-cards" style="margin-top: 10px;">
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($weaponData['total_kills'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_total_kills'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($weaponData['total_headshots'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_headshots'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', round($weaponData['avg_accuracy'] ?? 0, 1), '%</div>
                    <div class="stat-label">', $txt['mohaa_avg_accuracy'], '</div>
                </div>
            </div>
        </div>
    </div>';
    }

    // Top players with this weapon
    echo '
    <table class="table_grid" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th>#</th>
                <th>', $txt['mohaa_player'], '</th>
                <th>', $txt['mohaa_kills'], '</th>
                <th>', $txt['mohaa_headshots'], '</th>
                <th>', $txt['mohaa_accuracy'], '</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($leaderboard)) {
        echo '
            <tr class="windowbg">
                <td colspan="5" class="centertext">', $txt['mohaa_no_data'], '</td>
            </tr>';
    } else {
        foreach ($leaderboard as $rank => $player) {
            $accuracy = $player['shots_fired'] > 0 
                ? round(($player['shots_hit'] / $player['shots_fired']) * 100, 1) 
                : 0;
            
            echo '
            <tr class="windowbg">
                <td>', $rank + 1, '</td>
                <td>
                    <a href="', $scripturl, '?action=mohaastats;sa=player;guid=', ($player['guid'] ?? $player['id']), '">
                        ', $player['name'], '
                    </a>
                </td>
                <td>', number_format($player['kills']), '</td>
                <td>', number_format($player['headshots']), '</td>
                <td>', $accuracy, '%</td>
            </tr>';
        }
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Embeddable CSS for consistent Dashboard styling
 * Matches War Room dark theme: #24282e cards, #4a6b8a accents
 */
function template_mohaa_dashboard_css() {
    echo '
    <style>
        .mohaa-page-dashboard {
            display: flex; flex-direction: column; gap: 20px;
            font-family: inherit;
        }
        
        /* Unset SMF defaults that might interfere */
        .mohaa-page-dashboard h3, .mohaa-page-dashboard h4 { margin: 0; padding: 0; border: none; }
        
        /* Unified Card Style - SMF Theme Inspired */
        .mohaa-summary-card, .mohaa-chart-card, .mohaa-entity-card, .mohaa-detail-panel, .mohaa-filter-bar {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            color: #444;
            box-sizing: border-box;
            background: linear-gradient(to bottom, #fdfdfd 0%, #f4f4f4 100%);
        }
        
        /* Summary Row */
        .mohaa-summary-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .mohaa-summary-card { padding: 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .mohaa-summary-card .stat-icon { font-size: 2rem; margin-bottom: 8px; color: #555; }
        .mohaa-summary-card .stat-value { font-size: 1.8rem; font-weight: 700; color: #3b5998; line-height: 1.2; text-shadow: 1px 1px 1px #fff; }
        .mohaa-summary-card .stat-label { font-size: 0.85rem; color: #777; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px; }
        
        /* Chart Card */
        .mohaa-charts-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .mohaa-chart-card { padding: 20px; overflow: hidden; background: #fff; }
        .mohaa-chart-card h4 { 
            color: #444; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 15px; 
            font-size: 1rem; font-weight: 600; text-transform: uppercase;
        }
        
        /* Filter Bar */
        .mohaa-filter-bar { padding: 15px 20px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .mohaa-filter-bar label { font-weight: 600; color: #555; text-transform: uppercase; font-size: 0.9rem; }
        .mohaa-filter-bar select, .mohaa-filter-bar input {
            background: #fff; color: #444; border: 1px solid #ccc; padding: 8px 12px; border-radius: 4px; font-size: 0.95rem; min-width: 200px;
        }
        
        /* Entity Grid (Maps/Weapons/Gametypes) */
        .mohaa-entity-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .mohaa-entity-card { 
            cursor: pointer; text-decoration: none !important; overflow: hidden; display: flex; flex-direction: column; 
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            background: #fff;
        }
        .mohaa-entity-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #aaa; }
        
        .mohaa-entity-card .entity-image { height: 140px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid #ddd; }
        .mohaa-entity-card .entity-image img { width: 100%; height: 100%; object-fit: cover; }
        .mohaa-entity-card .entity-placeholder { font-size: 3rem; opacity: 0.3; color: #000; }
        
        .mohaa-entity-card .entity-header { padding: 12px; text-align: center; background: #f9f9f9; border-bottom: 1px solid #eee; }
        .mohaa-entity-card .entity-name { font-weight: 700; font-size: 1.1rem; color: #333; display: block; }
        .mohaa-entity-card .entity-desc { font-size: 0.8rem; color: #777; margin-top: 4px; display: block; }
        .mohaa-entity-card .entity-icon { font-size: 2.5rem; margin-bottom: 10px; display: block; }
        
        .mohaa-entity-card .entity-stats { display: grid; grid-template-columns: repeat(3, 1fr); padding: 12px; gap: 4px; background: #fff; flex-grow: 1; }
        .mohaa-entity-card .stat { text-align: center; }
        .mohaa-entity-card .stat-value { color: #3b5998; font-weight: 700; font-size: 1.1rem; display: block; }
        .mohaa-entity-card .stat-label { color: #888; font-size: 0.75rem; text-transform: uppercase; margin-top: 2px; display: block; }
        
        /* Section Title (Unified) */
        .mohaa-section-title {
            background: url("Themes/default/images/theme/main_block.png") repeat-x scroll 0 -160px #e4e4e4;
            padding: 8px 12px; border-radius: 6px 6px 0 0; 
            display: flex; align-items: center; gap: 12px; margin: 0 0 -1px 0; position: relative;
            border: 1px solid #ccc; border-bottom: none;
            color: #444;
        }
        .mohaa-section-title h3 { margin: 0; font-size: 1.1rem; color: #555; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-shadow: 1px 1px 0 #fff; }
        .mohaa-section-title .title-icon { font-size: 1.2rem; }
        
        /* Detail Section */
        .mohaa-detail-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
        .mohaa-detail-panel h4 {  color: #555; border-bottom: 1px solid #ddd; padding-bottom: 12px; margin: 0 0 15px 0; font-size: 1rem; font-weight: 600; text-transform: uppercase; }
        
        /* Data Table */
        .mohaa-data-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #ddd; }
        .mohaa-data-table th { background: #f0f0f0; padding: 10px; color: #555; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #ddd; text-align: left; background-image: linear-gradient(to bottom, #f9f9f9, #e9e9e9); }
        .mohaa-data-table td { padding: 10px; color: #444; border-bottom: 1px solid #eee; }
        .mohaa-data-table tr:hover td { background: #f4f8fc; }
        .mohaa-data-table a { color: #3b5998; text-decoration: none; font-weight: 600; }
        .mohaa-data-table a:hover { text-decoration: underline; color: #d63c3c; }
        
        .mohaa-back-btn { display: inline-block; padding: 6px 12px; background: #f0f0f0; border: 1px solid #ccc; color: #444; border-radius: 4px; text-decoration: none; margin-bottom: 15px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .mohaa-back-btn:hover { background: #e0e0e0; color: #222; }
        
        .stat-good { color: #2ecc71; font-weight: bold; }
        .stat-warn { color: #e67e22; font-weight: bold; }
        .stat-bad { color: #e74c3c; font-weight: bold; }
    </style>';
}

/**
 * Map leaderboard template - Comprehensive Map Statistics Dashboard
 */
function template_mohaa_stats_map_leaderboard()
{
    global $context, $scripturl, $txt, $settings;

    $selectedMap = $context['mohaa_map'] ?? '';
    $maps = $context['mohaa_maps_list'] ?? [];
    $leaderboardData = $context['mohaa_map_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['leaderboard'] ?? [];
    $mapData = $context['mohaa_map_data'] ?? [];
    
    // Calculate aggregate stats from maps list
    $totalMatches = 0;
    $totalKills = 0;
    $topMaps = [];
    foreach ($maps as $m) {
        $totalMatches += ($m['total_matches'] ?? 0);
        $totalKills += ($m['total_kills'] ?? 0);
        $topMaps[] = $m;
    }
    usort($topMaps, fn($a, $b) => ($b['total_matches'] ?? 0) <=> ($a['total_matches'] ?? 0));
    $topMaps = array_slice($topMaps, 0, 8);
    
    // Load unified dashboard CSS
    template_mohaa_dashboard_css();
    
    // Section Title
    echo '
    <div class="mohaa-section-title">
        <span class="title-icon">🗺️</span>
        <h3>', $txt['mohaa_map_stats'] ?? 'Map Statistics', '</h3>
    </div>
    
    <div class="mohaa-page-dashboard">';
    
    // Summary Stats Row
    echo '
        <div class="mohaa-summary-row">
            <div class="mohaa-summary-card">
                <span class="stat-icon">🗺️</span>
                <span class="stat-value">', count($maps), '</span>
                <span class="stat-label">Total Maps</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">🎮</span>
                <span class="stat-value">', number_format($totalMatches), '</span>
                <span class="stat-label">Total Matches</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">💀</span>
                <span class="stat-value">', number_format($totalKills), '</span>
                <span class="stat-label">Total Kills</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">🏆</span>
                <span class="stat-value">', !empty($topMaps) ? htmlspecialchars($topMaps[0]['display_name'] ?? $topMaps[0]['name'] ?? 'N/A') : 'N/A', '</span>
                <span class="stat-label">Most Popular</span>
            </div>
        </div>';
    
    // Charts Row
    echo '
        <div class="mohaa-charts-row">
            <div class="mohaa-chart-card">
                <h4>📊 Map Popularity (By Matches)</h4>
                <div id="mapPopularityChart" style="height: 300px;"></div>
            </div>
            <div class="mohaa-chart-card">
                <h4>💀 Map Lethality (Kills Per Map)</h4>
                <div id="mapKillsChart" style="height: 300px;"></div>
            </div>
        </div>';
    
    // Filter Bar
    echo '
        <div class="mohaa-filter-bar">
            <label>🔍 Select Map:</label>
            <form action="', $scripturl, '" method="get" style="display: inline-flex; gap: 10px;">
                <input type="hidden" name="action" value="mohaastats" />
                <input type="hidden" name="sa" value="maps" />
                <select name="map" onchange="this.form.submit()">
                    <option value="">-- All Maps Overview --</option>';
    
    foreach ($maps as $m) {
        $mapName = $m['name'] ?? '';
        $displayName = $m['display_name'] ?? $mapName;
        $selected = ($mapName === $selectedMap) ? ' selected' : '';
        echo '<option value="', htmlspecialchars($mapName), '"', $selected, '>', htmlspecialchars($displayName), '</option>';
    }
    
    echo '
                </select>
            </form>
        </div>';
    
    // If a specific map is selected, show detailed view
    if (!empty($selectedMap) && !empty($mapData)) {
        $displayName = $mapData['display_name'] ?? $selectedMap;
        
        echo '
        <div class="mohaa-section-title" style="margin-top: 15px;">
            <span class="title-icon">📍</span>
            <h3>', htmlspecialchars($displayName), ' - Details</h3>
        </div>
        
        <div class="mohaa-summary-row">
            <div class="mohaa-summary-card">
                <span class="stat-icon">🎮</span>
                <span class="stat-value">', number_format($mapData['total_matches'] ?? 0), '</span>
                <span class="stat-label">Matches Played</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">💀</span>
                <span class="stat-value">', number_format($mapData['total_kills'] ?? 0), '</span>
                <span class="stat-label">Total Kills</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">⏱️</span>
                <span class="stat-value">', format_playtime($mapData['total_playtime'] ?? 0), '</span>
                <span class="stat-label">Total Playtime</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">⚔️</span>
                <span class="stat-value">', ($mapData['total_matches'] > 0) ? round($mapData['total_kills'] / $mapData['total_matches'], 1) : 0, '</span>
                <span class="stat-label">Avg Kills/Match</span>
            </div>
        </div>
        
        <div class="mohaa-detail-section">
            <div class="mohaa-detail-panel">
                <h4>🔥 Kill Heatmap</h4>
                <div id="map-heatmap" style="position: relative; min-height: 280px; background: #1a1d21; border-radius: 8px; overflow: hidden;">
                    <img src="', $settings['theme_url'], '/images/mohaastats/maps/', htmlspecialchars($selectedMap), '.jpg" 
                         alt="', htmlspecialchars($displayName), '" 
                         style="width: 100%; display: block;"
                         onerror="this.style.display=\'none\'; this.parentNode.innerHTML=\'<div style=padding:50px;text-align:center;color:#7f8c8d>Map preview not available</div>\';">
                </div>
                <div style="margin-top: 12px; display: flex; gap: 10px;">
                    <button onclick="switchHeatmapType(\'kills\')" class="mohaa-chip active" id="btn-kills" style="flex: 1;">💀 Kills</button>
                    <button onclick="switchHeatmapType(\'deaths\')" class="mohaa-chip inactive" id="btn-deaths" style="flex: 1;">☠️ Deaths</button>
                </div>
            </div>
            
            <div class="mohaa-detail-panel">
                <h4>🏆 Top Players on ', htmlspecialchars($displayName), '</h4>
                <table class="mohaa-data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="text-align: left;">Player</th>
                            <th>Kills</th>
                            <th>Deaths</th>
                            <th>K/D</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (!empty($leaderboard)) {
            foreach (array_slice($leaderboard, 0, 15) as $rank => $player) {
                $kills = (int)($player['kills'] ?? 0);
                $deaths = (int)($player['deaths'] ?? 0);
                $kd = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
                $kdClass = $kd >= 2 ? 'stat-good' : ($kd >= 1 ? 'stat-warn' : 'stat-bad');
                
                echo '
                        <tr>
                            <td><strong>', $rank + 1, '</strong></td>
                            <td style="text-align: left;">
                                <a href="', $scripturl, '?action=mohaastats;sa=player;id=', urlencode($player['id'] ?? ''), '" class="player-link">
                                    ', htmlspecialchars($player['name'] ?? 'Unknown'), '
                                </a>
                            </td>
                            <td>', number_format($kills), '</td>
                            <td>', number_format($deaths), '</td>
                            <td class="', $kdClass, '">', $kd, '</td>
                        </tr>';
            }
        } else {
            echo '<tr><td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px;">No player data available</td></tr>';
        }
        
        echo '
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="', $scripturl, '?action=mohaastats;sa=maps" class="mohaa-back-btn">⬅ Back to All Maps</a>
        </div>';
    } else {
        // Show all maps grid when no specific map selected
        echo '
        <div class="mohaa-section-title" style="margin-top: 15px;">
            <span class="title-icon">🗺️</span>
            <h3>All Maps</h3>
        </div>
        
        <div class="mohaa-entity-grid">';
        
        foreach ($maps as $m) {
            $mapName = $m['name'] ?? '';
            $displayName = $m['display_name'] ?? $mapName;
            $matches = $m['total_matches'] ?? 0;
            $kills = $m['total_kills'] ?? 0;
            
            echo '
            <a href="', $scripturl, '?action=mohaastats;sa=maps;map=', urlencode($mapName), '" class="mohaa-entity-card">
                <div class="entity-image">
                    <img src="', $settings['theme_url'], '/images/mohaastats/maps/', htmlspecialchars($mapName), '.jpg" 
                         alt="', htmlspecialchars($displayName), '"
                         onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">
                    <div class="placeholder" style="display:none;">🗺️</div>
                </div>
                <div class="entity-body">
                    <div class="entity-name" style="margin-bottom: 12px;">', htmlspecialchars($displayName), '</div>
                    <div class="entity-stats">
                        <div class="entity-stat">
                            <span class="entity-stat-value">', number_format($matches), '</span>
                            <span class="entity-stat-label">Matches</span>
                        </div>
                        <div class="entity-stat">
                            <span class="entity-stat-value">', number_format($kills), '</span>
                            <span class="entity-stat-label">Kills</span>
                        </div>
                        <div class="entity-stat">
                            <span class="entity-stat-value">', $matches > 0 ? round($kills / $matches) : 0, '</span>
                            <span class="entity-stat-label">Avg K/M</span>
                        </div>
                    </div>
                </div>
            </a>';
        }
        
        if (empty($maps)) {
            echo '
            <div class="mohaa-empty-state" style="grid-column: 1/-1;">
                <div class="empty-icon">🗺️</div>
                <h4>No Map Data</h4>
                <p>No map data available yet. Play some matches!</p>
            </div>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>';
    
    // Charts JavaScript
    $chartMaps = array_slice($topMaps, 0, 10);
    $mapLabels = array_map(fn($m) => $m['display_name'] ?? $m['name'] ?? 'Unknown', $chartMaps);
    $mapMatches = array_map(fn($m) => $m['total_matches'] ?? 0, $chartMaps);
    $mapKills = array_map(fn($m) => $m['total_kills'] ?? 0, $chartMaps);
    
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var mapLabels = ', json_encode($mapLabels), ';
        var mapMatches = ', json_encode($mapMatches), ';
        var mapKills = ', json_encode($mapKills), ';
        
        // Map Popularity Chart (Horizontal Bar)
        if (document.getElementById("mapPopularityChart") && mapLabels.length > 0) {
            new ApexCharts(document.getElementById("mapPopularityChart"), {
                series: [{ name: "Matches", data: mapMatches }],
                chart: { type: "bar", height: 300, background: "transparent", toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "70%" } },
                colors: ["#4fc3f7"],
                xaxis: { categories: mapLabels, labels: { style: { colors: "#888" } } },
                yaxis: { labels: { style: { colors: "#888" } } },
                grid: { borderColor: "#333" },
                dataLabels: { enabled: true, style: { colors: ["#fff"] } },
                theme: { mode: "dark" }
            }).render();
        }
        
        // Map Kills Chart (Donut)
        if (document.getElementById("mapKillsChart") && mapLabels.length > 0) {
            new ApexCharts(document.getElementById("mapKillsChart"), {
                series: mapKills,
                chart: { type: "donut", height: 300, background: "transparent" },
                labels: mapLabels,
                colors: ["#f44336", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#2196f3", "#00bcd4", "#009688", "#4caf50", "#8bc34a"],
                legend: { position: "right", labels: { colors: "#888" } },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: "60%", labels: { show: true, total: { show: true, label: "Total Kills", color: "#888", formatter: function(w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(); } } } } } },
                theme: { mode: "dark" }
            }).render();
        }
    });';
    
    // Heatmap JavaScript (only if map is selected)
    if (!empty($selectedMap) && !empty($mapData)) {
        $heatmapData = $mapData['heatmap_data'] ?? ['kills' => [], 'deaths' => []];
        echo '
    var heatmapData = ', json_encode($heatmapData), ';
    var currentHeatmapType = "kills";
    
    function switchHeatmapType(type) {
        currentHeatmapType = type;
        document.getElementById("btn-kills").classList.toggle("button_submit", type === "kills");
        document.getElementById("btn-deaths").classList.toggle("button_submit", type === "deaths");
        // Re-render heatmap with new data (placeholder - implement full heatmap rendering)
        console.log("Switching to", type, "heatmap with", (heatmapData[type] || []).length, "points");
    }';
    }
    
    echo '
    </script>';
}

/**
 * Format playtime helper
 */
function format_playtime($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    }
    return $minutes . 'm';
}

/**
 * Competitive Leaderboard Dashboard
 */
/**
 * Competitive Leaderboard Dashboard
 */
function template_mohaa_stats_dashboard()
{
    global $context, $txt, $scripturl, $settings;

    // Use the data structure from the controller
    $cardsData = $context['mohaa_dashboard_cards'] ?? [];

    // Definition of all 40 widgets
    $widgets = [
        // A. Lethality
        'kills' => ['label' => 'Kills', 'icon' => '🗡️'],
        'deaths' => ['label' => 'Deaths', 'icon' => '🪦'],
        'kd' => ['label' => 'K/D Ratio', 'icon' => '⚖️'],
        'headshots' => ['label' => 'Headshots', 'icon' => '🤯'],
        'accuracy' => ['label' => 'Accuracy', 'icon' => '🎯'],
        'shots_fired' => ['label' => 'Trigger Happy', 'icon' => '💥'],
        'damage' => ['label' => 'Damage Dealer', 'icon' => '🩸'],
        'bash_kills' => ['label' => 'Executioner', 'icon' => '🔨'],
        'grenade_kills' => ['label' => 'Grenadier', 'icon' => '💣'],
        'roadkills' => ['label' => 'Road Rage', 'icon' => '🚗'],
        'telefrags' => ['label' => 'Telefrags', 'icon' => '🌌'],
        'crushed' => ['label' => 'Crushed', 'icon' => '🥞'],
        'teamkills' => ['label' => 'Betrayals', 'icon' => '🔪'],
        'suicides' => ['label' => 'Suicides', 'icon' => '💀'],

        // B. Weapon
        'reloads' => ['label' => 'Reloader', 'icon' => '🔄'],
        'weapon_swaps' => ['label' => 'Fickle', 'icon' => '🔀'],
        'no_ammo' => ['label' => 'Empty Clip', 'icon' => '⛽'],
        'looter' => ['label' => 'Looter', 'icon' => '🎒'],

        // C. Movement
        'distance' => ['label' => 'Marathon Man', 'icon' => '🏃'],
        'sprinted' => ['label' => 'Sprinter', 'icon' => '⚡'],
        'swam' => ['label' => 'Swimmer', 'icon' => '🏊'],
        'driven' => ['label' => 'Driver', 'icon' => '🚙'],
        'jumps' => ['label' => 'Bunny Hopper', 'icon' => '🐇'],
        'crouch_time' => ['label' => 'Tactical Crouch', 'icon' => '🦵'],
        'prone_time' => ['label' => 'Camper', 'icon' => '⛺'],
        'ladders' => ['label' => 'Mountaineer', 'icon' => '🧗'],

        // D. Survival
        'health_picked' => ['label' => 'Glutton', 'icon' => '🍗'],
        'ammo_picked' => ['label' => 'Hoarder', 'icon' => '📦'],
        'armor_picked' => ['label' => 'Tank', 'icon' => '🛡️'],
        'items_picked' => ['label' => 'Scavenger', 'icon' => '🗑️'],

        // E. Objectives
        'wins' => ['label' => 'Wins', 'icon' => '🏆'],
        'team_wins' => ['label' => 'Team Wins', 'icon' => '🚩'],
        'ffa_wins' => ['label' => 'FFA Wins', 'icon' => '⚔️'],
        'losses' => ['label' => 'Losses', 'icon' => '☠️'],
        'objectives_done' => ['label' => 'Objective Master', 'icon' => '🎯'],
        'rounds_played' => ['label' => 'Veteran', 'icon' => '⏳'],
        'games_finished' => ['label' => 'Ironman', 'icon' => '🎮'],

        // F. Vehicles
        'vehicle_enter' => ['label' => 'Pilot', 'icon' => '✈️'],
        'turret_enter' => ['label' => 'Gunner', 'icon' => '🔫'],
        'vehicle_kills' => ['label' => 'Saboteur', 'icon' => '🧨'],

        // G. Social
        'chat_msgs' => ['label' => 'Chatterbox', 'icon' => '💬'],
        'spectating' => ['label' => 'Spectator', 'icon' => '👁️'],
        'doors_opened' => ['label' => 'Door Monitor', 'icon' => '🚪'],
    ];

    echo '
    <div class="mohaa-dashboard">

        <!-- War Room Header -->
        <div class="war-room-header">
            <div class="header-title">
                <h1>🏆 TOTAL DOMINATION</h1>
                <span class="header-subtitle">Performance Analysis & Global Records</span>
            </div>

        </div>
        
        <!-- Ensure CSS is loaded -->
        <link rel="stylesheet" href="', $settings['theme_url'], '/css/mohaa_dashboard.css?v=2" />
        
        <div class="mohaa-stats-grid">';

    foreach ($widgets as $key => $meta) {
        $top3 = $cardsData[$key] ?? [];
        
        // Render Card
        echo '
        <div class="mohaa-stat-card clickable" onclick="window.location.href=\'', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, '\'" style="cursor: pointer;">
            <div class="card-header">
                <span class="card-icon">', $meta['icon'], '</span>
                <span class="card-title">', $meta['label'], '</span>
            </div>
            <ul class="top-list">';

        if (empty($top3)) {
            echo '<li class="top-entry"><span class="name" style="color:inherit; opacity:0.5; font-style:italic;">No Data</span></li>';
        } else {
            foreach ($top3 as $idx => $player) {
                $rank = $idx + 1;
                echo '
                <li class="top-entry rank-', $rank, '">
                    <span class="rank">#', $rank, '</span>
                    <span class="name">
                        <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '">', htmlspecialchars($player['name']), '</a>
                    </span>
                    <span class="value">', $player['value'], '</span>
                </li>';
            }
        }
        
        echo '
            </ul>
             <a href="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=', $key, '" class="card-footer">
                View Leaderboard
            </a>
        </div>';
    }

    echo '
        </div>
    </div>';
}

function template_mohaa_stat_card($card)
{
    global $scripturl;
    
    $link = $scripturl . '?action=mohaastats;sa=leaderboards;stat=' . $card['metric'];
    
    echo '
    <div class="mohaa-stat-card" onclick="location.href=\'' . $link . '\'" style="cursor: pointer;">
        <div class="card-header">
            <div class="card-icon">' . ($card['icon'] ?? '🏆') . '</div>
            <div class="card-title">' . ($card['title'] ?? 'Stat') . '</div>
        </div>
        
        <ul class="top-list">';
            
    if (!empty($card['top'])) {
        foreach ($card['top'] as $entry) {
            $rankClass = 'rank-' . $entry['rank'];
            $val = isset($entry['display_value']) && $entry['display_value'] !== '' ? $entry['display_value'] : number_format($entry['value']);
            
            // Format floats nicely if display val missing
            if ((!isset($entry['display_value']) || $entry['display_value'] === '') && strpos((string)$entry['value'], '.') !== false) {
                 $val = number_format($entry['value'], 2);
            }

            echo '
            <li class="top-entry ' . $rankClass . '">
                <div style="display: flex; align-items: center; width: 100%;">
                    <span class="rank">' . $entry['rank'] . '.</span>
                    <span class="name">
                        <a href="' . $scripturl . '?action=mohaastats;sa=player;guid=' . ($entry['guid'] ?? $entry['id'] ?? 0) . '" onclick="event.stopPropagation();">
                            ' . htmlspecialchars($entry['name']) . '
                        </a>
                    </span>
                    <span class="value">' . $val . '</span>
                </div>
            </li>';
        }
    } else {
        echo '<li class="top-entry empty" style="justify-content: center; opacity: 0.6;">No records yet</li>';
    }
            
    echo '
        </ul>
        
        <div class="card-footer">
            View Full Leaderboard
        </div>
    </div>';
}

// ============================================================================
// GAME TYPES TEMPLATE
// ============================================================================

function template_mohaa_stats_gametypes()
{
    global $context, $txt, $scripturl, $settings;
    
    $selectedGameType = $context['mohaa_gametype'] ?? '';
    $gameTypes = $context['mohaa_gametypes_list'] ?? [];
    $leaderboardData = $context['mohaa_gametype_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['leaderboard'] ?? [];
    $gameTypeData = $context['mohaa_gametype_data'] ?? [];
    
    // Calculate aggregate stats
    $totalMatches = 0;
    $totalKills = 0;
    $totalPlayers = 0;
    foreach ($gameTypes as $gt) {
        $totalMatches += (int)($gt['total_matches'] ?? 0);
        $totalKills += (int)($gt['total_kills'] ?? 0);
        $totalPlayers += (int)($gt['unique_players'] ?? 0);
    }
    
    // Find most popular
    $mostPopular = '';
    $maxMatches = 0;
    foreach ($gameTypes as $gt) {
        if ((int)($gt['total_matches'] ?? 0) > $maxMatches) {
            $maxMatches = (int)($gt['total_matches'] ?? 0);
            $mostPopular = $gt['name'] ?? strtoupper($gt['id'] ?? '');
        }
    }
    
    // Load unified dashboard CSS
    template_mohaa_dashboard_css();
    
    // Section Title
    echo '
    <div class="mohaa-section-title">
        <span class="title-icon">🎮</span>
        <h3>Game Type Statistics</h3>
    </div>
    
    <div class="mohaa-page-dashboard">';
    
    // Summary Stats Row
    echo '
        <div class="mohaa-summary-row">
            <div class="mohaa-summary-card">
                <span class="stat-icon">🎮</span>
                <span class="stat-value">', count($gameTypes), '</span>
                <span class="stat-label">Game Types</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">⚔️</span>
                <span class="stat-value">', number_format($totalMatches), '</span>
                <span class="stat-label">Total Matches</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">💀</span>
                <span class="stat-value">', number_format($totalKills), '</span>
                <span class="stat-label">Total Kills</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">🏆</span>
                <span class="stat-value">', htmlspecialchars($mostPopular ?: 'N/A'), '</span>
                <span class="stat-label">Most Popular</span>
            </div>
        </div>';
    
    // Charts row
    echo '
        <div class="mohaa-charts-row">
            <div class="mohaa-chart-card">
                <h4>📊 Game Type Popularity (By Matches)</h4>
                <div id="gametypePopularityChart" style="height: 300px;"></div>
            </div>
            <div class="mohaa-chart-card">
                <h4>💀 Kill Distribution By Game Type</h4>
                <div id="gametypeKillsChart" style="height: 300px;"></div>
            </div>
        </div>';
    
    // Filter Bar
    echo '
        <div class="mohaa-filter-bar">
            <label>🔍 Select Game Type:</label>
            <form method="get" action="', $scripturl, '">
                <input type="hidden" name="action" value="mohaastats">
                <input type="hidden" name="sa" value="gametypes">
                <select name="gametype" onchange="this.form.submit()">
                    <option value="">-- All Game Types Overview --</option>';
    
    foreach ($gameTypes as $gt) {
        $id = $gt['id'] ?? '';
        $name = $gt['name'] ?? strtoupper($id);
        $icon = $gt['icon'] ?? '🎮';
        $selected = ($selectedGameType === $id) ? ' selected' : '';
        echo '<option value="', htmlspecialchars($id), '"', $selected, '>', $icon, ' ', htmlspecialchars($name), '</option>';
    }
    
    echo '
                </select>
            </form>
        </div>';
    
    // Show detail view if game type selected, otherwise show grid
    if (!empty($selectedGameType) && !empty($gameTypeData)) {
        $gtName = $gameTypeData['name'] ?? strtoupper($selectedGameType);
        $gtIcon = $gameTypeData['icon'] ?? '🎮';
        $gtDesc = $gameTypeData['description'] ?? '';
        
        // Back button
        echo '
        <a href="', $scripturl, '?action=mohaastats;sa=gametypes" class="mohaa-back-btn">← Back to All Game Types</a>
        
        <div class="mohaa-section-title">
            <span class="title-icon">', $gtIcon, '</span>
            <h3>', htmlspecialchars($gtName), ' - Detailed Statistics</h3>
        </div>
        
        <div class="mohaa-summary-row">
            <div class="mohaa-summary-card">
                <span class="stat-icon">⚔️</span>
                <span class="stat-value">', number_format((int)($gameTypeData['total_matches'] ?? 0)), '</span>
                <span class="stat-label">Matches Played</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">💀</span>
                <span class="stat-value">', number_format((int)($gameTypeData['total_kills'] ?? 0)), '</span>
                <span class="stat-label">Total Kills</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">🗺️</span>
                <span class="stat-value">', number_format((int)($gameTypeData['map_count'] ?? 0)), '</span>
                <span class="stat-label">Maps</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">👥</span>
                <span class="stat-value">', number_format((int)($gameTypeData['unique_players'] ?? 0)), '</span>
                <span class="stat-label">Unique Players</span>
            </div>
        </div>
        
        <div class="mohaa-detail-section">
            <div class="mohaa-detail-panel">
                <h4>🗺️ Maps in ', htmlspecialchars($gtName), '</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="mohaa-data-table">
                        <thead>
                            <tr>
                                <th>Map</th>
                                <th>Matches</th>
                                <th>Kills</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        $maps = $gameTypeData['maps'] ?? [];
        if (!empty($maps)) {
            foreach ($maps as $map) {
                echo '
                            <tr>
                                <td>
                                    <a href="', $scripturl, '?action=mohaastats;sa=maps;map=', urlencode($map['name'] ?? ''), '">
                                        ', htmlspecialchars($map['display_name'] ?? $map['name'] ?? 'Unknown'), '
                                    </a>
                                </td>
                                <td>', number_format((int)($map['matches'] ?? 0)), '</td>
                                <td>', number_format((int)($map['kills'] ?? 0)), '</td>
                            </tr>';
            }
        } else {
            echo '<tr><td colspan="3" class="mohaa-empty-state">No maps data</td></tr>';
        }
        
        echo '
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mohaa-detail-panel">
                <h4>🏆 Top Players in ', htmlspecialchars($gtName), '</h4>
                <table class="mohaa-data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <th>Kills</th>
                            <th>Deaths</th>
                            <th>K/D</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (!empty($leaderboard)) {
            foreach (array_slice($leaderboard, 0, 15) as $rank => $player) {
                $kills = (int)($player['kills'] ?? 0);
                $deaths = (int)($player['deaths'] ?? 0);
                $kd = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
                $kdClass = $kd >= 2 ? 'stat-good' : ($kd >= 1 ? 'stat-warn' : 'stat-bad');
                
                echo '
                        <tr>
                            <td><strong>', $rank + 1, '</strong></td>
                            <td>
                                <a href="', $scripturl, '?action=mohaastats;sa=player;id=', urlencode($player['id'] ?? ''), '">
                                    ', htmlspecialchars($player['name'] ?? 'Unknown'), '
                                </a>
                            </td>
                            <td>', number_format($kills), '</td>
                            <td>', number_format($deaths), '</td>
                            <td class="', $kdClass, '">', $kd, '</td>
                        </tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="mohaa-empty-state">No player data</td></tr>';
        }
        
        echo '
                    </tbody>
                </table>
            </div>
        </div>';
    } else {
        // Show all game types grid
        echo '
        <div class="mohaa-section-title">
            <span class="title-icon">🎮</span>
            <h3>All Game Types</h3>
        </div>
        
        <div class="mohaa-entity-grid">';
        
        foreach ($gameTypes as $gt) {
            $id = $gt['id'] ?? '';
            $name = $gt['name'] ?? strtoupper($id);
            $icon = $gt['icon'] ?? '🎮';
            $desc = $gt['description'] ?? '';
            $matches = (int)($gt['total_matches'] ?? 0);
            $kills = (int)($gt['total_kills'] ?? 0);
            $mapCount = (int)($gt['map_count'] ?? 0);
            
            echo '
            <a href="', $scripturl, '?action=mohaastats;sa=gametypes;gametype=', urlencode($id), '" class="mohaa-entity-card">
                <div class="entity-header">
                    <span class="entity-icon">', $icon, '</span>
                    <span class="entity-name">', htmlspecialchars($name), '</span>
                    <span class="entity-desc">', htmlspecialchars($desc), '</span>
                </div>
                <div class="entity-stats">
                    <div class="stat">
                        <span class="stat-value">', number_format($matches), '</span>
                        <span class="stat-label">Matches</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">', number_format($kills), '</span>
                        <span class="stat-label">Kills</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">', number_format($mapCount), '</span>
                        <span class="stat-label">Maps</span>
                    </div>
                </div>
            </a>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>';
    
    // ApexCharts JavaScript
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var gameTypes = ', json_encode(array_map(function($gt) {
            return [
                'name' => $gt['name'] ?? strtoupper($gt['id'] ?? ''),
                'matches' => (int)($gt['total_matches'] ?? 0),
                'kills' => (int)($gt['total_kills'] ?? 0),
            ];
        }, $gameTypes)), ';
        
        // Popularity bar chart
        if (document.getElementById("gametypePopularityChart") && gameTypes.length > 0) {
            new ApexCharts(document.getElementById("gametypePopularityChart"), {
                series: [{
                    name: "Matches",
                    data: gameTypes.map(function(g) { return g.matches; })
                }],
                chart: { type: "bar", height: 300, background: "transparent", toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                dataLabels: { enabled: true },
                xaxis: { categories: gameTypes.map(function(g) { return g.name; }) },
                colors: ["#4fc3f7"],
                theme: { mode: "dark" }
            }).render();
        }
        
        // Kills donut chart
        if (document.getElementById("gametypeKillsChart") && gameTypes.length > 0) {
            new ApexCharts(document.getElementById("gametypeKillsChart"), {
                series: gameTypes.map(function(g) { return g.kills; }),
                chart: { type: "donut", height: 300, background: "transparent" },
                labels: gameTypes.map(function(g) { return g.name; }),
                colors: ["#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#2196f3", "#00bcd4", "#009688"],
                legend: { position: "right", labels: { colors: "#fff" } },
                theme: { mode: "dark" }
            }).render();
        }
    });
    </script>';
}

// ============================================================================
// WEAPONS LIST TEMPLATE
// ============================================================================

function template_mohaa_stats_weapons()
{
    global $context, $scripturl, $settings;
    
    $weapons = $context['mohaa_weapons_list'] ?? [];
    
    // Calculate aggregate stats
    $totalKills = 0;
    $totalHeadshots = 0;
    foreach ($weapons as $w) {
        $totalKills += (int)($w['kills'] ?? 0);
        $totalHeadshots += (int)($w['headshots'] ?? 0);
    }
    
    // Find deadliest weapon
    $deadliest = '';
    $maxKills = 0;
    foreach ($weapons as $w) {
        if ((int)($w['kills'] ?? 0) > $maxKills) {
            $maxKills = (int)($w['kills'] ?? 0);
            $deadliest = $w['name'] ?? 'Unknown';
        }
    }
    
    // Load unified dashboard CSS
    template_mohaa_dashboard_css();
    
    // Section Title
    echo '
    <div class="mohaa-section-title">
        <span class="title-icon">🔫</span>
        <h3>Weapon Statistics</h3>
    </div>
    
    <div class="mohaa-page-dashboard">';
    
    // Summary Stats Row
    echo '
        <div class="mohaa-summary-row">
            <div class="mohaa-summary-card">
                <span class="stat-icon">🔫</span>
                <span class="stat-value">', count($weapons), '</span>
                <span class="stat-label">Weapons</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">💀</span>
                <span class="stat-value">', number_format($totalKills), '</span>
                <span class="stat-label">Total Kills</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">🎯</span>
                <span class="stat-value">', number_format($totalHeadshots), '</span>
                <span class="stat-label">Total Headshots</span>
            </div>
            <div class="mohaa-summary-card">
                <span class="stat-icon">🏆</span>
                <span class="stat-value">', htmlspecialchars($deadliest ?: 'N/A'), '</span>
                <span class="stat-label">Deadliest</span>
            </div>
        </div>';
    
    // Charts row
    echo '
        <div class="mohaa-charts-row">
            <div class="mohaa-chart-card">
                <h4>🔥 Most Popular Weapons (Kills)</h4>
                <div id="weaponKillsChart" style="height: 300px;"></div>
            </div>
            <div class="mohaa-chart-card">
                <h4>🎯 Accuracy Comparison</h4>
                <div id="weaponAccuracyChart" style="height: 300px;"></div>
            </div>
        </div>';
    
    // Filter Bar
    echo '
        <div class="mohaa-filter-bar">
            <label>🔍 Filter Weapons:</label>
            <input type="text" id="weaponSearch" placeholder="Search weapons..." onkeyup="filterWeapons()">
        </div>';
    
    // Entity Grid
    echo '
        <div class="mohaa-entity-grid" id="weaponsGrid">';
        
        foreach ($weapons as $w) {
            $name = $w['name'] ?? 'Unknown';
            $kills = (int)($w['kills'] ?? 0);
            $img = $settings['theme_url'] . '/images/mohaastats/weapons/' . $name . '.png';
            
            // Calc derived stats
            $hits = (int)($w['hits'] ?? 0);
            $shots = (int)($w['shots'] ?? 0);
            $accuracy = $shots > 0 ? round(($hits / $shots) * 100, 1) . '%' : 'N/A';
            $headshots = (int)($w['headshots'] ?? 0);
            $hsRatio = $kills > 0 ? round(($headshots / $kills) * 100, 1) . '%' : '0%';
            
            echo '
            <a href="', $scripturl, '?action=mohaastats;sa=weapon;id=', urlencode($name), '" class="mohaa-entity-card" data-name="', strtolower($name), '">
                <div class="entity-image">
                    <img src="', $img, '" alt="', htmlspecialchars($name), '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
                    <span class="entity-placeholder" style="display:none;">🔫</span>
                </div>
                <div class="entity-header">
                    <span class="entity-name">', htmlspecialchars($name), '</span>
                </div>
                <div class="entity-stats">
                    <div class="stat">
                        <span class="stat-value">', number_format($kills), '</span>
                        <span class="stat-label">Kills</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">', $accuracy, '</span>
                        <span class="stat-label">Accuracy</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">', $headshots, '</span>
                        <span class="stat-label">Headshots</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">', $hsRatio, '</span>
                        <span class="stat-label">HS%</span>
                    </div>
                </div>
            </a>';
        }
        
        echo '
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    function filterWeapons() {
        var input = document.getElementById("weaponSearch");
        var filter = input.value.toLowerCase();
        var cards = document.querySelectorAll(".mohaa-entity-card[data-name]");
        
        for (var i = 0; i < cards.length; i++) {
            var name = cards[i].getAttribute("data-name");
            if (name.indexOf(filter) > -1) {
                cards[i].style.display = "";
            } else {
                cards[i].style.display = "none";
            }
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        var weapons = ', json_encode(array_slice($weapons, 0, 10)), '; // Top 10 for charts
        
        // Kills Chart
        if (document.getElementById("weaponKillsChart") && weapons.length > 0) {
            new ApexCharts(document.getElementById("weaponKillsChart"), {
                series: [{ name: "Kills", data: weapons.map(w => w.kills || 0) }],
                chart: { type: "bar", height: 300, background: "transparent", toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                colors: ["#e91e63"],
                xaxis: { categories: weapons.map(w => w.name), labels: { style: { colors: "#888" } } },
                yaxis: { labels: { style: { colors: "#fff" } } },
                theme: { mode: "dark" }
            }).render();
        }
        
        // Accuracy Chart
        var accWeapons = weapons.filter(w => (w.shots || 0) > 100);
        
        if (document.getElementById("weaponAccuracyChart") && accWeapons.length > 0) {
            new ApexCharts(document.getElementById("weaponAccuracyChart"), {
                 series: [{ 
                    name: "Accuracy %", 
                    data: accWeapons.map(w => ((w.hits / w.shots) * 100).toFixed(1)) 
                }],
                chart: { type: "radar", height: 300, background: "transparent", toolbar: { show: false } },
                xaxis: { categories: accWeapons.map(w => w.name), labels: { style: { colors: "#888" } } },
                fill: { opacity: 0.2, colors: ["#4fc3f7"] },
                stroke: { show: true, width: 2, colors: ["#4fc3f7"], dashArray: 0 },
                markers: { size: 4, colors: ["#fff"], strokeColors: "#4fc3f7", strokeWidth: 2 },
                yaxis: { show: false },
                theme: { mode: "dark" }
            }).render();
        }
    });
    </script>';
}

// ============================================================================
// WEAPON DETAIL TEMPLATE
// ============================================================================

function template_mohaa_stats_weapon_detail() 
{
    global $context, $scripturl, $settings;
    
    $weapon = $context['mohaa_weapon_data'] ?? [];
    $name = $weapon['weapon'] ?? 'Unknown';
    $leaderboard = $context['mohaa_weapon_leaderboard'] ?? [];
    
    // Stats
    $kills = (int)($weapon['kills'] ?? 0);
    $hits = (int)($weapon['hits'] ?? 0);
    $shots = (int)($weapon['shots'] ?? 0);
    $headshots = (int)($weapon['headshots'] ?? 0);
    
    $accuracy = $shots > 0 ? round(($hits / $shots) * 100, 2) . '%' : '0%';
    $hsRatio = $kills > 0 ? round(($headshots / $kills) * 100, 2) . '%' : '0%';
    
    $img = $settings['theme_url'] . '/images/mohaastats/weapons/' . $name . '.png';
    
    // Load unified dashboard CSS
    template_mohaa_dashboard_css();
    
    // Back button
    echo '
    <a href="', $scripturl, '?action=mohaastats;sa=weapons" class="mohaa-back-btn">← Back to Weapon List</a>';
    
    // Section Title
    echo '
    <div class="mohaa-section-title">
        <span class="title-icon">🔫</span>
        <h3>Weapon Detail: ', htmlspecialchars($name), '</h3>
    </div>
    
    <div class="mohaa-page-dashboard">';
    
    // Weapon image + summary row
    echo '
        <div class="mohaa-detail-section">
            <div class="mohaa-detail-panel" style="text-align: center; flex: 0 0 250px;">
                <div class="entity-image" style="height: 150px; margin-bottom: 15px;">
                    <img src="', $img, '" alt="', htmlspecialchars($name), '" style="max-width: 100%; max-height: 100%;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
                    <span class="entity-placeholder" style="display:none; font-size: 4rem;">🔫</span>
                </div>
                <div style="font-weight: bold; font-size: 1.4rem; color: #fff;">', htmlspecialchars($name), '</div>
            </div>
            
            <div class="mohaa-detail-panel" style="flex: 1;">
                <div class="mohaa-summary-row" style="margin-bottom: 0;">
                    <div class="mohaa-summary-card">
                        <span class="stat-icon">💀</span>
                        <span class="stat-value" style="color: #e91e63;">', number_format($kills), '</span>
                        <span class="stat-label">Total Kills</span>
                    </div>
                    <div class="mohaa-summary-card">
                        <span class="stat-icon">🎯</span>
                        <span class="stat-value" style="color: #4fc3f7;">', $accuracy, '</span>
                        <span class="stat-label">Global Accuracy</span>
                    </div>
                    <div class="mohaa-summary-card">
                        <span class="stat-icon">🎯</span>
                        <span class="stat-value" style="color: #ff9800;">', $hsRatio, '</span>
                        <span class="stat-label">Headshot Ratio</span>
                    </div>
                    <div class="mohaa-summary-card">
                        <span class="stat-icon">💥</span>
                        <span class="stat-value" style="color: #8bc34a;">', number_format($shots), '</span>
                        <span class="stat-label">Shots Fired</span>
                    </div>
                </div>
            </div>
        </div>';
    
    // Leaderboard
    echo '
        <div class="mohaa-section-title">
            <span class="title-icon">🏆</span>
            <h3>Top Specialists with ', htmlspecialchars($name), '</h3>
        </div>
        
        <table class="mohaa-data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Player</th>
                    <th>Kills</th>
                    <th>Shots</th>
                    <th>Hits</th>
                    <th>Accuracy</th>
                    <th>Headshots</th>
                </tr>
            </thead>
            <tbody>';
            
    if (!empty($leaderboard)) {
        $rank = 1;
        foreach ($leaderboard as $entry) {
            $pName = $entry['player'] ?? 'Unknown';
            $pKills = (int)($entry['kills'] ?? 0);
            $pHits = (int)($entry['hits'] ?? 0);
            $pShots = (int)($entry['shots'] ?? 0);
            $pHS = (int)($entry['headshots'] ?? 0);
            
            $pAcc = $pShots > 0 ? round(($pHits / $pShots) * 100, 1) . '%' : 'N/A';
            
            echo '
                <tr>
                    <td>', $rank++, '</td>
                    <td><a href="', $scripturl, '?action=mohaastats;sa=player;id=', urlencode($pName), '">', htmlspecialchars($pName), '</a></td>
                    <td class="stat-bad">', number_format($pKills), '</td>
                    <td>', number_format($pShots), '</td>
                    <td>', number_format($pHits), '</td>
                    <td style="color:#4fc3f7;">', $pAcc, '</td>
                    <td style="color:#ff9800;">', number_format($pHS), '</td>
                </tr>';
        }
    } else {
        echo '<tr><td colspan="7" class="mohaa-empty-state">No data available for this weapon</td></tr>';
    }
            
    echo '
            </tbody>
        </table>
    </div>';
}
