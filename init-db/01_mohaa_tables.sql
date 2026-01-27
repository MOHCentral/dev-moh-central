-- =============================================================================
-- MOHAA Stats Plugin Tables for SMF
-- =============================================================================
-- These tables are created after SMF is installed
-- Run via: mysql -u smf -p smf < 01_mohaa_tables.sql
-- =============================================================================

-- Player identity linking (GUID to SMF member)
CREATE TABLE IF NOT EXISTS smf_mohaa_identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    player_guid VARCHAR(64) NOT NULL UNIQUE,
    player_name VARCHAR(100),
    linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_primary TINYINT(1) DEFAULT 1,
    INDEX idx_member (id_member),
    INDEX idx_guid (player_guid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Achievement definitions
CREATE TABLE IF NOT EXISTS smf_mohaa_achievement_defs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    tier ENUM('bronze', 'silver', 'gold', 'platinum', 'diamond') DEFAULT 'bronze',
    points INT DEFAULT 10,
    category VARCHAR(50),
    requirement_type VARCHAR(50),
    requirement_value INT,
    is_hidden TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Player achievement unlocks
CREATE TABLE IF NOT EXISTS smf_mohaa_player_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0,
    UNIQUE KEY unique_unlock (id_member, achievement_id),
    INDEX idx_member (id_member),
    FOREIGN KEY (achievement_id) REFERENCES smf_mohaa_achievement_defs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teams
CREATE TABLE IF NOT EXISTS smf_mohaa_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    tag VARCHAR(10),
    description TEXT,
    logo_url VARCHAR(255),
    leader_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_leader (leader_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team members
CREATE TABLE IF NOT EXISTS smf_mohaa_team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    id_member INT NOT NULL,
    role ENUM('leader', 'officer', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_membership (id_member),
    INDEX idx_team (team_id),
    FOREIGN KEY (team_id) REFERENCES smf_mohaa_teams(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tournaments
CREATE TABLE IF NOT EXISTS smf_mohaa_tournaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    format ENUM('single_elim', 'double_elim', 'round_robin', 'swiss') DEFAULT 'single_elim',
    game_type VARCHAR(20),
    max_teams INT DEFAULT 16,
    status ENUM('draft', 'registration', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE,
    end_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tournament registrations
CREATE TABLE IF NOT EXISTS smf_mohaa_tournament_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    team_id INT NOT NULL,
    seed INT,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_entry (tournament_id, team_id),
    FOREIGN KEY (tournament_id) REFERENCES smf_mohaa_tournaments(id),
    FOREIGN KEY (team_id) REFERENCES smf_mohaa_teams(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tournament matches
CREATE TABLE IF NOT EXISTS smf_mohaa_tournament_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    round INT NOT NULL,
    match_order INT NOT NULL,
    team1_id INT,
    team2_id INT,
    team1_score INT DEFAULT 0,
    team2_score INT DEFAULT 0,
    winner_id INT,
    status ENUM('pending', 'scheduled', 'in_progress', 'completed') DEFAULT 'pending',
    scheduled_at DATETIME,
    completed_at DATETIME,
    FOREIGN KEY (tournament_id) REFERENCES smf_mohaa_tournaments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Seed initial achievements
-- =============================================================================
INSERT INTO smf_mohaa_achievement_defs (code, name, description, icon, tier, points, category, requirement_type, requirement_value) VALUES
-- Combat achievements
('first_blood', 'First Blood', 'Get your first kill', '🩸', 'bronze', 10, 'combat', 'kills', 1),
('soldier', 'Soldier', 'Get 100 kills', '🎖️', 'bronze', 25, 'combat', 'kills', 100),
('warrior', 'Warrior', 'Get 500 kills', '⚔️', 'silver', 50, 'combat', 'kills', 500),
('veteran', 'Veteran', 'Get 1000 kills', '🏅', 'gold', 100, 'combat', 'kills', 1000),
('legend', 'Legend', 'Get 5000 kills', '👑', 'platinum', 250, 'combat', 'kills', 5000),
('god_of_war', 'God of War', 'Get 10000 kills', '⚡', 'diamond', 500, 'combat', 'kills', 10000),

-- Headshot achievements
('sharpshooter', 'Sharpshooter', 'Get 50 headshots', '🎯', 'bronze', 25, 'precision', 'headshots', 50),
('marksman', 'Marksman', 'Get 250 headshots', '🔫', 'silver', 75, 'precision', 'headshots', 250),
('sniper_elite', 'Sniper Elite', 'Get 1000 headshots', '💀', 'gold', 150, 'precision', 'headshots', 1000),

-- Survival achievements
('survivor', 'Survivor', 'Play 10 matches', '🛡️', 'bronze', 15, 'survival', 'matches', 10),
('hardened', 'Hardened', 'Play 100 matches', '🏆', 'silver', 50, 'survival', 'matches', 100),
('immortal', 'Immortal', 'Play 500 matches', '♾️', 'gold', 150, 'survival', 'matches', 500),

-- KD achievements
('efficient', 'Efficient', 'Maintain 2.0 K/D ratio over 50 matches', '📈', 'silver', 75, 'skill', 'kd_ratio', 2),
('unstoppable', 'Unstoppable', 'Maintain 3.0 K/D ratio over 50 matches', '🔥', 'gold', 150, 'skill', 'kd_ratio', 3),

-- Playtime achievements  
('dedicated', 'Dedicated', 'Play for 24 hours total', '⏰', 'bronze', 25, 'dedication', 'playtime_hours', 24),
('addicted', 'Addicted', 'Play for 100 hours total', '🎮', 'silver', 75, 'dedication', 'playtime_hours', 100),
('no_life', 'No Life', 'Play for 500 hours total', '😵', 'gold', 200, 'dedication', 'playtime_hours', 500)
ON DUPLICATE KEY UPDATE name=VALUES(name);
