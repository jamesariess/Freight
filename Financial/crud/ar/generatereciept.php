<?php
require_once __DIR__ . '/../../vendor/autoload.php';
include_once('../../utility/head.php');
use Cloudinary\Cloudinary;

function generateReceiptImage($receiptNumber, $invoiceRef, $amount, $method, $issuedBy, $receiptID) {
    $cloudinary = new Cloudinary([
        'cloud' => [
            'cloud_name' => 'dccvicfzv',
            'api_key' => '868917412781798',
            'api_secret' => '3ZBsffXT_xh10IAgsjCKhqU7pyk'
        ]
    ]);

    $width = 600;
    $height = 800;
    $im = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    $gray = imagecolorallocate($im, 128, 128, 128);
    $green = imagecolorallocate($im, 0, 128, 0);

    imagefilledrectangle($im, 0, 0, $width, $height, $white);

    $y = 20;
    imagestring($im, 5, 150, $y, "SLATE Freight Mgmt System", $black);
    $y += 30;
    imagestring($im, 3, 200, $y, "123 Business Avenue, SF, CA", $black);
    $y += 20;
    imagestring($im, 3, 220, $y, "www.slatefreight.com", $black);
    $y += 30;

    imagestring($im, 3, 50, $y, "Official Receipt No: $receiptNumber", $black);
    imagestring($im, 3, 350, $y, "Date: " . date("F j, Y"), $black);
    $y += 20;
    imagestring($im, 3, 50, $y, "Invoice Ref: $invoiceRef", $black);
    $y += 30;

    imagestring($im, 4, 50, $y, "Bill To:", $black);
    $y += 20;
    imagestring($im, 3, 50, $y, "Client Company Ltd.", $black);
    $y += 20;
    imagestring($im, 3, 50, $y, "Attn: John Smith", $black);
    $y += 30;

    imagestring($im, 3, 50, $y, "Description", $black);
    imagestring($im, 3, 400, $y, "Amount", $black);
    $y += 20;
    imagestring($im, 3, 50, $y, "Payment", $black);
    imagestring($im, 3, 400, $y, number_format($amount, 2), $black);
    $y += 30;

    imagestring($im, 4, 50, $y, "TOTAL PAID:", $black);
    imagestring($im, 4, 400, $y, number_format($amount, 2), $green);
    $y += 30;

    imagestring($im, 3, 50, $y, "Method: $method", $black);
    $y += 20;
    imagestring($im, 3, 50, $y, "Issued By: $issuedBy", $black);
    $y += 40;

    imagestring($im, 2, 100, $y, "Thank you for your business! This is a computer-generated receipt.", $gray);

    $tempFile = tempnam(sys_get_temp_dir(), 'receipt_');
    imagepng($im, $tempFile);
    imagedestroy($im);

    $year = date('Y');
    $folderPath = "financial/$year/official_receipt";

    $result = $cloudinary->uploadApi()->upload($tempFile, [
        'folder' => $folderPath,
        'public_id' => "receipt_" . $receiptID
    ]);

    unlink($tempFile);

    return $result['secure_url'];
}

function getOrCreateAccountID($pdo, $accountName, $accountType) {
    $prefixMap = [
        'Assets' => 'AS',
        'Liabilities' => 'LI',
        'Equity' => 'EQ',
        'Revenue' => 'RE',
        'Expenses' => 'EX'
    ];

    if (!array_key_exists($accountType, $prefixMap)) {
        throw new Exception("Invalid account type: $accountType");
    }

    $prefix = $prefixMap[$accountType];

    $sql = "SELECT accountID, accountCode FROM ledger.chartofaccount WHERE accountName = :accountName AND accounType = :accounType";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':accountName', $accountName, PDO::PARAM_STR);
    $stmt->bindParam(':accounType', $accountType, PDO::PARAM_STR);
    $stmt->execute();
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        return $account['accountID'];
    }

    $sql = "SELECT accountCode FROM ledger.chartofaccount WHERE accounType = :accounType ORDER BY accountCode DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':accounType', $accountType, PDO::PARAM_STR);
    $stmt->execute();
    $lastAccount = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastAccount) {
        $lastNumber = (int)substr($lastAccount['accountCode'], strpos($lastAccount['accountCode'], '-') + 1);
        $newNumber = str_pad($lastNumber + 1, 3, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "001";
    }

    $accountCode = $prefix . "-" . $newNumber;

    $sql = "INSERT INTO ledger.chartofaccount (accountName, accounType, accountCode) VALUES (:accountName, :accounType, :accountCode)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':accountName', $accountName, PDO::PARAM_STR);
    $stmt->bindParam(':accounType', $accountType, PDO::PARAM_STR);
    $stmt->bindParam(':accountCode', $accountCode, PDO::PARAM_STR);
    $stmt->execute();

    return $pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    if (empty($_POST['Invoice']) || empty($_POST['Amount']) || empty($_POST['paymethod'])) {
        $errorMessage = "Error: All fields (Invoice, Amount, Payment Method) are required.";
        error_log("Form validation failed: " . print_r($_POST, true));
    } else {
        $invoiceID = $_POST['Invoice'];
        $amount    = floatval($_POST['Amount']);
        $method    = $_POST['paymethod'];
        $remarks   = $_POST['remarks'] ?? 'Payment recorded';
        $issuedBy  = $user_name ?? 'System';

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO ar_ap.ar_collections (invoice_id, amount, method, remarks, payment_date, created_at) 
                    VALUES (:invoiceID, :amount, :method, :remarks, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':invoiceID', $invoiceID, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':method', $method, PDO::PARAM_STR);
            $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
            $stmt->execute();
            $paymentID = $pdo->lastInsertId();

            $stmtInvoice = $pdo->prepare(
                "SELECT reference_no, amount, stat FROM ar_ap.ar_invoices WHERE invoice_id = :id"
            );
            $stmtInvoice->bindParam(':id', $invoiceID, PDO::PARAM_INT);
            $stmtInvoice->execute();
            $invoiceData = $stmtInvoice->fetch(PDO::FETCH_ASSOC);

            if (!$invoiceData) {
                throw new Exception("Invoice not found for ID: $invoiceID");
            }

            $invoiceRef    = $invoiceData['reference_no'];
            $invoiceAmount = floatval($invoiceData['amount']);

            $stmtPaidAmount = $pdo->prepare(
                "SELECT SUM(amount) FROM ar_ap.ar_collections WHERE invoice_id = :invoiceID"
            );
            $stmtPaidAmount->bindParam(':invoiceID', $invoiceID, PDO::PARAM_INT);
            $stmtPaidAmount->execute();
            $totalPaid = floatval($stmtPaidAmount->fetchColumn());

            $newInvoiceStat = ($totalPaid >= $invoiceAmount) ? 'Paid' : 'Partially Paid';
            $newRemarks      = ($totalPaid >= $invoiceAmount) ? 'Full Payment' : 'Partial Payment';

            $sql = "UPDATE ar_ap.ar_collections SET remarks = :remarks WHERE collection_id = :paymentID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':remarks', $newRemarks, PDO::PARAM_STR);
            $stmt->bindParam(':paymentID', $paymentID, PDO::PARAM_INT);
            $stmt->execute();

            $sqlInvoiceUpdate = "UPDATE ar_ap.ar_invoices SET stat = :stat WHERE invoice_id = :invoiceID";
            $stmtInvoiceUpdate = $pdo->prepare($sqlInvoiceUpdate);
            $stmtInvoiceUpdate->bindParam(':stat', $newInvoiceStat, PDO::PARAM_STR);
            $stmtInvoiceUpdate->bindParam(':invoiceID', $invoiceID, PDO::PARAM_INT);
            $stmtInvoiceUpdate->execute();

            $sqlFollowUpdate = "UPDATE collection.follow SET paymentstatus = :paymentstatus WHERE InvoiceID = :invoiceID AND Archive = 'NO'";
            $stmtFollowUpdate = $pdo->prepare($sqlFollowUpdate);
            $stmtFollowUpdate->bindParam(':paymentstatus', $newInvoiceStat, PDO::PARAM_STR);
            $stmtFollowUpdate->bindParam(':invoiceID', $invoiceID, PDO::PARAM_INT);
            $stmtFollowUpdate->execute();

            $receiptNumber = "RCP-" . date("Y") . "-" . str_pad($paymentID, 5, "0", STR_PAD_LEFT);
            $sqlReceipt = "INSERT INTO collection.receipt (paymentID, receiptNumber, receiptsdate, issueBy, receiptImage) 
                           VALUES (:paymentID, :receiptNumber, NOW(), :issuedBy, '')";
            $stmtReceipt = $pdo->prepare($sqlReceipt);
            $stmtReceipt->bindParam(':paymentID', $paymentID, PDO::PARAM_INT);
            $stmtReceipt->bindParam(':receiptNumber', $receiptNumber, PDO::PARAM_STR);
            $stmtReceipt->bindParam(':issuedBy', $issuedBy, PDO::PARAM_STR);
            $stmtReceipt->execute();
            $receiptID = $pdo->lastInsertId();

            $imagePath = generateReceiptImage(
                $receiptNumber,
                $invoiceRef,
                $amount,
                $method,
                $issuedBy,
                $receiptID
            );

            $sql = "UPDATE collection.receipt SET receiptImage = :imagePath WHERE receiptID = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':imagePath', $imagePath, PDO::PARAM_STR);
            $stmt->bindParam(':id', $receiptID, PDO::PARAM_INT);
            $stmt->execute();

            $sqlEntries = "
                INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive)
                VALUES (NOW(), :description, :ref, :createdBy, 'NO')
            ";
            $stmtEntries = $pdo->prepare($sqlEntries);
            $stmtEntries->bindValue(':description', 'Collection for Invoice #' . $invoiceRef, PDO::PARAM_STR);
            $stmtEntries->bindValue(':ref', 'INV-' . $invoiceRef, PDO::PARAM_STR);
            $stmtEntries->bindValue(':createdBy', $issuedBy, PDO::PARAM_STR);
            $stmtEntries->execute();
            $journalID = $pdo->lastInsertId();

            $cashAccountName = (strtolower($method) === 'cash')
                ? 'Cash on Hand'
                : 'Cash on Bank';

            $cashAccountID      = getOrCreateAccountID($pdo, $cashAccountName, 'Assets');
            $receivableAccountID = getOrCreateAccountID($pdo, 'Account Receivable', 'Assets');

            $detailSqlDebit = "
                INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $stmtDetailsDebit = $pdo->prepare($detailSqlDebit);
            $stmtDetailsDebit->bindParam(':journalID', $journalID, PDO::PARAM_INT);
            $stmtDetailsDebit->bindParam(':accountID', $cashAccountID, PDO::PARAM_INT);
            $stmtDetailsDebit->bindParam(':debit', $amount, PDO::PARAM_STR);
            $stmtDetailsDebit->bindValue(':credit', 0, PDO::PARAM_STR);
            $stmtDetailsDebit->execute();

            $detailSqlCredit = "
                INSERT INTO ledger.details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $stmtDetailsCredit = $pdo->prepare($detailSqlCredit);
            $stmtDetailsCredit->bindParam(':journalID', $journalID, PDO::PARAM_INT);
            $stmtDetailsCredit->bindParam(':accountID', $receivableAccountID, PDO::PARAM_INT);
            $stmtDetailsCredit->bindValue(':debit', 0, PDO::PARAM_STR);
            $stmtDetailsCredit->bindParam(':credit', $amount, PDO::PARAM_STR);
            $stmtDetailsCredit->execute();

            $pdo->commit();

            $successMessage = "Collection & Receipt created successfully.";
            $auditDescription = "Created collection #$paymentID (Invoice: #$invoiceRef, Amount: ₱" .
                                number_format($amount, 2) . ", Receipt: $receiptNumber).";
            addAuditLog($pdo, $issuedBy, $role ?? 'Unknown', 'Create', 'Collection', $auditDescription);
            $notifMessage = "Collection #$paymentID (Invoice: #$invoiceRef) created with receipt #$receiptNumber.";
            addNotification($pdo, $user_id ?? 0, 'Collection Created', $notifMessage, 'fa-file-invoice-dollar');

            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = "Database error: " . $e->getMessage();
            error_log(
                "Create collection error for invoice_id #$invoiceID: " .
                $e->getMessage() .
                " | Query: $sql | Bindings: " .
                print_r($stmt->debugDumpParams(), true)
            );
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "General error: " . $e->getMessage();
            error_log("Create collection error for invoice_id #$invoiceID: " . $e->getMessage());
        }
    }
}
?>
