
<div class="table-section" id="invoiceTableSection">
    <h3>Payment Report</h3>
    <!-- Rows per page + counter -->
<div class="flex justify-between items-center mb-4">
    <div class="flex items-center space-x-2 form-group">
        <label for="rowsPerPage" class="text-sm">Rows per page:</label>
        <select id="rowsPerPage"
                onchange="window.location.href='?<?=http_build_query(array_merge($_GET,['limit'=>'']))?>'+this.value"
                class="border p-1 rounded text-sm">
            <option value="5"  <?= $limit==5  ?'selected':'' ?>>5</option>
            <option value="10" <?= $limit==10 ?'selected':'' ?>>10</option>
            <option value="25" <?= $limit==25 ?'selected':'' ?>>25</option>
            <option value="50" <?= $limit==50 ?'selected':'' ?>>50</option>
        </select>
    </div>
    <div class="text-sm">
        Showing <?= $offset+1 ?> to <?= min($offset+$limit, $totalRows) ?> of <?= $totalRows ?> entries
    </div>
</div>
    <table id="employeesTable">
        <thead>
            <tr>
    <th>Collection</th>
    <th>Invoice ID</th>
    <th>Payment Date</th>
    <th>Amount</th>
    <th>Payment Method</th>
    <th>Remarks</th>
    
  
    <th>Action</th>
            </tr>
        </thead>
        <tbody id="employeesTableBody" class="text-center">
            <?php if(!empty($collectionReports)):
            foreach($collectionReports as $row): ?>
            <tr> 
    <td><?php echo htmlspecialchars($row['collection_id']);?></td>
    <td><?php echo htmlspecialchars($row['reference_no']);?></td>
    <td><?php echo htmlspecialchars($row['fmt_payment_date']);?></td>
    <td><?php echo htmlspecialchars($row['amount']);?></td>
    <td><?php echo htmlspecialchars($row['method']);?></td>
    <td><?php echo htmlspecialchars($row['remarks']);?></td>
 
     <td>

    <div class="flex justify-center space-x-2">
        <button class="relative flex items-center justify-center w-9 h-9 p-0 rounded-md transition-all duration-200 cursor-pointer bg-white border border-gray-200 shadow-sm text-blue-600 hover:border-blue-600 hover:bg-blue-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-0 group"
            onclick="openViewModal(
                '<?php echo $row['collection_id'];?>',
                '<?php echo htmlspecialchars($row['reference_no'],ENT_QUOTES);?>',
                '<?php echo htmlspecialchars($row['fmt_payment_date'],ENT_QUOTES);?>',
                '<?php echo htmlspecialchars($row['amount'],ENT_QUOTES);?>',
                '<?php echo htmlspecialchars($row['method'],ENT_QUOTES);?>',
                '<?php echo htmlspecialchars($row['remarks'],ENT_QUOTES);?>'
            ); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-700 text-white text-xs font-normal rounded py-1 px-2 opacity-0 whitespace-nowrap transition-opacity duration-200 pointer-events-none group-hover:opacity-100">View</span>
        </button>

        <button class="relative flex items-center justify-center w-9 h-9 p-0 rounded-md transition-all duration-200 cursor-pointer bg-white border border-gray-200 shadow-sm text-green-700 hover:border-green-700 hover:bg-green-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-0 group"
            onclick="openUpdateModal(
                '<?php echo $row['collection_id'];?>',
             
                '<?php echo htmlspecialchars($row['amount'],ENT_QUOTES);?>',
              
                '<?php echo htmlspecialchars($row['remarks'],ENT_QUOTES);?>'
            ); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 20l-4 1 1-4 9.586-9.586z" />
            </svg>
            <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-700 text-white text-xs font-normal rounded py-1 px-2 opacity-0 whitespace-nowrap transition-opacity duration-200 pointer-events-none group-hover:opacity-100">Update</span>
        </button>

        <button class="relative flex items-center justify-center w-9 h-9 p-0 rounded-md transition-all duration-200 cursor-pointer bg-white border border-gray-200 shadow-sm text-gray-600 hover:border-gray-600 hover:bg-gray-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-0 group"
            onclick="openArchiveModal('<?php echo $row['collection_id']?>'); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7H4m16 0a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2m16 0V5a2 2 0 00-2-2H6a2 2 0 00-2 2v2m6 8h4" />
            </svg>
            <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-700 text-white text-xs font-normal rounded py-1 px-2 opacity-0 whitespace-nowrap transition-opacity duration-200 pointer-events-none group-hover:opacity-100">Archive</span>
        </button>
    </div>
</td>
         
            </tr>
            <?php endforeach; else:?>
            <tr><Td colspan="8" class="text-center p-4">NO Records Found.</Td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>
    </tbody>
</table>

<!-- Pagination -->
<div class="flex justify-between items-center mt-4">
    <div class="text-sm">Page <?= $page ?> of <?= $totalPages ?></div>
    <div class="flex space-x-1">
        <?php if ($page > 1): ?>
            <a href="<?= buildPaginatedUrl($page-1) ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">Previous</a>
        <?php else: ?>
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded text-sm cursor-not-allowed">Previous</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page-2);
        $end   = min($totalPages, $start+4);
        for ($i=$start; $i<=$end; $i++):
            if ($i == $page): ?>
                <span class="px-3 py-1 bg-blue-500 text-white rounded text-sm"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= buildPaginatedUrl($i) ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm"><?= $i ?></a>
            <?php endif;
        endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= buildPaginatedUrl($page+1) ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">Next</a>
        <?php else: ?>
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded text-sm cursor-not-allowed">Next</span>
        <?php endif; ?>
    </div>
</div>

<div id="viewModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'viewModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">💰 View Payment Details</h2>
      <button class="text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeModal('viewModal')">&times;</button>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-base text-gray-700">
      <p class="font-semibold text-gray-600">Collection ID:</p><p><span id="viewAdjustID"></span></p>
      <p class="font-semibold text-gray-600">Invoice ID:</p><p><span id="viewBudgetID"></span></p>
      <p class="font-semibold text-gray-600">Payment Date:</p><p><span id="viewAdjustedBy"></span></p>
      <p class="font-semibold text-gray-600">Amount:</p><p><span id="viewAdjustmentDate"></span></p>
      <p class="font-semibold text-gray-600">Payment Method:</p><p><span id="viewReason"></span></p>
      <p class="font-semibold text-gray-600">Status:</p><p><span id="viewStatus"></span></p>
      
      <p class="font-semibold text-gray-600 col-span-2 mt-2">Remarks:</p>
      <p class="col-span-2 pl-4 break-words"><span id="viewNewAmount"></span></p>
    </div>

    <div class="mt-6 flex justify-end">
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700" onclick="closeModal('viewModal')">Close</button>
    </div>
  </div>
</div>

<div id="updateModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'updateModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">✍️ Update Payment Collection</h2>
      <button class="text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeModal('updateModal')">&times;</button>
    </div>

    <form method="post" class="space-y-4">
      <input type="hidden" name="update_collectionID" id="updateAdjustID">
      
   

      <div>
        <label for="updateAmount" class="block text-sm mb-1">Amount</label>
        <input type="number" step="0.01" name="update_amount" id="updateAmount" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required> 
      </div>
    
      
      <div>
        <label for="updateRemarks" class="block text-sm mb-1">Remarks</label>
        <textarea name="update_remarks" id="updateRemarks" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" rows="3" required></textarea> 
      </div>

      <div class="flex justify-end space-x-3 pt-4">
        <button type="button" onclick="closeModal('updateModal')" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" name="update" class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">Update</button>
      </div>
    </form>
  </div>
</div>

<div id="archiveModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'archiveModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">🗃️ Archive Collection</h2>
      <button class="text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeModal('archiveModal')">&times;</button>
    </div>

    <div class="text-center mb-6">
      <svg class="mx-auto mb-4 text-red-500 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
      </svg>
      <p class="text-lg text-gray-700">Are you sure you want to archive this collection record?</p>
      <p class="text-red-600 font-semibold mt-1">This action cannot be undone.</p>
    </div>

    <form method="post">
      <input type="hidden" name="archive_collectionID" id="archiveAdjustID">
      <div class="flex justify-end space-x-3 pt-4">
        <button type="button" onclick="closeModal('archiveModal')" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700" name="archive">Yes, Archive</button>
      </div>
    </form>
  </div>
</div>

<script>
function openViewModal(collectionID, invoiceID, paymentDate, amount, method, remarks) {
    document.getElementById('viewAdjustID').innerText = collectionID;
    document.getElementById('viewBudgetID').innerText = invoiceID;
    document.getElementById('viewAdjustedBy').innerText = paymentDate;  
    document.getElementById('viewAdjustmentDate').innerText = amount;
    document.getElementById('viewReason').innerText = method;
    document.getElementById('viewNewAmount').innerText = remarks;
    document.getElementById('viewModal').classList.remove('hidden');
}
function openUpdateModal(collectionID,  amount,  remarks) {
    document.getElementById('updateAdjustID').value = collectionID;

    document.getElementById('updateAmount').value = amount;

    document.getElementById('updateRemarks').value = remarks;
    document.getElementById('updateModal').classList.remove('hidden');
}
function openArchiveModal(collectionID) {
    document.getElementById('archiveAdjustID').value = collectionID;
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