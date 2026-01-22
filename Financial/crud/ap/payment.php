<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "SELECT * FROM ar_ap.ap_payments WHERE payment_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $errorMessage = "❌ Error fetching payment: " . $e->getMessage();
        error_log("Fetch payment error for payment_id #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['archive'])) {
        $paymentID = $_POST['paymentID'];

        // Fetch payment and vendor details for audit and notification
        $stmtPayment = $pdo->prepare("
            SELECT ap.payment_id, ap.amount, v.vendor_name 
            FROM ar_ap.ap_payments ap
            LEFT JOIN ar_ap.vendor v ON ap.vendor_id = v.vendor_id
            WHERE ap.payment_id = :payment_id
        ");
        $stmtPayment->execute([':payment_id' => $paymentID]);
        $paymentData = $stmtPayment->fetch(PDO::FETCH_ASSOC);
        $vendorName = $paymentData ? ($paymentData['vendor_name'] ?? 'Unknown Vendor') : 'Unknown Vendor';
        $amount = $paymentData && isset($paymentData['amount']) ? number_format($paymentData['amount'], 2) : 'Unknown Amount';

        $sql = "UPDATE ar_ap.ap_payments SET Archive = 'YES' WHERE payment_id = :paymentID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':paymentID', $paymentID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for payment_id: $paymentID");
            }
      
            $auditDescription = "Archived payment #$paymentID (Vendor: '$vendorName', Amount: ₱$amount).";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Payment', $auditDescription);
            $notifMessage = "Payment #$paymentID (Vendor: '$vendorName') archived.";
            addNotification($pdo, $user_id, 'Payment Archived', $notifMessage, 'fa-archive');
            $successMessage = "✅ Payment archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error archiving payment: " . $e->getMessage();
            error_log("Archive payment error for payment_id #$paymentID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $paymentID = $_POST['paymentID'];
        $amount = trim($_POST['amount'] ?? '');
        $method = trim($_POST['method'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

      
        $validationErrors = [];
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $validationErrors[] = "Valid amount is required.";
        }
        if (empty($method)) {
            $validationErrors[] = "Payment method is required.";
        }
     
        if (!empty($remarks) && strlen($remarks) > 255) {
            $validationErrors[] = "Remarks must not exceed 255 characters.";
        }

        if (empty($validationErrors)) {
        
            $stmtPayment = $pdo->prepare("
                SELECT ap.amount, ap.method, ap.remarks, v.vendor_name 
                FROM ar_ap.ap_payments ap
                LEFT JOIN ar_ap.vendor v ON ap.vendor_id = v.vendor_id
                WHERE ap.payment_id = :payment_id
            ");
            $stmtPayment->execute([':payment_id' => $paymentID]);
            $paymentData = $stmtPayment->fetch(PDO::FETCH_ASSOC);
            $oldAmount = $paymentData ? number_format($paymentData['amount'], 2) : 'N/A';
            $oldMethod = $paymentData ? $paymentData['method'] : 'N/A';
            $oldRemarks = $paymentData ? $paymentData['remarks'] : 'N/A';
            $vendorName = $paymentData ? ($paymentData['vendor_name'] ?? 'Unknown Vendor') : 'Unknown Vendor';

            $sql = "UPDATE ar_ap.ap_payments SET 
                    amount = :amount,
                    method = :method,
                    remarks = :remarks
                    WHERE payment_id = :paymentID";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':remarks', $remarks);
            $stmt->bindParam(':paymentID', $paymentID);

            try {
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No rows updated for payment_id: $paymentID");
                }
            
                $auditDescription = "Updated payment #$paymentID (Vendor: '$vendorName'). Changes: Amount from ₱$oldAmount to ₱$amount, Method from '$oldMethod' to '$method', Remarks from '$oldRemarks' to '$remarks'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Payment', $auditDescription);
                $notifMessage = "Payment #$paymentID (Vendor: '$vendorName') updated.";
                addNotification($pdo, $user_id, 'Payment Updated', $notifMessage, 'fa-edit');
                $successMessage = "✅ Payment updated successfully.";
            } catch (PDOException $e) {
                $errorMessage = "❌ Error updating payment: " . $e->getMessage();
                error_log("Update payment error for payment_id #$paymentID: " . $e->getMessage());
            }
        } else {
            $errorMessage = "❌ Validation errors: " . implode(' ', $validationErrors);
            error_log("Validation errors for payment update (payment_id #$paymentID): " . implode(' ', $validationErrors));
        }
    }
}

$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])              : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["ap.Archive = 'NO'"];
$bindings     = [];


if (!empty($_GET['search'])) {
    $whereClauses[] = "(CONCAT('LN-', YEAR(ap.payment_date), '-', LPAD(l.LoanID, 3, '0')) LIKE :search 
                        OR v.vendor_name LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}


if (!empty($_GET['method'])) {
    $whereClauses[] = "ap.method = :method";
    $bindings[':method'] = $_GET['method'];
}


if (!empty($_GET['date_from'])) {
    $whereClauses[] = "ap.payment_date >= :date_from";
    $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $whereClauses[] = "ap.payment_date <= :date_to";
    $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}


if (!empty($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
    $whereClauses[] = "ap.amount >= :amount_min";
    $bindings[':amount_min'] = $_GET['amount_min'];
}
if (!empty($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
    $whereClauses[] = "ap.amount <= :amount_max";
    $bindings[':amount_max'] = $_GET['amount_max'];
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "
    SELECT COUNT(*) 
    FROM ar_ap.ap_payments ap
    LEFT JOIN ar_ap.loan l ON ap.LoanID = l.LoanID
    
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "Error counting payments: " . $e->getMessage();
    $totalRows = 0;
}
$totalPages = (int)ceil($totalRows / $limit);


$sql = "
    SELECT 
        ap.payment_id,
        ap.payment_date,
        ap.amount,
        ap.method,
        ap.remarks,
        ap.created_at,
        l.LoanID,
   
        DATE_FORMAT(ap.payment_date, '%M %e, %Y') AS formatted_payment_date,
        DATE_FORMAT(ap.created_at, '%M %e, %Y \\a\\t %l:%i %p') AS formatted_created_at
    FROM ar_ap.ap_payments ap
    LEFT JOIN ar_ap.loan l ON ap.LoanID = l.LoanID
  
    WHERE $whereSql
    ORDER BY ap.payment_date DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $collectionReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching payments: " . $e->getMessage();
    $collectionReports = [];
}


function buildPaginatedUrl($page) {
    $p = $_GET;
    $p['page'] = $page;
    return '?' . http_build_query($p);
}

?>

