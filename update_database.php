<?php
// update_database.php
require_once 'common/config.php';

echo "<h1>Database Migration Script</h1>";

$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS upi_id VARCHAR(100) DEFAULT NULL AFTER wallet_balance;",
    "ALTER TABLE tournaments ADD COLUMN IF NOT EXISTS type ENUM('Solo','Duo','Squad') NOT NULL DEFAULT 'Solo' AFTER game_name;",
    "ALTER TABLE tournaments ADD COLUMN IF NOT EXISTS total_slots INT(11) NOT NULL DEFAULT 48 AFTER type;",
    "ALTER TABLE tournaments ADD COLUMN IF NOT EXISTS details TEXT DEFAULT NULL AFTER match_time;",
    "CREATE TABLE IF NOT EXISTS `teams` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `tournament_id` int(11) NOT NULL,
      `team_name` varchar(100) NOT NULL,
      `slot_number` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    "CREATE TABLE IF NOT EXISTS `deposits` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `transaction_id` varchar(255) NOT NULL,
      `status` enum('Pending','Completed','Rejected') NOT NULL DEFAULT 'Pending',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    "CREATE TABLE IF NOT EXISTS `withdrawals` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `status` enum('Pending','Completed','Rejected') NOT NULL DEFAULT 'Pending',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    "CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `upi_id` varchar(100) DEFAULT NULL,
      `qr_code_path` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

$success = true;
foreach ($migrations as $query) {
    if ($conn->query($query) === TRUE) {
        echo "<p style='color:green;'>Successfully executed: <pre>" . htmlspecialchars($query) . "</pre></p>";
    } else {
        echo "<p style='color:red;'>Error executing query: <pre>" . htmlspecialchars($query) . "</pre><br><strong>Error:</strong> " . $conn->error . "</p>";
        $success = false;
    }
}

if ($success) {
    echo "<h2>Database migration completed successfully! You can now delete this file.</h2>";
} else {
    echo "<h2>Database migration encountered errors. Please check the output above.</h2>";
}

$conn->close();
?>