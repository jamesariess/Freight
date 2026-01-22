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

    // Form submissions
    const submitEmployee = document.getElementById('submitEmployee');
    const empSuccess = document.getElementById('empSuccess');
    submitEmployee.addEventListener('click', () => {
        const name = document.getElementById('empName').value.trim();
        const id = document.getElementById('empId').value.trim();
        const amount = document.getElementById('empAmount').value.trim();
        const term = document.getElementById('empTerm').value.trim();
        const purpose = document.getElementById('empPurpose').value.trim();
        if (!name || !id || !amount || !term || !purpose) return alert('Please fill all fields.');
        empSuccess.classList.remove('hidden');
        document.getElementById('empName').value = '';
        document.getElementById('empId').value = '';
        document.getElementById('empAmount').value = '';
        document.getElementById('empTerm').value = '';
        document.getElementById('empPurpose').value = '';
    });

    const submitCustomer = document.getElementById('submitCustomer');
    const custSuccess = document.getElementById('custSuccess');
    submitCustomer.addEventListener('click', () => {
        const name = document.getElementById('custName').value.trim();
        const id = document.getElementById('custId').value.trim();
        const amount = document.getElementById('custAmount').value.trim();
        const term = document.getElementById('custTerm').value.trim();
        const purpose = document.getElementById('custPurpose').value.trim();
        if (!name || !id || !amount || !term || !purpose) return alert('Please fill all fields.');
        custSuccess.classList.remove('hidden');
        document.getElementById('custName').value = '';
        document.getElementById('custId').value = '';
        document.getElementById('custAmount').value = '';
        document.getElementById('custTerm').value = '';
        document.getElementById('custPurpose').value = '';
    });