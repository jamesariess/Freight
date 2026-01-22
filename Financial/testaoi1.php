<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AI Cost Allocation Test</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; margin: 0; }
    .container { max-width: 900px; margin: 40px auto; background: white; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); overflow: hidden; }
    .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
    .budget-info { padding: 25px; background: #ecf0f1; text-align: center; font-size: 1.3em; }
    .remaining { font-size: 2.5em; color: #27ae60; font-weight: bold; margin: 10px 0; }
    .expenses-section { padding: 25px; }
    .expenses-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
    #aiAllocateBtn { padding: 14px 28px; background: #3498db; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; }
    #aiAllocateBtn:hover { background: #2980b9; }
    #aiAllocateBtn:disabled { background: #95a5a6; cursor: not-allowed; }
    .expense-item { display: flex; justify-content: space-between; align-items: center; padding: 18px; margin-bottom: 12px; background: #f9f9f9; border-radius: 10px; border: 1px solid #eee; }
    .expense-name { font-weight: bold; font-size: 1.1em; }
    .tag { padding: 5px 10px; border-radius: 6px; font-size: 0.8em; color: white; }
    .tag-supplies { background: #27ae60; }
    .tag-general { background: #7f8c8d; }
    input[type="checkbox"] { transform: scale(1.4); margin-right: 15px; }
    input[type="number"] { width: 160px; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 1em; }
    .note { text-align: center; color: #e74c3c; font-weight: bold; margin: 20px 0; }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>AI-Powered Cost Allocation</h1>
    <p>Department: Operations | FY 2025 | Remaining: ₱6,000,000.00</p>
  </div>

  <div class="budget-info">
    <div class="remaining">₱6,000,000.00</div>
    <div>Available for AI Allocation</div>
  </div>

  <div class="expenses-section">
    <div class="expenses-header">
      <h3>Unallocated Accounts</h3>
      <button id="aiAllocateBtn">Allocate with AI</button>
    </div>

    <div id="expensesList"></div>

    <div class="note">
      Check accounts → Click "Allocate with AI" → See smart distribution!
    </div>
  </div>
</div>

<script>
const accounts = [
  { accountID: 48, accountName: "Cash on Hand" },
  { accountID: 49, accountName: "Cash on Bank" },
  { accountID: 53, accountName: "Fuel Inventory / Supplies" },
  { accountID: 54, accountName: "Vehicles / Trucks" },
  { accountID: 55, accountName: "Trailers / Containers" },
  { accountID: 56, accountName: "Office Equipment" },
  { accountID: 57, accountName: "Furniture and Fixtures" },
  { accountID: 58, accountName: "Leasehold Improvements" },
  { accountID: 91, accountName: "Accounts Receivable" },
  { accountID: 52, accountName: "Accumulated Depreciation" }
];

const list = document.getElementById('expensesList');
accounts.forEach(acc => {
  const tag = acc.accountName.toLowerCase().includes('supplies') ? 'supplies' : 'general';
  list.insertAdjacentHTML('beforeend', `
    <div class="expense-item" data-account-id="${acc.accountID}">
      <div style="display:flex; align-items:center;">
        <input type="checkbox" class="expense-checkbox">
        <div class="expense-details">
          <div class="expense-name">${acc.accountName}</div>
          <span class="tag tag-${tag}">${tag.charAt(0).toUpperCase() + tag.slice(1)}</span>
        </div>
      </div>
      <input type="number" class="expense-input" placeholder="Enter amount" min="0" step="0.01">
    </div>
  `);
});

async function allocateWithAI() {
  const checked = document.querySelectorAll('.expense-checkbox:checked');
  if (checked.length === 0) return alert('Please check at least one account!');

  const remainingBudget = 6000000;
  let totalManual = 0;
  const selected = [];

  checked.forEach(cb => {
    const item = cb.closest('.expense-item');
    const name = item.querySelector('.expense-name').textContent.trim();
    const input = item.querySelector('.expense-input');
    const manual = input.value ? parseFloat(input.value) : 0;
    selected.push({ accountName: name, manualAmount: manual });
    totalManual += manual;
  });

  if (totalManual > remainingBudget) return alert('Manual total exceeds budget!');

  const amountForAI = remainingBudget - totalManual;

  const prompt = `You are an expert Philippine government budget allocator.

Distribute exactly ₱${amountForAI.toFixed(2)} across these selected accounts realistically:

${selected.map(s => `- ${s.accountName}${s.manualAmount > 0 ? ` (₱${s.manualAmount.toFixed(2)} manual)` : ''}`).join('\n')}

Return ONLY valid JSON:
[{"accountName": "Fuel Inventory / Supplies", "suggestedAmount": 1500000.00}, ...]

Use exact names. 2 decimals. Prioritize fuel, vehicles, supplies. No extra text.`;

  const btn = document.getElementById('aiAllocateBtn');
  btn.disabled = true;
  btn.textContent = 'Allocating...';

  try {
    const response = await fetch('testphp.php', {  // ← Points to the PHP proxy
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'groq_allocate',
        prompt: prompt
      })
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Error ${response.status}: ${text}`);
    }

    const data = await response.json();
    const text = data.choices?.[0]?.message?.content?.trim();

    if (!text) throw new Error('Empty response from AI');

    const suggestions = JSON.parse(text);

    let total = 0;
    suggestions.forEach(sug => {
      const item = Array.from(document.querySelectorAll('.expense-item')).find(
        el => el.querySelector('.expense-name').textContent.trim() === sug.accountName.trim()
      );
      if (item) {
        const input = item.querySelector('.expense-input');
        const current = parseFloat(input.value) || 0;
        input.value = (current + sug.suggestedAmount).toFixed(2);
        total += sug.suggestedAmount;
      }
    });

    alert(`SUCCESS! AI allocated ₱${total.toFixed(2)} across ${suggestions.length} accounts.`);

  } catch (err) {
    console.error(err);
    alert('Failed: ' + err.message + '\nCheck console (F12)');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Allocate with AI';
  }
}

document.getElementById('aiAllocateBtn').onclick = allocateWithAI;
</script>

</body>
</html>