const API = "budget.php";
const DETAILS_API = "budget2.php";
const revealTimers = new Map(); 
let currentDeptID = '';
let currentYear = '';

function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(value);
}

async function content() {
    try {
        const res = await fetch(`${API}?year=${currentYear}`);
        const json = await res.json();

        const container = document.getElementById('budgetCardsContainer');

        if (json.status === "success" && json.overalldata && json.overalldata.length > 0) {
            console.log(json.overalldata);
            container.innerHTML = json.overalldata.map(dept => {
                const utilization = dept.utilizationPercentage || 0;
                const remaining = parseFloat(dept.RemainingBudget || 0);
                const used = parseFloat(dept.UsedBudget || 0);
                const total = parseFloat(dept.totalBudget || 0);

                const approval = dept.approval || 'Pending';
                const isPending = approval === 'Pending';
                const isApproved = approval === 'Approved';

                const badgeColor = approval === 'Approved'
                ? 'bg-green-100 text-green-700'
               : approval === 'Rejected'
               ? 'bg-red-100 text-red-700'
               : 'bg-yellow-100 text-yellow-700';


                const year = dept.year || json.year;

                const totalFormatted = '₱' + Number(total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const usedFormatted = '₱' + Number(used).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const remainingFormatted = '₱' + Number(remaining).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                return `
                <div class="border border-gray-200 p-6 mb-8 rounded-xl shadow-sm bg-white">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h5 class="text-lg font-semibold text-gray-900">Budget Details</h5>
                            <p class="text-sm text-gray-600">${dept.departmentName || 'Unknown Department'}</p>
                            <p class="text-sm text-gray-500">Year ${year}</p>
                        </div>
                        
                      ${isApproved ? `
                    <button class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition" 
                            onclick="budgetDetails('${dept.Deptbudget}', ${year})">
                        View Details →
                    </button>
                    ` : ''}
                    </div>

                   <div class="grid grid-cols-3 gap-8 mb-8">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Total Budget</p>
                            <p class="text-2xl font-bold text-gray-900 masked cursor-pointer"
                               data-value="${totalFormatted}"
                               onclick="toggleMask(this)">****</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Allocated / Used</p>
                            <p class="text-2xl font-bold text-blue-600 masked cursor-pointer"
                               data-value="${usedFormatted}"
                               onclick="toggleMask(this)">****</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Remaining</p>
                            <p class="text-2xl font-bold text-green-600 masked cursor-pointer"
                               data-value="${remainingFormatted}"
                               onclick="toggleMask(this)">****</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-2">
                            <h6 class="text-sm font-medium text-gray-700">Budget Utilization</h6>
                            <span class="text-sm font-medium text-gray-600">${utilization}%</span>
                        </div>
                        <div class="w-full h-4 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-700"
                                 style="width: ${utilization}%;"></div>
                        </div>
                    </div>
                 <div class="flex items-center justify-between mt-6">
             <span class="px-3 py-1 rounded-full text-xs font-semibold ${badgeColor}">
              ${approval}
            </span>

           ${isPending ? `
         <div class="flex gap-3">
          <button
            onclick="updateApproval(${dept.Deptbudget}, 'Approved')"
            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            Approve
         </button>

         <button
            onclick="updateApproval(${dept.Deptbudget}, 'Rejected')"
            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            Reject
         </button>
             </div>
               ` : `
             <span class="text-sm text-gray-400 italic">
        Action completed
              </span>
                 `}
         </div>


                </div>`;
            }).join('');

              document.querySelectorAll('#budgetCardsContainer .masked').forEach(el => {
                el.addEventListener('dblclick', (e) => {
                    if (el.dataset.visible === "true") {
                        toggleMask(el);
                        e.stopPropagation();
                    }
                });
            });
        } else {


            container.innerHTML = `
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">No budget data available for ${json.year || 'selected year'}.</p>
                </div>`;
        }
    } catch (error) {
        console.error("Error loading budget cards:", error);
        document.getElementById('budgetCardsContainer').innerHTML = `
            <div class="text-center py-12 text-red-600">
                Failed to load budget data. Please try again later.
            </div>`;
    }
}
async function updateApproval(deptBudgetID, approval) {
    if (!confirm(`Are you sure you want to ${approval.toLowerCase()} this budget?`)) return;

    try {
        const res = await fetch('savebudget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'updateApproval',
                deptBudgetID: deptBudgetID,
                approval: approval
            })
        });

        const json = await res.json();

        if (json.status === 'success') {
            alert(json.message);
            content(); 
        } else {
            alert(json.message || 'Failed to update budget');
        }

    } catch (err) {
        console.error(err);
        alert('Server error');
    }
}

function setMaskedValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.dataset.value = formatCurrency(value);
    el.textContent = "****";
    el.dataset.visible = "false";
}

function toggleMask(el) {
    const isVisible = el.dataset.visible === "true";
    if (isVisible) {
        el.textContent = "****";
        el.dataset.visible = "false";

        if (revealTimers.has(el)) {
            clearTimeout(revealTimers.get(el));
            revealTimers.delete(el);
        }
        return;
    }
    el.textContent = el.dataset.value;
    el.dataset.visible = "true";

    const timer = setTimeout(() => {
        el.textContent = "****";
        el.dataset.visible = "false";
        revealTimers.delete(el);
    }, 10000);

    revealTimers.set(el, timer);
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".masked").forEach(el => {
        el.addEventListener("click", () => toggleMask(el));
     
        el.addEventListener("dblclick", () => {
            if (el.dataset.visible === "true") {
                toggleMask(el); 
            }
        });
    });
});

async function totalNumbers() {
    try {
        const res = await fetch(`${API}?year=${currentYear}`);
        const json = await res.json();
      

        if (json.status === "success") {
            setMaskedValue("totalBudgets", json.data.totalBudget);
            setMaskedValue("approvedBudgets", json.data.totalApproved);
            setMaskedValue("cancelledBudgets", json.data.totalCancelled);
            setMaskedValue("Cash", json.cashOnHand);

            document.getElementById("totalDepartments").textContent =
                json.data.totalDepartment;
        }
    } catch (err) {
        console.error(err);
    }
}

async function budgetDetails(Deptbudget, year) {
    currentDeptID = Deptbudget;
    currentYear = year;  
    const noBudgetModal = document.getElementById('noBudgetModal');
    try {
        const res = await fetch(`${DETAILS_API}?deptID=${currentDeptID}&year=${currentYear}`);
        const json = await res.json();
    

        if (json.status === "success" && json.overalldata && json.overalldata.length > 0) {
            console.log("inside",json.overalldata);
            const detailsContainer = document.getElementById('budgetDetailsModal');

    
            
            detailsContainer.classList.add('active');
            document.body.style.overflow = 'hidden';
            const formatCurrency = (amount) => '₱' + Number(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            const headerDept = json.overalldata[0].departmentName || 'Department';
            const headerYear = json.overalldata[0].year || currentYear;

            document.getElementById('budgetDetails').innerHTML = `
               <div style="padding: 24px; background: white; border-radius: 16px;">
              <h2 style="font-size: 28px; font-weight: bold; margin-bottom: 24px; text-align: center;">
                Budget Details for ${headerDept} || ${headerYear}
               </h2>
                    <table>
                        <thead>
                            <tr style="background: #f3f4f6; text-align: left;">
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Account Name</th>
                                 <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Details</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Q1</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Q2</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Q3</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Q4</th>
                               
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Total Budget Allocated</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Used Budget</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Remaining</th>
                                <th style="padding: 16px; border-bottom: 2px solid #e5e7eb;">Utilization %</th>
                            </tr>
                        </thead>
                        <tbody>
                          ${json.overalldata.map(item => `
        <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="padding: 16px;">${item.AllocatedName || 'N/A'}</td>
                <td style="padding:16px;">${item.Details}</td>
        
        <td onclick="showQuarterModal(1, '${item.allocationID}', '${item.AllocatedName.replace(/'/g,"\\'")}')" 
            style="padding:16px; cursor:pointer; color:#2563eb; text-decoration:underline;">
            ${formatCurrency(item.Q1 || 0)}
        </td>
        <td onclick="showQuarterModal(2, '${item.allocationID}', '${item.AllocatedName.replace(/'/g,"\\'")}')" 
            style="padding:16px; cursor:pointer; color:#2563eb; text-decoration:underline;">
            ${formatCurrency(item.Q2 || 0)}
        </td>
        <td onclick="showQuarterModal(3, '${item.allocationID}', '${item.AllocatedName.replace(/'/g,"\\'")}')" 
            style="padding:16px; cursor:pointer; color:#2563eb; text-decoration:underline;">
            ${formatCurrency(item.Q3 || 0)}
        </td>
        <td onclick="showQuarterModal(4, '${item.allocationID}', '${item.AllocatedName.replace(/'/g,"\\'")}')" 
            style="padding:16px; cursor:pointer; color:#2563eb; text-decoration:underline;">
            ${formatCurrency(item.Q4 || 0)}
        </td>


        <td style="padding:16px; font-weight:600;">${formatCurrency(item.totalbudget)}</td>
        <td style="padding:16px; color:#3b82f6;">${formatCurrency(item.UsedBudget)}</td>
        <td style="padding:16px; color:#10b981;">${formatCurrency(item.RemainingBudget)}</td>
        <td style="padding:16px;">
            <span style="font-weight:600;">${item.utilizationPercentages || 0}%</span>
            <div style="margin-top:8px;width:100%;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:${item.utilizationPercentages || 0}%;background:linear-gradient(to right, #3b82f6, #6366f1);"></div>
            </div>
        </td>
    </tr>
`).join('')}
                        </tbody>
                    </table>
                </div>`;

            

        } else {
          
            noBudgetModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            console.log("No budget found for department ID:", currentDeptID, "and year:", currentYear);
            const allocateBtn = document.getElementById('btnAllocateBudget');
            if (allocateBtn) {
                allocateBtn.onclick = function() {
                    openFullAllocationModal(currentDeptID, currentYear);
                    noBudgetModal.classList.remove('active');
                    document.body.style.overflow = '';
                };
            }
        }
    } catch (error) {
        console.error("Error fetching budget details:", error);
        noBudgetModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}
async function showQuarterModal(quarter, allocationID, accountName) {
    const modalId = 'quarterDetailsModal';
    let modal = document.getElementById(modalId);

    if (!modal) {
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal-overlay';        
        modal.innerHTML = `
            <div class="modal-content">
                <button class="close-btn" 
                        onclick="document.getElementById('${modalId}').classList.remove('active'); 
                                 document.body.style.overflow='';">
                    ×
                </button>
                <h2 id="quarterModalTitle"></h2>
                <div id="quarterTableContainer"></div>
            </div>`;
        document.body.appendChild(modal);
    }

    document.getElementById('quarterModalTitle').textContent = 
        `Q${quarter} Expenses – ${accountName} (${currentYear})`;

    const container = document.getElementById('quarterTableContainer');
    container.innerHTML = '<div style="text-align:center; padding:60px 0; color:#6b7280; font-size:16px;">Loading...</div>';

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    try {
        const url = `${DETAILS_API}?action=quarter_detail&quarter=${quarter}&allocationID=${allocationID || ''}&year=${currentYear}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Failed to load data');
        }

        const formatCur = (amt) => '₱' + Number(amt || 0).toLocaleString('en-PH', {minimumFractionDigits:2});

        let html = '';

        if (!data.transactions || data.transactions.length === 0) {
            html = '<div style="text-align:center; padding:60px 0; color:#6b7280; font-size:16px;">No paid expenses recorded in this quarter for this account.</div>';
        } else {
            html = `
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Requested by</th>
                            <th>Purpose / Title</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.transactions.forEach(row => {
                html += `
                    <tr>
                        <td>${row.date || '—'}</td>
                        <td style="text-align:right;">${formatCur(row.Amount)}</td>
                        <td>${row.Requested_by || '—'}</td>
                        <td>${row.requestTitle || '—'}</td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>

                <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; font-weight:600; font-size:17px; text-align:right;">
                    Total for Q${quarter}: ${formatCur(data.total)}
                </div>
            `;
        }

        container.innerHTML = html;

    } catch (err) {
        console.error(err);
        container.innerHTML = `
            <div style="text-align:center; padding:60px 0; color:#dc2626; font-size:16px;">
                Error loading quarterly details<br>
                <small style="color:#6b7280;">${err.message || 'Please try again'}</small>
            </div>
        `;
    }
}
document.getElementById('allocatemodal').addEventListener('click', function(e) {
 
    if (e.target === this) {
        this.classList.remove('active');
        document.body.style.overflow = ''; 
    }
});
document.getElementById('noBudgetModal').addEventListener('click', function(e) {
 
    if (e.target === this) {
        this.classList.remove('active');
        document.body.style.overflow = ''; 
    }
});
document.getElementById('budgetDetailsModal').addEventListener('click', function(e) {
 
    if (e.target === this) {
        this.classList.remove('active');
        document.body.style.overflow = ''; 
    }
});
async function openFullAllocationModal(deptID, year) {
    const modal = document.getElementById('allocatemodal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    window.currentDeptBudgetID = deptID;
    window.currentFiscalYear = year;

    document.getElementById('departmentName').textContent = 'Loading...';
    document.getElementById('fiscalYear').textContent = `FY ${year}`;
    document.querySelector('.budget-title').textContent = 'Loading...';
    document.querySelector('.total').textContent = '₱0.00';
    document.querySelector('.used').textContent = '₱0.00';
    document.querySelector('.remaining').textContent = '₱0.00';
    document.querySelector('.util-percent').textContent = '0%';
    document.querySelector('.progress-fill').style.width = '0%';
    document.querySelector('#expensesList').innerHTML = '<div class="text-center py-8 text-gray-500">Loading...</div>';

    try {
        const res = await fetch(`${DETAILS_API}?dept_id=${deptID}&year=${year}`);
        const json = await res.json();

        if (json.status === "success") {
            const budget = json.budget;
            const accounts = json.accounts || [];
            const historicalData = json.historicalData || {}; 

            
            document.getElementById('departmentName').textContent = budget.departmentName || 'Unknown';
            document.querySelector('#allocationHeaderText').textContent = 
                `Add and manage expenses for ${budget.departmentName} (FY ${year})`;
            document.querySelector('.budget-title').textContent = budget.departmentName;
            document.querySelector('.budget-subtitle').textContent = `Fiscal Year ${year}`;
            document.querySelector('.total').textContent = `₱${budget.totalBudget || '0.00'}`;
            document.querySelector('.used').textContent = `₱${budget.UsedBudget || '0.00'}`;
            document.querySelector('.remaining').textContent = `₱${budget.RemainingBudget || '0.00'}`;

            const util = budget.utilizationPercentage || 0;
            document.querySelector('.util-percent').textContent = `${util}%`;
            document.querySelector('.progress-fill').style.width = `${util}%`;

           
            document.querySelector('.expenses-subtitle').textContent = 
                `${accounts.length} expenses available for allocation`;

            const list = document.getElementById('expensesList');
            list.innerHTML = '';

            if (accounts.length === 0) {
                list.innerHTML = '<div class="text-center py-8 text-gray-500">No expenses available.</div>';
                return;
            }

      
            accounts.forEach(exp => {
                const tag = exp.accountName.toLowerCase().includes('supplies') ? 'supplies' :
                            exp.accountName.toLowerCase().includes('software') ? 'software' : 'general';

                list.insertAdjacentHTML('beforeend', `
                    <div class="expense-item" data-account-id="${exp.accountID}">
                        <div style="display:flex; align-items:center;">
                            <input type="checkbox" class="expense-checkbox">
                            <div class="expense-details">
                                <div class="expense-name">${exp.accountName}</div>
                                <div class="expense-meta">
                                    <span class="tag tag-${tag}">${tag.charAt(0).toUpperCase() + tag.slice(1)}</span>
                                    No date
                                </div>
                            </div>
                        </div>
                        <div class="ai-reason-box" style="display: none;">
                        <strong>🤖 AI Reason:</strong><br>
                       <span class="ai-reason-text">Waiting for allocation...</span>
                        </div>
                        <div>
                            <input type="number" class="expense-input" placeholder="Enter amount" min="0" step="0.01" readonly>
                            <div class="expense-amount"></div>
                            <div class="dropdown-arrow">▼</div>
                        </div>
                    </div>
                `);
            });

            
            window.historicalData = historicalData;

            const aiButton = document.getElementById('aiAllocateBtn');
            if (aiButton) {
                aiButton.onclick = allocateWithAI;  
            } else {
                console.error("AI Button not found! Check HTML id='aiAllocateBtn'");
            }

        } else {
            alert('Error loading data: ' + (json.message || 'Unknown'));
        }
    } catch (error) {
        console.error("Fetch error:", error);
        alert('Failed to load department data.');
    }
}

function closeBudgetDetailsModal() {
    const modal = document.getElementById('budgetDetailsModal');
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

function closeAllocationModal() {
    document.getElementById('allocatemodal').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('noBudgetModal');
    const detailsModal = document.getElementById('budgetDetailsModal');

    if (e.key === 'Escape' && detailsModal && detailsModal.classList.contains('active')) {
        detailsModal.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
});
async function loadAllocationSummary() {
    try {
        const res = await fetch(`${API}?year=${currentYear}`);
        const json = await res.json();

        if (json.status !== "success" || !json.summarydata) {
            console.error("No summary data received");
            return;
        }

        const s = json.summarydata; 

      
        setMaskedValue("totalBudgetss", parseFloat(s.totalBudgetss || 0));
        setMaskedValue("allocatedBudgets", parseFloat(s.UsedBudgetss || 0));
        setMaskedValue("remainingBudgets", parseFloat(s.RemainingBudgets || 0));

     
        const utilization = parseFloat(s.utilizationPercentagess || 0).toFixed(1);
        document.getElementById("utilizationPercentagess").textContent = utilization + "%";

        document.getElementById("percentages").innerHTML = `
            <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Budget Usage</span>
                <span class="text-sm font-medium text-gray-600">${utilization}%</span>
            </div>
            <div class="w-full h-4 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-1000 ease-out"
                     style="width: ${utilization}%;"></div>
            </div>
        `;

        
        const statusEl = document.querySelector("#percentages ~ div span.text-green-600"); 
        if (statusEl) {
            if (utilization > 90) {
                statusEl.textContent = "Over-allocated";
                statusEl.classList.replace("text-green-600", "text-red-600");
                statusEl.parentElement.classList.replace("bg-green-50", "bg-red-50");
                statusEl.parentElement.classList.replace("border-green-200", "border-red-200");
            } else if (utilization > 70) {
                statusEl.textContent = "High Usage";
                statusEl.classList.replace("text-green-600", "text-amber-600");
            } else {
                statusEl.textContent = "Healthy";
                statusEl.classList.replace("text-red-600", "text-green-600");
            }
        }
    } catch (error) {
        console.error("Error loading allocation summary:", error);
    }
}

async function loadFinancialYears() {
    try {
        const res = await fetch(API);
        const json = await res.json();

        const container = document.getElementById('financialYearsContainer');

        if (json.status !== "success" || !json.fiscalYears || json.fiscalYears.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    No financial year data available.
                </div>
            `;
            return;
        }

        container.innerHTML = json.fiscalYears.map(fy => {
            const status = fy.status || 'Archived';
            const deptCount = parseInt(fy.department_count) || 0;
            const plural = deptCount === 1 ? 'Department' : 'Departments';

         
            let badgeClass = '';
            if (status === 'Active') {
                badgeClass = 'bg-green-100 text-green-700';
            } else if (status === 'Closed') {
                badgeClass = 'bg-gray-100 text-gray-600';
            } else {
                badgeClass = 'bg-amber-100 text-amber-700';
            }

            return `
            <div class="fy-item flex items-center justify-between p-5 bg-gray-50 rounded-xl hover:bg-gray-100 transition cursor-pointer">
                <div>
                    <h4 class="text-lg font-bold text-gray-900">FY ${fy.fy_year}</h4>
                    <p class="text-sm text-gray-600 mt-1">${deptCount} ${plural}</p>
                </div>
                <span class="px-4 py-2 text-sm font-medium rounded-full ${badgeClass}">
                    ${status}
                </span>
            </div>
            `;
        }).join('');

     
        const fyItems = container.querySelectorAll('.fy-item');
        fyItems.forEach(item => {
            item.addEventListener('click', () => {
                const year = item.querySelector('h4').textContent.replace('FY ', '');
                selectYear(year);
            });
        });

    } catch (error) {
        console.error("Error loading financial years:", error);
        document.getElementById('financialYearsContainer').innerHTML = `
            <div class="text-center py-8 text-red-600">
                Failed to load financial years.
            </div>
        `;
    }
}

function selectYear(year) {
    currentYear = year;

    const fyItems = document.querySelectorAll('.fy-item');
    fyItems.forEach(item => item.classList.remove('bg-indigo-50'));
    const selected = Array.from(fyItems).find(item => item.querySelector('h4').textContent === `FY ${year}`);
    if (selected) selected.classList.add('bg-indigo-50');
console.log("Selected Year:", year);
    content();
    totalNumbers();
    loadAllocationSummary();
}

async function init() {
    await loadFinancialYears();
    
    const firstFY = document.querySelector('.fy-item');
    if (firstFY) {
        const defaultYear = firstFY.querySelector('h4').textContent.replace('FY ', '');
        firstFY.classList.add('bg-indigo-50');
        currentYear = defaultYear;
    } else {
        currentYear = new Date().getFullYear();
    }
    await Promise.all([content(), totalNumbers(), loadAllocationSummary()]);
}

document.addEventListener('DOMContentLoaded', () => {
    init();
});


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
    let rejectthisyear = 0;
    let departmentsLoaded = false;
    let currentDepartments = [];

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

   async function loadYearData(year) {
    try {
        const res = await fetch(`${API}?year=${year}`);
        const json = await res.json();

        if (json.status !== "success") {
            console.warn(`API failed for year ${year}:`, json);
            return { approved: 0, departments: [] };
        }

        const approved = Number(json.totalApprovedThisYear || 0);

        if (!departmentsLoaded && json.departments?.length > 0) {
            currentDepartments = json.departments;
            departmentsLoaded = true;
            
            deptSelect.innerHTML = `<option value="">-- Select Department --</option>` +
                currentDepartments.map(d => 
                    `<option value="${d.DepartmentName}">${d.DepartmentName}</option>`
                ).join('');
        }

        totalGLCash = Number(json.cashOnHand || 0);

        console.log(`Year ${year}: Approved = ${approved}, Cash = ${totalGLCash}`);

        return { approved, departments: currentDepartments };
    } catch (err) {
        console.error(`Fetch failed for year ${year}:`, err);
        return { approved: 0, departments: [] };
    }
}
    const currentYear = new Date().getFullYear();
    await loadYearData(currentYear);


    budgetYearSelect.innerHTML = '';
    for (let i = 0; i <= 5; i++) {
        const y = currentYear + i;
        budgetYearSelect.innerHTML += `<option value="${y}">${y}</option>`;
    }

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

   async function updateMaxAllowed() {
    const selectedYear = parseInt(budgetYearSelect.value);
    if (!selectedYear) {
        maxAllowedMsg.classList.add('hidden');
        maxValueSpan.textContent = '0.00';
        return;
    }

    const { approved } = await loadYearData(selectedYear);
    const maxAllowed = Math.max(0, totalGLCash - approved);
    budgetAmountInput.max = maxAllowed;
    maxValueSpan.textContent = Number(maxAllowed).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    let current = parseFloat(budgetAmountInput.value) || 0;
    if (current > maxAllowed) {
        budgetAmountInput.value = maxAllowed > 0 ? maxAllowed.toFixed(2) : '';
    }

    maxAllowedMsg.classList.remove('hidden');

    if (maxAllowed >= totalGLCash || approved === 0) {
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


    updateMaxAllowed();


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