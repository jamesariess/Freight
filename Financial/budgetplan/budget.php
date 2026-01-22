<?php
header('Content-Type: application/json');
include_once '../utility/connection.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');


$validYearsStmt = $pdo->query("
    SELECT DISTINCT YEAR(DateValid) AS fy_year
    FROM budget.departmentbudget 
    WHERE status != 'Cancel'
    ORDER BY fy_year DESC
");
$validYears = $validYearsStmt->fetchAll(PDO::FETCH_COLUMN);

$minYear = $validYears ? min($validYears) : date('Y');
if ($year < $minYear && !empty($validYears)) {
    $year = $minYear;
}
try {
  

    $sql = "
        SELECT
            COUNT(DISTINCT Name) AS totalDepartment,
            IFNULL(SUM(CASE WHEN status = 'Proceed' AND DateValid = :year1 THEN Amount END), 0) AS totalApproved,
            IFNULL(SUM(CASE WHEN status = 'Cancel' AND DateValid = :year2 THEN Amount END), 0) AS totalCancelled,
            IFNULL(SUM(CASE WHEN status = 'Proceed' THEN Amount END), 0) AS totalBudget
        FROM budget.departmentbudget
        WHERE status != 'Cancel'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'year1' => $year,
        'year2' => $year
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);


   $stmtCashOnHand = $pdo->query("
    SELECT IFNULL(SUM(jd.debit) - SUM(jd.credit), 0) AS cashOnHand
    FROM ledger.details jd
    JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
    WHERE c.accountName = 'Cash on Hand' 
");
$cashOnHand = $stmtCashOnHand->fetchColumn();

$stmtBankBalance = $pdo->query("
    SELECT IFNULL(SUM(f.Amount - f.UsedAmount - COALESCE(f.Transfer, 0)), 0) AS bankBalance
    FROM ledger.funds f
    WHERE f.Archive = 'NO'
");
$bankBalance = $stmtBankBalance->fetchColumn();

$totalGLCash = $cashOnHand + $bankBalance;



$overalldata = "
    SELECT 
        Name AS departmentName,
        Deptbudget,
        approval,
        YEAR(DateValid) AS year,
        SUM(Amount) AS totalBudget,
        SUM(UsedBudget) AS UsedBudget,
        SUM(Amount) - SUM(UsedBudget) AS RemainingBudget,
        ROUND(
            CASE 
                WHEN SUM(Amount) > 0 
                THEN (SUM(UsedBudget) / SUM(Amount)) * 100 
                ELSE 0 
            END, 1
        ) AS utilizationPercentage
    FROM budget.departmentbudget 
    WHERE status != 'Cancel' 
    AND YEAR(DateValid) = :year
    AND approval != 'Rejected'
    GROUP BY Name
    ORDER BY 
    Name ASC,
    CASE approval
        WHEN 'Pending'  THEN 2
        WHEN 'Approved' THEN 1
        ELSE 3
    END;
";

$stmtOverall = $pdo->prepare($overalldata);
$stmtOverall->execute([':year' => $year]);
$overallresult = $stmtOverall->fetchAll(PDO::FETCH_ASSOC);


$allocationsumary = "
    SELECT
        COALESCE(SUM(Amount), 0) AS totalBudgetss,
        COALESCE(SUM(UsedBudget), 0) AS UsedBudgetss,
        COALESCE(SUM(Amount) - SUM(UsedBudget), 0) AS RemainingBudgets,
        ROUND(
            CASE 
                WHEN SUM(Amount) > 0 
                THEN (SUM(UsedBudget) / SUM(Amount)) * 100 
                ELSE 0 
            END, 1
        ) AS utilizationPercentagess
    FROM budget.departmentbudget
    WHERE status != 'Cancel' 
      AND YEAR(DateValid) = :year
";

$stmtSummary = $pdo->prepare($allocationsumary);
$stmtSummary->execute([':year' => $year]);
$summaryresult = $stmtSummary->fetch(PDO::FETCH_ASSOC);



$fiscalYearsQuery = "
    SELECT 
        YEAR(DateValid) AS fy_year,
        
        COUNT(DISTINCT Name) AS department_count,
        MAX(CASE 
            WHEN YEAR(DateValid) = YEAR(CURDATE()) THEN 'Active'
            WHEN YEAR(DateValid) = YEAR(CURDATE()) - 1 THEN 'Closed'
            ELSE 'Archived'
        END) AS status
    FROM budget.departmentbudget
    WHERE status != 'Cancel'
    GROUP BY YEAR(DateValid)
    ORDER BY fy_year DESC
";

$stmtFY = $pdo->query($fiscalYearsQuery);
$fiscalYears = $stmtFY->fetchAll(PDO::FETCH_ASSOC);

$totalAllocatedQuery = "
    SELECT COALESCE(SUM(Amount), 0) AS totalAllocatedThisYear
    FROM budget.departmentbudget
    WHERE status = 'Proceed' 
      AND YEAR(DateValid) = :year
";

$stmtTotal = $pdo->prepare($totalAllocatedQuery);
$stmtTotal->execute([':year' => $year]);
$totalAllocatedThisYear = (float)($stmtTotal->fetchColumn() ?? 0);

$approvedQuery = "
    SELECT COALESCE(SUM(Amount), 0) AS totalApprovedThisYear
    FROM budget.departmentbudget
    WHERE status = 'Proceed' 
      AND approval = 'Approved'
      AND YEAR(DateValid) = :year
";

$stmtApproved = $pdo->prepare($approvedQuery);
$stmtApproved->execute([':year' => $year]);
$totalApprovedThisYear = (float)($stmtApproved->fetchColumn() ?? 0);


$rejectedQuery = "
    SELECT COALESCE(SUM(Amount), 0) AS totalRejectedThisYear
    FROM budget.departmentbudget
    WHERE status = 'Proceed' 
      AND approval = 'Rejected'
      AND YEAR(DateValid) = :year
";

$stmtRejected = $pdo->prepare($rejectedQuery);
$stmtRejected->execute([':year' => $year]);
$totalRejectedThisYear = (float)($stmtRejected->fetchColumn() ?? 0);


$deptQuery = "
SELECT DISTINCT
    Name AS DepartmentName,
    approval,
    Details AS DepartmentDetails
FROM budget.departmentbudget
WHERE status = 'Proceed' AND approval = 'Approved'
ORDER BY Name ASC;

";

$stmtDept = $pdo->prepare($deptQuery);
$stmtDept->execute();
$departments = $stmtDept->fetchAll(PDO::FETCH_ASSOC);

 echo json_encode([
        'status' => 'success',
        'year' => $year,
        'data' => $result,
        'cashOnHand' =>(float)$totalGLCash,
        'overalldata' => $overallresult,
        'summarydata' => $summaryresult,
        'fiscalYears' => $fiscalYears,
'totalApprovedThisYear'=> $totalApprovedThisYear,     // ← NEW - this is what JS really needs
    'totalRejectedThisYear'=> $totalRejectedThisYear,     // ← optional, nice to have
    'totalAllocatedThisYear' => (float)$totalAllocatedThisYear,


    'departments' => $departments,
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}


?>