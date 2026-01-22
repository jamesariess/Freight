<?php
require_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
header('Content-Type: application/json');

$accountID = $_GET['accountID'] ?? null;

if (!$accountID) {
    echo json_encode(['error' => 'Missing accountID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.journalID,
            e.date,
            e.description,
            d.debit,
            d.credit
        FROM ledger.details d
        INNER JOIN ledger.entries e ON e.journalID = d.journalID
        WHERE d.accountID = ?
        ORDER BY e.date ASC
    ");
    $stmt->execute([$accountID]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($transactions);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
