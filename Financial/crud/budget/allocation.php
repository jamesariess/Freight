

<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

if (isset($_GET['check_allocation']) && isset($_GET['accountID']) && isset($_GET['deptname'])) {
    $accountID = $_GET['accountID'];
    $deptname = trim($_GET['deptname']);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM budget.scostallocation c
        JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget
        WHERE c.accountID = :accountID AND TRIM(d.Name) = :deptname
    ");
    $stmt->execute([':accountID' => $accountID, ':deptname' => $deptname]);
    $count = $stmt->fetchColumn();
    
    echo json_encode(['isAllocated' => $count > 0]);
    exit;
}

if (isset($_GET['deptname']) && isset($_GET['year'])) {
    $deptname = trim($_GET['deptname']);
    $year = $_GET['year'];
    error_log("Fetching budget for Deptname: $deptname, Year: $year at " . date('Y-m-d H:i:s'));
    
    $stmt = $pdo->prepare("
        SELECT Amount, COALESCE(UsedBudget, 0) AS UsedBudget 
        FROM budget.departmentbudget 
        WHERE TRIM(Name) = :deptname AND DateValid = :year AND status = 'Proceed'
    ");
    $stmt->execute([':deptname' => $deptname, ':year' => $year]);
    $deptData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'remainingBudget' => 0,
        'yearlyBudget' => 0,
        'usedBudget' => 0,
        'existing_accounts' => [],
        'restricted_accounts' => []
    ];
    
    if ($deptData) {
        $initialBudget = $deptData['Amount'];
        $usedBudgetFromDept = $deptData['UsedBudget'];
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(Amount), 0) AS totalUsed 
            FROM budget.costallocation 
            WHERE Deptbudget = (
                SELECT Deptbudget 
                FROM budget.departmentbudget 
                WHERE TRIM(Name) = :deptname AND DateValid = :year
            ) AND yearlybudget = :year
        ");
        $stmt->execute([':deptname' => $deptname, ':year' => $year]);
        $totalUsed = $stmt->fetchColumn();
        
        $remainingBudget = $initialBudget - $totalUsed;
        
        $response['remainingBudget'] = $remainingBudget;
        $response['yearlyBudget'] = $initialBudget;
        $response['usedBudget'] = $totalUsed;
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT accountID 
            FROM budget.costallocation 
            WHERE Deptbudget = (
                SELECT Deptbudget 
                FROM budget.departmentbudget 
                WHERE TRIM(Name) = :deptname AND DateValid = :year
            ) AND yearlybudget = :year
        ");
        $stmt->execute([':deptname' => $deptname, ':year' => $year]);
        $response['existing_accounts'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.accountID 
            FROM budget.costallocation c
            JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget
            WHERE TRIM(d.Name) != :deptname
        ");
        $stmt->execute([':deptname' => $deptname]);
        $response['restricted_accounts'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        error_log("No budget data found for Deptname: $deptname, Year: $year");
    }
    
    echo json_encode($response);
    exit;
}

if (isset($_GET['deptname'])) {
    $deptname = trim($_GET['deptname']);
    error_log("Fetching years for Deptname: $deptname at " . date('Y-m-d H:i:s'));
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT DateValid AS yearlybudget 
        FROM budget.departmentbudget 
        WHERE TRIM(Name) = :deptname AND status = 'Proceed'
        UNION
        SELECT DISTINCT yearlybudget 
        FROM budget.costallocation c 
        JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget 
        WHERE TRIM(d.Name) = :deptname 
        ORDER BY yearlybudget DESC
    ");
    $stmt->execute([':deptname' => $deptname]);
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    error_log("Years found: " . json_encode($years));
    if (empty($years)) {
        error_log("No years found for Deptname: $deptname");
    }
    
    echo json_encode($years);
    exit;
} 


$departments = $pdo->query("
    SELECT 
        db.Name,
        db.Deptbudget,
        db.Amount,
        COALESCE(db.UsedBudget, 0) AS UsedBudget,
        db.DateValid
    FROM budget.departmentbudget db
    INNER JOIN (
        SELECT Name, MAX(DateValid) AS MaxDateValid
        FROM budget.departmentbudget
        WHERE status = 'Proceed'
        GROUP BY Name
    ) latest ON db.Name = latest.Name AND db.DateValid = latest.MaxDateValid
    WHERE db.status = 'Proceed'
      AND (db.Amount - COALESCE(db.UsedBudget, 0)) > 0
    ORDER BY db.Name
")->fetchAll(PDO::FETCH_ASSOC);


$remaining = 0;
$errors = [];
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['department'])) {
        $errors[] = "Please select a department.";
    } else {
        $deptbudget = $_POST['department'];
    }
    
    if (empty($_POST['year'])) {
        $errors[] = "Please select a year.";
    } else {
        $year = $_POST['year'];
    }
    
    if (empty($_POST['allocations']) || !is_array($_POST['allocations'])) {
        $errors[] = "Please add at least one allocation.";
    } else {
        $allocations = $_POST['allocations'];
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT Amount, COALESCE(UsedBudget, 0) AS UsedBudget 
            FROM budget.departmentbudget 
            WHERE Deptbudget = :dept AND DateValid = :year AND status = 'Proceed'
        ");
        $stmt->execute([':dept' => $deptbudget, ':year' => $year]);
        $deptData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$deptData) {
            $errors[] = "No budget data found for the selected department and year.";
        } else {
            $initialBudget = $deptData['Amount'];
            $usedBudgetFromDept = $deptData['UsedBudget'];
            $totalUsed = 0;
            
            foreach ($allocations as $row) {
                $amount = str_replace(',', '', $row['amount']);
                if (!is_numeric($amount)) $amount = 0;
                $totalUsed += $amount;
            }
            
            $newUsedBudget = $usedBudgetFromDept + $totalUsed;
            if ($newUsedBudget > $initialBudget) {
                $errors[] = "Allocation exceeds the budget for the selected year!";
            }
        }
    }
    
    if (empty($errors)) {
        // Get account names for audit and notification
        $accountNames = [];
        foreach ($allocations as $row) {
            $stmt = $pdo->prepare("SELECT accountName FROM ledger.chartofaccount WHERE accountID = :accountID");
            $stmt->execute([':accountID' => $row['accountID']]);
            $accountName = $stmt->fetchColumn();
            if ($accountName) {
                $accountNames[] = $accountName;
            }
        }
        $accountNamesList = implode(', ', $accountNames) ?: 'Unknown Accounts';

        try {
            $pdo->beginTransaction();

            foreach ($allocations as $row) {
                $amount = str_replace(',', '', $row['amount']);
                if (!is_numeric($amount)) $amount = 0;
                $stmt = $pdo->prepare("
                    INSERT INTO budget.costallocation (Deptbudget, accountID, Amount, percentage, yearlybudget, AllocationCreate) 
                    VALUES (:dept, :accountID, :amount, :percentage, :year, NOW())
                ");
                $stmt->execute([
                    ':dept' => $deptbudget,
                    ':accountID' => $row['accountID'],
                    ':amount' => $amount,
                    ':percentage' => $row['percentage'],
                    ':year' => $year
                ]);
            }

            $update = $pdo->prepare("
                UPDATE budget.departmentbudget 
                SET UsedBudget = COALESCE(UsedBudget, 0) + :used 
                WHERE Deptbudget = :dept AND DateValid = :year
            ");
            $update->execute([
                ':used' => $totalUsed,
                ':dept' => $deptbudget,
                ':year' => $year
            ]);

            $auditDescription = "Created cost allocations for accounts ($accountNamesList) with total ₱" . number_format($totalUsed, 2) . " for year $year.";
            addAuditLog($pdo, $user_name, $role, 'Create', 'Cost Allocation', $auditDescription);

            $notifMessage = "New cost allocation added for accounts ($accountNamesList) totaling ₱" . number_format($totalUsed, 2) . ".";
            addNotification($pdo, $user_id, 'New Cost Allocation', $notifMessage, 'fa-coins');

            $pdo->commit();
            $successMessage = "Allocation saved successfully!";

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database Error: " . $e->getMessage());
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

$exclude = ['Cash On Hand', 'Cash On Bank', 'Account Receivable'];
$placeholders = str_repeat('?,', count($exclude) - 1) . '?';
$sql = "SELECT accountID, accountName, accounType 
        FROM ledger.chartofaccount 
        WHERE accountName NOT IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($exclude);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$years = $pdo->query("
    SELECT DISTINCT yearlybudget 
    FROM budget.costallocation 
    ORDER BY yearlybudget DESC
")->fetchAll(PDO::FETCH_COLUMN);
$selectedYear = isset($_GET['year']) ? $_GET['year'] : ($years[0] ?? null);

$allocationsByDept = [];
if ($selectedYear) {
    $stmt = $pdo->prepare("
        SELECT d.Name AS deptName, ch.accountName AS Title, c.Percentage, c.Amount 
        FROM budget.costallocation c 
        INNER JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget 
        JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID 
        WHERE c.yearlybudget = :year 
        ORDER BY d.Name, c.AllocationID
    ");
    $stmt->execute([':year' => $selectedYear]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $allocationsByDept[$row['deptName']][] = $row;
    }
} 
?>