<?php
/**
 * MOHAA Stats - Player Identity Resolver
 *
 * This class handles resolving player GUIDs to SMF member IDs and vice versa.
 * It provides methods for looking up players across the stats system.
 *
 * @package MohaaStats
 * @version 2.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

class MohaaIdentityResolver
{
    /** @var array Local cache of GUID -> member_id mappings */
    private static $cache = [];
    
    /** @var array Local cache of member_id -> GUID mappings */
    private static $memberCache = [];
    
    /**
     * Get SMF member ID from a player GUID.
     * 
     * @param string $guid The player's game GUID
     * @return int|null The SMF member ID, or null if not found
     */
    public static function getMemberIdFromGuid($guid)
    {
        global $smcFunc;
        
        if (empty($guid)) {
            return null;
        }
        
        // Check cache first
        if (isset(self::$cache[$guid])) {
            return self::$cache[$guid];
        }
        
        $request = $smcFunc['db_query']('', '
            SELECT id_member
            FROM {db_prefix}mohaa_identities
            WHERE player_guid = {string:guid}
            LIMIT 1',
            [
                'guid' => $guid,
            ]
        );
        
        $row = $smcFunc['db_fetch_assoc']($request);
        $smcFunc['db_free_result']($request);
        
        $memberId = $row ? (int)$row['id_member'] : null;
        
        // Cache the result
        self::$cache[$guid] = $memberId;
        
        return $memberId;
    }
    
    /**
     * Get player GUID(s) from an SMF member ID.
     * A member can have multiple GUIDs linked.
     * 
     * @param int $memberId The SMF member ID
     * @return array Array of player GUIDs
     */
    public static function getGuidsFromMemberId($memberId)
    {
        global $smcFunc;
        
        if (empty($memberId)) {
            return [];
        }
        
        // Check cache first
        if (isset(self::$memberCache[$memberId])) {
            return self::$memberCache[$memberId];
        }
        
        $request = $smcFunc['db_query']('', '
            SELECT player_guid
            FROM {db_prefix}mohaa_identities
            WHERE id_member = {int:member_id}
            ORDER BY linked_date DESC',
            [
                'member_id' => $memberId,
            ]
        );
        
        $guids = [];
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $guids[] = $row['player_guid'];
        }
        $smcFunc['db_free_result']($request);
        
        // Cache the result
        self::$memberCache[$memberId] = $guids;
        
        return $guids;
    }
    
    /**
     * Resolve multiple GUIDs to member IDs in a single query.
     * More efficient than calling getMemberIdFromGuid repeatedly.
     * 
     * @param array $guids Array of player GUIDs
     * @return array Associative array of GUID => member_id (0 if not found)
     */
    public static function resolveGuids($guids)
    {
        global $smcFunc;
        
        if (empty($guids)) {
            return [];
        }
        
        // Filter out empty GUIDs and check cache
        $result = [];
        $toQuery = [];
        
        foreach ($guids as $guid) {
            if (empty($guid)) {
                continue;
            }
            
            if (isset(self::$cache[$guid])) {
                $result[$guid] = self::$cache[$guid] ?? 0;
            } else {
                $toQuery[] = $guid;
                $result[$guid] = 0; // Default to 0
            }
        }
        
        if (empty($toQuery)) {
            return $result;
        }
        
        // Query all uncached GUIDs at once
        $request = $smcFunc['db_query']('', '
            SELECT player_guid, id_member
            FROM {db_prefix}mohaa_identities
            WHERE player_guid IN ({array_string:guids})',
            [
                'guids' => $toQuery,
            ]
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $memberId = (int)$row['id_member'];
            $result[$row['player_guid']] = $memberId;
            self::$cache[$row['player_guid']] = $memberId;
        }
        $smcFunc['db_free_result']($request);
        
        return $result;
    }
    
    /**
     * Build a player link for display.
     * Returns an HTML link to the player's profile if they're linked to SMF.
     * 
     * @param string $playerName The player's in-game name
     * @param string|null $guid The player's GUID (optional)
     * @param int|null $smfId The player's SMF ID if already known (optional)
     * @return string HTML link or plain name
     */
    public static function buildPlayerLink($playerName, $guid = null, $smfId = null)
    {
        global $scripturl;
        
        // If we don't have an SMF ID but have a GUID, try to resolve it
        if ($smfId === null && !empty($guid)) {
            $smfId = self::getMemberIdFromGuid($guid);
        }
        
        // Sanitize the name for display
        $displayName = htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8');
        
        if ($smfId > 0) {
            // Player is linked - show profile link
            return sprintf(
                '<a href="%s?action=profile;u=%d" class="mohaa-player-linked">%s</a>',
                $scripturl,
                $smfId,
                $displayName
            );
        }
        
        if (!empty($guid)) {
            // Player has GUID but not linked - show stats link
            return sprintf(
                '<a href="%s?action=mohaaidentity;guid=%s" class="mohaa-player-unlinked" title="View player stats">%s</a>',
                $scripturl,
                urlencode($guid),
                $displayName
            );
        }
        
        // No identity info - just show name
        return $displayName;
    }
    
    /**
     * Get full player info including SMF member data.
     * 
     * @param string $guid The player's GUID
     * @return array|null Player info array or null if not found
     */
    public static function getPlayerInfo($guid)
    {
        global $smcFunc;
        
        if (empty($guid)) {
            return null;
        }
        
        $request = $smcFunc['db_query']('', '
            SELECT 
                i.id_identity,
                i.id_member,
                i.player_guid,
                i.player_name AS last_known_name,
                i.linked_date,
                i.verified,
                m.member_name,
                m.real_name,
                m.avatar,
                m.posts
            FROM {db_prefix}mohaa_identities AS i
            LEFT JOIN {db_prefix}members AS m ON m.id_member = i.id_member
            WHERE i.player_guid = {string:guid}
            LIMIT 1',
            [
                'guid' => $guid,
            ]
        );
        
        $row = $smcFunc['db_fetch_assoc']($request);
        $smcFunc['db_free_result']($request);
        
        if (!$row) {
            return null;
        }
        
        return [
            'guid' => $row['player_guid'],
            'smf_id' => (int)$row['id_member'],
            'last_known_name' => $row['last_known_name'],
            'linked_date' => $row['linked_date'],
            'verified' => (bool)$row['verified'],
            'member_name' => $row['member_name'],
            'real_name' => $row['real_name'],
            'avatar' => $row['avatar'],
            'posts' => (int)$row['posts'],
        ];
    }
    
    /**
     * Register a GUID to an SMF member.
     * Called when a player verifies their identity in-game.
     * 
     * @param string $guid The player's GUID
     * @param int $memberId The SMF member ID
     * @param string $playerName The player's current in-game name
     * @return bool Success
     */
    public static function linkGuidToMember($guid, $memberId, $playerName)
    {
        global $smcFunc, $modSettings;
        
        if (empty($guid) || empty($memberId)) {
            return false;
        }
        
        // Check if this member already has max identities
        $maxIdentities = isset($modSettings['mohaa_stats_max_identities']) 
            ? (int)$modSettings['mohaa_stats_max_identities'] 
            : 3;
        
        $request = $smcFunc['db_query']('', '
            SELECT COUNT(*) AS count
            FROM {db_prefix}mohaa_identities
            WHERE id_member = {int:member_id}',
            [
                'member_id' => $memberId,
            ]
        );
        $row = $smcFunc['db_fetch_assoc']($request);
        $smcFunc['db_free_result']($request);
        
        if ($row && (int)$row['count'] >= $maxIdentities) {
            return false; // Too many identities linked
        }
        
        // Insert or update the identity link
        $smcFunc['db_insert']('replace',
            '{db_prefix}mohaa_identities',
            [
                'player_guid' => 'string',
                'id_member' => 'int',
                'player_name' => 'string',
                'linked_date' => 'int',
                'verified' => 'int',
            ],
            [
                $guid,
                $memberId,
                $playerName,
                time(),
                1, // Verified since they authenticated
            ],
            ['player_guid']
        );
        
        // Clear cache
        unset(self::$cache[$guid]);
        unset(self::$memberCache[$memberId]);
        
        return true;
    }
    
    /**
     * Process event data and add SMF profile links where possible.
     * 
     * @param array $events Array of event data from the API
     * @return array Events with added SMF links
     */
    public static function enrichEventsWithProfiles($events)
    {
        if (empty($events)) {
            return [];
        }
        
        // Collect all unique GUIDs from events
        $guids = [];
        foreach ($events as $event) {
            if (!empty($event['actor_id'])) {
                $guids[] = $event['actor_id'];
            }
            if (!empty($event['target_id'])) {
                $guids[] = $event['target_id'];
            }
            if (!empty($event['player_guid'])) {
                $guids[] = $event['player_guid'];
            }
        }
        
        // Resolve all GUIDs in one query
        $guidToMember = self::resolveGuids(array_unique($guids));
        
        // Enrich events with profile links
        foreach ($events as &$event) {
            // Actor
            if (!empty($event['actor_id'])) {
                $event['actor_smf_id'] = $guidToMember[$event['actor_id']] ?? 0;
                $event['actor_link'] = self::buildPlayerLink(
                    $event['actor_name'] ?? 'Unknown',
                    $event['actor_id'],
                    $event['actor_smf_id']
                );
            }
            
            // Target
            if (!empty($event['target_id'])) {
                $event['target_smf_id'] = $guidToMember[$event['target_id']] ?? 0;
                $event['target_link'] = self::buildPlayerLink(
                    $event['target_name'] ?? 'Unknown',
                    $event['target_id'],
                    $event['target_smf_id']
                );
            }
            
            // Generic player
            if (!empty($event['player_guid'])) {
                $event['player_smf_id'] = $guidToMember[$event['player_guid']] ?? 0;
                $event['player_link'] = self::buildPlayerLink(
                    $event['player_name'] ?? 'Unknown',
                    $event['player_guid'],
                    $event['player_smf_id']
                );
            }
        }
        
        return $events;
    }
    
    /**
     * Clear the local cache.
     */
    public static function clearCache()
    {
        self::$cache = [];
        self::$memberCache = [];
    }
}
