<div class="table-section" id="fundsTableSection">
    <h3>Bank Ledger</h3>
    <table id="fundsTable" class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th>Transaction Date</th>
                <th>Bank Name</th>
                <th>Available Amount</th>
                <th>Withdrawn</th>
                <th>Transfer</th>
                <th>Reference</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($fundDetails)): ?>
                <?php foreach ($fundDetails as  $row): ?>
                    <tr>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['Date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['bankName'] ?? 'N/A'); ?></td>
                        <td style="color: green; font-weight: bold;">
                            <?php echo number_format($row['Amount'], 2); ?>
                        </td>
                        <td style="color: red; font-weight: bold;">
                            <?php echo number_format($row['UsedAmount'] ?? 0, 2); ?>
                        </td>
                        <td style="color: rgb(62, 17, 159); font-weight: bold;">
                            <?php echo number_format($row['Transfer'] ?? 0, 2); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['reference'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['Notes'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center p-4">No Records Found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>