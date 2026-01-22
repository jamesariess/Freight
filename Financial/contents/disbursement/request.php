<?php

try {
    $stmt = $pdo->query("
        SELECT c.Name, GROUP_CONCAT(DISTINCT ch.accountName SEPARATOR ', ') AS accountNames, c.Details
        FROM departmentbudget c
        LEFT JOIN costallocation ca ON c.Deptbudget = ca.Deptbudget
        LEFT JOIN chartofaccount ch ON ca.accountID = ch.accountID
        WHERE ca.Status = 'Activate' AND ch.Status = 'Active' AND c.DateValid = 2025
        GROUP BY c.Name
    ");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

 


        .container {
            padding: 1rem;
          
            margin: 0 auto;
        }

        .department-list {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            overflow: hidden;
            background: white;
            box-shadow: var(--shadow);
        }

        .department-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.2s;
        }

        .department-item:last-child {
            border-bottom: none;
        }

        .department-item:hover {
            background: #f9fafb;
        }

        .dept-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .dept-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .dept-summary {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .dept-details {
            font-size: 0.85rem;
            color: #374151;
            margin-top: 0.5rem;
        }

        .account-badges {
            margin-top: 0.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .account-badge {
            background: #f3f4f6;
            color: #374151;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .account-badge:hover {
            background: var(--primary-color);
            color: var(--text-light);
        }

 
        body.dark-mode .section-header h2 {
            color: var(--text-light);
        }

        body.dark-mode .section-header p {
            color: #9ca3af;
        }

        body.dark-mode .department-list {
            background: var(--dark-card);
            border-color: #3a4b6e;
        }

        body.dark-mode .department-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        body.dark-mode .dept-name {
            color: var(--text-light);
        }

        body.dark-mode .dept-summary,
        body.dark-mode .dept-details {
            color: #9ca3af;
        }

        body.dark-mode .account-badge {
            background: rgba(243, 244, 246, 0.1);
            color: #9ca3af;
        }

        body.dark-mode .account-badge:hover {
            background: var(--primary-color);
            color: var(--text-light);
        }

  .section-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .section-header p {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        

    </style>

  
    <div class="container">
        <div class="section-header">
            <h2>Request Type Categories</h2>
            <p>Select the department your request belongs to</p>
        </div>

        <div class="department-list">
            <?php foreach ($departments as $dept): ?>
                <?php
                $deptName = $dept['Name'];
                $iconColors = [
                    'Finance' => 'Money, funding & reporting',
                    'General Services' => 'Office & company-wide support',
                    'HR' => 'Employees & workforce management',
                    'Maintenance' => 'Fleet, equipment & facility upkeep',
                    'Operations' => 'Running freight & logistics activities'
                ];
                $summary = $iconColors[$deptName] ?? '';
                ?>
                <div class="department-item">
                    <div class="dept-header">
                        <span class="dept-name"><?php echo htmlspecialchars($deptName); ?></span>
                        <span class="dept-summary"><?php echo htmlspecialchars($summary); ?></span>
                    </div>
                    <div class="dept-details">
                        <?php echo htmlspecialchars($dept['Details']); ?>
                    </div>
                    <div class="account-badges">
                        <?php
                        if (!empty($dept['accountNames'])) {
                            $detailsArray = explode(', ', $dept['accountNames']);
                            foreach ($detailsArray as $detail) {
                                echo "<span class='account-badge'>" . trim(htmlspecialchars($detail)) . "</span>";
                            }
                        } else {
                            echo "<span class='account-badge'>No accounts assigned</span>";
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>