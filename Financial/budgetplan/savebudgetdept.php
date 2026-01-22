<?php

header('Content-Type: application/json');
include_once '../utility/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$deptName     = trim($_POST['deptName'] ?? '');
$budgetYear   = $_POST['budgetYear'] ?? '';
$budgetAmount = floatval($_POST['budgetAmount'] ?? 0);
$budgetDetails = trim($_POST['budgetDetails'] ?? '');

if (empty($deptName) || empty($budgetYear) || $budgetAmount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required and amount must be positive.']);
    exit;
}

try {
    $pdo->beginTransaction();

  
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM budget.departmentbudget 
        WHERE Name = ? AND YEAR(DateValid) = ? AND status = 'Proceed' AND approval IN ('Approved', 'Pending')
    ");
    $checkStmt->execute([$deptName, $budgetYear]);
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("Department '$deptName' already has a budget for year $budgetYear.");
    }

    $dateValid = $budgetYear . '-01-01';

    $stmt = $pdo->prepare("
        INSERT INTO budget.departmentbudget 
        (Name, Amount, DateValid, Details, status, UsedBudget) 
        VALUES (?, ?, ?, ?, 'Proceed', 0)
    ");
    $stmt->execute([$deptName, $budgetAmount, $dateValid, $budgetDetails]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Budget created successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>