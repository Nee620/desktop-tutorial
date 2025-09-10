<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE DEPOSIT ACTION (APPROVE/REJECT) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action']; // 'approve' or 'reject'
    $deposit_id = (int)$_GET['id'];
    
    // Get deposit details
    $stmt = $conn->prepare("SELECT * FROM deposits WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    $deposit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($deposit) {
        if ($action === 'approve') {
            $conn->begin_transaction();
            try {
                // Update deposit status
                $stmt = $conn->prepare("UPDATE deposits SET status = 'Completed' WHERE id = ?");
                $stmt->bind_param("i", $deposit_id);
                $stmt->execute();

                // Add funds to user wallet
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->bind_param("di", $deposit['amount'], $deposit['user_id']);
                $stmt->execute();

                // Add transaction record
                $desc = "Deposit approved";
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Credit', ?)");
                $stmt->bind_param("ids", $deposit['user_id'], $deposit['amount'], $desc);
                $stmt->execute();
                
                $conn->commit();
                $_SESSION['message'] = 'Deposit approved and funds added.';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'An error occurred during approval.';
                $_SESSION['message_type'] = 'error';
            }
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE deposits SET status = 'Rejected' WHERE id = ?");
            $stmt->bind_param("i", $deposit_id);
            $stmt->execute();
            $_SESSION['message'] = 'Deposit rejected.';
            $_SESSION['message_type'] = 'success';
        }
    }
    header("Location: deposit_requests.php");
    exit();
}


// --- FETCH PENDING DEPOSITS ---
$requests = [];
$sql = "SELECT d.*, u.username FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.status = 'Pending' ORDER BY d.created_at ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
?>

<h2 class="text-xl font-bold mb-4">Pending Deposit Requests</h2>
<div class="space-y-4">
    <?php if (empty($requests)): ?>
        <p class="text-gray-400 text-center">No pending deposit requests.</p>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($req['username']); ?></p>
                    <p class="text-xl font-mono text-green-400"><?php echo format_inr($req['amount']); ?></p>
                    <p class="text-xs text-gray-400 mt-1">Ref ID: <?php echo htmlspecialchars($req['transaction_id']); ?></p>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="?action=approve&id=<?php echo $req['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 text-sm rounded text-center">Approve</a>
                    <a href="?action=reject&id=<?php echo $req['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 text-sm rounded text-center">Reject</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<?php require_once 'common/bottom.php'; ?>