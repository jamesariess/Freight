<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = "";
$errorMessage   = "";

$id = $_GET['id'] ?? null;
$sql = "SELECT * FROM ledger.chartofaccount WHERE accountID = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    if (isset($_POST['archive'])) {
        $accountID = $_POST['accountID'];
        $sql = "UPDATE ledger.chartofaccount SET Archive = 'YES' WHERE accountID = :accountID";
        $stmt =$pdo->prepare($sql);
        $stmt->bindParam(':accountID', $accountID);

        try {
            $stmt->execute();
            $successMessage = "Account archived successfully.";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Chart of Account', "Archived account ID $accountID");
            addNotification($pdo, $user_id, 'Account Archived', "Account ID '$accountID' has been archived.", 'fa-archive');

        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();
        }
    } 

    if (isset($_POST['update'])) {
    $accountID = $_POST['accountID'];
    $accountName = $_POST['accountName'];
    $accounType = $_POST['accountType'];
    $accountstatus = $_POST['status'];

    if (empty($accountName) || empty($accounType)) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        
        $prefixMap = [
            'Assets'      => 'AS',
            'Liabilities' => 'LI',
            'Equity'      => 'EQ',
            'Revenue'     => 'RE',
            'Expenses'    => 'EX'
        ];

        if (!array_key_exists($accounType, $prefixMap)) {
            $errorMessage = "Invalid account type.";
        } else {
            $prefix = $prefixMap[$accounType];

           
            $sql = "SELECT accountCode FROM ledger.chartofaccount 
                    WHERE accounType = :accounType AND accountID != :accountID
                    ORDER BY accountCode DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':accounType', $accounType);
            $stmt->bindParam(':accountID', $accountID);
            $stmt->execute();
            $lastAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lastAccount) {
                $lastNumber = (int)substr($lastAccount['accountCode'], strpos($lastAccount['accountCode'], '-') + 1);
                $newNumber = str_pad($lastNumber + 1, 3, "0", STR_PAD_LEFT);
            } else {
                $newNumber = "001";
            }

            $accountCode = $prefix . "-" . $newNumber;

          
            $sql = "UPDATE ledger.chartofaccount 
                    SET accountCode = :accountCode, 
                        accountName = :accountName, 
                        accounType = :accounType, 
                        status = :status 
                    WHERE accountID = :accountID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':accountCode', $accountCode);
            $stmt->bindParam(':accountName', $accountName);
            $stmt->bindParam(':accounType', $accounType);
            $stmt->bindParam(':status', $accountstatus);
            $stmt->bindParam(':accountID', $accountID);

            try {
                $stmt->execute();
                $successMessage = "Account updated successfully. New Code: $accountCode";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Chart of Account', "Updated account ID $accountID to $accountName ($accountCode)");
                addNotification($pdo, $user_id, 'Account Updated', "Account '$accountName' has been updated with new code '$accountCode'.", 'fa-edit');
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();
            }
        }
    }
}

    if (isset($_POST['submit'])) {
        $accountName = $_POST['accountName'];
        $accounType = $_POST['accountType'];

        if (empty($accountName) || empty($accounType)) {
            $errorMessage = "Please fill in all required fields.";
        } else {
            $prefixMap = [
                'Assets'      => 'AS',
                'Liabilities' => 'LI',
                'Equity'      => 'EQ',
                'Revenue'     => 'RE',
                'Expenses'    => 'EX'
            ];

            if (!array_key_exists($accounType, $prefixMap)) {
                $errorMessage = "Invalid account type.";
            } else {
                $prefix = $prefixMap[$accounType];

                $sql = "SELECT accountCode FROM ledger.chartofaccount 
                        WHERE accounType = :accounType 
                        ORDER BY accountCode DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':accounType', $accounType);
                $stmt->execute();
                $lastAccount = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($lastAccount) {
                    $lastNumber = (int)substr($lastAccount['accountCode'], strpos($lastAccount['accountCode'], '-') + 1);
                    $newNumber  = str_pad($lastNumber + 1, 3, "0", STR_PAD_LEFT);
                } else {
                    $newNumber = "001";
                }

                $accountCode = $prefix . "-" . $newNumber;

                $sql = "INSERT INTO ledger.chartofaccount (accountCode, accountName, accounType, Archive) 
                        VALUES (:accountCode, :accountName, :accounType, 'NO')";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':accountCode', $accountCode);
                $stmt->bindParam(':accountName', $accountName);
                $stmt->bindParam(':accounType', $accounType);

 

                try {
                    $stmt->execute();
                    $successMessage = "Account added successfully. Code: $accountCode";
                addAuditLog($pdo, $user_name, $role, 'Add', 'Chart of Account', "Added new account: $accountName ($accountCode)");
                addNotification($pdo, $user_id, 'New Account Added', "Account '$accountName' has been created successfully.", 'fa-plus-circle');

                } catch (PDOException $e) {
                    $errorMessage = $e->getMessage();
                }
            }
        }
    }
}


$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["Archive = 'NO'"];
$bindings = [];

if (!empty($_GET['account_code'])) {
    $whereClauses[] = "accountCode LIKE :account_code";
    $bindings[':account_code'] = '%' . $_GET['account_code'] . '%';
}
if (!empty($_GET['account_name'])) {
    $whereClauses[] = "accountName LIKE :account_name";
    $bindings[':account_name'] = '%' . $_GET['account_name'] . '%';
}
if (!empty($_GET['account_type'])) {
    $whereClauses[] = "accounType = :account_type";
    $bindings[':account_type'] = $_GET['account_type'];
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "status = :status";
    $bindings[':status'] = $_GET['status'];
}
if (!empty($_GET['created_from'])) {
    $whereClauses[] = "created >= :created_from";
    $bindings[':created_from'] = $_GET['created_from'];
}
if (!empty($_GET['created_to'])) {
    $whereClauses[] = "created <= :created_to";
    $bindings[':created_to'] = $_GET['created_to'];
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "SELECT COUNT(*) FROM ledger.chartofaccount WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);


$sql = "SELECT * FROM ledger.chartofaccount WHERE $whereSql ORDER BY created DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);


function buildPaginatedUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
 

?>
