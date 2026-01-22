<div class="max-w-7xl mx-auto p-6 form-group shadow-lg rounded-xl mt-4">
    <h1 class="text-3xl font-extrabold mb-8  border-b-2 border-gray-200 pb-4">
        Department Allocations
    </h1>

    <div class="mb-8 flex items-center space-x-4">
        <label for="yearFilter" class="text-gray-600 font-semibold text-lg">
            Filter by Year:
        </label>
        <div class="relative w-48">
            <select id="yearFilter" class="block w-full px-4 py-2 pr-8 text-gray-700 bg-gray-100 border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                <?php foreach($years as $year): ?>
                    <option value="<?= $year ?>" <?= ($selectedYear == $year) ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
        </div>
    </div>

    <div id="cardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if($selectedYear && count($allocationsByDept) > 0): ?>
            <?php foreach($allocationsByDept as $deptName => $allocs): ?>
                <div class=" border border-gray-200 rounded-xl p-6 shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <h2 class="text-2xl font-bold mb-4 text-indigo-700 border-b-2 border-indigo-200 pb-2">
                        <?= $deptName ?>
                    </h2>
                    <p class="text-sm text-gray-500 font-medium mb-4">
                        Year: <span class="text-gray-700 font-bold"><?= $selectedYear ?></span>
                    </p>

                    <div class="space-y-4">
                        <?php foreach($allocs as $alloc): 
                            $yearly = $alloc['Amount'];
                            $monthly = round($yearly / 12);
                            $daily = round($yearly / 365);
                        ?>
                            <div class="p-4 border border-gray-100 rounded-lg   ">
                                <p class="font-bold  text-lg mb-1"><?= $alloc['Title'] ?></p>
                                <p class="text-sm text-gray-600">
                                    Percentage: <span class="font-semibold text-indigo-600"><?= $alloc['Percentage'] ?>%</span>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Yearly: <span class="font-bold text-green-600">₱<?= number_format($yearly) ?></span>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Monthly: <span class="font-medium">₱<?= number_format($monthly) ?></span> | Daily: <span class="font-medium">₱<?= number_format($daily) ?></span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif($selectedYear): ?>
            <p class="text-gray-500 text-center col-span-full">
                No allocations found for the year <span class="font-bold text-gray-700"><?= $selectedYear ?></span>.
            </p>
        <?php endif; ?>
    </div>
</div>

<script>

    document.getElementById('yearFilter').addEventListener('change', function() {
        const year = this.value;

        window.location.href = "?year=" + year;
    });
</script>