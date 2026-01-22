<?php
ob_start();
include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');
include_once('../../utility/head.php');
function getPaymentHistory($pdo, $loanId) {
    $sql = "
        SELECT payment_date, amount, method, remarks
        FROM ap_payments
        WHERE LoanID = :loanId AND Archive = 'NO'
        ORDER BY payment_date DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':loanId', $loanId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOutstandingForLoan($pdo, $loanId) {
    $sql = "
        SELECT LoanAmount, paidAmount, interestRate
        FROM loan
        WHERE LoanID = :loanId 
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':loanId', $loanId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $principal = $row['LoanAmount'];
        $interestRate = $row['interestRate'];
        $totalInterest = $principal * ($interestRate / 100);
        $totalRepayable = $principal + $totalInterest;
        $paid = $row['paidAmount'] ?? 0;
        return $totalRepayable - $paid;
    }
    return 0;
}

function getActiveLoans($pdo) {
    $sql = "
        SELECT 
            l.LoanID,
            l.LoanTitle,
            l.LoanAmount,
            l.interestRate,
            l.paidAmount,
            l.startDate,
            l.EndDate,
            l.Status,
            l.pdf_filename,
            l.Remarks,
            v.vendor_name as lender
        FROM loan l
        LEFT JOIN vendor v ON l.VendorID = v.vendor_id
        WHERE l.Archive = 'NO' AND l.Status != 'Paid'
        ORDER BY l.LoanID
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $loans = [];
    while ($row = $stmt->fetch()) {
        $principal = $row['LoanAmount'];
        $interestRate = $row['interestRate'];
        $paid = $row['paidAmount'] ?? 0;
        $totalInterest = $principal * ($interestRate / 100);
        $totalRepayable = $principal + $totalInterest;
        $outstanding = $totalRepayable - $paid;
        $monthlyDue = ($outstanding > 0) ? ($outstanding + ($outstanding * $interestRate / 100)) / 12 : 0;
        $dueDate = date('Y-m-d', strtotime($row['EndDate'])); 
        $loans[] = [
            'id' => 'LN-' . date('Y') . '-' . str_pad($row['LoanID'], 3, '0', STR_PAD_LEFT),
            'rawId' => $row['LoanID'], 
            'lender' => $row['lender'],
            'title' => $row['LoanTitle'],
            'principal' => '₱' . number_format($principal),
            'outstanding' => '₱' . number_format($outstanding, 2),
            'rate' => $interestRate . '%',
            'monthlyDue' => '₱' . number_format($monthlyDue, 0),
            'dueDate' => $dueDate,
            'status' => $row['Status'],
            'pdf_filename' => $row['pdf_filename'],
            'remarks' => $row['Remarks'] ?? 'Draft'
        ];
    }
    return $loans;
}

function getTotalActiveLoans($pdo) {
    $sql = "SELECT COUNT(*) as count FROM loan WHERE Archive = 'NO' AND Status != 'Paid' AND Remarks = 'Approved' ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['count'];
}
 
function getTotalOutstanding($pdo) {
    $sql = "
        SELECT l.LoanID, l.LoanAmount, COALESCE(l.paidAmount, 0) as paidAmount, l.interestRate
        FROM loan l
        WHERE l.Archive = 'NO' AND l.Status != 'Paid' AND Remarks = 'Approved'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $totalOutstanding = 0;
    while ($row = $stmt->fetch()) {
        $principal = $row['LoanAmount'];
        $interestRate = $row['interestRate'];
        $paid = $row['paidAmount'];
        $totalInterest = $principal * ($interestRate / 100);
        $totalRepayable = $principal + $totalInterest;
        $outstanding = $totalRepayable - $paid;
        $totalOutstanding += $outstanding;
    }
    return '₱' . number_format($totalOutstanding, 2);
}

function getNextDueDate($pdo) {
    $sql = "
        SELECT MIN(EndDate) as nextDue
        FROM loan
        WHERE Archive = 'NO' AND Status != 'Paid' AND Remarks = 'Approved'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch();
    return !empty($row['nextDue']) 
    ? date('M d, Y', strtotime($row['nextDue'])) 
    : 'No Active Loan';
}

ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'submit_payment') {
            try {
                $loanId = (int)$_POST['loanId'];
                $amount = (float)$_POST['amount'];
                $method = $_POST['method'];
                $remarks = htmlspecialchars($_POST['remarks'] ?? '', ENT_QUOTES, 'UTF-8');
                $paymentDate = date('Y-m-d');
                $requested = "admin";

                $validMethods = ['Bank Transfer', 'Check', 'Cash'];
                if (!in_array($method, $validMethods)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
                    exit;
                }

                $pdo->beginTransaction();

                $sql = "INSERT INTO ap_payments (LoanID, payment_date, amount, method, remarks, created_at, Archive) 
                        VALUES (:loanId, :paymentDate, :amount, :method, :remarks, NOW(), 'NO')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'loanId' => $loanId,
                    'paymentDate' => $paymentDate,
                    'amount' => $amount,
                    'method' => $method,
                    'remarks' => $remarks
                ]);

                $updateSql = "UPDATE loan SET paidAmount = COALESCE(paidAmount, 0) + :amount WHERE LoanID = :loanId";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    'amount' => $amount,
                    'loanId' => $loanId
                ]);

                $getSql = "SELECT LoanTitle, startDate FROM loan WHERE LoanID = :loanId";
                $getStmt = $pdo->prepare($getSql);
                $getStmt->execute(['loanId' => $loanId]);
                $loanData = $getStmt->fetch(PDO::FETCH_ASSOC);
                if (!$loanData) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Loan not found']);
                    exit;
                }
                $loanTitle = $loanData['LoanTitle'];
                $startDate = $loanData['startDate'];

                $Loantitle = 11;
                $yearlybudget = date('Y');
                $select = "SELECT allocationID FROM costallocation WHERE accountID = :accountID AND yearlybudget = :yearlybudget";
                $allocStmt = $pdo->prepare($select);
                $allocStmt->execute([
                    'accountID' => $Loantitle,
                    'yearlybudget' => $yearlybudget
                ]);
                $allocData = $allocStmt->fetch(PDO::FETCH_ASSOC);
                $allocationID = $allocData ? $allocData['allocationID'] : null;

                $requestSql = "INSERT INTO request (requestTitle, amount, Requested_by, Due, LoanID, Purpuse, allocationID) 
                              VALUES (:title, :amount, :requested, :due, :loanId, 'Loan Payment', :allocationID)";
                $requestStmt = $pdo->prepare($requestSql);
                $requestStmt->execute([
                    'title' => $loanTitle,
                    'amount' => $amount,
                    'requested' => $requested,
                    'due' => $startDate,
                    'loanId' => $loanId,
                    'allocationID' => $allocationID
                ]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Payment submitted for approval']);
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error submitting payment: ' . $e->getMessage()]);
                exit;
            }
        } elseif ($_POST['action'] === 'get_history') {
            try {
                $loanId = (int)$_POST['loanId'];
                $history = getPaymentHistory($pdo, $loanId);
                echo json_encode(['success' => true, 'message' => 'History retrieved successfully', 'data' => $history]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching history: ' . $e->getMessage()]);
                exit;
            }
        } elseif ($_POST['action'] === 'approve_loan') {
            try {
                $loanId = (int)$_POST['loanId'];
                $uploadDir = __DIR__ . '/../../pdfs/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['document']['tmp_name'];
                    $fileExt = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['pdf', 'doc', 'docx'];
                    if (!in_array($fileExt, $allowedExts)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, or DOCX allowed']);
                        exit;
                    }

                    $fileName = 'loan_' . $loanId . '_' . time() . '.' . $fileExt;
                    $uploadPath = $uploadDir . $fileName;
                    $relativePath = 'pdfs/' . $fileName;

                    if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                        $pdo->beginTransaction();

                        $sql = "UPDATE loan SET Remarks = 'Approved', pdf_filename = :pdf_filename, paidAmount = 0 WHERE LoanID = :loanId AND Archive = 'NO'";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute(['pdf_filename' => $relativePath, 'loanId' => $loanId]);

                        $loanSql = "SELECT LoanAmount, interestRate, LoanTitle, startDate, paymentMethod FROM loan WHERE LoanID = :loanId";
                        $loanStmt = $pdo->prepare($loanSql);
                        $loanStmt->execute(['loanId' => $loanId]);
                        $loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);

                        $loanAmount = (float)$loanData['LoanAmount'];
                        $interestRate = (float)$loanData['interestRate'];
                        $interest = $loanAmount * ($interestRate / 100);
                        $totalAmount = $loanAmount + $interest;
                        $loanTitle = $loanData['LoanTitle'];
                        $startDate = $loanData['startDate'];
                        $paymentmethod = $loanData['paymentMethod'];

                        if ($paymentmethod === 'Cash') {
                            $accountID = 1;
                        } else {
                            $accountID = 2;
                        }

                        $entrySql = "INSERT INTO entries (date, description, referenceType, createdBy, Archive) 
                                    VALUES (NOW(), :desc, 'Loan Approval', 'system', 'NO')";
                        $entryStmt = $pdo->prepare($entrySql);
                        $entryStmt->execute(['desc' => 'Loan Approved: ' . $loanTitle]);
                        $journalID = $pdo->lastInsertId();

                        $detailSql = "INSERT INTO details (journalID, accountID, debit, credit, Archive) 
                                     VALUES (:journalID, :accountID, :debit, :credit, 'NO')";
                        $detailStmt = $pdo->prepare($detailSql);

                        $detailStmt->execute([
                            'journalID' => $journalID,
                            'accountID' => $accountID,
                            'debit' => $totalAmount,
                            'credit' => 0
                        ]);

                        $detailStmt->execute([
                            'journalID' => $journalID,
                            'accountID' => 11,
                            'debit' => 0,
                            'credit' => $totalAmount
                        ]);

                        $pdo->commit();
                        echo json_encode(['success' => true, 'message' => 'Loan approved, journal entries created, and document uploaded successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error uploading file']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                }
                exit;
            } catch (Exception $e) {
                if (isset($pdo)) {
                    try {
                        $pdo->rollBack();
                    } catch (PDOException $pe) {
                    }
                }
                echo json_encode(['success' => false, 'message' => 'Error approving loan: ' . $e->getMessage()]);
                exit;
            }
        } elseif ($_POST['action'] === 'reject_loan') {
            try {
                $loanId = (int)$_POST['loanId'];
                $pdo->beginTransaction();
                $sql = "UPDATE loan SET Remarks = 'Rejected' WHERE LoanID = :loanId AND Archive = 'NO'";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['loanId' => $loanId]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Loan rejected successfully']);
                exit;
            } catch (PDOException $e) {
                if (isset($pdo)) $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error rejecting loan: ' . $e->getMessage()]);
                exit;
            }
        } elseif ($_POST['action'] === 'get_loan_details') {
            try {
                $loanId = (int)$_POST['loanId'];
                $sql = "
                    SELECT LoanID, LoanAmount, paidAmount, AmountperMonth, interestRate
                    FROM loan
                    WHERE LoanID = :loanId AND Archive = 'NO'
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':loanId', $loanId, PDO::PARAM_INT);
                $stmt->execute();
                $loanData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($loanData) {
                    $principal = $loanData['LoanAmount'];
                    $interestRate = $loanData['interestRate'];
                    $totalInterest = $principal * ($interestRate / 100);
                    $totalRepayable = $principal + $totalInterest;
                    $paid = $loanData['paidAmount'] ?? 0;
                    $outstanding = $totalRepayable - $paid;
                    echo json_encode([
                        'success' => true,
                        'message' => 'Loan details retrieved successfully',
                        'data' => [
                            'loanId' => $loanData['LoanID'],
                            'outstanding' => $outstanding,
                            'amountPerMonth' => $loanData['AmountperMonth'] ?? 0
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Loan not found']);
                }
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching loan details: ' . $e->getMessage()]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
        exit;
    }
}
?>
<?php ob_end_flush(); ?>
