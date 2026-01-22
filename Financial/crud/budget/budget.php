<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila'); 


$yearSums = [];
$stmtYearSums = $pdo->prepare("SELECT DateValid AS yr, IFNULL(SUM(Amount), 0) AS sum_amt FROM budget.departmentbudget WHERE status = 'Proceed' GROUP BY DateValid");
$stmtYearSums->execute();
foreach ($stmtYearSums->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $yearSums[(int)$row['yr']] = (float)$row['sum_amt'];
}


$dataByYear = [];


$years = $pdo->query("SELECT DISTINCT DateValid AS y FROM budget.departmentbudget ORDER BY y ASC")->fetchAll(PDO::FETCH_COLUMN);
foreach ($years as $year) {
    $year = (int)$year;

    $stmtTotalBudgets = $pdo->prepare("SELECT IFNULL(SUM(Amount), 0) AS total FROM budget.departmentbudget WHERE DateValid = :year AND status = 'Proceed'");
    $stmtTotalBudgets->execute([':year' => $year]);
    $totalBudgets = $stmtTotalBudgets->fetchColumn();

    $stmtApproved = $pdo->prepare("SELECT IFNULL(SUM(Amount), 0) AS approved FROM budget.departmentbudget WHERE DateValid = :year AND status = 'Proceed'");
    $stmtApproved->execute([':year' => $year]);
    $approvedBudgets = $stmtApproved->fetchColumn();

    $stmtCancelled = $pdo->prepare("SELECT IFNULL(SUM(Amount), 0) AS cancelled FROM budget.departmentbudget WHERE DateValid = :year AND status = 'Cancel'");
    $stmtCancelled->execute([':year' => $year]);
    $cancelledBudgets = $stmtCancelled->fetchColumn();

    $stmtTotalDepartments = $pdo->prepare("SELECT COUNT(DISTINCT Name) AS totalDepartments FROM budget.departmentbudget WHERE DateValid = :year AND status = 'Proceed'");
    $stmtTotalDepartments->execute([':year' => $year]);
    $totalDepartments = $stmtTotalDepartments->fetchColumn();

    $dataByYear[$year] = [
        'totalBudgets'     => '₱' . number_format($totalBudgets, 2),
        'approvedBudgets'  => '₱' . number_format($approvedBudgets, 2),
        'cancelledBudgets' => '₱' . number_format($cancelledBudgets, 2),
        'totalDepartments' => (int)$totalDepartments,
    ];
}


$stmtAllTotal = $pdo->query("SELECT IFNULL(SUM(Amount), 0) FROM budget.departmentbudget WHERE status = 'Proceed'");
$allTotalBudgets = $stmtAllTotal->fetchColumn();

$stmtAllApproved = $pdo->query("SELECT IFNULL(SUM(Amount), 0) FROM budget.departmentbudget WHERE status = 'Proceed'");
$allApproved = $stmtAllApproved->fetchColumn();

$stmtAllCancelled = $pdo->query("SELECT IFNULL(SUM(Amount), 0) FROM budget.departmentbudget WHERE status = 'Cancel'");
$allCancelled = $stmtAllCancelled->fetchColumn();

$stmtAllDepts = $pdo->query("SELECT COUNT(DISTINCT Name) FROM budget.departmentbudget WHERE status = 'Proceed'");
$allDepts = $stmtAllDepts->fetchColumn();

$dataByYear['all'] = [
    'totalBudgets'     => '₱' . number_format($allTotalBudgets, 2),
    'approvedBudgets'  => '₱' . number_format($allApproved, 2),
    'cancelledBudgets' => '₱' . number_format($allCancelled, 2),
    'totalDepartments' => (int)$allDepts,
];


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
$dataByYear['cash'] = '₱' . number_format($totalGLCash, 2);

$reload = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['cancel_id'])) {
    $deptName = trim($_POST['deptName'] ?? '');
    $budgetAmount = floatval($_POST['budgetAmount'] ?? 0);
    $budgetDetails = trim($_POST['budgetDetails'] ?? '');
    $year = (int)($_POST['budgetYear'] ?? date("Y"));

    if ($deptName && $budgetAmount > 0 && $budgetDetails && preg_match('/^\d{4}$/', $year)) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM budget.departmentbudget WHERE Name = :name AND DateValid = :year AND status = 'Proceed'");
        $check->execute([':name' => $deptName, ':year' => $year]);
        if ($check->fetchColumn() > 0) {
            $error = "Budget for department '$deptName' in year $year already exists.";
        } else {
            $existing = $yearSums[$year] ?? 0;
            if ($existing + $budgetAmount > $totalGLCash) {
                $maxAllowed = $totalGLCash - $existing;
                $error = "Insufficient GL cash. Maximum allowed: ₱" . number_format($maxAllowed, 2);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO budget.departmentbudget (Name, Amount, DateValid, Details, status) 
                    VALUES (:name, :amount, :year, :details, 'Proceed')
                ");
                $stmt->execute([
                    ':name' => $deptName,
                    ':amount' => $budgetAmount,
                    ':year' => $year,
                    ':details' => $budgetDetails
                ]);

                addAuditLog($pdo, $user_name, $role, 'Add', 'Budget Management', "Added budget ₱" . number_format($budgetAmount, 2) . " for '$deptName' ($year)");
                addNotification($pdo, $user_id, 'Budget Added', "New budget for '$deptName' ($year) created.", 'fa-money-bill');
                $reload = true;
            }
        }
    } else {
        $error = "Please fill all fields correctly.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = (int)$_POST['cancel_id'];
    $stmtDept = $pdo->prepare("SELECT Name FROM budget.departmentbudget WHERE Deptbudget = :id");
    $stmtDept->execute([':id' => $cancelId]);
    $deptName = $stmtDept->fetchColumn() ?: 'Unknown';

    $stmt = $pdo->prepare("UPDATE budget.departmentbudget SET status = 'Cancel' WHERE Deptbudget = :id");
    $stmt->execute([':id' => $cancelId]);

    addAuditLog($pdo, $user_name, $role, 'Cancel', 'Budget Management', "Cancelled budget for '$deptName'");
    addNotification($pdo, $user_id, 'Budget Cancelled', "Budget for '$deptName' cancelled.", 'fa-ban');
    $reload = true;
}

// === Fetch active budgets for display ===
$stmt = $pdo->query("SELECT Name, Amount, Details, Deptbudget, DateValid FROM budget.departmentbudget WHERE status = 'Proceed' ORDER BY DateValid DESC, Name ASC");
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Department list with details (for dropdown) ===
$deptDetailsStmt = $pdo->query("SELECT DISTINCT Name, Details FROM budget.departmentbudget WHERE status = 'Proceed' ORDER BY Name ASC");
$deptDetails = $deptDetailsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>