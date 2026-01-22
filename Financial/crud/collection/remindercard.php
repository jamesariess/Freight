<?php
include_once('../../utility/connection.php');
include_once('../../utility/head.php');
try {
  
    $sql1 = "SELECT a.reference_no, a.amount, c.name, a.due_date, a.stat
             FROM collection.follow f
             INNER JOIN ar_ap.ar_invoices a ON f.invoiceID = a.invoice_id
             INNER JOIN ar_ap.customers c ON a.customer_id = c.customer_id
             WHERE f.Archive = 'NO'";

    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    $reminders = $stmt1->fetchAll(PDO::FETCH_ASSOC);

  
    $sql2 = "SELECT a.reference_no, a.amount, c.name, a.due_date, a.stat
             FROM collection.follow f
             INNER JOIN ar_ap.ar_invoices a ON f.invoiceID = a.invoice_id
             INNER JOIN ar_ap.customers c ON a.customer_id = c.customer_id
             WHERE f.Archive = 'NO' AND a.stat = 'Paid'";

    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $paid = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $sql3 = "SELECT a.reference_no, a.amount, c.name, a.due_date, a.stat
             FROM collection.follow f
             INNER JOIN ar_ap.ar_invoices a ON f.invoiceID = a.invoice_id
             INNER JOIN ar_ap.customers c ON a.customer_id = c.customer_id
             WHERE f.Archive = 'NO' AND a.stat = 'Pending'";
  $stmt3 = $pdo->prepare($sql3);
  $stmt3->execute();
  $pending = $stmt3->fetchAll(PDO::FETCH_ASSOC);

  $sql4 =" SELECT r.Status,r.issue,r.ReminderSent,a.reference_no,c.name,c.email,c.phone
  FROM collection.reminder r
  INNER JOIN collection.follow f ON r.followID = f.reminderID 
  INNER JOIN ar_ap.ar_invoices a ON f.invoiceID = a.invoice_id
  INNER JOIN ar_ap.customers c ON a.customer_id = c.customer_id
  WHERE r.Archive = 'NO' AND Status = 'Failed'
  ";

  $stmt4 = $pdo->prepare($sql4);
  $stmt4->execute();
  $failed = $stmt4 -> fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'success',
        'data'   => $reminders,  
        'data2'  => $paid,
        'data3'  =>$pending,
        'data4' =>$failed
        
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>