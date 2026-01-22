<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "SELECT * FROM collection.collection_plan WHERE planID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $errorMessage = "❌ Error fetching collection plan: " . $e->getMessage();
        error_log("Fetch collection plan error for planID #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

   if (isset($_POST['plans'])) {
    $plan = $_POST['requestTitle'];
    $remaining = $_POST['remaining'];
    $planType = $_POST['planType'];
    $emailSubject = $_POST['emailSubject'] ?? null;
    $emailBody = $_POST['emailBody'] ?? null;
    $created_at = date('Y-m-d H:i:s');

    if (empty($plan) || empty($remaining) || empty($planType)) {
        $errorMessage = "❌ All fields are required.";
    } else {
        $sql = "INSERT INTO collection.collection_plan 
                (`plan`, remaining_days, plan_type, email_subject, email_body, status, Archive, Date)
                VALUES 
                (:plan, :remaining_days, :plan_type, :email_subject, :email_body, 'Active', 'NO', :created_at)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':plan', $plan);
        $stmt->bindParam(':remaining_days', $remaining);
        $stmt->bindParam(':plan_type', $planType);
        $stmt->bindParam(':email_subject', $emailSubject);
        $stmt->bindParam(':email_body', $emailBody);
        $stmt->bindParam(':created_at', $created_at);

        try {
            $stmt->execute();
            $planID = $pdo->lastInsertId();

           
            $auditDescription = "Created collection plan #$planID: Plan='$plan', Remaining Days='$remaining', Plan Type='$planType', Status='Active'.";
            addAuditLog($pdo, $user_name, $role, 'Create', 'Collection Plan', $auditDescription);

       
            $notifMessage = "Collection plan #$planID ('$plan') created.";
            addNotification($pdo, $user_id, 'Collection Plan Created', $notifMessage, 'fa-plus');

            $successMessage = "✅ Collection plan created successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error creating collection plan: " . $e->getMessage();
            error_log("Create collection plan error: " . $e->getMessage());
        }
    }
}

    if (isset($_POST['archive'])) {
        $planID = $_POST['planID'];

       
        $stmtPlan = $pdo->prepare("
            SELECT plan, remaining_days, plan_type, status
            FROM collection.collection_plan 
            WHERE planID = :planID
        ");
        $stmtPlan->execute([':planID' => $planID]);
        $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        $planName = $planData ? $planData['plan'] : 'Unknown Plan';

        $sql = "UPDATE collection.collection_plan SET Archive = 'YES' WHERE planID = :planID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':planID', $planID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for planID: $planID");
            }
        
            $auditDescription = "Archived collection plan #$planID ('$planName').";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Collection Plan', $auditDescription);
            $notifMessage = "Collection plan #$planID ('$planName') archived.";
            addNotification($pdo, $user_id, 'Collection Plan Archived', $notifMessage, 'fa-archive');
            $successMessage = "✅ Collection plan archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error archiving collection plan: " . $e->getMessage();
            error_log("Archive collection plan error for planID #$planID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $planID = $_POST['planID'];
        $plan = $_POST['plan'];
        $remaining = $_POST['remaining'];
        $planType = $_POST['type'];
        $status = $_POST['status'];

        if (empty($plan) || empty($remaining) || empty($planType)) {
            $errorMessage = "❌ All fields are required.";
        } else {
     
            $stmtPlan = $pdo->prepare("
                SELECT plan, remaining_days, plan_type, status
                FROM collection.collection_plan 
                WHERE planID = :planID
            ");
            $stmtPlan->execute([':planID' => $planID]);
            $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);
            $oldPlan = $planData ? $planData['plan'] : 'N/A';
            $oldRemaining = $planData ? $planData['remaining_days'] : 'N/A';
            $oldPlanType = $planData ? $planData['plan_type'] : 'N/A';
            $oldStatus = $planData ? $planData['status'] : 'N/A';

            $sql = "UPDATE collection.collection_plan 
                    SET plan = :plan, 
                        remaining_days = :remaining_days, 
                        plan_type = :plan_type, 
                        status = :status 
                    WHERE planID = :planID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':plan', $plan);
            $stmt->bindParam(':remaining_days', $remaining);
            $stmt->bindParam(':plan_type', $planType);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':planID', $planID);

            try {
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No rows updated for planID: $planID");
                }
               
                $auditDescription = "Updated collection plan #$planID. Changes: Plan from '$oldPlan' to '$plan', Remaining Days from '$oldRemaining' to '$remaining', Plan Type from '$oldPlanType' to '$planType', Status from '$oldStatus' to '$status'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Collection Plan', $auditDescription);
                $notifMessage = "Collection plan #$planID ('$plan') updated.";
                addNotification($pdo, $user_id, 'Collection Plan Updated', $notifMessage, 'fa-edit');
                $successMessage = "✅ Collection plan updated successfully.";
            } catch (PDOException $e) {
                $errorMessage = "❌ Error updating collection plan: " . $e->getMessage();
                error_log("Update collection plan error for planID #$planID: " . $e->getMessage());
            }
        }
    }
}

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;


$whereClauses = ["Archive = 'NO'"];
$bindings = [];

if (!empty($_GET['plan_name'])) {
    $whereClauses[] = "plan LIKE :plan_name";
    $bindings[':plan_name'] = '%' . $_GET['plan_name'] . '%';
}
if (!empty($_GET['plan_type'])) {
    $whereClauses[] = "plan_type = :plan_type";
    $bindings[':plan_type'] = $_GET['plan_type'];
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "status = :status";
    $bindings[':status'] = $_GET['status'];
}
if (!empty($_GET['created_from'])) {
    $whereClauses[] = "Date >= :created_from";
    $bindings[':created_from'] = $_GET['created_from'] . ' 00:00:00';
}
if (!empty($_GET['created_to'])) {
    $whereClauses[] = "Date <= :created_to";
    $bindings[':created_to'] = $_GET['created_to'] . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "SELECT COUNT(*) FROM collection.collection_plan WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);


$sql = "SELECT * FROM collection.collection_plan WHERE $whereSql ORDER BY Date ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildPaginatedUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>