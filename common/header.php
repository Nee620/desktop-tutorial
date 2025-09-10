<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gods Arena</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
            background-color: #111827;
            color: #f3f4f6;
            overflow-x: hidden;
        }
        .no-zoom { touch-action: manipulation; }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans leading-normal tracking-normal no-zoom">

    <?php if (basename($_SERVER['PHP_SELF']) != 'login.php' && isset($_SESSION['user_id'])): ?>
    <header class="bg-gray-800 p-4 flex justify-between items-center sticky top-0 z-50 shadow-lg">
        <h1 class="text-xl font-bold text-yellow-400">Gods Arena</h1>
        <div class="bg-gray-700 px-3 py-1 rounded-full text-sm">
            <i class="fas fa-wallet text-yellow-400"></i>
            <span><?php echo format_inr($user_wallet_balance); ?></span>
        </div>
    </header>
    <?php endif; ?>

    <main class="p-4 pb-24"> <!-- Padding bottom to avoid overlap with bottom nav -->
        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>