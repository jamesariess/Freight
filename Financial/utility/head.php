<?php 


 include_once 'session_check.php';

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    } else {
        return 'UNKNOWN';
    }
}

function addAuditLog($pdo, $user_name, $role, $action, $module, $details) {
    $ip_address = getUserIP();
    $sql = "INSERT INTO settings.audit_log (user_name, role, action, module, ip_address, details)
            VALUES (:user_name, :role, :action, :module, :ip_address, :details)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_name' => $user_name,
        ':role' => $role,
        ':action' => $action,
        ':module' => $module,
        ':ip_address' => $ip_address,
        ':details' => $details
    ]);
}

function addNotification($pdo, $user_id, $title, $message, $icon = 'fa-bell') {
    $sql = "INSERT INTO settings.notifications (user_id, title, message, icon)
            VALUES (:user_id, :title, :message, :icon)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $title,
        ':message' => $message,
        ':icon' => $icon
    ]);
}         

?>