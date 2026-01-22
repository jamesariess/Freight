 <div class="mt-8 grid md:grid-cols-2 gap-6 mb-4">
            <!-- Bank Balances -->
            <div class="shadow-lg rounded-2xl p-6 table-section">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-lg">🏦 Bank Balances</h2>
                    <span class="text-xs text-gray-500" id="balanceUpdateTime">Updated <?php echo date("M d, Y"); ?></span>
                </div>
                <ul id="bankBalancesList">
                    <?php if (!empty($bankBalances)): ?>
                        <?php foreach ($bankBalances as $bank): ?>
                            <li class="flex justify-between items-center py-3 hover:bg-gray-100 text-blue-700 rounded-lg px-2 transition">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-full font-semibold">
                                        <?php echo strtoupper(substr($bank['bankName'], 0, 1)); ?>
                                    </div>
                                    <span class="font-medium"><?php echo htmlspecialchars($bank['bankName']); ?></span>
                                </div>
                                <span class="font-bold text-green-600">₱<?php echo number_format($bank['availableBalance'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-center py-4">No bank balances available.</li>
                    <?php endif; ?>
                </ul>
            </div> 

          
            <div class="shadow rounded-xl p-5 table-section">
                <h2 class="font-bold text-lg mb-3">🏦 Bank Details</h2>
                <table class="w-full text-sm border-collapse" id="bankDetailsTable">
                    <thead class="text-gray-600 border-b">
                        <tr>
                            <th class="py-2 px-3 text-left">Bank Name</th>
                            <th class="py-2 px-3 text-left">Account Number</th>
                            <th class="py-2 px-3 text-left">Account Name</th>
                            <th class="py-2 px-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($banks)): ?>
                            <?php foreach ($banks as $row): ?>
                                <tr 
                                    class="cursor-pointer hover:bg-gray-100 transition" 
                                    onclick="openBankModal(<?php echo $row['bankID']; ?>, '<?php echo htmlspecialchars($row['bankName']); ?>', '<?php echo htmlspecialchars($row['status']); ?>')"
                                >
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($row['bankName']); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($row['accountNo'] ?: 'N/A'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($row['accountName'] ?: 'N/A'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($row['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center p-4">No Records Found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>