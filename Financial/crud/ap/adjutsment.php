<?php

include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$id = $_GET['id'] ?? null;
$sql = "SELECT * FROM ar_ap.ar_adjustments WHERE adjustment_id   = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
if(isset($_POST['archive'])) {
        $archive_adjustID = $_POST['archive_collectionID'];

        $sql = "UPDATE ar_ap.ar_adjustments SET 
                Archive = 'YES'
                WHERE adjustment_id  = :archive_adjustID";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':archive_adjustID', $archive_adjustID);

        try {
            $stmt->execute();
            echo "✅ Adjustment archived successfully.";
        } catch (PDOException $e) {
            echo "❌ Error: " . $e->getMessage();
        }
    }if (isset($_POST['update'])) {
        $adjustment_id = $_POST['adjustment_id'];
        $invoice_id = $_POST['invoice_id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $reason = $_POST['reason'];
        $status = $_POST['status'];
        $created_at = date('Y-m-d H:i:s');  
        $sql = "UPDATE ar_ap.ar_adjustments SET 
                invoice_id = :invoice_id,
                type = :type,
                amount = :amount,
                reason = :reason,
                status = :status,
                created_at = :created_at
                WHERE adjustment_id = :adjustment_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':adjustment_id', $adjustment_id);
        try {
            $stmt->execute();
            echo "✅ Adjustment updated successfully.";
        } catch (PDOException $e) {
            echo "❌ Error: " . $e->getMessage();
        }
       
    }
   
}

try {
    $sql = "SELECT * FROM  ar_ap.ar_adjustments WHERE Archive = 'NO'
            ORDER BY created_at Asc";
    $stmt = $pdo->query($sql);
    $adjustReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "❌ Error fetching plans: " . $e->getMessage();
}


?>

