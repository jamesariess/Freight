<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');
$successMessage = '';
$errorMessage = '';

function getBankBalance($pdo) {

    $stmt = $pdo->query("SELECT SUM(Amount - UsedAmount) - SUM(COALESCE(Transfer, 0)) AS balance FROM ledger.funds WHERE Archive='NO'");

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row['balance'] ?? 0; 
}

function getOwnerDeposit($pdo) {
    $sql = "
        SELECT 
            COALESCE(SUM(jd.credit), 0) - COALESCE(SUM(jd.debit), 0) AS balance
        FROM ledger.details jd
        JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
        JOIN ledger.entries e ON jd.journalID = e.journalID
        WHERE c.accountName = 'Owner Capital' 
    ";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['balance'] ?? 0;
}

function getCashOnHand($pdo, $selectedPeriodId = null) {
    try {      
        $sql = "
            SELECT 
                SUM(jd.debit) - SUM(jd.credit) AS balance
            FROM ledger.details jd
            JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
            JOIN ledger.entries e ON jd.journalID = e.journalID
            WHERE c.accountName = 'Cash On Hand'
        ";
        if ($selectedPeriodId) {
            $sql .= " AND e.periodID = :period_id";
        }
        $stmt = $pdo->prepare($sql);
        if ($selectedPeriodId) {
            $stmt->bindValue(':period_id', $selectedPeriodId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC); 
        return $result && $result['balance'] !== null ? $result['balance'] : 0;
    } catch (Exception $e) {
        error_log("Error fetching Cash On Hand: " . $e->getMessage());
        return 0;
    }
}

function getOrCreateAccount($pdo, $accountName, $accounType) {
    $checkSql = "SELECT accountID FROM ledger.chartofaccount 
                 WHERE accountName = :name AND Archive = 'NO' LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':name' => $accountName]);
    $account = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($account) return $account['accountID'];

    $lastCodeSql = "SELECT accountCode FROM ledger.chartofaccount 
                    WHERE accounType = :type ORDER BY accountCode DESC LIMIT 1";
    $lastCodeStmt = $pdo->prepare($lastCodeSql);
    $lastCodeStmt->execute([':type' => $accounType]);
    $lastCode = $lastCodeStmt->fetch(PDO::FETCH_ASSOC);

    $newCodeNumber = $lastCode ? (int)substr($lastCode['accountCode'], 3) + 1 : 1;
    $prefix = strtoupper(substr($accounType, 0, 2));
    $newAccountCode = $prefix . '-' . str_pad($newCodeNumber, 3, '0', STR_PAD_LEFT);

    $insertSql = "INSERT INTO ledger.chartofaccount (accountCode, accountName, accounType, Archive, status) 
                  VALUES (:code, :name, :type, 'NO', 'Active')";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':code' => $newAccountCode,
        ':name' => $accountName,
        ':type' => $accounType
    ]);
    return $pdo->lastInsertId();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT bankID, bankName, accountNo, accountName, status FROM ledger.bank WHERE Archive='NO' ORDER BY bankName ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cashOnHand = getCashOnHand($pdo);
} catch (PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
    $banks = [];
    $cashOnHand = 0;
}

if (isset($_POST['saveDeposit'])) {
    $depositType = $_POST['depositType'];
    $bankID = $_POST['bankID'] ?? null;
    $newBankName = trim($_POST['newBankName'] ?? '');
    $accountNo = trim($_POST['accountNo'] ?? '');
    $accountName = trim($_POST['accountname'] ?? '');
    $amount = floatval($_POST['amount']);
    $reference = $_POST['reference'] ?: "N/A";
    $date = date("Y-m-d H:i:s");

   
    $bankName = 'N/A';
    if ($bankID) {
        $stmtBank = $pdo->prepare("SELECT bankName FROM ledger.bank WHERE bankID = :bankID AND Archive = 'NO'");
        $stmtBank->execute([':bankID' => $bankID]);
        $bankData = $stmtBank->fetch(PDO::FETCH_ASSOC);
        $bankName = $bankData ? $bankData['bankName'] : 'N/A';
    } elseif ($newBankName !== "") {
        $bankName = ucwords(strtolower($newBankName));
    }

   
    $cashOnHand = getCashOnHand($pdo);

    if ($amount <= 0) {
        $errorMessage = "Invalid deposit amount.";
        error_log("Deposit error: Invalid amount ($amount)");
    } elseif ($newBankName !== "" && ($depositType === "Bank" || $depositType === "Owner")) {
        $normalizedBank = strtoupper($newBankName);
        $check = $pdo->prepare("SELECT bankID FROM ledger.bank WHERE UPPER(bankName) = :bankName AND Archive = 'NO'");
        $check->execute([':bankName' => $normalizedBank]);
        $existingBank = $check->fetch(PDO::FETCH_ASSOC);

        if ($existingBank) {
            $errorMessage = "Bank name already exists!";
            $bankID = $existingBank['bankID'];
            error_log("Deposit error: Bank name '$newBankName' already exists (bankID: $bankID)");
        } else {
            $cleanBank = ucwords(strtolower($newBankName));
            $stmt = $pdo->prepare("
                INSERT INTO ledger.bank (bankName, accountNo, accountName, status, Archive) 
                VALUES (:bankName, :accountNo, :accountName, 'Active', 'NO')
            ");
            $stmt->execute([
                ':bankName' => $cleanBank,
                ':accountNo' => $accountNo,
                ':accountName' => $accountName
            ]);
            $bankID = $pdo->lastInsertId();
            $bankName = $cleanBank;

          
            $auditDescription = "Created new bank #$bankID (Name: '$bankName', Account: $accountNo).";
            addAuditLog($pdo, $user_name, $role, 'Create', 'Bank', $auditDescription);
            $notifMessage = "New bank '$bankName' (ID: #$bankID) created.";
            addNotification($pdo, $user_id, 'Bank Created', $notifMessage, 'fa-bank');
        }
    }

    $entrySql = "INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive)
                 VALUES (:date, :description, :ref, :createdBy, 'NO')";
    $entryStmt = $pdo->prepare($entrySql);
    $entryStmt->execute([
        ':date' => $date,
        ':description' => "Deposit ($depositType)",
        ':ref' => $depositType,
        ':createdBy' => 'System'
    ]);
    $journalID = $pdo->lastInsertId();

    $detailSql = "INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                  VALUES (:journalID, :accountID, :debit, :credit, 'NO')";
    $detailStmt = $pdo->prepare($detailSql);

    if ($depositType === "Bank" && $bankID) {
        if ($cashOnHand <= 0) {
            $errorMessage = "No cash on hand available for deposit.";
            error_log("Deposit error: No cash on hand for bankID #$bankID");
        } elseif ($amount > $cashOnHand) {
            $errorMessage = "Deposit amount exceeds available cash on hand (₱" . number_format($cashOnHand, 2) . ").";
            error_log("Deposit error: Amount ($amount) exceeds cash on hand ($cashOnHand) for bankID #$bankID");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO ledger.funds (bankID, Amount, UsedAmount, reference, Notes, Archive, Date, fundType) 
                VALUES (:bankID, :amount, 0, :reference, :notes, 'NO', :date, 'Bank')
            ");
            $stmt->execute([
                ':bankID' => $bankID,
                ':amount' => $amount,
                ':reference' => $reference,
                ':notes' => $depositType,
                ':date' => $date
            ]);
            $fundsID = $pdo->lastInsertId();

            $bankAccountID = getOrCreateAccount($pdo, "Cash On Bank", "Assets");
            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $bankAccountID,
                ':debit' => $amount,
                ':credit' => 0
            ]);

            $cashOnHandID = getOrCreateAccount($pdo, "Cash On Hand", "Assets");
            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $cashOnHandID,
                ':debit' => 0,
                ':credit' => $amount
            ]);
            $successMessage = "Bank deposit recorded successfully!";

            $auditDescription = "Deposited ₱" . number_format($amount, 2) . " to bank #$bankID (Name: '$bankName', Type: $depositType).";
            addAuditLog($pdo, $user_name, $role, 'Deposit', 'Funds', $auditDescription);
            $notifMessage = "Deposit of ₱" . number_format($amount, 2) . " to bank '$bankName' (ID: #$bankID).";
            addNotification($pdo, $user_id, 'Bank Deposit', $notifMessage, 'fa-money-check');
        }
    } elseif ($depositType === "Owner" && $bankID) {
        $stmt = $pdo->prepare("
            INSERT INTO ledger.funds (bankID, Amount, UsedAmount, reference, Notes, Archive, Date, fundType) 
            VALUES (:bankID, :amount, 0, :reference, :notes, 'NO', :date, 'Owner')
        ");
        $stmt->execute([
            ':bankID' => $bankID,
            ':amount' => $amount,
            ':reference' => "$reference",
            ':notes' => $depositType,
            ':date' => $date
        ]);
        $fundsID = $pdo->lastInsertId();

        $bankAccountID = getOrCreateAccount($pdo, "Cash On Bank", "Assets");
        $detailStmt->execute([
            ':journalID' => $journalID,
            ':accountID' => $bankAccountID,
            ':debit' => $amount,
            ':credit' => 0
        ]);

        $equityAccountID = getOrCreateAccount($pdo, "Owner Capital", "Equity");
        $detailStmt->execute([
            ':journalID' => $journalID,
            ':accountID' => $equityAccountID,
            ':debit' => 0,
            ':credit' => $amount
        ]);
        $successMessage = "Owner deposit recorded successfully!";

        // Add audit log and notification
        $auditDescription = "Deposited ₱" . number_format($amount, 2) . " to bank #$bankID (Name: '$bankName', Type: $depositType).";
        addAuditLog($pdo, $user_name, $role, 'Deposit', 'Funds', $auditDescription);
        $notifMessage = "Owner deposit of ₱" . number_format($amount, 2) . " to bank '$bankName' (ID: #$bankID).";
        addNotification($pdo, $user_id, 'Owner Deposit', $notifMessage, 'fa-money-check');
    }
}

function getBanksWithBalance($pdo) {
    $sql = "
        SELECT 
            b.bankID, 
            b.bankName,
            SUM(f.Amount) - SUM(COALESCE(f.UsedAmount, 0)) - SUM(COALESCE(f.Transfer, 0)) AS useAmount
        FROM ledger.funds f
        JOIN ledger.bank b ON f.bankID = b.bankID
        WHERE f.Archive = 'NO'
        GROUP BY b.bankID, b.bankName
        HAVING (SUM(f.Amount) - SUM(COALESCE(f.UsedAmount, 0)) - SUM(COALESCE(f.Transfer, 0))) > 0
        ORDER BY b.bankName
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$banksForWithdraw = getBanksWithBalance($pdo);

function getthebank($pdo) {
    $sql = "
        SELECT 
            b.bankName,
            SUM(COALESCE(f.Amount, 0)) 
            - SUM(COALESCE(f.UsedAmount, 0)) 
            - SUM(COALESCE(f.Transfer, 0)) AS availableBalance
        FROM ledger.funds f
        JOIN ledger.bank b ON f.bankID = b.bankID
        WHERE f.Archive = 'NO'
        GROUP BY f.bankID, b.bankName
        ORDER BY b.bankName
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST['saveWithdraw'])) {
    $bankID = $_POST['fundID'] ?? null; 
    $amount = floatval($_POST['amount'] ?? 0);
    $date = date("Y-m-d H:i:s");


    $stmtBank = $pdo->prepare("SELECT bankName FROM ledger.bank WHERE bankID = :bankID AND Archive = 'NO'");
    $stmtBank->execute([':bankID' => $bankID]);
    $bankData = $stmtBank->fetch(PDO::FETCH_ASSOC);
    $bankName = $bankData ? $bankData['bankName'] : 'N/A';

    if (!$bankID || $amount <= 0) {
        $errorMessage = "Invalid bank selection or withdrawal amount.";
        error_log("Withdrawal error: Invalid bankID ($bankID) or amount ($amount)");
    } else {
        $stmt = $pdo->prepare("
            SELECT fundsID, Amount, COALESCE(UsedAmount, 0) AS UsedAmount
            FROM ledger.funds
            WHERE bankID = ? AND Archive = 'NO'
            ORDER BY Date ASC, fundsID ASC
        ");
        $stmt->execute([$bankID]);
        $fundRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalAvailable = 0;
        foreach ($fundRows as $f) {
            $totalAvailable += ($f['Amount'] - $f['UsedAmount']);
        }

        if ($amount > $totalAvailable) {
            $errorMessage = "Withdrawal amount exceeds available balance (₱" . number_format($totalAvailable, 2) . ").";
            error_log("Withdrawal error: Amount ($amount) exceeds available balance ($totalAvailable) for bankID #$bankID");
        } else {
            $remaining = $amount;

            foreach ($fundRows as $row) {
                $available = $row['Amount'] - $row['UsedAmount'];
                if ($available <= 0) continue;

                $deduct = min($available, $remaining);
                $newUsed = $row['UsedAmount'] + $deduct;

                $updateStmt = $pdo->prepare("UPDATE ledger.funds SET UsedAmount = ? WHERE fundsID = ?");
                $updateStmt->execute([$newUsed, $row['fundsID']]);

                $remaining -= $deduct;
                if ($remaining <= 0) break;
            }

            $entrySql = "INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive)
                         VALUES (:date, :description, :ref, :createdBy, 'NO')";
            $entryStmt = $pdo->prepare($entrySql);
            $entryStmt->execute([
                ':date' => $date,
                ':description' => "Withdrawal from Bank",
                ':ref' => 'Withdrawal',
                ':createdBy' => 'System'
            ]);
            $journalID = $pdo->lastInsertId();

            $detailSql = "INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                          VALUES (:journalID, :accountID, :debit, :credit, 'NO')";
            $detailStmt = $pdo->prepare($detailSql);

            $bankAccountID = getOrCreateAccount($pdo, "Cash On Bank", "Assets");
            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $bankAccountID,
                ':debit' => 0,
                ':credit' => $amount
            ]);

            $ownerEquityID = getOrCreateAccount($pdo, "Owner Capital", "Equity");
            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $ownerEquityID,
                ':debit' => $amount,
                ':credit' => 0
            ]);

            $successMessage = "Withdrawal of ₱" . number_format($amount, 2) . " completed successfully.";

         
            $auditDescription = "Withdrew ₱" . number_format($amount, 2) . " from bank #$bankID (Name: '$bankName').";
            addAuditLog($pdo, $user_name, $role, 'Withdrawal', 'Funds', $auditDescription);
            $notifMessage = "Withdrawal of ₱" . number_format($amount, 2) . " from bank '$bankName' (ID: #$bankID).";
            addNotification($pdo, $user_id, 'Bank Withdrawal', $notifMessage, 'fa-money-check');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['editBank'])) {
            $id = $_POST['bankId'] ?? null;
            $status = $_POST['status'] ?? null;

         
            $stmtBank = $pdo->prepare("SELECT bankName FROM ledger.bank WHERE bankID = :bankID AND Archive = 'NO'");
            $stmtBank->execute([':bankID' => $id]);
            $bankData = $stmtBank->fetch(PDO::FETCH_ASSOC);
            $bankName = $bankData ? $bankData['bankName'] : 'N/A';

            if ($id && $status) {
                $stmt = $pdo->prepare("UPDATE ledger.bank SET status = :status WHERE bankID = :id");
                $stmt->execute([
                    ':status' => $status,
                    ':id' => $id
                ]);
                $successMessage = "Bank status updated successfully!";

 
                $auditDescription = "Updated bank #$id (Name: '$bankName') status to '$status'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Bank', $auditDescription);
                $notifMessage = "Bank '$bankName' (ID: #$id) status updated to '$status'.";
                addNotification($pdo, $user_id, 'Bank Status Updated', $notifMessage, 'fa-edit');
            } else {
                $errorMessage = "Missing bankId or status.";
                error_log("Edit bank error: Missing bankId ($id) or status ($status)");
            }
        }

        if (isset($_POST['archiveBank'])) {
            $id = $_POST['bankId'] ?? null;

         
            $stmtBank = $pdo->prepare("SELECT bankName FROM ledger.bank WHERE bankID = :bankID AND Archive = 'NO'");
            $stmtBank->execute([':bankID' => $id]);
            $bankData = $stmtBank->fetch(PDO::FETCH_ASSOC);
            $bankName = $bankData ? $bankData['bankName'] : 'N/A';

            if ($id) {
                $stmt = $pdo->prepare("UPDATE ledger.bank SET archive = 'YES' WHERE bankID = :id");
                $stmt->execute([':id' => $id]);
                $successMessage = "Bank archived successfully!";

            
                $auditDescription = "Archived bank #$id (Name: '$bankName').";
                addAuditLog($pdo, $user_name, $role, 'Archive', 'Bank', $auditDescription);
                $notifMessage = "Bank '$bankName' (ID: #$id) archived.";
                addNotification($pdo, $user_id, 'Bank Archived', $notifMessage, 'fa-archive');
            } else {
                $errorMessage = "Missing bankId.";
                error_log("Archive bank error: Missing bankId ($id)");
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
        error_log("Bank operation error: " . $e->getMessage());
    }
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "
        SELECT 
            f.fundsID, 
            f.Amount, 
            f.UsedAmount, 
            f.Date, 
            f.reference, 
            f.Notes, 
            f.Archive,
            b.bankName,
            f.Transfer
        FROM ledger.funds f
        LEFT JOIN ledger.bank b ON f.bankID = b.bankID
        WHERE f.Archive = 'NO'
        ORDER BY f.Date DESC, b.bankName ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $fundDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
} catch (PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
    $fundDetails = [];
    error_log("PDO Exception: " . $e->getMessage());
}
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT bankID, bankName, accountNo, accountName, status FROM ledger.bank WHERE Archive='NO' ORDER BY bankName ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cashOnHand = getCashOnHand($pdo);


    $bankBalances = getthebank($pdo);

} catch (PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
    $banks = [];
    $cashOnHand = 0;
    $bankBalances = []; 
}
?>