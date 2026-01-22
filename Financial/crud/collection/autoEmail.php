
<?php

include_once __DIR__ . '/../../utility/connn.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$debug_log = __DIR__ . '/send_reminders_debug.log';
function log_debug($msg) {
    global $debug_log;
    $t = date('Y-m-d H:i:s');
    file_put_contents($debug_log, "[$t] $msg\n", FILE_APPEND | LOCK_EX);
}

log_debug("=== Script START ===");


$companyName = "Slate Freight Finance";
$fromEmail   = "slatetransportsystem@gmail.com";
$fromName    = "Slate Finance Department";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer-master/src/Exception.php';


function alreadySentToday(PDO $pdo, $customerID, $planType) {
    $sql = "SELECT COUNT(*) FROM collection.reminder WHERE custumerID = :cid AND Type = :type AND DATE(ReminderSent) = CURDATE() AND Archive != 'YES'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $customerID, ':type' => $planType]);
    return ((int)$stmt->fetchColumn()) > 0;
}


function saveReminder(PDO $pdo, $customerID, $planType, $status) {
    $sql = "INSERT INTO collection.reminder (ReminderSent, Type, custumerID, Reply, Status, Archive) VALUES (NOW(), :type, :cid, '', :status, 'NO')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':type' => $planType, ':cid' => $customerID, ':status' => $status]);
}

function alreadySentAtHour(PDO $pdo, $customerID, $planType, $hour) {
    $sql = "SELECT COUNT(*) FROM collection.reminder WHERE custumerID = :cid AND Type = :type AND HOUR(ReminderSent) = :hour AND Archive != 'YES'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $customerID, ':type' => $planType, ':hour' => $hour]);
    return ((int)$stmt->fetchColumn()) > 0;
}


function runIfTimeSlot($pdo, $allowedHours, $description, $callback) {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $currentHour = (int)$now->format('H');
    $currentMinute = (int)$now->format('i');


    if (in_array($currentHour, $allowedHours) && $currentMinute < 5) {
        log_debug("=== $description @ {$currentHour}:00 START ===");
        $callback($pdo, $currentHour);
        log_debug("=== $description @ {$currentHour}:00 END ===");
    }
}

function send_email_smtp($toEmail, $toName, $subject, $htmlBody) {
    global $fromEmail, $fromName;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'slatetransportsystem@gmail.com';
            $mail->Password   = 'mfkkigrgxtoascov';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();
        log_debug("Email SENT to {$toName} <{$toEmail}> (subject: {$subject})");
        return ['ok' => true, 'error' => ''];
    } catch (Exception $e) {
        $err = $mail->ErrorInfo ?: $e->getMessage();
        log_debug("Email FAILED to {$toEmail}. Error: {$err}");
        return ['ok' => false, 'error' => $err];
    }
}


try {
    $beforeSql = "
        SELECT 
            f.FollowUpDate,
            p.planID,
            p.plan_type,
            p.email_subject,
            p.email_body,
            p.remaining_days,
            a.invoice_id,
            a.amount,
            a.due_date,
            a.reference_no,
            c.customer_id,
            c.name AS client_name,
            c.email AS client_email
        FROM collection.collection_plan p
        INNER JOIN collection.follow f ON p.planID = f.planID
        INNER JOIN ar_ap.ar_invoices a ON f.InvoiceID = a.invoice_id
        INNER JOIN ar_ap.customers c ON a.customer_id = c.customer_id
        WHERE f.FollowUpDate = CURDATE()
          AND p.remaining_days > 0
          AND (p.plan_type IS NULL OR p.plan_type != 'Manual')
        ORDER BY f.FollowUpDate ASC
    ";

    $stmt = $pdo->prepare($beforeSql);
    $stmt->execute();
    $beforeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    log_debug("Before-due reminders found: " . count($beforeRows));

    foreach ($beforeRows as $r) {
        $custID = $r['customer_id'];
        $planType = $r['plan_type'] ?? 'Automatic';

      
        if (alreadySentToday($pdo, $custID, $planType)) {
            log_debug("SKIP before-due: already sent today for customer {$custID} plan {$planType}");
            continue;
        }

     
        $body = str_replace(
            ['{customerName}', '{invoiceNo}', '{amount}', '{dueDate}', '{companyName}'],
            [
                $r['client_name'],
                $r['reference_no'],
                number_format($r['amount'], 2),
                date('F d, Y', strtotime($r['due_date'])),
                $companyName
            ],
            $r['email_body']
        );

 
        $res = send_email_smtp($r['client_email'], $r['client_name'], $r['email_subject'], nl2br($body));


        saveReminder($pdo, $custID, $planType, $res['ok'] ? 'Delivered' : 'Failed');
    }

} catch (Exception $e) {
    log_debug("ERROR in BEFORE-DUE block: " . $e->getMessage());
}


try {
  
    $pdo->exec("UPDATE ar_ap.ar_invoices SET stat = 'Overdue' WHERE due_date < CURDATE() AND stat = 'Pending'");
    log_debug("Marked invoices as Overdue where due_date < CURDATE() and stat was Pending.");

    $negPlans = $pdo->query("SELECT planID, plan_type, email_subject, email_body, remaining_days 
                              FROM collection.collection_plan 
                              WHERE remaining_days < 0 AND (plan_type IS NULL OR plan_type != 'Manual') 
                              ORDER BY remaining_days ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);
    log_debug("Negative plans found: " . count($negPlans));


    $rows = $pdo->query("
        SELECT 
            a.invoice_id,
            a.customer_id,
            a.amount,
            a.invoice_date,
            a.due_date,
            a.reference_no,
            a.stat,
            DATEDIFF(CURDATE(), a.due_date) AS days_late,
            c.name AS customer_name,
            c.email AS customer_email
        FROM ar_ap.ar_invoices a
        INNER JOIN ar_ap.customers c ON a.customer_id = c.customer_id
        WHERE a.stat IN ('Pending','Partially Paid','Overdue')
        ORDER BY a.customer_id, a.invoice_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);


    $customers = [];
    foreach ($rows as $r) {
        $cid = $r['customer_id'];
        if (!isset($customers[$cid])) {
            $customers[$cid] = [
                'customer_id' => $cid,
                'name' => $r['customer_name'],
                'email' => $r['customer_email'],
                'invoices' => [],
                'overdue_count' => 0,
                'has_partial' => false
            ];
        }
        $customers[$cid]['invoices'][] = $r;
        if ((int)$r['days_late'] > 0) $customers[$cid]['overdue_count']++;
        if ($r['stat'] === 'Partially Paid') $customers[$cid]['has_partial'] = true;
    }

    function chooseNegativePlan(array $negPlans, $overdueCount, $hasPartial) {
        if (empty($negPlans)) return null;
        $mostNeg = $negPlans[0];
        $second  = $negPlans[1] ?? $mostNeg;
        $least   = $negPlans[count($negPlans)-1];

        if ($overdueCount == 1) return $mostNeg;
        if ($hasPartial) return $second;
        if ($overdueCount >= 3) return $least;
        return $mostNeg;
    }

    foreach ($customers as $cid => $cust) {
        $overdueInvoices = array_filter($cust['invoices'], fn($inv) => (int)$inv['days_late'] > 0);
        if (empty($overdueInvoices)) continue;

        $chosenPlan = chooseNegativePlan($negPlans, $cust['overdue_count'], $cust['has_partial']);
        if (!$chosenPlan) { log_debug("No chosen plan for customer {$cid} — skipping."); continue; }

        $planType = $chosenPlan['plan_type'] ?? 'OverdueAutomatic';
        if (alreadySentToday($pdo, $cid, $planType)) { 
            log_debug("SKIP overdue send: already sent today for customer {$cid} plan {$planType}"); 
            continue; 
        }

  
        $soaHtml = "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                        <thead style='background:#eee;'>
                          <tr>
                            <th>Invoice No</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>";
        $totalAmount = 0;
        foreach ($overdueInvoices as $inv) {
            $soaHtml .= "<tr>
                          <td>{$inv['reference_no']}</td>
                          <td>" . date('F d, Y', strtotime($inv['invoice_date'])) . "</td>
                          <td>" . date('F d, Y', strtotime($inv['due_date'])) . "</td>
                          <td style='text-align:right;'>₱" . number_format($inv['amount'],2) . "</td>
                          <td>{$inv['stat']}</td>
                        </tr>";
            $totalAmount += (float)$inv['amount'];
        }
        $soaHtml .= "<tr>
                       <td colspan='3' style='text-align:right; font-weight:bold;'>Total</td>
                       <td style='text-align:right; font-weight:bold;'>₱" . number_format($totalAmount,2) . "</td>
                       <td></td>
                     </tr>";
        $soaHtml .= "</tbody></table>";


        $latestInv = $overdueInvoices[0];

        $body = str_replace(
            ['{customerName}', '{invoiceNo}', '{amount}', '{dueDate}', '{companyName}', '{SOA}'],
            [
                $cust['name'],
                $latestInv['reference_no'],
                number_format($totalAmount, 2),
                date('F d, Y', strtotime($latestInv['due_date'])),
                $companyName,
                $soaHtml
            ],
            $chosenPlan['email_body']
        );

        $subject = $chosenPlan['email_subject'] ?: "Overdue Notice - {$companyName}";
        $res = send_email_smtp($cust['email'], $cust['name'], $subject, $body);

        saveReminder($pdo, $cid, $planType, $res['ok'] ? 'Delivered' : 'Failed');
    }

} catch (Exception $e) {
    log_debug("ERROR in OVERDUE block: " . $e->getMessage());
}

log_debug("=== Script END ===");
?>

