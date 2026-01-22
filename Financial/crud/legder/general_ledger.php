<?php
require_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            c.accountID,
            c.accountName,
            c.accounType,
            COALESCE(SUM(d.debit), 0) AS totalDebit,
            COALESCE(SUM(d.credit), 0) AS totalCredit,
            (COALESCE(SUM(d.debit), 0) - COALESCE(SUM(d.credit), 0)) AS balance
        FROM ledger.chartofaccount c
        LEFT JOIN ledger.details d ON c.accountID = d.accountID
        LEFT JOIN ledger.entries e ON d.journalID = e.journalID
        WHERE c.Archive != 'YES'AND e.status != 'Draft' AND (e.Archive != 'YES' OR e.Archive IS NULL)
        GROUP BY c.accountID, c.accountName, c.accounType
        ORDER BY c.accounType, c.accountName
    ");

    $ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    echo json_encode($ledger ?: []);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
