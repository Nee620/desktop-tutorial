<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all users
$users = [];
$result = $conn->query("SELECT id, username, email, wallet_balance, upi_id, created_at FROM users ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<h2 class="text-xl font-bold mb-4">User Management</h2>

<div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-2">Username</th>
                    <th class="px-4 py-2">Wallet</th>
                    <th class="px-4 py-2">UPI ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr class="border-b border-gray-700">
                    <td class="px-4 py-2 font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="px-4 py-2 text-green-400 font-mono"><?php echo format_inr($user['wallet_balance']); ?></td>
                    <td class="px-4 py-2 text-gray-400"><?php echo htmlspecialchars($user['upi_id'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>