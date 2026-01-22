<?php
$dept = 'Logistic1';
//  eto nakabase to sa department na naka assign si user pero pansamantala ganyan muna
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Requests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 300px;
        }
        select, input, textarea {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
        }
        label {
            font-weight: bold;
            display: block;
        }
        optgroup {
            font-weight: bold;
        }
        table {
            border: 1px solid black;
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #000;
            color: white;
        }
        button {
            padding: 10px;
            margin-top: 10px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<form id="requestForm">
    <h3>Add New Request</h3>
    <label for="requestTitle">Request Title</label>
    <input type="text" id="requestTitle" name="requestTitle" required>
    
    <label for="Amount">Amount</label>
    <input type="number" id="Amount" name="Amount" step="0.01" required>
    
    <label for="Requested_by">Requested By</label>
    <input type="text" id="Requested_by" name="Requested_by" required>
    
    <label for="Due">Due Date</label>
    <input type="date" id="Due" name="Due" required>
    
    <label for="Purpose">Purpose</label>
    <textarea id="Purpose" name="Purpose" required></textarea>
    
    <label for="allocationID">Allocation ID</label>
    <select id="allocationID" name="allocationID" required>
        <option value="">SELECT TITLE ON ALLOCATION ID</option>
    </select>
    
    <button type="submit">Submit Request</button>
</form>

<div id="error" class="error"></div>

<table id="requestTable">
    <thead>
        <tr>
            <th>Title</th>
            <th>Approved Amount</th>
            <th>Requested By</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Purpose</th>
        </tr>
    </thead>
    <tbody id="requestTableBody"></tbody>
</table>
<input type="hidden" id="deptInput" value="<?php echo htmlspecialchars($dept); ?>">
<!-- https://finance.slatefreight-ph.com/api/ -->
<script>
    const apikey = "FinancialMalakas";
   const baseURL = "https://finance.slatefreight-ph.com/api/request.php";
async function loadAllocations(dept) {
    try {
        const response = await fetch(`${baseURL}?action=get_allocations&dept=${encodeURIComponent(dept)}`, {
            headers: { 'X-API-KEY': apikey }
        });
        if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
        const data = await response.json();
        
        const select = document.querySelector('#allocationID');
        const errorDiv = document.querySelector('#error');

        select.innerHTML = '<option value="">SELECT TITLE ON ALLOCATION ID</option>';
        errorDiv.innerHTML = '';

        const groupedData = data.reduce((acc, row) => {
            const dept = row.department || 'Other';
            if (!acc[dept]) acc[dept] = [];
            acc[dept].push(row);
            return acc;
        }, {});

        Object.keys(groupedData).forEach(dept => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = dept;
            groupedData[dept].forEach(row => {
                const option = document.createElement('option');
                option.value = row.allocationID;
                option.textContent = row.title;
                optgroup.appendChild(option);
            });
            select.appendChild(optgroup);
        });
    } catch (error) {
        document.querySelector('#error').textContent = 'Failed to load allocations: ' + error.message;
    }
}

async function loadRequests(dept) {
    try {
        const response = await fetch(`${baseURL}?action=get_requests&dept=${encodeURIComponent(dept)}`, {
            headers: { 'X-API-KEY': apikey }
        });
        if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
        const data = await response.json();
        
        const tableBody = document.querySelector('#requestTableBody');
        const errorDiv = document.querySelector('#error');

        tableBody.innerHTML = '';
        errorDiv.innerHTML = '';

        if (data.error) {
            errorDiv.textContent = data.error;
            return;
        }

        if (!data.length) {
            tableBody.innerHTML = '<tr><td colspan="7">No requests found for this department.</td></tr>';
            return;
        }
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.title || ''}</td>
                <td>${row.ApprovedAmount || ''}</td>
                <td>${row.Requested_by || ''}</td>
                <td>${row.Due || ''}</td>
                <td>${row.status || ''}</td>
                <td>${row.Remarks || ''}</td>
                <td>${row.Purpuse || ''}</td>
            `;
            tableBody.appendChild(tr);
        });
    } catch (error) {
        document.querySelector('#error').textContent = 'Failed to load requests: ' + error.message;
    }
}

async function submitRequest(event) {
    event.preventDefault();

    const dept = document.querySelector('#deptInput').value;
    const allocationIDValue = document.querySelector('#allocationID').value;
    const allocationID = parseInt(allocationIDValue, 10);

    if (isNaN(allocationID) || allocationID <= 0) {
        document.querySelector('#error').textContent = 'Please select a valid allocation ID';
        return;
    }

    const data = {
        requestTitle: document.querySelector('#requestTitle').value,
        Amount: parseFloat(document.querySelector('#Amount').value),
        Requested_by: document.querySelector('#Requested_by').value,
        Due: document.querySelector('#Due').value,
        Purpuse: document.querySelector('#Purpose').value,
        allocationID: allocationID
    };

    if (!data.requestTitle || !data.Amount || !data.Requested_by || !data.Due || 
        !data.Purpuse || !data.allocationID) {
        document.querySelector('#error').textContent = 'Please fill all fields';
        return;
    }

    if (isNaN(data.Amount) || data.Amount <= 0) {
        document.querySelector('#error').textContent = 'Amount must be a valid positive number';
        return;
    }

    try {
        const response = await fetch(`${baseURL}?action=insert_request&dept=${encodeURIComponent(dept)}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-KEY': apikey  
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (!response.ok) {
            document.querySelector('#error').textContent = result.error || `HTTP error: ${response.status}`;
            return;
        }
        document.querySelector('#error').textContent = result.message;
        document.getElementById('requestForm').reset();
        loadRequests(dept);
    } catch (error) {
        document.querySelector('#error').textContent = 'Failed to submit request: ' + error.message;
    }
}


window.onload = function() {
    document.getElementById('requestForm').addEventListener('submit', submitRequest);
    const dept = "<?php echo htmlspecialchars($dept); ?>";
    loadAllocations(dept);
    loadRequests(dept);
};
</script>
</body>
</html>