<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_tournaments = $conn->query("SELECT COUNT(*) as count FROM tournaments")->fetch_assoc()['count'];
$prize_distributed = $conn->query("SELECT SUM(prize_pool) as sum FROM tournaments WHERE status = 'Completed' AND winner_id IS NOT NULL")->fetch_assoc()['sum'] ?? 0;

$total_revenue_result = $conn->query("
    SELECT SUM(t.entry_fee * (t.commission_percentage / 100) * (SELECT COUNT(*) FROM participants p WHERE p.tournament_id = t.id)) as total 
    FROM tournaments t WHERE t.status = 'Completed'
")->fetch_assoc();
$total_revenue = $total_revenue_result['total'] ?? 0;

?>

<div class="grid grid-cols-2 gap-4">
    <div class="bg-gray-800 p-4 rounded-lg text-center">
        <h4 class="text-gray-400 text-sm">Total Users</h4>
        <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
    </div>
    <div class="bg-gray-800 p-4 rounded-lg text-center">
        <h4 class="text-gray-400 text-sm">Total Tournaments</h4>
        <p class="text-2xl font-bold"><?php echo $total_tournaments; ?></p>
    </div>
    <div class="bg-gray-800 p-4 rounded-lg text-center">
        <h4 class="text-gray-400 text-sm">Prize Distributed</h4>
        <p class="text-2xl font-bold text-green-400"><?php echo format_inr($prize_distributed); ?></p>
    </div>
    <div class="bg-gray-800 p-4 rounded-lg text-center">
        <h4 class="text-gray-400 text-sm">Total Revenue</h4>
        <p class="text-2xl font-bold text-yellow-400"><?php echo format_inr($total_revenue); ?></p>
    </div>
</div>

<div class="mt-8">
    <a href="tournament.php" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
        <i class="fas fa-plus-circle mr-2"></i>Create New Tournament
    </a>
</div>

<div class="mt-6 space-y-4">
    <a href="deposit_requests.php" class="block w-full text-left bg-gray-700 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg">
        <i class="fas fa-download mr-3 w-5 text-center"></i>Manage Deposit Requests
    </a>
    <a href="withdrawal_requests.php" class="block w-full text-left bg-gray-700 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg">
        <i class="fas fa-upload mr-3 w-5 text-center"></i>Manage Withdrawal Requests
    </a>
</div>

<?php require_once 'common/bottom.php'; ?>