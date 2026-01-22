<div class="container mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Total Budgets -->
        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col border-l-4 border-purple-500">
            <div class="flex items-center mb-3">
                <i class="fas fa-sack-dollar text-purple-600 text-xl mr-2"></i>
                <h3 class="text-base font-semibold text-gray-800">Total Budgets This Year</h3>
            </div>
            <div class="flex items-end justify-between">
                <div id="totalBudgets" class="text-2l font-bold text-purple-600">0</div>
                <span class="text-gray-500 text-sm">All</span>
            </div>
        </div>

        <!-- Approved Budgets -->
        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col border-l-4 border-green-500">
            <div class="flex items-center mb-3">
                <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
                <h3 class="text-base font-semibold text-gray-800">Active</h3>
            </div>
            <div class="flex items-end justify-between">
                <div id="approvedBudgets" class="text-2l font-bold text-green-600">0</div>
                <span class="text-gray-500 text-sm">Budgets This year</span>
            </div>
        </div>

        <!-- Cancelled Budgets -->
        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col border-l-4 border-red-500">
            <div class="flex items-center mb-3">
                <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
                <h3 class="text-base font-semibold text-gray-800">Cancelled</h3>
            </div>
            <div class="flex items-end justify-between">
                <div id="cancelledBudgets" class="text-2l font-bold text-red-600">0</div>
                <span class="text-gray-500 text-sm">Budgets This year</span>
            </div>
        </div>

        <!-- Pending Budgets -->
        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col border-l-4 border-yellow-500">
            <div class="flex items-center mb-3">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-2"></i>
                <h3 class="text-base font-semibold text-gray-800">Total Department</h3>
            </div>
            <div class="flex items-end justify-between">
                <div id="totalDepartments" class="text-3xl font-bold text-yellow-600">0</div>
                <span class="text-gray-500 text-sm">Budgets</span>
            </div>
        </div>

    </div>
</div>

<script>
  const data = <?php echo json_encode($data); ?>;
            document.getElementById("totalBudgets").textContent = data.totalBudgets;
            document.getElementById("approvedBudgets").textContent = data.approvedBudgets;
            document.getElementById("cancelledBudgets").textContent = data.cancelledBudgets;
            document.getElementById("totalDepartments").textContent = data.totalDepartments;
</script>
