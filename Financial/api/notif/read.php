<?php
include_once __DIR__ . '/../../utility/connn.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 1;

$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);

echo json_encode(["status" => "success"]);
?>
