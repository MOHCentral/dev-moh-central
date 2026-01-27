<?php
/**
 * MOHAA Stats - Server Dashboard Template
 * 
 * Displays:
 * 1. Global Activity Heatmap (Day x Hour)
 * 2. Map Popularity (Pie/Donut)
 * 3. Recent Matches List
 */

function template_mohaa_server_stats()
{
    global $context, $scripturl, $txt, $settings;

    $stats = $context['mohaa_server_stats'] ?? [];
    $activity = $stats['activity'] ?? [];
    $maps = $stats['maps'] ?? [];
    $matches = $stats['recent_matches']['list'] ?? [];

    echo '
    <div class="mohaa-war-room">
        <div class="cat_bar">
            <h3 class="catbg">', $txt['mohaa_server_dashboard'] ?? 'Server Analytics', '</h3>
        </div>

        <!-- Upper Section: Charts -->
        <div class="mohaa-grid-2">
            
            <!-- Global Activity Heatmap -->
            <div class="windowbg">
                <h4 class="mohaa-title">Global Activity (30 Days)</h4>
                <div id="chart-activity" style="min-height: 300px;"></div>
            </div>

            <!-- Map Popularity -->
            <div class="windowbg">
                <h4 class="mohaa-title">Map Popularity</h4>
                <div id="chart-maps" style="min-height: 300px;"></div>
            </div>
            
        </div>

        <!-- Lower Section: Recent Matches -->
        <div class="cat_bar" style="margin-top: 20px;">
            <h3 class="catbg">', $txt['mohaa_recent_matches'] ?? 'Recent Matches', '</h3>
        </div>
        
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>Map</th>
                    <th>Mode</th>
                    <th>Size</th>
                    <th>Winner</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

    if (empty($matches)) {
        echo '
                <tr class="windowbg">
                    <td colspan="6" class="centertext">No recent matches found.</td>
                </tr>';
    } else {
        foreach ($matches as $match) {
            $winClass = ''; 
            // format winning team color logic if needed
            echo '
                <tr class="windowbg">
                    <td>', $match['map_name'], '</td>
                    <td>', $match['gametype'], '</td>
                    <td>', $match['player_count'], '/', $match['max_players'] ?? '?', '</td>
                    <td>', ucfirst($match['winning_team'] ?? '-'), '</td>
                    <td>', time_since(strtotime($match['ended_at'] ?? 'now')), ' ago</td>
                    <td class="centertext">
                        <a href="', $scripturl, '?action=mohaastats;sa=match;id=', $match['match_id'], '" class="button">Report</a>
                    </td>
                </tr>';
        }
    }

    echo '
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Process Activity Data for Heatmap
            // ApexCharts Heatmap expects: [{ name: "Monday", data: [{ x: "00:00", y: 10 }, ... ] }, ...]
            const rawActivity = ', json_encode($activity), ';
            const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]; // 1=Mon in clickhouse toDayOfWeek?
            // standard CH toDayOfWeek: 1=Mon, 7=Sun.
            // Let\'s verify mapping. If rawActivity has day:1, it implies Mon.
            
            let heatMapSeries = [];
            
            // Initialize empty structure
            // We want y-axis to be days, x-axis to be hours? Or reverse?
            // Usually Heatmap: Y=Day, X=Hour
            
            // Map 1..7 to Mon..Sun
            const dayMap = {1:"Mon", 2:"Tue", 3:"Wed", 4:"Thu", 5:"Fri", 6:"Sat", 7:"Sun"};
            
            // Group by Day
            let dayGroups = {};
            [7,6,5,4,3,2,1].forEach(d => { dayGroups[d] = []; }); // Order Sun-Mon top-down or Mon-Sun
            
            // Populate sparse
            for(let d=1; d<=7; d++) {
                for(let h=0; h<24; h++) {
                    // find value
                    let val = 0;
                    let found = rawActivity.find(r => r.day == d && r.hour == h);
                    if(found) val = found.value;
                    
                    dayGroups[d].push({ x: h.toString().padStart(2,"0")+":00", y: val });
                }
            }
            
            Object.keys(dayGroups).forEach(d => {
                heatMapSeries.push({ name: dayMap[d], data: dayGroups[d] });
            });

            const optActivity = {
                series: heatMapSeries,
                chart: { type: "heatmap", height: 300, toolbar: {show:false}, background: "transparent" },
                dataLabels: { enabled: false },
                colors: ["#008FFB"],
                title: { text: "Player Activity by Time", style: { color: "#fff" } },
                theme: { mode: "dark" },
                tooltip: { theme: "dark" }
            };
            new ApexCharts(document.querySelector("#chart-activity"), optActivity).render();

            // 2. Map Popularity Pie
            const rawMaps = ', json_encode($maps), ';
            const mapLabels = rawMaps.map(m => m.map_name);
            const mapSeries = rawMaps.map(m => parseInt(m.matches_played));
            
            const optMaps = {
                series: mapSeries,
                labels: mapLabels,
                chart: { type: "donut", height: 300, background: "transparent" },
                theme: { mode: "dark" },
                stroke: { show: true, colors: ["#333"], width: 2 },
                 plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: "Total Matches",
                                    color: "#fff"
                                }
                            }
                        }
                    }
                },
                legend: { position: "bottom", labels: { colors: "#fff" } }
            };
            new ApexCharts(document.querySelector("#chart-maps"), optMaps).render();
        });
    </script>
    ';
}

function time_since($time) {
    $time = time() - $time;
    $time = ($time<1)? 1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }
}
?>
