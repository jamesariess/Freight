<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "SELECT * FROM collection.payment WHERE paymentID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $errorMessage = "❌ Error fetching payment: " . $e->getMessage();
        error_log("Fetch payment error for paymentID #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    if (isset($_POST['archive'])) {
        $paymentID = $_POST['paymentID'];

       
        $stmtPayment = $pdo->prepare("
            SELECT amount, paymentDate 
            FROM collection.payment 
            WHERE paymentID = :paymentID
        ");
        $stmtPayment->execute([':paymentID' => $paymentID]);
        $paymentData = $stmtPayment->fetch(PDO::FETCH_ASSOC);
        $amount = $paymentData ? number_format($paymentData['amount'], 2) : 'Unknown Amount';
        $paymentDate = $paymentData ? $paymentData['paymentDate'] : 'Unknown Date';

        $sql = "UPDATE collection.payment SET Archive = 'YES' WHERE paymentID = :paymentID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':paymentID', $paymentID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for paymentID: $paymentID");
            }
      
            $auditDescription = "Archived payment #$paymentID (Amount: ₱$amount, Date: $paymentDate).";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Payment', $auditDescription);
            $notifMessage = "Payment #$paymentID (₱$amount) archived.";
            addNotification($pdo, $user_id, 'Payment Archived', $notifMessage, 'fa-archive');
            $successMessage = "✅ Payment archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error archiving payment: " . $e->getMessage();
            error_log("Archive payment error for paymentID #$paymentID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $paymentID = $_POST['paymentID'];
        $remarks = $_POST['remarks'];

        if (empty($remarks)) {
            $errorMessage = "❌ Don't leave remarks empty.";
            exit;
        }

        $stmtPaymentDetails = $pdo->prepare("
            SELECT amount, remarks, paymentDate 
            FROM collection.payment 
            WHERE paymentID = :paymentID
        ");
        $stmtPaymentDetails->execute([':paymentID' => $paymentID]);
        $paymentDetails = $stmtPaymentDetails->fetch(PDO::FETCH_ASSOC);
        $amount = $paymentDetails ? number_format($paymentDetails['amount'], 2) : 'Unknown Amount';
        $oldRemarks = $paymentDetails ? $paymentDetails['remarks'] : 'N/A';
        $paymentDate = $paymentDetails ? $paymentDetails['paymentDate'] : 'Unknown Date';

        try {
            $pdo->beginTransaction();

         
            $sql = "UPDATE collection.payment SET remarks = :remarks WHERE paymentID = :paymentID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':remarks', $remarks);
            $stmt->bindParam(':paymentID', $paymentID);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for paymentID: $paymentID");
            }

            $sqlPaymentDetails = "SELECT amount FROM collection.payment WHERE paymentID = :paymentID";
            $stmtPaymentDetails = $pdo->prepare($sqlPaymentDetails);
            $stmtPaymentDetails->bindParam(':paymentID', $paymentID);
            $stmtPaymentDetails->execute();
            $paymentData = $stmtPaymentDetails->fetch(PDO::FETCH_ASSOC);
            $journalAmount = $paymentData['amount'];

            $sqlEntries = "
                INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive)
                VALUES (CURDATE(), :description, :ref, :createdBy, 'NO')
            ";
            $stmtEntries = $pdo->prepare($sqlEntries);
            $stmtEntries->execute([
                ':description' => 'Customer Payment Received - Updated Remarks',
                ':ref' => 'Payment-' . $paymentID,
                ':createdBy' => 'User'
            ]);
            $journalID = $pdo->lastInsertId();

            $sqlDebit = "
                INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $stmtDebit = $pdo->prepare($sqlDebit);
            $stmtDebit->execute([
                ':journalID' => $journalID,
                ':accountID' => 1, 
                ':debit' => $journalAmount,
                ':credit' => 0
            ]);

            $sqlCredit = "
                INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $stmtCredit = $pdo->prepare($sqlCredit);
            $stmtCredit->execute([
                ':journalID' => $journalID,
                ':accountID' => 3,
                ':debit' => 0,
                ':credit' => $journalAmount
            ]);

            $pdo->commit();

            
            $auditDescription = "Updated payment #$paymentID (Amount: ₱$amount, Date: $paymentDate). Remarks changed from '$oldRemarks' to '$remarks'. Journal entries created for accounting.";
            addAuditLog($pdo, $user_name, $role, 'Update', 'Payment', $auditDescription);
            $notifMessage = "Payment #$paymentID (₱$amount) remarks updated and journal entries created.";
            addNotification($pdo, $user_id, 'Payment Updated', $notifMessage, 'fa-edit');
            $successMessage = "✅ Remarks updated successfully and journal entries created.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = "❌ Error: " . $e->getMessage();
            error_log("Update payment error for paymentID #$paymentID: " . $e->getMessage());
        }
    }
}

try {
    $sql = "SELECT * FROM collection.payment WHERE Archive = 'NO'
            ORDER BY paymentDate ASC";
    $stmt = $pdo->query($sql);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "❌ Error fetching payments: " . $e->getMessage();
    error_log("Fetch payments error: " . $e->getMessage());
}
?>