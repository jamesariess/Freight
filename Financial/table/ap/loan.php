<div class="table-section" id="invoiceTableSection">
        <h3>Loan Details</h3>
        <table id="employeesTable">
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Vendor Name</th>
                    <th>Loan Title</th>
                    <th>Loan Amount</th>
                    <th>Interest Rate</th>
                    <th>Paid Amount</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Payment Terms</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                <?php if (!empty($adjustReports)): ?>
                    <?php foreach ($adjustReports as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['LoanID']); ?></td>
                            <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['LoanTitle']); ?></td>
                            <td><?php echo htmlspecialchars($row['loanAmount']); ?></td>
                            <td><?php echo htmlspecialchars($row['interestRate']); ?>%</td>

                            <td><?php echo htmlspecialchars($row['paidAmount']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['startDate'])); ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['EndDate'])); ?></td>

                            <td><?php echo htmlspecialchars($row['PaymentTerms']); ?></td>
                            <td><?php echo htmlspecialchars($row['Notes']); ?></td>
                            
                            <td>
                                <a href="#" onclick="openViewModal(
                                    '<?php echo htmlspecialchars($row['LoanID'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['LoanTitle'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['loanAmount'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['interestRate'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['paidAmount'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['startDate'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['EndDate'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['PaymentTerms'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['Notes'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['vendor_name'], ENT_QUOTES); ?>'
                                ); return false;">👁️ View</a>
                                <a href="#" onclick="openUpdateModal(
                                    '<?php echo htmlspecialchars($row['LoanID'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['LoanTitle'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['loanAmount'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['interestRate'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['startDate'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['EndDate'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['PaymentTerms'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['Notes'], ENT_QUOTES); ?>'
                                ); return false;">✏️ Update</a>
                                <a href="#" onclick="openArchiveModal('<?php echo htmlspecialchars($row['LoanID'], ENT_QUOTES); ?>'); return false;">🗄️ Archive</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center p-4">No Records Found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'viewModal')">
        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative" onclick="event.stopPropagation()">
            <h2 class="text-lg font-bold mb-4">View Loan</h2>
            <button class="absolute top-2 right-2 text-gray-500 hover:text-black" onclick="closeModal('viewModal')">&times;</button>
            <div class="mb-4">
                <p><strong>Loan ID:</strong> <span id="viewLoanID"></span></p>
                <p><strong>Vendor Name:</strong> <span id="viewVendorID"></span></p>
                <p><strong>Loan Title:</strong> <span id="viewLoanTitle"></span></p>
                <p><strong>Loan Amount:</strong> <span id="viewLoanAmount"></span></p>
                <p><strong>Interest Rate:</strong> <span id="viewInterestRate"></span></p>
                <p><strong>Paid Amount:</strong> <span id="viewPaidAmount"></span></p>
                <p><strong>Start Date:</strong> <span id="viewStartDate"></span></p>
                <p><strong>End Date:</strong> <span id="viewEndDate"></span></p>
                <p><strong>Payment Terms:</strong> <span id="viewPaymentTerms"></span></p>
                <p><strong>Notes:</strong> <span id="viewNotes"></span></p>
                
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div id="updateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'updateModal')">
        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative" onclick="event.stopPropagation()">
            <h2 class="text-lg font-bold mb-4">Update Loan</h2>
            <button class="absolute top-2 right-2 text-gray-500 hover:text-black" onclick="closeModal('updateModal')">&times;</button>
            <form method="post">
                <input type="hidden" name="loan_id" id="updateLoanID">
                <div class="mb-4 form-group">
                    <label class="block text-gray-700">Loan Title:</label>
                    <input type="text" name="loanTitle" id="updateLoanTitle" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4 form-group">
                    <label class="block text-gray-700">Loan Amount:</label>
                    <input type="number" name="loanAmount" id="updateLoanAmount" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4    form-group">
                    <label class="block text-gray-700">Interest Rate:</label>
                    <input type="number" name="interestRate" id="updateInterestRate" class="w-full border border-gray-300 p-2 rounded" step="0.01" required>
                </div>

                <div class="mb-4 form-group">
                    <label class="block text-gray-700">Start Date:</label>
                    <input type="date" name="startDate" id="updateStartDate" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4 form-group">
                    <label class="block text-gray-700">End Date:</label>
                    <input type="date" name="endDate" id="updateEndDate" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4 form-group">
                    <label class="block text-gray-700">Payment Terms:</label>
                    <input type="number" name="paymentTerms" id="updatePaymentTerms" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4 form-group">
                    <label class="block text-gray-700">Notes:</label>
                    <input type="text" name="notes" id="updateNotes" class="w-full border border-gray-300 p-2 rounded">
                </div>
            
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400" onclick="closeModal('updateModal')">Cancel</button>
                    <button type="submit" name="update" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'archiveModal')">
        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative" onclick="event.stopPropagation()">
            <h2 class="text-lg font-bold mb-4">Archive Loan</h2>
            <button class="absolute top-2 right-2 text-gray-500 hover:text-black" onclick="closeModal('archiveModal')">&times;</button>
            <p>Are you sure you want to archive this Loan?</p>
            <form method="post">
                <input type="hidden" name="archive_collectionID" id="archiveLoanID">
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="submit" name="archive" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Archive</button>
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400" onclick="closeModal('archiveModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const modalOverlay = document.getElementById('modalOverlay');
        const cancelBtn = document.getElementById("cancelBtn");

        toggleFormBtn.addEventListener('click', () => {
            modalOverlay.style.display = 'flex';
        });
        cancelBtn.addEventListener('click', () => {
            modalOverlay.style.display = "none";
        });

        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                modalOverlay.style.display = 'none';
            }
        });

        function closeAllModals() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('updateModal').classList.add('hidden');
            document.getElementById('archiveModal').classList.add('hidden');
        }
        function formatDate(dateStr) {
    if (!dateStr) return '';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString(undefined, options);
}


function openViewModal(loanId, loanTitle, loanAmount, interestRate, paidAmount, startDate, endDate, paymentTerms, notes, vendorId) {
    closeAllModals();
    document.getElementById('viewLoanID').innerText = loanId;
    document.getElementById('viewLoanTitle').innerText = loanTitle;
    document.getElementById('viewLoanAmount').innerText = loanAmount;
    document.getElementById('viewInterestRate').innerText = interestRate + '%';
    document.getElementById('viewPaidAmount').innerText = paidAmount;
    document.getElementById('viewStartDate').innerText = formatDate(startDate);
    document.getElementById('viewEndDate').innerText = formatDate(endDate);
    document.getElementById('viewPaymentTerms').innerText = paymentTerms;
    document.getElementById('viewNotes').innerText = notes;
    document.getElementById('viewVendorID').innerText = vendorId;
    document.getElementById('viewModal').classList.remove('hidden');
}


        function openUpdateModal(loanId, loanTitle, loanAmount, interestRate, startDate, endDate, paymentTerms, notes) {
            closeAllModals();
            document.getElementById('updateLoanID').value = loanId;
            document.getElementById('updateLoanTitle').value = loanTitle;
            document.getElementById('updateLoanAmount').value = loanAmount;
            document.getElementById('updateInterestRate').value = interestRate;
            document.getElementById('updateStartDate').value = startDate;
            document.getElementById('updateEndDate').value = endDate;
            document.getElementById('updatePaymentTerms').value = paymentTerms;
            document.getElementById('updateNotes').value = notes;
    
            document.getElementById('updateModal').classList.remove('hidden');
        }

        function openArchiveModal(loanId) {
            closeAllModals();
            document.getElementById('archiveLoanID').value = loanId;
            document.getElementById('archiveModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function outsideClick(event, modalId) {
            if (event.target.id === modalId) {
                closeModal(modalId);
            }
        }
    </script>