 <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

<div class="bg-slate-900 p-5 rounded-xl shadow-lg border border-slate-800">
<canvas class="text-var(--text-light)" id="disburseChart" width="400" height="250"></canvas>
</div>
<div class="bg-slate-900 p-5 rounded-xl shadow-lg border border-slate-800">
<canvas  id="accountpayable" width="400" height="250"></canvas>
</div>
</section>



<section  class="grid grid-cols-1 lg:grid-cols-3 gap-6">
     <div>
                <div class="bg-slate-900 p-2 rounded-xl shadow-lg border border-slate-800 h-80 overflow-y-auto">
                    <h2 class="text-xl font-semibold mb-4 text-var(--text-light)">New Request Transactions</h2>
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase ">
                            <tr>
                                <th scope="col" class="py-3 px-4">Title</th>
                                <th scope="col" class="py-3 px-4">Account Name</th>
                                <th scope="col" class="py-3 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($disbursementReports)):
                            foreach($disbursementReports as $row): 
                            $amount = htmlspecialchars($row['Amount']);
                            $hala = formatShortNumber($amount);?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['requestTiTle']);?></td>
                                <td><?php echo htmlspecialchars($row['accountName']);?></td>
                                <td>₱ <?php echo $hala;?></td>
                            </tr>
                            <?php endforeach; else:?>
                            <tr><td colspan="9" class="text-center p-4">NO Records Found.</td></tr>
                            <?php endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
     
        <div class="bg-slate-900 p-5 rounded-xl shadow-lg border border-slate-800">
        <canvas id="budgetdept" width="400" height="250"></canvas>

        </div>


        <div>
                <div class="bg-slate-900 p-2 rounded-xl shadow-lg border border-slate-800 h-80 overflow-y-auto">
                    <h2 class="text-xl font-semibold mb-4 text-var(--text-light)">New Request Transactions</h2>
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase ">
                            <tr>
                                <th scope="col" class="py-3 px-4">Title</th>
                                <th scope="col" class="py-3 px-4">Account Name</th>
                                <th scope="col" class="py-3 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($disbursementReports)):
                            foreach($disbursementReports as $row): 
                            $amount = htmlspecialchars($row['Amount']);
                            $hala = formatShortNumber($amount);?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['requestTiTle']);?></td>
                                <td><?php echo htmlspecialchars($row['accountName']);?></td>
                                <td>₱ <?php echo $hala;?></td>
                            </tr>
                            <?php endforeach; else:?>
                            <tr><td colspan="9" class="text-center p-4">NO Records Found.</td></tr>
                            <?php endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
</section>

<script>
let disburseChartInstance = null;
let apChartInstance = null;
let budgetChartInstance = null;

const refreshInterval = 1000;
let isDarkMode = false;

function getChartColors(dark) {
    return dark ? {
        text: '#f8fafc',
        grid: '#475569',
        revenue: '#38bdf8',
        expenses: '#f87171'
    } : {
        text: '#334155',
        grid: '#cbd5e1',
        revenue: '#0ea5e9',
        expenses: '#ef4444'
    };
}

let colors = getChartColors(isDarkMode);


async function fetchDashboardData() {
    try {
        const res = await fetch("../../crud/system/graph.php");
        if (!res.ok) throw new Error('Network error');
        return await res.json();
    } catch (err) {
        console.error('Failed to fetch dashboard data:', err);
        return null;
    }
}

async function graphDisburs(data) {
    if (!data?.disbuere) return;

    const labels = data.disbuere.map(d => d.entry_date);
    const values = data.disbuere.map(d => parseFloat(d.Amount));

    if (disburseChartInstance) disburseChartInstance.destroy();

    disburseChartInstance = new Chart(document.getElementById("disburseChart"), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Approved Disbursements',
                data: values,
                borderColor: colors.revenue,
                backgroundColor: colors.revenue + '33',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: colors.text } },
                title: { display: true, text: 'Disbursement Trend', color: colors.text }
            },
            scales: {
                x: { ticks: { color: colors.text }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid } }
            }
        }
    });
}


async function aoGraph(data) {
    if (!data?.Apgraph) return;

    const labels = data.Apgraph.map(item => {
        const date = new Date(item.month_year + '-01');
        return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'long' });
    });
    const paid = data.Apgraph.map(item => item.Paid);
    const pending = data.Apgraph.map(item => item.Pending);

    if (apChartInstance) apChartInstance.destroy();

    apChartInstance = new Chart(document.getElementById('accountpayable'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Paid', data: paid, backgroundColor: colors.revenue },
                { label: 'Pending', data: pending, backgroundColor: colors.expenses }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: colors.text } },
                title: { display: true, text: 'Accounts Payable', color: colors.text }
            },
            scales: {
                x: { ticks: { color: colors.text }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid } }
            }
        }
    });
}


async function piebudget(data) {
    if (!data?.deptbud) return;

    const labels = data.deptbud.map(d => d.Name);
    const values = data.deptbud.map(d => parseFloat(d.amount));

    const bgColors = [
        '#0ea5e9', '#22c55e', '#f97316', '#ef4444',
        '#a855f7', '#14b8a6', '#eab308', '#6366f1', '#353be0'
    ];

    if (budgetChartInstance) budgetChartInstance.destroy();

    budgetChartInstance = new Chart(document.getElementById('budgetdept'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                label: 'Remaining Budget',
                data: values,
                backgroundColor: bgColors.slice(0, values.length)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right' },
                title: { display: true, text: 'Department Budget Allocation' }
            }
        }
    });
}


function loadFollowUpQueue(data) {
    const container = document.querySelector('#reminderList');
    if (!container) return;

    container.innerHTML = '';

    if (!data?.followups || data.followups.length === 0) {
        container.innerHTML = '<div class="no-data text-center"><p>No follow-up reminders today.</p></div>';
        return;
    }

    data.followups.forEach(item => {
        const overdue = item.days_overdue > 0
            ? `Overdue ${item.days_overdue} day${item.days_overdue > 1 ? 's' : ''}`
            : `Due Today`;

        const row = `
            <div class="reminder-item">
                <div class="dot pending"></div>
                <div class="meta">
                    <b>${item.reference_no} — ₱${parseFloat(item.amount).toLocaleString()}</b>
                    <small>${overdue} · ${item.client}</small>
                </div>
                <div class="actions">
                    <div class="pill">Manual</div>
                    <button class="btn primary" onclick="openQuickSend('${item.reminderID}')">Send</button>
                </div>
            </div>`;
        container.innerHTML += row;
    });
}


async function refreshDashboard() {
    const data = await fetchDashboardData();
    if (data?.status === 'success') {
        graphDisburs(data);
        aoGraph(data);
        piebudget(data);
        loadFollowUpQueue(data);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    refreshDashboard();
    getCssVariable();
    setInterval(refreshDashboard, refreshInterval);
});
</script>