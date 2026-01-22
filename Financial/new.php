<script>
    async function budgetcreation() {
    const modal = document.getElementById('budgetModal');
    const openBtn = document.getElementById('toggleFormBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('budgetForm');
    const deptSelect = document.getElementById("deptNameSelect");
    const deptInput = document.getElementById("deptNameInput");
    const realDeptName = document.getElementById("realDeptName");
    const detailsInput = document.getElementById("budgetDetailsInput");
    const budgetYearSelect = document.getElementById("budgetYear");
    const budgetAmountInput = document.getElementById("budgetAmount");
    const addNewBtn = document.getElementById("addNewDeptBtn");
    const maxAllowedMsg = document.getElementById("maxAllowedMsg");
    const maxValueSpan = document.getElementById("maxValue");

    let totalGLCash = 0;
    let departmentsLoaded = false;
    let currentDepartments = [];

    // ==============================================
    // Modal Controls
    // ==============================================
    openBtn.onclick = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    closeBtn.onclick = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form.reset();
    };

    modal.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
        }
    };

    // ==============================================
    // Load fresh data for ANY year - NO CACHE for allocated amount
    // ==============================================
    async function loadYearData(year) {
        try {
            const res = await fetch(`${API}?year=${year}`);
            const json = await res.json();

            if (json.status !== "success") {
                console.warn(`API failed for year ${year}:`, json);
                return { allocated: 0, departments: [] };
            }

            const allocated = Number(json.totalAllocatedThisYear || 0);

            // Load departments only once (they are global)
            if (!departmentsLoaded && json.departments?.length > 0) {
                currentDepartments = json.departments;
                departmentsLoaded = true;

                // Populate dropdown
                deptSelect.innerHTML = `<option value="">-- Select Department --</option>` +
                    currentDepartments.map(d => 
                        `<option value="${d.DepartmentName}">${d.DepartmentName}</option>`
                    ).join('');
            }

            // Update global cash (should be same every time, but we take latest)
            totalGLCash = Number(json.cashOnHand || 0);

            console.log(`Year ${year}: Allocated = ${allocated}, Cash = ${totalGLCash}`);

            return { allocated, departments: currentDepartments };
        } catch (err) {
            console.error(`Fetch failed for year ${year}:`, err);
            return { allocated: 0, departments: [] };
        }
    }

    // Initial load (current year)
    const currentYear = new Date().getFullYear();
    await loadYearData(currentYear);

    // Populate years
    budgetYearSelect.innerHTML = '';
    for (let i = 0; i <= 5; i++) {
        const y = currentYear + i;
        budgetYearSelect.innerHTML += `<option value="${y}">${y}</option>`;
    }

    // ==============================================
    // Department auto-fill
    // ==============================================
    deptSelect.addEventListener('change', () => {
        const selected = deptSelect.value;
        realDeptName.value = selected;
        const dept = currentDepartments.find(d => d.DepartmentName === selected);
        if (dept?.DepartmentDetails) {
            detailsInput.value = dept.DepartmentDetails;
            detailsInput.readOnly = true;
        } else {
            detailsInput.value = '';
            detailsInput.readOnly = false;
        }
    });

    // Add New toggle
    addNewBtn.addEventListener('click', () => {
        deptSelect.classList.toggle('hidden');
        deptInput.classList.toggle('hidden');
        addNewBtn.textContent = deptSelect.classList.contains('hidden') 
            ? "Select Existing" 
            : "Add New Department";
        if (deptSelect.classList.contains('hidden')) {
            detailsInput.value = '';
            detailsInput.readOnly = false;
        } else {
            detailsInput.readOnly = true;
        }
    });

    // ==============================================
    // MAX ALLOWED — The Final Correct Logic
    // ==============================================
    async function updateMaxAllowed() {
    const selectedYear = parseInt(budgetYearSelect.value);
    if (!selectedYear) {
        maxAllowedMsg.classList.add('hidden');
        maxValueSpan.textContent = '0.00';
        return;
    }

    const { allocated } = await loadYearData(selectedYear);
    const maxAllowed = Math.max(0, totalGLCash - allocated);

    budgetAmountInput.max = maxAllowed;
    maxValueSpan.textContent = Number(maxAllowed).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // Auto-correct input
    let current = parseFloat(budgetAmountInput.value) || 0;
    if (current > maxAllowed) {
        budgetAmountInput.value = maxAllowed > 0 ? maxAllowed.toFixed(2) : '';
    }

    // Always show the message
    maxAllowedMsg.classList.remove('hidden');

    // Optional: nicer UX - change text when full amount is available
    if (maxAllowed >= totalGLCash || allocated === 0) {
        maxAllowedMsg.innerHTML = `Available cash: ₱<span id="maxValue">${maxValueSpan.textContent}</span>`;
        maxAllowedMsg.classList.add('text-green-600');
        maxAllowedMsg.classList.remove('text-gray-500');
    } else {
        maxAllowedMsg.innerHTML = `Maximum allowed: ₱<span id="maxValue">${maxValueSpan.textContent}</span>`;
        maxAllowedMsg.classList.add('text-gray-500');
        maxAllowedMsg.classList.remove('text-green-600');
    }
}

    budgetYearSelect.addEventListener('change', updateMaxAllowed);
    budgetAmountInput.addEventListener('input', updateMaxAllowed);

    // Initial calculation
    updateMaxAllowed();

    // ==============================================
    // Form submit
    // ==============================================
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        realDeptName.value = deptSelect.classList.contains('hidden')
            ? deptInput.value.trim()
            : deptSelect.value;

        if (!realDeptName.value) {
            alert("Please select or enter a department name.");
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch('savebudgetdept.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === "success") {
                alert('Budget successfully created!');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                form.reset();

                content?.();
                totalNumbers?.();
                loadAllocationSummary?.();
                loadFinancialYears?.();
            } else {
                alert('Error: ' + (result.message || 'Failed to create budget.'));
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('Network error. Please try again.');
        }
    });
}

document.addEventListener('DOMContentLoaded', budgetcreation);
</script>

<?php 

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get valid existing years
$validYearsStmt = $pdo->query("
    SELECT DISTINCT YEAR(DateValid) AS fy_year
    FROM budget.departmentbudget 
    WHERE status != 'Cancel'
    ORDER BY fy_year DESC
");
$validYears = $validYearsStmt->fetchAll(PDO::FETCH_COLUMN);

// Only correct if the requested year is invalid AND in the past (before earliest known year)
$minYear = $validYears ? min($validYears) : date('Y');
if ($year < $minYear && !empty($validYears)) {
    $year = $minYear;
}

// Now proceed — for future years ($year > max valid), totalAllocatedThisYear will naturally be 0
?>