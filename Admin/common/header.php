<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);

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

function format_inr($amount) {
    return '₹' . number_format($amount, 2);
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gods Arena - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body { background-color: #111827; color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans">
    <?php if (basename($_SERVER['PHP_SELF']) != 'login.php' && isset($_SESSION['admin_id'])): ?>
    <header class="bg-gray-800 p-4 flex justify-between items-center sticky top-0 z-50 shadow-lg">
        <h1 class="text-xl font-bold text-yellow-400">Admin Panel</h1>
        <a href="login.php?action=logout" class="text-red-400 hover:text-red-600">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>
    <?php endif; ?>
    <main class="p-4 pb-24">
        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>