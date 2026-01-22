<div class="table-section" id="invoiceTableSection">
    <h3>Vendor Details</h3>
    <table id="employeesTable">
        <thead>
            <tr>
                <th>Company ID</th>
                <th>Company Name</th>
                <th>Contact Info</th>
                <th>Address</th>
                <th>Email</th>
                <th>Contact Person</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="employeesTableBody">
            <?php if (!empty($vendors)): ?>
                <?php foreach ($vendors as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['vendor_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_info']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['Email']); ?></td>
                        <td><?php echo htmlspecialchars($row['Contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($row['Status']); ?></td>
                        <td>
                           
    <div class="flex justify-center space-x-2">
        <button class="relative flex items-center justify-center w-9 h-9 p-0 rounded-md transition-all duration-200 cursor-pointer bg-white border border-gray-200 shadow-sm text-blue-600 hover:border-blue-600 hover:bg-blue-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-0 group"
            onclick="openViewModal(
                '<?php echo htmlspecialchars($row['vendor_id'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['vendor_name'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['contact_info'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['address'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['Email'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['Contact_person'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['Status'], ENT_QUOTES); ?>'
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
                '<?php echo htmlspecialchars($row['vendor_id'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['vendor_name'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['contact_info'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['address'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['Email'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['Contact_person'], ENT_QUOTES); ?>',
                '<?php echo htmlspecialchars($row['Status'], ENT_QUOTES); ?>'
            ); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 20l-4 1 1-4 9.586-9.586z" />
            </svg>
            <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-700 text-white text-xs font-normal rounded py-1 px-2 opacity-0 whitespace-nowrap transition-opacity duration-200 pointer-events-none group-hover:opacity-100">Update</span>
        </button>

        <button class="relative flex items-center justify-center w-9 h-9 p-0 rounded-md transition-all duration-200 cursor-pointer bg-white border border-gray-200 shadow-sm text-gray-600 hover:border-gray-600 hover:bg-gray-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-0 group"
            onclick="openArchiveModal('<?php echo htmlspecialchars($row['vendor_id'], ENT_QUOTES); ?>'); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7H4m16 0a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2m16 0V5a2 2 0 00-2-2H6a2 2 0 00-2 2v2m6 8h4" />
            </svg>
            <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-700 text-white text-xs font-normal rounded py-1 px-2 opacity-0 whitespace-nowrap transition-opacity duration-200 pointer-events-none group-hover:opacity-100">Archive</span>
        </button>
    </div>
</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center p-4">No Records Found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'viewModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">📄 Vendor Details</h2>
      <button class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="closeModal('viewModal')">&times;</button>
    </div>
    <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-gray-700">
            <p class="font-semibold text-gray-600"><strong>Vendor ID:</strong></p><p> <span id="viewVendorID"></span></p>
            <p class="font-semibold text-gray-600"><strong>Vendor Name:</strong></p><p> <span id="viewName"></span></p>
            <p class="font-semibold text-gray-600"><strong>Contact Info:</strong></p><p> <span id="viewContact"></span></p>
            <p class="font-semibold text-gray-600"><strong>Address:</strong></p><p> <span id="viewAddress"></span></p>
            <p class="font-semibold text-gray-600"><strong>Email:</strong></p><p> <span id="viewEmail"></span></p>
            <p class="font-semibold text-gray-600"><strong>Contact Person:</strong></p><p> <span id="viewPhone"></span></p>
            <p class="font-semibold text-gray-600"><strong>Status:</strong> </p><p><span id="viewStatus"></span></p>
        </div>
        <div class="mt-6 flex justify-end">
          <button class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700" onclick="closeModal('viewModal')">Close</button>
    </div>
            </div>
</div>

<div id="updateModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'updateModal')">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative transition-all transform hover:scale-[1.01]" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">✍️ Update Vendor</h2>
      <button class="text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeModal('updateModal')">&times;</button>
    </div>
    
    <form method="post" class="space-y-4">
      <input type="hidden" name="vendors_id" id="updateVendorID">
      
      <div>
        <label class="block text-sm mb-1">Vendor Name:</label>
        <input type="text" name="name" id="updateName" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>
      
      <div>
        <label class="block text-sm mb-1">Contact Info:</label>
        <input type="text" name="contact" id="updateContact" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>
      
      <div>
        <label class="block text-sm mb-1">Address:</label>
        <input type="text" name="address" id="updateAddress" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>
      
      <div>
        <label class="block text-sm mb-1">Email:</label>
        <input type="email" name="email" id="updateEmail" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>
      
      <div>
        <label class="block text-sm mb-1">Contact Person:</label>
        <input type="text" name="phone" id="updatePhone" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>
      
      <div>
        <label class="block text-sm mb-1">Status:</label>
        <select name="status" id="updateStatus" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500" required>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
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
      <h2 class="text-2xl font-semibold text-gray-800">🗃️ Archive Vendor</h2>
      <button class="text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeModal('archiveModal')">&times;</button>
    </div>

    <div class="text-center mb-6">
      <svg class="mx-auto mb-4 text-red-500 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
      </svg>
      <p class="text-lg text-gray-700">Are you sure you want to archive this Vendor record?</p>
      <p class="text-red-600 font-semibold mt-1">This action cannot be undone.</p>
    </div>

    <form method="post">
      <input type="hidden" name="archive_collectionID" id="archiveVendorID">
      <div class="flex justify-end space-x-3 pt-4">
        <button type="button" onclick="closeModal('archiveModal')" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700" name="archive">Yes, Archive</button>
      </div>
    </form>
  </div>
</div>

<script>
    // Close all modals
    function closeAllModals() {
        document.getElementById('viewModal').classList.add('hidden');
        document.getElementById('updateModal').classList.add('hidden');
        document.getElementById('archiveModal').classList.add('hidden');
    }

    // Open View Modal
    function openViewModal(vendor_id, vendor_name, contact_info, address, email, contact_person, status) {
        closeAllModals();
        document.getElementById('viewVendorID').innerText = vendor_id;
        document.getElementById('viewName').innerText = vendor_name;
        document.getElementById('viewContact').innerText = contact_info;
        document.getElementById('viewAddress').innerText = address;
        document.getElementById('viewEmail').innerText = email;
        document.getElementById('viewPhone').innerText = contact_person;
        document.getElementById('viewStatus').innerText = status;
        document.getElementById('viewModal').classList.remove('hidden');
    }

    function openUpdateModal(vendor_id, vendor_name, contact_info, address, email, contact_person, status) {
        closeAllModals();
        document.getElementById('updateVendorID').value = vendor_id;
        document.getElementById('updateName').value = vendor_name;
        document.getElementById('updateContact').value = contact_info;
        document.getElementById('updateAddress').value = address;
        document.getElementById('updateEmail').value = email;
        document.getElementById('updatePhone').value = contact_person;
        document.getElementById('updateStatus').value = status;
        document.getElementById('updateModal').classList.remove('hidden');
    }

    function openArchiveModal(vendor_id) {
        closeAllModals();
        document.getElementById('archiveVendorID').value = vendor_id;
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