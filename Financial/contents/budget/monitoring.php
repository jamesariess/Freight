<style>
    .bar:hover { opacity: .85; }
    .pointer { cursor: pointer; }
    #stackedBarChart {
        max-height: 500px; 
        overflow-y: auto;
    }
    .title-text {
        max-width: 200px; 
        white-space: nowrap;
        overflow: hidden; 
        text-overflow: ellipsis;
    }
    .no-data {
        animation: pulse 2s infinite; 
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
  .quick-stat-card {
    position: relative;
}

  .masked {
    cursor: pointer;
    letter-spacing: 2px;
  }

</style>

<div class="max-w-7xl mx-auto p-6 form-group">
    <header class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Budget Monitoring</h1>
            <p class="text-sm text-gray-600">Track budgets, allocations, expenditures and variances</p>
        </div>
        <div class="flex gap-3 items-center">
            <select id="yearFilter" class="border rounded px-3 py-2">
            
            </select>
            <select id="deptFilter" class="border rounded px-3 py-2">
                <option value="all">All Departments</option>
              
            </select>
        </div>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
       <div class="quick-stat-card purple border-b-2 border-opacity-50 relative">


    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Total Budget</p>
            <p id="totalBudget" class="text-xl font-semibold masked" data-value="–">****</p>
        </div>
        <div class="text-sm text-green-600 font-medium">Year</div>
    </div>
    <p class="text-xs text-gray-500 mt-2">Sum of all budgets for selected Year</p>
</div>


        <div class="quick-stat-card red border-b-2 border-opacity-50">
            <p class="text-sm text-gray-500">Total Allocated</p>
            <p id="totalSpent" class="text-xl font-semibold masked" data-value="–">****</p>
            <p class="text-xs text-gray-500 mt-2">Total expenditures recorded</p>
        </div>

        <div class="quick-stat-card green border-b-2 border-opacity-50">
            <p class="text-sm text-gray-500">Remaining</p>
            <p id="totalRemaining" class="text-xl font-semibold masked" data-value="–">****</p>
            <p class="text-xs text-gray-500 mt-2">Budget - Spent</p>
        </div>

        <div class="quick-stat-card yellow border-b-2 border-opacity-50">
            <p class="text-sm text-gray-500">Utilization</p>
            <p id="utilization" class="text-xl font-semibold" data-value="–">–</p>
            <p class="text-xs text-gray-500 mt-2">% of budget used</p>
        </div>
    </section>

    <div class="p-4 rounded-lg shadow mb-6 bg-white card">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-medium">Budget vs Actual (by Title)</h2>
            <div class="text-sm text-gray-500 mb-4">Click bars to view details</div>
        </div>
        <div class="flex flex-col md:flex-row gap-6">
            <div class="w-full md:w-1/2 p-4 rounded-lg border border-gray-200">
                <h3 class="text-sm font-semibold mb-2">Chart View</h3>
                <div id="stackedBarChart" class="w-full">
                    <!-- Chart rendered by JavaScript -->
                </div>
            </div>
            <div class="w-full md:w-1/2 p-4 rounded-lg border border-gray-200">
                <h3 class="text-sm font-semibold mb-2">Table View</h3>
                <div class="table-section overflow-x-auto" id="invoiceTableSection">
                    <table id="employeesTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Budget</th>
                                <th>Actual</th>
                            </tr>
                        </thead>
                        <tbody id="costAllocationTableBody" class="divide-y divide-gray-200">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-end sm:items-center justify-center p-4">
        <div class="rounded-lg shadow w-full max-w-2xl p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 id="detailTitle" class="text-lg font-semibold">Title Detail</h3>
                <button id="closeModal" class="px-3 py-1 border rounded">X</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Title</p>
                    <p id="detailTitleValue" class="font-semibold">–</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Department</p>
                    <p id="detailDept" class="font-semibold">–</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Budget</p>
                    <p id="detailBudget" class="font-semibold">–</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Actual</p>
                    <p id="detailActual" class="font-semibold">–</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Percentage</p>
                    <p id="detailPercentage" class="font-semibold">–</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Year</p>
                    <p id="detailYear" class="font-semibold">–</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600">Creation Date</p>
                    <p id="detailCreateDate" class="font-semibold">–</p>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button id="closeModal2" class="px-3 py-2 rounded border">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    const data = <?= json_encode($data) ?>;

    const yearFilter = document.getElementById('yearFilter');
    const deptFilter = document.getElementById('deptFilter');
    const totalBudgetEl = document.getElementById('totalBudget');
    const totalSpentEl = document.getElementById('totalSpent');
    const totalRemainingEl = document.getElementById('totalRemaining');
    const utilizationEl = document.getElementById('utilization');
    const stackedBarChart = document.getElementById('stackedBarChart');
    const costAllocationTableBody = document.getElementById('costAllocationTableBody');
    const detailModal = document.getElementById('detailModal');
    const detailTitle = document.getElementById('detailTitle');
    const detailTitleValue = document.getElementById('detailTitleValue');
    const detailDept = document.getElementById('detailDept');
    const detailBudget = document.getElementById('detailBudget');
    const detailActual = document.getElementById('detailActual');
    const detailPercentage = document.getElementById('detailPercentage');
    const detailYear = document.getElementById('detailYear');
    const detailCreateDate = document.getElementById('detailCreateDate');


    document.addEventListener("DOMContentLoaded", () => {
       
        document.querySelectorAll('.masked:not(#utilization)').forEach(el => {
            el.textContent = '****'; 
        });

        
        document.querySelectorAll('.masked:not(#utilization)').forEach(el => {
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

    function formatCurrency(n) {
        return n.toLocaleString(undefined, { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 });
    }

    function filteredDeptBudgets() {
        const selectedYear = parseInt(yearFilter.value);
        const selectedDept = deptFilter.value;
        return data.deptBudgets.filter(item =>
            item.year === selectedYear && (selectedDept === 'all' || item.name === selectedDept)
        );
    }

    function filteredGraphBudgets() {
        const selectedYear = parseInt(yearFilter.value);
        const selectedDept = deptFilter.value;
        return data.graphBudgets
            .filter(item => item.year === selectedYear)
            .filter(item => selectedDept === 'all' || item.dept === selectedDept)
            .filter(item => item.usedAllocation > 0);
    }

    function updateSummary() {
        const filtered = filteredDeptBudgets();
        if (filtered.length === 0) {
            totalBudgetEl.dataset.value = '–';
            totalBudgetEl.textContent = '****';
            totalSpentEl.dataset.value = '–';
            totalSpentEl.textContent = '****';
            totalRemainingEl.dataset.value = '–';
            totalRemainingEl.textContent = '****';
            utilizationEl.dataset.value = '–';
            utilizationEl.textContent = '–';
            renderStackedBarChart(filteredGraphBudgets());
            renderCostAllocationTable(filteredGraphBudgets());
            return;
        }

        const totalBudget = filtered.reduce((sum, item) => sum + item.amount, 0);
        const totalSpent = filtered.reduce((sum, item) => sum + item.usedBudget, 0);
        const remaining = Math.max(0, totalBudget - totalSpent);
        const util = totalBudget > 0 ? Math.min(100, Math.round((totalSpent / totalBudget) * 100)) : 0;

        totalBudgetEl.dataset.value = formatCurrency(totalBudget);
        totalBudgetEl.textContent = '****';
        totalSpentEl.dataset.value = formatCurrency(totalSpent);
        totalSpentEl.textContent = '****';
        totalRemainingEl.dataset.value = formatCurrency(remaining);
        totalRemainingEl.textContent = '****';
        utilizationEl.dataset.value = util + '%';
        utilizationEl.textContent = util + '%';
        renderStackedBarChart(filteredGraphBudgets());
        renderCostAllocationTable(filteredGraphBudgets());
    }

    let doughnutChartInstance = null;

    function renderStackedBarChart(budgets) {
        const ctxId = "doughnutCanvas";
        stackedBarChart.innerHTML = `<canvas id="${ctxId}" class="w-full"></canvas>`;
        const ctx = document.getElementById(ctxId);

        if (budgets.length === 0) {
            stackedBarChart.innerHTML = `
                <div class="text-center text-gray-500 py-10 animate-pulse">
                    No Data Available
                </div>`;
            return;
        }

        const labels = budgets.map(b => b.accountCode || b.title || "N/A");
        const usedData = budgets.map(b => b.usedAllocation);
        const remainingData = budgets.map(b => Math.max(0, b.amount - b.usedAllocation));

        const colors = [
            "#60a5fa", "#a78bfa", "#34d399", "#fbbf24", "#f87171",
            "#f472b6", "#4ade80", "#facc15", "#38bdf8", "#c084fc"
        ];

        if (doughnutChartInstance) {
            doughnutChartInstance.destroy();
        }

        doughnutChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Actual Spending',
                    data: usedData,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#374151',
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const val = context.parsed;
                                return `${context.label}: ₱${val.toLocaleString()}`;
                            }
                        }
                    }
                },
                onClick: (evt, activeEls) => {
                    if (activeEls.length > 0) {
                        const index = activeEls[0].index;
                        const item = budgets[index];
                        if (item) showModal(item);
                    }
                }
            }
        });
    }

    function renderCostAllocationTable(budgets) {
        const sorted = [...budgets].sort((a, b) => b.amount - a.amount);
        let html = '';
        if (sorted.length === 0) {
            html += `
                <tr class="no-data">
                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v8m0 0a8 8 0 01-8 8m8-8v8"></path>
                        </svg>
                        No Data Available
                    </td>
                </tr>
            `;
        } else {
            sorted.forEach(item => {
                html += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">${item.title || '–'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${formatCurrency(item.amount || 0)}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${formatCurrency(item.usedAllocation || 0)}</td>
                    </tr>
                `;
            });
        }
        costAllocationTableBody.innerHTML = html;
    }

    function showModal(item) {
        detailTitleValue.textContent = item.title || '–';
        detailDept.textContent = item.dept || '–';
        detailBudget.textContent = formatCurrency(item.amount || 0);
        detailActual.textContent = formatCurrency(item.usedAllocation || 0);
        detailPercentage.textContent = `${item.percentage || 0}%`;
        detailYear.textContent = item.year || '–';
        detailCreateDate.textContent = item.AllocationCreate || '–';
        detailModal.classList.remove('hidden');
        detailModal.classList.add('flex');
    }

    function closeModal() {
        detailModal.classList.add('hidden');
        detailModal.classList.remove('flex');
    }

    document.getElementById('closeModal').addEventListener('click', closeModal);
    document.getElementById('closeModal2').addEventListener('click', closeModal);
    detailModal.addEventListener('click', (e) => { if (e.target === detailModal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    function initFilters() {
        yearFilter.innerHTML = '';
        data.years.forEach(y => {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearFilter.appendChild(opt);
        });

        deptFilter.innerHTML = '<option value="all">All Departments</option>';
        data.departments.forEach(dept => {
            const opt = document.createElement('option');
            opt.value = dept;
            opt.textContent = dept;
            deptFilter.appendChild(opt);
        });

        yearFilter.addEventListener('change', updateSummary);
        deptFilter.addEventListener('change', updateSummary);
    }

    initFilters();
    if (data.years.length > 0) {
        yearFilter.value = data.years[0];
        updateSummary();
    }
</script>