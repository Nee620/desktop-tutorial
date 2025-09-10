<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$edit_mode = false;
$tournament_data = ['id' => '', 'title' => '', 'game_name' => '', 'type' => 'Solo', 'total_slots' => 48, 'entry_fee' => '', 'prize_pool' => '', 'match_time' => '', 'commission_percentage' => 10, 'details' => ''];

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Tournament deleted successfully.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to delete tournament.';
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
    header("Location: tournament.php");
    exit();
}

// --- HANDLE ADD/EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $game_name = trim($_POST['game_name']);
    $type = $_POST['type'];
    $total_slots = (int)$_POST['total_slots'];
    $entry_fee = (float)$_POST['entry_fee'];
    $prize_pool = (float)$_POST['prize_pool'];
    $match_time = $_POST['match_time'];
    $commission = (float)$_POST['commission_percentage'];
    $details = trim($_POST['details']);

    if ($id > 0) { // Update
        $stmt = $conn->prepare("UPDATE tournaments SET title=?, game_name=?, type=?, total_slots=?, entry_fee=?, prize_pool=?, match_time=?, commission_percentage=?, details=? WHERE id=?");
        $stmt->bind_param("sssiddisi", $title, $game_name, $type, $total_slots, $entry_fee, $prize_pool, $match_time, $commission, $details, $id);
        $action = 'updated';
    } else { // Insert
        $stmt = $conn->prepare("INSERT INTO tournaments (title, game_name, type, total_slots, entry_fee, prize_pool, match_time, commission_percentage, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiddis", $title, $game_name, $type, $total_slots, $entry_fee, $prize_pool, $match_time, $commission, $details);
        $action = 'created';
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Tournament $action successfully.";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Failed to $action tournament.";
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
    header("Location: tournament.php");
    exit();
}

// --- HANDLE EDIT MODE ---
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $tournament_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}


// --- FETCH ALL TOURNAMENTS ---
$tournaments = [];
$result = $conn->query("SELECT * FROM tournaments ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $tournaments[] = $row;
}
?>

<div class="bg-gray-800 p-4 rounded-lg shadow-lg">
    <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2"><?php echo $edit_mode ? 'Edit' : 'Create'; ?> Tournament</h3>
    <form method="POST" action="tournament.php">
        <input type="hidden" name="id" value="<?php echo $tournament_data['id']; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="title" placeholder="Title" value="<?php echo htmlspecialchars($tournament_data['title']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            <input type="text" name="game_name" placeholder="Game Name" value="<?php echo htmlspecialchars($tournament_data['game_name']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            <select name="type" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3">
                <option value="Solo" <?php echo $tournament_data['type'] == 'Solo' ? 'selected' : ''; ?>>Solo</option>
                <option value="Duo" <?php echo $tournament_data['type'] == 'Duo' ? 'selected' : ''; ?>>Duo</option>
                <option value="Squad" <?php echo $tournament_data['type'] == 'Squad' ? 'selected' : ''; ?>>Squad</option>
            </select>
            <input type="number" name="total_slots" placeholder="Total Slots (e.g., 48)" value="<?php echo htmlspecialchars($tournament_data['total_slots']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            <input type="number" step="0.01" name="entry_fee" placeholder="Entry Fee" value="<?php echo htmlspecialchars($tournament_data['entry_fee']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            <input type="number" step="0.01" name="prize_pool" placeholder="Prize Pool" value="<?php echo htmlspecialchars($tournament_data['prize_pool']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            <input type="datetime-local" name="match_time" value="<?php echo date('Y-m-d\TH:i', strtotime($tournament_data['match_time'])); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            <input type="number" step="0.01" name="commission_percentage" placeholder="Commission %" value="<?php echo htmlspecialchars($tournament_data['commission_percentage']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
        </div>
        <textarea name="details" placeholder="Details/Rules..." class="w-full mt-4 bg-gray-700 border border-gray-600 rounded py-2 px-3" rows="4"><?php echo htmlspecialchars($tournament_data['details']); ?></textarea>
        <div class="mt-4 flex gap-4">
             <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded"><?php echo $edit_mode ? 'Update' : 'Create'; ?></button>
             <?php if ($edit_mode): ?>
                <a href="tournament.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Cancel</a>
             <?php endif; ?>
        </div>
    </form>
</div>

<div class="mt-8">
    <h3 class="text-lg font-semibold mb-4">All Tournaments</h3>
    <div class="space-y-3">
    <?php foreach ($tournaments as $t): ?>
        <div class="bg-gray-800 p-3 rounded-lg flex justify-between items-center">
            <div>
                <p class="font-bold"><?php echo htmlspecialchars($t['title']); ?> <span class="text-xs font-normal px-2 py-1 rounded-full bg-<?php echo $t['status'] == 'Upcoming' ? 'blue' : ($t['status'] == 'Live' ? 'red' : 'green'); ?>-600"><?php echo $t['status']; ?></span></p>
                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($t['game_name']); ?> - <?php echo date('d M, h:i A', strtotime($t['match_time'])); ?></p>
            </div>
            <div class="flex gap-2">
                <a href="manage_tournament.php?id=<?php echo $t['id']; ?>" class="bg-blue-600 px-3 py-1 rounded text-sm">Manage</a>
                <a href="?edit=<?php echo $t['id']; ?>" class="bg-yellow-600 px-3 py-1 rounded text-sm text-gray-900">Edit</a>
                <a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure?')" class="bg-red-600 px-3 py-1 rounded text-sm">Delete</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>