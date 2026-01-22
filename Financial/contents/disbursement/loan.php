<?php if(!empty($successMessage)): ?>
<p class="text-green-600 font-medium text-center mb-4"><?php echo $successMessage; ?></p>
<?php endif; ?>

<?php if(!empty($errorMessage)): ?>
<p class="text-red-600 font-medium text-center mb-4"><?php echo $errorMessage; ?></p>
<?php endif; ?>


<div class="flex items-center justify-center p-4 w-full">
<div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-6 ">

    <h1 class="text-2xl font-bold mb-6 text-gray-800 text-center">Loan Request</h1>

    <!-- Tabs -->
    <div class="flex mb-6 border-b border-gray-200">
        <button id="employeeTab" class="px-4 py-2 font-medium text-blue-600 border-b-2 border-blue-600 focus:outline-none">Employee Request</button>
        <button id="customerTab" class="px-4 py-2 font-medium text-gray-600 hover:text-blue-600 focus:outline-none">Customer Request</button>
    </div>

    
    <div id="employeeForm" class="space-y-4">
       <form id="employeeForm" class="space-y-4" method="POST">
    <div class="relative">
        <label class="block text-gray-700 font-medium mb-1">Employee ID</label>
        <input type="text" name="empId" id="empId" placeholder="Type Employee ID" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        <ul id="empSuggestions" class="absolute bg-white border border-gray-300 mt-1 w-full rounded-lg shadow-md hidden z-10"></ul>
    </div>

    <div>
        <label class="block text-gray-700 font-medium mb-1">Employee Name</label>
        <input type="text" name="empName" id="empName" placeholder="Full name" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" readonly>
    </div>

    <div>
        <label class="block text-gray-700 font-medium mb-1">Title</label>
        <input type="text" name="empTitle" id="empTitle" placeholder="Enter Title" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
        <label class="block text-gray-700 font-medium mb-1">Loan Amount</label>
        <input type="number" name="empAmount" id="empAmount" placeholder="Loan amount" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
        <label class="block text-gray-700 font-medium mb-1">Due Date</label>
        <input type="date" name="empTerm" id="empTerm" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
        <label class="block text-gray-700 font-medium mb-1">Purpose</label>
        <textarea name="empPurpose" id="empPurpose" rows="3" placeholder="Purpose of loan" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
    </div>

    <button type="submit" name="submit_employee" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Submit Request</button>
</form>
        <p id="empSuccess" class="text-green-600 font-medium text-center hidden">Employee loan request submitted!</p>
    </div>

    <!-- Customer Request Form -->
    <div id="customerForm" class="space-y-4 hidden">
        <div>
            <label class="block text-gray-700 font-medium mb-1">Customer ID</label>
            <input type="text" id="custId" placeholder="Customer ID"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Customer Name</label>
            <input type="text" id="custName" placeholder="Full name"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Loan Amount</label>
            <input type="number" id="custAmount" placeholder="Loan amount"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Loan Term</label>
            <select id="custTerm" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Select term</option>
                <option value="1 month">1 Month</option>
                <option value="3 months">3 Months</option>
                <option value="6 months">6 Months</option>
                <option value="12 months">12 Months</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Purpose</label>
            <textarea id="custPurpose" rows="3" placeholder="Purpose of loan"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
        </div>

        <button id="submitCustomer" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Submit Request</button>
        <p id="custSuccess" class="text-green-600 font-medium text-center hidden">Customer loan request submitted!</p>
    </div>

</div>
</div>

<script>
const employeeTab = document.getElementById('employeeTab');
const customerTab = document.getElementById('customerTab');
const employeeForm = document.getElementById('employeeForm');
const customerForm = document.getElementById('customerForm');

employeeTab.addEventListener('click', () => {
    employeeForm.classList.remove('hidden');
    customerForm.classList.add('hidden');
    employeeTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
    customerTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
    customerTab.classList.add('text-gray-600');
});

customerTab.addEventListener('click', () => {
    customerForm.classList.remove('hidden');
    employeeForm.classList.add('hidden');
    customerTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
    employeeTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
    employeeTab.classList.add('text-gray-600');
});

// Employee autocomplete
const empIdInput = document.getElementById('empId');
const empNameInput = document.getElementById('empName');
const empSuggestions = document.getElementById('empSuggestions');

empIdInput.addEventListener('input', () => {
    const query = empIdInput.value.trim();
    empSuggestions.innerHTML = '';
    if (!query) {
        empSuggestions.classList.add('hidden');
        return;
    }

    fetch(`${window.location.pathname}?ajax=1&query=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
        if(data.length === 0) { empSuggestions.classList.add('hidden'); return; }

        data.forEach(emp => {
            const li = document.createElement('li');
            li.textContent = `${emp.EmployeeID} - ${emp.EmployeeName}`;
            li.classList.add('px-4','py-2','hover:bg-blue-100','cursor-pointer');
            li.addEventListener('click', () => {
                empIdInput.value = emp.EmployeeID;
                empNameInput.value = emp.EmployeeName;
                empSuggestions.classList.add('hidden');
            });
            empSuggestions.appendChild(li);
        });
        empSuggestions.classList.remove('hidden');
    })
    .catch(err => console.error(err));
});

document.addEventListener('click', (e) => {
    if(!empIdInput.contains(e.target) && !empSuggestions.contains(e.target)) {
        empSuggestions.classList.add('hidden');
    }
});

// Submit Employee Form
const submitEmployee = document.getElementById('submitEmployee');
const empSuccess = document.getElementById('empSuccess');
submitEmployee.addEventListener('click', () => {
    const id = document.getElementById('empId').value.trim();
    const name = document.getElementById('empName').value.trim();
    const title = document.getElementById('empTitle').value.trim();
    const amount = document.getElementById('empAmount').value.trim();
    const term = document.getElementById('empTerm').value.trim();
    const purpose = document.getElementById('empPurpose').value.trim();
    if(!id || !name || !title || !amount || !term || !purpose) return alert('Please fill all fields.');
    empSuccess.classList.remove('hidden');

    // Clear form
    document.getElementById('empId').value = '';
    document.getElementById('empName').value = '';
    document.getElementById('empTitle').value = '';
    document.getElementById('empAmount').value = '';
    document.getElementById('empTerm').value = '';
    document.getElementById('empPurpose').value = '';
});

// Submit Customer Form
const submitCustomer = document.getElementById('submitCustomer');
const custSuccess = document.getElementById('custSuccess');
submitCustomer.addEventListener('click', () => {
    const id = document.getElementById('custId').value.trim();
    const name = document.getElementById('custName').value.trim();
    const amount = document.getElementById('custAmount').value.trim();
    const term = document.getElementById('custTerm').value.trim();
    const purpose = document.getElementById('custPurpose').value.trim();
    if(!id || !name || !amount || !term || !purpose) return alert('Please fill all fields.');
    custSuccess.classList.remove('hidden');

    // Clear form
    document.getElementById('custId').value = '';
    document.getElementById('custName').value = '';
    document.getElementById('custAmount').value = '';
    document.getElementById('custTerm').value = '';
    document.getElementById('custPurpose').value = '';
});
</script>

