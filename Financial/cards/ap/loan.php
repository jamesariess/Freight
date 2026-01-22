    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="quick-stat-card purple border-b-2 border-opacity-50">
        <p class="text-gray-500">Total Active Loans</p>
        <h2 class="text-2xl font-semibold mt-1"><?php echo getTotalActiveLoans($pdo); ?></h2>
      </div>
      <div class="quick-stat-card green border-b-2 border-opacity-50">
        <p class="text-gray-500">Outstanding Balance</p>
        <h2 class="text-2xl font-semibold mt-1"><?php echo getTotalOutstanding($pdo); ?></h2>
      </div>
      <div class="quick-stat-card red border-b-2 border-opacity-50">
        <p class="text-gray-500">Next Due</p>
        <h2 class="text-2xl font-semibold mt-1"><?php echo getNextDueDate($pdo); ?></h2>  
      </div>
    </div>