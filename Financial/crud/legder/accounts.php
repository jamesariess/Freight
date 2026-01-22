<?php
require_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT accountID, accountName FROM ledger.chartofaccount ORDER BY accountName ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
