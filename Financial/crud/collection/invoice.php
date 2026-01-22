<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM ar_ap.ar_invoices WHERE invoice_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $errorMessage = "❌ Error fetching invoice: " . $e->getMessage();
        error_log("Fetch invoice error for invoice_id #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['archive'])) {
        $archive_InvoiceID = $_POST['archive_InvoiceID'];
        $archiveDate = date('Y-m-d H:i:s');

      
        $stmtCustomer = $pdo->prepare("
            SELECT c.name
            FROM ar_ap.ar_invoices a
            JOIN ar_ap.customers c ON a.customer_id = c.customer_id
            WHERE a.invoice_id = :archive_InvoiceID
        ");
        $stmtCustomer->execute([':archive_InvoiceID' => $archive_InvoiceID]);
        $customerName = $stmtCustomer->fetchColumn() ?: 'Unknown Customer';

        $sql = "UPDATE ar_ap.ar_invoices 
                SET Archive = 'YES'
                WHERE invoice_id = :archive_InvoiceID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':archive_InvoiceID', $archive_InvoiceID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for invoice_id: $archive_InvoiceID");
            }
     
            $auditDescription = "Archived invoice #$archive_InvoiceID for customer '$customerName'.";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Invoice', $auditDescription);
            $notifMessage = "Invoice #$archive_InvoiceID archived for customer '$customerName'.";
            addNotification($pdo, $user_id, 'Invoice Archived', $notifMessage, 'fa-archive');
            $successMessage = "✅ Invoice archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error archiving invoice: " . $e->getMessage();
            error_log("Archive invoice error for invoice_id #$archive_InvoiceID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $invoice_id = $_POST['update_InvoiceID'];
        $customer_id = $_POST['update_CustumerName'];
        $amount = $_POST['update_Address'];
        $description = $_POST['update_Email'];
        $reference_number = $_POST['update_PaymentTerms'];
        $due_date = $_POST['update_ContactNumber'];
        $status = $_POST['update_Status'];
        $updated_at = date('Y-m-d H:i:s');

   
        $stmtCustomer = $pdo->prepare("
            SELECT c.name, a.customer_id, a.description, a.reference_no, a.amount, a.due_date, a.stat
            FROM ar_ap.ar_invoices a
            JOIN ar_ap.customers c ON a.customer_id = c.customer_id
            WHERE a.invoice_id = :invoice_id
        ");
        $stmtCustomer->execute([':invoice_id' => $invoice_id]);
        $invoiceData = $stmtCustomer->fetch(PDO::FETCH_ASSOC);
        $customerName = $invoiceData ? $invoiceData['name'] : 'Unknown Customer';
        $oldCustomerId = $invoiceData ? $invoiceData['customer_id'] : 'N/A';
        $oldDescription = $invoiceData ? $invoiceData['description'] : 'N/A';
        $oldReferenceNo = $invoiceData ? $invoiceData['reference_no'] : 'N/A';
        $oldAmount = $invoiceData ? $invoiceData['amount'] : 'N/A';
        $oldDueDate = $invoiceData ? $invoiceData['due_date'] : 'N/A';
        $oldStatus = $invoiceData ? $invoiceData['stat'] : 'N/A';

        $sql = "UPDATE ar_ap.ar_invoices SET 
                customer_id = :customer_id,
                description = :description,
                reference_no = :reference_number,
                amount = :amount,
                due_date = :due_date,
                stat = :status,
                updated_at = :updated_at
                WHERE invoice_id = :invoice_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':reference_number', $reference_number);
        $stmt->bindParam(':updated_at', $updated_at);
        $stmt->bindParam(':invoice_id', $invoice_id);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for invoice_id: $invoice_id");
            }
       
            $auditDescription = "Updated invoice #$invoice_id for customer '$customerName'. Changes: Customer ID from '$oldCustomerId' to '$customer_id', Description from '$oldDescription' to '$description', Reference No from '$oldReferenceNo' to '$reference_number', Amount from '$oldAmount' to '$amount', Due Date from '$oldDueDate' to '$due_date', Status from '$oldStatus' to '$status'.";
            addAuditLog($pdo, $user_name, $role, 'Update', 'Invoice', $auditDescription);
            $notifMessage = "Invoice #$invoice_id updated for customer '$customerName'.";
            addNotification($pdo, $user_id, 'Invoice Updated', $notifMessage, 'fa-edit');
            $successMessage = "✅ Invoice updated successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error updating invoice: " . $e->getMessage();
            error_log("Update invoice error for invoice_id #$invoice_id: " . $e->getMessage());
        }
    }
}

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;


$whereClauses = ["a.Archive = 'NO'"];
$bindings = [];

if (empty($_GET['status'])) {
    $whereClauses[] = "a.stat != 'Paid'";
}

if (!empty($_GET['search'])) {
    $whereClauses[] = "(a.reference_no LIKE :search OR c.name LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "a.stat = :status";
    $bindings[':status'] = $_GET['status'];
}
if (!empty($_GET['created_from'])) {
    $whereClauses[] = "a.invoice_date >= :created_from";
    $bindings[':created_from'] = $_GET['created_from'] . ' 00:00:00';
}
if (!empty($_GET['created_to'])) {
    $whereClauses[] = "a.invoice_date <= :created_to";
    $bindings[':created_to'] = $_GET['created_to'] . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "SELECT COUNT(*) FROM ar_ap.ar_invoices a JOIN ar_ap.customers c ON a.customer_id = c.customer_id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "❌ Error counting invoices: " . $e->getMessage();
    error_log("Count invoices error: " . $e->getMessage());
    $totalRows = 0;
}
$totalPages = ceil($totalRows / $limit);


$sql = "SELECT a.*, c.name FROM ar_ap.ar_invoices a JOIN ar_ap.customers c ON a.customer_id = c.customer_id WHERE $whereSql ORDER BY a.created_at ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $invoiceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "❌ Error fetching invoices: " . $e->getMessage();
    error_log("Fetch invoices error: " . $e->getMessage());
    $invoiceReports = [];
}


function buildPaginatedUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>