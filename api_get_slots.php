<?php
// api_get_slots.php
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
$conn->close();
?>