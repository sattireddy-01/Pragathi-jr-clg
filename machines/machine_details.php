

<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

$machineId = isset($_GET['machine_id']) ? (int) $_GET['machine_id'] : 0;

if ($machineId <= 0) {
    send_response(false, 'machine_id is required', null, 400);
}

try {
    $stmt = $pdo->prepare('
        SELECT machine_id, category_id, model_name, price_per_hour, specs, model_year, image
        FROM machines
        WHERE machine_id = ?
    ');
    $stmt->execute([$machineId]);
    $row = $stmt->fetch();

    if (!$row) {
        send_response(false, 'Machine not found', null, 404);
    }

    send_response(true, 'Machine details fetched', $row);
} catch (Exception $e) {
    send_response(false, 'Error fetching machine details', ['error' => $e->getMessage()], 500);
}