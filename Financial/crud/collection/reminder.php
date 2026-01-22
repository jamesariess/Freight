<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer-master/src/Exception.php';

function sendEmailPHPMailer($to, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'slatetransportsystem@gmail.com';
        $mail->Password   = 'mfkkigrgxtoascov';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('slatetransportsystem@gmail.com', 'Slate Finance Department');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: {$mail->ErrorInfo}";
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

if (!empty($input['action']) && $input['action'] === 'sendReminder' && !empty($input['reminderID'])) {
    $reminderID = intval($input['reminderID']);


    $rawMethod = !empty($input['method']) ? strtolower(trim($input['method'])) : 'email';
    $methodMap = [
        'email'       => 'Email',
        'cellnumber'  => 'CellNumber',
        'call'        => 'CellNumber',
        'in person'   => 'In Person',
        'visit'       => 'In Person',
    ];
    $methodEnum = $methodMap[$rawMethod] ?? 'Email';   

    $response = ['success' => true, 'message' => 'Reminder marked as sent'];

    try {
      
        $sql = "SELECT f.reminderID, f.planID, f.Contactinfo AS current_contact,
                       i.invoice_id, i.reference_no, i.due_date,
                       c.name AS customer_name, c.email AS customer_email,
                       c.phone AS customer_phone
                FROM collection.follow f
                INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
                INNER JOIN ar_ap.customers c   ON i.customer_id = c.customer_id 
                WHERE f.reminderID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $reminderID]);
        $reminder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reminder) {
            throw new PDOException("Reminder not found for reminderID: $reminderID");
        }

        $remarks        = 'Reminder Sent';
        $success        = true;
        $auditExtra     = '';
        $successMessage = '';
        $errorMessage   = '';

      
        switch ($methodEnum) {
            case 'Email':
                if (!empty($reminder['customer_email'])) {
                    $subject = "Payment Reminder - Invoice #{$reminder['reference_no']}";
                if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
   
                  $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
               . "://{$_SERVER['HTTP_HOST']}/financial";
                } else {
 
                $baseUrl = "https://finance.slatefreight-ph.com";
              }

              $disputeLink = "$baseUrl/contents/collection/dispute.php?invoice_id={$reminder['invoice_id']}";


                    $body = "<p>Dear {$reminder['customer_name']},</p>
                             <p>This is a reminder for your invoice <b>#{$reminder['reference_no']}</b>.</p>
                             <p>The due date is <b>" . date("F j, Y", strtotime($reminder['due_date'])) . "</b>.</p>
                             <p>If you have already made the payment, please disregard this notice.</p>
                             <p>Please contact us if you have any questions or need assistance. <a href='$disputeLink'>Contact Us</a></p>
                             <p>Please ensure payment on time to avoid penalties.</p>
                             <p>Thank you,<br>Slate Finance Department</p>";

                    $mailResult = sendEmailPHPMailer($reminder['customer_email'], $subject, $body);

                    if ($mailResult === true) {
                        $remarks        = 'Emailed Sent';
                        $auditExtra     = " (via Email)";
                        $successMessage = "Email sent to {$reminder['customer_email']}";
                    } else {
                        $remarks        = 'Email Failed';
                        $success        = false;
                        $errorMessage   = "Email failed: $mailResult";
                        $auditExtra     = " (Email failed: $mailResult)";
                    }
                } else {
                    $remarks      = 'Email not Working';
                    $success      = false;
                    $errorMessage = "Customer email not found.";
                    $auditExtra   = " (Email not found)";
                }
                $nextContactEnum = 'Email';
                break;

            case 'CellNumber':
                $remarks        = 'Called';
                $auditExtra     = " (via Cell Number)";
                $successMessage = "Marked as called";
                $nextContactEnum = 'CellNumber';
                break;

            case 'In Person':
                $remarks        = 'Visited In Person';
                $auditExtra     = " (In Person)";
                $successMessage = "Marked as visited in person";
                $nextContactEnum = 'In Person';
                break;

            default:
                throw new Exception("Invalid method enum: $methodEnum");
        }

        
        $upd = $pdo->prepare("UPDATE collection.follow SET Remarks = :remarks WHERE reminderID = :id");
        $upd->execute([':remarks' => $remarks, ':id' => $reminderID]);
        if ($upd->rowCount() === 0) {
            throw new PDOException("No rows updated for reminderID: $reminderID");
        }


        $auditDesc = "Sent payment reminder #$reminderID, invoice #{$reminder['reference_no']} " .
                     "to '{$reminder['customer_name']}' (Contact method: {$reminder['current_contact']}){$auditExtra}.";
        addAuditLog($pdo, $user_name, $role, 'Send Reminder', 'Follow', $auditDesc);

        $notifMsg = "Reminder #$reminderID for invoice #{$reminder['reference_no']} sent to {$reminder['customer_name']}.";
        addNotification($pdo, $user_id, 'Payment Reminder Sent', $notifMsg, 'fa-envelope');

        if ($success) {
            $followUpDate = date("Y-m-d", strtotime("+1 day"));   

            $ins = $pdo->prepare("
                INSERT INTO collection.follow 
                    (planID, InvoiceID, FollowUpDate, Contactinfo, Remarks, paymentstatus, Archive) 
                VALUES 
                    (:planID, :invoiceID, :followUpDate, :contactinfo, 'To Be Sent', 'NOT PAID', 'NO')
            ");
            $ins->execute([
                ':planID'       => $reminder['planID'],
                ':invoiceID'    => $reminder['invoice_id'],
                ':followUpDate' => $followUpDate,
                ':contactinfo'  => $nextContactEnum
            ]);
            $newReminderID = $pdo->lastInsertId();

            $newAudit = "Created next-day follow-up #$newReminderID for invoice #{$reminder['reference_no']} " .
                        "(Customer: '{$reminder['customer_name']}', Follow-up Date: $followUpDate).";
            addAuditLog($pdo, $user_name, $role, 'Create Follow-up', 'Follow', $newAudit);

            $newNotif = "Next-day reminder #$newReminderID created for invoice #{$reminder['reference_no']}.";
            addNotification($pdo, $user_id, 'Next Reminder Created', $newNotif, 'fa-plus');

            $response['message'] = $successMessage;
        } else {
            $response = ['success' => false, 'message' => $errorMessage];
        }

    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        error_log("Send reminder error (reminderID #$reminderID): " . $e->getMessage());
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        error_log("Send reminder DB error (reminderID #$reminderID): " . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

    if (isset($_POST['create'])) {
        $planID = $_POST['planID'] ?? null;
        $invoiceID = $_POST['InvoiceID'] ?? null;
        $contactinfo = $_POST['Contactinfo'] ?? null;

        if (empty($planID) || empty($invoiceID) || empty($contactinfo)) {
            $errorMessage = "❌ Please fill in all required fields.";
        } else {
            try {
              
                $sqlInvoice = "SELECT i.reference_no, c.name AS customer_name
                               FROM ar_ap.ar_invoices i
                               INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
                               WHERE i.invoice_id = :invoiceID AND (i.Archive IS NULL OR UPPER(TRIM(i.Archive))='NO')";
                $stmtInvoice = $pdo->prepare($sqlInvoice);
                $stmtInvoice->execute([':invoiceID' => $invoiceID]);
                $invoice = $stmtInvoice->fetch(PDO::FETCH_ASSOC);

                if (!$invoice) {
                    $errorMessage = "❌ Invoice not found or archived.";
                } else {
                    $sqlPlan = "SELECT remaining_days, plan FROM collection.collection_plan WHERE planID = :planID AND (Archive IS NULL OR UPPER(TRIM(Archive))='NO')";
                    $stmtPlan = $pdo->prepare($sqlPlan);
                    $stmtPlan->execute([':planID' => $planID]);
                    $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

                    if (!$plan) {
                        $errorMessage = "❌ Plan not found or archived.";
                    } else {
                        $remainingDays = (int)$plan['remaining_days'];
                        $planName = $plan['plan'];

                        $sqlDue = "SELECT due_date FROM ar_ap.ar_invoices WHERE invoice_id = :invoiceID";
                        $stmtDue = $pdo->prepare($sqlDue);
                        $stmtDue->execute([':invoiceID' => $invoiceID]);
                        $dueDateData = $stmtDue->fetch(PDO::FETCH_ASSOC);
                        $dueDate = new DateTime($dueDateData['due_date'], new DateTimeZone('Asia/Manila'));

                        $followUpDate = clone $dueDate;
                        $followUpDate->modify("-{$remainingDays} days");
                        $followUpDateStr = $followUpDate->format("Y-m-d");

                        $checkSql = "SELECT COUNT(*) FROM collection.follow 
                                     WHERE InvoiceID = :invoiceID 
                                       AND planID = :planID
                                       AND (Archive IS NULL OR UPPER(TRIM(Archive)) = 'NO')";
                        $checkStmt = $pdo->prepare($checkSql);
                        $checkStmt->execute([':invoiceID' => $invoiceID, ':planID' => $planID]);
                        if ($checkStmt->fetchColumn() > 0) {
                            $errorMessage = "❌ This invoice already has a follow-up with the same plan.";
                        } else {
                            $sql = "INSERT INTO collection.follow 
                                    (planID, InvoiceID, FollowUpDate, Contactinfo, Remarks, paymentstatus, Archive) 
                                    VALUES 
                                    (:planID, :invoiceID, :followUpDate, :contactinfo, 'To Be Sent', 'NOT PAID', 'NO')";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':planID' => $planID,
                                ':invoiceID' => $invoiceID,
                                ':followUpDate' => $followUpDateStr,
                                ':contactinfo' => $contactinfo
                            ]);
                            $reminderID = $pdo->lastInsertId();
                            // Add audit log and notification
                            $auditDescription = "Created payment reminder #$reminderID for invoice #{$invoice['reference_no']} (Customer: '{$invoice['customer_name']}', Plan: '$planName', Follow-up Date: $followUpDateStr, Contact: '$contactinfo').";
                            addAuditLog($pdo, $user_name, $role, 'Create', 'Follow', $auditDescription);
                            $notifMessage = "Payment reminder #$reminderID created for invoice #{$invoice['reference_no']} (Customer: {$invoice['customer_name']}).";
                            addNotification($pdo, $user_id, 'Payment Reminder Created', $notifMessage, 'fa-plus');
                            $successMessage = "✅ Reminder created successfully.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $errorMessage = "❌ Error inserting reminder: " . $e->getMessage();
                error_log("Create reminder error for invoiceID #$invoiceID: " . $e->getMessage());
            }
        }
    }

    if (isset($_POST['archive'])) {
        $reminderID = $_POST['reminderID'];


        $sql = "SELECT f.reminderID, i.invoice_id, i.reference_no, c.name AS customer_name
                FROM collection.follow f
                INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
                INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
                WHERE f.reminderID = :reminderID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':reminderID' => $reminderID]);
        $reminder = $stmt->fetch(PDO::FETCH_ASSOC);
        $customerName = $reminder ? $reminder['customer_name'] : 'Unknown Customer';
        $referenceNo = $reminder ? $reminder['reference_no'] : 'Unknown Invoice';

        $sql = "UPDATE follow SET Archive = 'YES' WHERE reminderID = :reminderID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':reminderID', $reminderID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for reminderID: $reminderID");
            }

            $auditDescription = "Archived payment reminder #$reminderID for invoice #{$referenceNo} (Customer: '$customerName').";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Follow', $auditDescription);
            $notifMessage = "Payment reminder #$reminderID for invoice #{$referenceNo} archived.";
            addNotification($pdo, $user_id, 'Payment Reminder Archived', $notifMessage, 'fa-archive');
            $successMessage = "✅ Payment reminder archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error archiving payment reminder: " . $e->getMessage();
            error_log("Archive reminder error for reminderID #$reminderID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $reminderID = $_POST['reminderID'];
        $contactinfo = $_POST['Contactinfo'];
        $remarks = $_POST['Remarks'];
        $status = $_POST['status'];

        if (empty($contactinfo) || empty($remarks) || empty($status)) {
            $errorMessage = "❌ All fields are required.";
        } else {

            $sql = "SELECT f.reminderID, f.Contactinfo, f.Remarks, f.paymentstatus, i.reference_no, c.name AS customer_name
                    FROM collection.follow f
                    INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
                    INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
                    WHERE f.reminderID = :reminderID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reminderID' => $reminderID]);
            $reminder = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldContactinfo = $reminder ? $reminder['Contactinfo'] : 'N/A';
            $oldRemarks = $reminder ? $reminder['Remarks'] : 'N/A';
            $oldStatus = $reminder ? $reminder['paymentstatus'] : 'N/A';
            $customerName = $reminder ? $reminder['customer_name'] : 'Unknown Customer';
            $referenceNo = $reminder ? $reminder['reference_no'] : 'Unknown Invoice';

            $sql = "UPDATE collection.follow 
                    SET Contactinfo = :contactinfo, Remarks = :remarks, paymentstatus = :status 
                    WHERE reminderID = :reminderID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':contactinfo', $contactinfo);
            $stmt->bindParam(':remarks', $remarks);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':reminderID', $reminderID);

            try {
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No rows updated for reminderID: $reminderID");
                }
       
                $auditDescription = "Updated payment reminder #$reminderID for invoice #{$referenceNo} (Customer: '$customerName'). Changes: Contactinfo from '$oldContactinfo' to '$contactinfo', Remarks from '$oldRemarks' to '$remarks', Payment Status from '$oldStatus' to '$status'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Follow', $auditDescription);
                $notifMessage = "Payment reminder #$reminderID for invoice #{$referenceNo} updated.";
                addNotification($pdo, $user_id, 'Payment Reminder Updated', $notifMessage, 'fa-edit');
                $successMessage = "✅ Reminder updated successfully.";
            } catch (PDOException $e) {
                $errorMessage = "❌ Error updating reminder: " . $e->getMessage();
                error_log("Update reminder error for reminderID #$reminderID: " . $e->getMessage());
            }
        }
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $response = [
        'success' => true,
        'data' => [
            'totalRequest' => 0,
            'totalAmountRelease' => 0,
            'rejectedRequest' => 0,
            'newRequest' => 0,
            'overdue' => [],
            'todayReminders' => [],
            'tomorrowReminders' => []
        ]
    ];

    try {

$sql = "SELECT COUNT(DISTINCT InvoiceID) as total FROM collection.follow WHERE Archive='NO'";
$stmt = $pdo->query($sql);
$response['data']['totalRequest'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


$sql = "SELECT COUNT(DISTINCT InvoiceID) as total FROM collection.follow WHERE paymentstatus='Paid' AND Archive='NO'";
$stmt = $pdo->query($sql);
$response['data']['totalAmountRelease'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


$sql = "SELECT COUNT(DISTINCT InvoiceID) as total FROM collection.follow WHERE paymentstatus='Not Paid' AND Archive='NO'";
$stmt = $pdo->query($sql);
$response['data']['rejectedRequest'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


$sql = "SELECT COUNT(DISTINCT InvoiceID) as total FROM collection.follow WHERE Remarks='Failed To Sent' AND Archive='NO'";
$stmt = $pdo->query($sql);
$response['data']['newRequest'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


$now = date("Y-m-d H:i:s");
$sql = "
    SELECT 
        i.reference_no,
        i.amount,
        i.due_date,
        f.paymentstatus,
        f.Contactinfo,
        f.Remarks,
        f.reminderID
    FROM (
        SELECT InvoiceID, MAX(reminderID) AS max_reminderID
        FROM collection.follow
        WHERE paymentstatus = 'Not Paid' AND Archive = 'NO'
        GROUP BY InvoiceID
    ) latest
    JOIN collection.follow f ON f.reminderID = latest.max_reminderID
    JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
    WHERE i.due_date < :now
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['now' => $now]);
$response['data']['overdue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$startOfToday = date("Y-m-d 00:00:00");
$endOfToday = date("Y-m-d 23:59:59");
$sql = "
    SELECT 
        i.reference_no,
        i.amount,
        f.FollowUpDate,
        f.paymentstatus,
        f.Contactinfo,
        f.Remarks,
        f.reminderID
    FROM (
        SELECT InvoiceID, MAX(reminderID) AS max_reminderID
        FROM collection.follow
        WHERE paymentstatus = 'Not Paid' AND Archive = 'NO'
        GROUP BY InvoiceID
    ) today_latest
    JOIN collection.follow f ON f.reminderID = today_latest.max_reminderID
    JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
    WHERE f.FollowUpDate BETWEEN :start AND :end
      AND i.due_date >= :start  -- Not overdue
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['start' => $startOfToday, 'end' => $endOfToday]);
$response['data']['todayReminders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$startOfTomorrow = date("Y-m-d 00:00:00", strtotime("+1 day"));
$endOfTomorrow = date("Y-m-d 23:59:59", strtotime("+1 day"));
$sql = "
    SELECT 
        i.reference_no,
        i.amount,
        f.FollowUpDate,
        f.paymentstatus,
        f.Contactinfo,
        f.Remarks,
        f.reminderID
    FROM (
        SELECT InvoiceID, MAX(reminderID) AS max_reminderID
        FROM collection.follow
        WHERE paymentstatus = 'Not Paid' AND Archive = 'NO'
        GROUP BY InvoiceID
    ) tomorrow_latest
    JOIN collection.follow f ON f.reminderID = tomorrow_latest.max_reminderID
    JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
    WHERE f.FollowUpDate BETWEEN :start AND :end
      AND i.due_date >= :tomorrow_start  -- Not overdue or today
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'start' => $startOfTomorrow,
    'end' => $endOfTomorrow,
    'tomorrow_start' => $startOfTomorrow
]);
$response['data']['tomorrowReminders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $response['success'] = false;
        $response['message'] = "❌ Database error: " . $e->getMessage();
        $errorMessage = "❌ Database error: " . $e->getMessage();
        error_log("Fetch AJAX data error: " . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])              : 1;
$offset = ($page - 1) * $limit;

$whereClauses = ["f.Archive = 'NO'"];
$bindings     = [];

if (!empty($_GET['search'])) {
    $whereClauses[] = "(i.reference_no LIKE :search OR c.name LIKE :search OR p.plan LIKE :search)";
    $bindings[':search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['payment_status'])) {
    $whereClauses[] = "LOWER(f.paymentstatus) = :payment_status";
    $bindings[':payment_status'] = strtolower($_GET['payment_status']);
}
if (!empty($_GET['remarks_status'])) {
    $whereClauses[] = "LOWER(f.Remarks) = :remarks_status";
    $bindings[':remarks_status'] = strtolower($_GET['remarks_status']);
}
if (!empty($_GET['date_from'])) {
    $whereClauses[] = "f.FollowUpDate >= :date_from";
    $bindings[':date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $whereClauses[] = "f.FollowUpDate <= :date_to";
    $bindings[':date_to'] = $_GET['date_to'] . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereClauses);


$countSql = "
    SELECT COUNT(*) 
    FROM collection.follow f
    INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
    INNER JOIN collection.collection_plan p ON f.planID = p.planID
    INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
foreach ($bindings as $k => $v) $countStmt->bindValue($k, $v);
try {
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $errorMessage = "Error counting reminders: " . $e->getMessage();
    $totalRows = 0;
}
$totalPages = (int)ceil($totalRows / $limit);


$sql = "
    SELECT  
        f.reminderID,
        f.FollowUpDate,
        f.Contactinfo, 
        f.Remarks,
        f.paymentstatus,
        i.reference_no,
        p.plan,
        c.name AS customer_name,
        DATE_FORMAT(f.FollowUpDate, '%M %e, %Y \\a\\t %l:%i %p') AS formatted_date
    FROM collection.follow f
    INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
    INNER JOIN collection.collection_plan p ON f.planID = p.planID
    INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
    WHERE $whereSql
    ORDER BY f.reminderID DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
foreach ($bindings as $k => $v) $stmt->bindValue($k, $v);
try {
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching reminders: " . $e->getMessage();
    error_log("Fetch reminders error: " . $e->getMessage());
    $reminders = [];
} 

function buildPaginatedUrl($page) {
    $p = $_GET;
    $p['page'] = $page;
    return '?' . http_build_query($p);
}

try {
    $now = date("Y-m-d H:i:s");
    $sql = "SELECT i.reference_no, i.amount, i.due_date, f.paymentstatus, f.Contactinfo, f.Remarks, f.reminderID
            FROM collection.follow f
            JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id 
            WHERE paymentstatus='Not Paid'
              AND i.due_date < :now
              AND f.Archive='NO'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['now' => $now]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $startOfToday = date("Y-m-d 00:00:00");
    $endOfToday = date("Y-m-d 23:59:59");
    $sql = "SELECT i.reference_no, i.amount, f.FollowUpDate, f.paymentstatus, f.Contactinfo, f.Remarks, f.reminderID
            FROM collection.follow f
            JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id 
            WHERE f.FollowUpDate BETWEEN :start AND :end
              AND f.Archive='NO'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start' => $startOfToday, 'end' => $endOfToday]);
    $todayReminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $startOfTomorrow = date("Y-m-d 00:00:00", strtotime("+1 day"));
    $endOfTomorrow = date("Y-m-d 23:59:59", strtotime("+1 day"));
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start' => $startOfTomorrow, 'end' => $endOfTomorrow]);
    $tomorrowReminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "❌ Error fetching reminders: " . $e->getMessage();
    error_log("Fetch reminders error: " . $e->getMessage());
}
?>