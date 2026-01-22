<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['archive'])) {
        $custumerID = $_POST['archive_collectionID'];

        $stmtCollection = $pdo->prepare("
            SELECT c.collection_id, c.invoice_id, c.amount, i.reference_no 
            FROM ar_ap.ar_collections c
            JOIN ar_ap.ar_invoices i ON c.invoice_id = i.invoice_id
            WHERE c.collection_id = :custumerID
        ");
        $stmtCollection->execute([':custumerID' => $custumerID]);
        $collectionData = $stmtCollection->fetch(PDO::FETCH_ASSOC);
        $invoiceRef = $collectionData ? $collectionData['reference_no'] : 'N/A';
        $invoiceId = $collectionData ? $collectionData['invoice_id'] : 'N/A';
        $amount = $collectionData && isset($collectionData['amount']) ? number_format($collectionData['amount'], 2) : 'Unknown Amount';

        $sql = "UPDATE ar_ap.ar_collections SET Archive = 'YES' WHERE collection_id = :custumerID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':custumerID', $custumerID);

        try {
            $stmt->execute();
            $successMessage = "Customer archived successfully.";
            $auditDescription = "Archived collection #$custumerID (Invoice: #$invoiceRef, Amount: ₱$amount).";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Collection', $auditDescription);
            $notifMessage = "Collection #$custumerID (Invoice: #$invoiceRef) archived.";
            addNotification($pdo, $user_id, 'Collection Archived', $notifMessage, 'fa-archive');
        } catch (PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
            error_log("Archive collection error for collection_id #$custumerID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $custumerID = $_POST['update_collectionID'];
        $custumerName = $_POST['update_invoiceID'];
        $contactNumber = $_POST['update_amount'];
        $email = $_POST['update_paymentMethod'];
        $address = $_POST['update_remarks'];

        $stmtCollection = $pdo->prepare("
            SELECT c.invoice_id, c.amount, c.method, c.remarks, i.reference_no 
            FROM ar_ap.ar_collections c
            JOIN ar_ap.ar_invoices i ON c.invoice_id = i.invoice_id
            WHERE c.collection_id = :custumerID
        ");
        $stmtCollection->execute([':custumerID' => $custumerID]);
        $collectionData = $stmtCollection->fetch(PDO::FETCH_ASSOC);
        $oldInvoiceId = $collectionData ? $collectionData['invoice_id'] : 'N/A';
        $oldAmount = $collectionData && isset($collectionData['amount']) ? number_format($collectionData['amount'], 2) : 'N/A';
        $oldMethod = $collectionData ? $collectionData['method'] : 'N/A';
        $oldRemarks = $collectionData ? $collectionData['remarks'] : 'N/A';
        $invoiceRef = $collectionData ? $collectionData['reference_no'] : 'N/A';

        $sql = "UPDATE ar_ap.ar_collections SET 
                    invoice_id = :custumerName,
                    amount = :contactNumber,
                    method = :email,
                    remarks = :address
                WHERE collection_id = :custumerID";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':custumerName', $custumerName);
        $stmt->bindParam(':contactNumber', $contactNumber);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':custumerID', $custumerID);

        try {
            $stmt->execute();
            $successMessage = "Customer updated successfully.";
            $auditDescription = "Updated collection #$custumerID (Invoice: #$invoiceRef). Changes: Invoice ID from '$oldInvoiceId' to '$custumerName', Amount from ₱$oldAmount to ₱" . number_format($contactNumber, 2) . ", Method from '$oldMethod' to '$email', Remarks from '$oldRemarks' to '$address'.";
            addAuditLog($pdo, $user_name, $role, 'Update', 'Collection', $auditDescription);
            $notifMessage = "Collection #$custumerID (Invoice: #$invoiceRef) updated.";
            addNotification($pdo, $user_id, 'Collection Updated', $notifMessage, 'fa-edit');
        } catch (PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
            error_log("Update collection error for collection_id #$custumerID: " . $e->getMessage());
        }
    }

  }

$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])              : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["c.Archive = 'NO'"];
$bindings     = [];

if (!empty($_GET['search'])) {
    $whereClauses[] = "(c.collection_id LIKE :search 
                        OR c.invoice_id LIKE :search 
                        OR i.reference_no LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}

if (!empty($_GET['method'])) {
    $whereClauses[] = "c.method = :method";
    $bindings[':method'] = $_GET['method'];
}

if (!empty($_GET['date_from'])) {
    $whereClauses[] = "c.payment_date >= :date_from";
    $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $whereClauses[] = "c.payment_date <= :date_to";
    $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}

if (!empty($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
    $whereClauses[] = "c.amount >= :amount_min";
    $bindings[':amount_min'] = $_GET['amount_min'];
}
if (!empty($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
    $whereClauses[] = "c.amount <= :amount_max";
    $bindings[':amount_max'] = $_GET['amount_max'];
}

$whereSql = implode(' AND ', $whereClauses);

$countSql = "
    SELECT COUNT(*) 
    FROM ar_ap.ar_collections c
    LEFT JOIN ar_ap.ar_invoices i ON c.invoice_id = i.invoice_id
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "Error counting collections: " . $e->getMessage();
    $totalRows = 0;
}
$totalPages = (int)ceil($totalRows / $limit);

$sql = "
    SELECT 
        c.collection_id,
        c.invoice_id,
        c.payment_date,
        c.amount,
        c.method,
        i.reference_no,
        c.remarks,
        c.created_at,
        i.reference_no,
        DATE_FORMAT(c.payment_date, '%M %e, %Y') AS fmt_payment_date,
        DATE_FORMAT(c.created_at,   '%M %e, %Y \\a\\t %l:%i %p') AS fmt_created_at
    FROM ar_ap.ar_collections c
    LEFT JOIN ar_ap.ar_invoices i ON c.invoice_id = i.invoice_id
    WHERE $whereSql
    ORDER BY c.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $collectionReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching collections: " . $e->getMessage();
    $collectionReports = [];
}

function buildPaginatedUrl($page) {
    $p = $_GET;
    $p['page'] = $page;
    return '?' . http_build_query($p);
}
?>
