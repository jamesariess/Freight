<?php

ob_clean(); 
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');


require_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');


if (isset($_GET['json'])) {
    $sql = "SELECT
        r.requestID, r.requestTiTle, r.ApprovedAmount, r.Requested_by, r.Due, r.status, r.date, r.Amount,
        ch.accountName as Title, c.allocationID,  r.documents, r.Purpuse,
        d.Name,
        (c.Amount - COALESCE(c.usedAllocation, 0)) as balance
    FROM disbursment.request r
    JOIN budget.costallocation c ON r.allocationID = c.allocationID
    JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID 
    JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget
    WHERE r.status IN ('Pending') AND r.Archive = 'NO' AND d.status = 'Proceed'
    ORDER BY r.date DESC 
    LIMIT 12";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($requests as &$req) {
            $req['documents'] = !empty($req['documents']) ? json_decode($req['documents'], true) : [];
        }
        echo json_encode($requests);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error', 'message' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['json_reports'])) {
    $sql = "SELECT r.*,
               ch.accountName AS Title,
               d.Name
            FROM disbursment.request r
            JOIN budget.costallocation c ON r.allocationID = c.allocationID
            JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID 
            JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget
            WHERE r.Archive = 'NO'";

    try {
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed', 'message' => $e->getMessage()]);
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $requestID = $_POST['update_request_id'] ?? null;
    $newAmount = floatval($_POST['update_amount'] ?? 0);

    if (!$requestID || $newAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $oldStmt = $pdo->prepare("SELECT requestTiTle, ApprovedAmount FROM disbursment.request WHERE requestID = ?");
    $oldStmt->execute([$requestID]);
    $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

    $reqTitle  = $oldData['requestTiTle'] ?? 'Unknown Title';
    $oldAmount = floatval($oldData['ApprovedAmount'] ?? 0);

    $logChanges = [];
    if ($newAmount != $oldAmount) {
        $logChanges[] = "Approved Amount changed from ₱" . number_format($oldAmount, 2) . " to ₱" . number_format($newAmount, 2);
    }

    if (empty($logChanges)) {
        echo json_encode(['success' => false, 'message' => 'No changes detected.']);
        exit;
    }

    $sql = "UPDATE disbursment.request SET ApprovedAmount = :approvedAmount WHERE requestID = :requestID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':approvedAmount', $newAmount);
    $stmt->bindParam(':requestID', $requestID);

    if ($stmt->execute()) {
        $changeDetails = implode(", ", $logChanges);
        $auditDescription = "Updated Request '$reqTitle' ($changeDetails)";
        $notifMessage = "Request '$reqTitle' updated with new Amount ₱" . number_format($newAmount, 2);

        addAuditLog($pdo, $user_name, $role, 'Update', 'Request', $auditDescription);
        addNotification($pdo, $user_id, 'Request Updated', $notifMessage, 'fa-edit');

        echo json_encode(['success' => true, 'message' => 'Update successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}


$input = json_decode(file_get_contents("php://input"), true) ?? [];

if ($input && isset($input["requestID"], $input["status"])) {
    $requestID = intval($input["requestID"]);
    $status = $input["status"];
    $success = false;


    if ($status === "Approved" && isset($input["approvedAmount"])) {
        $approvedAmount = floatval($input["approvedAmount"]);
        if ($approvedAmount <= 0) {
            echo json_encode(["success" => false, "message" => "Invalid amount"]);
            exit;
        }

        $checkStmt = $pdo->prepare("
            SELECT (c.Amount - COALESCE(c.usedAllocation, 0)) as balance 
            FROM budget.costallocation c 
            JOIN disbursment.request r ON r.allocationID = c.allocationID 
            WHERE r.requestID = :id
        ");
        $checkStmt->execute([':id' => $requestID]);
        $balanceRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $balance = $balanceRow ? floatval($balanceRow['balance']) : 0;

        if ($approvedAmount > $balance) {
            echo json_encode(["success" => false, "message" => "Approved amount exceeds available balance of ₱" . number_format($balance, 2)]);
            exit;
        }

        $reqTitle = $pdo->query("SELECT requestTiTle FROM disbursment.request WHERE requestID = $requestID")->fetchColumn();

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare("
                UPDATE disbursment.request 
                SET status = :status, ApprovedAmount = :amount 
                WHERE requestID = :id
            ");
            $update->execute([
                ":status" => $status,
                ":amount" => $approvedAmount,
                ":id" => $requestID
            ]);

            $updateAlloc = $pdo->prepare("
                UPDATE budget.costallocation 
                SET usedAllocation = COALESCE(usedAllocation, 0) + :amount 
                WHERE allocationID = (SELECT allocationID FROM disbursment.request WHERE requestID = :id)
            ");
            $updateAlloc->execute([
                ":amount" => $approvedAmount,
                ":id" => $requestID
            ]);

            $pdo->commit();
            $success = true;

            addAuditLog($pdo, $user_name, $role, 'Approve', 'Request', "Approved Request '$reqTitle' for ₱" . number_format($approvedAmount, 2));
            addNotification($pdo, $user_id, 'Request Approved', "Request '$reqTitle' has been approved (₱" . number_format($approvedAmount, 2) . ")", 'fa-check');

        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("Database error: " . $e->getMessage());
            $success = false;
        }
    }


    elseif ($status === "Rejected") {
        $remarks = isset($input["remarks"]) ? trim($input["remarks"]) : "Rejected by approver";
        $reqTitle = $pdo->query("SELECT requestTiTle FROM disbursment.request WHERE requestID = $requestID")->fetchColumn();

        try {
            $update = $pdo->prepare("
                UPDATE disbursment.request 
                SET status = :status, Remarks = :remarks 
                WHERE requestID = :id
            ");
            $success = $update->execute([
                ":status" => $status,
                ":remarks" => $remarks,
                ":id" => $requestID
            ]);

            if ($success) {
                addAuditLog($pdo, $user_name, $role, 'Reject', 'Request', "Rejected Request '$reqTitle' — Remarks: $remarks");
                addNotification($pdo, $user_id, 'Request Rejected', "Request '$reqTitle' was rejected. Remarks: $remarks", 'fa-times');
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $success = false;
        }
    }

    echo json_encode(["success" => $success, "message" => $success ? "Update successful" : "Update failed"]);
    exit;
}


echo json_encode(['error' => 'Invalid request']);