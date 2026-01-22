<?php

session_start();
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['type'], $data['msg'])) {
    $_SESSION[$data['type'] . 'Message'] = $data['msg'];
}
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
exit;