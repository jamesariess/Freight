<?php


include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../vendor/autoload.php';
    use Cloudinary\Cloudinary;

    $cloudinary = new Cloudinary([
        'cloud' => [
            'cloud_name' => 'dccvicfzv',
            'api_key' => '868917412781798',
            'api_secret' => '3ZBsffXT_xh10IAgsjCKhqU7pyk'
        ]
    ]);
function getOutstandingForLoan($pdo, $loanId) {
    try {
        $sql = "
            SELECT LoanAmount, paidAmount
            FROM ar_ap.loan
            WHERE LoanID = :loanId
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':loanId', $loanId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $principal = $row['LoanAmount'];
            $paid = $row['paidAmount'] ?? 0;
            return $principal - $paid;
        }
        return 0;
    } catch (Exception $e) {
        error_log("getOutstandingForLoan error: " . $e->getMessage());
        return 0;
    }
}


function fetchRequests($pdo) {
    try {
        $sql = "SELECT
                  r.requestID, r.requestTitle, r.ApprovedAmount, r.Requested_by, r.Due, r.status, r.date,
                  ch.accountName, c.allocationID, c.accountID, JSON_UNQUOTE(JSON_EXTRACT(r.documents, '$')) as documents, r.Purpuse,
                  d.Name, r.receipt
                FROM disbursment.request r
                JOIN budget.costallocation c ON r.allocationID = c.allocationID
                JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID 
                JOIN budget.departmentbudget d ON c.Deptbudget = d.Deptbudget
                WHERE r.status IN ('Approved') AND r.Archive = 'NO'
                ORDER BY r.date DESC 
                LIMIT 12";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("fetchRequests error: " . $e->getMessage());
        return [];
    }
}

$requests = fetchRequests($pdo);
foreach ($requests as &$req) {
    $req['documents'] = json_decode($req['documents'], true) ?? [];
}

if (isset($_GET['fetch']) && $_GET['fetch'] === 'requests') {
    header('Content-Type: application/json');
    echo json_encode($requests);
    ob_end_flush();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $input = json_decode($_POST['data'] ?? '{}', true);
    $requestID = intval($input["requestID"] ?? 0);
    $paymentMethod = $input["paymentMethod"] ?? null;
    $bankID = $input["bankName"] ?? null; 

    try {
        if (!$requestID || !$paymentMethod) {
            throw new Exception("Invalid request data: requestID or paymentMethod missing");
        }

        $pdo->beginTransaction();

        $sql = "
            SELECT r.ApprovedAmount, r.allocationID, r.LoanID, ca.accountID, r.requestTitle, r.Purpuse
            FROM disbursment.request r
            JOIN budget.costallocation c ON r.allocationID = c.allocationID
            JOIN ledger.chartofaccount ca ON c.accountID = ca.accountID
            WHERE r.requestID = :id AND r.status = 'Approved' AND r.Archive = 'NO'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":id" => $requestID]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "error" => "Request not found or not approved"]);
            ob_end_flush();
            exit;
        }

        $approvedAmount = $request['ApprovedAmount'];
        $allocationID = $request['allocationID'];
        $loanId = $request['LoanID'];
        $allocationAccountID = $request['accountID'];
        $requestTitle = $request['requestTitle'];
        $purpose = $request['Purpuse'];

        $stmtAccount = $pdo->prepare("SELECT accountName FROM ledger.chartofaccount WHERE accountID = :accountID");
        $stmtAccount->execute([':accountID' => $allocationAccountID]);
        $accountName = $stmtAccount->fetchColumn() ?: 'Unknown Account';

    
        $stmtCashOnHand = $pdo->query("
            SELECT IFNULL(SUM(debit) - SUM(credit), 0) AS cashOnHand
            FROM ledger.details d
            JOIN ledger.chartofaccount c ON d.accountID = c.accountID
            WHERE c.accountName = 'Cash on Hand'
        ");
        $cashOnHand = $stmtCashOnHand->fetch(PDO::FETCH_ASSOC)['cashOnHand'] ?? 0;

        $stmtBankBalance = $pdo->query("
            SELECT IFNULL(SUM(Amount - COALESCE(UsedAmount, 0) - COALESCE(Transfer, 0)), 0) AS bankBalance
            FROM ledger.funds f
            WHERE f.Archive = 'NO'
        ");
        $bankBalance = $stmtBankBalance->fetch(PDO::FETCH_ASSOC)['bankBalance'] ?? 0;

        if ($paymentMethod === 'PettyCash' && $cashOnHand <= 0) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "error" => "No available balance in Cash on Hand for Petty Cash payment."]);
            ob_end_flush();
            exit;
        }

        if ($paymentMethod === 'bank' && $bankBalance <= 0) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "error" => "No available balance in bank accounts for Bank Transfer."]);
            ob_end_flush();
            exit;
        }

       
        if ($paymentMethod === 'PettyCash' && $cashOnHand < $approvedAmount) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "error" => "Insufficient balance in Cash on Hand for this payment."]);
            ob_end_flush();
            exit;
        }

        $remaining = $approvedAmount;
        $bankUsed = 0;
        $cashUsed = 0;
        $receiptPath = null;
        $bankName = '';

        if ($paymentMethod === 'bank' && isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
            $fileExt = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedTypes)) {
                $pdo->rollBack();
                echo json_encode(["success" => false, "error" => "Invalid file type. Only PDF, JPG, JPEG, and PNG are allowed."]);
                ob_end_flush();
                exit;
            }

            $maxFileSize = 5 * 1024 * 1024; 
            if ($_FILES['receipt']['size'] > $maxFileSize) {
                $pdo->rollBack();
                echo json_encode(["success" => false, "error" => "File size exceeds 5MB limit."]);
                ob_end_flush();
                exit;
            }

        $year = date('Y');
        $folderPath = "financial/$year/Payment_transaction";
    try {
        $result = $cloudinary->uploadApi()->upload($_FILES['receipt']['tmp_name'], [
            'folder' => $folderPath,
            'public_id' => "receipt_" . $requestID,
            'resource_type' => 'auto' 
        ]);

      
        $receiptPath = $result['secure_url'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Cloudinary upload error: " . $e->getMessage());
        echo json_encode(["success" => false, "error" => "Failed to upload receipt to Cloudinary: " . $e->getMessage()]);
        ob_end_flush();
        exit;
    }

        }

        if ($paymentMethod === 'bank' && $bankID) {
           
            $sqlBankCheck = "SELECT bankID, bankName FROM ledger.bank WHERE bankID = :bankID AND Archive = 'NO' AND status = 'Active'";
            $stmtBankCheck = $pdo->prepare($sqlBankCheck);
            $stmtBankCheck->execute([':bankID' => $bankID]);
            $bank = $stmtBankCheck->fetch(PDO::FETCH_ASSOC);
            if (!$bank) {
                $pdo->rollBack();
                echo json_encode(["success" => false, "error" => "Selected bank is not active or does not exist"]);
                ob_end_flush();
                exit;
            }
            $bankName = $bank['bankName'];

            
            $sqlFund = "SELECT fundsID, Amount, COALESCE(UsedAmount, 0) as UsedAmount, COALESCE(Transfer, 0) as Transfer 
                        FROM ledger.funds 
                        WHERE Archive = 'NO' AND bankID = :bankID";
            $stmtFund = $pdo->prepare($sqlFund);
            $stmtFund->execute([':bankID' => $bankID]);
            $fund = $stmtFund->fetch(PDO::FETCH_ASSOC);

            if ($fund) {
                $available = $fund['Amount'] - $fund['UsedAmount'] - $fund['Transfer'];
                if ($available >= $remaining) {
                    $updateFund = $pdo->prepare("UPDATE ledger.funds SET Transfer = COALESCE(Transfer, 0) + :use WHERE fundsID = :id");
                    $updateFund->execute([':use' => $remaining, ':id' => $fund['fundsID']]);
                    $bankUsed = $remaining;
                    $remaining = 0;
                } else {
                    $pdo->rollBack();
                    echo json_encode(["success" => false, "error" => "Insufficient funds in selected bank account"]);
                    ob_end_flush();
                    exit;
                }
            } else {
                $pdo->rollBack();
                echo json_encode(["success" => false, "error" => "Selected bank account not found in funds"]);
                ob_end_flush();
                exit;
            }
        } else if ($paymentMethod === 'PettyCash') {
           
            $cashUsed = $remaining;
            $remaining = 0;
        }


        $updateRequest = $pdo->prepare("UPDATE disbursment.request SET status = 'Paid', receipt = :receipt WHERE requestID = :id");
        $updateRequest->execute([
            ":id" => $requestID,
            ":receipt" => $receiptPath
        ]);

        $updateAllocation = $pdo->prepare("UPDATE budget.costallocation SET usedAllocation = COALESCE(usedAllocation, 0) + :amount WHERE allocationID = :allocationID");
        $updateAllocation->execute([
            ":amount" => $approvedAmount,
            ":allocationID" => $allocationID
        ]);

        if ($loanId) {
            $interestAccountSql = "SELECT accountID FROM ledger.chartofaccount WHERE accountName = 'Interest'";
            $interestAccountStmt = $pdo->prepare($interestAccountSql);
            $interestAccountStmt->execute();
            $interestAccount = $interestAccountStmt->fetch(PDO::FETCH_ASSOC);

            if (!$interestAccount) {
                $lastExpenseCodeSql = "SELECT accountCode FROM ledger.chartofaccount WHERE accountType = 'Expenses' ORDER BY accountCode DESC LIMIT 1";
                $lastExpenseCodeStmt = $pdo->prepare($lastExpenseCodeSql);
                $lastExpenseCodeStmt->execute();
                $lastCode = $lastExpenseCodeStmt->fetch(PDO::FETCH_ASSOC);

                $newCodeNumber = 1;
                if ($lastCode) {
                    $lastCodeNumber = (int)substr($lastCode['accountCode'], 3);
                    $newCodeNumber = $lastCodeNumber + 1;
                }
                $newAccountCode = 'EX-' . str_pad($newCodeNumber, 3, '0', STR_PAD_LEFT);
                
                $insertSql = "INSERT INTO ledger.chartofaccount (accountCode, accountName, accountType, Archive, status) VALUES (:code, 'Interest', 'Expenses', 'NO', 'Active')";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([':code' => $newAccountCode]);
                $interestAccountID = $pdo->lastInsertId();
            } else {
                $interestAccountID = $interestAccount['accountID'];
            }

            $loanSql = "SELECT LoanAmount, interestRate, paidAmount FROM ar_ap.loan WHERE LoanID = :loanId";
            $loanStmt = $pdo->prepare($loanSql);
            $loanStmt->execute([':loanId' => $loanId]);
            $loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);

            if (!$loanData) {
                throw new Exception("Loan data not found for LoanID: " . $loanId);
            }
            
            $currentPrincipalBalance = $loanData['LoanAmount'] - ($loanData['paidAmount'] ?? 0);
            $interestRateDecimal = $loanData['interestRate'] / 100;
            $interestPaid = $currentPrincipalBalance * ($interestRateDecimal / 12); 
            $principalPaid = $approvedAmount - $interestPaid;
            if ($principalPaid > $currentPrincipalBalance) {
                $principalPaid = $currentPrincipalBalance;
                $interestPaid = $approvedAmount - $principalPaid;
            }

            $updateSql = "UPDATE ar_ap.loan SET paidAmount = COALESCE(paidAmount, 0) + :principalPaid WHERE LoanID = :loanId";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([':principalPaid' => $principalPaid, ':loanId' => $loanId]);

            $outstanding = getOutstandingForLoan($pdo, $loanId);
            $newStatus = ($outstanding <= 0) ? 'Paid' : 'Partially Paid';
            $statusSql = "UPDATE ar_ap.loan SET Status = :status WHERE LoanID = :loanId";
            $statusStmt = $pdo->prepare($statusSql);
            $statusStmt->execute([':status' => $newStatus, ':loanId' => $loanId]);

            $entrySql = "
                INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive)
                VALUES (CURDATE(), :description, :ref, :createdBy, 'NO')
            ";
            $entryStmt = $pdo->prepare($entrySql);
            $entryStmt->execute([
                ':description' => "Loan Payment Request #{$requestID}",
                ':ref' => 'Loan Payment',
                ':createdBy' => 'System'
            ]);
            $journalID = $pdo->lastInsertId();

            $detailSql = "
                INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $detailStmt = $pdo->prepare($detailSql);

            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $interestAccountID,
                ':debit' => $interestPaid,
                ':credit' => 0
            ]);

            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $allocationAccountID,
                ':debit' => $principalPaid,
                ':credit' => 0
            ]);

            $cashStmt = $pdo->query("SELECT accountID FROM ledger.chartofaccount WHERE accountName = 'Cash On Hand' LIMIT 1");
            $cashAccountID = $cashStmt->fetch(PDO::FETCH_ASSOC)['accountID'] ?? null;

            $bankStmt = $pdo->query("SELECT accountID FROM ledger.chartofaccount WHERE accountName = 'Cash On Bank' LIMIT 1");
            $bankAccountID = $bankStmt->fetch(PDO::FETCH_ASSOC)['accountID'] ?? null;

            if ($cashUsed > 0 && !$cashAccountID) {
                throw new Exception("Cash On Hand account not found.");
            }
            if ($bankUsed > 0 && !$bankAccountID) {
                throw new Exception("Cash On Bank account not found.");
            }

            if ($bankUsed > 0 && $bankAccountID) {
                $detailStmt->execute([
                    ':journalID' => $journalID,
                    ':accountID' => $bankAccountID,
                    ':debit' => 0,
                    ':credit' => $bankUsed
                ]);
            }

            if ($cashUsed > 0 && $cashAccountID) {
                $detailStmt->execute([
                    ':journalID' => $journalID,
                    ':accountID' => $cashAccountID,
                    ':debit' => 0,
                    ':credit' => $cashUsed
                ]);
            }
        } else {
            $entrySql = "
                INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive)
                VALUES (CURDATE(), :description, :ref, :createdBy, 'NO')
            ";
            $entryStmt = $pdo->prepare($entrySql);
            $entryStmt->execute([
                ':description' => "Expense Request #{$requestID}",
                ':ref' => 'General Expense',
                ':createdBy' => 'System'
            ]);
            $journalID = $pdo->lastInsertId();

            $detailSql = "
                INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $detailStmt = $pdo->prepare($detailSql);

            $detailStmt->execute([
                ':journalID' => $journalID,
                ':accountID' => $allocationAccountID,
                ':debit' => $approvedAmount,
                ':credit' => 0
            ]);

            $cashStmt = $pdo->query("SELECT accountID FROM ledger.chartofaccount WHERE accountName = 'Cash On Hand' LIMIT 1");
            $cashAccountID = $cashStmt->fetch(PDO::FETCH_ASSOC)['accountID'] ?? null;

            $bankStmt = $pdo->query("SELECT accountID FROM ledger.chartofaccount WHERE accountName = 'Cash On Bank' LIMIT 1");
            $bankAccountID = $bankStmt->fetch(PDO::FETCH_ASSOC)['accountID'] ?? null;

            if ($cashUsed > 0 && !$cashAccountID) {
                throw new Exception("Cash On Hand account not found.");
            }
            if ($bankUsed > 0 && !$bankAccountID) {
                throw new Exception("Cash On Bank account not found.");
            }

            if ($bankUsed > 0 && $bankAccountID) {
                $detailStmt->execute([
                    ':journalID' => $journalID,
                    ':accountID' => $bankAccountID,
                    ':debit' => 0,
                    ':credit' => $bankUsed
                ]);
            }

            if ($cashUsed > 0 && $cashAccountID) {
                $detailStmt->execute([
                    ':journalID' => $journalID,
                    ':accountID' => $cashAccountID,
                    ':debit' => 0,
                    ':credit' => $cashUsed
                ]);
            }


            if (preg_match('/Payment for (.+?) Invoice (.+)/', $requestTitle, $matches)) {
                $vendor = $matches[1];
                $ref = $matches[2];

                $billStmt = $pdo->prepare("
                    SELECT a.bill_id, a.amount
                    FROM ar_ap.ap_bills a 
                    JOIN ar_ap.vendor v ON a.vendor_id = v.vendor_id 
                    WHERE a.reference_no = :ref AND v.vendor_name = :vendor
                ");
                $billStmt->execute([':ref' => $ref, ':vendor' => $vendor]);
                $bill = $billStmt->fetch(PDO::FETCH_ASSOC);

                if ($bill) {
                    $billId = $bill['bill_id'];
                    $billAmount = $bill['amount'];
                    $newBillStatus = ($approvedAmount >= $billAmount) ? 'Paid' : 'Partially Paid';

                    $updateBill = $pdo->prepare("UPDATE ar_ap.ap_bills SET status = :status WHERE bill_id = :id");
                    $updateBill->execute([':status' => $newBillStatus, ':id' => $billId]);

  
                    $auditDescription = "Updated bill #$billId status to '$newBillStatus' after payment release.";
                    addAuditLog($pdo, $user_name, $role, 'Update', 'Bill', $auditDescription);
                    $notifMessage = "Bill #$billId updated to '$newBillStatus'.";
                    addNotification($pdo, $user_id, 'Bill Updated', $notifMessage, 'fa-edit');
                }
            }
        }


        $methodMap = ['bank' => 'Bank Transfer', 'PettyCash' => 'Cash'];
        $paymentMethodMapped = $methodMap[$paymentMethod] ?? $paymentMethod;

        $insertPaymentSql = "
            INSERT INTO ar_ap.ap_payments (LoanID, payment_date, requestID, amount, method, remarks, created_at)
            VALUES (:loanID, CURDATE(), :requestID, :amount, :method, :remarks, NOW())
        ";
        $insertPaymentStmt = $pdo->prepare($insertPaymentSql);
        $insertPaymentStmt->execute([
            ':loanID' => $loanId ?: null,
            ':requestID' => $requestID,
            ':amount' => $approvedAmount,
            ':method' => $paymentMethodMapped,
            ':remarks' => $purpose ?? ''
        ]);
        
        $pdo->commit();

        $paymentDetails = ($paymentMethod === 'bank' && $bankName) ? " from bank $bankName" : ($paymentMethod === 'PettyCash' ? ' via Petty Cash' : '');
        if ($loanId) {
            $auditDescription = "Released loan payment for request #$requestID to account '$accountName' amounting to ₱" . number_format($approvedAmount, 2) . " (Principal: ₱" . number_format($principalPaid, 2) . ", Interest: ₱" . number_format($interestPaid, 2) . ")$paymentDetails.";
            $notifMessage = "Loan payment released for request #$requestID amounting to ₱" . number_format($approvedAmount, 2) . "$paymentDetails.";
        } else {
            $auditDescription = "Released payment for request #$requestID to account '$accountName' amounting to ₱" . number_format($approvedAmount, 2) . "$paymentDetails.";
            $notifMessage = "Payment released for request #$requestID amounting to ₱" . number_format($approvedAmount, 2) . "$paymentDetails.";
        }
        addAuditLog($pdo, $user_name, $role, 'Create', 'Payment Release', $auditDescription);
        addNotification($pdo, $user_id, 'Payment Release', $notifMessage, 'fa-coins');

        $updatedRequests = fetchRequests($pdo);
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true, 
            "requests" => $updatedRequests,
            "message" => "Payment successfully released"
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("POST error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "error" => "Server error: " . $e->getMessage()]);
    }
    ob_end_flush();
    exit;
}

$data = [
    "totalRequest" => 0,
    "totalAmountRelease" => 0,
    "rejectedRequest" => 0,
    "newRequest" => 0
]; 

try {
    $sql = "SELECT COUNT(*) as total FROM disbursment.request WHERE Archive = 'NO'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data["totalRequest"] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql = "SELECT IFNULL(SUM(ApprovedAmount), 0) as total FROM disbursment.request WHERE status IN ('Paid') AND Archive = 'NO'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data["totalAmountRelease"] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql = "SELECT COUNT(*) as total FROM disbursment.request WHERE status = 'Reject' AND Archive = 'NO'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data["rejectedRequest"] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql = "SELECT COUNT(*) as total FROM disbursment.request WHERE DATE(date) = CURDATE() AND Archive = 'NO'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data["newRequest"] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    error_log("Data fetch error: " . $e->getMessage());
}
?>