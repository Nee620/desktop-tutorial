<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_GET['id'])) {
    header("Location: tournament.php");
    exit();
}
$tournament_id = (int)$_GET['id'];

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Room Details
    if (isset($_POST['update_room'])) {
        $room_id = trim($_POST['room_id']);
        $room_password = trim($_POST['room_password']);
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE tournaments SET room_id = ?, room_password = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $room_id, $room_password, $status, $tournament_id);
        if ($stmt->execute()) $_SESSION['message'] = 'Room details updated.'; else $_SESSION['message'] = 'Update failed.';
        $_SESSION['message_type'] = $stmt->execute() ? 'success' : 'error';
    }
    // Declare Winner
    if (isset($_POST['declare_winner'])) {
        $winner_participant_id = (int)$_POST['winner_id'];
        
        // Get user_id from participant_id
        $stmt_p = $conn->prepare("SELECT user_id FROM participants WHERE id = ?");
        $stmt_p->bind_param("i", $winner_participant_id);
        $stmt_p->execute();
        $winner_user_id = $stmt_p->get_result()->fetch_assoc()['user_id'];
        $stmt_p->close();

        // Get prize pool
        $stmt_t = $conn->prepare("SELECT prize_pool FROM tournaments WHERE id = ?");
        $stmt_t->bind_param("i", $tournament_id);
        $stmt_t->execute();
        $prize_pool = $stmt_t->get_result()->fetch_assoc()['prize_pool'];
        $stmt_t->close();

        if ($winner_user_id && $prize_pool > 0) {
            $conn->begin_transaction();
            try {
                // Update tournament status and winner
                $stmt = $conn->prepare("UPDATE tournaments SET status = 'Completed', winner_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $winner_user_id, $tournament_id);
                $stmt->execute();

                // Add prize to winner's wallet
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->bind_param("di", $prize_pool, $winner_user_id);
                $stmt->execute();
                
                // Add transaction record
                $desc = "Prize money for winning the tournament";
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Credit', ?)");
                $stmt->bind_param("ids", $winner_user_id, $prize_pool, $desc);
                $stmt->execute();

                $conn->commit();
                $_SESSION['message'] = 'Winner declared and prize distributed successfully!';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'An error occurred: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
            }
        }
    }
    header("Location: manage_tournament.php?id=$tournament_id");
    exit();
}


// --- FETCH TOURNAMENT & PARTICIPANT DATA ---
$stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();
$stmt->close();

$participants = [];
$sql = "SELECT p.id, u.username, p.in_game_name, p.in_game_uid, t.team_name, t.slot_number
        FROM participants p
        JOIN users u ON p.user_id = u.id
        JOIN teams t ON p.team_id = t.id
        WHERE p.tournament_id = ?
        ORDER BY t.slot_number ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $participants[] = $row;
}
$stmt->close();
?>

<a href="tournament.php" class="text-sm text-yellow-400 mb-4 inline-block">&larr; Back to Tournaments</a>
<h2 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($tournament['title']); ?></h2>

<!-- Room & Status Management -->
<div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6">
    <h3 class="text-lg font-semibold mb-4">Room & Status</h3>
    <form method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="room_id" placeholder="Room ID" value="<?php echo htmlspecialchars($tournament['room_id']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3">
            <input type="text" name="room_password" placeholder="Room Password" value="<?php echo htmlspecialchars($tournament['room_password']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3">
        </div>
        <select name="status" class="w-full mt-4 bg-gray-700 border border-gray-600 rounded py-2 px-3">
            <option value="Upcoming" <?php echo $tournament['status'] == 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
            <option value="Live" <?php echo $tournament['status'] == 'Live' ? 'selected' : ''; ?>>Live</option>
            <option value="Completed" <?php echo $tournament['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
        <button type="submit" name="update_room" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Details</button>
    </form>
</div>

<!-- Winner Declaration -->
<?php if($tournament['status'] != 'Completed'): ?>
<div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6">
    <h3 class="text-lg font-semibold mb-4">Declare Winner</h3>
    <form method="POST">
        <select name="winner_id" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3 mb-4" required>
            <option value="">-- Select Winner --</option>
            <?php foreach ($participants as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo "#" . $p['slot_number'] . " - " . htmlspecialchars($p['team_name']) . " (" . htmlspecialchars($p['username']) . ")"; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="declare_winner" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Are you sure? This action is irreversible.')">Declare Winner & Distribute Prize</button>
    </form>
</div>
<?php else: ?>
    <div class="bg-green-800 text-green-200 p-4 rounded-lg mb-6 text-center">
        <p class="font-bold">This tournament is completed.</p>
    </div>
<?php endif; ?>

<!-- Participants List -->
<div class="bg-gray-800 p-4 rounded-lg shadow-lg">
    <h3 class="text-lg font-semibold mb-4">Participants (<?php echo count($participants); ?>)</h3>
    <div class="space-y-2">
        <?php foreach ($participants as $p): ?>
            <div class="bg-gray-700 p-3 rounded-md grid grid-cols-4 text-sm">
                <span class="font-bold">#<?php echo $p['slot_number']; ?></span>
                <span class="col-span-2"><?php echo htmlspecialchars($p['team_name']); ?></span>
                <span class="text-gray-400 truncate"><?php echo htmlspecialchars($p['username']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>