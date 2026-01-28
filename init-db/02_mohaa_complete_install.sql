-- =============================================================================
-- MOHAA Stats Plugin - Complete Installation SQL
-- Run this in phpMyAdmin AFTER SMF is installed
-- Database: smf
-- 
-- THIS MATCHES THE PHP CODE IN opm-stats-smf-integration/smf-mohaa/Sources/
-- =============================================================================

-- =============================================================================
-- PART 1: SMF Integration Hooks (smf_settings table)
-- =============================================================================

-- Delete any existing MOHAA entries first
DELETE FROM smf_settings WHERE variable LIKE 'mohaa%';
DELETE FROM smf_settings WHERE variable = 'integrate_pre_include' AND value LIKE '%MohaaStats%';
DELETE FROM smf_settings WHERE variable = 'integrate_actions' AND value LIKE '%MohaaStats%';
DELETE FROM smf_settings WHERE variable = 'integrate_menu_buttons' AND value LIKE '%MohaaStats%';
DELETE FROM smf_settings WHERE variable = 'integrate_admin_areas' AND value LIKE '%MohaaStats%';

-- Insert integration hooks
INSERT INTO smf_settings (variable, value) VALUES 
    ('integrate_pre_include', '$sourcedir/MohaaStats/MohaaStats.php'),
    ('integrate_actions', 'MohaaStats_Actions'),
    ('integrate_menu_buttons', 'MohaaStats_MenuButtons'),
    ('integrate_admin_areas', 'MohaaStats_AdminAreas'),
    ('mohaa_stats_installed', '1'),
    ('mohaa_stats_enabled', '1'),
    ('mohaa_stats_api_url', 'http://77.42.64.214:8084/api/v1'),
    ('mohaa_stats_cache_ttl', '300');

-- =============================================================================
-- PART 2: Core Identity Tables
-- =============================================================================

-- Player identity linking (GUID to SMF member)
CREATE TABLE IF NOT EXISTS smf_mohaa_identities (
    id_identity INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    player_guid VARCHAR(64) NOT NULL UNIQUE,
    player_name VARCHAR(100),
    linked_date INT UNSIGNED DEFAULT 0,
    verified TINYINT(1) DEFAULT 0,
    INDEX idx_member (id_member),
    INDEX idx_guid (player_guid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Claim codes for linking
CREATE TABLE IF NOT EXISTS smf_mohaa_claim_codes (
    id_claim INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    claim_code VARCHAR(16) NOT NULL UNIQUE,
    created_at INT UNSIGNED DEFAULT 0,
    expires_at INT UNSIGNED DEFAULT 0,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_member (id_member),
    INDEX idx_code (claim_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Device tokens for in-game auth
CREATE TABLE IF NOT EXISTS smf_mohaa_device_tokens (
    id_token INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    user_code VARCHAR(16) NOT NULL UNIQUE,
    device_code VARCHAR(64),
    created_at INT UNSIGNED DEFAULT 0,
    expires_at INT UNSIGNED DEFAULT 0,
    verified TINYINT(1) DEFAULT 0,
    INDEX idx_member (id_member),
    INDEX idx_device (device_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PART 3: Achievement System (matches MohaaAchievements.php)
-- =============================================================================

DROP TABLE IF EXISTS smf_mohaa_achievement_defs;
DROP TABLE IF EXISTS smf_mohaa_player_achievements;
DROP TABLE IF EXISTS smf_mohaa_achievement_progress;

-- Achievement definitions
-- PHP uses: id_achievement, code, name, description, category, tier (INT), icon, requirement_type, requirement_value, points, is_hidden, sort_order
CREATE TABLE smf_mohaa_achievement_defs (
    id_achievement INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    tier INT DEFAULT 1,
    points INT DEFAULT 10,
    category VARCHAR(50),
    requirement_type VARCHAR(50),
    requirement_value INT,
    is_hidden TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_tier (tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Player achievement unlocks
-- PHP uses: id_achievement, id_member, unlocked_date
CREATE TABLE smf_mohaa_player_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    id_achievement INT NOT NULL,
    unlocked_date INT UNSIGNED DEFAULT 0,
    UNIQUE KEY unique_unlock (id_member, id_achievement),
    INDEX idx_member (id_member),
    INDEX idx_achievement (id_achievement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Achievement progress tracking
-- PHP uses: id_achievement, id_member, current_progress
CREATE TABLE smf_mohaa_achievement_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    id_achievement INT NOT NULL,
    current_progress INT DEFAULT 0,
    UNIQUE KEY unique_progress (id_member, id_achievement),
    INDEX idx_member (id_member)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PART 4: Teams System (matches MohaaTeams.php)
-- =============================================================================

DROP TABLE IF EXISTS smf_mohaa_teams;
DROP TABLE IF EXISTS smf_mohaa_team_members;
DROP TABLE IF EXISTS smf_mohaa_team_invites;
DROP TABLE IF EXISTS smf_mohaa_team_matches;
DROP TABLE IF EXISTS smf_mohaa_team_challenges;

-- Teams
-- PHP uses: id_team, team_name, team_tag, description, logo_url, website, id_captain, founded_date, status, rating, wins, losses, recruiting
CREATE TABLE smf_mohaa_teams (
    id_team INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL UNIQUE,
    team_tag VARCHAR(10) DEFAULT '',
    description TEXT,
    logo_url VARCHAR(255) DEFAULT '',
    website VARCHAR(255) DEFAULT '',
    id_captain INT UNSIGNED DEFAULT 0,
    founded_date INT UNSIGNED DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    rating INT DEFAULT 1000,
    wins INT UNSIGNED DEFAULT 0,
    losses INT UNSIGNED DEFAULT 0,
    recruiting TINYINT UNSIGNED DEFAULT 0,
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team members
-- PHP uses: id_team, id_member, role, joined_date, status
CREATE TABLE smf_mohaa_team_members (
    id_team INT UNSIGNED NOT NULL,
    id_member INT UNSIGNED NOT NULL,
    role VARCHAR(20) DEFAULT 'member',
    joined_date INT UNSIGNED DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    PRIMARY KEY (id_team, id_member),
    INDEX idx_member (id_member)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team invites
-- PHP uses: id_invite, id_team, id_member, id_inviter, invite_type, status, created_date
CREATE TABLE smf_mohaa_team_invites (
    id_invite INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_team INT UNSIGNED NOT NULL,
    id_member INT UNSIGNED NOT NULL,
    id_inviter INT UNSIGNED NOT NULL,
    invite_type VARCHAR(20) DEFAULT 'invite',
    status VARCHAR(20) DEFAULT 'pending',
    created_date INT UNSIGNED DEFAULT 0,
    INDEX idx_team (id_team),
    INDEX idx_member (id_member)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team matches (history)
-- PHP uses: id_match, id_team, id_opponent, match_date, result, map, score_us, score_them
CREATE TABLE smf_mohaa_team_matches (
    id_match INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_team INT UNSIGNED NOT NULL,
    id_opponent INT UNSIGNED NOT NULL,
    match_date INT UNSIGNED DEFAULT 0,
    result VARCHAR(10) DEFAULT 'win',
    map VARCHAR(100) DEFAULT '',
    score_us INT DEFAULT 0,
    score_them INT DEFAULT 0,
    INDEX idx_team (id_team),
    INDEX idx_match_date (match_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team challenges
-- PHP uses: id_challenge, id_team_challenger, id_team_target, challenge_date, match_date, game_mode, map, status
CREATE TABLE smf_mohaa_team_challenges (
    id_challenge INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_team_challenger INT UNSIGNED NOT NULL,
    id_team_target INT UNSIGNED NOT NULL,
    challenge_date INT UNSIGNED DEFAULT 0,
    match_date INT UNSIGNED DEFAULT 0,
    game_mode VARCHAR(50) DEFAULT 'tdm',
    map VARCHAR(100) DEFAULT '',
    status VARCHAR(20) DEFAULT 'pending',
    INDEX idx_target (id_team_target)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PART 5: Tournaments System (matches MohaaTournaments.php)
-- =============================================================================

DROP TABLE IF EXISTS smf_mohaa_tournaments;
DROP TABLE IF EXISTS smf_mohaa_tournament_registrations;
DROP TABLE IF EXISTS smf_mohaa_tournament_matches;

-- Tournaments
-- PHP uses: id_tournament, name, description, format, game_type, max_teams, status, tournament_start, tournament_end
CREATE TABLE smf_mohaa_tournaments (
    id_tournament INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    format VARCHAR(20) DEFAULT 'single_elim',
    game_type VARCHAR(50) DEFAULT 'tdm',
    max_teams INT UNSIGNED DEFAULT 16,
    status VARCHAR(20) DEFAULT 'open',
    tournament_start INT UNSIGNED DEFAULT 0,
    tournament_end INT UNSIGNED DEFAULT 0,
    prize_description TEXT,
    rules TEXT,
    created_by INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tournament registrations
-- PHP uses: id_tournament, id_team, seed, registration_date, status
CREATE TABLE smf_mohaa_tournament_registrations (
    id_tournament INT UNSIGNED NOT NULL,
    id_team INT UNSIGNED NOT NULL,
    seed INT UNSIGNED DEFAULT 0,
    registration_date INT UNSIGNED DEFAULT 0,
    status VARCHAR(20) DEFAULT 'approved',
    PRIMARY KEY (id_tournament, id_team),
    INDEX idx_team (id_team)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tournament matches
-- PHP uses: id_match, id_tournament, round, bracket_group, id_team_a, id_team_b, score_a, score_b, winner_id, match_date
CREATE TABLE smf_mohaa_tournament_matches (
    id_match INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_tournament INT UNSIGNED NOT NULL,
    round INT UNSIGNED DEFAULT 1,
    bracket_group INT UNSIGNED DEFAULT 0,
    id_team_a INT UNSIGNED DEFAULT 0,
    id_team_b INT UNSIGNED DEFAULT 0,
    score_a INT UNSIGNED DEFAULT 0,
    score_b INT UNSIGNED DEFAULT 0,
    winner_id INT UNSIGNED DEFAULT 0,
    match_date INT UNSIGNED DEFAULT 0,
    INDEX idx_tournament (id_tournament),
    INDEX idx_team_a (id_team_a),
    INDEX idx_team_b (id_team_b)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PART 6: Seed Achievement Data
-- tier is INT (1=bronze, 2=silver, 3=gold, 4=platinum, 5=diamond)
-- =============================================================================

INSERT INTO smf_mohaa_achievement_defs (code, name, description, icon, tier, points, category, requirement_type, requirement_value, sort_order) VALUES
-- Combat achievements (tier 1-5 = bronze to diamond)
('first_blood', 'First Blood', 'Get your first kill', 'blood', 1, 10, 'combat', 'kills', 1, 1),
('soldier', 'Soldier', 'Get 100 kills', 'medal', 1, 25, 'combat', 'kills', 100, 2),
('warrior', 'Warrior', 'Get 500 kills', 'sword', 2, 50, 'combat', 'kills', 500, 3),
('veteran', 'Veteran', 'Get 1000 kills', 'star', 3, 100, 'combat', 'kills', 1000, 4),
('legend', 'Legend', 'Get 5000 kills', 'crown', 4, 250, 'combat', 'kills', 5000, 5),
('god_of_war', 'God of War', 'Get 10000 kills', 'lightning', 5, 500, 'combat', 'kills', 10000, 6),
-- Precision achievements
('sharpshooter', 'Sharpshooter', 'Get 50 headshots', 'target', 1, 25, 'precision', 'headshots', 50, 1),
('marksman', 'Marksman', 'Get 250 headshots', 'crosshair', 2, 75, 'precision', 'headshots', 250, 2),
('sniper_elite', 'Sniper Elite', 'Get 1000 headshots', 'skull', 3, 150, 'precision', 'headshots', 1000, 3),
('deadeye', 'Deadeye', 'Get 5000 headshots', 'eye', 4, 300, 'precision', 'headshots', 5000, 4),
-- Survival achievements
('survivor', 'Survivor', 'Play 10 matches', 'shield', 1, 15, 'survival', 'matches', 10, 1),
('hardened', 'Hardened', 'Play 100 matches', 'armor', 2, 50, 'survival', 'matches', 100, 2),
('immortal', 'Immortal', 'Play 500 matches', 'infinity', 3, 150, 'survival', 'matches', 500, 3),
-- Dedication achievements
('rookie', 'Rookie', 'Play for 1 hour total', 'clock', 1, 10, 'dedication', 'playtime_hours', 1, 1),
('dedicated', 'Dedicated', 'Play for 24 hours total', 'timer', 1, 25, 'dedication', 'playtime_hours', 24, 2),
('addicted', 'Addicted', 'Play for 100 hours total', 'gamepad', 2, 75, 'dedication', 'playtime_hours', 100, 3),
('no_life', 'No Life', 'Play for 500 hours total', 'zombie', 3, 200, 'dedication', 'playtime_hours', 500, 4),
-- Skill achievements
('kd_positive', 'Going Positive', 'Achieve a K/D ratio above 1.0', 'thumbsup', 1, 20, 'skill', 'kd_ratio', 1, 1),
('kd_master', 'K/D Master', 'Achieve a K/D ratio above 2.0', 'fire', 2, 75, 'skill', 'kd_ratio', 2, 2),
('unstoppable', 'Unstoppable', 'Achieve a K/D ratio above 3.0', 'rocket', 3, 150, 'skill', 'kd_ratio', 3, 3),
-- Streak achievements
('triple_kill', 'Triple Kill', 'Get 3 kills without dying', 'x3', 1, 20, 'streak', 'killstreak', 3, 1),
('rampage', 'Rampage', 'Get 5 kills without dying', 'x5', 2, 50, 'streak', 'killstreak', 5, 2),
('godlike', 'Godlike', 'Get 10 kills without dying', 'x10', 3, 100, 'streak', 'killstreak', 10, 3),
('unkillable', 'Unkillable', 'Get 20 kills without dying', 'x20', 4, 250, 'streak', 'killstreak', 20, 4);

-- =============================================================================
-- DONE! Your MOHAA Stats plugin should now be fully installed.
-- Clear SMF cache: Admin -> Maintenance -> Forum Maintenance -> Rebuild Cache
-- =============================================================================
