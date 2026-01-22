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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer-master/src/Exception.php';
require '../../PHPMailer-master/src/PHPMailer.php';
require '../../PHPMailer-master/src/SMTP.php';
include_once '../../utility/connection.php'; 

$form_to_display = 'login';
$error_message = $success_message = '';


define('MAX_LOGIN_ATTEMPTS', 3);
$lockout_levels = [1 => 60, 2 => 300, 3 => 900]; 
define('OTP_TTL', 300);
define('MAX_OTP_PER_WINDOW', 3);
define('OTP_WINDOW_SECONDS', 600); 
define('SESSION_IDLE_TIMEOUT', 900); 

function sendVerificationEmail($email, $code): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'slatetransportsystem@gmail.com';
        $mail->Password   = 'mfkkigrgxtoascov';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('slatetransportsystem@gmail.com', 'Slate Account Management');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your SLATE Verification Code';
        $mail->Body    = "Your verification code is: <b>{$code}</b>. This code is valid for 5 minutes.";
        $mail->AltBody = "Your verification code is: {$code}. This code is valid for 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


if (!isset($_SESSION['otp_requests'])) {
    $_SESSION['otp_requests'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $form_to_display = 'login';
    $input_email = trim($_POST['username'] ?? '');
    $input_password = $_POST['password'] ?? '';


    $sql = "SELECT id, password, failed_attempts, lockout_until FROM settings.users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error_message = "Invalid email or password.";
    } else {
        $now = new DateTime();

        if (!empty($user['lockout_until']) && strtotime($user['lockout_until']) > time()) {
            $remaining = strtotime($user['lockout_until']) - time();
            $minutes = ceil($remaining / 60);
            $error_message = "Your account is locked. Please try again in {$minutes} minute(s).";
        } else {
   
            if (password_verify($input_password, $user['password'])) {
            
                $sql_reset = "UPDATE settings.users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?";
                $pdo->prepare($sql_reset)->execute([$user['id']]);


                $emailKey = $input_email;
                $_SESSION['otp_requests'][$emailKey] = array_filter(
                    $_SESSION['otp_requests'][$emailKey] ?? [],
                    function($ts){ return ($ts + OTP_WINDOW_SECONDS) > time(); }
                );
                if (count($_SESSION['otp_requests'][$emailKey] ?? []) >= MAX_OTP_PER_WINDOW) {
                    $error_message = "Too many OTP requests. Please wait and try again later.";
                } else {
                    $verification_code = random_int(100000, 999999);
                    $_SESSION['user_id_pending'] = $user['id'];
                    $_SESSION['email_pending'] = $input_email;
                    $_SESSION['verification_code'] = (string)$verification_code;
                    $_SESSION['verification_expires'] = time() + OTP_TTL;


                    $_SESSION['otp_requests'][$emailKey][] = time();

               
                    if (!isset($_SESSION['lockout_level'][$user['id']])) {
                        $_SESSION['lockout_level'][$user['id']] = 0;
                    }
                    sendVerificationEmail($input_email, $verification_code);
                    header("Location: verify.php");
                    exit();
                }
            } else {
               
                $failed_attempts = ($user['failed_attempts'] ?? 0) + 1;
                $lockout_until = null;

                if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
  
                    $level = ($_SESSION['lockout_level'][$user['id']] ?? 0) + 1;
                    $_SESSION['lockout_level'][$user['id']] = min($level, 3);
                    $duration = $lockout_levels[$_SESSION['lockout_level'][$user['id']]];
                    $lockout_until = date('Y-m-d H:i:s', time() + $duration);
                    $error_message = "Incorrect password. Your account is locked for " . ceil($duration/60) . " minute(s).";
                    $failed_attempts = 0; 
                } else {
                    $remaining = MAX_LOGIN_ATTEMPTS - $failed_attempts;
                    $error_message = "Invalid email or password. You have {$remaining} attempts remaining.";
                }

                $sql_update = "UPDATE settings.users SET failed_attempts = ?, lockout_until = ? WHERE id = ?";
                $pdo->prepare($sql_update)->execute([$failed_attempts, $lockout_until, $user['id']]);
            }
        }
    }
}

if (!empty($_SESSION['user_id']) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] < SESSION_IDLE_TIMEOUT)) {
    header("Location: ../../pages/dashboard/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SLATE Login</title>
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../static/css/login.css">
  <style>
   
  </style>
</head>
<body>
  <div class="main-container">
    <div class="auth-container">
      <div class="welcome-panel">
        <div class="welcome-panel centered">
        
           <h1>FREIGHT MANAGEMENT SYSTEM</h1>
          </div>
             </div>
      <div class="auth-panel">
        <div class="auth-box">
          <img src="../../image/logo.png" alt="SLATE Logo">
          <div id="login-form-container">
            <h2>SLATE Login</h2>
            <?php if (!empty($error_message)): ?><div class="error-message"><?=htmlspecialchars($error_message)?></div><?php endif; ?>
            <?php if (!empty($success_message)): ?><div class="success-message"><?=htmlspecialchars($success_message)?></div><?php endif; ?>
            <form method="POST" novalidate>
              <input type="email" name="username" placeholder="Email" required value="<?=htmlspecialchars($_POST['username'] ?? '')?>">
             <div class="password-wrapper">
               <input type="password" id="password" name="password" placeholder="Password">
               <i class="fa-solid fa-eye" id="togglePassword"></i>
              </div>
           <div class="login-options">
           <p><i class="fa-solid fa-circle-info"></i> Having trouble signing in? <a href="https://mail.google.com/mail/?view=cm&fs=1&to=slatetransportsystem@gmail.com&su=Problem%20signing%20in%20to%20my%20SLATE%20Financial%20Account" target="_blank">Contact Support</a></p>
           </div>
                
              <button type="submit" name="login">Log In</button>
            </form>
           
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer>&copy; <span id="currentYear"></span> SLATE Freight Management System. All rights reserved.</footer>

  <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
  <script>
  const toggle = document.querySelector('#togglePassword');
  const password = document.querySelector('#password');
  toggle.addEventListener('click', () => {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    toggle.classList.toggle('fa-eye-slash');
  });
</script>
</body>
</html>
