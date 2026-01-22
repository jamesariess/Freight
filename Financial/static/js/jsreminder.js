let ALL_PLANS = [];
let CURR_IDX = 0;
const refreshInterval = 10000;


let loadedOverviewIDs = new Set();
let loadedQueueIDs = new Set();
let loadedLogTimes = new Set(); 


let overviewPage = 1, queuePage = 1, logPage = 1;
let overviewHasMore = true, queueHasMore = true, logHasMore = true;
let currentReminderID = null;

async function Refresh() {
    await Promise.all([
        loadReminders(false),
        loadFollowUpQueue(false),
   
    ]);
    loadAllPlans();
    loadSentLog();
    counting();
    
}

setInterval(Refresh, refreshInterval);


async function initialLoad() {
    loadedOverviewIDs.clear();
    loadedQueueIDs.clear();
  

    overviewPage = 1; queuePage = 1; ;
    overviewHasMore = true; queueHasMore = true;

    await Promise.all([
        loadReminders(true),
        loadFollowUpQueue(true),
      
    ]);
    loadAllPlans();
     loadSentLog();
     counting();
     
}

document.addEventListener('DOMContentLoaded', initialLoad);
async function counting() {
    try {
        const res = await fetch('../../crud/collection/reminderapi.php?mode=counting');
        const json = await res.json();
        if (json.status === 'success' && json.counts) {
            document.getElementById('totalRequest').textContent = json.counts.totalReminder;
            document.getElementById('totalAmountRelease').textContent = json.counts.paidReminder;       
            document.getElementById('rejectedRequest').textContent = json.counts.pendingReminder;
            document.getElementById('Failed').textContent = json.counts.failedReminder;
        }
    } catch (err) {
        console.error(err);
    }
}
async function loadReminders(reset = false) {
    if (reset) {
        loadedOverviewIDs.clear();
        overviewPage = 1;
        overviewHasMore = true;
        document.getElementById('reminderList').innerHTML = '';
    }
    if (!overviewHasMore) return;

    try {
        const res = await fetch(`../../crud/collection/reminderapi.php?mode=overview&page=${overviewPage}&limit=10`);
        const result = await res.json();
        if (!result.data) return;

        const list = document.getElementById('reminderList');
        let added = 0;

        result.data.forEach(r => {
            if (!loadedOverviewIDs.has(r.reminderID)) {
                loadedOverviewIDs.add(r.reminderID);

                const item = document.createElement('div');
                item.className = 'reminder-item';
                item.innerHTML = `
                    <div class="dot ${r.status}"></div>
                    <div class="meta">
                        <b>${r.reference_no} — ₱${Number(r.amount).toLocaleString()}</b>
                        <small>Due: ${new Date(r.due_date).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'})} · ${r.client}</small>
                    </div>
                    <div class="actions">
                        <div class="pill"> ${r.mode || '—'}</div>
                        <button class="btn" onclick="openPreview('${r.reminderID}')"> Preview</button>
                        <button class="btn primary" onclick="openQuickSend('${r.reminderID}')"> Send Now</button>
                    </div>
                `;
                list.appendChild(item);
                added++;
            }
        });

        if (result.data.length === 0 && overviewPage === 1) {
            list.innerHTML = `<div class="no-data text-center"><p>No available reminders.</p></div>`;
        }

        overviewHasMore = result.pagination?.hasMore ?? false;
        if (added > 0) overviewPage++;

        updateLoadMore('reminderList', overviewHasMore, () => loadReminders(false));

    } catch (err) {
        console.error('loadReminders error:', err);
    }
}


async function loadFollowUpQueue(reset = false) {
    if (reset) {
        loadedQueueIDs.clear();
        queuePage = 1;
        queueHasMore = true;
        document.querySelector('#followup-list').innerHTML = '';
    }
    if (!queueHasMore) return;

    try {
        const res = await fetch(`../../crud/collection/reminderapi.php?mode=queue&page=${queuePage}&limit=10`);
        const result = await res.json();
        if (!result.data) return;

        const container = document.querySelector('#followup-list');
        let added = 0;

        result.data.forEach(item => {
            if (!loadedQueueIDs.has(item.reminderID)) {
                loadedQueueIDs.add(item.reminderID);

                const overdue = item.days_overdue > 0
                    ? `Overdue ${item.days_overdue} day${item.days_overdue > 1 ? 's' : ''}`
                    : `Due ${new Date(item.due_date).toLocaleDateString('en-PH', {month:'short', day:'numeric'})}`;

                const isManual = (item.mode || '').toLowerCase() === 'manual';
                const btn = isManual
                    ? `<button class="btn primary" onclick="openQuickSend('${item.reminderID}')"> Send</button>`
                    : `<button class="btn" onclick="openPreview('${item.reminderID}')"> Preview</button>`;

                container.innerHTML += `
                    <div class="reminder-item">
                        <div class="dot ${item.status}"></div>
                        <div class="meta">
                            <b>${item.reference_no} — ₱${parseFloat(item.amount).toLocaleString()}</b>
                            <small> ${overdue} · ${item.client}</small>
                        </div>
                        <div class="actions">
                            <div class="pill"> ${item.mode || '—'}</div>
                            ${btn}
                        </div>
                    </div>`;
                added++;
            }
        });

        if (result.data.length === 0 && queuePage === 1) {
            container.innerHTML = `<div class="no-data text-center"><p>No follow-up reminders.</p></div>`;
        }

        queueHasMore = result.pagination?.hasMore ?? false;
        if (added > 0) queuePage++;

        updateLoadMore('followup-list', queueHasMore, () => loadFollowUpQueue(false));

    } catch (err) {
        console.error('loadFollowUpQueue error:', err);
    }
}

async function loadSentLog() {
    try {
        const response = await fetch('../../crud/collection/reminderapi.php?mode=log');
        const result = await response.json();

        const tbody = document.querySelector('.log tbody');
        tbody.innerHTML = '';

        if (result.status !== 'success' || !result.data.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align:center; color:#94a3b8;">
                        <i class="fa-solid fa-circle-info"> </i> No sent logs available.
                    </td>
                </tr>`;
            return;
        }

        result.data.forEach(row => {
            const date = new Date(row.time);
            const formatted = date.toLocaleString('en-US', {
                month: 'short', day: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            }).replace(',', ' ·');

            const icon = row.status === 'Delivered' 
                ? '<i class="fa-solid fa-check-circle" style="color:green"></i>'
                : '<i class="fa-solid fa-xmark-circle" style="color:red;"></i>';

            tbody.innerHTML += `
                <tr>
                    <td><i class="fa-regular fa-calendar"></i> ${formatted}</td>
                    <td>${row.type}</td>
                    <td>${row.to}</td>
                    <td>${icon} ${row.status}</td>
                </tr>
            `;
        });
    } catch (err) {
        console.error('Error loading sent log:', err);
        const tbody = document.querySelector('.log tbody');
        tbody.innerHTML = `
            <tr><td colspan="4" style="text-align:center; color:red">
                <i class="fa-solid fa-triangle-exclamation"></i> Error loading sent logs.
            </td></tr>`;
    }
}

function updateLoadMore(containerId, hasMore, loadFn, isLog = false) {
    let parent = isLog ? document.querySelector('.log') : document.getElementById(containerId);
    const existing = parent.querySelector('.load-more-btn');
    if (existing) existing.remove();

    if (hasMore) {
        const div = document.createElement('div');
        div.className = 'load-more-btn';
        div.style.textAlign = 'center';
        div.style.marginTop = '16px';
        div.innerHTML = `<button class="btn primary" style="width:100%;max-width:300px;">Load More</button>`;
        div.onclick = () => {
            const btn = div.querySelector('button');
            btn.disabled = true;
            btn.innerHTML = 'Loading...';
            loadFn().finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Load More';
            });
        };
        parent.appendChild(div);
    }
}
async function totalReminders() {
    const modal = document.getElementById('cardmodal');
    const title = document.getElementById('cardmodalTitle');
    const content = document.getElementById('cardmodalContent');
    const table = document.querySelector('#cardmodal table');
    title.textContent = 'Total Reminders';
    table.innerHTML = `<tbody><tr><td colspan="5">Loading...</td></tr></tbody>`;
    modal.classList.add('open');

    try {
        const res = await fetch('../../crud/collection/remindercard.php');
        const json = await res.json();

        if (json.status !== 'success') {
            throw new Error(json.message || 'Failed');
        }
        const d = json.data;
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Customer</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${d.map(r => `
                    <tr>
                        <td>${r.reference_no}</td>
                        <td>₱${Number(r.amount).toLocaleString()}</td>
                        <td>${r.name}</td>
                        <td>${new Date(r.due_date).toLocaleDateString('en-PH',{
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric'
                })}</td>
                        <td>${r.stat}</td>
                    </tr>
                `).join('')}
            </tbody>
        `;
    } catch (err) {
        content.innerHTML = `<span style="color:red">Error: ${err.message}</span>`;
    }
}
async function totalPaid() {
    const modal = document.getElementById('cardmodal');
    const title = document.getElementById('cardmodalTitle'); // Fixed typo: Tittle → Title
    const table = document.querySelector('#cardmodal table');

    title.textContent = 'Total Paid';
    table.innerHTML = `<tbody><tr><td colspan="5">Loading...</td></tr></tbody>`;
    modal.classList.add('open');

    try {
        const res = await fetch('../../crud/collection/remindercard.php');
        const json = await res.json();

        if (json.status !== 'success') { // Fixed typo: succces → success
            throw new Error(json.message || 'Failed to fetch data');
        }

        const d = json.data2; // Paid invoices

        table.innerHTML = `
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Customer</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${d.map(r => `
                    <tr>
                        <td>${r.reference_no}</td>
                        <td>₱${Number(r.amount).toLocaleString()}</td>
                        <td>${r.name}</td>
                        <td>${new Date(r.due_date).toLocaleDateString('en-PH', {
                            month: 'long',
                            day: 'numeric',   // Fixed: 'date' → 'day'
                            year: 'numeric'
                        })}</td>
                        <td>${r.stat}</td>
                    </tr>
                `).join('')}
            </tbody>
        `;

    } catch (err) {
        document.getElementById('cardmodalContent').innerHTML = 
            `<span style="color:red">Error: ${err.message}</span>`;
    }
}

async function totalPending() {
    const modal = document.getElementById('cardmodal');
    document.getElementById('cardmodalTitle').textContent='Pending Payment';
    const table = document.querySelector('#cardmodal table');
    table.innerHTML = `<tbody><tr><td>Loading....</td></tr></tbody>`;
    modal.classList.add('open');

  try{
    const res = await fetch('../../crud/collection/remindercard.php');
    const json = await res.json();

    if(json.status !== 'success'){
        throw new Error(json.message || 'Failed');
    }
    const d = json.data3;

    table.innerHTML=`
    <thead>
    <tr>
    <th> Costumer Name</th>
    <th> Invoice No</th>
    <th> Amount</th>
    <th> Due Date </th>
    <th> Status </th>
    </thead>
    <tbody>
    ${d.map(r =>`
        <td>${r.name}</td>
        <td>${r.reference_no}</td>
        <td>${Number(r.amount).toLocaleString()}</td>
        <td>${new Date(r.due_date).toDateString('en-PH',{
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        })}
        <td> ${r.stat}</td>
        </tr>
        `
    ).join('')}
    `;
  }
  catch(err){
    document.getElementById('cardmodalContent').innerHTML=
    `<span style="color:red">Error: ${err.message}</span>`;
    
  } 
}

async function totalFailed(){
    document.getElementById('cardmodal').classList.add('open');
    const table =document.querySelector('#cardmodal table');
    document.getElementById('cardmodalTitle').textContent='Failed Reminder';

    try{
        const res = await fetch('../../crud/collection/remindercard.php');
        const json = await res.json();

        if( json.status !== 'success'){
            throw new Error(json.message || 'Failed To fetch Data');
        }
        const d = json.data4;
        table.innerHTML =`
         <thead>
    <tr>
    <th> Costumer Name</th>
    <th> Invoice No</th>
    <th> Gmail</th>
    <th> Number </th>
    <th> Issue </th>
    <th> Date Issue </th>
    <th> Status </th>
    </thead>
    <tbody>
    ${d.map(r => `
        <tr>
        <td>${r.name}</td>
        <td>${r.reference_no}</td>
        <td>${r.email}</td>
        <td>+63 ${r.phone}</td>
        <td>${r.issue}</td>
        <td>${new Date(r.ReminderSent).toDateString('en-PH',{
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        })}
        <td>${r.Status}</td>
        </tr>
        `).join('')}
        `;
    }catch(err){
       document.getElementById('cardmodalContent').innerHTML = `
       <tbody><tr><td>Error: ${err.message}</td></tr></tbody>`;
    }
}

async function openPreview(reminderID) {
    currentReminderID = reminderID;
    const modal = document.getElementById('modal');
    document.getElementById('modalTitle').textContent = 'Preview Reminder';
    document.getElementById('modalSub').textContent = 'Loading...';
    document.getElementById('modalPreview').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
    modal.classList.add('open');

    try {
        const res = await fetch('../../crud/collection/reminderapi.php?mode=preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reminderID })
        });
        const json = await res.json();

        if (json.status !== 'success') throw new Error(json.message || 'Failed');

        const d = json.data;
        document.getElementById('modalSub').innerHTML = `
            <strong>${d.invoice}</strong> — ${d.amount}
            <small style="color:var(--muted)"> · Due: ${d.due} · ${d.customer}</small>
        `;

        document.getElementById('modalPreview').innerHTML = `
            <div style="background:rgba(255,255,255,0.03);padding:12px;border-radius:8px;margin-bottom:12px;font-weight:600;">
                Subject: ${d.subject}
            </div>
            <div style="line-height:1.7;white-space:pre-wrap;">${d.body}</div>
            <div style="margin-top:16px;font-size:12px;color:var(--muted);text-align:right;">
                Template: <strong>${d.plan}</strong>
            </div>
        `;

    } catch (err) {
        document.getElementById('modalPreview').innerHTML = `<span style="color:var(--danger)">Error: ${err.message}</span>`;
    }
}

async function openQuickSend(id) {
    currentReminderID = id;
    document.getElementById('modalTitle').textContent = 'Send Reminder Now';
    openPreview(id);
}

async function sendNow() {
    if (!currentReminderID) return;
    const btn = document.querySelector('#modal .btn.primary');
    btn.disabled = true;
    btn.innerHTML = 'Sending...';

    try {
        const res = await fetch('../../crud/collection/reminderapi.php?mode=sendnow', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reminderID: currentReminderID })
        });
        const json = await res.json();

        alert(json.status === 'success' ? 'Email sent!' : 'Failed: ' + json.message);
        if (json.status === 'success') {
            closeModal();
            initialLoad(); 
        }
    } catch (err) {
        alert('Error: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Send Now';
    }
}

function closeModal() {
    document.getElementById('modal').classList.remove('open');
    document.getElementById('cardmodal').classList.remove('open');
}

document.addEventListener('click', function (e) {
    const modal = document.getElementById('modal');
    const cardmodal = document.getElementById('cardmodal');
    if (modal && e.target === modal) {
        modal.classList.remove('open');
    }
    if (cardmodal && e.target === cardmodal) {
        cardmodal.classList.remove('open');
    }
});

document.addEventListener('keydown', e => e.key === 'Escape' && closeModal());

async function loadAllPlans() {
    try {
        const res = await fetch('../../crud/collection/reminderapi.php?mode=plans');
        const json = await res.json();
        ALL_PLANS = json.status === 'success' ? json.data : [];
        updatePlanDisplay();
    } catch (e) {
        console.error(e);
        document.getElementById('templateBody').textContent = 'Failed to load templates.';
    }
}

function updatePlanDisplay() {
    const counter = document.getElementById('planCounter');
    const nameEl = document.getElementById('planName');
    const subjEl = document.getElementById('templateSubject');
    const bodyEl = document.getElementById('templateBody');

    if (!ALL_PLANS.length) {
        counter.textContent = '0 / 0';
        document.getElementById('planBadge').style.display = 'none';
        nameEl.textContent = '';
        subjEl.textContent = '';
        bodyEl.textContent = 'No templates found.';
        return;
    }

    const plan = ALL_PLANS[CURR_IDX];
    counter.textContent = `${CURR_IDX + 1} / ${ALL_PLANS.length}`;
    document.getElementById('planBadge').style.display = 'flex';
    nameEl.textContent = `${plan.plan} (${plan.plan_type})`;
    subjEl.textContent = plan.subject || '(no subject)';
    bodyEl.textContent = plan.body || '(no body)';
    bodyEl.scrollTop = 0;
}

document.getElementById('prevPlan')?.addEventListener('click', () => {
    if (ALL_PLANS.length === 0) return;
    CURR_IDX = (CURR_IDX - 1 + ALL_PLANS.length) % ALL_PLANS.length;
    updatePlanDisplay();
});

document.getElementById('nextPlan')?.addEventListener('click', () => {
    if (ALL_PLANS.length === 0) return;
    CURR_IDX = (CURR_IDX + 1) % ALL_PLANS.length;
    updatePlanDisplay();  
});


