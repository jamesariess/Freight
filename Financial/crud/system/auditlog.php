<?php
include_once __DIR__ . '/../../utility/connection.php';
header('Content-Type: application/json');

try {
  
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;


    $whereClauses = [];
    $bindings = [];

    if (!empty($_GET['user_name'])) {
        $whereClauses[] = "user_name LIKE :user_name";
        $bindings[':user_name'] = '%' . $_GET['user_name'] . '%';
    }
    if (!empty($_GET['role'])) {
        $whereClauses[] = "role = :role";
        $bindings[':role'] = $_GET['role'];
    }
    if (!empty($_GET['action'])) {
        $whereClauses[] = "action = :action";
        $bindings[':action'] = $_GET['action'];
    }
    if (!empty($_GET['module'])) {
        $whereClauses[] = "module = :module";
        $bindings[':module'] = $_GET['module'];
    }
    if (!empty($_GET['ip_address'])) {
        $whereClauses[] = "ip_address LIKE :ip_address";
        $bindings[':ip_address'] = '%' . $_GET['ip_address'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $whereClauses[] = "timestamp >= :date_from";
        $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $whereClauses[] = "timestamp <= :date_to";
        $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Count total rows for pagination
    $countSql = "SELECT COUNT(*) FROM audit_log $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($bindings as $k => $v) {
        $countStmt->bindValue($k, $v);
    }
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
    $totalPages = (int)ceil($totalRows / $limit);

    // Fetch paginated and filtered logs
    $sql = "SELECT * FROM audit_log $whereSql ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode([
        'logs' => $logs,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'limit' => $limit
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>