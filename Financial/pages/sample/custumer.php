<?php
$custumerID = '1';

include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

/**
 * Generates a unique reference number.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return string The unique reference number.
 */
function generateReferenceNo($pdo) {
    $prefix = 'INV-' . date('Ymd') . '-';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < 4; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    $referenceNo = $prefix . $randomString;

    // Check if reference number already exists
    $sqlCheck = "SELECT COUNT(*) FROM ar_invoices WHERE reference_no = :referenceNo";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':referenceNo', $referenceNo);
    $stmtCheck->execute();
    if ($stmtCheck->fetchColumn() > 0) {
        // If exists, generate a new one (recursive call)
        return generateReferenceNo($pdo);
    }
    return $referenceNo;
}

/**
 * Retrieves or creates an account ID in the chartofaccount table.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $accountName The name of the account (e.g., "Account Receivable", "Freight Revenue").
 * @param string $accountType The type of account (e.g., "Assets", "Revenue").
 * @return int The account ID.
 * @throws Exception If the account type is invalid or database error occurs.
 */
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

    // Check if account exists
    $sql = "SELECT accountID, accountCode FROM chartofaccount WHERE accountName = :accountName AND accounType = :accounType";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':accountName', $accountName, PDO::PARAM_STR);
    $stmt->bindParam(':accounType', $accountType, PDO::PARAM_STR);
    $stmt->execute();
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        return $account['accountID'];
    }

    // Account doesn't exist, create new one
    $sql = "SELECT accountCode FROM chartofaccount WHERE accounType = :accounType ORDER BY accountCode DESC LIMIT 1";
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

    // Insert new account
    $sql = "INSERT INTO chartofaccount (accountName, accounType, accountCode) VALUES (:accountName, :accounType, :accountCode)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':accountName', $accountName, PDO::PARAM_STR);
    $stmt->bindParam(':accounType', $accountType, PDO::PARAM_STR);
    $stmt->bindParam(':accountCode', $accountCode, PDO::PARAM_STR);
    $stmt->execute();

    return $pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert'])) {
    $invoiceDate = filter_input(INPUT_POST, 'invoiceDate', FILTER_SANITIZE_STRING);
    $dueDate = filter_input(INPUT_POST, 'dueDate', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (!$invoiceDate || !$dueDate || !$description || $amount === false) {
        $errorMessage = "All fields are required and amount must be a valid number.";
    } else {
        try {
            $pdo->beginTransaction();

            $referenceNo = generateReferenceNo($pdo);

            // Insert into ar_invoices table
            $sql = "INSERT INTO ar_invoices (customer_id, invoice_date, due_date, description, amount, reference_no, created_at)
                    VALUES (:custumerID, :invoiceDate, :dueDate, :description, :amount, :referenceNo, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':custumerID', $custumerID, PDO::PARAM_INT);
            $stmt->bindParam(':invoiceDate', $invoiceDate, PDO::PARAM_STR);
            $stmt->bindParam(':dueDate', $dueDate, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':referenceNo', $referenceNo, PDO::PARAM_STR);
            $stmt->execute();
            $invoiceID = $pdo->lastInsertId(); // Get the ID of the new invoice

            // Insert journal entries (debit/credit)
            $sqlEntries = "
                INSERT INTO entries (date, description, referenceType, createdBy, Archive)
                VALUES (NOW(), :description, :ref, :createdBy, 'NO')
            ";
            $stmtEntries = $pdo->prepare($sqlEntries);
            $stmtEntries->bindParam(':description', $description, PDO::PARAM_STR);
            $stmtEntries->bindValue(':ref', $referenceNo, PDO::PARAM_STR);
            $stmtEntries->bindValue(':createdBy', 'System', PDO::PARAM_STR);
            $stmtEntries->execute();
            $journalID = $pdo->lastInsertId();

            // Get or create account IDs
            $receivableAccountID = getOrCreateAccountID($pdo, 'Account Receivable', 'Assets');
            $revenueAccountID = getOrCreateAccountID($pdo, 'Freight Revenue', 'Revenue');

            // Insert debit entry (Account Receivable)
            $detailSqlDebit = "
                INSERT INTO details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $stmtDetailsDebit = $pdo->prepare($detailSqlDebit);
            $stmtDetailsDebit->bindParam(':journalID', $journalID, PDO::PARAM_INT);
            $stmtDetailsDebit->bindParam(':accountID', $receivableAccountID, PDO::PARAM_INT);
            $stmtDetailsDebit->bindParam(':debit', $amount, PDO::PARAM_STR);
            $stmtDetailsDebit->bindValue(':credit', 0, PDO::PARAM_STR);
            $stmtDetailsDebit->execute();

            // Insert credit entry (Freight Revenue)
            $detailSqlCredit = "
                INSERT INTO details (journalID, accountID, debit, credit, Archive)
                VALUES (:journalID, :accountID, :debit, :credit, 'NO')
            ";
            $stmtDetailsCredit = $pdo->prepare($detailSqlCredit);
            $stmtDetailsCredit->bindParam(':journalID', $journalID, PDO::PARAM_INT);
            $stmtDetailsCredit->bindParam(':accountID', $revenueAccountID, PDO::PARAM_INT);
            $stmtDetailsCredit->bindValue(':debit', 0, PDO::PARAM_STR);
            $stmtDetailsCredit->bindParam(':credit', $amount, PDO::PARAM_STR);
            $stmtDetailsCredit->execute();

            // Logic to create a SINGLE follow-up reminder
            $followUpMessage = "No automated reminders created.";
            try {
                // Fetch all active plans
                $sqlPlans = "SELECT planID, remaining_days FROM collection_plan WHERE status = 'Active' AND Archive = 'NO'";
                $stmtPlans = $pdo->prepare($sqlPlans);
                $stmtPlans->execute();
                $collectionPlans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

                if ($collectionPlans) {
                    $bestPlan = null;
                    $bestPlanRemainingDays = -1;

                    // Find the best plan to use for the reminder
                    foreach ($collectionPlans as $plan) {
                        $remainingDays = $plan['remaining_days'];
                        $calculatedFollowUpDate = date('Y-m-d', strtotime($dueDate . ' -' . $remainingDays . ' days'));
                        
                        // Check if the calculated follow-up date is between the invoice date and due date
                        if ($calculatedFollowUpDate > $invoiceDate) { 
                            // Prioritize the plan with the most remaining days
                            if ($remainingDays > $bestPlanRemainingDays) {
                                $bestPlanRemainingDays = $remainingDays;
                                $bestPlan = $plan;
                                $bestFollowUpDate = $calculatedFollowUpDate;
                            }
                        }
                    }

                    // If a suitable plan was found, create the single reminder
                    if ($bestPlan) {
                        $sqlFollow = "INSERT INTO follow (planID, InvoiceID, FollowUpDate, Contactinfo, Remarks, paymentstatus, Archive)
                                      VALUES (:planID, :invoiceID, :followUpDate, :contactInfo, :remarks, :paymentStatus, 'NO')";
                        $stmtFollow = $pdo->prepare($sqlFollow);
                        $stmtFollow->bindParam(':planID', $bestPlan['planID'], PDO::PARAM_INT);
                        $stmtFollow->bindParam(':invoiceID', $invoiceID, PDO::PARAM_INT);
                        $stmtFollow->bindParam(':followUpDate', $bestFollowUpDate, PDO::PARAM_STR);
                        $stmtFollow->bindValue(':contactInfo', 'Email', PDO::PARAM_STR); // Placeholder
                        $stmtFollow->bindValue(':remarks', 'To Be Sent', PDO::PARAM_STR);
                        $stmtFollow->bindValue(':paymentStatus', 'Not Paid', PDO::PARAM_STR);
                        $stmtFollow->execute();

                        $followUpMessage = "Successfully created a single automated follow-up reminder.";
                    } else {
                        $followUpMessage = "No suitable reminder plan was found for this invoice.";
                    }
                }
            } catch (PDOException $e) {
                // Do not roll back the whole transaction for this part, just append to error message
                $errorMessage .= " Error scheduling follow-up reminders: " . $e->getMessage();
            }

            // Commit the transaction if all queries were successful
            $pdo->commit();

            $successMessage = "✅ Invoice added successfully with Reference No: $referenceNo. <br>" . $followUpMessage;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = "❌ Database error: " . $e->getMessage();
            error_log("Create invoice error for reference_no #$referenceNo: " . $e->getMessage() . " | Query: $sql | Bindings: " . print_r($stmt->debugDumpParams(), true));
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "❌ General error: " . $e->getMessage();
            error_log("Create invoice error for reference_no #$referenceNo: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Freight Finance — Invoice Management</title>
    <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?>

    <div class="container mx-auto p-6 max-w-2xl">
        <h1 class="text-3xl font-bold text-center mb-6">Create Invoice</h1>

        <?php if ($successMessage): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $successMessage; ?></p>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $errorMessage; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="shadow-md rounded-lg p-6 form-group">
            <input type="hidden" name="insert" value="1">
            <div class="mb-4">
                <label for="invoiceDate" class="block text-sm font-medium text-gray-700">Invoice Date</label>
                <input type="date" id="invoiceDate" name="invoiceDate" required
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="mb-4">
                <label for="dueDate" class="block text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" id="dueDate" name="dueDate" required
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" name="description" required
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          rows="4"></textarea>
            </div>
            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" required
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Add Invoice
                </button>
            </div>
        </form>

        <div class="mt-8">
            <h2 class="text-2xl font-semibold mb-4">Recent Invoices</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full shadow-md rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference No</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        try {
                            $sql = "SELECT invoice_date, due_date, description, amount, reference_no 
                                    FROM ar_invoices 
                                    WHERE customer_id = :custumerID 
                                    ORDER BY created_at DESC 
                                    LIMIT 5";
                            $stmt = $pdo->prepare($sql);
                            $stmt->bindParam(':custumerID', $custumerID, PDO::PARAM_INT);
                            $stmt->execute();
                            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($invoices) {
                                foreach ($invoices as $invoice) {
                                    echo '<tr>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($invoice['invoice_date']) . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($invoice['due_date']) . '</td>';
                                    echo '<td class="px-6 py-4 text-sm text-gray-900">' . htmlspecialchars($invoice['description']) . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . number_format($invoice['amount'], 2) . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($invoice['reference_no']) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">No invoices found.</td></tr>';
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="5" class="px-6 py-4 text-sm text-red-500 text-center">Error fetching invoices: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="<?php echo '../../static/js/filter.js';?>"></script>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        themeToggle.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode', this.checked);
        });

        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const hamburger = document.getElementById('hamburger');
        const overlay = document.getElementById('overlay');

        // Sidebar toggle logic
        hamburger.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded'); 
            }
        });

        // Close sidebar on overlay click
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Dropdown toggle logic
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                const parentDropdown = this.closest('.dropdown');
                parentDropdown.classList.toggle('active');
            });
        });
    </script>
</body>
</html>