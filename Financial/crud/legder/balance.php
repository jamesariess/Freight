<?php
include_once __DIR__ . '/../../utility/connection.php';
date_default_timezone_set('Asia/Manila');
include_once('../../utility/head.php');

$successMessage = '';
$errorMessage = '';
$response = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = intval($_POST['year'] ?? date('Y'));
    $month = intval($_POST['month'] ?? date('n'));
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'close') {
            $checkStmt = $pdo->prepare("SELECT period_id, status FROM ledger.periods WHERE year = :year AND month = :month");
            $checkStmt->execute([':year' => $year, ':month' => $month]);
            $existingPeriod = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingPeriod) {
                $insertStmt = $pdo->prepare("INSERT INTO ledger.periods (year, month, status) VALUES (:year, :month, 'Closed')");
                $insertStmt->execute([':year' => $year, ':month' => $month]);
                $newPeriodId = $pdo->lastInsertId();

                $assignStmt = $pdo->prepare("
                    UPDATE ledger.entries 
                    SET periodID = :period_id 
                    WHERE YEAR(date) = :year 
                      AND MONTH(date) = :month 
                      AND (periodID IS NULL OR periodID = 0)
                ");
                $assignStmt->execute([':period_id' => $newPeriodId, ':year' => $year, ':month' => $month]);
                $affectedRows = $assignStmt->rowCount();

                addAuditLog($pdo, $user_name, $role, 'Close Period', 'Periods',
                    "Closed new accounting period #$newPeriodId for $year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . " and assigned $affectedRows entries."
                );
                addNotification($pdo, $user_id, 'Period Closed',
                    "Accounting period #$newPeriodId ($year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . ") closed.", 'fa-lock'
                );

                $periodId = $newPeriodId;
                $response['message'] = "✅ Accounting period closed successfully.";
            } else {
                if ($existingPeriod['status'] === 'Open') {
                    $updateStmt = $pdo->prepare("UPDATE ledger.periods SET status = 'Closed' WHERE period_id = :period_id");
                    $updateStmt->execute([':period_id' => $existingPeriod['period_id']]);
                    $periodId = $existingPeriod['period_id'];

                    $assignStmt = $pdo->prepare("
                        UPDATE ledger.entries 
                        SET periodID = :period_id 
                        WHERE YEAR(date) = :year 
                          AND MONTH(date) = :month 
                          AND (periodID IS NULL OR periodID = 0)
                    ");
                    $assignStmt->execute([':period_id' => $periodId, ':year' => $year, ':month' => $month]);
                    $affectedRows = $assignStmt->rowCount();

                    addAuditLog($pdo, $user_name, $role, 'Close Period', 'Periods',
                        "Closed accounting period #$periodId for $year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . " and assigned $affectedRows entries."
                    );
                    addNotification($pdo, $user_id, 'Period Closed',
                        "Accounting period #$periodId ($year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . ") closed.", 'fa-lock'
                    );

                    $response['message'] = "✅ Accounting period closed successfully.";
                } else {
                    $response['error'] = "❌ Period is already closed or locked.";
                }
            }

            if (isset($periodId)) {
                try {
                    $acctStmt = $pdo->prepare("
                        SELECT c.accountID, c.accountName, LOWER(c.accounType) AS acctype,
                               COALESCE(SUM(d.debit), 0) AS total_debit,
                               COALESCE(SUM(d.credit), 0) AS total_credit
                        FROM ledger.details d
                        JOIN ledger.chartofaccount c ON d.accountID = c.accountID
                        JOIN ledger.entries e ON d.journalID = e.journalID
                        WHERE e.periodID = :period_id
                          AND LOWER(c.accounType) IN ('revenue','income','expense','expenses')
                        GROUP BY c.accountID, c.accountName, c.accounType
                    ");
                    $acctStmt->execute([':period_id' => $periodId]);
                    $acctRows = $acctStmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($acctRows && count($acctRows) > 0) {
                        $revenueLines = [];
                        $expenseLines = [];
                        $totalRevenue = 0.0;
                        $totalExpenses = 0.0;

                        foreach ($acctRows as $r) {
                            $atype = $r['acctype'];
                            $td = (float)$r['total_debit'];
                            $tc = (float)$r['total_credit'];

                            if (strpos($atype, 'rev') !== false || strpos($atype, 'inc') !== false) {
                                $net = $tc - $td;
                                if ($net > 0) {
                                    $revenueLines[] = ['accountID' => $r['accountID'], 'debit' => $net, 'credit' => 0];
                                    $totalRevenue += $net;
                                }
                            } else {
                                $net = $td - $tc;
                                if ($net > 0) {
                                    $expenseLines[] = ['accountID' => $r['accountID'], 'debit' => 0, 'credit' => $net];
                                    $totalExpenses += $net;
                                }
                            }
                        }

                        $netIncome = $totalRevenue - $totalExpenses;

                        $ownerStmt = $pdo->prepare("
                            SELECT accountID FROM ledger.chartofaccount
                            WHERE LOWER(accounType) IN ('equity','owner equity','capital')
                              AND (LOWER(accountName) LIKE '%owner%' OR LOWER(accountName) LIKE '%retain%' OR LOWER(accountName) LIKE '%capital%')
                            LIMIT 1
                        ");
                        $ownerStmt->execute();
                        $ownerRow = $ownerStmt->fetch(PDO::FETCH_ASSOC);

                        if ($ownerRow) {
                            $ownerAccountID = $ownerRow['accountID'];
                        } else {
                            $insOwner = $pdo->prepare("
                                INSERT INTO ledger.chartofaccount (accountCode, accountName, accounType, Archive, status, created)
                                VALUES ('EQ0001', 'Owner\'s Capital', 'Equity', 'NO', 'Active', NOW())
                            ");
                            $insOwner->execute();
                            $ownerAccountID = $pdo->lastInsertId();
                        }

                        $pdo->beginTransaction();
                        $desc = "Closing Entry - Period $year-" . str_pad($month, 2, '0', STR_PAD_LEFT);
                        $insJournal = $pdo->prepare("
                            INSERT INTO ledger.entries (date, description, referenceType, createdBy, Archive, status, periodID, posted_by, posted_at)
                            VALUES (NOW(), :desc, 'Closing', :createdBy, 'NO', 'Posted', :periodID, :posted_by, NOW())
                        ");
                        $insJournal->execute([
                            ':desc' => $desc,
                            ':createdBy' => $user_name,
                            ':periodID' => $periodId,
                            ':posted_by' => $user_name
                        ]);
                        $journalID = $pdo->lastInsertId();

                        $insDetail = $pdo->prepare("INSERT INTO ledger.details (journalID, accountID, debit, credit) VALUES (?, ?, ?, ?)");
                        foreach ($revenueLines as $rl) {
                            $insDetail->execute([$journalID, $rl['accountID'], $rl['debit'], $rl['credit']]);
                        }
                        foreach ($expenseLines as $el) {
                            $insDetail->execute([$journalID, $el['accountID'], $el['debit'], $el['credit']]);
                        }

                        if (abs($netIncome) > 0.0001) {
                            if ($netIncome > 0) {
                                $insDetail->execute([$journalID, $ownerAccountID, 0, $netIncome]);
                            } else {
                                $insDetail->execute([$journalID, $ownerAccountID, abs($netIncome), 0]);
                            }
                        }

                        $pdo->commit();

                        addAuditLog($pdo, $user_name, $role, 'Auto Close', 'Journal Entry',
                            "Auto-closing journal ID $journalID created for period $periodId with net income $netIncome"
                        );
                        addNotification($pdo, $user_id, 'Auto Close',
                            "Auto-closing journal created for period $periodId.", 'fa-lock'
                        );

                        $response['auto_close'] = "Auto-closing journal created successfully.";
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Auto-close error: " . $e->getMessage());
                }
            }
        } elseif ($action === 'reopen') {
            $checkStmt = $pdo->prepare("SELECT period_id FROM ledger.periods WHERE year = :year AND month = :month AND status = 'Closed'");
            $checkStmt->execute([':year' => $year, ':month' => $month]);
            $closedPeriod = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($closedPeriod) {
                $updateStmt = $pdo->prepare("UPDATE ledger.periods SET status = 'Open' WHERE period_id = :period_id");
                $updateStmt->execute([':period_id' => $closedPeriod['period_id']]);

                addAuditLog($pdo, $user_name, $role, 'Reopen Period', 'Periods',
                    "Reopened accounting period #{$closedPeriod['period_id']} for $year-" . str_pad($month, 2, '0', STR_PAD_LEFT)
                );
                addNotification($pdo, $user_id, 'Period Reopened',
                    "Accounting period #{$closedPeriod['period_id']} reopened.", 'fa-unlock'
                );

                $response['message'] = "✅ Accounting period reopened successfully.";
            } else {
                $response['error'] = "❌ No closed period found.";
            }
        }
    } catch (Exception $e) {
        $response['error'] = "❌ Error processing period: " . $e->getMessage();
        error_log("Period processing error: " . $e->getMessage());
    }
}

echo json_encode($response);

function fmtMoney($centsOrFloat) {
    if (is_int($centsOrFloat)) {
        $v = $centsOrFloat / 100;
    } else {
        $v = (float)$centsOrFloat;
    }
    return '₱' . number_format($v, 2);
}

function safe($s) { return htmlspecialchars((string)$s); }

try {
    $periodsStmt = $pdo->query("SELECT period_id, year, month, status FROM ledger.periods ORDER BY year DESC, month DESC");
    $periods = $periodsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $periods = [];
    $errorMessage = "❌ Error fetching periods: " . $e->getMessage();
    error_log("Error fetching periods: " . $e->getMessage());
}

$selectedPeriodId = isset($_POST['period_id']) ? (int)$_POST['period_id'] : (isset($_GET['period_id']) ? (int)$_GET['period_id'] : null);
$selectedAccountName = isset($_POST['accountName']) ? trim($_POST['accountName']) : (isset($_GET['accountName']) ? trim($_GET['accountName']) : 'all');

$periodWhere = '';
$params = [];
if ($selectedPeriodId) {
    $periodWhere = ' WHERE e.periodID = :period_id ';
    $params[':period_id'] = $selectedPeriodId;
}

try {
    $sqlAccounts = "SELECT DISTINCT c.accountName
                    FROM ledger.chartofaccount c
                    JOIN ledger.details d ON c.accountID = d.accountID
                    JOIN ledger.entries e ON d.journalID = e.journalID";
    if ($selectedPeriodId) $sqlAccounts .= " WHERE e.periodID = :period_id ";
    $sqlAccounts .= " ORDER BY c.accountName";
    $stmt = $pdo->prepare($sqlAccounts);
    if ($selectedPeriodId) $stmt->bindValue(':period_id', $selectedPeriodId, PDO::PARAM_INT);
    $stmt->execute();
    $accountNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $accountNames = [];
    $errorMessage = "❌ Error fetching account names: " . $e->getMessage();
    error_log("Error fetching account names: " . $e->getMessage());
}

$accountFilterSql = '';
if ($selectedAccountName && $selectedAccountName !== 'all') {
    $accountFilterSql = ' AND c.accountName = :accountName ';
    $params[':accountName'] = $selectedAccountName;
}

try {
    $sqlJournal = "SELECT
        jd.entriesID,
        jd.journalID,
        c.accountName,
        c.accountID,
        jd.debit,
        jd.credit,
        c.accounType AS accountType,
        e.date
    FROM ledger.details jd
    JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
    JOIN ledger.entries e ON jd.journalID = e.journalID
    WHERE e.status = 'Posted'";
    if ($selectedPeriodId) {
        $sqlJournal .= " AND e.periodID = :period_id ";
    }
    if ($selectedAccountName && $selectedAccountName !== 'all') {
        $sqlJournal .= $selectedPeriodId ? ' AND ' : ' WHERE ';
        $sqlJournal .= " c.accountName = :accountName ";
    }
    $sqlJournal .= " ORDER BY c.accountID, e.date, jd.entriesID LIMIT 10";

    $stmt = $pdo->prepare($sqlJournal);
    if ($selectedPeriodId) $stmt->bindValue(':period_id', $selectedPeriodId, PDO::PARAM_INT);
    if ($selectedAccountName && $selectedAccountName !== 'all') $stmt->bindValue(':accountName', $selectedAccountName);
    $stmt->execute();
    $journalDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $journalDetails = [];
    $errorMessage = "❌ Error fetching journal details: " . $e->getMessage();
    error_log("Error fetching journal details: " . $e->getMessage());
}

try {
    $sqlTB = "SELECT c.accountID, c.accountName, c.accounType,
                     SUM(jd.debit) AS total_debit, SUM(jd.credit) AS total_credit
              FROM ledger.details jd
              JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
              JOIN ledger.entries e ON jd.journalID = e.journalID
              WHERE e.status = 'Posted'";
    if ($selectedPeriodId) {
        $sqlTB .= " AND e.periodID = :period_id ";
    }
    $sqlTB .= " GROUP BY c.accountName, c.accounType
                ORDER BY c.accountName";
    $stmt = $pdo->prepare($sqlTB);
    if ($selectedPeriodId) $stmt->bindValue(':period_id', $selectedPeriodId, PDO::PARAM_INT);
    $stmt->execute();
    $tbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tbRows = [];
    $errorMessage = "❌ Error fetching trial balance: " . $e->getMessage();
    error_log("Error fetching trial balance: " . $e->getMessage());
}

try {
    $sqlIS = "SELECT c.accountID, c.accountName, LOWER(c.accounType) as acctype,
                     SUM(jd.debit) AS total_debit, SUM(jd.credit) AS total_credit
              FROM ledger.details jd
              JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
              JOIN ledger.entries e ON jd.journalID = e.journalID
              WHERE LOWER(c.accounType) IN ('revenue','income','expense','expenses')
                AND e.status = 'Posted'
                AND (e.referenceType IS NULL OR e.referenceType != 'Closing')";
    if ($selectedPeriodId) $sqlIS .= " AND e.periodID = :period_id";
    $sqlIS .= " GROUP BY c.accountName, c.accounType ORDER BY c.accounType, c.accountName";

    $stmt = $pdo->prepare($sqlIS);
    if ($selectedPeriodId) $stmt->bindValue(':period_id', $selectedPeriodId);
    $stmt->execute();
    $isRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $isRows = [];
}
try {
    $sqlBS = "SELECT c.accountID, c.accountName, LOWER(c.accounType) as acctype,
                     SUM(jd.debit) AS total_debit, SUM(jd.credit) AS total_credit
              FROM ledger.details jd
              JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
              JOIN ledger.entries e ON jd.journalID = e.journalID
              WHERE LOWER(c.accounType) IN ('assets','asset','liabilities','liability','equity','owner equity')
                AND e.status = 'Posted'";
    if ($selectedPeriodId) $sqlBS .= " AND e.periodID = :period_id";
    $sqlBS .= " GROUP BY c.accountName, c.accounType
                ORDER BY FIELD(LOWER(c.accounType),'assets','asset','liabilities','liability','equity','owner equity'), c.accountName";

    $stmt = $pdo->prepare($sqlBS);
    if ($selectedPeriodId) $stmt->bindValue(':period_id', $selectedPeriodId);
    $stmt->execute();
    $bsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bsRows = [];
}
$totalAccounts = 0;
$totalDebitBalance = 0;
$totalCreditBalance = 0;
foreach ($tbRows as $r) {
    $totalAccounts++;
    $acctType = strtolower(trim($r['accounType']));
    $debit = (float)$r['total_debit'];
    $credit = (float)$r['total_credit'];
    if (in_array($acctType, ['assets','asset','expenses','expense'])) {
        $net = $debit - $credit;
        if ($net >= 0) $totalDebitBalance += $net;
        else $totalCreditBalance += abs($net);
    } else {
        $net = $credit - $debit;
        if ($net >= 0) $totalCreditBalance += $net;
        else $totalDebitBalance += abs($net);
    }
}

$selectedPeriodStatus = null;
if ($selectedPeriodId) {
    foreach ($periods as $p) {
        if ((int)$p['period_id'] === (int)$selectedPeriodId) {
            $selectedPeriodStatus = $p['status'];
            $selectedPeriodLabel = $p['year'] . '-' . str_pad($p['month'], 2, '0', STR_PAD_LEFT);
            break;
        }
    }
} else {
    $selectedPeriodLabel = 'All Periods';
}

$totalRevenue = 0; $totalExpenses = 0;
foreach ($isRows as $r) {
    $debit = (float)$r['total_debit'];
    $credit = (float)$r['total_credit'];
    $atype = strtolower($r['acctype']);
    if (strpos($atype, 'rev') !== false || strpos($atype, 'inc') !== false) {
        $totalRevenue += ($credit - $debit);
    } else {
        $totalExpenses += ($debit - $credit);
    }
}
$netIncome = $totalRevenue - $totalExpenses;


$bsAssets = 0; $bsLiabilities = 0; $bsEquity = 0;
foreach ($bsRows as $r) {
    $debit = (float)$r['total_debit'];
    $credit = (float)$r['total_credit'];
    $atype = strtolower($r['acctype']);
    if (strpos($atype, 'asset') !== false) {
        $bsAssets += ($debit - $credit);
    } elseif (strpos($atype, 'liabil') !== false) {
        $bsLiabilities += ($credit - $debit);
    } else {
        $bsEquity += ($credit - $debit);
    }
}
$bsEquity += $netIncome;
?><?php if ($selectedPeriodStatus === 'Closed' && abs($netIncome) < 0.01): ?>
  <div class="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-lg text-sm mb-4">
    <strong>Period Closed:</strong> All revenue and expense accounts are zeroed. 
    Pre-closing Net Income was: <strong><?= fmtMoney($preNetIncome ?? $netIncome) ?></strong>
  </div>
<?php endif; ?>