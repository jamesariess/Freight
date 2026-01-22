<div class="card">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-check-circle text-primary-color mr-2"></i>
            Payment Release Section
        </h2>
    </div>

    <div id="approvalCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
</div>

<div id="detailsModal" 
     class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     onclick="outsideClick(event, 'detailsModal')">
  
  <div class="bg-white rounded-2xl shadow-2xl w-[90vw] max-w-7xl p-8 relative overflow-y-auto max-h-[90vh]"
       onclick="event.stopPropagation()">

    <div class="flex items-center justify-between mb-6 border-b pb-3">
      <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-info-circle text-blue-600"></i>
        Request Details
      </h2>
      <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
              onclick="closeModal()">&times;</button>
    </div>

    <div id="modalContent" class="grid grid-cols-2 gap-x-6 gap-y-3 text-gray-700 text-sm">
    </div>

    <div id="modalButtons" class="mt-6 flex justify-end space-x-2">
    </div>

  </div>
</div>

<div id="releaseConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" onclick="outsideClick(event, 'releaseConfirmModal')">
    <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative max-w-md" onclick="event.stopPropagation()">
        <button class="absolute top-2 right-2 text-gray-500 hover:text-black" onclick="closeModal('releaseConfirmModal')">&times;</button>
        <h2 class="text-lg font-bold mb-4">Confirm Payment Release</h2>
        <input type="hidden" id="releaseRequestId">
        <div class="space-y-4">
            <p class="text-gray-700">Are you sure you want to confirm the payment release for this request?</p>
            <div>
                <label for="paymentMethod" class="block text-sm font-medium text-gray-700">Select Payment Method</label>
                <select name="paymentMethod" id="paymentMethod" class="w-full border border-gray-300 rounded px-3 py-2 mt-1">
                    <option value="PettyCash">Petty Cash</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>
            <div id="bankSelection" class="hidden">
                <label for="bankName" class="block text-sm font-medium text-gray-700">Select Bank</label>
                <select name="bankName" id="bankName" class="w-full border border-gray-300 rounded px-3 py-2 mt-1">
                    <option value="">Select a bank</option>
                </select>
            </div>
            <div id="receiptSection" class="hidden">
                <label for="receipt" class="block text-sm font-medium text-gray-700">Bank Transfer Receipt</label>
                <input type="file" name="receipt" id="receipt" class="w-full border border-gray-300 rounded px-3 py-2 mt-1" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
        <div class="flex justify-end space-x-3 mt-4">
            <button type="button" class="text-white px-4 py-2 bg-blue-500 rounded-lg hover:bg-blue-600" onclick="closeModal('releaseConfirmModal')">Cancel</button>
            <button type="button" id="confirmReleaseBtn" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Confirm Release</button>
        </div>
    </div>
</div>

<script>
    let requests = <?php echo json_encode($requests); ?>;
    const container = document.getElementById("approvalCards");
    const modal = document.getElementById("detailsModal");
    const modalContent = document.getElementById("modalContent");
    const modalButtons = document.getElementById("modalButtons");

    function renderCards() {
        container.innerHTML = '';
        if (requests.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-10 text-gray-400">
                    <i class="fas fa-money-check-alt text-6xl mb-4"></i>
                    <h4 class="text-lg font-medium">No Payments to Release</h4>
                    <p>All pending payments have been released. Good job!</p>
                </div>
            `;
            return;
        }

        requests.forEach(req => {
            if (req.status === "Paid") return;

            let cardClasses = "";
            let iconClass = "fa-file-alt";
            let statusClass = req.status === 'Approved' ? 'bg-success-color text-white' : 'bg-info-color text-white';

            switch (req.Name.toLowerCase()) {
                case "hr":
                    cardClasses = "purple";
                    iconClass = "fa-users";
                    break;
                case "maintenance":
                case "operations":
                    cardClasses = "green";
                    iconClass = "fa-truck";
                    break;
                case "finance":
                    cardClasses = "info";
                    iconClass = "fa-briefcase";
                    break;
                case "general services":
                    cardClasses = "red";
                    iconClass = "fa-gas-pump";
                    break;
                default:
                    cardClasses = "primary";
                    iconClass = "fa-layer-group";
            }

            let buttonHTML = "";
            let amountDisplay = req.status === "Approved" ? req.ApprovedAmount : null;

            if (req.status === "Approved") {
                buttonHTML = `<button class="btn btn-primary" onclick="openReleaseConfirmModal(${req.requestID})">Confirm Release</button>`;
            } else if (req.status === "Pending") {
                buttonHTML = `<button class="btn btn-secondary opacity-70 cursor-not-allowed">Waiting for Approval</button>`;
            }
            container.innerHTML += `
                <div class="quick-stat-card ${cardClasses}" data-id="${req.requestID}" role="article" aria-labelledby="card-title-${req.requestID}">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas ${iconClass} text-2xl text-${cardClasses}-color"></i>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${req.status === 'Approved' ? 'bg-green-300 text-green-800' : 'bg-yellow-300 text-yellow-800'}">
                            ${req.status}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold mb-2 capitalize" id="card-title-${req.requestID}">${req.Name} Department</h3>
                    <h4 class="text-md font-semibold mb-4 capitalize">${req.accountName}</h4>
                    <div class="text-sm space-y-2">
                        <p><span class="font-medium">ID:</span> REQ-${req.requestID}</p>
                        <p><span class="font-medium">Title:</span> ${req.requestTitle}</p>
                        <p><span class="font-medium">Amount:</span> ${amountDisplay != null ? `₱${Number(amountDisplay).toLocaleString()}` : 'Waiting For Approved Amount'}</p>
                        <p><span class="font-medium">Requested By:</span> ${req.Requested_by}</p>
                    </div>
                    <div class="flex mt-6 space-x-3">
                        ${buttonHTML}
                        <button class="text-primary-color text-sm font-medium underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" onclick="viewDetails(${req.requestID})" aria-label="View details for request ${req.requestID}">
                            Details
                        </button>
                    </div>
                </div>
            `;
        });
    }

    function formatDate(dateString, withTime = false) {
        if (!dateString) return "N/A";
        const date = new Date(dateString);
        const options = {
            year: "numeric",
            month: "long",
            day: "numeric",
        };
        if (withTime) {
            options.hour = "numeric";
            options.minute = "2-digit";
            options.second = "2-digit";
            options.hour12 = true;
        }
        return date.toLocaleDateString("en-US", options);
    }

    // Initial render
    renderCards();

    // Polling for automatic updates
    setInterval(() => {
        fetch('?fetch=requests')
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! Status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (JSON.stringify(requests) !== JSON.stringify(data)) {
                    requests = data;
                    renderCards();
                }
            })
            .catch(err => console.error('Polling error:', err));
    }, 5000);

    function openReleaseConfirmModal(id) {
        document.getElementById('releaseRequestId').value = id;
        document.getElementById('releaseConfirmModal').classList.remove('hidden');
        document.getElementById('bankSelection').classList.add('hidden');
        document.getElementById('receiptSection').classList.add('hidden');
        document.getElementById('paymentMethod').value = 'PettyCash';
        document.getElementById('receipt').value = ''; // Reset file input
        fetchBankNames();
    }

    function fetchBankNames() {
        fetch('../../crud/disbursement/paymentmethod.php')
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! Status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                const bankSelect = document.getElementById('bankName');
                bankSelect.innerHTML = '<option value="">Select a bank</option>';
                if (data.success === false) {
                    console.warn(data.message);
                    alert(data.message || 'No banks available');
                    return;
                }
                data.forEach(bank => {
                    bankSelect.innerHTML += `<option value="${bank.bankID}">${bank.bankName}</option>`;
                });
            })
            .catch(err => {
                console.error('Error fetching banks:', err);
                alert('Failed to load bank names. Please try again.');
            });
    }

    document.getElementById('paymentMethod').addEventListener('change', function() {
        const bankSelection = document.getElementById('bankSelection');
        const receiptSection = document.getElementById('receiptSection');
        if (this.value === 'bank') {
            bankSelection.classList.remove('hidden');
            receiptSection.classList.remove('hidden');
            fetchBankNames();
        } else {
            bankSelection.classList.add('hidden');
            receiptSection.classList.add('hidden');
        }
    });

    document.getElementById('confirmReleaseBtn').addEventListener('click', function() {
        const id = document.getElementById('releaseRequestId').value;
        const paymentMethod = document.getElementById('paymentMethod').value;
        const bankName = document.getElementById('bankName').value;
        const receipt = document.getElementById('receipt').files[0];

        if (paymentMethod === 'bank' && !bankName) {
            alert('Please select a bank for bank transfer.');
            return;
        }

        const formData = new FormData();
        formData.append('data', JSON.stringify({
            requestID: id,
            paymentMethod: paymentMethod,
            bankName: paymentMethod === 'bank' ? bankName : null
        }));
        if (paymentMethod === 'bank' && receipt) {
            formData.append('receipt', receipt);
        }

        closeModal('releaseConfirmModal');

        fetch("", {
            method: "POST",
            body: formData
        })
        .then(res => {
            console.log('Response status:', res.status);
            console.log('Response headers:', res.headers.get('Content-Type'));
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP error! Status: ${res.status}, Response: ${text}`);
                });
            }
            return res.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                requests = data.requests || requests;
                renderCards();
                alert('Payment successfully released!');
            } else {
                alert(data.error || 'Failed to confirm payment release.');
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('An error occurred: ' + err.message);
        });
    });

    function viewDetails(id) {
        const req = requests.find(r => Number(r.requestID) === Number(id));
        if (!req) return;

        let amountToShow = req.status === "Approved" ? req.ApprovedAmount : null;

        modalContent.innerHTML = `
            <p><span class="block text-gray-500 text-xs uppercase font-semibold">Request ID</span>
            <span class="text-gray-800 font-medium">REQ-${req.requestID}</span></p>
            <p><span class="block text-gray-500 text-xs uppercase font-semibold">Title</span>
            <span class="text-gray-800 font-medium">${req.requestTitle}</span></p>
            <p><span class="font-semibold w-36">Account Name:</span> ${req.accountName}</p>
            <p><span class="font-semibold w-36">Amount:</span> ${
                amountToShow != null
                    ? `<span class="text-green-700 font-semibold">₱${Number(amountToShow).toLocaleString()}</span>`
                    : '<span class="text-yellow-700 italic">Waiting for Approved Amount</span>'
            }</p>
            <p><span class="font-semibold w-36">Requested By:</span> ${req.Requested_by}</p>
            <p><span class="font-semibold w-36">Purpose:</span> ${req.Purpuse}</p>
            <p><span class="font-semibold w-36">Due:</span> ${formatDate(req.Due)}</p>
            <p><span class="font-semibold w-36">Request Date:</span> ${formatDate(req.date, true)}</p>
        
            <div class="col-span-2 mt-5 border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                    <i class="fas fa-file-alt text-blue-600"></i> Attached Documents
                </h3>
                ${
                    req.documents && req.documents.length > 0
  ? req.documents.map(doc => {
      const fileName = doc.split('/').pop();
      const ext = fileName.split('.').pop().toLowerCase();
      if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
        return `
          <a href="${doc}" target="_blank" class="block">
            <img src="${doc}" alt="Document" class="w-full h-full g-100 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition cursor-pointer" />
          </a>
        `;
      } else if (ext === 'pdf') {
        return `
          <div class="relative">
            <iframe src="${doc}" class="w-full h-80 border border-gray-200 rounded-lg" title="PDF Document"></iframe>
            <a href="${doc}" target="_blank" class="absolute bottom-2 right-2 inline-block bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Open New Tab</a>
          </div>
        `;
      } else {
        return `<a href="${doc}" target="_blank" class="block text-blue-600 hover:text-blue-800 underline">${fileName}</a>`;
      }
    }).join('')
  : `<p class="text-gray-500 italic text-sm">No attached documents.</p>`

                }
            </div>
        `;

        let approvedButton = `<button class="btn btn-primary" onclick="openReleaseConfirmModal(${req.requestID})">Confirm Release</button>`;
        let waitingButton = `<button class="btn btn-secondary opacity-70 cursor-not-allowed">Waiting for Approval</button>`;
        let closeButton = `<button onclick="closeModal()" class="btn btn-secondary">Close</button>`;

        if (req.status === "Approved") {
            modalButtons.innerHTML = `${approvedButton} ${closeButton}`;
        } else {
            modalButtons.innerHTML = `${waitingButton} ${closeButton}`;
        }

        modal.classList.remove("hidden");
    }

    function closeModal(modalId = 'detailsModal') {
        document.getElementById(modalId).classList.add('hidden');
    }

    function outsideClick(event, modalId) {
        if (event.target.id === modalId) {
            closeModal(modalId);
        }
    }
</script>