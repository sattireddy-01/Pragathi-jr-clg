

<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

require_method('POST');
$userId = require_user(); // from X-User-Id header
$data = get_json_input();

$machineId = isset($data['machine_id']) ? (int) $data['machine_id'] : 0;
$hours     = isset($data['hours']) ? (int) $data['hours'] : 0;

if ($machineId <= 0 || $hours <= 0) {
    send_response(false, 'machine_id and hours are required and must be > 0', null, 400);
}

try {
    // Get price_per_hour
    $stmt = $pdo->prepare('SELECT price_per_hour FROM machines WHERE machine_id = ?');
    $stmt->execute([$machineId]);
    $machine = $stmt->fetch();

    if (!$machine) {
        send_response(false, 'Machine not found', null, 404);
    }

    $amount = $machine['price_per_hour'] * $hours;

    $stmt = $pdo->prepare('
        INSERT INTO bookings (user_id, machine_id, hours, amount)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $machineId, $hours, $amount]);

    $bookingId = $pdo->lastInsertId();

    send_response(true, 'Booking created successfully', [
        'booking_id' => (int) $bookingId,
        'amount'     => (float) $amount,
        'status'     => 'PENDING',
    ]);
} catch (Exception $e) {
    send_response(false, 'Error creating booking', ['error' => $e->getMessage()], 500);
}