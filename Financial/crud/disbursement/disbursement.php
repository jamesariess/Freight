<?php
include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');
include_once('../../utility/head.php');

$successMessage = '';
$errorMessage = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive'])) {
    $disbursement_id = $_POST['archive_disbursement_id'];
    $archive = 'YES';

   
    $stmtAccount = $pdo->prepare("
        SELECT ch.accountName
        FROM disbursment.request r
        JOIN budget.costallocation c ON r.allocationID = c.allocationID
        JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID
        WHERE r.requestID = :requestID
    ");
    $stmtAccount->execute([':requestID' => $disbursement_id]);
    $accountName = $stmtAccount->fetchColumn() ?: 'Unknown Account';

    $sql = "UPDATE disbursment.request SET Archive = :archive WHERE requestID = :disbursement_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':archive', $archive);
    $stmt->bindParam(':disbursement_id', $disbursement_id);

    try {
        $stmt->execute();
        
        $auditDescription = "Archived disbursement request #$disbursement_id for account '$accountName'.";
        addAuditLog($pdo, $user_name, $role, 'Archive', 'Disbursement Request', $auditDescription);
        $notifMessage = "Disbursement request #$disbursement_id archived for account '$accountName'.";
        addNotification($pdo, $user_id, 'Disbursement Archived', $notifMessage, 'fa-archive');
        $successMessage = "✅ Disbursement archived successfully.";
    } catch (PDOException $e) {
        $errorMessage = "❌ Error: " . $e->getMessage();
        error_log("Archive error for request #$disbursement_id: " . $e->getMessage());
    }
}


$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;


$whereClauses = ["r.Archive = 'NO'"];
$bindings = [];

if (!empty($_GET['account_name'])) {
    $whereClauses[] = "ch.accountName LIKE :account_name";
    $bindings[':account_name'] = '%' . $_GET['account_name'] . '%';
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "r.status = :status";
    $bindings[':status'] = $_GET['status'];
}
if (!empty($_GET['created_from'])) {
    $whereClauses[] = "r.date >= :created_from";
    $bindings[':created_from'] = $_GET['created_from'] . ' 00:00:00';
}
if (!empty($_GET['created_to'])) {
    $whereClauses[] = "r.date <= :created_to";
    $bindings[':created_to'] = $_GET['created_to'] . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "
    SELECT COUNT(*) 
    FROM disbursment.request r
    JOIN budget.costallocation c ON r.allocationID = c.allocationID
    JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);


$sql = "
    SELECT r.*, ch.accountName, r.documents, r.receipt
    FROM disbursment.request r
    JOIN budget.costallocation c ON r.allocationID = c.allocationID
    JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID
    WHERE $whereSql
    ORDER BY r.date DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$disbursementReports = $stmt->fetchAll(PDO::FETCH_ASSOC);


function buildPaginatedUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>
