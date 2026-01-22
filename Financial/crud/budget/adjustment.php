

<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');

date_default_timezone_set('Asia/Manila');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$departments = $pdo->query("
    SELECT MIN(Deptbudget) AS Deptbudget, Name, Amount
    FROM budget.departmentbudget
    WHERE status='Proceed'
    GROUP BY Name
    ORDER BY Name
")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['dept']) && !isset($_GET['year'])) {
    $dept = $_GET['dept'];
    $stmt = $pdo->prepare("
        SELECT DISTINCT yearlybudget 
        FROM budget.costallocation 
        WHERE Deptbudget = :dept 
        ORDER BY yearlybudget DESC
    ");
    $stmt->execute([':dept' => $dept]);
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json');
    echo json_encode($years);
    exit();
}

if (isset($_GET['dept']) && isset($_GET['year'])) {
    $dept = $_GET['dept'];
    $year = $_GET['year'];

    $stmt = $pdo->prepare("
        SELECT ca.allocationID, ca.accountID, ca.Percentage, ca.Amount, ch.accountName AS Title 
        FROM budget.costallocation ca 
        JOIN ledger.chartofaccount ch ON ca.accountID = ch.accountID 
        WHERE ca.Deptbudget = :dept AND ca.yearlybudget = :year
    ");
    $stmt->execute([':dept' => $dept, ':year' => $year]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" 
    && isset($_POST['department'], $_POST['title_increase'], $_POST['year'], $_POST['new_percent_increase'], $_POST['reason'])) {

    $dept = $_POST['department'];
    $targetID = $_POST['title_increase'];
    $decreaseID = $_POST['title_decrease'] ?? '';
    $year = $_POST['year'];
    $newTargetPercent = floatval($_POST['new_percent_increase']);
    $reason = trim($_POST['reason']);
    $successMessage = '';
    $errorMessage = '';

    if ($newTargetPercent < 0 || $newTargetPercent > 100) {
        $errorMessage = "New percentage must be between 0 and 100.";
        
    } elseif (!empty($decreaseID) && $decreaseID == $targetID) {
        $errorMessage = "Cannot use the same allocation for both.";
      
    } else {
        try {
            $pdo->beginTransaction();

            $stmtTarget = $pdo->prepare("SELECT allocationID, percentage, Amount, accountID FROM budget.costallocation WHERE allocationID = :target AND Deptbudget = :dept AND yearlybudget = :year");
            $stmtTarget->execute([':target' => $targetID, ':dept' => $dept, ':year' => $year]);
            $targetAlloc = $stmtTarget->fetch(PDO::FETCH_ASSOC);

            if (!$targetAlloc) {
                $pdo->rollBack();
                $errorMessage = "Target allocation not found.";
               
            } else {
                $stmtDept = $pdo->prepare("SELECT Amount FROM budget.departmentbudget WHERE Deptbudget = :dept");
                $stmtDept->execute([':dept' => $dept]);
                $totalBudget = floatval($stmtDept->fetchColumn());

                $oldTargetPercent = $targetAlloc['percentage'];
                $oldTargetAmount = $targetAlloc['Amount'];
                $targetAccountID = $targetAlloc['accountID'];
                $newTargetAmount = ($newTargetPercent / 100) * $totalBudget;
                $deltaPercent = $newTargetPercent - $oldTargetPercent;

                
                $stmtAccount = $pdo->prepare("SELECT accountName FROM ledger.chartofaccount WHERE accountID = :accountID");
                $stmtAccount->execute([':accountID' => $targetAccountID]);
                $targetAccountName = $stmtAccount->fetchColumn() ?: 'Unknown Account';

                if ($deltaPercent > 0) {
                    if (empty($decreaseID)) {
                        $pdo->rollBack();
                        $errorMessage = "Please select a decrease source to fund the increase.";
                
                    } else {
                        $stmtDecrease = $pdo->prepare("SELECT allocationID, percentage, Amount, accountID FROM ledger.costallocation WHERE allocationID = :decrease AND Deptbudget = :dept AND yearlybudget = :year");
                        $stmtDecrease->execute([':decrease' => $decreaseID, ':dept' => $dept, ':year' => $year]);
                        $decreaseAlloc = $stmtDecrease->fetch(PDO::FETCH_ASSOC);

                        if (!$decreaseAlloc) {
                            $pdo->rollBack();
                            $errorMessage = "Decrease allocation not found.";
                           
                        } else {
                            $oldDecreasePercent = $decreaseAlloc['percentage'];
                            $decreaseAccountID = $decreaseAlloc['accountID'];
                            if ($deltaPercent > $oldDecreasePercent) {
                                $pdo->rollBack();
                                $errorMessage = "Not enough budget in the decrease source.";
          
                            } else {
                                $newDecreasePercent = $oldDecreasePercent - $deltaPercent;
                                $newDecreaseAmount = ($newDecreasePercent / 100) * $totalBudget;

                                $stmtAccount->execute([':accountID' => $decreaseAccountID]);
                                $decreaseAccountName = $stmtAccount->fetchColumn() ?: 'Unknown Account';

                                $stmtAdj = $pdo->prepare("INSERT INTO budget.allocationadjustment (allocationID, oldpercent, oldamount, Reason, ChaneDate) VALUES (:aid, :oldp, :olda, :reason, NOW())");
                                $stmtAdj->execute([
                                    ':aid' => $targetID,
                                    ':oldp' => $oldTargetPercent,
                                    ':olda' => $oldTargetAmount,
                                    ':reason' => $reason
                                ]);
                                $stmtAdj->execute([
                                    ':aid' => $decreaseID,
                                    ':oldp' => $oldDecreasePercent,
                                    ':olda' => $decreaseAlloc['Amount'],
                                    ':reason' => $reason
                                ]);

                                $stmtUpdate = $pdo->prepare("UPDATE budget.costallocation SET Percentage = :p, Amount = :a WHERE allocationID = :id");
                                $stmtUpdate->execute([':p' => $newTargetPercent, ':a' => $newTargetAmount, ':id' => $targetID]);
                                $targetRowsAffected = $stmtUpdate->rowCount();
                                error_log("Target Update: allocationID=$targetID, Percentage=$newTargetPercent, Amount=$newTargetAmount, Rows Affected=$targetRowsAffected");

                              
                                $stmtUpdate->execute([':p' => $newDecreasePercent, ':a' => $newDecreaseAmount, ':id' => $decreaseID]);
                                $decreaseRowsAffected = $stmtUpdate->rowCount();
                                error_log("Decrease Update: allocationID=$decreaseID, Percentage=$newDecreasePercent, Amount=$newDecreaseAmount, Rows Affected=$decreaseRowsAffected");

                                if ($targetRowsAffected === 0 || $decreaseRowsAffected === 0) {
                                    $pdo->rollBack();
                                    $errorMessage = "Failed to update cost allocation(s).";
                                  
                                } else {
                               
                                    $auditDescription = "Adjusted allocation for account '$targetAccountName' (AllocationID #$targetID) from ₱" . number_format($oldTargetAmount, 2) . " ($oldTargetPercent%) to ₱" . number_format($newTargetAmount, 2) . " ($newTargetPercent%), funded by decreasing account '$decreaseAccountName' (AllocationID #$decreaseID) from ₱" . number_format($decreaseAlloc['Amount'], 2) . " ($oldDecreasePercent%) to ₱" . number_format($newDecreaseAmount, 2) . " ($newDecreasePercent%) with reason: $reason.";
                                    addAuditLog($pdo, $user_name, $role, 'Update', 'Allocation Adjustment', $auditDescription);

                                    $notifMessage = "Allocation adjusted for account '$targetAccountName' (AllocationID #$targetID) to ₱" . number_format($newTargetAmount, 2) . " ($newTargetPercent%), funded by account '$decreaseAccountName' (AllocationID #$decreaseID) to ₱" . number_format($newDecreaseAmount, 2) . " ($newDecreasePercent%).";
                                    addNotification($pdo, $user_id, 'Allocation Adjustment', $notifMessage, 'fa-coins');

                                    $pdo->commit();
                                    $successMessage = "Allocation adjusted successfully!";
                                }
                            }
                        }
                    }
                } else {
                    $stmtAdj = $pdo->prepare("INSERT INTO budget.allocationadjustment (allocationID, oldpercent, oldamount, Reason, ChaneDate) VALUES (:aid, :oldp, :olda, :reason, NOW())");
                    $stmtAdj->execute([
                        ':aid' => $targetID,
                        ':oldp' => $oldTargetPercent,
                        ':olda' => $oldTargetAmount,
                        ':reason' => $reason
                    ]);

                    $stmtUpdate = $pdo->prepare("UPDATE budget.costallocation SET Percentage = :p, Amount = :a WHERE allocationID = :id");
                    $stmtUpdate->execute([':p' => $newTargetPercent, ':a' => $newTargetAmount, ':id' => $targetID]);
                    $targetRowsAffected = $stmtUpdate->rowCount();
                    error_log("Target Update (No Decrease): allocationID=$targetID, Percentage=$newTargetPercent, Amount=$newTargetAmount, Rows Affected=$targetRowsAffected");

                    if ($targetRowsAffected === 0) {
                        $pdo->rollBack();
                        $errorMessage = "Failed to update cost allocation.";
                       
                    } else {
                        if ($deltaPercent < 0) {
                            $freedAmount = $oldTargetAmount - $newTargetAmount;
                            $stmtUsed = $pdo->prepare("UPDATE budget.departmentbudget SET Usedbudget = Usedbudget - :freed WHERE Deptbudget = :dept");
                            $stmtUsed->execute([':freed' => $freedAmount, ':dept' => $dept]);
                            $deptRowsAffected = $stmtUsed->rowCount();
                            error_log("Department Update: Deptbudget=$dept, Freed=$freedAmount, Rows Affected=$deptRowsAffected");
                        }

                        
                        $auditDescription = "Adjusted allocation for account '$targetAccountName' (AllocationID #$targetID) from ₱" . number_format($oldTargetAmount, 2) . " ($oldTargetPercent%) to ₱" . number_format($newTargetAmount, 2) . " ($newTargetPercent%) with reason: $reason.";
                        addAuditLog($pdo, $user_name, $role, 'Update', 'Allocation Adjustment', $auditDescription);

                        $notifMessage = "Allocation adjusted for account '$targetAccountName' (AllocationID #$targetID) to ₱" . number_format($newTargetAmount, 2) . " ($newTargetPercent%).";
                        addNotification($pdo, $user_id, 'Allocation Adjustment', $notifMessage, 'fa-coins');

                        $pdo->commit();
                        $successMessage = "Allocation adjusted successfully!";
                    }
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database Error: " . $e->getMessage());
            $errorMessage = "Database Error: " . $e->getMessage();
           
        }
    }
}

try {
    $sql = "
    SELECT 
        aa.allocateadjustmentID,
        ch.accountName AS Title,
        aa.oldpercent,
        aa.oldamount,
        ca.percentage AS newpercent,
        ca.Amount AS newamount,
        aa.Reason,
        aa.ChaneDate
    FROM budget.allocationadjustment aa
    JOIN budget.costallocation ca ON aa.allocationID = ca.allocationID
    JOIN ledger.chartofaccount ch ON ca.accountID = ch.accountID 
    ORDER BY aa.ChaneDate DESC";
    $stmt = $pdo->query($sql);
    $allocationAdjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching plans: " . $e->getMessage();
}
?>