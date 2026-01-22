<?php
include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');
include_once('../../utility/head.php');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql  = "SELECT * FROM collection.receipt WHERE receiptID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try { $stmt->execute(); }
    catch (PDOException $e) {
        $errorMessage = "Error fetching receipt: " . $e->getMessage();
        error_log("Fetch receipt error for receiptID #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['archive'])) {
        $receiptID = $_POST['receiptID'];

        $stmt = $pdo->prepare("
            SELECT issueBy, receiptsdate, amount 
            FROM collection.receipt 
            WHERE receiptID = :receiptID
        ");
        $stmt->execute([':receiptID' => $receiptID]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $issueBy = $r['issueBy'] ?? 'Unknown';
        $date    = $r['receiptsdate'] ?? 'Unknown';
        $amount  = $r && isset($r['amount']) ? number_format($r['amount'], 2) : '0.00';

        $sql  = "UPDATE collection.receipt SET Archive = 'YES' WHERE receiptID = :receiptID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':receiptID', $receiptID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for receiptID: $receiptID");
            }
            $audit = "Archived receipt #$receiptID (Issuer: '$issueBy', Amount: ₱$amount, Date: $date).";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Receipt', $audit);
            addNotification($pdo, $user_id, 'Receipt Archived',
                "Receipt #$receiptID (₱$amount) archived.", 'fa-archive');
            $successMessage = "Receipt archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error archiving receipt: " . $e->getMessage();
            error_log("Archive receipt error for receiptID #$receiptID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $receiptID = $_POST['receiptID'];
        $issueBy   = trim($_POST['issueBy']);

        if (empty($issueBy)) {
            $errorMessage = "Don't leave issueBy empty.";
        } else {
            $stmt = $pdo->prepare("
                SELECT issueBy, receiptsdate, amount 
                FROM collection.receipt 
                WHERE receiptID = :receiptID
            ");
            $stmt->execute([':receiptID' => $receiptID]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldIssueBy = $r['issueBy'] ?? 'N/A';
            $date       = $r['receiptsdate'] ?? 'Unknown';
            $amount     = $r && isset($r['amount']) ? number_format($r['amount'], 2) : '0.00';

            $sql  = "UPDATE collection.receipt SET issueBy = :issueBy WHERE receiptID = :receiptID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':issueBy', $issueBy);
            $stmt->bindParam(':receiptID', $receiptID);

            try {
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No rows updated for receiptID: $receiptID");
                }
                $audit = "Updated receipt #$receiptID (Amount: ₱$amount, Date: $date). IssueBy changed from '$oldIssueBy' to '$issueBy'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Receipt', $audit);
                addNotification($pdo, $user_id, 'Receipt Updated',
                    "Receipt #$receiptID (₱$amount) issueBy updated.", 'fa-edit');
                $successMessage = "issueBy updated successfully.";
            } catch (PDOException $e) {
                $errorMessage = "Error updating receipt: " . $e->getMessage();
                error_log("Update receipt error for receiptID #$receiptID: " . $e->getMessage());
            }
        }
    }
}

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page  = isset($_GET['page'])  ? max(1, (int)$_GET['page'])               : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["Archive = 'NO'"];
$bindings     = [];

if (!empty($_GET['search'])) {
    $whereClauses[] = "(receiptNumber LIKE :search OR issueBy LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}

if (!empty($_GET['date_from'])) {
    $whereClauses[] = "receiptsdate >= :date_from";
    $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $whereClauses[] = "receiptsdate <= :date_to";
    $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}
$whereSql = implode(' AND ', $whereClauses);

$countSql = "SELECT COUNT(*) FROM collection.receipt WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "Error counting receipts: " . $e->getMessage();
    $totalRows = 0;
}
$totalPages = (int)ceil($totalRows / $limit);

$sql = "
    SELECT *, 
           DATE_FORMAT(r.receiptsdate, '%M %e, %Y at %l:%i %p') AS formatted_date
    FROM collection.receipt r
    JOIN ar_ap.ar_collections p ON r.paymentID = p.collection_id 
    JOIN ar_ap.ar_invoices pay ON p.invoice_id  = pay.invoice_id
    WHERE r.$whereSql
    ORDER BY r.receiptsdate ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching receipts: " . $e->getMessage();
    $receipts = [];
}

function buildPaginatedUrl($page) {
    $p = $_GET;
    $p['page'] = $page;
    return '?' . http_build_query($p);
}
?>
