<?php

require_once __DIR__ . '/../../utility/connection.php'; 
include_once('../../utility/head.php');
file_put_contents('session_debug.log', print_r($_SESSION, true) . "\n", FILE_APPEND);



function is_admin() {
    if (!isset($_SESSION['user_id'])) {
        file_put_contents('session_debug.log', "is_admin: No user_id in session\n", FILE_APPEND);
        return false;
    }
    $user_id = $_SESSION['user_id'];
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT role FROM settings.users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $isAdmin = ($user && $user['role'] === 'Admin');
        // Log for debugging
        file_put_contents('session_debug.log', "is_admin check for user_id $user_id: " . ($isAdmin ? 'true' : 'false') . "\n", FILE_APPEND);
        return $isAdmin;
    } catch (PDOException $e) {
        file_put_contents('session_debug.log', "Database error in is_admin: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error in authentication']);
        exit;
    }
}

// Allow OPTIONS without authentication since it's just returning static permissions
$method = $_SERVER['REQUEST_METHOD'];
$ALLOWED_PERMS = [
    'can_create_user' => 'Create',
    'can_edit_user' => 'Update',
    'can_archive_user' => 'Archive',
    'can_view_reports' => 'View reports',
    'can_approve_payments' => 'Approve payments',
    'can_follow_up_collections' => 'Follow up collections',
    'can_manage_finance' => 'Manage finance'
];

if ($method === 'OPTIONS') {
    echo json_encode(['status' => 'success', 'permissions' => $ALLOWED_PERMS]);
    ob_end_flush(); // Send output and clear buffer
    exit;
}

// Check for authentication for other methods
if (!isset($_SESSION['user_id']) || !is_admin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Not logged in or not an admin']);
    ob_end_flush();
    exit;
}

if ($method === 'GET') {
    // List users
    try {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        if (strlen($q) > 100) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Search query too long']);
            ob_end_flush();
            exit;
        }

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT id, username, email, role, permissions, status, created_at, updated_at
                FROM settings.users
                WHERE status = 1";
        $params = [];

        if ($q !== '') {
            if (!preg_match('/^[a-zA-Z0-9\s@._-]+$/', $q)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid search characters']);
                ob_end_flush();
                exit;
            }
            $sql .= " AND (username LIKE :q OR email LIKE :q)";
            $params[':q'] = "%$q%";
        }

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM settings.users WHERE status = 1" . ($q !== '' ? " AND (username LIKE :q OR email LIKE :q)" : "");
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['permissions'] = $r['permissions'] ? json_decode($r['permissions'], true) : [];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'page' => $page,
            'total' => $total,
            'limit' => $limit
        ]);
        ob_end_flush();
    } catch (PDOException $e) {
        file_put_contents('session_debug.log', "Database error in GET: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
        ob_end_flush();
    }
} elseif ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || !isset($payload['id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        ob_end_flush();
        exit;
    }

    $userId = (int)$payload['id'];

    try {
        // Check if user exists and is active
        $stmt = $pdo->prepare("SELECT permissions, status FROM settings.users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['status'] != 1) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found or inactive']);
            ob_end_flush();
            exit;
        }

        if (isset($payload['archive']) && $payload['archive'] === true) {
            // Archive user
            $stmt = $pdo->prepare("UPDATE settings.users SET status = 0, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            echo json_encode(['status' => 'success', 'message' => 'User archived']);
            ob_end_flush();
        } else {
            // Update permissions
            if (!isset($payload['permissions'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Permissions required']);
                ob_end_flush();
                exit;
            }

            $permissionsInput = (array)$payload['permissions'];
            $existingPerms = $user['permissions'] ? json_decode($user['permissions'], true) : [];
            $filtered = [];
            foreach ($ALLOWED_PERMS as $k => $label) {
                $filtered[$k] = isset($permissionsInput[$k]) ? (bool)$permissionsInput[$k] : (isset($existingPerms[$k]) ? $existingPerms[$k] : false);
            }

            $stmt = $pdo->prepare("UPDATE settings.users SET permissions = :p, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':p' => json_encode($filtered), ':id' => $userId]);

            echo json_encode(['status' => 'success', 'message' => 'Permissions updated', 'data' => $filtered]);
            ob_end_flush();
        }
    } catch (PDOException $e) {
        file_put_contents('session_debug.log', "Database error in POST: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
        ob_end_flush();
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    ob_end_flush();
}

?>