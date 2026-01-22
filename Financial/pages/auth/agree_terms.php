<?php
declare(strict_types=1);
session_start();
include_once '../../utility/connection.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("UPDATE settings.users SET agreed_terms = 1 WHERE id = ?");
$stmt->execute([$user_id]);

echo 'ok';
?>
