<?php
/**
 * MOHAA Identity Linking Template
 * 
 * Displays the token for linking in-game identity to forum account.
 * Refactored to use "Hybrid Design" (CSS Grid + SMF Native Classes).
 *
 * @package MohaaPlayers
 * @version 1.2.0
 */

function template_mohaaidentity()
{
    global $context, $scripturl, $txt;
    
    $pendingCount = count($context['mohaa_pending_ips'] ?? []);

    // Helper for safe time formatting
    $safeTime = function($t) {
        if (empty($t)) return 'never';
        // If string (e.g. ISO8601), convert to int
        if (is_string($t) && !is_numeric($t)) $t = strtotime($t);
        // If conversion failed or still invalid
        if (!$t) return 'invalid date';
        return timeformat($t);
    };

    // 1. Header
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons members"></span> Game Identity & Security
        </h3>
    </div>';

    // 2. Alert Box for Pending requests (Only if any)
    if ($pendingCount > 0) {
        echo '
        <div class="warningbox" style="margin: 10px 0;">
            <strong style="font-size: 1.1em; display: block; margin-bottom: 5px;">⚠️ Action Required: ', $pendingCount, ' Pending Login Request', ($pendingCount > 1 ? 's' : ''), '</strong>
            <span class="smalltext">New connections detected. Please ensure you approve only your own connection attempts below.</span>
        </div>';
    }

    // 3. Main Dashboard Grid
    echo '
    <div style="display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 10px;">';

    // --- SECTION A: TOKEN & LINKING ---
    echo '
        <div class="windowbg" style="padding: 15px; border-top: 3px solid #ff9800; border-radius: 4px;">
            <div style="text-align: center;">
                <h4 style="margin: 0 0 10px 0;">Link Your Account</h4>
                <p class="smalltext" style="margin: 0;">Enter this command in your game console (~ top left key) to link your player:</p>
                
                <div style="background: #1e1e1e; color: #4caf50; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 1.2em; margin: 15px 0; display: inline-block; border: 1px solid #333;">
                    ', $context['mohaa_console_command'], '
                </div>
                
                <div style="margin-bottom: 15px;">
                     <span class="smalltext">Token: <strong>', $context['mohaa_token'], '</strong> (Never Expire)</span>
                </div>
                
                 <form id="regenForm" action="', $scripturl, '?action=profile;area=mohaaidentity" method="post">
                    <input type="hidden" name="regenerate_token" value="1">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                    <button type="button" class="button" onclick="showConfirm(\'Generate new token?\', \'This will invalidate your current token.\', function() { document.getElementById(\'regenForm\').submit(); });">
                        🔄 Regenerate Token
                    </button>
                </form>
            </div>
        </div>';

    // --- SECTION B: PENDING APPROVALS (Conditional) ---
    if ($pendingCount > 0) {
        echo '
        <div>
            <div class="title_bar">
                <h3 class="titlebg">Pending Approvals</h3>
            </div>
            <div class="windowbg">
                <table class="table_grid" style="width: 100%">
                    <thead>
                        <tr class="title_bar">
                            <th>Date</th>
                            <th>Server</th>
                            <th>IP Address</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($context['mohaa_pending_ips'] as $pending) {
            echo '
                <tr class="windowbg">
                    <td>', $safeTime($pending['requested_at'] ?? 0), '</td>
                    <td>', htmlspecialchars($pending['server_name']), '</td>
                    <td>', $pending['ip_address'], '</td>
                    <td style="text-align: right;">
                        <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                            <input type="hidden" name="resolve_pending_ip" value="1">
                            <input type="hidden" name="approval_id" value="', $pending['id'], '">
                            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                            
                            <button type="submit" name="action_type" value="approve" class="button_submit" style="color: green; font-weight: bold; margin-right: 5px;">✓ Allow</button>
                            <button type="submit" name="action_type" value="deny" class="button_submit" style="color: red;">✗ Deny</button>
                        </form>
                    </td>
                </tr>';
        }
        echo '      </tbody>
                </table>
            </div>
        </div>';
    }

    // --- SECTION C: TRUSTED DEVICES & HISTORY (2 Columns) ---
    
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">';
    
    // 1. Trusted Devices
    echo '
        <div>
            <div class="title_bar"><h3 class="titlebg">Trusted Devices</h3></div>
            <div class="windowbg">
                <ul style="list-style: none; padding: 0; margin: 0;">';
                
    if (empty($context['mohaa_trusted_ips'])) {
        echo '<li style="padding: 15px; text-align: center; opacity: 0.6;">No trusted devices yet. Login in-game to add one.</li>';
    } else {
        foreach ($context['mohaa_trusted_ips'] as $trusted) {
             $formId = 'removeTrusted_' . $trusted['id'];
             echo '
                <li style="padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: bold;">', $trusted['ip_address'], '</div>
                        <div class="smalltext">Last used: ', $safeTime($trusted['last_used_at'] ?? 0), '</div>
                    </div>
                    <form id="', $formId, '" action="', $scripturl, '?action=profile;area=mohaaidentity" method="post">
                        <input type="hidden" name="remove_trusted_ip" value="1">
                        <input type="hidden" name="ip_id" value="', $trusted['id'], '">
                        <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                        <button type="button" class="button_submit" style="min-height: auto; padding: 4px 8px; font-size: 0.8em;" onclick="showConfirm(\'Remove IP?\', \'This device will need a token to login again.\', function() { document.getElementById(\'', $formId, '\').submit(); });">Remove</button>
                    </form>
                </li>';
        }
    }
    echo '      </ul>
            </div>
        </div>';

    // 2. Recent History
    echo '
        <div>
           <div class="title_bar"><h3 class="titlebg">Recent Activity</h3></div>
            <table class="table_grid" style="width: 100%">
                 <thead>
                    <tr class="title_bar">
                        <th>Date</th>
                        <th>Server</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
                
    if (!empty($context['mohaa_login_history'])) {
        foreach ($context['mohaa_login_history'] as $login) {
            // Clean status string
            $rawStatus = $login['status'] ?? '-';
            $statusColor = (strpos($rawStatus, 'Success') !== false) ? 'green' : 'red';
            $statusText = $rawStatus;
            if (strpos($rawStatus, 'Failed: ') !== false) $statusText = str_replace('Failed: ', '', $rawStatus);
            
            echo '
                <tr class="windowbg">
                    <td class="smalltext">', $safeTime($login['date'] ?? 0), '</td>
                    <td class="smalltext">', htmlspecialchars($login['server'] ?? 'Unknown'), '</td>
                    <td style="color: ', $statusColor, '; font-weight: bold;">', $statusText, '</td>
                </tr>';
        }
    } else {
        echo '<tr class="windowbg"><td colspan="3" style="text-align:center; opacity:0.6;">No activity recorded.</td></tr>';
    }
    
    echo '      </tbody>
            </table>
        </div>';
        
    echo '</div>'; // End 2-col grid

    echo '</div>'; // End Main Grid

    // Custom Modal Script & HTML
    echo '
    <div id="mohaaModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
        <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 300px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
            <h4 id="modalTitle" style="margin-top:0;">Confirm</h4>
            <p id="modalText">Are you sure?</p>
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                <button id="modalConfirmBtn" class="button_submit" style="background:#dc3545; color:white;">Confirm</button>
                <button onclick="closeModal()" class="button_submit">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        var confirmCallback = null;
        function showConfirm(title, text, callback) {
            document.getElementById("modalTitle").innerText = title;
            document.getElementById("modalText").innerText = text;
            confirmCallback = callback;
            document.getElementById("mohaaModal").style.display = "flex";
        }
        function closeModal() {
            document.getElementById("mohaaModal").style.display = "none";
            confirmCallback = null;
        }
        document.getElementById("modalConfirmBtn").onclick = function() {
            if (confirmCallback) confirmCallback();
            closeModal();
        };
        // Close on outside click
        window.onclick = function(event) {
            var modal = document.getElementById("mohaaModal");
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>';
}
?>
