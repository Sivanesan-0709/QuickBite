<?php
// Tiny JSON endpoint used by orders.php to refresh order statuses without reloading the page.
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'login required']);
    exit;
}

$st = db()->prepare('SELECT id, status FROM orders WHERE user_id = ?');
$st->execute([$user['id']]);

$out = [];
foreach ($st->fetchAll() as $row) {
    $out[$row['id']] = ['status' => $row['status'], 'class' => status_class($row['status'])];
}
echo json_encode($out);
