<style>
.tooltip {
  @apply absolute bottom-10 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition duration-200 whitespace-nowrap;
}
  h1 { color: #0a2d64; }
        button {
            background-color: #0a2d64;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover { background-color: #143c7d; }
        .msg {
            margin-top: 20px;
            font-size: 15px;
            color: #333;
        }
</style>


    <form method="post">
        <button type="submit" name="run_now">🚀 Check Email</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_now'])) {
        include '../../crud/ap/invoice.php';
    }
    ?>
<div class="table-section" id="invoiceTableSection">


  <!-- Pagination Controls Top -->
  <div class="flex justify-between items-center mb-4 form-group">
    <div class="flex items-center space-x-2">
      <label for="rowsPerPage" class="text-sm ">Rows per page:</label>
      <select id="rowsPerPage" onchange="changeLimit(this.value)" class="border border-gray-300 p-1 rounded text-sm">
        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
      </select>
    </div> 
    <div class="text-sm" >
      Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> entries
    </div>
  </div>

  <div class="overflow-x-auto">
    <table id="employeesTable" class="min-w-full text-sm 0 border-collapse">
      <thead>
        <tr>
          <th class="p-3 text-left">Account Number</th>
          <th class="p-3 text-left">Vendor Name</th>
          <th class="p-3 text-left">Invoice No.</th>
          <th class="p-3 text-left">Bill Date</th>
          <th class="p-3 text-left">Due Date</th>
          <th class="p-3 text-left">Description</th>
          <th class="p-3 text-left">Amount</th>
          <th class="p-3 text-left">Payment Status</th>
          <th class="p-3 text-left">Received At</th>
          <th class="p-3 text-left">Invoice Image</th>
          <th class="p-3 text-left">Action</th>
        </tr>
      </thead>
      <tbody id="employeesTableBody">
        <?php if (!empty($adjustReports)): ?>
          <?php foreach ($adjustReports as $row): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-3 border-t border-gray-300"><?= htmlspecialchars($row['account_number']); ?></td>
              <td class="p-3 border-t border-gray-300"><?= htmlspecialchars($row['vendor_name']); ?></td>
              <td class="p-3 border-t border-gray-300"><?= htmlspecialchars($row['reference_no']); ?></td>
              <td class="p-3 border-t border-gray-300"><?= date('F j, Y', strtotime($row['bill_date'])); ?></td>
              <td class="p-3 border-t border-gray-300"><?= date('F j, Y', strtotime($row['due_date'])); ?></td>
              <td class="p-3 border-t border-gray-300"><?= htmlspecialchars($row['description']); ?></td>
              <td class="p-3 border-t border-gray-300 font-semibold text-green-600">₱<?= number_format($row['amount'], 2); ?></td>
              <td class="p-3 border-t border-gray-300"><?= htmlspecialchars($row['status']); ?></td>
              <td class="p-3 border-t border-gray-300"><?= date('F j, Y g:i A', strtotime($row['created_at'])); ?></td>
              <td class="p-3 border-t border-gray-300">
                <?php if (!empty($row['file_path'])): ?>
                  <a href="<?=  htmlspecialchars($row['file_path']); ?>" target="_blank">
                    <img src="<?=  htmlspecialchars($row['file_path']); ?>" alt="Invoice" class="w-12 h-12 object-cover rounded border">
                  </a>
                <?php else: ?>
                  <span class="text-gray-500 text-xs">No Image</span>
                <?php endif; ?>
              </td>
              <td class="flex items-center justify-center gap-2">
                <button onclick="openViewModal(
                  '<?= $row['bill_id'] ?>',
                  '<?= addslashes(htmlspecialchars($row['vendor_name'])) ?>',
                  '<?= $row['account_number'] ?>',
                  '<?= $row['reference_no'] ?>',
                  '<?= $row['bill_date'] ?>',
                  '<?= $row['due_date'] ?>',
                  '<?= addslashes(htmlspecialchars($row['description'])) ?>',
                  '<?= $row['amount'] ?>',
                  '<?= $row['status'] ?>',
                  '<?= $row['created_at'] ?>',
                  '<?= !empty($row['file_path']) ?  htmlspecialchars($row['file_path']) : '' ?>'
                )"  class="relative group p-2.5 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition" 
                   title="View Bill"> 
                    <i class="fa-solid fa-eye"></i>
                </button>

                <button onclick="openUpdateModal(
                  '<?= $row['bill_id'] ?>',
                  '<?= addslashes(htmlspecialchars($row['description'])) ?>',
                  '<?= $row['amount'] ?>',
                  '<?= $row['due_date'] ?>',
                  '<?= $row['status'] ?>'
                )"   class="relative group p-2.5 rounded-lg bg-green-500 text-white hover:bg-green-600 transition" 
                    title="Update Bill"><i class="fa-solid fa-pen-to-square"></i></button>

                <button onclick="openArchiveModal('<?= $row['bill_id'] ?>')"
                class="relative group p-2.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition" 
                title="Archive Bill">
               <i class="fa-solid fa-box-archive"></i></button>


                <button onclick="openRequestPaymentModal(
                  '<?= $row['bill_id'] ?>',
                  '<?= addslashes(htmlspecialchars($row['vendor_name'])) ?>',
                  '<?= $row['reference_no'] ?>',
                  '<?= $row['amount'] ?>',
                  '<?= $row['due_date'] ?>',
                  '<?= addslashes(htmlspecialchars($row['description'])) ?>',
                  '<?= !empty($row['file_path']) ? htmlspecialchars($row['file_path']) : '' ?>'
                )" class="relative group p-2.5 rounded-lg bg-purple-500 text-white hover:bg-purple-600 transition" 
                title="Request Payment">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="11" class="p-4 text-center text-gray-500">No Records Found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>


  <div class="flex justify-between items-center mt-4">
    <div class="text-sm ">
      Page <?= $page ?> of <?= $totalPages ?>
    </div>
    <div class="flex space-x-1">
      <?php if ($page > 1): ?>
        <a href="<?= buildPaginatedUrl($page - 1) ?>" class="px-3 py-1 bg-gray-200  rounded hover:bg-gray-300 text-sm">« Previous</a>
      <?php else: ?>
        <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded text-sm cursor-not-allowed">« Previous</span>
      <?php endif; ?>

      <?php 
      $startPage = max(1, $page - 2);
      $endPage = min($totalPages, $startPage + 4);
      for ($i = $startPage; $i <= $endPage; $i++): 
      ?>
        <?php if ($i == $page): ?>
          <span class="px-3 py-1 bg-blue-500 text-white rounded text-sm"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= buildPaginatedUrl($i) ?>" class="px-3 py-1 bg-gray-200  rounded hover:bg-gray-300 text-sm"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="<?= buildPaginatedUrl($page + 1) ?>" class="px-3 py-1 bg-gray-200  rounded hover:bg-gray-300 text-sm">Next »</a>
      <?php else: ?>
        <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded text-sm cursor-not-allowed">Next »</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- 🔹 VIEW MODAL -->
<div id="viewModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'viewModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">📄 Invoice Details</h2>
      <a class="text-gray-400 hover: text-2xl font-bold cursor-pointer" onclick="closeModal('viewModal')">&times;</a>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm ">
      <p class="font-semibold text-gray-600">Bill ID:</p><p id="vBillID"></p>
      <p class="font-semibold text-gray-600">Vendor:</p><p id="vVendor"></p>
      <p class="font-semibold text-gray-600">Account #:</p><p id="vAccount"></p>
      <p class="font-semibold text-gray-600">Invoice No.:</p><p id="vRef"></p>
      <p class="font-semibold text-gray-600">Bill Date:</p><p id="vBillDate"></p>
      <p class="font-semibold text-gray-600">Due Date:</p><p id="vDueDate"></p>
      <p class="font-semibold text-gray-600">Description:</p><p id="vDesc"></p>
      <p class="font-semibold text-gray-600">Amount:</p><p id="vAmount" class="text-green-600 font-semibold"></p>
      <p class="font-semibold text-gray-600">Status:</p><p id="vStatus"></p>
      <p class="font-semibold text-gray-600">Received:</p><p id="vCreated"></p>
    </div>

    <div id="imageContainer" class="mt-4 hidden">
      <p class="text-gray-600 font-semibold mb-2">Attached Bill Image:</p>
      <div class="flex justify-center">
        <a id="imageLink" href="#" target="_blank">
          <img id="vImage" src="" alt="Bill Image" class="max-h-40 max-w-full rounded-lg border cursor-pointer object-contain shadow-sm transition-transform duration-200 hover:scale-105">
        </a>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button onclick="closeModal('viewModal')" class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">Close</button>
    </div>
  </div>
</div>

<div id="updateModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'updateModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">✏️ Update Invoice</h2>
      <button class="text-gray-400 hover: text-2xl font-bold" onclick="closeModal('updateModal')">&times;</button>
    </div>

    <form method="post" class="space-y-4">
      <input type="hidden" name="bill_id" id="uBillID">

      <div>
        <label class="block  text-sm mb-1">Description</label>
        <input type="text" name="description" id="uDesc" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>

      <div>
        <label class="block  text-sm mb-1">Amount</label>
        <input type="number" step="0.01" name="amount" id="uAmount" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>

      <div>
        <label class="block  text-sm mb-1">Due Date</label>
        <input type="date" name="due_date" id="uDueDate" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>

      <div>
        <label class="block  text-sm mb-1">Payment Status</label>
        <select name="status" id="uStatus" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="Added">Added</option>
          <option value="Pending">Pending</option>
          <option value="Paid">Paid</option>
          <option value="Overdue">Overdue</option>
        </select>
      </div>

      <div class="flex justify-end space-x-3 pt-4">
        <button type="button" onclick="closeModal('updateModal')" class="px-4 py-2 bg-gray-300  rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" name="update" class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- 🔹 ARCHIVE MODAL -->
<div id="archiveModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'archiveModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">🗃️ Archive Invoice</h2>
      <button class="text-gray-400 hover: text-2xl font-bold" onclick="closeModal('archiveModal')">&times;</button>
    </div>

    <p class=" mb-6 text-sm">Are you sure you want to archive this invoice? <span class="text-red-600 font-semibold">This action cannot be undone.</span></p>

    <form method="post" class="flex justify-end space-x-3">
      <input type="hidden" name="archive_bill_id" id="aBillID">
      <button type="button" onclick="closeModal('archiveModal')" class="px-4 py-2 bg-gray-300  rounded-lg hover:bg-gray-400">Cancel</button>
      <button type="submit" name="archive" class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700">Archive</button>
    </form>
  </div>
</div>

<!-- 🔹 REQUEST PAYMENT MODAL -->
<div id="requestPaymentModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'requestPaymentModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">💰 Request Payment</h2>
      <button class="text-gray-400 hover: text-2xl font-bold" onclick="closeModal('requestPaymentModal')">&times;</button>
    </div>

    <form method="post" class="space-y-4">
      <input type="hidden" name="bill_id" id="rBillID">
      <input type="hidden" name="requestTitle" id="rTitle">
      <input type="hidden" name="amount" id="rAmount">
      <input type="hidden" name="due" id="rDue">
      <input type="hidden" name="Purpuse" id="rPurpuse">
      <input type="hidden" name="documents[]" id="rDocuments">
     
       <div>
<?php



$currentYear = date('Y');

try {
    $sql = "
        SELECT 
            ca.accountID, 
            coa.accountName, 
            ca.allocationID
        FROM budget.costallocation ca
        INNER JOIN ledger.chartofaccount coa ON coa.accountID = ca.accountID
        WHERE ca.yearlybudget = :year
          AND ca.Status = 'Activate'
          AND coa.Archive = 'NO'
          AND coa.status = 'Active'
        ORDER BY coa.accountName ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['year' => $currentYear]);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching allocations: ' . $e->getMessage());
}
?>

<div>
  <label class="block text-sm mb-1">Account Name</label>
  <select name="allocationID" id="rAllocationID" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
    <option value="">Select Account Name</option>
    <?php
    if (!empty($allocations)) {
        foreach ($allocations as $row) {
            echo '<option value="' . htmlspecialchars($row['allocationID']) . '">'
                . htmlspecialchars($row['accountName'])
                . '</option>';
        }
    } else {
        echo '<option disabled>No active cost allocation for ' . htmlspecialchars($currentYear) . '</option>';
    }
    ?>
  </select>
</div>

      <!-- <div>
        <label class="block text-sm mb-1">Allocation ID</label>
        <input type="number" name="allocationID" id="rAllocationID" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div> -->

      <p class="mb-4 text-sm">Confirm requesting payment for this invoice?</p>

      <div class="flex justify-end space-x-3 pt-4">
        <button type="button" onclick="closeModal('requestPaymentModal')" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" name="request_payment" class="px-4 py-2 bg-purple-600 text-white rounded-lg shadow hover:bg-purple-700">Request</button>
      </div>
    </form>
  </div>
</div>

<script>
  function closeAllModals() {
    ['viewModal', 'updateModal', 'archiveModal', 'requestPaymentModal'].forEach(id => document.getElementById(id).classList.add('hidden'));
  }

  function openViewModal(id, vendor, account, ref, billDate, dueDate, desc, amount, status, created, image) {
    closeAllModals();

    // --- Format date strings ---
    function formatDate(dateStr) {
      if (!dateStr) return '—';
      const date = new Date(dateStr);
      return isNaN(date) ? dateStr : date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    }

    // --- Fill modal data ---
    document.getElementById('vBillID').innerText = id || '—';
    document.getElementById('vVendor').innerText = vendor || '—';
    document.getElementById('vAccount').innerText = account || '—';
    document.getElementById('vRef').innerText = ref || '—';
    document.getElementById('vBillDate').innerText = formatDate(billDate);
    document.getElementById('vDueDate').innerText = formatDate(dueDate);
    document.getElementById('vDesc').innerText = desc || '—';
    document.getElementById('vAmount').innerText = '₱' + parseFloat(amount || 0).toLocaleString();
    document.getElementById('vStatus').innerText = status || '—';
    document.getElementById('vCreated').innerText = formatDate(created);

    const img = document.getElementById('vImage');
    const imgContainer = document.getElementById('imageContainer');
    const imgLink = document.getElementById('imageLink');

    if (image) {
      img.src = image;
      imgLink.href = image; // Set the href to open in new tab
      img.classList.remove('hidden');
      imgContainer.classList.remove('hidden');
    } else {
      img.classList.add('hidden');
      imgContainer.classList.add('hidden');
    }

    document.getElementById('viewModal').classList.remove('hidden');
  }

  function openUpdateModal(id, desc, amount, due, status) {
    closeAllModals();
    document.getElementById('uBillID').value = id;
    document.getElementById('uDesc').value = desc;
    document.getElementById('uAmount').value = amount;
    document.getElementById('uDueDate').value = due;
    document.getElementById('uStatus').value = status;
    document.getElementById('updateModal').classList.remove('hidden');
  }

  function openArchiveModal(id) {
    closeAllModals();
    document.getElementById('aBillID').value = id;
    document.getElementById('archiveModal').classList.remove('hidden');
  }

function openRequestPaymentModal(billId, vendor, ref, amount, due, Purpuse, file_path) {
    closeAllModals();
    document.getElementById('rBillID').value       = billId;
    document.getElementById('rTitle').value        = `Payment for ${vendor} Invoice ${ref}`;
    document.getElementById('rAmount').value       = amount;
    document.getElementById('rDue').value          = due;
    document.getElementById('rPurpuse').value      = Purpuse;
    document.getElementById('rDocuments').value    = file_path;   // <-- still the local path
    document.getElementById('requestPaymentModal').classList.remove('hidden');
}

  function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
  }

  function outsideClick(e, id) {
    if (e.target.id === id) closeModal(id);
  }
  function changeLimit(limit) {
    const url = new URL(window.location);
    url.searchParams.set('limit', limit);
    url.searchParams.set('page', 1); // Reset to page 1 on limit change
    window.location = url;
  }
</script>
