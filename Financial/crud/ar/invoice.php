<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
$sql = "SELECT * FROM ar_ap.ar_invoices WHERE invoice_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['archive'])) {
        $archive_InvoiceID = $_POST['archive_InvoiceID'];
        $archiveDate = date('Y-m-d H:i:s');

      
        $stmtInvoice = $pdo->prepare("
            SELECT i.invoice_id, i.amount, c.name AS customer_name  
            FROM ar_ap.ar_invoices i
            JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            WHERE i.invoice_id = :archive_InvoiceID
        ");
        $stmtInvoice->execute([':archive_InvoiceID' => $archive_InvoiceID]);
        $invoiceData = $stmtInvoice->fetch(PDO::FETCH_ASSOC);
        $customerName = $invoiceData ? $invoiceData['customer_name'] : 'Unknown Customer';
        $amount = $invoiceData && isset($invoiceData['amount']) ? number_format($invoiceData['amount'], 2) : 'Unknown Amount';

        $sql = "UPDATE ar_ap.ar_invoices 
                SET Archive = 'YES'
                WHERE invoice_id = :archive_InvoiceID";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':archive_InvoiceID', $archive_InvoiceID);

        try {
            $stmt->execute();
            $successMessage = "Invoice archived successfully.";
     
            $auditDescription = "Archived invoice #$archive_InvoiceID (Customer: '$customerName', Amount: ₱$amount).";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Invoice', $auditDescription);
            $notifMessage = "Invoice #$archive_InvoiceID (Customer: '$customerName') archived.";
            addNotification($pdo, $user_id, 'Invoice Archived', $notifMessage, 'fa-archive');
        } catch (PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
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

      
        $stmtInvoice = $pdo->prepare("
            SELECT i.customer_id, i.description, i.reference_no, i.amount, i.due_date, i.stat, c.name AS customer_name  
            FROM ar_ap.ar_invoices i
            JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            WHERE i.invoice_id = :invoice_id
        ");
        $stmtInvoice->execute([':invoice_id' => $invoice_id]);
        $invoiceData = $stmtInvoice->fetch(PDO::FETCH_ASSOC);
        $oldCustomerId = $invoiceData ? $invoiceData['customer_id'] : 'N/A';
        $oldDescription = $invoiceData ? $invoiceData['description'] : 'N/A';
        $oldReferenceNo = $invoiceData ? $invoiceData['reference_no'] : 'N/A';
        $oldAmount = $invoiceData && isset($invoiceData['amount']) ? number_format($invoiceData['amount'], 2) : 'N/A';
        $oldDueDate = $invoiceData ? $invoiceData['due_date'] : 'N/A';
        $oldStatus = $invoiceData ? $invoiceData['stat'] : 'N/A';
        $customerName = $invoiceData ? $invoiceData['customer_name'] : 'Unknown Customer';

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
            $successMessage = "Invoice updated successfully.";
        
            $auditDescription = "Updated invoice #$invoice_id (Customer: '$customerName'). Changes: Customer ID from '$oldCustomerId' to '$customer_id', Description from '$oldDescription' to '$description', Reference No from '$oldReferenceNo' to '$reference_number', Amount from ₱$oldAmount to ₱" . number_format($amount, 2) . ", Due Date from '$oldDueDate' to '$due_date', Status from '$oldStatus' to '$status'.";
            addAuditLog($pdo, $user_name, $role, 'Update', 'Invoice', $auditDescription);
            $notifMessage = "Invoice #$invoice_id (Customer: '$customerName') updated.";
            addNotification($pdo, $user_id, 'Invoice Updated', $notifMessage, 'fa-edit');
        } catch (PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
            error_log("Update invoice error for invoice_id #$invoice_id: " . $e->getMessage());
        }
    }
}

$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])              : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["i.Archive = 'NO'", "i.stat != 'Draft'"];
$bindings     = [];

if (!empty($_GET['search'])) {
    $whereClauses[] = "(i.invoice_id LIKE :search 
                        OR i.reference_no LIKE :search 
                        OR c.name LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}


if (!empty($_GET['status'])) {
    $whereClauses[] = "i.stat = :status";
    $bindings[':status'] = $_GET['status'];
}

if (!empty($_GET['date_from'])) {
    $whereClauses[] = "i.invoice_date >= :date_from";
    $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $whereClauses[] = "i.invoice_date <= :date_to";
    $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}


if (!empty($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
    $whereClauses[] = "i.amount >= :amount_min";
    $bindings[':amount_min'] = $_GET['amount_min'];
}
if (!empty($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
    $whereClauses[] = "i.amount <= :amount_max";
    $bindings[':amount_max'] = $_GET['amount_max'];
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "
    SELECT COUNT(*) 
    FROM ar_ap.ar_invoices i
    JOIN ar_ap.customers c ON i.customer_id = c.customer_id
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "Error counting invoices: " . $e->getMessage();
    $totalRows = 0;
}
$totalPages = (int)ceil($totalRows / $limit);

$sql = "
    SELECT 
        i.invoice_id,
        i.customer_id,
        i.invoice_date,
        i.due_date,
        i.description,
        i.amount,
        i.reference_no,
        i.created_at,
        i.stat,
        c.name,
        DATE_FORMAT(i.invoice_date, '%M %e, %Y') AS fmt_invoice_date,
        DATE_FORMAT(i.due_date,   '%M %e, %Y') AS fmt_due_date,
        DATE_FORMAT(i.created_at, '%M %e, %Y \\a\\t %l:%i %p') AS fmt_created_at
    FROM ar_ap.ar_invoices i
    JOIN ar_ap.customers c ON i.customer_id = c.customer_id
    WHERE $whereSql
    ORDER BY i.created_at ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $invoiceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching invoices: " . $e->getMessage();
    $invoiceReports = [];
}


function buildPaginatedUrl($page) {
    $p = $_GET;
    $p['page'] = $page;
    return '?' . http_build_query($p);
}
?>