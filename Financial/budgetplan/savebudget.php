<?php
header('Content-Type: application/json');
include_once '../utility/connection.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Handle approval update (unchanged)
$action = $input['action'] ?? null;
if ($action === 'updateApproval') {
    $deptBudgetID = $input['deptBudgetID'] ?? null;
    $approval     = $input['approval'] ?? null;

    if (!$deptBudgetID || !in_array($approval, ['Approved', 'Rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid approval request']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE budget.departmentbudget SET approval = ? WHERE Deptbudget = ?");
        $stmt->execute([$approval, $deptBudgetID]);
        echo json_encode(['status' => 'success', 'message' => "Budget successfully {$approval}"]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}


$deptBudgetID = $input['deptBudgetID'] ?? null;
$year         = $input['year'] ?? date('Y');
$allocations  = $input['allocations'] ?? [];

if (!$deptBudgetID || !is_numeric($deptBudgetID) || empty($allocations)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data: DeptBudgetID or allocations']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO budget.costallocation 
        (Deptbudget, accountID, Amount, percentage, yearlybudget, AllocationCreate, Status, Details)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Activate', ?)
        ON DUPLICATE KEY UPDATE
            Amount     = VALUES(Amount),
            percentage = VALUES(percentage),
            Details    = VALUES(Details),
            Status     = 'Activate'
    ");

    $savedCount = 0;
    $totalAmount = 0;

    foreach ($allocations as $alloc) {
        $accountID  = (int)($alloc['accountID'] ?? 0);
        $amount     = (float)($alloc['amount'] ?? 0);
        $percentage = (float)($alloc['percentage'] ?? 0);
        $reason     = $alloc['reason'] ?? 'No reason provided';

        if ($accountID <= 0 || $amount <= 0) {
            continue;
        }

        $stmt->execute([
            $deptBudgetID,
            $accountID,
            $amount,
            $percentage,
            $year,
            $reason         
        ]);

        $savedCount++;
        $totalAmount += $amount;
    }

    $pdo->commit();

    echo json_encode([
        'status'           => 'success',
        'message'          => "Successfully saved $savedCount allocations!",
        'total_saved_amount' => number_format($totalAmount, 2),
        'records_saved'    => $savedCount
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Allocation Save Error: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}