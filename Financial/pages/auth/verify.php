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
include_once '../../utility/connection.php';
 require '../../PHPMailer-master/src/Exception.php';
            require '../../PHPMailer-master/src/PHPMailer.php';
            require '../../PHPMailer-master/src/SMTP.php';
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;

define('OTP_TTL', 300);
define('SESSION_IDLE_TIMEOUT', 900);


if (empty($_SESSION['user_id_pending']) || empty($_SESSION['email_pending']) || empty($_SESSION['verification_code'])) {
    header("Location: login.php");
    exit();
}


if (isset($_SESSION['verification_expires']) && time() > $_SESSION['verification_expires']) {
 
    unset($_SESSION['verification_code'], $_SESSION['verification_expires'], $_SESSION['user_id_pending'], $_SESSION['email_pending']);
    $expired = true;
} else {
    $expired = false;
}

$error_message = $success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $input = trim($_POST['code'] ?? '');
     
        $input = preg_replace('/\D/', '', $input);

        if (empty($_SESSION['verification_code']) || $expired) {
            $error_message = "The verification code has expired. Please log in again to request a new code.";
        } elseif ($input === (string)$_SESSION['verification_code']) {
      
            session_regenerate_id(true);
            $_SESSION['user_id'] = $_SESSION['user_id_pending'];
            $_SESSION['email'] = $_SESSION['email_pending'];
            $_SESSION['last_activity'] = time();

            unset($_SESSION['user_id_pending'], $_SESSION['email_pending'], $_SESSION['verification_code'], $_SESSION['verification_expires']);

            header("Location: ../../pages/dashboard/dashboard.php");
            exit();
        } else {
            $error_message = "Invalid verification code.";
        }
    } elseif (isset($_POST['resend'])) {
        
        $email = $_SESSION['email_pending'];
        if (!isset($_SESSION['otp_requests'][$email])) $_SESSION['otp_requests'][$email] = [];
       
        $_SESSION['otp_requests'][$email] = array_filter(
            $_SESSION['otp_requests'][$email],
            function($ts){ return ($ts + 600) > time(); }
        );
        if (count($_SESSION['otp_requests'][$email]) >= 3) {
            $error_message = "Too many OTP requests. Please wait and try again later.";
        } else {
            $verification_code = random_int(100000,999999);
            $_SESSION['verification_code'] = (string)$verification_code;
            $_SESSION['verification_expires'] = time() + OTP_TTL;
            $_SESSION['otp_requests'][$email][] = time();
        
           
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'slatetransportsystem@gmail.com';
                $mail->Password   = 'mfkkigrgxtoascov';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPOptions = ['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
                $mail->setFrom('slatetransportsystem@gmail.com','Slate Account Management');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your SLATE Verification Code';
                $mail->Body = "Your verification code is: <b>{$verification_code}</b>. This code is valid for 5 minutes.";
                $mail->AltBody = "Your verification code is: {$verification_code}.";
                $mail->send();
                $success_message = "A new verification code has been sent to your email.";
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
                $error_message = "Unable to send verification email at the moment. Please try again later.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verify - SLATE</title>
  <link rel="stylesheet" href="../../static/css/login.css">
</head>
<body>
  <div class="verify-wrapper">
    <div class="verify-card" role="dialog" aria-labelledby="verifyTitle">
      <div class="verify-logo"><img src="../../image/logo.png" alt="SLATE" style="height:52px"></div>
      <h3 id="verifyTitle">Verify your account</h3>
      <p>Enter the 6-digit code sent to <strong><?=htmlspecialchars($_SESSION['email_pending'] ?? '')?></strong>. The code expires in 5 minutes.</p>

      <?php if (!empty($error_message)): ?><div class="error-message"><?=htmlspecialchars($error_message)?></div><?php endif; ?>
      <?php if (!empty($success_message)): ?><div class="success-message"><?=htmlspecialchars($success_message)?></div><?php endif; ?>

      <form method="post" style="display:flex;flex-direction:column;gap:12px">
        <div class="otp-inputs">
  <div class="otp-boxes">
    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box" name="otp[]" required>
    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box" name="otp[]" required>
    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box" name="otp[]" required>
    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box" name="otp[]" required>
    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box" name="otp[]" required>
    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box" name="otp[]" required>
  </div>
</div>
<input type="hidden" name="code" id="hiddenCode">


        <div class="verify-actions">
          <button type="submit" name="verify_code" class="btn btn-primary">Verify & Continue</button>
          <button type="submit" name="resend" class="btn btn-ghost">Resend Code</button>
        </div>
        <div class="resend">
          <small>If you didn't receive the email, check spam or click <strong>Resend Code</strong>.</small>
        </div>
      </form>
      <div style="margin-top:12px;text-align:center">
        <a href="login.php" style="color:var(--accent2);text-decoration:none">Back to login</a>
      </div>
    </div>
  </div>
  <script>
const inputs = document.querySelectorAll('.otp-box');
const hiddenCode = document.getElementById('hiddenCode');

inputs.forEach((input, index) => {
  input.addEventListener('input', e => {
    const value = e.target.value.replace(/\D/, '');
    e.target.value = value;
    if (value && index < inputs.length - 1) inputs[index + 1].focus();
    hiddenCode.value = Array.from(inputs).map(i => i.value).join('');
  });

  input.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !e.target.value && index > 0) {
      inputs[index - 1].focus();
    }
  });

  input.addEventListener('paste', e => {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    paste.split('').forEach((char, i) => { if (inputs[i]) inputs[i].value = char; });
    hiddenCode.value = paste;
  });
});
</script>

</body>
</html>
