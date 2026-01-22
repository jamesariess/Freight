<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "SELECT * FROM ar_ap.ar_adjustments WHERE adjustment_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $errorMessage = "❌ Error fetching adjustment: " . $e->getMessage();
        error_log("Fetch adjustment error for adjustment_id #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['archive'])) {
        $adjustment_id = $_POST['adjustment_id'];

        if (empty($adjustment_id) || !is_numeric($adjustment_id)) {
            $_SESSION['errorMessage'] = "❌ Valid adjustment ID is required.";
            error_log("Validation error for archive: Invalid adjustment_id");
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

  
        $stmtAdjust = $pdo->prepare("
            SELECT adjustment_id, invoice_id, amount 
            FROM ar_ap.ar_adjustments 
            WHERE adjustment_id = :adjustment_id
        ");
        $stmtAdjust->execute([':adjustment_id' => $adjustment_id]);
        $adjustData = $stmtAdjust->fetch(PDO::FETCH_ASSOC);
        $invoice_id = $adjustData ? $adjustData['invoice_id'] : 'N/A';
        $amount = $adjustData && isset($adjustData['amount']) ? number_format($adjustData['amount'], 2) : 'Unknown Amount';

        $sql = "UPDATE ar_ap.ar_adjustments SET Archive = 'YES' WHERE adjustment_id = :adjustment_id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':adjustment_id', $adjustment_id);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for adjustment_id: $adjustment_id");
            }
       
            $auditDescription = "Archived adjustment #$adjustment_id (Invoice ID: $invoice_id, Amount: ₱$amount).";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Adjustment', $auditDescription);
            $notifMessage = "Adjustment #$adjustment_id (Invoice ID: $invoice_id) archived.";
            addNotification($pdo, $user_id, 'Adjustment Archived', $notifMessage, 'fa-archive');
            $_SESSION['successMessage'] = "✅ Adjustment archived successfully.";
        } catch (PDOException $e) {
            $_SESSION['errorMessage'] = "❌ Error archiving adjustment: " . $e->getMessage();
            error_log("Archive adjustment error for adjustment_id #$adjustment_id: " . $e->getMessage());
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['update'])) {
        $adjustment_id = $_POST['adjustment_id'];
        $invoice_id = $_POST['invoice_id'];
        $type = trim($_POST['type'] ?? '');
        $amount = trim($_POST['amount'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $status = trim($_POST['status'] ?? '');

    
        $validationErrors = [];
        if (empty($adjustment_id) || !is_numeric($adjustment_id)) {
            $validationErrors[] = "Valid adjustment ID is required.";
        }
        if (empty($invoice_id) || !is_numeric($invoice_id)) {
            $validationErrors[] = "Valid invoice ID is required.";
        }
        if (empty($type)) {
            $validationErrors[] = "Type is required.";
        } elseif (!in_array($type, ['Credit', 'Debit', 'Discount', 'Write-off'])) {
            $validationErrors[] = "Invalid type (e.g., Credit, Debit, Discount, Write-off).";
        }
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $validationErrors[] = "Valid amount is required.";
        }
        if (empty($reason)) {
            $validationErrors[] = "Reason is required.";
        } elseif (strlen($reason) > 255) {
            $validationErrors[] = "Reason must not exceed 255 characters.";
        }
        if (empty($status) || !in_array($status, ['Pending', 'Approved', 'Rejected'])) {
            $validationErrors[] = "Valid status (Pending, Approved, Rejected) is required.";
        }

        if (empty($validationErrors)) {
      
            $stmtAdjust = $pdo->prepare("
                SELECT invoice_id, type, amount, reason, status 
                FROM ar_ap.ar_adjustments 
                WHERE adjustment_id = :adjustment_id
            ");
            $stmtAdjust->execute([':adjustment_id' => $adjustment_id]);
            $adjustData = $stmtAdjust->fetch(PDO::FETCH_ASSOC);
            $oldInvoiceId = $adjustData ? $adjustData['invoice_id'] : 'N/A';
            $oldType = $adjustData ? $adjustData['type'] : 'N/A';
            $oldAmount = $adjustData && isset($adjustData['amount']) ? number_format($adjustData['amount'], 2) : 'N/A';
            $oldReason = $adjustData ? $adjustData['reason'] : 'N/A';
            $oldStatus = $adjustData ? $adjustData['status'] : 'N/A';

            $sql = "UPDATE ar_ap.ar_adjustments SET 
                    invoice_id = :invoice_id,
                    type = :type,
                    amount = :amount,
                    reason = :reason,
                    status = :status
                    WHERE adjustment_id = :adjustment_id";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':invoice_id', $invoice_id);
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':reason', $reason);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':adjustment_id', $adjustment_id);
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No rows updated for adjustment_id: $adjustment_id");
                }
                
                $auditDescription = "Updated adjustment #$adjustment_id. Changes: Invoice ID from '$oldInvoiceId' to '$invoice_id', Type from '$oldType' to '$type', Amount from ₱$oldAmount to ₱" . number_format($amount, 2) . ", Reason from '$oldReason' to '$reason', Status from '$oldStatus' to '$status'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Adjustment', $auditDescription);
                $notifMessage = "Adjustment #$adjustment_id (Invoice ID: $invoice_id) updated.";
                addNotification($pdo, $user_id, 'Adjustment Updated', $notifMessage, 'fa-edit');
                $_SESSION['successMessage'] = "✅ Adjustment updated successfully.";
            } catch (PDOException $e) {
                $_SESSION['errorMessage'] = "❌ Error updating adjustment: " . $e->getMessage();
                error_log("Update adjustment error for adjustment_id #$adjustment_id: " . $e->getMessage());
            }
        } else {
            $_SESSION['errorMessage'] = "❌ Validation errors: " . implode(' ', $validationErrors);
            error_log("Validation errors for adjustment update (adjustment_id #$adjustment_id): " . implode(' ', $validationErrors));
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

try {
    $sql = "SELECT * FROM ar_ap.ar_adjustments ar
    JOIN ar_invoice p ON ar.bill_id = p.invoice_id
     WHERE  ar.Archive = 'NO' ORDER BY ar.created_at ASC";
    $stmt = $pdo->query($sql);
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "❌ Error fetching adjustments: " . $e->getMessage();
    error_log("Fetch adjustments error: " . $e->getMessage());
    $adjustments = [];
}
?>