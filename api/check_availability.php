<?php
header('Content-Type: application/json');
require_once '../session.php';

$facility_id = (int)($_POST['facility_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';

if (empty($facility_id) || empty($booking_date)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$time_slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
$available_slots = [];

foreach ($time_slots as $slot) {
    if (is_slot_available($facility_id, $booking_date, $slot)) {
        $available_slots[] = $slot;
    }
}

echo json_encode([
    'success' => true,
    'available' => count($available_slots) > 0,
    'slots' => $available_slots
]);
?>
