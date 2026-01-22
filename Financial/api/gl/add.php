<?php
include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

function getBankBalance($pdo) {
    $stmt = $pdo->query("SELECT SUM(Amount - UsedAmount - Transfer) AS balance FROM funds WHERE Archive='NO' AND fundType != 'PettyCash'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['balance'] ?? 0;
}

function getBanksWithBalance($pdo) {
    $sql = "
        SELECT 
            b.bankID, 
            b.bankName,
            SUM(f.Amount) - SUM(COALESCE(f.UsedAmount, 0)) - SUM(COALESCE(f.Transfer, 0)) AS useAmount
        FROM funds f
        JOIN bank b ON f.bankID = b.bankID
        WHERE f.Archive = 'NO'
        GROUP BY b.bankID, b.bankName
        HAVING (SUM(f.Amount) - SUM(COALESCE(f.UsedAmount, 0)) - SUM(COALESCE(f.Transfer, 0))) > 0
        ORDER BY b.bankName
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getthebank($pdo) {
    $sql = "
        SELECT 
            b.bankName,
            SUM(f.Amount) - SUM(COALESCE(f.UsedAmount, 0)) - SUM(COALESCE(f.Transfer, 0)) AS availableBalance
        FROM funds f
        JOIN bank b ON f.bankID = b.bankID
        WHERE f.Archive = 'NO'
        GROUP BY b.bankID, b.bankName
        ORDER BY b.bankName ASC
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getCashOnHand($pdo, $selectedPeriodId = null) {
    try {
        $sql = "
            SELECT 
                SUM(jd.debit) - SUM(jd.credit) AS balance
            FROM details jd
            JOIN chartofaccount c ON jd.accountID = c.accountID
            JOIN entries e ON jd.journalID = e.journalID
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

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch banks
    $sql = "SELECT bankID, bankName, accountNo, accountName, status FROM bank WHERE Archive='NO' ORDER BY bankName ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch bank balances
    $bankBalances = getthebank($pdo);

    // Fetch banks for withdraw
    $banksForWithdraw = getBanksWithBalance($pdo);

    // Fetch fund details
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
        FROM funds f
        LEFT JOIN bank b ON f.bankID = b.bankID
        WHERE f.Archive = 'NO'
        ORDER BY f.Date DESC, b.bankName ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $fundDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch cash on hand
    $cashOnHand = getCashOnHand($pdo);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => [
            'banks' => $banks,
            'bankBalances' => $bankBalances,
            'banksForWithdraw' => $banksForWithdraw,
            'fundDetails' => $fundDetails,
            'cashOnHand' => $cashOnHand
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>