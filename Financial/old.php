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
let jsonData = null;

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

    try {
        const res = await fetch(API);  
        const json = await res.json();
        if (json.status === "success") {
            const departments = json.departments || [];  
            const totalGLCash = json.cashOnHand || 0;
            const yearSums = json.yearSums || {};  
            const totalbudget = departments.TotalBudget || 0;
            const totalallocate = departments.TotalAllocated || 0;


            deptSelect.innerHTML = `<option value="">-- Select Department --</option>` +
                departments.map(dept => `<option value="${dept.DepartmentName}">${dept.DepartmentName}</option>`).join('');  // Fixed: departments.map()

            const currentYear = new Date().getFullYear();
            budgetYearSelect.innerHTML = '';
            for (let i = 0; i <= 5; i++) {
                const year = currentYear + i;
                budgetYearSelect.innerHTML += `<option value="${year}">${year}</option>`;
            }

            maxValueSpan.textContent = Number(totalGLCash).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

    
            deptSelect.addEventListener('change', () => {
                const selected = deptSelect.value;
                realDeptName.value = selected;
                const deptDetail = departments.find(d => d.DepartmentName === selected);  // Fixed: d.DepartmentName
                if (deptDetail && deptDetail.DepartmentDetails) {
                    detailsInput.value = deptDetail.DepartmentDetails;
                    detailsInput.readOnly = true;
                } else {
                    detailsInput.value = '';
                    detailsInput.readOnly = false;
                }
            });

       
            addNewBtn.addEventListener('click', () => {
                if (deptSelect.classList.contains('hidden')) {
                    deptSelect.classList.remove('hidden');
                    deptInput.classList.add('hidden');
                    addNewBtn.textContent = "Add New Department";
                    detailsInput.readOnly = true;
                } else {
                    deptSelect.classList.add('hidden');
                    deptInput.classList.remove('hidden');
                    addNewBtn.textContent = "Select Existing";
                    detailsInput.value = '';
                    detailsInput.readOnly = false;
                    realDeptName.value = '';
                }
            });

         
         function validateBudgetAmount() {
    const year = parseInt(budgetYearSelect.value);
    if (!year) {
        maxAllowedMsg.classList.add('hidden');
        budgetAmountInput.max = '';
        maxValueSpan.textContent = '0.00';
        return;
    }

    // Get total already used/allocated in this year
    const alreadyAllocated = json.totalAllocatedThisYear || 0;  // from API
    const currentCash = json.cashOnHand || 0;

    const maxAllowed = Math.max(0, currentCash - alreadyAllocated);

    budgetAmountInput.max = maxAllowed;
    maxValueSpan.textContent = Number(maxAllowed).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // Auto-correct input if too high
    let currentInput = parseFloat(budgetAmountInput.value) || 0;
    if (currentInput > maxAllowed) {
        budgetAmountInput.value = maxAllowed > 0 ? maxAllowed.toFixed(2) : '';
    }

    // Show/hide message
    maxAllowedMsg.classList.toggle('hidden', maxAllowed <= 0);
}
            budgetYearSelect.addEventListener('change', validateBudgetAmount);
            budgetAmountInput.addEventListener('input', validateBudgetAmount);
            validateBudgetAmount();

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
                        form.reset();
                        
                        content();
                        totalNumbers();
                        loadAllocationSummary();
                        loadFinancialYears();
                        
                    } else {
                        alert('Error: ' + (result.message || 'Failed to create budget.'));
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert('Network error. Please try again.');
                }
            });
        }
    } catch (error) {
        console.error("Error loading budget data:", error);
    }
}

document.addEventListener('DOMContentLoaded', budgetcreation);
</script>

<?php 

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');


$validYearsStmt = $pdo->query("
    SELECT DISTINCT YEAR(DateValid) AS fy_year
    FROM budget.departmentbudget 
    WHERE status != 'Cancel'
    ORDER BY fy_year DESC
");
$validYears = $validYearsStmt->fetchAll(PDO::FETCH_COLUMN);


if (!in_array($year, $validYears) && !empty($validYears)) {
    $year = $validYears[0];
}
?>