<?php


require_once __DIR__ . '/../../utility/connection.php';
header('Content-Type: application/json');
include_once('../../utility/head.php');


function json_error($message, $code = 400) {
    http_response_code($code);
    global $errorMessage;
    $errorMessage = $message; 
    echo json_encode(['error' => $message]);
    exit;
}

$successMessage = "";
$errorMessage = "";


$method = $_SERVER['REQUEST_METHOD'];


$action = '';
if ($method === 'POST') {
    $input = file_get_contents('php://input');
    if ($input) {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($content_type, 'application/json') !== false) {
            $payload = json_decode($input, true);
            $action = $payload['action'] ?? '';
        } else {
            parse_str($input, $postData);
            $action = $postData['action'] ?? '';
        }
        
        error_log("Received POST data (raw): " . $input);
        error_log("Content-Type: " . $content_type);
        error_log("Parsed action: " . $action);
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
}


if (!isset($pdo) || !$pdo) {
    json_error('Database connection failed', 500);
    exit;
}


if ($method === 'GET') {
    if (isset($_GET['journalID'])) {
        $journalID = $_GET['journalID'];
        try {
            $headerStmt = $pdo->prepare("
                SELECT journalID, date, description, referenceType, createdBy, status, posted_by, posted_at
                FROM ledger.entries
                WHERE journalID = ?
            ");
            $headerStmt->execute([$journalID]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT d.entriesID, d.journalID, d.accountID, d.debit, d.credit,
                       c.accountName
                FROM ledger.details d
                LEFT JOIN ledger.chartofaccount c ON d.accountID = c.accountID
                WHERE d.journalID = ?
                ORDER BY d.entriesID ASC
            ");
            $stmt->execute([$journalID]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$header) {
                json_error('Journal not found', 404);
            }

            $result = [
                'header' => $header,
                'details' => $details
            ];
            echo json_encode($result);
        } catch (PDOException $e) {
            json_error($e->getMessage(), 500);
        }
    } else {
        try {
            $stmt = $pdo->query("
                SELECT e.journalID, e.date, e.description, e.referenceType, e.createdBy, e.status, e.posted_by, e.posted_at,
                       COALESCE(p.status, 'Open') as periodStatus,
                       e.Archive
                FROM ledger.entries e
                LEFT JOIN ledger.periods p ON p.year = YEAR(e.date) AND p.month = MONTH(e.date)
                WHERE e.Archive != 'YES' 
                ORDER BY FIELD(e.status, 'Draft') DESC, e.date DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            json_error($e->getMessage(), 500);
        }
    }
    exit;
}

if ($method === 'POST' && $action === 'add') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) json_error('Invalid JSON');

    $date = $payload['date'] ?? date('Y-m-d H:i:s');
    $currentYear = date('Y', strtotime($date));
    $currentMonth = date('n', strtotime($date));

    $stmt = $pdo->prepare("SELECT status FROM ledger.periods WHERE year = :year AND month = :month");
    $stmt->execute([':year' => $currentYear, ':month' => $currentMonth]);
    $status = $stmt->fetchColumn() ?: 'Open';

    if ($status === 'Closed' || $status === 'Locked') {
        json_error("The accounting period ($currentYear-$currentMonth) is $status. No changes allowed.");
    }

    $description = $payload['description'] ?? '';
    $referenceType = $payload['referenceType'] ?? '';
    $createdBy = $user_name;
    $details = $payload['details'] ?? [];

    if (!is_array($details) || count($details) < 1) {
        json_error('Missing details');
    }

    $totalDebit = array_sum(array_column($details, 'debit'));
    $totalCredit = array_sum(array_column($details, 'credit'));
    if (abs($totalDebit - $totalCredit) > 0.001) {
        $errorMessage = "Journal is not balanced.";
        json_error($errorMessage);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive, status)
            VALUES (?, ?, ?, ?, 'NO', 'Draft')
        ");
        $stmt->execute([$date, $description, $referenceType, $createdBy]);
        $journalID = $pdo->lastInsertId();

        $ins = $pdo->prepare("
            INSERT INTO ledger.details (journalID, accountID, debit, credit)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($details as $d) {
            $ins->execute([
                $journalID,
                $d['accountID'],
                floatval($d['debit']),
                floatval($d['credit'])
            ]);
        }

        $pdo->commit();
        $successMessage = "Journal entry added successfully. Journal ID: $journalID";
        addAuditLog($pdo, $user_name, $role, 'Create', 'Journal Entry', "Created new journal ID $journalID");
        addNotification($pdo, $user_id, 'Journal Created', "A new journal entry '$description' has been created with ID $journalID.", 'fa-plus');
        echo json_encode(['success' => true, 'message' => $successMessage, 'journalID' => $journalID]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMessage = $e->getMessage();
        json_error($errorMessage, 500);
    }
    exit;
}


if ($method === 'PUT') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) json_error('Invalid JSON');

    $journalID = $payload['journalID'] ?? '';
    if (!$journalID) json_error('Missing journalID');

    $date = $payload['date'] ?? date('Y-m-d H:i:s');
    $currentYear = date('Y', strtotime($date));
    $currentMonth = date('n', strtotime($date));

    $stmt = $pdo->prepare("SELECT status FROM ledger.periods WHERE year = :year AND month = :month");
    $stmt->execute([':year' => $currentYear, ':month' => $currentMonth]);
    $status = $stmt->fetchColumn() ?: 'Open';

    if ($status === 'Closed' || $status === 'Locked') {
        json_error("The accounting period ($currentYear-$currentMonth) is $status. No changes allowed.");
    }

    $stmt = $pdo->prepare("SELECT status FROM ledger.entries WHERE journalID = ?");
    $stmt->execute([$journalID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_error('Journal not found', 404);
    if ($row['status'] !== 'Draft') json_error('Only Draft journals can be edited');

    $description = $payload['description'] ?? '';
    $referenceType = $payload['referenceType'] ?? '';
    $details = $payload['details'] ?? [];

    $totalDebit = array_sum(array_column($details, 'debit'));
    $totalCredit = array_sum(array_column($details, 'credit'));
    if (abs($totalDebit - $totalCredit) > 0.001) {
        $errorMessage = "Journal is not balanced.";
        json_error($errorMessage);
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("
            UPDATE ledger.entries SET date = ?, description = ?, referenceType = ? WHERE journalID = ?
        ")->execute([$date, $description, $referenceType, $journalID]);

        $pdo->prepare("DELETE FROM ledger.details WHERE journalID = ?")->execute([$journalID]);

        $ins = $pdo->prepare("
            INSERT INTO ledger.details (journalID, accountID, debit, credit)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($details as $d) {
            $ins->execute([
                $journalID,
                $d['accountID'],
                floatval($d['debit']),
                floatval($d['credit'])
            ]);
        }

        $pdo->commit();
        $successMessage = "Journal entry updated successfully. Journal ID: $journalID";
        addAuditLog($pdo, $user_name, $role, 'Update', 'Journal Entry', "Updated journal ID $journalID to '$description'");
        addNotification($pdo, $user_id, 'Journal Updated', "Journal entry '$description' with ID $journalID has been updated.", 'fa-edit');
        echo json_encode(['success' => true, 'message' => $successMessage]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMessage = $e->getMessage();
        json_error($errorMessage, 500);
    }
    exit;
}

if ($method === 'POST' && $action === 'post') {
    $input = file_get_contents('php://input');
    parse_str($input, $postData);
    $journalID = $postData['journalID'] ?? '';
    $postBy = $user_name;
    if (!$journalID) json_error('Missing journalID');

    try {
        $dateStmt = $pdo->prepare("SELECT date FROM ledger.entries WHERE journalID = ?");
        $dateStmt->execute([$journalID]);
        $date = $dateStmt->fetchColumn();
        if (!$date) json_error('Journal not found', 404);

        $currentYear = date('Y', strtotime($date));
        $currentMonth = date('n', strtotime($date));

        $periodStmt = $pdo->prepare("SELECT status FROM ledger.periods WHERE year = :year AND month = :month");
        $periodStmt->execute([':year' => $currentYear, ':month' => $currentMonth]);
        $status = $periodStmt->fetchColumn() ?: 'Open';

        if ($status === 'Closed' || $status === 'Locked') {
            json_error("The accounting period ($currentYear-$currentMonth) is $status. No changes allowed.");
        }

        $stmt = $pdo->prepare("SELECT status, description FROM ledger.entries WHERE journalID = ?");
        $stmt->execute([$journalID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) json_error('Journal not found', 404);
        if ($row['status'] !== 'Draft') json_error('Only Draft journals can be posted');

        $sum = $pdo->prepare("
            SELECT COALESCE(SUM(debit),0) AS td, COALESCE(SUM(credit),0) AS tc
            FROM ledger.details WHERE journalID = ?
        ");
        $sum->execute([$journalID]);
        $s = $sum->fetch(PDO::FETCH_ASSOC);
        if (abs($s['td'] - $s['tc']) > 0.001) {
            $errorMessage = "Journal is not balanced.";
            json_error($errorMessage);
        }

        $upd = $pdo->prepare("
            UPDATE ledger.entries SET status = 'Posted', posted_by = ?, posted_at = NOW() WHERE journalID = ?
        ");
        $upd->execute([$postBy, $journalID]);

        $successMessage = "Journal entry posted successfully. Journal ID: $journalID";
        addAuditLog($pdo, $user_name, $role, 'Post', 'Journal Entry', "Posted journal ID $journalID ('{$row['description']}')");
        addNotification($pdo, $user_id, 'Journal Posted', "Journal entry '{$row['description']}' with ID $journalID has been posted.", 'fa-check-circle');
        echo json_encode(['success' => true, 'message' => $successMessage]);
    } catch (PDOException $e) {
        $errorMessage = $e->getMessage();
        json_error($errorMessage, 500);
    }
    exit;
}


if ($method === 'POST' && $action === 'archive') {
    $input = file_get_contents('php://input');
    if ($input) {
        parse_str($input, $postData);
        error_log("Received POST data (raw): " . $input);
        error_log("Parsed POST data: " . print_r($postData, true));
    }
    $journalID = $postData['journalID'] ?? '';
    $actionType = $postData['actionType'] ?? 'archive';
    if (!$journalID) json_error('Missing journalID');

    try {
        $dateStmt = $pdo->prepare("SELECT date FROM ledger.entries WHERE journalID = ?");
        $dateStmt->execute([$journalID]);
        $date = $dateStmt->fetchColumn();
        if (!$date) json_error('Journal not found', 404);

        $currentYear = date('Y', strtotime($date));
        $currentMonth = date('n', strtotime($date));

        $periodStmt = $pdo->prepare("SELECT status FROM ledger.periods WHERE year = :year AND month = :month");
        $periodStmt->execute([':year' => $currentYear, ':month' => $currentMonth]);
        $status = $periodStmt->fetchColumn() ?: 'Open';

        if ($status === 'Closed' || $status === 'Locked') {
            json_error("The accounting period ($currentYear-$currentMonth) is $status. No changes allowed.");
        }

        $val = ($actionType === 'unarchive') ? 'NO' : 'YES';
        $stmt = $pdo->prepare("SELECT description FROM ledger.entries WHERE journalID = ?");
        $stmt->execute([$journalID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $description = $row['description'] ?? 'Unknown';

        $stmt = $pdo->prepare("UPDATE ledger.entries SET Archive = ? WHERE journalID = ?");
        $stmt->execute([$val, $journalID]);
        $actionVerb = ($actionType === 'unarchive') ? 'Unarchived' : 'Archived';
        $successMessage = "Journal entry $actionVerb successfully. Journal ID: $journalID";
        addAuditLog($pdo, $user_name, $role, ucfirst($actionType), 'Journal Entry', "$actionVerb journal ID $journalID ('$description')");
        addNotification($pdo, $user_id, "Journal $actionVerb", "Journal entry '$description' with ID $journalID has been $actionVerb.", 'fa-archive');
        echo json_encode(['success' => true, 'message' => $successMessage, 'archived' => ($val === 'YES')]);
    } catch (PDOException $e) {
        $errorMessage = $e->getMessage();
        json_error($errorMessage, 500);
    }
    exit;
}

?>