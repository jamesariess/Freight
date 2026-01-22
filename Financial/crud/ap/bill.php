<?php
include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');
include_once('../../utility/head.php');
require_once __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;



require_once __DIR__ . '/../../vendor/autoload.php';
use Cloudinary\Cloudinary;

/* ---------- CLOUDINARY CONFIG ---------- */
$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dccvicfzv',
        'api_key'    => '868917412781798',
        'api_secret' => '3ZBsffXT_xh10IAgsjCKhqU7pyk'
    ]
]);

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["a.Archive = 'NO'"];
$bindings = [];

if (!empty($_GET['vendor_name'])) {
    $whereClauses[] = "v.vendor_name LIKE :vendor_name";
    $bindings[':vendor_name'] = '%' . $_GET['vendor_name'] . '%';
}
if (!empty($_GET['invoice_no'])) {
    $whereClauses[] = "a.reference_no LIKE :reference_no";
    $bindings[':reference_no'] = '%' . $_GET['invoice_no'] . '%';
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "a.status = :status";
    $bindings[':status'] = $_GET['status'];
}
if (!empty($_GET['due_date_from'])) {
    $whereClauses[] = "a.due_date >= :due_date_from";
    $bindings[':due_date_from'] = $_GET['due_date_from'];
}
if (!empty($_GET['due_date_to'])) {
    $whereClauses[] = "a.due_date <= :due_date_to";
    $bindings[':due_date_to'] = $_GET['due_date_to'];
}
if (!empty($_GET['amount_min'])) {
    $whereClauses[] = "a.amount >= :amount_min";
    $bindings[':amount_min'] = (float)$_GET['amount_min'];
}
if (!empty($_GET['amount_max'])) {
    $whereClauses[] = "a.amount <= :amount_max";
    $bindings[':amount_max'] = (float)$_GET['amount_max'];
}

$whereSql = implode(' AND ', $whereClauses);

if (isset($_GET['export']) && in_array($_GET['export'], ['pdf', 'excel'])) {
    $exportType = $_GET['export'];

    $exportSql = "SELECT a.*, v.vendor_name, v.account_number 
                  FROM ar_ap.ap_bills a 
                  JOIN ar_ap.vendor v ON a.vendor_id = v.vendor_id
                  WHERE $whereSql
                  ORDER BY a.created_at ASC";
    $exportStmt = $pdo->prepare($exportSql);
    foreach ($bindings as $k => $v) $exportStmt->bindValue($k, $v);
    $exportStmt->execute();
    $data = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($exportType === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Account #', 'Vendor', 'Invoice No', 'Bill Date', 'Due Date', 'Description', 'Amount', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $rowNum = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowNum, $row['account_number']);
            $sheet->setCellValue('B' . $rowNum, $row['vendor_name']);
            $sheet->setCellValue('C' . $rowNum, $row['reference_no']);
            $sheet->setCellValue('D' . $rowNum, date('d M y', strtotime($row['bill_date'])));
            $sheet->setCellValue('E' . $rowNum, date('d M y', strtotime($row['due_date'])));
            $sheet->setCellValue('F' . $rowNum, $row['description']);
            $sheet->setCellValue('G' . $rowNum, $row['amount']);
            $sheet->setCellValue('H' . $rowNum, $row['status']);
            $rowNum++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="invoices_report.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    if ($exportType === 'pdf') {
        class PDF extends FPDF {
            function Header() {
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(0, 10, 'Vendor Invoice Report', 0, 1, 'C');
                $this->Ln(5);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
            }
        }

        $pdf = new PDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 10);

        $headers = ['Account #', 'Vendor', 'Invoice No', 'Bill Date', 'Due Date', 'Description', 'Amount', 'Status'];
        $widths = [20, 20, 25, 20, 20, 35, 20, 20, 25];

        $pdf->SetFillColor(10, 45, 100);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($data as $row) {
            $pdf->Cell($widths[0], 8, $row['account_number'], 1);
            $pdf->Cell($widths[1], 8, $row['vendor_name'], 1);
            $pdf->Cell($widths[2], 8, $row['reference_no'], 1);
            $pdf->Cell($widths[3], 8, date('d M y', strtotime($row['bill_date'])), 1);
            $pdf->Cell($widths[4], 8, date('d M y', strtotime($row['due_date'])), 1);
            $pdf->Cell($widths[5], 8, substr($row['description'], 0, 35), 1);
            $pdf->Cell($widths[6], 8, number_format($row['amount'], 2), 1, 0, 'R');
            $pdf->Cell($widths[7], 8, $row['status'], 1, 0, 'C');
            $pdf->Ln();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoices_report.pdf"');
        $pdf->Output('D');
        exit;
    }
}

try {
    $countSql = "SELECT COUNT(*) FROM ar_ap.ap_bills a 
                 JOIN ar_ap.vendor v ON a.vendor_id = v.vendor_id
                 WHERE $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($bindings);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
} catch (PDOException $e) {
    echo "Error fetching count: " . $e->getMessage();
    $totalRows = 0;
    $totalPages = 1;
}

try {
    $sql = "SELECT a.*, v.vendor_name, v.account_number FROM ar_ap.ap_bills a 
            JOIN ar_ap.vendor v ON a.vendor_id = v.vendor_id
            WHERE $whereSql
            ORDER BY a.created_at ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $adjustReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching data: " . $e->getMessage();
    $adjustReports = [];
}

function buildPaginatedUrl($newPage) {
    global $_GET;
    $params = $_GET;
    $params['page'] = $newPage;
    return '?' . http_build_query($params);
}

$currentQuery = http_build_query($_GET);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['request_payment'])) {
  $bill_id      = $_POST['bill_id'] ?? '';
    $allocationID = $_POST['allocationID'] ?? '';
    $requestTitle = $_POST['requestTitle'] ?? '';
    $amount       = $_POST['amount'] ?? 0;
    $due          = $_POST['due'] ?? '';
    $Purpuse      = $_POST['Purpuse'] ?? '';

    $cloudinaryUrls = [];
    if (!empty($_FILES['documents']['name'][0] ?? '')) {
        $cloudinaryUrls = uploadDocumentsToCloudinary(
            $cloudinary,
            $_FILES['documents'],
            $bill_id               
        );
    }

  
    $insertSql = "INSERT INTO disbursment.request 
        (allocationID, requestTitle, Amount, Requested_by, Due, `date`,
         status, Archive, Purpuse, documents)
        VALUES (?,?,?,?,?,NOW(),'Pending','NO',?,?)";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        $allocationID,
        $requestTitle,
        $amount,
        $user_name,          
        $due,
        $Purpuse,
        json_encode($cloudinaryUrls)   
    ]);


    $auditDescription = "Requested payment for bill #$bill_id (Title: '$requestTitle', Amount: ₱".number_format($amount,2).").";
    addAuditLog($pdo, $user_name, $role, 'Request Payment', 'Request', $auditDescription);

    $notifMessage = "Payment request created for bill #$bill_id.";
    addNotification($pdo, $user_id, 'Payment Request', $notifMessage, 'fa-hand-holding-dollar');

    
   $redirectUrl = '../../pages/ap/bill.php';
if (!empty($currentQuery)) {
    $redirectUrl .= '?' . $currentQuery;
}
header('Location: ' . $redirectUrl);
exit;
    exit;
}

    if (isset($_POST['update'])) {
        $bill_id = $_POST['bill_id'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $status = $_POST['status'];

       
        $stmtBill = $pdo->prepare("
            SELECT a.description, a.amount, a.due_date, a.status, v.vendor_name 
            FROM ar_ap.ap_bills a
            JOIN ar_ap.vendor v ON a.vendor_id = v.vendor_id
            WHERE a.bill_id = :bill_id
        ");
        $stmtBill->execute([':bill_id' => $bill_id]);
        $billData = $stmtBill->fetch(PDO::FETCH_ASSOC);
        $oldDescription = $billData ? $billData['description'] : 'N/A';
        $oldAmount = $billData && isset($billData['amount']) ? number_format($billData['amount'], 2) : 'N/A';
        $oldDueDate = $billData ? $billData['due_date'] : 'N/A';
        $oldStatus = $billData ? $billData['status'] : 'N/A';
        $vendorName = $billData ? $billData['vendor_name'] : 'Unknown Vendor';

        $updateSql = "UPDATE ar_ap.ap_bills SET description = :description, amount = :amount, due_date = :due_date, status = :status WHERE bill_id = :bill_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':description' => $description,
            ':amount' => $amount,
            ':due_date' => $due_date,
            ':status' => $status,
            ':bill_id' => $bill_id
        ]);


        $auditDescription = "Updated bill #$bill_id (Vendor: '$vendorName'). Changes: Description from '$oldDescription' to '$description', Amount from ₱$oldAmount to ₱" . number_format($amount, 2) . ", Due Date from '$oldDueDate' to '$due_date', Status from '$oldStatus' to '$status'.";
        addAuditLog($pdo, $user_name, $role, 'Update', 'Bill', $auditDescription);
        $notifMessage = "Bill #$bill_id (Vendor: '$vendorName') updated.";
        addNotification($pdo, $user_id, 'Bill Updated', $notifMessage, 'fa-edit');

        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $currentQuery);
        exit;
    }
    
    if (isset($_POST['archive'])) {
        $bill_id = $_POST['archive_bill_id'];

  
        $stmtBill = $pdo->prepare("
            SELECT a.bill_id, a.amount, v.vendor_name 
            FROM ar_ap.ap_bills a
            JOIN ar_ap.vendor v ON a.vendor_id = v.vendor_id
            WHERE a.bill_id = :bill_id
        ");
        $stmtBill->execute([':bill_id' => $bill_id]);
        $billData = $stmtBill->fetch(PDO::FETCH_ASSOC);
        $vendorName = $billData ? $billData['vendor_name'] : 'Unknown Vendor';
        $amount = $billData && isset($billData['amount']) ? number_format($billData['amount'], 2) : 'Unknown Amount';

        $archiveSql = "UPDATE ar_ap.ap_bills SET Archive = 'YES' WHERE bill_id = :bill_id";
        $archiveStmt = $pdo->prepare($archiveSql);
        $archiveStmt->execute([':bill_id' => $bill_id]);

        
        $auditDescription = "Archived bill #$bill_id (Vendor: '$vendorName', Amount: ₱$amount).";
        addAuditLog($pdo, $user_name, $role, 'Archive', 'Bill', $auditDescription);
        $notifMessage = "Bill #$bill_id (Vendor: '$vendorName') archived.";
        addNotification($pdo, $user_id, 'Bill Archived', $notifMessage, 'fa-archive');

        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $currentQuery);
        exit;
    }
}
?>