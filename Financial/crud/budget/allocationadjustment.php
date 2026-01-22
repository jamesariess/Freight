

<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$departments = $pdo->query("
    SELECT Deptbudget, Name, Amount, COALESCE(UsedBudget,0) AS UsedBudget
    FROM budget.departmentbudget
    WHERE status='Proceed'
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department'], $_POST['title'])) {
    $dept = $_POST['department'];
    $allocationID = $_POST['title'];
    $increase = floatval($_POST['increase'] ?? 0);
    $decrease = floatval($_POST['decrease'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $successMessage = '';
    $errorMessage = '';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT Amount, Percentage, Deptbudget, accountID FROM budget.costallocation WHERE AllocationID = :id");
        $stmt->execute([':id' => $allocationID]);
        $allocation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$allocation) {
            $pdo->rollBack();
            $errorMessage = "Allocation not found.";
        } else {
            $oldPercent = floatval($allocation['Percentage']);
            $oldAmount = floatval($allocation['Amount']);
            $accountID = $allocation['accountID'];
            $newPercent = $oldPercent + $increase - $decrease;
            if ($newPercent < 0) $newPercent = 0;
            if ($newPercent > 100) $newPercent = 100;

            $stmtDept = $pdo->prepare("SELECT Amount FROM budget.departmentbudget WHERE Deptbudget = :dept");
            $stmtDept->execute([':dept' => $dept]);
            $deptData = $stmtDept->fetch(PDO::FETCH_ASSOC);
            if (!$deptData) {
                $pdo->rollBack();
                $errorMessage = "Department budget not found.";
            } else {
                $newAmount = ($newPercent / 100) * $deptData['Amount'];

                $stmtAccount = $pdo->prepare("SELECT accountName FROM budget.chartofaccount WHERE accountID = :accountID");
                $stmtAccount->execute([':accountID' => $accountID]);
                $accountName = $stmtAccount->fetchColumn() ?: 'Unknown Account';

                $insertAdj = $pdo->prepare("
                    INSERT INTO budget.allocationadjustment 
                    (allocationID, oldpercent, oldamount, Reason, Archine, ChaneDate) 
                    VALUES (:aid, :oldp, :olda, :reason, 'NO', NOW())
                ");
                $insertAdj->execute([
                    ':aid' => $allocationID,
                    ':oldp' => $oldPercent,
                    ':olda' => $oldAmount,
                    ':reason' => $reason
                ]);

                $updateAlloc = $pdo->prepare("
                    UPDATE budget.costallocation 
                    SET Percentage = :percent, Amount = :amount 
                    WHERE AllocationID = :id
                ");
                $updateAlloc->execute([
                    ':percent' => $newPercent,
                    ':amount' => $newAmount,
                    ':id' => $allocationID
                ]);

                $stmtSum = $pdo->prepare("SELECT SUM(Amount) AS totalUsed FROM budget.costallocation WHERE Deptbudget = :dept");
                $stmtSum->execute([':dept' => $dept]);
                $sumData = $stmtSum->fetch(PDO::FETCH_ASSOC);
                $usedBudget = floatval($sumData['totalUsed']);

                $updateDept = $pdo->prepare("UPDATE budget.departmentbudget SET UsedBudget = :used WHERE Deptbudget = :dept");
                $updateDept->execute([
                    ':used' => $usedBudget,
                    ':dept' => $dept
                ]);

                $auditDescription = "Adjusted allocation for account '$accountName' (AllocationID #$allocationID) from ₱" . number_format($oldAmount, 2) . " ($oldPercent%) to ₱" . number_format($newAmount, 2) . " ($newPercent%) with reason: $reason.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Allocation Adjustment', $auditDescription);

                $notifMessage = "Allocation adjusted for account '$accountName' (AllocationID #$allocationID) to ₱" . number_format($newAmount, 2) . " ($newPercent%).";
                addNotification($pdo, $user_id, 'Allocation Adjustment', $notifMessage, 'fa-coins');

                $pdo->commit();
                $successMessage = "Allocation updated and adjustment logged successfully!";
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database Error: " . $e->getMessage());
        $errorMessage = "Database Error: " . $e->getMessage();
    }
}
?>