<script>
    const accounts = <?php echo json_encode($accounts); ?>;
</script>

<div id="allocationError" class="mb-4 hidden bg-red-200 text-red-800 p-3 rounded"></div>

<div class=" p-6 rounded-2xl shadow-md form-group">
  <h1 class="text-2xl font-bold mb-4 text-indigo-700">Department Cost Allocation</h1>

  <form method="POST" id="allocationForm">
    <div class="grid grid-cols-2 gap-4 mb-6 ">
      <div >
        <label class="block  mb-1">Select Department</label>
        <select id="department" name="department" class="w-full p-2 border rounded">
          <option value="">-- Choose Department --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['Deptbudget'] ?>" 
                    data-amount="<?= $d['Amount'] ?>" 
                    data-used="<?= $d['UsedBudget'] ?>" 
                    data-year="<?= $d['DateValid'] ?>">
              <?= $d['Name'] ?> (₱<?= number_format($d['Amount'] - $d['UsedBudget']) ?> remaining)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block  mb-1">Year</label>
        <select id="year" name="year" class="w-full p-2 border rounded">
          <option value="">-- Choose Year --</option>
        </select>
      </div>
    </div>

    <div id="budgetInfo" class="mb-6 hidden  p-4 rounded border">
      <p class="">Yearly Budget: <span id="yearlyBudget" class="font-bold text-indigo-600"></span></p>
      <p class="">Remaining Budget: <span id="remainingBudget" class="font-bold text-green-600"></span></p>
    </div>

    <div class="mt-6">
      <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span>Total Percentage</span>
        <span id="totalPercent">0%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
        <div id="progressBar" class="bg-indigo-500 h-3 rounded-full w-0"></div>
      </div>

      <div id="allocationRows" class="space-y-4"></div>
  
      <button type="button" id="addAllocationBtn" onclick="addRow()" 
        class="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600" disabled>+ Add Allocation</button>
    </div>

    <button type="submit" 
      class="mt-6 px-6 py-3 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700">Submit Allocation</button>
  </form>
</div>
<script>
    const accounts = <?php echo json_encode($accounts); ?>;

    let yearlyBudget = 0;
    let remainingBudget = 0;
    let rowIndex = 0;
    let excludedAccountsFromDB = [];
    let restrictedAccountsFromDB = [];
    let allocatedCache = {}; // New: cache for isAllocated per accountID for current dept

    function formatPeso(value) {
        return "₱" + Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function areDepartmentAndYearSelected() {
        const deptSelect = document.getElementById("department");
        const yearSelect = document.getElementById("year");
        return deptSelect.value && yearSelect.value;
    }

    function updateFormControls() {
        const addBtn = document.getElementById("addAllocationBtn");
        const accountSelects = document.querySelectorAll('.account-select');
        const isValid = areDepartmentAndYearSelected();

        addBtn.disabled = !isValid;
        accountSelects.forEach(select => {
            select.disabled = !isValid;
            if (!isValid) {
                select.value = "";
                const percentInput = select.closest('.grid')?.querySelector('.percentage');
                if (percentInput) {
                    percentInput.value = "";
                    recalculate(percentInput);
                }
            }
        });
    }

    // === DEPARTMENT CHANGE ===
    document.getElementById("department").addEventListener("change", function() {
        const option = this.options[this.selectedIndex];
        yearlyBudget = parseFloat(option.dataset.amount || 0);
        remainingBudget = yearlyBudget - parseFloat(option.dataset.used || 0);

        const yearSelect = document.getElementById("year");
        yearSelect.innerHTML = "<option value=''>-- Choose Year --</option>";

        const deptname = option.textContent.split(' (')[0].trim();
        allocatedCache = {}; // Reset cache when department changes

        if (deptname) {
            fetch(`../../crud/budget/allocation.php?deptname=${encodeURIComponent(deptname)}`)
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    return res.json();
                })
                .then(years => {
                    years.forEach(y => {
                        const opt = document.createElement("option");
                        opt.value = y;
                        opt.text = y;
                        yearSelect.appendChild(opt);
                    });
                    if (years.length === 0) {
                        const opt = document.createElement("option");
                        opt.value = "";
                        opt.text = "No years available";
                        opt.disabled = true;
                        yearSelect.appendChild(opt);
                    }
                    updateFormControls();
                })
                .catch(err => {
                    console.error('Error fetching years:', err);
                    alert('Failed to load years.');
                });
        } else {
            updateFormControls();
        }

        updateBudgetInfo();
    });

    // === YEAR CHANGE ===
    document.getElementById("year").addEventListener("change", function() {
        const deptSelect = document.getElementById("department");
        const deptname = deptSelect.options[deptSelect.selectedIndex]?.text.split(' (')[0].trim();
        const year = this.value;

        if (deptname && year) {
            fetch(`../../crud/budget/allocation.php?deptname=${encodeURIComponent(deptname)}&year=${year}`)
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    return res.json();
                })
                .then(data => {
                    yearlyBudget = data.yearlyBudget || 0;
                    remainingBudget = data.remainingBudget || 0;
                    excludedAccountsFromDB = data.existing_accounts || [];
                    restrictedAccountsFromDB = data.restricted_accounts || [];
                    allocatedCache = {}; // Reset cache on year change too

                    updateBudgetInfo();
                    repopulateAllSelects();
                    updateFormControls();
                })
                .catch(err => {
                    console.error('Error fetching budget data:', err);
                    alert('Failed to load budget data.');
                    yearlyBudget = remainingBudget = 0;
                    updateBudgetInfo();
                    repopulateAllSelects();
                    updateFormControls();
                });
        } else {
            yearlyBudget = remainingBudget = 0;
            excludedAccountsFromDB = [];
            restrictedAccountsFromDB = [];
            allocatedCache = {};
            updateBudgetInfo();
            repopulateAllSelects();
            updateFormControls();
        }
    });

    function updateBudgetInfo() {
        const budgetInfo = document.getElementById("budgetInfo");
        if (yearlyBudget > 0) {
            budgetInfo.classList.remove("hidden");
            document.getElementById("yearlyBudget").innerText = formatPeso(yearlyBudget);
        } else {
            budgetInfo.classList.add("hidden");
        }
        // Remaining budget will be updated in updateTotal()
        updateTotal();
    }

    function addRow() {
        if (!areDepartmentAndYearSelected()) return;

        const container = document.getElementById("allocationRows");
        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center p-3 rounded border";
        row.innerHTML = `
            <select name="allocations[${rowIndex}][accountID]" class="col-span-3 p-2 border rounded account-select" onchange="updateAccountSelections(this)">
                <option value="">-- Select Account --</option>
            </select>
            <input type="number" name="allocations[${rowIndex}][percentage]" placeholder="%" min="0" max="100" 
                   class="col-span-2 p-2 border rounded percentage" oninput="recalculate(this)">
            <input type="text" name="allocations[${rowIndex}][amount]" readonly class="col-span-3 p-2 border rounded bg-gray-100 amount" placeholder="Yearly">
            <div class="col-span-3 text-sm text-gray-600">
                <p>Monthly: <span class="monthly font-bold">₱0.00</span></p>
                <p>Daily: <span class="daily font-bold">₱0.00</span></p>
            </div>
            <button type="button" onclick="removeRow(this)" class="col-span-1 text-red-500 font-bold text-xl">-</button>
        `;
        container.appendChild(row);

        const newSelect = row.querySelector('.account-select');
        populateAccounts(newSelect);
        updateAccountSelections(newSelect);
        rowIndex++;
    }

    // Optimized: uses cache to avoid repeated AJAX calls
    async function isAccountAllocatedToCurrentDept(accountID) {
        const deptSelect = document.getElementById("department");
        const deptname = deptSelect.options[deptSelect.selectedIndex]?.text.split(' (')[0].trim();
        if (!deptname) return false;

        if (allocatedCache[accountID] !== undefined) {
            return allocatedCache[accountID];
        }

        try {
            const res = await fetch(`../../crud/budget/allocation.php?check_allocation=true&accountID=${accountID}&deptname=${encodeURIComponent(deptname)}`);
            if (!res.ok) throw new Error("Network error");
            const data = await res.json();
            allocatedCache[accountID] = data.isAllocated;
            return data.isAllocated;
        } catch (err) {
            console.error("Error checking allocation:", err);
            return false;
        }
    }

    async function populateAccounts(selectElement) {
        selectElement.innerHTML = '<option value="">-- Select Account --</option>';

        const deptSelect = document.getElementById("department");
        const deptname = deptSelect.options[deptSelect.selectedIndex]?.text.split(' (')[0].trim();

        for (const account of accounts) {
            const isExcluded = excludedAccountsFromDB.includes(account.accountID);
            const isRestricted = restrictedAccountsFromDB.includes(account.accountID);
            const isValidType = ['Expenses', 'Assets'].includes(account.accounType);

            let allowed = isValidType && !isExcluded;

            if (allowed && isRestricted) {
                const isAllocated = await isAccountAllocatedToCurrentDept(account.accountID);
                allowed = isAllocated;
            }

            if (allowed) {
                const opt = document.createElement("option");
                opt.value = account.accountID;
                opt.text = account.accountName;
                selectElement.appendChild(opt);
            }
        }
    }

    function repopulateAllSelects() {
        const allSelects = document.querySelectorAll('.account-select');
        allSelects.forEach(select => {
            const currentValue = select.value;
            populateAccounts(select).then(() => {
                // Restore previous value if still available
                if (currentValue && Array.from(select.options).some(o => o.value === currentValue)) {
                    select.value = currentValue;
                } else if (currentValue) {
                    select.value = "";
                    const percentInput = select.closest('.grid')?.querySelector('.percentage');
                    if (percentInput) {
                        percentInput.value = "";
                        recalculate(percentInput);
                    }
                }
                updateAccountSelections();
            });
        });
    }

    // Fixed and simplified version
    async function updateAccountSelections(changedSelect = null) {
        const allSelects = document.querySelectorAll('.account-select');
        const usedAccountIDs = new Set();

        // Collect currently selected accounts (except the one being changed)
        allSelects.forEach(sel => {
            if (sel !== changedSelect && sel.value) {
                usedAccountIDs.add(sel.value);
            }
        });

        for (const select of allSelects) {
            for (const option of select.options) {
                if (!option.value) continue;

                const isDuplicate = usedAccountIDs.has(option.value);
                const isExcluded = excludedAccountsFromDB.includes(option.value);
                const isRestricted = restrictedAccountsFromDB.includes(option.value);

                let disabled = isDuplicate || isExcluded || !areDepartmentAndYearSelected();

                if (!disabled && isRestricted) {
                    const isAllocated = await isAccountAllocatedToCurrentDept(option.value);
                    disabled = !isAllocated;
                }

                option.disabled = disabled;
            }

            // If the changed select now has its value disabled, clear it
            if (changedSelect === select && select.value && select.selectedOptions[0]?.disabled) {
                select.value = "";
                const percentInput = select.closest('.grid')?.querySelector('.percentage');
                if (percentInput) {
                    percentInput.value = "";
                    recalculate(percentInput);
                }
                alert("This account is already allocated to another row or restricted for this department.");
            }
        }

        updateTotal();
    }

    function removeRow(btn) {
        btn.parentElement.remove();
        updateAccountSelections();
        updateTotal();
        rowIndex--;
    }

    function recalculate(input) {
        let percent = parseFloat(input.value) || 0;
        if (percent > 100) {
            percent = 100;
            input.value = 100;
        }

        const totalPercent = Array.from(document.querySelectorAll('.percentage'))
            .reduce((sum, p) => sum + (parseFloat(p.value) || 0), 0);

        const errorDiv = document.getElementById("allocationError");
        if (totalPercent > 100) {
            errorDiv.innerText = "⚠ Total allocation cannot exceed 100%!";
            errorDiv.classList.remove("hidden");
        } else {
            errorDiv.classList.add("hidden");
        }

        const row = input.closest('.grid');
        const amountField = row.querySelector(".amount");
        const monthlyField = row.querySelector(".monthly");
        const dailyField = row.querySelector(".daily");

        const yearly = (percent / 100) * remainingBudget;
        const monthly = yearly / 12;
        const daily = yearly / 365;

        amountField.value = yearly.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        monthlyField.innerText = formatPeso(monthly);
        dailyField.innerText = formatPeso(daily);

        updateTotal();
    }

    function updateTotal() {
        const percents = document.querySelectorAll('.percentage');
        const amounts = document.querySelectorAll('.amount');

        let totalPercent = 0;
        let totalAmount = 0;

        percents.forEach(p => totalPercent += parseFloat(p.value) || 0);
        amounts.forEach(a => totalAmount += parseFloat(a.value.replace(/,/g, "")) || 0);

        document.getElementById("totalPercent").innerText = totalPercent.toFixed(2) + "%";
        document.getElementById("progressBar").style.width = Math.min(totalPercent, 100) + "%";
        document.getElementById("progressBar").classList.toggle("bg-red-500", totalPercent > 100);
        document.getElementById("progressBar").classList.toggle("bg-indigo-500", totalPercent <= 100);

        const currentRemaining = remainingBudget - totalAmount;
        document.getElementById("remainingBudget").innerText = formatPeso(currentRemaining < 0 ? 0 : currentRemaining);
    }
</script>