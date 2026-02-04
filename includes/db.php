<?php

// Database connection settings
// Adjust these values to match your local MySQL setup.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'user';

// Disable default exception handling to prevent fatal errors on table creation checks
mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    die('Failed to connect to MySQL: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Ensure proper character set
if (! $mysqli->set_charset('utf8mb4')) {
    // Not fatal, but useful to know
    error_log('Error loading character set utf8mb4: ' . $mysqli->error);
}

// Disable foreign key checks temporarily for table creation
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_roll (roll_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_teams_sql)) {
    error_log('Error creating teams table: ' . $mysqli->error);
}

// Team Members table
$create_team_members_sql = "CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    member_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_team_id (team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$mysqli->query($create_team_members_sql)) {
    error_log('Error creating team_members table: ' . $mysqli->error);
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