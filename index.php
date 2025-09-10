<?php
require_once 'common/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- HANDLE TOURNAMENT JOINING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_tournament'])) {
    $tournament_id = (int)$_POST['tournament_id'];
    $slot_number = (int)$_POST['slot_number'];
    
    // Fetch tournament details
    $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ? AND status = 'Upcoming'");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($tournament) {
        // Check if user has already joined
        $stmt = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
        $stmt->bind_param("ii", $user_id, $tournament_id);
        $stmt->execute();
        $stmt->store_result();
        $has_joined = $stmt->num_rows > 0;
        $stmt->close();

        // Check wallet balance
        if ($user_wallet_balance >= $tournament['entry_fee'] && !$has_joined) {
            $conn->begin_transaction();
            try {
                // Deduct entry fee
                $new_balance = $user_wallet_balance - $tournament['entry_fee'];
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $stmt->bind_param("di", $new_balance, $user_id);
                $stmt->execute();
                
                // Record transaction
                $desc = "Entry fee for " . $tournament['title'];
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Debit', ?)");
                $stmt->bind_param("ids", $user_id, $tournament['entry_fee'], $desc);
                $stmt->execute();
                
                // Create Team & Add Participant
                $team_name = ($_POST['team_name']) ? trim($_POST['team_name']) : 'Team ' . $user_id . '_' . $tournament_id;
                $in_game_name = ($_POST['in_game_name']) ? trim($_POST['in_game_name']) : null;
                $in_game_uid = ($_POST['in_game_uid']) ? trim($_POST['in_game_uid']) : null;

                $stmt = $conn->prepare("INSERT INTO teams (tournament_id, team_name, slot_number) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $tournament_id, $team_name, $slot_number);
                $stmt->execute();
                $team_id = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO participants (user_id, tournament_id, team_id, in_game_name, in_game_uid) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiss", $user_id, $tournament_id, $team_id, $in_game_name, $in_game_uid);
                $stmt->execute();
                
                $conn->commit();
                $_SESSION['message'] = 'Successfully joined the tournament!';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'An error occurred: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
            }
        } elseif ($has_joined) {
             $_SESSION['message'] = 'You have already joined this tournament.';
             $_SESSION['message_type'] = 'error';
        } else {
            $_SESSION['message'] = 'Insufficient wallet balance.';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Tournament not available or already started.';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: index.php");
    exit();
}

// --- FETCH TOURNAMENTS TO DISPLAY ---
$tournaments = [];
$result = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM participants p WHERE p.tournament_id = t.id) as slots_filled FROM tournaments t WHERE t.status = 'Upcoming' ORDER BY t.match_time ASC");
while ($row = $result->fetch_assoc()) {
    $tournaments[] = $row;
}
?>

<?php require 'common/header.php'; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if (empty($tournaments)): ?>
        <p class="text-gray-400 text-center col-span-full">No upcoming tournaments found.</p>
    <?php else: ?>
    <?php foreach ($tournaments as $t): ?>
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-4">
                <h3 class="font-bold text-lg text-yellow-400"><?php echo htmlspecialchars($t['title']); ?></h3>
                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($t['game_name']); ?> - <?php echo htmlspecialchars($t['type']); ?></p>
                <p class="text-sm text-gray-300 mt-2"><i class="fas fa-clock mr-2"></i><?php echo date('d M Y, h:i A', strtotime($t['match_time'])); ?></p>
            </div>
            <div class="px-4 py-2 bg-gray-700 grid grid-cols-3 text-center text-sm">
                <div>
                    <p class="text-gray-400">Entry Fee</p>
                    <p class="font-bold"><?php echo format_inr($t['entry_fee']); ?></p>
                </div>
                <div>
                    <p class="text-gray-400">Prize Pool</p>
                    <p class="font-bold text-green-400"><?php echo format_inr($t['prize_pool']); ?></p>
                </div>
                 <div>
                    <p class="text-gray-400">Type</p>
                    <p class="font-bold"><?php echo htmlspecialchars($t['type']); ?></p>
                </div>
            </div>
            <div class="p-4">
                <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
                    <div class="bg-yellow-500 h-2.5 rounded-full" style="width: <?php echo ($t['slots_filled'] / $t['total_slots']) * 100; ?>%"></div>
                </div>
                <p class="text-xs text-center text-gray-400"><?php echo $t['slots_filled']; ?> / <?php echo $t['total_slots']; ?> Slots Filled</p>
            </div>
            <div class="p-4 bg-gray-800">
                <?php if ($t['slots_filled'] >= $t['total_slots']): ?>
                    <button class="w-full bg-red-600 text-white font-bold py-2 px-4 rounded cursor-not-allowed" disabled>Tournament Full</button>
                <?php else: ?>
                    <button onclick="openJoinModal(<?php echo $t['id']; ?>, '<?php echo $t['type']; ?>', <?php echo $t['total_slots']; ?>)" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Join Now</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Join Tournament Modal -->
<div id="joinModal" class="modal fixed inset-0 bg-black bg-opacity-75 z-50 items-center justify-center p-4">
    <div class="bg-gray-800 rounded-lg shadow-lg w-full max-w-md max-h-full overflow-y-auto">
        <div class="p-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold">Join Tournament</h3>
            <button onclick="closeJoinModal()" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <form method="POST" action="index.php">
            <input type="hidden" id="modalTournamentId" name="tournament_id">
            <div class="p-4">
                <h4 class="font-semibold mb-2">Select a Slot</h4>
                <div id="slotGrid" class="grid grid-cols-6 gap-2 text-center">
                    <!-- Slots will be generated by JS -->
                </div>
                <input type="hidden" name="slot_number" id="selectedSlotNumber" required>
            </div>
            <div id="additionalFields" class="p-4 border-t border-gray-700 hidden">
                <!-- Fields for Solo/Duo/Squad will be inserted here -->
            </div>
            <div class="p-4 bg-gray-700">
                 <button type="submit" name="join_tournament" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded">Confirm & Pay</button>
            </div>
        </form>
    </div>
</div>

<script>
    const joinModal = document.getElementById('joinModal');
    const slotGrid = document.getElementById('slotGrid');
    const modalTournamentIdInput = document.getElementById('modalTournamentId');
    const selectedSlotNumberInput = document.getElementById('selectedSlotNumber');
    const additionalFieldsContainer = document.getElementById('additionalFields');

    async function openJoinModal(tournamentId, type, totalSlots) {
        modalTournamentIdInput.value = tournamentId;
        
        // Fetch booked slots
        const response = await fetch(`api_get_slots.php?tournament_id=${tournamentId}`);
        const bookedSlots = await response.json();

        slotGrid.innerHTML = '';
        for (let i = 1; i <= totalSlots; i++) {
            const slot = document.createElement('button');
            slot.type = 'button';
            slot.textContent = i;
            slot.className = 'p-2 rounded ';
            if (bookedSlots.includes(i)) {
                slot.className += 'bg-red-500 text-white cursor-not-allowed';
                slot.disabled = true;
            } else {
                slot.className += 'bg-gray-600 hover:bg-yellow-500';
                slot.onclick = () => selectSlot(i, type);
            }
            slotGrid.appendChild(slot);
        }
        
        additionalFieldsContainer.innerHTML = '';
        additionalFieldsContainer.classList.add('hidden');
        selectedSlotNumberInput.value = '';

        joinModal.classList.add('active');
    }

    function selectSlot(slotNumber, type) {
        // Reset previous selection
        Array.from(slotGrid.children).forEach(child => {
            if (!child.disabled) child.classList.replace('bg-yellow-500', 'bg-gray-600');
        });
        // Highlight new selection
        slotGrid.querySelector(`button:nth-child(${slotNumber})`).classList.replace('bg-gray-600', 'bg-yellow-500');

        selectedSlotNumberInput.value = slotNumber;
        
        let fieldsHtml = '';
        if (type === 'Solo') {
            fieldsHtml = `
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">In-game Name</label>
                    <input type="text" name="in_game_name" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">In-game UID</label>
                    <input type="text" name="in_game_uid" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
                </div>
            `;
        } else { // Duo or Squad
            fieldsHtml = `
                <div>
                    <label class="block text-sm font-bold mb-2">Team Name</label>
                    <input type="text" name="team_name" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
                </div>
            `;
        }
        additionalFieldsContainer.innerHTML = fieldsHtml;
        additionalFieldsContainer.classList.remove('hidden');
    }

    function closeJoinModal() {
        joinModal.classList.remove('active');
    }

    // This is a placeholder for the API call. We'll create this file next.
    // For now, let's create a dummy file to avoid errors.
</script>

<!-- Create a new file api_get_slots.php for the JS to fetch from -->
<?php
// A file named 'api_get_slots.php' should be created in the root with the following content:
/*
<?php
header('Content-Type: application/json');
require_once 'common/config.php';

$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;
$booked_slots = [];

if ($tournament_id > 0) {
    $stmt = $conn->prepare("SELECT slot_number FROM teams WHERE tournament_id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = (int)$row['slot_number'];
    }
    $stmt->close();
}

echo json_encode($booked_slots);
?>
*/
?>

<?php require 'common/bottom.php'; ?>