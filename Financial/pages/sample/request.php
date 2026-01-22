<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Form</title>
    <?php include "../../static/head/header.php" ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9fafb;
            margin: 0;
        }

        .content {
            margin-left: 220px;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #111827;
        }

        .system-title {
            font-weight: normal;
            font-size: 16px;
            color: #6b7280;
        }

        .form-container {
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            margin: auto;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
            color: #374151;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            background: #f9fafb;
        }

        textarea {
            min-height: 120px;
            grid-column: span 2;
        }

        input[type="submit"] {
            background-color: #4F46E5;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            grid-column: span 2;
            transition: background-color 0.2s ease-in-out;
        }

        input[type="submit"]:hover {
            background-color: #4338ca;
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }

        .error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        .dropdown.active .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body>
    <?php include "../sidebar.php"; ?>

    <div class="form-container form-group">
        <div id="message" class="message hidden"></div>
        <form id="requestForm" enctype="multipart/form-data">
            <div>
                <label for="requestTitle">Request Title:</label>
                <input type="text" id="requestTitle" name="requestTitle" maxlength="500" required>
            </div>

            <div>
                <label for="allocationID">Title</label>
                <select name="allocationID" id="allocationID" required>
                    <option value="">SELECT TITLE ON ALLOCATION ID</option>
                </select>
            </div>

            <div>
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="Amount" min="0" step="0.01" required>
            </div>

            <div>
                <label for="requestedBy">Requested By:</label>
                <input type="text" id="requestedBy" name="Requested_by" maxlength="500" required>
            </div>

            <div>
                <label for="due">Due Date:</label>
                <input type="date" id="due" name="Due" required>
            </div>

            <div>
                <label for="documents">Add File:</label>
                <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>

            <div>
                <label for="purpose">Purpose:</label>
                <textarea id="purpose" name="Purpuse" required></textarea>
            </div>

            <input type="submit" value="Submit Request">
        </form>
    </div>

    <script src="<?php echo '../../static/js/filter.js';?>"></script>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        themeToggle?.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode', this.checked);
        });

        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const hamburger = document.getElementById('hamburger');
        const overlay = document.getElementById('overlay');

        hamburger?.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });

        overlay?.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                const parentDropdown = this.closest('.dropdown');
                parentDropdown.classList.toggle('active');
            });
        });

        const API_KEY = 'FinancialMalakas';
        const API_URL = 'https://finance.slatefreight-ph.com/api/request.php';
        const DEPARTMENT = 'Logistic2';
        const MAX_FILES = 5; 
        const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

        document.addEventListener('DOMContentLoaded', () => {
            fetchAllocations();
            document.getElementById('requestForm').addEventListener('submit', handleSubmit);
        });

        function fetchAllocations() {
            fetch(`${API_URL}?action=get_allocations&dept=${encodeURIComponent(DEPARTMENT)}`, {
                headers: {
                    'X-API-KEY': API_KEY
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const select = document.getElementById('allocationID');
                select.innerHTML = '<option value="">SELECT TITLE ON ALLOCATION ID</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.allocationID;
                    option.textContent = `${item.title} (${item.department})`;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching allocations:', error);
                showMessage('Failed to load allocations. Please try again.', 'error');
            });
        }

        function handleSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            const data = {
                requestTitle: formData.get('requestTitle'),
                allocationID: formData.get('allocationID'),
                Amount: parseFloat(formData.get('Amount')),
                Requested_by: formData.get('Requested_by'),
                Due: formData.get('Due'),
                Purpuse: formData.get('Purpuse'),
                documents: formData.getAll('documents[]')
            };

            if (!data.requestTitle || !data.allocationID || isNaN(data.Amount) || !data.Requested_by || !data.Due || !data.Purpuse) {
                showMessage('Please fill in all required fields.', 'error');
                return;
            }
            if (data.Amount <= 0) {
                showMessage('Amount must be a positive number.', 'error');
                return;
            }

            const files = document.getElementById('documents').files;
            if (files.length > MAX_FILES) {
                showMessage(`Maximum ${MAX_FILES} files allowed.`, 'error');
                return;
            }
            for (let file of files) {
                if (file.size > MAX_FILE_SIZE) {
                    showMessage(`File ${file.name} exceeds 5MB limit.`, 'error');
                    return;
                }
            }

            fetch(`${API_URL}?action=insert_request&dept=${encodeURIComponent(DEPARTMENT)}`, {
                method: 'POST',
                headers: {
                    'X-API-KEY': API_KEY
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.error || `HTTP error! Status: ${response.status}`); });
                }
                return response.json();
            })
            .then(result => {
                showMessage(result.message || 'Request sent successfully', 'success');
                form.reset();
                fetchAllocations();
            })
            .catch(error => {
                console.error('Error submitting request:', error);
                showMessage(error.message || 'Failed to submit request. Please try again.', 'error');
            });
        }

        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
            messageDiv.classList.remove('hidden');
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>
</html>