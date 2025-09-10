<?php
require_once 'common/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

// --- FETCH USER'S TOURNAMENTS ---
$upcoming_live = [];
$completed = [];

$sql = "SELECT t.*, tm.team_name, tm.slot_number 
        FROM tournaments t
        JOIN participants p ON t.id = p.tournament_id
        JOIN teams tm ON p.team_id = tm.id
        WHERE p.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['status'] == 'Upcoming' || $row['status'] == 'Live') {
        $upcoming_live[] = $row;
    } else {
        $completed[] = $row;
    }
}
$stmt->close();
?>

<?php require 'common/header.php'; ?>

<div class="w-full max-w-md mx-auto bg-gray-800 rounded-lg shadow-lg p-2">
    <!-- Tabs -->
    <div class="flex border-b border-gray-700">
        <a href="?tab=upcoming" class="flex-1 py-2 text-center font-semibold <?php echo $active_tab == 'upcoming' ? 'text-yellow-400 border-b-2 border-yellow-400' : 'text-gray-400'; ?>">
            Upcoming/Live
        </a>
        <a href="?tab=completed" class="flex-1 py-2 text-center font-semibold <?php echo $active_tab == 'completed' ? 'text-yellow-400 border-b-2 border-yellow-400' : 'text-gray-400'; ?>">
            Completed
        </a>
    </div>

    <!-- Upcoming/Live Content -->
    <div id="upcomingContent" class="<?php echo $active_tab == 'upcoming' ? '' : 'hidden'; ?> p-4 space-y-4">
        <?php if (empty($upcoming_live)): ?>
            <p class="text-gray-400 text-center">You haven't joined any upcoming tournaments.</p>
        <?php else: ?>
            <?php foreach ($upcoming_live as $t): ?>
            <div class="bg-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-lg text-yellow-400"><?php echo htmlspecialchars($t['title']); ?></h3>
                <p class="text-sm text-gray-300"><?php echo date('d M Y, h:i A', strtotime($t['match_time'])); ?></p>
                <div class="mt-2 text-sm grid grid-cols-2">
                    <p><span class="text-gray-400">Team:</span> <?php echo htmlspecialchars($t['team_name']); ?></p>
                    <p><span class="text-gray-400">Slot:</span> #<?php echo htmlspecialchars($t['slot_number']); ?></p>
                </div>
                <?php if ($t['status'] == 'Live' && $t['room_id']): ?>
                <div class="mt-3 bg-blue-900 p-3 rounded-lg text-center">
                    <p class="font-bold">Room ID: <span class="text-green-400"><?php echo htmlspecialchars($t['room_id']); ?></span></p>
                    <p class="font-bold">Password: <span class="text-green-400"><?php echo htmlspecialchars($t['room_password']); ?></span></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Completed Content -->
    <div id="completedContent" class="<?php echo $active_tab == 'completed' ? '' : 'hidden'; ?> p-4 space-y-4">
        <?php if (empty($completed)): ?>
            <p class="text-gray-400 text-center">No completed tournaments found.</p>
        <?php else: ?>
            <?php foreach ($completed as $t): ?>
            <div class="bg-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($t['title']); ?></h3>
                <p class="text-sm text-gray-300"><?php echo date('d M Y', strtotime($t['match_time'])); ?></p>
                <div class="mt-2">
                    <?php if ($t['winner_id'] == $user_id): ?>
                        <p class="font-bold text-green-400 text-lg">Winner <i class="fas fa-crown"></i></p>
                    <?php else: ?>
                        <p class="text-gray-400">Participated</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require 'common/bottom.php'; ?>