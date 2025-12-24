

<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

require_method('POST');
$data = get_json_input();

$bookingId  = isset($data['booking_id']) ? (int) $data['booking_id'] : 0;
$operatorId = isset($data['operator_id']) ? (int) $data['operator_id'] : 0;
$rating     = isset($data['rating']) ? (int) $data['rating'] : null;
$feedback   = isset($data['feedback']) ? trim($data['feedback']) : null;

if ($bookingId <= 0 || $operatorId <= 0) {
    send_response(false, 'booking_id and operator_id are required', null, 400);
}

try {
    $pdo->beginTransaction();

    // Get booking
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE booking_id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $pdo->rollBack();
        send_response(false, 'Booking not found', null, 404);
    }

    // Mark booking as completed and paid (for simplicity)
    $stmt = $pdo->prepare('
        UPDATE bookings 
        SET operator_id = ?, status = "COMPLETED", payment_status = "PAID"
        WHERE booking_id = ?
    ');
    $stmt->execute([$operatorId, $bookingId]);

    // Add operator earnings
    $stmt = $pdo->prepare('
        INSERT INTO operator_earnings (operator_id, booking_id, amount)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$operatorId, $bookingId, $booking['amount']]);

    // Insert rating if provided
    if ($rating !== null) {
        if ($rating < 1 || $rating > 5) {
            $pdo->rollBack();
            send_response(false, 'Rating must be between 1 and 5', null, 400);
        }

        $stmt = $pdo->prepare('
            INSERT INTO ratings (booking_id, user_id, operator_id, rating, feedback)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $bookingId,
            $booking['user_id'],
            $operatorId,
            $rating,
            $feedback,
        ]);
    }

    $pdo->commit();

    send_response(true, 'Booking completed successfully');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_response(false, 'Error completing booking', ['error' => $e->getMessage()], 500);
}