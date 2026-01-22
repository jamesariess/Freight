<?php

include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');

date_default_timezone_set('Asia/Manila');

$id = $_GET['id'] ?? null;
$sql = "SELECT * FROM ar_ap.customers WHERE customer_id  = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();



try {
    $sql = "SELECT * FROM  ar_ap.customers WHERE Archive = 'NO'
            ORDER BY created_at Asc";
    $stmt = $pdo->query($sql);
    $custumerReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "❌ Error fetching plans: " . $e->getMessage();
}


?>

