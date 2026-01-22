<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

  <div class="quick-stat-card purple border-b-2 border-opacity-50 p-4 rounded-xl shadow-sm">
    <p class="text-gray-500 text-xs">Bank Balance</p>
    <h2 class="text-xl font-bold mt-1 text-gray-800">₱<?php echo number_format(getBankBalance($pdo), 2); ?></h2>
    <div class="flex gap-2 mt-2">
      <button type="button" class="open-deposit px-3 py-1.5 bg-purple-500 text-white text-sm rounded-md" data-type="Bank">Deposit</button>
      <button type="button" id="open-withdraw" class="px-3 py-1.5 bg-gray-200 text-sm rounded-md">Withdraw</button>
    </div>
  </div>


  <div class="quick-stat-card green border-b-2 border-opacity-50 p-4 rounded-xl shadow-sm">
    <p class="text-gray-500 text-xs">Owner Deposit</p>
    <h2 class="text-xl font-bold mt-1 text-gray-800">₱<?php echo number_format(getOwnerDeposit($pdo), 2); ?></h2>
    <div class="flex gap-2 mt-2">
      <button type="button" class="open-deposit px-3 py-1.5 bg-green-500 text-white text-sm rounded-md" data-type="Owner">Add Capital</button>
    </div>
  </div>

 
  <div class="quick-stat-card red border-b-2 border-opacity-50 p-4 rounded-xl shadow-sm">
    <p class="text-gray-500 text-xs">Cash On Hand</p>
    <h2 class="text-xl font-bold mt-1 text-gray-800">₱<?php echo number_format(getCashOnHand($pdo), 2); ?></h2>
    <div class="mt-2">
     
    </div>
  </div>
</div>
