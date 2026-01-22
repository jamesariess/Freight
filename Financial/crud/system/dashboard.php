<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

function formatShortNumber($n) {
    if ($n < 1000) {
        return number_format($n);
    } else if ($n < 1000000) {
        return number_format($n / 1000, 1) . 'K';
    } else if ($n < 1000000000) {
        return number_format($n / 1000000, 1) . 'M';
    } else if ($n < 1000000000000) {
        return number_format($n / 1000000000, 1) . 'B';
    } else if ($n < 1000000000000000) {
        return number_format($n / 1000000000000, 1) . 'T';
    } else {
        return number_format($n / 1000000000000000, 1) . 'Q';
    }
}

function getTotalDisburseAmount($pdo) {
    $sql = "SELECT IFNULL(SUM(ApprovedAmount), 0) as total FROM disbursment.request WHERE status IN ('Paid') AND Archive = 'NO'";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $result['total'];
        return '₱' . formatShortNumber($total);
    } catch (PDOException $e) {
        error_log("Error in getTotalDisburseAmount: " . $e->getMessage());
        return '₱0'; 
    }
}

function getTotalOutstanding($pdo) {
    $sql = "
        SELECT l.LoanAmount, COALESCE(l.paidAmount, 0) as paidAmount, l.interestRate
        FROM financial.loan l
        WHERE l.Archive = 'NO' AND l.Status != 'Paid' AND Remarks = 'Approved'
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $totalOutstanding = 0;
        while ($row = $stmt->fetch()) {
            $principal = $row['LoanAmount'];
            $interestRate = $row['interestRate'];
            $paid = $row['paidAmount'];
            $totalInterest = $principal * ($interestRate / 100);
            $totalRepayable = $principal + $totalInterest;
            $outstanding = $totalRepayable - $paid;
            $totalOutstanding += $outstanding;
        }
        return '₱' . formatShortNumber($totalOutstanding);
    } catch (PDOException $e) {
        error_log("Error in getTotalOutstanding: " . $e->getMessage());
        return '₱0';
    }
}

function getTotalPayment($pdo) {
    $sql = "SELECT SUM(amount) AS total_amount FROM ar_ap.ar_collections WHERE Archive = 'NO'";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $result['total_amount'];
        return '₱' . formatShortNumber($total);
    } catch (PDOException $e) {
        error_log("Error in getTotalPayment: " . $e->getMessage());
        return '₱0';
    }
}

function getFollowUp($pdo){
    $sql = "SELECT COUNT(*) as total FROM collection.follow WHERE paymentstatus='Not Paid' AND Archive='NO'";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data['total'];
    } catch (PDOException $e) {
        error_log("Error in getFollowUp: " . $e->getMessage());
        return 0;
    }
}

function getUtilization($pdo) {
    $sql = "SELECT SUM(Amount) as total_budget, SUM(UsedBudget) as total_used FROM budget.departmentbudget WHERE DateValid = :year AND status = 'Proceed'";
    $params = [':year' => date('Y')];
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalBudget = (int)$data['total_budget'];
        $totalUsed = (int)$data['total_used'];
        $utilization = 0;
        if ($totalBudget > 0) {
            $utilization = min(100, round(($totalUsed / $totalBudget) * 100));
        }
        return $utilization . '%';
    } catch (PDOException $e) {
        error_log("Error in getUtilization: " . $e->getMessage());
        return '0%';
    }
}

function getTotalEntires($pdo) {
    try {
        $sql = "SELECT COUNT(*) as total FROM ledger.details WHERE Archive = 'NO'";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getTotalEntires: " . $e->getMessage());
        return 0; 
    }
}

try {
    $chartDataStmt = $pdo->query("
        SELECT p.year, p.month, 
               SUM(CASE WHEN LOWER(c.accounType) IN ('revenue','income') THEN jd.credit - jd.debit ELSE 0 END) as total_revenue,
               SUM(CASE WHEN LOWER(c.accounType) IN ('expense','expenses') THEN jd.debit - jd.credit ELSE 0 END) as total_expenses
        FROM ledger.periods p
        LEFT JOIN ledger.entries e ON p.period_id = e.periodID
        LEFT JOIN ledger.details jd ON e.journalID = jd.journalID
        LEFT JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
        GROUP BY p.year, p.month
        ORDER BY p.year, p.month
    ");
    $chartData = $chartDataStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $chartData = [];
    error_log("Error fetching chart data: " . $e->getMessage());
}

$chartLabels = [];
$chartRevenue = [];
$chartExpenses = [];
foreach ($chartData as $row) {
    $chartLabels[] = date('M Y', mktime(0, 0, 0, $row['month'], 1, $row['year']));
    $chartRevenue[] = (float)$row['total_revenue'];
    $chartExpenses[] = (float)$row['total_expenses'];
}

try {
    $sql = "SELECT r.* ,
    ch.accountName,
    d.Name
    FROM disbursment.request r
    JOIN budget.costallocation c on r.allocationID = c.allocationID
    JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID 
    JOIN budget.departmentbudget d on c.Deptbudget = d.Deptbudget
    WHERE r.Archive = 'NO' 
    LIMIT 6";
    $stmt = $pdo->query($sql);
    $disbursementReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "❌ Error fetching plans: " . $e->getMessage();
}


?>