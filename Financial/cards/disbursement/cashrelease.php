
<div >
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Total Request -->
    <div class="quick-stat-card purple border-b-2 border-opacity-50" data-tooltip="Total number of requests">
      <div class="flex items-center mb-3">
        <i class="fas fa-file-alt text-purple-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Total Request</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="totalRequest" class="text-3xl font-bold text-purple-600">0</div>
        <span class="text-gray-500 text-sm">Requests</span>
      </div>
    </div>

    <div class="quick-stat-card blue border-b-2 border-opacity-50" data-tooltip="Total amount released">
      <div class="flex items-center mb-3">
        <i class="fas fa-wallet text-teal-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Total Release</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="totalAmountRelease" class="text-3xl font-bold text-teal-600">₱0</div>
        <span class="text-gray-500 text-sm">Released</span>
      </div>
    </div>

    <!-- Rejected Request -->
    <div class="quick-stat-card red border-b-2 border-opacity-50" data-tooltip="Number of rejected requests">
      <div class="flex items-center mb-3">
        <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Rejected</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="rejectedRequest" class="text-3xl font-bold text-red-600">0</div>
        <span class="text-gray-500 text-sm">Requests</span>
      </div>
    </div>

    <!-- New Request -->
    <div class="quick-stat-card green border-b-2 border-opacity-50" data-tooltip="Number of new pending requests">
      <div class="flex items-center mb-3">
        <i class="fas fa-plus-circle text-green-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">New Request</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="newRequest" class="text-3xl font-bold text-green-600">0</div>
        <span class="text-gray-500 text-sm">Pending</span>
      </div>
    </div>
  </div>
<script>
  // Use PHP variables directly
  const data = <?php echo json_encode($data); ?>;

  document.getElementById("totalRequest").textContent = data.totalRequest;
  document.getElementById("totalAmountRelease").textContent = "₱" + Number(data.totalAmountRelease).toLocaleString();
  document.getElementById("rejectedRequest").textContent = data.rejectedRequest;
  document.getElementById("newRequest").textContent = data.newRequest;
</script>
