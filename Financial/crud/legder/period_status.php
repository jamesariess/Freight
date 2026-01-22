<?php
require_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
header('Content-Type: application/json');

try {
    $currentYear = date('Y');
    $currentMonth = date('n');

    $stmt = $pdo->prepare("SELECT status FROM ledger.periods WHERE year = :year AND month = :month");
    $stmt->execute([':year' => $currentYear, ':month' => $currentMonth]);
    $status = $stmt->fetchColumn();

    if (!$status) $status = 'Open';

    echo json_encode(['status' => $status]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>