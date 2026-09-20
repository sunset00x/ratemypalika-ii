<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$palika_id = (int)($_GET['palika_id'] ?? 0);

if ($palika_id <= 0) {
    echo json_encode(['error' => 'Invalid Palika ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT total_wards FROM palikas WHERE id = ?");
$stmt->execute([$palika_id]);
$res = $stmt->fetch();

if ($res) {
    echo json_encode(['total_wards' => (int)$res['total_wards']]);
} else {
    echo json_encode(['error' => 'Palika not found']);
}