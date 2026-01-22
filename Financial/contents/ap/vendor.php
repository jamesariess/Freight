<div class="container w-full">
    <div id="notificationContainer" class="mx-auto mt-4"></div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between  shadow-md rounded-2xl p-6 border border-gray-200 mb-4">
        <div>
            <h1 class="text-3xl font-bold  mb-6">Loan Billing & Payments</h1> 
        </div>
        <div class="mt-4 md:mt-0">
            <button id="toggleFormBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl shadow-md flex items-center gap-2 transition">
                <i class="fas fa-plus-circle"></i>
                Request Loan
            </button>
        </div>
    </div>
</div>
<div class="table-section" id="invoiceTableSection">
    <table id="employeesTable">
        <thead>
            <tr>
                <th class="px-6 py-3">Loan ID</th>
                <th class="px-6 py-3">Lender</th>
                <th class="px-6 py-3">Loan Type</th>
                <th class="px-6 py-3">Principal</th>
                <th class="px-6 py-3">Outstanding</th>
                <th class="px-6 py-3">Interest Rate</th>
                
                <th class="px-6 py-3">Due Date</th>
                <th class="px-6 py-3">Remarks</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y text-sm">
            <?php 
            $loans = getActiveLoans($pdo);
            if (empty($loans)) {
                echo '<tr><td colspan="11" class="px-6 py-4 text-center text-gray-500">No active loans available.</td></tr>';
            } else {
                foreach ($loans as $loan): 
                ?>
                <tr>
                    <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($loan['id']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($loan['lender']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($loan['title']); ?></td>
                    <td class="px-6 py-4"><?php echo $loan['principal']; ?></td>
                    <td class="px-6 py-4"><?php echo $loan['outstanding']; ?></td>
                    <td class="px-6 py-4"><?php echo $loan['rate']; ?></td>
                    
                    <td class="px-6 py-4"><?php echo $loan['dueDate']; ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($loan['remarks']); ?></td>
                    <td class="px-6 py-4">
                        <?php
                        $statusClass = '';
                        $statusText = $loan['status'];
                        switch ($statusText) {
                            case 'Overdue': $statusClass = 'bg-red-100 text-red-700'; break;
                            case 'Pending': $statusClass = 'bg-yellow-100 text-yellow-700'; break;
                            case 'Partially Paid': $statusClass = 'bg-blue-100 text-blue-700'; break;
                            case 'Paid': $statusClass = 'bg-green-100 text-green-700'; break;
                        }
                        ?>
                        <span class="px-2 py-1 text-xs rounded <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <?php
                            if (!empty($loan['pdf_filename'])) {
                                $fullPath = __DIR__ . '/../../' . $loan['pdf_filename']; 
                                $webPath = '../../' . $loan['pdf_filename'];
                                if (file_exists($fullPath)) {
                                    echo '<a href="' . htmlspecialchars($webPath) . '" 
                                           class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 mr-2 inline-block"
                                           download>
                                           Download
                                          </a>';
                                } else {
                                    echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                            No PDF
                                          </span>';
                                }
                            } else {
                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                        No PDF
                                      </span>';
                            }
                            ?>
                            <?php if ($loan['remarks'] === 'Draft'): ?>
                                <button onclick="openApprovalModal('<?php echo htmlspecialchars($loan['rawId']); ?>')" 
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 inline-block">
                                    Approve
                                </button>
                                <button onclick="rejectLoan('<?php echo htmlspecialchars($loan['rawId']); ?>')" 
                                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200 inline-block">
                                    Reject
                                </button>
                            <?php elseif ($loan['remarks'] === 'Approved'): ?>
                                <button onclick="openPaymentForm('<?php echo htmlspecialchars($loan['rawId']); ?>', <?php echo str_replace(['₱', ','], '', $loan['outstanding']); ?>)" 
                                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-200 inline-block">
                                    Manage
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach;
            } ?>
        </tbody>
    </table>
</div>

<div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class=" rounded-xl shadow-lg w-full max-w-md p-6">
        <h2 class="text-xl font-bold mb-4">Approve Loan</h2>
        <p class="text-gray-600 mb-4">Please upload the loan document (PDF or DOCS) to approve this loan.</p>
        <form id="approvalForm" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" id="approvalLoanId" name="loanId">
            <input type="hidden" name="action" value="approve_loan">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Upload Document</label>
                <input type="file" id="documentFile" name="document" accept=".pdf,.doc,.docx" class="w-full border rounded p-2" required>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeApprovalModal()" class="px-4 py-2 border rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Approve</button>
            </div>
        </form>
    </div>
</div>

<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class=" rounded-xl shadow-lg w-full max-w-2xl p-6">
        <h2 class="text-xl font-bold mb-4">Loan Management</h2>
        <div class="flex border-b mb-4">
            <button id="tabPayment" onclick="switchTab('payment')" class="px-4 py-2 font-medium border-b-2 border-indigo-600 text-indigo-600">Make Payment</button>
            <button id="tabHistory" onclick="switchTab('history')" class="px-4 py-2 font-medium text-gray-600 hover:text-indigo-600">Billing History</button>
        </div>

        <form id="paymentForm" class="space-y-4 form-group" data-tab="payment">
            <input type="hidden" id="loanId" name="loanId">
            <input type="hidden" name="action" value="submit_payment">
            <div class="grid grid-cols-2 gap-4 ">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Loan ID</label>
                    <input type="text" id="loanIdDisplay" class="w-full border rounded p-2 bg-gray-100" readonly>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Outstanding Balance</label>
                    <input type="text" id="balanceDisplay" class="w-full border rounded p-2 bg-gray-100" readonly>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Payment Amount (Monthly Due)</label>
                <input type="number" id="amount" name="amount" step="0.01" class="w-full border rounded p-2" placeholder="Monthly due amount" required>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Payment Method</label>
                <select id="method" name="method" class="w-full border rounded p-2" required>
                    <option value="">-- Select Method --</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Check">Check</option>
                    <option value="Cash">Cash</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Remarks</label>
                <textarea id="remarks" name="remarks" rows="2" class="w-full border rounded p-2"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closePaymentForm()" class="px-4 py-2 border rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit</button>
            </div>
        </form>

        <div id="billingHistory" class="hidden table-section" data-tab="history">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Amount</th>
                        <th class="px-4 py-2">Method</th>
                        <th class="px-4 py-2">Remarks</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay">
  <div class="form-collection" id="collectionform">
    <legend>Add Loan</legend>
    <form action="loan.php" method="post" class="form-group">
      <input type="hidden" id="RequestId" name="RequestId">

      <!-- Loan Info -->
      <div class="grid-container">
        <div class="form-group">
          <label for="loanTitle">Loan Title</label>
          <input type="text" id="loanTitle" name="loanTitle" required>
        </div>
        <div class="form-group">
          <label for="vendorName">Vendor Name</label>
          <select id="vendorName" name="vendorId" required>
            <option value="">--SELECT VENDOR--</option>
            <?php
              try {
                $sql = "SELECT vendor_id, vendor_name FROM vendor WHERE Status = 'Active' AND Archive='NO' ORDER BY vendor_name ASC";
                $stmt = $pdo->query($sql);
                $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($vendors as $vendor) {
                  echo "<option value='" . htmlspecialchars($vendor['vendor_id'], ENT_QUOTES) . "'>" . htmlspecialchars($vendor['vendor_name'], ENT_QUOTES) . "</option>";
                }
              } catch (PDOException $e) {
                echo "<option value=''>Error loading vendors</option>";
              }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label for="loanAmount">Loan Amount</label>
          <input type="number" id="loanAmount" name="loanAmount" required>
        </div>
      </div>

      <!-- Rates -->
      <div class="grid-container rates">
        <div class="form-group">
          <label for="interestRate">Interest Rate</label>
          <input type="number" id="interestRate" name="interestRate" step="0.01" required>
        </div>
        <div class="form-group">
          <label for="PenaltyRate">Penalty Interest Rate</label>
          <input type="number" id="PenaltyRate" name="PenaltyRate" step="0.01" required>
        </div>
        <div class="form-group">
          <label for="Disbursement">Loan Disbursement Date</label>
          <input type="date" id="Disbursement" name="Disbursement" required>
        </div>
        <div class="form-group">
          <label for="day">Payment Due Day</label>
          <input type="number" id="day" name="day" required>
        </div>
      </div>

  
      <div class="grid-container">
        <div class="form-group">
          <label for="Installment">Installment</label>
          <input type="number" id="Installment" name="Installment" required>
        </div>
        <div class="form-group">
          <label for="Collateral">Collateral</label>
          <input type="text" id="Collateral" name="Collateral">
        </div>
        <div class="form-group">
          <label for="Penalty">Penalty Details</label>
          <input type="text" id="Penalty" name="Penalty">
        </div>
      </div>

     
      <div class="form-group">
        <label for="Received">Received Via</label>
        <select id="Received" name="Received">
          <option value="">--Select Method</option>
          <option value="Cash">Cash</option>
          <option value="Check">Check</option>
          <option value="Bank Transfer">Bank Transfer</option>
        </select>
      </div>

      <!-- Purpose -->
      <div class="form-group">
        <label for="Purpose">Purpose</label>
        <input type="text" id="Purpose" name="Purpose">
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
        <button type="submit" name="create" class="btn btn-primary">Create Loan</button>
      </div>
    </form>
  </div>
</div>

<script>
    const modal = document.getElementById('paymentModal');
    const form = document.getElementById('paymentForm');
    const tabPayment = document.getElementById('tabPayment');
    const tabHistory = document.getElementById('tabHistory');
    const billingHistory = document.getElementById('billingHistory');
    const historyBody = document.getElementById('historyBody');
    const notificationContainer = document.getElementById('notificationContainer');
    const approvalModal = document.getElementById('approvalModal');
    const approvalForm = document.getElementById('approvalForm');


    function showNotification(message, isSuccess) {
        const bgClass = isSuccess ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
        const iconPath = isSuccess 
            ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'
            : 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z';
        const notification = `
            <div class="bg-${isSuccess ? 'green' : 'red'}-100 border-l-4 border-${isSuccess ? 'green' : 'red'}-500 text-${isSuccess ? 'green' : 'red'}-700 p-6 rounded-lg shadow-md" role="alert">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="${iconPath}" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-lg font-semibold">${message}</span>
                    </div>
                    <button type="button" class="text-${isSuccess ? 'green' : 'red'}-700 hover:text-${isSuccess ? 'green' : 'red'}-900" onclick="this.parentElement.parentElement.style.display='none';">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        `;
        notificationContainer.innerHTML = notification;
        setTimeout(() => {
            const notif = notificationContainer.querySelector('div');
            if (notif) notif.style.display = 'none';
        }, 5000);
    }

    function openApprovalModal(loanId) {
        document.getElementById('approvalLoanId').value = loanId;
        approvalModal.classList.remove('hidden');
        approvalModal.classList.add('flex');
    }

    function closeApprovalModal() {
        approvalModal.classList.add('hidden');
        approvalModal.classList.remove('flex');
        approvalForm.reset();
    }

    async function rejectLoan(loanId) {
        if (confirm('Are you sure you want to reject this loan?')) {
            try {
                const response = await fetch('../../crud/ap/loan2.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=reject_loan&loanId=${loanId}`
                });
                const text = await response.text();
                console.log('Reject response:', text);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const result = JSON.parse(text);
                showNotification(result.message, result.success);
                if (result.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                console.error('Error rejecting loan:', error);
                showNotification('Error rejecting loan: ' + error.message, false);
            }
        }
    }

    approvalForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(approvalForm);
        try {
            const response = await fetch('../../crud/ap/loan2.php', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            console.log('Approve response:', text);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const result = JSON.parse(text);
            showNotification(result.message, result.success);
            if (result.success) {
                closeApprovalModal();
                setTimeout(() => location.reload(), 2000);
            }
        } catch (error) {
            console.error('Error approving loan:', error);
            showNotification('Error approving loan: ' + error.message, false);
        }
    });

    async function openPaymentForm(loanId, balance) {
        try {
            const response = await fetch('../../crud/ap/loan2.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_loan_details&loanId=${loanId}`
            });
            const text = await response.text();
            console.log('Loan details response:', text);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const result = JSON.parse(text);

            if (result.success) {
                const loanData = result.data;
                document.getElementById('loanId').value = loanData.loanId;
                document.getElementById('loanIdDisplay').value = 'LN-' + loanData.loanId;
                document.getElementById('balanceDisplay').value = "₱" + parseFloat(loanData.outstanding).toLocaleString();
                document.getElementById('amount').value = parseFloat(loanData.amountPerMonth).toFixed(2);
            } else {
                showNotification(result.message, false);
                return;
            }
        } catch (error) {
            console.error('Error fetching loan details:', error);
            showNotification('Error fetching loan details: ' + error.message, false);
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        switchTab('payment');
    }

    function closePaymentForm() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form.reset();
    }

    function switchTab(tab) {
        if (tab === 'payment') {
            form.classList.remove('hidden');
            billingHistory.classList.add('hidden');
            tabPayment.classList.add('border-b-2', 'border-indigo-600', 'text-indigo-600');
            tabHistory.classList.remove('border-b-2', 'border-indigo-600', 'text-indigo-600');
        } else {
            form.classList.add('hidden');
            billingHistory.classList.remove('hidden');
            tabHistory.classList.add('border-b-2', 'border-indigo-600', 'text-indigo-600');
            tabPayment.classList.remove('border-b-2', 'border-indigo-600', 'text-indigo-600');
            loadHistory();
        }
    }

    async function loadHistory() {
        const loanId = document.getElementById('loanId').value;
        try {
            const response = await fetch('../../crud/ap/loan2.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_history&loanId=${loanId}`
            });
            const text = await response.text();
            console.log('History response:', text);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const result = JSON.parse(text);
            historyBody.innerHTML = '';
            if (!result.success) {
                showNotification(result.message, false);
                historyBody.innerHTML = `<tr><td colspan="5" class="px-4 py-2 text-center text-red-500">${result.message}</td></tr>`;
            } else if (result.data && result.data.length === 0) {
                historyBody.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No payment history available.</td></tr>';
            } else {
                result.data.forEach(entry => {
                    const row = `
                        <tr class="border-t">
                            <td class="px-4 py-2">${entry.payment_date}</td>
                            <td class="px-4 py-2">₱${parseFloat(entry.amount).toLocaleString()}</td>
                            <td class="px-4 py-2">${entry.method}</td>
                            <td class="px-4 py-2">${entry.remarks || ''}</td>
                            <td class="px-4 py-2"><span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Completed</span></td>
                        </tr>
                    `;
                    historyBody.insertAdjacentHTML('beforeend', row);
                });
            }
        } catch (error) {
            console.error('Error loading history:', error);
            showNotification('Error fetching history: ' + error.message, false);
            historyBody.innerHTML = `<tr><td colspan="5" class="px-4 py-2 text-center text-red-500">Error loading history: ${error.message}</td></tr>`;
        }
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            const response = await fetch('../../crud/ap/loan2.php', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            console.log('Payment response:', text);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const result = JSON.parse(text);
            showNotification(result.message, result.success);
            if (result.success) {
                closePaymentForm();
                setTimeout(() => location.reload(), 5000);
            }
        } catch (error) {
            console.error('Error submitting payment:', error);
            showNotification('Error submitting payment: ' + error.message, false);
        }
    });

    toggleFormBtn.addEventListener('click', () => {
        modalOverlay.style.display = 'flex';
    });

    cancelBtn.addEventListener('click', () => {
        modalOverlay.style.display = 'none';
    });

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
        }
    });

    approvalModal.addEventListener('click', (e) => {
        if (e.target === approvalModal) {
            closeApprovalModal();
        }
    });
</script>

