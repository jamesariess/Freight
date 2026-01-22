

    <div class="container">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between shadow-md rounded-2xl p-4 border border-gray-200 card">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-bell text-indigo-500"></i>
                    Budget | Add Budget
                </h2>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="flex gap-3 items-center">
                    <div class="mb-6 form-group">
                        <label class="block text-gray-700 font-semibold mb-2">Filter by Year</label>
                        <select id="yearFilter" class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <?php
                            $yearStmt = $pdo->query("SELECT DISTINCT DateValid AS y FROM budget.departmentbudget ORDER BY y ASC");
                            $years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
                            echo "<option value=''>All Years</option>";
                            foreach ($years as $year) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button id="toggleFormBtn" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl shadow-md flex items-center gap-2 transition">
                        <i class="fas fa-plus-circle"></i>
                        Create a Budget
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded-lg shadow-md border border-gray-200 card">
            <h2 class="text-xl font-semibold mb-4">Department Budgets</h2>
            <div id="budgetCardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($budgets) > 0): ?>
                    <?php foreach ($budgets as $row): ?>
                        <?php
                        $dateValid = $row['DateValid'];
                        $year = $dateValid; 
                        ?>
                        <div class="rounded-xl shadow p-6 border border-gray-200 hover:shadow-lg transition flex flex-col" data-year="<?= htmlspecialchars($year) ?>">
                            <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($row['Name']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($row['Details']) ?></p>
                            <div class="mt-auto pt-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-sack-dollar text-indigo-500 text-2xl"></i>
                                        <span class="text-indigo-600 font-extrabold text-lg">
                                            ₱<?= number_format((float)$row['Amount'], 2) ?>
                                        </span>
                                    </div>
                                    <span class="text-gray-500 text-sm"><?= htmlspecialchars($row['DateValid']) ?></span>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this budget?');">
                                        <input type="hidden" name="cancel_id" value="<?= $row['Deptbudget'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">
                                            Cancel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600">No budgets found. Create one to get started.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>





    <div id="budgetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center p-4">
        <div class="rounded-lg shadow-xl p-6 w-full max-w-lg">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-bold">Create New Budget</h3>
                <button id="closeModalBtn" type="button" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
            <form id="budgetForm" method="POST">
                <div class="mb-4 form-group">
                    <label class="block text-gray-700 font-semibold mb-2">Department Name</label>
                    <select id="deptNameSelect" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Select Department --</option>
                      
                    </select>
                    <input type="text" id="deptNameInput" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 hidden mt-2" placeholder="Enter Department Name">
                    <input type="hidden" id="realDeptName" name="deptName">
                    <button type="button" id="addNewDeptBtn" class="mt-2 text-indigo-600 hover:underline text-sm">➕ Add New</button>
                </div>

                <div class="mb-4 form-group">
                    <label for="budgetYear" class="block text-gray-700 font-semibold mb-2">Year</label>
                    <select id="budgetYear" name="budgetYear" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                      
                    </select>
                </div>
                
                <div class="mb-4 form-group">
                    <label for="budgetAmount" class="block text-gray-700 font-semibold mb-2">Budget Amount (₱)</label>
                    <input type="number" id="budgetAmount" name="budgetAmount" step="0.01" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required min="0">
                    <small id="maxAllowedMsg" class="text-gray-500 text-sm mt-1 hidden"><span id="maxValue"></span></small>
                </div>

                <div class="mb-4 form-group">
                    <label class="block text-gray-700 font-semibold mb-2">Budget Details</label>
                    <textarea id="budgetDetailsInput" name="budgetDetails" rows="4" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter Budget Details"></textarea>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">Submit Budget</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('budgetModal');
                modal.style.display = 'flex';
            });
        </script>
    <?php endif; ?>

    <script>
        const modal = document.getElementById('budgetModal');
        const openBtn = document.getElementById('toggleFormBtn');
        const closeBtn = document.getElementById('closeModalBtn');

        openBtn.onclick = () => modal.style.display = 'flex';
        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };

        const deptDetailsMap = <?php echo json_encode($deptDetails); ?>;
        const yearSums = <?php echo json_encode($yearSums); ?>;
        const totalGLCash = <?php echo json_encode($totalGLCash); ?>;
        const dataByYear = <?php echo json_encode($dataByYear); ?>;

        const deptSelect = document.getElementById("deptNameSelect");
        const deptInput = document.getElementById("deptNameInput");
        const realDeptName = document.getElementById("realDeptName");
        const detailsInput = document.getElementById("budgetDetailsInput");
        const addNewBtn = document.getElementById("addNewDeptBtn");
        const form = document.getElementById("budgetForm");

        deptSelect.addEventListener("change", function() {
            const selected = this.value;
            realDeptName.value = selected;

            if (deptDetailsMap[selected]) {
                detailsInput.value = deptDetailsMap[selected];
                detailsInput.readOnly = true;
            } else {
                detailsInput.value = "";
                detailsInput.readOnly = false;
            }
        });

        addNewBtn.addEventListener("click", function() {
            if (deptSelect.classList.contains("hidden")) {
                deptSelect.classList.remove("hidden");
                deptInput.classList.add("hidden");
                detailsInput.value = "";
                detailsInput.readOnly = true;
                this.textContent = "➕ Add New";
                realDeptName.value = deptSelect.value;
            } else {
                deptSelect.classList.add("hidden");
                deptInput.classList.remove("hidden");
                detailsInput.value = "";
                detailsInput.readOnly = false;
                this.textContent = "⬅ Back to Select";
                realDeptName.value = "";
            }
        });

        form.addEventListener("submit", function() {
            if (!deptSelect.classList.contains("hidden")) {
                realDeptName.value = deptSelect.value;
            } else {
                realDeptName.value = deptInput.value;
            }
        });

        const budgetYearSelect = document.getElementById('budgetYear');
        const budgetAmountInput = document.getElementById('budgetAmount');
        const maxAllowedMsg = document.getElementById('maxAllowedMsg');
        const maxValueSpan = document.getElementById('maxValue');

        function validateBudgetAmount() {
            const year = parseInt(budgetYearSelect.value);
            if (!year) {
                maxAllowedMsg.classList.add('hidden');
                budgetAmountInput.max = '';
                return;
            }

            const existing = yearSums[year] || 0;
            const maxAdditional = Math.max(0, totalGLCash - existing);

            budgetAmountInput.max = maxAdditional;

            maxValueSpan.textContent = maxAdditional.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            maxAllowedMsg.classList.remove('hidden');

            let amount = parseFloat(budgetAmountInput.value) || 0;
            if (amount > maxAdditional) {
                budgetAmountInput.value = maxAdditional.toFixed(2);
            }
        }

        budgetYearSelect.addEventListener('change', validateBudgetAmount);
        budgetAmountInput.addEventListener('input', validateBudgetAmount);

        document.addEventListener('DOMContentLoaded', validateBudgetAmount);

    
     function updateStats(year) {
    const yearData = dataByYear[year] || dataByYear[Object.keys(dataByYear)[0]] || {
        totalBudgets: '₱0.00',
        approvedBudgets: '₱0.00',
        cancelledBudgets: '₱0.00',
        totalDepartments: 0
    };

    document.getElementById("totalBudgets").dataset.value = yearData.totalBudgets;
    document.getElementById("totalBudgets").textContent = '****';
    document.getElementById("approvedBudgets").dataset.value = yearData.approvedBudgets;
    document.getElementById("approvedBudgets").textContent = '****';
    document.getElementById("cancelledBudgets").dataset.value = yearData.cancelledBudgets;
    document.getElementById("cancelledBudgets").textContent = '****';


 document.getElementById("totalDepartments").textContent = yearData.totalDepartments;
    document.getElementById("Cash").dataset.value = dataByYear['cash'];
    document.getElementById("Cash").textContent = '****';
}
        document.addEventListener('DOMContentLoaded', function() {
            const yearFilter = document.getElementById('yearFilter');
            updateStats(yearFilter.value || '');

            yearFilter.addEventListener('change', function() {
                const selectedYear = this.value;
                updateStats(selectedYear);
                applyFilter(); 
            });
        });

        function applyFilter() {
            const selectedYear = yearFilter.value.trim();
            const cards = document.querySelectorAll("#budgetCardsContainer > div");

            cards.forEach(card => {
                const cardYear = card.getAttribute("data-year").trim();
                const shouldShow = selectedYear === "" || (cardYear === selectedYear);
                card.style.display = shouldShow ? "flex" : "none";
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const yearFilter = document.getElementById('yearFilter');
            yearFilter.addEventListener('change', applyFilter);
        });
        
       document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll('.masked:not(#totalDepartments)').forEach(el => {
        el.textContent = '****'; 
    });

    document.querySelectorAll('.masked:not(#totalDepartments)').forEach(el => {
        el.addEventListener('click', () => {
            if (el.textContent === '****') {
           
                el.textContent = el.dataset.value;
         
                el.dataset.timeout = setTimeout(() => {
                    el.textContent = '****';
                }, 10000);
            } else {
  
                el.textContent = '****';
                clearTimeout(el.dataset.timeout);
            }
        });
    });
});
      
    </script>

    <?php if ($reload): ?>
        <script>
            window.onload = function() {
                window.location = window.location.href;
            };
        </script>
    <?php endif; ?>



