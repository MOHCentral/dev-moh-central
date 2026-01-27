<?php
/**
 * Battles List Template
 * 
 * Shows all battles/matches with filters and search
 */

function template_mohaa_battles_list()
{
    global $context, $scripturl, $txt;
    
    $battles = $context['mohaa_battles']['list'] ?? [];
    $total = $context['mohaa_battles']['total'] ?? 0;
    
    ?>
    <div class="cat_bar">
        <h3 class="catbg">
            ⚔️ <?php echo $txt['mohaa_battles'] ?? 'Battles & Matches'; ?>
        </h3>
    </div>
    
    <div class="windowbg">
        <div style="padding: 15px;">
            
            <!-- Filters -->
            <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                <div>
                    <label style="font-weight: bold; margin-right: 5px;">Map:</label>
                    <select id="mapFilter" onchange="filterBattles()">
                        <option value="">All Maps</option>
                        <option value="DM/Mohdm1">DM/Mohdm1</option>
                        <option value="DM/Mohdm2">DM/Mohdm2</option>
                        <option value="DM/Mohdm3">DM/Mohdm3</option>
                        <option value="obj/obj_team1">obj/obj_team1</option>
                        <option value="obj/obj_team2">obj/obj_team2</option>
                    </select>
                </div>
                
                <div>
                    <label style="font-weight: bold; margin-right: 5px;">Game Type:</label>
                    <select id="gameTypeFilter" onchange="filterBattles()">
                        <option value="">All Types</option>
                        <option value="FFA">Free For All</option>
                        <option value="TDM">Team Deathmatch</option>
                        <option value="OBJ">Objective</option>
                        <option value="RBM">Round Based</option>
                    </select>
                </div>
                
                <div style="margin-left: auto;">
                    <strong>Total Battles:</strong> <?php echo number_format($total); ?>
                </div>
            </div>
            
            <?php if (empty($battles)): ?>
                <div class="infobox" style="padding: 30px; text-align: center;">
                    <p style="font-size: 1.2em; color: #666;">
                        ⚔️ No battles found
                    </p>
                    <p style="color: #999;">
                        Battles will appear here after matches are played
                    </p>
                </div>
            <?php else: ?>
                
                <!-- Battles List -->
                <table class="table_grid" style="width: 100%;">
                    <thead>
                        <tr class="title_bar">
                            <th>Map</th>
                            <th>Game Type</th>
                            <th>Started</th>
                            <th>Duration</th>
                            <th>Players</th>
                            <th>Score</th>
                            <th>K/D</th>
                            <th>Winner</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($battles as $battle): ?>
                            <?php
                                $duration = gmdate('i:s', $battle['duration_seconds']);
                                $winnerText = $battle['winner_team'] === 'allies' ? 'Allies' : 
                                             ($battle['winner_team'] === 'axis' ? 'Axis' : 'Draw');
                                $winnerColor = $battle['winner_team'] === 'allies' ? '#4CAF50' : 
                                              ($battle['winner_team'] === 'axis' ? '#f44336' : '#999');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($battle['map_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background: #2196F3; color: white; padding: 3px 8px; border-radius: 3px;">
                                        <?php echo htmlspecialchars($battle['game_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M j, Y H:i', strtotime($battle['started_at'])); ?>
                                </td>
                                <td><?php echo $duration; ?></td>
                                <td><?php echo $battle['total_players']; ?></td>
                                <td>
                                    <span style="color: #4CAF50;"><?php echo $battle['final_score_allies']; ?></span>
                                    -
                                    <span style="color: #f44336;"><?php echo $battle['final_score_axis']; ?></span>
                                </td>
                                <td>
                                    <?php echo $battle['total_kills']; ?> / <?php echo $battle['total_deaths']; ?>
                                    <span style="color: #666; font-size: 0.9em;">
                                        (<?php echo $battle['total_deaths'] > 0 ? round($battle['total_kills'] / $battle['total_deaths'], 2) : $battle['total_kills']; ?>)
                                    </span>
                                </td>
                                <td>
                                    <span style="color: <?php echo $winnerColor; ?>; font-weight: bold;">
                                        <?php echo $winnerText; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo $scripturl; ?>?action=mohaastats;sa=battle;id=<?php echo $battle['battle_id']; ?>" 
                                       class="button" 
                                       style="padding: 5px 10px; font-size: 0.9em;">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if (!empty($context['page_index'])): ?>
                    <div style="margin-top: 15px;">
                        <?php echo $context['page_index']; ?>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
        </div>
    </div>
    
    <script>
    function filterBattles() {
        const map = document.getElementById('mapFilter').value;
        const gametype = document.getElementById('gameTypeFilter').value;
        
        let url = '<?php echo $scripturl; ?>?action=mohaastats;sa=battles';
        
        if (map) url += ';map=' + map;
        if (gametype) url += ';gametype=' + gametype;
        
        window.location.href = url;
    }
    
    // Restore filter values
    document.addEventListener('DOMContentLoaded', function() {
        const mapFilter = '<?php echo $context['mohaa_battles']['map_filter'] ?? ''; ?>';
        const gameTypeFilter = '<?php echo $context['mohaa_battles']['gametype_filter'] ?? ''; ?>';
        
        if (mapFilter) document.getElementById('mapFilter').value = mapFilter;
        if (gameTypeFilter) document.getElementById('gameTypeFilter').value = gameTypeFilter;
    });
    </script>
    
    <?php
}
