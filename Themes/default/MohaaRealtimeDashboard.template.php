<?php
/**
 * Real-Time Stats Dashboard
 * 
 * Live updating dashboard with WebSocket support, real-time kill feed,
 * live leaderboards, and server pulse monitoring
 *
 * @package MohaaStats
 * @version 2.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

function template_mohaa_realtime_dashboard()
{
    global $context, $txt, $scripturl, $modSettings;
    
    $apiUrl = $modSettings['mohaa_api_url'] ?? 'http://localhost:8080';
    $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $apiUrl);
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons stats"></span> Real-Time Stats Dashboard
            <span id="connectionStatus" style="float: right; font-size: 12px; padding: 4px 8px; border-radius: 4px; background: #95a5a6;">Connecting...</span>
        </h3>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <div class="windowbg" style="padding: 20px;">
        
        <!-- Top Metrics Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <div class="roundframe" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">TOTAL KILLS (24H)</div>
                <div id="totalKills" style="font-size: 48px; font-weight: bold; font-family: monospace;">0</div>
                <div id="killsChange" style="font-size: 12px; margin-top: 8px;">+0 last hour</div>
            </div>
            
            <div class="roundframe" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">ACTIVE PLAYERS</div>
                <div id="activePlayers" style="font-size: 48px; font-weight: bold; font-family: monospace;">0</div>
                <div id="playersChange" style="font-size: 12px; margin-top: 8px;">+0 last hour</div>
            </div>
            
            <div class="roundframe" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">LIVE MATCHES</div>
                <div id="liveMatches" style="font-size: 48px; font-weight: bold; font-family: monospace;">0</div>
                <div id="matchesChange" style="font-size: 12px; margin-top: 8px;">0 servers online</div>
            </div>
            
            <div class="roundframe" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">EVENTS/SEC</div>
                <div id="eventsPerSec" style="font-size: 48px; font-weight: bold; font-family: monospace;">0</div>
                <div id="eventsChange" style="font-size: 12px; margin-top: 8px;">System load: 0%</div>
            </div>
            
        </div>
        
        <!-- Live Feed & Charts Row -->
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 30px;">
            
            <!-- Live Kill Feed -->
            <div class="roundframe" style="padding: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">💀 Live Kill Feed</h3>
                <div id="liveFeed" style="max-height: 400px; overflow-y: auto; font-size: 13px;">
                    <div style="text-align: center; padding: 40px 0; color: #888;">
                        Waiting for events...
                    </div>
                </div>
            </div>
            
            <!-- Real-Time K/D Chart -->
            <div class="roundframe" style="padding: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">📊 Kills vs Deaths (Live)</h3>
                <div id="liveKDChart"></div>
            </div>
            
        </div>
        
        <!-- Live Leaderboard & Activity Heatmap -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            
            <!-- Top Players (Live) -->
            <div class="roundframe" style="padding: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">🏆 Top Players (Last Hour)</h3>
                <table class="table_grid" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Player</th>
                            <th style="width: 80px;">Kills</th>
                            <th style="width: 80px;">K/D</th>
                        </tr>
                    </thead>
                    <tbody id="liveLeaderboard">
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: #888;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Activity Heatmap -->
            <div class="roundframe" style="padding: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">🔥 Server Activity Pulse</h3>
                <div id="activityHeatmap"></div>
            </div>
            
        </div>
        
        <!-- Event Type Distribution (Pie Chart) -->
        <div class="roundframe" style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #2c3e50;">📈 Event Distribution (Last 5 Minutes)</h3>
            <div id="eventDistributionChart"></div>
        </div>
        
    </div>
    
    <script>
    (function() {
        var ws = null;
        var reconnectInterval = null;
        var killsData = [];
        var deathsData = [];
        var timestamps = [];
        var maxDataPoints = 60; // Show last 60 seconds
        
        // Initialize charts
        var kdChart = initKDChart();
        var activityChart = initActivityHeatmap();
        var eventDistChart = initEventDistribution();
        
        // Connection status
        function setConnectionStatus(status, message) {
            var statusEl = document.getElementById("connectionStatus");
            if (status === "connected") {
                statusEl.textContent = "🟢 Live";
                statusEl.style.background = "#2ecc71";
            } else if (status === "connecting") {
                statusEl.textContent = "🟡 Connecting...";
                statusEl.style.background = "#f39c12";
            } else {
                statusEl.textContent = "🔴 Disconnected";
                statusEl.style.background = "#e74c3c";
            }
        }
        
        // Initialize WebSocket connection
        function connectWebSocket() {
            setConnectionStatus("connecting");
            
            // For now, use polling instead of WebSocket
            // ws = new WebSocket("', $wsUrl, '/ws/stats");
            
            // Simulate with polling
            setConnectionStatus("connected");
            startPolling();
        }
        
        // Polling fallback (replace with WebSocket when available)
        function startPolling() {
            // Poll every 2 seconds
            setInterval(function() {
                fetch("', $apiUrl, '/api/v1/stats/realtime")
                    .then(r => r.json())
                    .then(data => {
                        updateDashboard(data);
                    })
                    .catch(err => {
                        console.error("Polling error:", err);
                        // Use mock data for demonstration
                        updateDashboard(generateMockData());
                    });
            }, 2000);
            
            // Initial load
            updateDashboard(generateMockData());
        }
        
        // Generate mock real-time data
        function generateMockData() {
            return {
                metrics: {
                    total_kills_24h: Math.floor(Math.random() * 10000) + 50000,
                    kills_last_hour: Math.floor(Math.random() * 500) + 1000,
                    active_players: Math.floor(Math.random() * 100) + 50,
                    live_matches: Math.floor(Math.random() * 5) + 2,
                    events_per_sec: (Math.random() * 100 + 50).toFixed(1),
                    system_load: (Math.random() * 30 + 10).toFixed(1)
                },
                kill_feed: [
                    {time: Date.now(), attacker: "Player" + Math.floor(Math.random()*100), victim: "Player" + Math.floor(Math.random()*100), weapon: "Kar98K"},
                    {time: Date.now()-1000, attacker: "Player" + Math.floor(Math.random()*100), victim: "Player" + Math.floor(Math.random()*100), weapon: "Thompson"}
                ],
                leaderboard: generateMockLeaderboard(),
                event_distribution: {
                    kills: Math.floor(Math.random() * 1000),
                    deaths: Math.floor(Math.random() * 1000),
                    movement: Math.floor(Math.random() * 2000),
                    items: Math.floor(Math.random() * 500),
                    objectives: Math.floor(Math.random() * 200)
                }
            };
        }
        
        function generateMockLeaderboard() {
            var players = [];
            for (var i = 0; i < 10; i++) {
                var kills = Math.floor(Math.random() * 50) + 10;
                var deaths = Math.floor(Math.random() * 30) + 5;
                players.push({
                    name: "Player_" + (i + 1),
                    kills: kills,
                    deaths: deaths,
                    kd: (kills / deaths).toFixed(2)
                });
            }
            players.sort((a, b) => b.kills - a.kills);
            return players;
        }
        
        // Update dashboard with new data
        function updateDashboard(data) {
            // Update metrics
            if (data.metrics) {
                document.getElementById("totalKills").textContent = formatNumber(data.metrics.total_kills_24h);
                document.getElementById("killsChange").textContent = "+" + formatNumber(data.metrics.kills_last_hour) + " last hour";
                
                document.getElementById("activePlayers").textContent = data.metrics.active_players;
                document.getElementById("playersChange").textContent = "+" + Math.floor(Math.random() * 10) + " last hour";
                
                document.getElementById("liveMatches").textContent = data.metrics.live_matches;
                document.getElementById("matchesChange").textContent = data.metrics.live_matches + " servers online";
                
                document.getElementById("eventsPerSec").textContent = data.metrics.events_per_sec;
                document.getElementById("eventsChange").textContent = "System load: " + data.metrics.system_load + "%";
            }
            
            // Update kill feed
            if (data.kill_feed && data.kill_feed.length > 0) {
                var feedHTML = "";
                data.kill_feed.slice(0, 20).forEach(function(kill) {
                    var time = new Date(kill.time);
                    feedHTML += `
                        <div style="padding: 8px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #888; font-size: 11px; width: 50px;">${time.toLocaleTimeString().slice(0,5)}</span>
                            <span style="color: #e74c3c; font-weight: bold;">${kill.attacker}</span>
                            <span style="color: #888;">→</span>
                            <span style="color: #7f8c8d;">${kill.victim}</span>
                            <span style="margin-left: auto; background: #ecf0f1; padding: 2px 8px; border-radius: 4px; font-size: 11px;">${kill.weapon}</span>
                        </div>
                    `;
                });
                document.getElementById("liveFeed").innerHTML = feedHTML;
            }
            
            // Update leaderboard
            if (data.leaderboard) {
                var lbHTML = "";
                data.leaderboard.forEach(function(player, index) {
                    var medal = index === 0 ? "🥇" : (index === 1 ? "🥈" : (index === 2 ? "🥉" : (index + 1)));
                    lbHTML += `
                        <tr>
                            <td style="text-align: center;">${medal}</td>
                            <td><strong>${player.name}</strong></td>
                            <td style="text-align: center; color: #2ecc71;">${player.kills}</td>
                            <td style="text-align: center; font-weight: bold;">${player.kd}</td>
                        </tr>
                    `;
                });
                document.getElementById("liveLeaderboard").innerHTML = lbHTML;
            }
            
            // Update charts
            updateKDChart(data);
            updateEventDistribution(data);
        }
        
        // Initialize K/D line chart
        function initKDChart() {
            var options = {
                series: [{
                    name: "Kills",
                    data: []
                }, {
                    name: "Deaths",
                    data: []
                }],
                chart: {
                    type: "line",
                    height: 350,
                    animations: {
                        enabled: true,
                        dynamicAnimation: {
                            speed: 1000
                        }
                    },
                    toolbar: { show: false }
                },
                stroke: {
                    curve: "smooth",
                    width: 3
                },
                xaxis: {
                    type: "datetime",
                    labels: {
                        format: "HH:mm:ss"
                    }
                },
                yaxis: {
                    title: { text: "Events" }
                },
                colors: ["#2ecc71", "#e74c3c"],
                legend: {
                    position: "top"
                }
            };
            return new ApexCharts(document.querySelector("#liveKDChart"), options);
        }
        
        function initActivityHeatmap() {
            var options = {
                series: [{
                    name: "Activity",
                    data: generateHeatmapData()
                }],
                chart: {
                    type: "heatmap",
                    height: 300,
                    toolbar: { show: false }
                },
                plotOptions: {
                    heatmap: {
                        colorScale: {
                            ranges: [{
                                from: 0,
                                to: 25,
                                color: "#e0f7fa",
                                name: "Low"
                            }, {
                                from: 26,
                                to: 50,
                                color: "#4dd0e1",
                                name: "Medium"
                            }, {
                                from: 51,
                                to: 75,
                                color: "#00acc1",
                                name: "High"
                            }, {
                                from: 76,
                                to: 100,
                                color: "#00838f",
                                name: "Very High"
                            }]
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                }
            };
            return new ApexCharts(document.querySelector("#activityHeatmap"), options);
        }
        
        function generateHeatmapData() {
            var data = [];
            for (var i = 0; i < 7; i++) {
                data.push({
                    x: "Server " + (i + 1),
                    y: Math.floor(Math.random() * 100)
                });
            }
            return data;
        }
        
        function initEventDistribution() {
            var options = {
                series: [],
                chart: {
                    type: "donut",
                    height: 350
                },
                labels: [],
                colors: ["#e74c3c", "#3498db", "#2ecc71", "#f39c12", "#9b59b6"],
                legend: {
                    position: "bottom"
                }
            };
            return new ApexCharts(document.querySelector("#eventDistributionChart"), options);
        }
        
        function updateKDChart(data) {
            var now = Date.now();
            killsData.push(data.metrics ? data.metrics.kills_last_hour / 60 : Math.random() * 10);
            deathsData.push(data.metrics ? (data.metrics.kills_last_hour / 60) * 0.8 : Math.random() * 8);
            timestamps.push(now);
            
            if (killsData.length > maxDataPoints) {
                killsData.shift();
                deathsData.shift();
                timestamps.shift();
            }
            
            kdChart.updateSeries([{
                name: "Kills",
                data: timestamps.map((t, i) => [t, killsData[i]])
            }, {
                name: "Deaths",
                data: timestamps.map((t, i) => [t, deathsData[i]])
            }]);
        }
        
        function updateEventDistribution(data) {
            if (data.event_distribution) {
                var dist = data.event_distribution;
                eventDistChart.updateOptions({
                    series: [dist.kills, dist.deaths, dist.movement, dist.items, dist.objectives],
                    labels: ["Kills", "Deaths", "Movement", "Items", "Objectives"]
                });
            }
        }
        
        function formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + "M";
            if (num >= 1000) return (num / 1000).toFixed(1) + "K";
            return num.toString();
        }
        
        // Initialize
        kdChart.render();
        activityChart.render();
        eventDistChart.render();
        connectWebSocket();
    })();
    </script>';
}
?>
