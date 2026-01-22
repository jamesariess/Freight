<?php


include_once __DIR__ . '/../../utility/connection.php';
header('Content-Type: application/json');
include_once('../../utility/head.php');

try {
    $sql = "SELECT DISTINCT bankID, bankName FROM ledger.bank WHERE Archive = 'NO' AND status = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($banks)) {
        echo json_encode(["success" => false, "message" => "No active banks found"]);
    } else {
        echo json_encode($banks);
    }
} catch (Exception $e) {
    error_log("get_banks error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>