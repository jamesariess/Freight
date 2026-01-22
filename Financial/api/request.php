<?php
define('API_KEY', 'FinancialMalakas');
define('LOG_FILE', __DIR__ . '/api_access.log');
define('RATE_LIMIT', 100);
define('RATE_WINDOW', 60);
define('ALLOWED_ORIGIN', '*');

// $dbHost = 'localhost';
// $dbName = 'financial';
// $dbUser = 'root';
// $dbPass = '';


$dbHost = 'localhost';
$dbName = 'fina_financial';
$dbUser = 'fina_finances';
$dbPass = '7rO-@mwup07Io^g0';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function logRequest($status, $message = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $time = date('Y-m-d H:i:s');
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $line = "[$time] IP:$ip STATUS:$status URI:$uri MSG:$message" . PHP_EOL;
    @file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function get_header_value($name) {
    $h = getallheaders() ?: [];
    $nameLower = strtolower($name);
    foreach ($h as $k => $v) {
        if (strtolower($k) === $nameLower) return $v;
    }
    return null;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['rate'])) {
    $_SESSION['rate'] = [];
}
if (!isset($_SESSION['rate'][$ip])) {
    $_SESSION['rate'][$ip] = ['count' => 0, 'time' => time()];
}
$elapsed = time() - $_SESSION['rate'][$ip]['time'];
if ($elapsed > RATE_WINDOW) {
    $_SESSION['rate'][$ip] = ['count' => 0, 'time' => time()];
}
$_SESSION['rate'][$ip]['count']++;
if ($_SESSION['rate'][$ip]['count'] > RATE_LIMIT) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests, slow down']);
    logRequest(429, 'Rate limit exceeded');
    exit;
}

$providedKey = get_header_value('X-API-KEY');
if (!isset($providedKey) || $providedKey !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    logRequest(401, 'Invalid API key');
    exit;
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    logRequest(500, "DB error: " . $e->getMessage());
    exit;
}
require_once __DIR__ . '/../vendor/autoload.php'; 
use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
$departmentMap = [
    'HR2' => ['Human Resources'],
    'HR3' => ['Human Resources'],
    'Logistic2' => ['Operation'],
    'Logistic1' => ['Management','Maintenance'],
    'Financial' => ['Financial'],
];

$action = $_GET['action'] ?? null;
$dept = $_GET['dept'] ?? null;

if (!$action || !$dept) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action or dept parameter']);
    exit;
}

if (!array_key_exists($dept, $departmentMap)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid department']);
    exit;
}
$departments = $departmentMap[$dept];
$placeholders = str_repeat('?,', count($departments) - 1) . '?';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_allocations') {
            $sql = "SELECT 
                        ch.accountName AS title,
                        c.allocationID,
                        d.Name AS department
                    FROM costallocation c
                    JOIN chartofaccount ch ON c.accountID = ch.accountID
                    JOIN departmentbudget d ON c.Deptbudget = d.Deptbudget
                    WHERE d.Name IN ($placeholders)
                      AND c.Status = 'Activate'
                      AND ch.Archive = 'NO'
                      AND ch.status = 'Active'
                      AND ch.accounType IN ('Liabilities','Expenses','Assets')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($departments);
            $results = $stmt->fetchAll();
            echo json_encode($results);
            exit;
        } elseif ($action === 'get_requests') {
            $sql = "SELECT
                        r.requestTitle AS title,
                        r.Amount AS ApprovedAmount,
                        r.Requested_by,
                        r.Due,
                        r.status,
                        r.Remarks,
                        r.Purpuse,
                        r.documents
                    FROM request r 
                    JOIN costallocation c ON r.allocationID = c.allocationID
                    JOIN chartofaccount ch ON c.accountID = ch.accountID
                    JOIN departmentbudget d ON c.Deptbudget = d.Deptbudget
                    WHERE d.Name IN ($placeholders)
                      AND c.Status = 'Activate'
                      AND ch.Archive = 'NO'
                      AND ch.status = 'Active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($departments);
            $results = $stmt->fetchAll();
            echo json_encode($results);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'insert_request') {
        $input = $_POST;
        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        $requiredFields = ['requestTitle', 'Amount', 'Requested_by', 'Due', 'Purpuse', 'allocationID'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
                http_response_code(400);
                echo json_encode(['error' => "Missing or empty field: $field"]);
                exit;
            }
        }

        $amount = floatval($input['Amount']);
        if (!is_numeric($amount) || $amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount must be a positive number']);
            exit;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['Due'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Due must be in YYYY-MM-DD format']);
            exit;
        }

        $validationSql = "SELECT c.allocationID
                          FROM costallocation c
                          JOIN departmentbudget d ON c.Deptbudget = d.Deptbudget
                          WHERE c.allocationID = ? AND d.Name IN ($placeholders)";
        $stmt = $pdo->prepare($validationSql);
        $stmt->execute(array_merge([$input['allocationID']], $departments));
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid allocationID for the specified department']);
            exit;
        }



$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dccvicfzv',
        'api_key'    => '868917412781798',
        'api_secret' => '3ZBsffXT_xh10IAgsjCKhqU7pyk'
    ]
]);

$documents = [];
if (!empty($_FILES['documents']['name'][0])) {
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $maxFiles = 5;

    if (count($_FILES['documents']['name']) > $maxFiles) {
        http_response_code(400);
        echo json_encode(['error' => "Maximum $maxFiles files allowed"]);
        exit;
    }

    foreach ($_FILES['documents']['name'] as $key => $name) {
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $fileType = $_FILES['documents']['type'][$key];
            $fileSize = $_FILES['documents']['size'][$key];
            $tmpName = $_FILES['documents']['tmp_name'][$key];

            if (!in_array($fileType, $allowedTypes)) {
                http_response_code(400);
                echo json_encode(['error' => "Invalid file type for $name. Allowed types: PDF, JPEG, PNG, DOC, DOCX"]);
                exit;
            }
            if ($fileSize > $maxFileSize) {
                http_response_code(400);
                echo json_encode(['error' => "File $name exceeds 5MB limit"]);
                exit;
            }

            // Upload to Cloudinary
            $year = date('Y');
            $folderPath = "financial/$year/documents";
            $publicId = "docu_" . uniqid();

            try {
                $uploadResult = $cloudinary->uploadApi()->upload($tmpName, [
                    'folder' => $folderPath,
                    'public_id' => $publicId
                ]);
                $documents[] = $uploadResult['secure_url'];
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => "Cloudinary upload failed for $name: " . $e->getMessage()]);
                exit;
            }
        }
    }
}

$documentsJson = !empty($documents) ? json_encode($documents) : null;

        $insertSql = "INSERT INTO request (requestTitle, Amount, Requested_by, Due, Purpuse, allocationID, documents)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            $input['requestTitle'],
            $amount,
            $input['Requested_by'],
            $input['Due'],
            $input['Purpuse'],
            $input['allocationID'],
            $documentsJson
        ]);

        http_response_code(201);
        echo json_encode(['message' => 'Request inserted successfully']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    logRequest(500, 'PDOException: ' . $e->getMessage());
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    logRequest(500, 'Exception: ' . $e->getMessage());
    exit;
}
?>