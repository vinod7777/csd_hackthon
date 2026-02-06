<?php


$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'user';
$DB_PORT = 3307;

mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if ($mysqli->connect_errno) {
    die('Failed to connect to MySQL: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

if (! $mysqli->set_charset('utf8mb4')) {
    
    error_log('Error loading character set utf8mb4: ' . $mysqli->error);
}

$mysqli->query('SET FOREIGN_KEY_CHECKS=0');

// ===== CREATE ALL TABLES =====

// Teams table
$create_teams_sql = "CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL,
    leader_name VARCHAR(255) NOT NULL,
    roll_number VARCHAR(50) NOT NULL UNIQUE,
    gender ENUM('male', 'female') NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255),
    residence ENUM('day-scholar', 'hostel'),
    phone_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_roll (roll_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_teams_sql)) {
    error_log('Error creating teams table: ' . $mysqli->error);
}

// Add phone_number column if it doesn't exist
$check_col = $mysqli->query("SHOW COLUMNS FROM teams LIKE 'phone_number'");
if ($check_col && $check_col->num_rows == 0) {
    $mysqli->query("ALTER TABLE teams ADD COLUMN phone_number VARCHAR(20) AFTER residence");
}

// Add address column if it doesn't exist
$check_col = $mysqli->query("SHOW COLUMNS FROM teams LIKE 'address'");
if ($check_col && $check_col->num_rows == 0) {
    $mysqli->query("ALTER TABLE teams ADD COLUMN address TEXT AFTER phone_number");
}

// Team Members table
$create_team_members_sql = "CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    member_name VARCHAR(100) NOT NULL,
    roll_number VARCHAR(50),
    email VARCHAR(255),
    phone_number VARCHAR(20),
    residence ENUM('day-scholar', 'hostel'),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_team_id (team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_team_members_sql)) {
    error_log('Error creating team_members table: ' . $mysqli->error);
}

// Add columns to team_members if they don't exist
$check_col = $mysqli->query("SHOW COLUMNS FROM team_members LIKE 'roll_number'");
if ($check_col && $check_col->num_rows == 0) {
    $mysqli->query("ALTER TABLE team_members ADD COLUMN roll_number VARCHAR(50) AFTER member_name");
    $mysqli->query("ALTER TABLE team_members ADD COLUMN email VARCHAR(255) AFTER roll_number");
    $mysqli->query("ALTER TABLE team_members ADD COLUMN phone_number VARCHAR(20) AFTER email");
    $mysqli->query("ALTER TABLE team_members ADD COLUMN residence ENUM('day-scholar', 'hostel') AFTER phone_number");
    $mysqli->query("ALTER TABLE team_members ADD COLUMN address TEXT AFTER residence");
}

// Problem Statements table
$create_problem_statements_sql = "CREATE TABLE IF NOT EXISTS problem_statements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sno INT NOT NULL,
    stmt_name VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    slot INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slot (slot),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_problem_statements_sql)) {
    error_log('Error creating problem_statements table: ' . $mysqli->error);
}

// Team Problem Statement Selection table
$create_team_ps_selection_sql = "CREATE TABLE IF NOT EXISTS team_ps_selection (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    ps_id INT UNSIGNED NOT NULL,
    selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_team (team_id),
    INDEX idx_ps (ps_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_team_ps_selection_sql)) {
    error_log('Error creating team_ps_selection table: ' . $mysqli->error);
}

// Submissions table
$create_submissions_sql = "CREATE TABLE IF NOT EXISTS submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    submission_type ENUM('github', 'drive') NOT NULL,
    submission_link VARCHAR(500) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_id (team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_submissions_sql)) {
    error_log('Error creating submissions table: ' . $mysqli->error);
}

// Admin Settings table
$create_admin_settings_sql = "CREATE TABLE IF NOT EXISTS admin_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_admin_settings_sql)) {
    error_log('Error creating admin_settings table: ' . $mysqli->error);
}

// Re-enable foreign key checks
$mysqli->query('SET FOREIGN_KEY_CHECKS=1');

?>