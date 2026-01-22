

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once('../../utility/connection.php'); 
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer-master/src/Exception.php';

$companyName = "Slate Freight Finance";
$fromEmail   = 'slatetransportsystem@gmail.com';
$fromName    = 'Slate Freight Finance';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true) ?: [];


$mode = $input['mode'] ?? $_GET['mode'] ?? 'overview';

$page  = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));


if (isset($input['limit'])) {
    $limit = (int)$input['limit'];
} elseif (isset($_GET['limit'])) {
    $limit = (int)$_GET['limit'];
} else {
    $limit = ($mode === 'log') ? 5 : 10;
}
$limit  = max(1, $limit);
$offset = ($page - 1) * $limit;

function send_email_smtp($toEmail, $toName, $subject, $htmlBody) {
    global $fromEmail, $fromName;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'slatetransportsystem@gmail.com';
        $mail->Password   = 'mfkkigrgxtoascov';  // App Password - consider moving to env/config
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();
        return ['ok' => true];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}

function getTotalCount($pdo, $mode) {
    if ($mode === 'overview') {
        $sql = "SELECT COUNT(DISTINCT i.invoice_id) FROM collection.follow f
                INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
                WHERE f.reminderID = (SELECT MAX(f2.reminderID) FROM collection.follow f2 WHERE f2.InvoiceID = f.InvoiceID)
                  AND f.FollowUpDate = CURDATE()
                  AND i.due_date >= CURDATE()
                  AND i.stat IN ('Draft','Pending','Partially Paid')";
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } elseif ($mode === 'queue') {
        $sql = "SELECT COUNT(DISTINCT i.invoice_id) FROM collection.follow f
                INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
                WHERE f.reminderID = (SELECT MAX(f2.reminderID) FROM collection.follow f2 WHERE f2.InvoiceID = f.InvoiceID)
                  AND i.due_date <= CURDATE()
                  AND i.stat = 'Pending'";
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } elseif ($mode === 'log') {
        $sql = "SELECT COUNT(*) FROM collection.reminder WHERE Archive = 'NO'";
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } else {
        return 0;
    }
}

try {

    $sql = '';
    $params = [];

    if ($mode === 'overview') {
        $sql = "
            SELECT DISTINCT 
                i.reference_no,
                i.amount,
                i.due_date,
                c.name AS client,
                f.reminderID,
                p.plan_type,
                i.stat,
                f.FollowUpDate
            FROM collection.follow f
            INNER JOIN collection.collection_plan p ON f.planID = p.planID
            INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
            INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            WHERE f.reminderID = (
                SELECT MAX(f2.reminderID)
                FROM collection.follow f2
                WHERE f2.InvoiceID = f.InvoiceID
            )
            AND f.FollowUpDate = CURDATE()
            AND i.due_date >= CURDATE()
            AND i.stat IN ('Draft','Pending','Partially Paid')
            ORDER BY i.due_date ASC
            LIMIT :limit OFFSET :offset
        ";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    } elseif ($mode === 'queue') {
        $sql = "
            SELECT 
                i.reference_no,
                i.amount,
                i.due_date,
                c.name AS client,
                f.reminderID,
                p.plan_type,
                i.stat
            FROM collection.follow f
            INNER JOIN collection.collection_plan p ON f.planID = p.planID
            INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
            INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            WHERE f.reminderID = (
                SELECT MAX(f2.reminderID)
                FROM collection.follow f2
                WHERE f2.InvoiceID = f.InvoiceID
            )
            AND i.due_date <= CURDATE()
            AND i.stat = 'Pending'
            ORDER BY i.due_date ASC
            LIMIT :limit OFFSET :offset
        ";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    } elseif ($mode === 'log') {
       
        $sql = "
            SELECT 
                r.ReminderSent,
                r.Type,
                c.name AS customer,
                r.Status
            FROM collection.reminder r
            INNER JOIN collection.follow f ON r.FollowID = f.reminderID
            INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
            INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            WHERE r.Archive = 'NO'
            ORDER BY r.ReminderSent DESC
            LIMIT :limit OFFSET :offset
        ";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    } elseif ($mode === 'sendnow') {
        $reminderID = $input['reminderID'] ?? null;
        if (!$reminderID) {
            echo json_encode(['status' => 'error', 'message' => 'Missing reminderID']);
            exit;
        }

        $sql = "
            SELECT 
                f.reminderID,
                i.invoice_id, 
                i.amount, 
                i.reference_no, 
                i.due_date,
                c.name AS customer_name, 
                c.email AS customer_email,
                p.email_subject, 
                p.email_body,
                p.plan_type
            FROM collection.follow f
            INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
            INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            INNER JOIN collection.collection_plan p ON f.planID = p.planID
            WHERE f.reminderID = :reminderID
              AND i.stat IN ('Pending', 'Partially Paid')
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':reminderID' => $reminderID]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$r) {
            echo json_encode(['status' => 'error', 'message' => 'Reminder not found or already processed']);
            exit;
        }

        $body = str_replace(
            ['{customerName}', '{invoiceNo}', '{amount}', '{dueDate}', '{companyName}'],
            [
                $r['customer_name'],
                $r['reference_no'],
                number_format($r['amount'], 2),
                date('F d, Y', strtotime($r['due_date'])),
                $companyName
            ],
            $r['email_body']
        );

        $sendResult = send_email_smtp(
            $r['customer_email'],
            $r['customer_name'],
            $r['email_subject'],
            nl2br($body)
        );

        if (!$sendResult['ok']) {
            echo json_encode(['status' => 'error', 'message' => 'Email failed: ' . $sendResult['error']]);
            exit;
        }

        $insertSql = "INSERT INTO collection.reminder (ReminderSent, Type, FollowID, Reply, Status, Archive) 
                      VALUES (NOW(), :type, :followID, '', 'Delivered', 'NO')";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            ':type'     => $r['plan_type'],
            ':followID' => $r['reminderID']
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => "Reminder sent to {$r['customer_name']}"
        ]);
        exit;
    } elseif ($mode === 'preview') {
        $reminderID = $input['reminderID'] ?? null;
        if (!$reminderID) {
            echo json_encode(['status' => 'error', 'message' => 'Missing reminderID']);
            exit;
        }

        $sql = "
            SELECT 
                i.reference_no,
                i.amount,
                i.due_date,
                c.name AS customer_name,
                c.email AS customer_email,
                p.email_subject,
                p.email_body,
                p.plan_type
            FROM collection.follow f
            INNER JOIN ar_ap.ar_invoices i ON f.InvoiceID = i.invoice_id
            INNER JOIN ar_ap.customers c ON i.customer_id = c.customer_id
            INNER JOIN collection.collection_plan p ON f.planID = p.planID
            WHERE f.reminderID = :reminderID
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':reminderID' => $reminderID]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$r) {
            echo json_encode(['status' => 'error', 'message' => 'Reminder not found']);
            exit;
        }


        $body = str_replace(
            ['{customerName}', '{invoiceNo}', '{amount}', '{dueDate}', '{companyName}'],
            [
                $r['customer_name'],
                $r['reference_no'],
                '₱' . number_format($r['amount'], 2),
                date('F d, Y', strtotime($r['due_date'])),
                $companyName
            ],
            $r['email_body']
        );

        $subject = str_replace(
            ['{customerName}', '{invoiceNo}', '{amount}', '{dueDate}', '{companyName}'],
            [
                $r['customer_name'],
                $r['reference_no'],
                '₱' . number_format($r['amount'], 2),
                date('F d, Y', strtotime($r['due_date'])),
                $companyName
            ],
            $r['email_subject']
        );

        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer' => $r['customer_name'],
                'invoice'  => $r['reference_no'],
                'amount'   => '₱' . number_format($r['amount'], 2),
                'due'      => date('F d, Y', strtotime($r['due_date'])),
                'subject'  => $subject,
                'body'     => nl2br($body),
                'plan'     => $r['plan_type']
            ]
        ]);
        exit;
    } elseif ($mode === 'plans') {
        $sql = "
            SELECT planID, plan, plan_type, email_subject, email_body,
                   remaining_days, details, status
            FROM collection.collection_plan
            WHERE Archive = 'NO'
            ORDER BY planID ASC
            LIMIT :limit OFFSET :offset
        ";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    } elseif ($mode === 'counting') {
        
        $sql_counting = "SELECT (SELECT COUNT(*) FROM collection.follow WHERE Archive = 'NO') AS total_reminders,
                         (SELECT COUNT(DISTINCT i.invoice_id) 
                          FROM ar_ap.ar_invoices i 
                          INNER JOIN collection.follow f ON f.InvoiceID = i.invoice_id 
                          WHERE i.stat = 'Pending' AND i.Archive = 'NO') AS pending_reminders,
                         (SELECT COUNT(DISTINCT i.invoice_id) 
                          FROM ar_ap.ar_invoices i 
                          INNER JOIN collection.follow f ON f.InvoiceID = i.invoice_id 
                          WHERE i.stat = 'Paid' AND i.Archive = 'NO') AS paid_reminders,
                         (SELECT COUNT(*) FROM collection.reminder WHERE Status = 'Failed' AND Archive = 'NO') AS failed_reminders";
        $result = $pdo->query($sql_counting);
        $counts = $result->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'counts' => [
                'totalReminder' => (int)($counts['total_reminders'] ?? 0),
                'paidReminder'  => (int)($counts['paid_reminders'] ?? 0),
                'pendingReminder' => (int)($counts['pending_reminders'] ?? 0),
                'failedReminder'  => (int)($counts['failed_reminders'] ?? 0)
            ]
        ]);
        exit;
    } else {
        echo json_encode(["message" => "Invalid mode"]);
        exit;
    }

  
    if (!empty($sql)) {
        $stmt = $pdo->prepare($sql);

        
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v);
            }
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total   = getTotalCount($pdo, $mode);
        $hasMore = ($page * $limit) < $total;
    } else {
        $rows = [];
        $total = 0;
        $hasMore = false;
    }

  
    if ($mode === 'log') {
        $data = array_map(function ($row) {
            return [
                'time' => $row['ReminderSent'],
                'type' => $row['Type'],
                'to'   => $row['customer'],
                'status'=> $row['Status']
            ];
        }, $rows);
    } elseif ($mode === 'plans') {
        $data = array_map(function($r){
            return [
                'planID'    => (int)$r['planID'],
                'plan'      => $r['plan'],
                'plan_type' => $r['plan_type'],
                'subject'   => $r['email_subject'] ?? '',
                'body'      => $r['email_body'] ?? '',
                'remaining' => $r['remaining_days'],
                'details'   => $r['details'],
                'status'    => $r['status'],
            ];
        }, $rows);
    } else {
      
        $data = [];
        foreach ($rows as $row) {
            $due = new DateTime($row['due_date']);
            $today = new DateTime();
            $daysOverdue = 0;
            $status = 'upcoming';
            if ($due < $today) {
                $daysOverdue = $today->diff($due)->days;
                $status = 'overdue';
            }

            $data[] = [
                'reference_no' => $row['reference_no'],
                'amount'       => $row['amount'],
                'due_date'     => $row['due_date'],
                'client'       => $row['client'],
                'mode'         => $row['plan_type'] ?? null,
                'days_overdue' => $daysOverdue,
                'status'       => $status,
                'reminderID'   => $row['reminderID'] ?? null
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'pagination' => [
            'page'    => $page,
            'limit'   => $limit,
            'total'   => $total,
            'hasMore' => $hasMore
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit();
}
