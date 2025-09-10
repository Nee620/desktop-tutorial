<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE WITHDRAWAL ACTION (MARK AS COMPLETED) ---
if (isset($_GET['action']) && $_GET['action'] == 'complete' && isset($_GET['id'])) {
    $withdrawal_id = (int)$_GET['id'];

    $stmt = $conn->prepare("UPDATE withdrawals SET status = 'Completed' WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $withdrawal_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = 'Withdrawal marked as completed.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update status or already completed.';
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
    header("Location: withdrawal_requests.php");
    exit();
}

// --- FETCH PENDING WITHDRAWALS ---
$requests = [];
$sql = "SELECT w.*, u.username, u.upi_id FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'Pending' ORDER BY w.created_at ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
?>

<h2 class="text-xl font-bold mb-4">Pending Withdrawal Requests</h2>
<div class="space-y-4">
    <?php if (empty($requests)): ?>
        <p class="text-gray-400 text-center">No pending withdrawal requests.</p>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($req['username']); ?></p>
                    <p class="text-xl font-mono text-red-400"><?php echo format_inr($req['amount']); ?></p>
                    <p class="text-sm text-yellow-300 mt-1">UPI: <?php echo htmlspecialchars($req['upi_id']); ?></p>
                </div>
                <div>
                    <a href="?action=complete&id=<?php echo $req['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center" onclick="return confirm('Confirm you have sent the money manually?')">Mark as Paid</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'common/bottom.php'; ?>