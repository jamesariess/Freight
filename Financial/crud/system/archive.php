<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once __DIR__ . '/../../utility/connection.php';

$tables = [
    'adjustment' => ['key' => 'adjustID'],
    'allocationadjustment' => ['key' => 'allocateadjustmentID'],
    'ap_payments' => ['key' => 'payment_id'],
    'ar_collections' => ['key' => 'collection_id'],
    'ar_invoices' => ['key' => 'invoice_id'],

     'ap_adjustments' => ['key' => 'invoice_id'],
      'ap_bills' => ['key' => 'bill_id'],
      
        'bank' => ['key' => 'bankID'],
         'audit_log' => ['key' => 'id'],
          'budgetplanning' => ['key' => 'bugdetID'],
           'chartofaccount' => ['key' => 'accountID'],
            'collection_plan' => ['key' => 'planID'],
             'costallocation' => ['key' => 'allocationID'],
              'departmentbudget' => ['key' => 'Deptbudget'],
               'details' => ['key' => 'entriesID'],

               'entries' => ['key' => 'journalID'],
          'dispute' => ['key' => 'dputeID'],
           'follow' => ['key' => 'reminderID'],
            'funds' => ['key' => 'fundsID'],
             'payment' => ['key' => 'paymentID'],
              'paymentmethod' => ['key' => 'payment_method_id'],
               'periods' => ['key' => 'period_id'],

                         'receipt' => ['key' => 'receiptID'],
           'request' => ['key' => 'requestID'],
              'vendor' => ['key' => 'vendor_id'],
           


];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


if (isset($_GET['listTables'])) {
    echo json_encode(['status' => 'success', 'tables' => array_keys($tables)]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tableFilter = $_GET['table'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $data = [];

    foreach ($tables as $table => $meta) {
        if ($tableFilter !== 'all' && $tableFilter !== $table) continue;

        try {
            $query = "SELECT * FROM $table WHERE archive = 'yes'";
            $params = [];

            if ($search) {

                $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                $searchParts = [];
                foreach ($cols as $col) $searchParts[] = "$col LIKE ?";
                $query .= " AND (" . implode(" OR ", $searchParts) . ")";
                $params = array_fill(0, count($cols), "%$search%");
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) $data[$table] = $rows;
        } catch (PDOException $e) {
            $data[$table] = ['error' => $e->getMessage()];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['table']) || !isset($input['key_value'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    $table = $input['table'];
    $key_value = $input['key_value'];

    if (!isset($tables[$table])) {
        echo json_encode(['status' => 'error', 'message' => 'Unknown table']);
        exit;
    }

    $key_col = $tables[$table]['key'];

    try {
        $stmt = $pdo->prepare("UPDATE $table SET archive = 'no' WHERE $key_col = ?");
        $stmt->execute([$key_value]);
        echo json_encode(['status' => 'success', 'message' => 'Record restored']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unsupported method']);
