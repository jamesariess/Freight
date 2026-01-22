<?php
declare(strict_types=1);
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

define('SESSION_IDLE_TIMEOUT', 900); 

if (empty($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT)) {
    session_unset();
    session_destroy();
    header("Location: ../../pages/auth/login.php?message=timeout");
    exit();
} 

$_SESSION['last_activity'] = time();
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, role, agreed_terms FROM settings.users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$show_terms_popup = ($user && !$user['agreed_terms']);
$user_name = $user['username'] ?? 'Unknown User';
$role = $user['role'] ?? 'Unknown Role';

?>
