<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['updateDispute'])) {
        $disputeID   = $_POST['Dispute_ID'];
        $resolution  = trim($_POST['Resolution']);
        $status      = $_POST['Status'];

        $stmt = $pdo->prepare("
            SELECT c.name, d.Resolution AS oldResolution, d.Status AS oldStatus
            FROM collection.dispute d
            JOIN ar_ap.ar_invoices a ON d.invoice_id = a.invoice_id
            JOIN ar_ap.customers c ON a.customer_id = c.customer_id
            WHERE d.Dispute_ID = :id
        ");
        $stmt->execute([':id' => $disputeID]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $customerName   = $data['name'] ?? 'Unknown';
        $oldResolution  = $data['oldResolution'] ?? 'N/A';
        $oldStatus      = $data['oldStatus'] ?? 'N/A';

        $sql = "UPDATE collection.dispute SET Resolution = :res, Status = :stat WHERE Dispute_ID = :id";
        $upd = $pdo->prepare($sql);
        try {
            $upd->execute([
                ':res' => $resolution,
                ':stat' => $status,
                ':id' => $disputeID
            ]);
            if ($upd->rowCount() === 0) {
                throw new PDOException("No rows updated for Dispute_ID: $disputeID");
            }

            $audit = "Updated dispute #$disputeID for customer '$customerName'. Resolution: '$oldResolution' → '$resolution', Status: '$oldStatus' → '$status'.";
            addAuditLog($pdo, $user_name, $role, 'Update', 'Dispute', $audit);
            addNotification($pdo, $user_id, 'Dispute Updated', "Dispute #$disputeID updated.", 'fa-edit');
            $successMessage = "Dispute updated successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error updating dispute: " . $e->getMessage();
            error_log("Update dispute error: " . $e->getMessage());
        }
    }

    if (isset($_POST['archive'])) {
        $archiveID = $_POST['archive_DisputeID'];

        $stmt = $pdo->prepare("
            SELECT c.name
            FROM collection.dispute d
            JOIN ar_ap.ar_invoices a ON d.invoice_id = a.invoice_id
            JOIN ar_ap.customers c ON a.customer_id = c.customer_id
            WHERE d.Dispute_ID = :id
        ");
        $stmt->execute([':id' => $archiveID]);
        $customerName = $stmt->fetchColumn() ?: 'Unknown Customer';

        $sql = "UPDATE collection.dispute SET Archive = 'YES' WHERE Dispute_ID = :id";
        $arch = $pdo->prepare($sql);
        try {
            $arch->execute([':id' => $archiveID]);
            if ($arch->rowCount() === 0) {
                throw new PDOException("No rows updated for Dispute_ID: $archiveID");
            }
            $audit = "Archived dispute #$archiveID for customer '$customerName'.";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Dispute', $audit);
            addNotification($pdo, $user_id, 'Dispute Archived', "Dispute #$archiveID archived.", 'fa-archive');
            $successMessage = "Dispute archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error archiving dispute: " . $e->getMessage();
            error_log("Archive dispute error: " . $e->getMessage());
        }
    }
}

$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])              : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["d.Archive = 'NO'"];
$bindings     = [];

if (!empty($_GET['search'])) {
    $whereClauses[] = "(a.reference_no LIKE :search OR c.name LIKE :search OR d.Issue LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "d.Status = :status";
    $bindings[':status'] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $whereClauses[] = "d.DisputeDate >= :date_from";
    $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $whereClauses[] = "d.DisputeDate <= :date_to";
    $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereClauses);

$countSql = "
    SELECT COUNT(*) 
    FROM collection.dispute d
    JOIN ar_ap.ar_invoices a ON d.invoice_id = a.invoice_id
    JOIN ar_ap.customers c ON a.customer_id = c.customer_id
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "Error counting disputes: " . $e->getMessage();
    $totalRows = 0;
}
$totalPages = (int)ceil($totalRows / $limit);

$sql = "
    SELECT d.*, c.name, a.reference_no,
           DATE_FORMAT(d.DisputeDate, '%M %e, %Y') AS formatted_date
    FROM collection.dispute d
    JOIN ar_ap.ar_invoices a ON d.invoice_id = a.invoice_id
    JOIN ar_ap.customers c ON a.customer_id = c.customer_id
    WHERE $whereSql
    ORDER BY d.DisputeDate DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching disputes: " . $e->getMessage();
    $disputes = [];
}

function buildPaginatedUrl($page) {
    $p = $_GET;
    $p['page'] = $page;
    return '?' . http_build_query($p);
}
?>
