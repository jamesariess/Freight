<?php
include_once __DIR__ . '/../../utility/connn.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 1; // Replace with real user ID

$sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 15";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($notifications);
?>
