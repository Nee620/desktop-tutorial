<?php
require_once 'common/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- HANDLE DEPOSIT REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $transaction_id = trim($_POST['transaction_id']);

    if ($amount > 0 && !empty($transaction_id)) {
        $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, transaction_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $user_id, $amount, $transaction_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Deposit request submitted successfully. It will be reviewed by the admin.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to submit request.';
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Invalid amount or transaction ID.';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: wallet.php");
    exit();
}

// --- HANDLE WITHDRAWAL REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_money'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // Get user's UPI ID and balance
    $stmt = $conn->prepare("SELECT upi_id, wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($user['upi_id'])) {
        $_SESSION['message'] = 'Please add your UPI ID in the profile before withdrawing.';
        $_SESSION['message_type'] = 'error';
    } elseif ($amount > 0 && $amount <= $user['wallet_balance']) {
        // Create withdrawal request
        $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount) VALUES (?, ?)");
        $stmt->bind_param("id", $user_id, $amount);
        if ($stmt->execute()) {
             // Deduct from wallet immediately and create a debit transaction
            $conn->begin_transaction();
            try {
                $new_balance = $user['wallet_balance'] - $amount;
                $stmt_update = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $stmt_update->bind_param("di", $new_balance, $user_id);
                $stmt_update->execute();

                $desc = "Withdrawal Request";
                $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Debit', ?)");
                $stmt_trans->bind_param("ids", $user_id, $amount, $desc);
                $stmt_trans->execute();

                $conn->commit();
                 $_SESSION['message'] = 'Withdrawal request submitted successfully.';
                 $_SESSION['message_type'] = 'success';
            } catch(Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'An error occurred during transaction.';
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = 'Failed to submit withdrawal request.';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Invalid amount or insufficient balance.';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: wallet.php");
    exit();
}

// --- FETCH TRANSACTIONS ---
$transactions = [];
$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

// Fetch Admin UPI details
$settings = $conn->query("SELECT upi_id, qr_code_path FROM settings WHERE id=1")->fetch_assoc();
?>

<?php require 'common/header.php'; ?>

<!-- Main Balance Card -->
<div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg shadow-lg p-6 text-center mb-6">
    <p class="text-sm opacity-75">Current Balance</p>
    <p class="text-4xl font-bold"><?php echo format_inr($user_wallet_balance); ?></p>
</div>

<!-- Action Buttons -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <button onclick="openModal('addMoneyModal')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center space-x-2">
        <i class="fas fa-plus-circle"></i>
        <span>Add Money</span>
    </button>
    <button onclick="openModal('withdrawModal')" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center space-x-2">
         <i class="fas fa-arrow-circle-down"></i>
        <span>Withdraw</span>
    </button>
</div>

<!-- Transaction History -->
<h3 class="text-lg font-semibold mb-4">Transaction History</h3>
<div class="space-y-3">
    <?php if (empty($transactions)): ?>
        <p class="text-gray-400 text-center">No transactions yet.</p>
    <?php else: ?>
        <?php foreach ($transactions as $tx): ?>
        <div class="bg-gray-800 p-3 rounded-lg flex justify-between items-center">
            <div>
                <p class="font-semibold"><?php echo htmlspecialchars($tx['description']); ?></p>
                <p class="text-xs text-gray-400"><?php echo date('d M Y, h:i A', strtotime($tx['created_at'])); ?></p>
            </div>
            <p class="font-bold text-lg <?php echo $tx['type'] == 'Credit' ? 'text-green-400' : 'text-red-400'; ?>">
                <?php echo $tx['type'] == 'Credit' ? '+' : '-'; ?>
                <?php echo format_inr($tx['amount']); ?>
            </p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Money Modal -->
<div id="addMoneyModal" class="modal fixed inset-0 bg-black bg-opacity-75 z-50 items-center justify-center p-4">
    <div class="bg-gray-800 rounded-lg shadow-lg w-full max-w-sm">
        <div class="p-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold">Add Money</h3>
            <button onclick="closeModal('addMoneyModal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <form method="POST" action="wallet.php">
            <div class="p-4 text-center">
                <p class="mb-2">Scan the QR code or use the UPI ID below.</p>
                <?php if ($settings && $settings['qr_code_path']): ?>
                    <img src="admin/<?php echo htmlspecialchars($settings['qr_code_path']); ?>" alt="QR Code" class="mx-auto w-48 h-48 mb-4 border border-gray-600 p-1">
                <?php else: ?>
                    <div class="mx-auto w-48 h-48 mb-4 border border-gray-600 flex items-center justify-center text-gray-500">QR Not Set</div>
                <?php endif; ?>
                <p class="font-mono bg-gray-700 p-2 rounded"><?php echo htmlspecialchars($settings['upi_id'] ?? 'UPI ID Not Set'); ?></p>
            </div>
            <div class="p-4 border-t border-gray-700">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Amount</label>
                    <input type="number" name="amount" step="0.01" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">UPI Transaction ID (Ref No.)</label>
                    <input type="text" name="transaction_id" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
                </div>
            </div>
            <div class="p-4 bg-gray-700">
                 <button type="submit" name="add_money" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="modal fixed inset-0 bg-black bg-opacity-75 z-50 items-center justify-center p-4">
    <div class="bg-gray-800 rounded-lg shadow-lg w-full max-w-sm">
        <div class="p-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold">Withdraw Money</h3>
            <button onclick="closeModal('withdrawModal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <form method="POST" action="wallet.php">
            <div class="p-4">
                <label class="block text-sm font-bold mb-2">Amount to Withdraw</label>
                <input type="number" name="amount" step="0.01" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
                <p class="text-xs text-gray-400 mt-2">The amount will be sent to your saved UPI ID. Make sure it's correct in your profile.</p>
            </div>
            <div class="p-4 bg-gray-700">
                 <button type="submit" name="withdraw_money" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Request Withdrawal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) { document.getElementById(modalId).classList.add('active'); }
    function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
</script>

<?php require 'common/bottom.php'; ?>