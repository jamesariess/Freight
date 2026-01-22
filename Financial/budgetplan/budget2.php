<?php
header('Content-Type: application/json');
include_once '../utility/connection.php';


if (isset($_GET['action']) && $_GET['action'] === 'quarter_detail') {
    $quarter     = (int)($_GET['quarter'] ?? 0);
    $allocationID = $_GET['allocationID'] ?? '';
    $year        = $_GET['year'] ?? date('Y');

    if (!$allocationID || $quarter < 1 || $quarter > 4) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        exit;
    }

    $q = "
        SELECT 
            d.date,
            d.Amount,
            d.Requested_by,
            d.requestTitle
        FROM disbursment.request d
        WHERE d.allocationID = ?
          AND d.status = 'Paid'
          AND d.Archive = 'NO'
          AND QUARTER(d.date) = ?
          AND YEAR(d.date) = ?
        ORDER BY d.date DESC
    ";

    $st = $pdo->prepare($q);
    $st->execute([$allocationID, $quarter, $year]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $total = array_sum(array_column($rows, 'Amount'));

    echo json_encode([
        'status'     => 'success',
        'transactions' => $rows,
        'total'      => number_format($total, 2),
        'quarter'    => $quarter
    ]);
    exit;
}

$dept_id = $_GET['dept_id'] ?? $_GET['deptID'] ?? '';
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

if (empty($dept_id) || empty($year)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing department ID or year']);
    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT 
            db.Name AS departmentName,
            YEAR(db.DateValid) AS year,
            COALESCE(SUM(db.Amount), 0) AS totalBudget,
            COALESCE(SUM(db.UsedBudget), 0) AS UsedBudget,
            COALESCE(SUM(db.Amount) - SUM(db.UsedBudget), 0) AS RemainingBudget,
            ROUND(
                CASE 
                    WHEN COALESCE(SUM(db.Amount), 0) > 0 
                    THEN (SUM(db.UsedBudget) / SUM(db.Amount)) * 100 
                    ELSE 0 
                END, 1
            ) AS utilizationPercentage
        FROM budget.departmentbudget db
        WHERE db.Deptbudget = ? 
          AND YEAR(db.DateValid) = ?
        GROUP BY db.Deptbudget, db.Name
    ");
    $stmt->execute([$dept_id, $year]);  
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget || $budget['totalBudget'] == 0) {
        $budget = [
            'departmentName' => 'Unknown Department',
            'year' => $year,
            'totalBudget' => 0,
            'UsedBudget' => 0,
            'RemainingBudget' => 0,
            'utilizationPercentage' => 0
        ];
        $unallocatedExpenses = [];
    } else {
  
        $budget['totalBudget'] = number_format((float)$budget['totalBudget'], 2);
        $budget['UsedBudget'] = number_format((float)$budget['UsedBudget'], 2);
        $budget['RemainingBudget'] = number_format((float)$budget['RemainingBudget'], 2);

   
        $unallocStmt = $pdo->prepare("
            SELECT 
             Amount,Name
             FROM budget.departmentbudget
            WHERE Deptbudget = ? 
              AND YEAR(DateValid) = ? 
              AND Archive = 'NO'
        ");
        $unallocStmt->execute([$year, $dept_id]);  // ← FIXED ORDER
        $unallocatedExpenses = $unallocStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($unallocatedExpenses as &$exp) {
            $exp['Amount'] = number_format((float)$exp['Amount'], 2);
            $exp['AllocationDate'] = $exp['AllocationDate'] ? date('M d, Y', strtotime($exp['AllocationDate'])) : 'Unknown Date';
        }
        $histStmt = $pdo->prepare("
            SELECT c.accountName, ca.Amount, YEAR(ca.AllocationCreate) AS year
            FROM budget.costallocation ca
            JOIN ledger.chartofaccount c ON ca.AccountID = c.accountID
            WHERE ca.Deptbudget = ?
              AND YEAR(ca.AllocationCreate) < ?
            ORDER BY c.accountName, year
        ");
        $histStmt->execute([$dept_id, $year]);
        $histRows = $histStmt->fetchAll(PDO::FETCH_ASSOC);

        $historicalData = [];
        foreach ($histRows as $row) {
            $accName = $row['accountName'];
            if (!isset($historicalData[$accName])) {
                $historicalData[$accName] = [];
            }
            $historicalData[$accName][] = (float)$row['Amount'];
        }


        
    }

$overall = "
SELECT 
    l.accountName               AS AllocatedName,
    d.Name                      AS departmentName,
    co.allocationID,
    co.Amount                   AS totalbudget,
    co.Details,
    YEAR(co.yearlybudget)       AS year,
    co.usedAllocation           AS UsedBudget,
    co.Amount - co.usedAllocation AS RemainingBudget,
    ROUND(
        CASE WHEN co.Amount > 0 
             THEN (co.usedAllocation / co.Amount) * 100 
             ELSE 0 
        END, 1
    )                           AS utilizationPercentages,

    COALESCE(SUM(CASE WHEN QUARTER(req.date) = 1 THEN req.Amount ELSE 0 END), 0) AS Q1,
    COALESCE(SUM(CASE WHEN QUARTER(req.date) = 2 THEN req.Amount ELSE 0 END), 0) AS Q2,
    COALESCE(SUM(CASE WHEN QUARTER(req.date) = 3 THEN req.Amount ELSE 0 END), 0) AS Q3,
    COALESCE(SUM(CASE WHEN QUARTER(req.date) = 4 THEN req.Amount ELSE 0 END), 0) AS Q4

FROM budget.costallocation co
INNER JOIN ledger.chartofaccount     l  ON co.accountID    = l.accountID
INNER JOIN budget.departmentbudget   d  ON co.Deptbudget    = d.Deptbudget
LEFT JOIN  disbursment.request       req 
    ON req.allocationID = co.allocationID 
   AND req.status       = 'Paid' 
   AND req.Archive      = 'NO'
   AND YEAR(req.date)   = YEAR(co.yearlybudget)

WHERE YEAR(co.yearlybudget) = ? 
  AND co.Deptbudget         = ?

GROUP BY 
    co.allocationID,
    l.accountName,
    d.Name,
    co.Amount,
    co.usedAllocation,
    YEAR(co.yearlybudget)
";

$stmtdata = $pdo->prepare($overall);
$stmtdata->execute([$year, $dept_id]);
$overalldata = $stmtdata->fetchAll(PDO::FETCH_ASSOC);




$sql = "
    SELECT DISTINCT c.accountID, c.accountName
    FROM ledger.chartofaccount c
    LEFT JOIN budget.costallocation ca 
        ON ca.accountID = c.accountID
    WHERE c.accounType IN ('Expense','Assets','Liabilities')
      AND c.Archive = 'NO'
      AND c.status = 'Active'
      AND (
            ca.accountID IS NULL
            OR ca.Deptbudget = ? 
          
          )
            
    ORDER BY c.accountCode ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$dept_id]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);



echo json_encode([
    'status' => 'success',
    'budget' => $budget,
    'unallocatedExpenses' => $unallocatedExpenses ?? [],
    'hasBudget' => (float)$budget['totalBudget'] > 0,
    'accounts' => $accounts,
    'historicalData' => $historicalData ?? [],
    'overalldata'=> $overalldata
    
]);

} catch (Exception $e) {
    error_log($e->getMessage());  
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}



?>