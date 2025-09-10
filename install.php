<?php
// install.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- DATABASE CONFIGURATION ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'gods_arena_db';

// --- Establish Connection (without selecting DB first) ---
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Create Database ---
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql_create_db) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// --- Select The Database ---
$conn->select_db($db_name);

// --- SQL to create tables ---
$sql_tables = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `upi_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `upi_id` varchar(100) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `game_name` varchar(100) NOT NULL,
  `type` enum('Solo','Duo','Squad') NOT NULL,
  `total_slots` int(11) NOT NULL,
  `entry_fee` decimal(10,2) NOT NULL,
  `prize_pool` decimal(10,2) NOT NULL,
  `match_time` datetime NOT NULL,
  `details` text DEFAULT NULL,
  `room_id` varchar(100) DEFAULT NULL,
  `room_password` varchar(100) DEFAULT NULL,
  `status` enum('Upcoming','Live','Completed') NOT NULL DEFAULT 'Upcoming',
  `winner_id` int(11) DEFAULT NULL,
  `commission_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tournament_id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `slot_number` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `in_game_name` varchar(100) DEFAULT NULL,
  `in_game_uid` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `status` enum('Pending','Completed','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Completed','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('Credit','Debit') NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// --- Execute Multi Query for Table Creation ---
if ($conn->multi_query($sql_tables)) {
    do {
        if ($result = $conn->store_result()) { $result->free(); }
    } while ($conn->next_result());
    echo "All tables created successfully.<br>";
} else {
    die("Error creating tables: " . $conn->error);
}

// --- Insert Default Admin ---
$admin_user = 'admin';
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);

$stmt_check = $conn->prepare("SELECT id FROM admin WHERE username = ?");
$stmt_check->bind_param("s", $admin_user);
$stmt_check->execute();
$stmt_check->store_result();
if ($stmt_check->num_rows == 0) {
    $stmt_insert = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $admin_user, $admin_pass);
    if ($stmt_insert->execute()) {
        echo "Default admin user created successfully (admin/admin123).<br>";
    } else {
        echo "Error creating admin user: " . $stmt_insert->error . "<br>";
    }
    $stmt_insert->close();
} else {
    echo "Admin user already exists.<br>";
}
$stmt_check->close();

// --- Insert Default Settings ---
$stmt_check_settings = $conn->prepare("SELECT id FROM settings WHERE id = 1");
$stmt_check_settings->execute();
$stmt_check_settings->store_result();
if ($stmt_check_settings->num_rows == 0) {
    $stmt_insert_settings = $conn->prepare("INSERT INTO settings (id, upi_id, qr_code_path) VALUES (1, 'your-upi-id@okaxis', NULL)");
    if ($stmt_insert_settings->execute()) {
        echo "Default settings initialized.<br>";
    } else {
        echo "Error initializing settings: " . $stmt_insert_settings->error . "<br>";
    }
    $stmt_insert_settings->close();
} else {
    echo "Settings already initialized.<br>";
}
$stmt_check_settings->close();

$conn->close();

echo "<h2>Installation Complete!</h2>";
echo "<p style='color:red; font-weight:bold;'>Please delete this file (install.php) for security reasons.</p>";
echo '<a href="login.php" style="display:inline-block; padding: 10px 20px; background-color: #007BFF; color: white; text-decoration: none; border-radius: 5px;">Go to User Login</a><br><br>';
echo '<a href="admin/login.php" style="display:inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">Go to Admin Login</a>';

?>