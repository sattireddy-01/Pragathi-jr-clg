

<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

require_method('POST');
$data = get_json_input();

$operatorId = isset($data['operator_id']) ? (int) $data['operator_id'] : 0;
$bookingId  = isset($data['booking_id']) ? (int) $data['booking_id'] : 0;

if ($operatorId <= 0 || $bookingId <= 0) {
    send_response(false, 'operator_id and booking_id are required', null, 400);
}

try {
    $stmt = $pdo->prepare('
        UPDATE bookings
        SET operator_id = ?, status = "ACCEPTED"
        WHERE booking_id = ? AND status = "PENDING"
    ');
    $stmt->execute([$operatorId, $bookingId]);

    if ($stmt->rowCount() === 0) {
        send_response(false, 'Booking not found or not in PENDING status', null, 400);
    }

    send_response(true, 'Booking accepted successfully');
} catch (Exception $e) {
    send_response(false, 'Error accepting booking', ['error' => $e->getMessage()], 500);
}