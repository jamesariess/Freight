<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <div class="quick-stat-card purple border-b-2 border-opacity-50">
        <p class="text-gray-500">Total Entries</p>
        <h2 class="text-2xl font-semibold mt-1"><?php echo getTotalEntires($pdo); ?></h2>
    </div>
    <div class="quick-stat-card green border-b-2 border-opacity-50">
        <p class="text-gray-500">Total Credits</p>
        <h2 class="text-2xl font-semibold mt-1">₱ <?php echo getTotalCredits($pdo); ?></h2>
    </div>
    <div class="quick-stat-card red border-b-2 border-opacity-50">
        <p class="text-gray-500">Total Debit</p>
        <h2 class="text-2xl font-semibold mt-1">₱ <?php echo getTotalDebits($pdo); ?></h2>
    </div>
</div>