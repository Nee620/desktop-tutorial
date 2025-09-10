<?php
// config.php
session_start();
error_reporting(E_ALL & ~E_NOTICE); // Hide notices for cleaner output

// --- DATABASE CONFIGURATION ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'gods_arena_db';

// --- Establish Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Helper Function ---
function format_inr($amount) {
    return '₹' . number_format($amount, 2);
}

// --- Fetch User Wallet Balance for Header ---
$user_wallet_balance = 0.00;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_wallet_balance = $result->fetch_assoc()['wallet_balance'];
    }
    $stmt->close();
}

// --- Message Handling ---
$message = '';
$message_type = ''; // 'success' or 'error'
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>