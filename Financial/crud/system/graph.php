<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../../utility/connection.php';

try {
    // 1. Disbursement Graph Data
    $sql1 = "
        SELECT 
            e.date AS entry_date,
            r.Amount
        FROM disbursment.request r
        INNER JOIN budget.costallocation c ON r.allocationID = c.allocationID
        INNER JOIN ledger.chartofaccount ch ON c.accountID = ch.accountID
        INNER JOIN ledger.details d ON ch.accountID = d.accountID
        INNER JOIN ledger.entries e ON d.journalID = e.journalID 
        WHERE r.Archive = 'NO' 
          AND r.status = 'Paid'
        ORDER BY e.date
    ";
    $stmt1 = $pdo->query($sql1);
    $disbuere = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Accounts Payable (Paid vs Pending by Month)
    $sql2 = "
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS month_year,
            SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) AS Paid,
            SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) AS Pending
        FROM ar_ap.ap_bills
        WHERE Archive = 'NO'
        GROUP BY month_year
        ORDER BY month_year
    ";
    $stmt2 = $pdo->query($sql2);
    $Apgraph = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // 3. Department Budget Allocation
    $sql3 = "
        SELECT 
            c.accountName AS Name,
            SUM(a.Amount - a.usedAllocation) AS amount
        FROM budget.costallocation a
        JOIN ledger.chartofaccount c ON a.accountID = c.accountID
        WHERE c.Archive = 'NO'
        GROUP BY c.accountName
    ";
    $stmt3 = $pdo->query($sql3);
    $deptbud = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // 4. Follow-up Reminders (Today's Manual Follow-ups)
    $sql4 = "
        SELECT 
            a.reference_no,
            a.due_date,
            a.amount,
            c.name AS client,
            f.FollowUpDate AS due_date_followup,
            DATEDIFF(CURDATE(), f.FollowUpDate) AS days_overdue,
            'Manual' AS mode,
            f.reminderID
        FROM collection.follow f
        JOIN collection.collection_plan co ON f.planID = co.planID
        JOIN ar_ap.ar_invoices a ON f.InvoiceID = a.invoice_id
        JOIN ar_ap.customers c ON a.customer_id = c.customer_id
        WHERE co.plan_type != 'Automated'
          AND f.Archive = 'NO' 
          AND a.stat != 'Paid' 
          AND DATE(f.FollowUpDate) = CURDATE()
        LIMIT 10
    ";
    $stmt4 = $pdo->query($sql4);
    $data4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'disbuere' => $disbuere,
        'Apgraph' => $Apgraph,
        'deptbud' => $deptbud,
        'followups' => $data4
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>