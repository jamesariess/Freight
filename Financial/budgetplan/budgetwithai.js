async function allocateWithAI(event) {
    event.preventDefault();

    const checkedBoxes = document.querySelectorAll('.expense-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please check at least one account first.');
        return;
    }

    const remainingText = document.querySelector('.remaining').textContent;
    const remainingBudget = parseFloat(remainingText.replace(/[^0-9.-]+/g, '')) || 0;

    if (remainingBudget <= 0) {
        alert('No remaining budget to allocate.');
        return;
    }

    const selectedAccounts = [];
    let totalManual = 0;

    checkedBoxes.forEach(checkbox => {
        const item = checkbox.closest('.expense-item');
        const accountName = item.querySelector('.expense-name').textContent.trim();
        const accountID = item.dataset.accountId;  
        const input = item.querySelector('.expense-input');
        const manualAmount = input.value ? parseFloat(input.value) : 0;

        selectedAccounts.push({ accountID, accountName, manualAmount });
        totalManual += manualAmount;
    });

    if (totalManual > remainingBudget) {
        alert('Manual amounts exceed remaining budget!');
        return;
    }

    const amountForAI = remainingBudget - totalManual;
    if (amountForAI <= 0) {
        alert('No budget left for AI to allocate.');
        return;
    }

    let forecastSummary = '';
    for (const acc of selectedAccounts) {
        const hist = window.historicalData[acc.accountName] || [];
        if (hist.length >= 3) { 
            const forecast = await forecastNextBudget(hist);
            forecastSummary += `- ${acc.accountName}: Forecasted ₱${forecast.toFixed(2)} for next year\n`;
        }
    }

const prompt = `
You are an expert government budget allocator.

${forecastSummary ? `Historical forecasts:\n${forecastSummary}\n` : ''}

Distribute exactly ₱${amountForAI.toFixed(2)} across these accounts realistically.

Selected accounts:
${selectedAccounts.map(a => `- ${a.accountName}${a.manualAmount > 0 ? ` (₱${a.manualAmount.toFixed(2)} manual)` : ''}`).join('\n')}

Return ONLY a JSON array with this format:
[
  {
    "accountName": "Cash on Hand",
    "suggestedAmount": 1000000.00,
    "reason": "Maintained high cash reserve for liquidity and emergency needs, standard practice for operational stability."
  },
  {
    "accountName": "Fuel Inventory / Supplies",
    "suggestedAmount": 2000000.00,
    "reason": "Increased allocation due to historical growth trend and forecasted demand for vehicle operations."
  }
]

- Use exact account names
- Every item must have a "reason" field (1-2 sentences explaining why this amount)
- Total must be very close to ₱${amountForAI.toFixed(2)}
- No extra text outside JSON
`;

    try {
        const btn = document.getElementById('aiAllocateBtn');
        btn.disabled = true;
        btn.textContent = 'Allocating...';

        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer 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',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: 'llama-3.3-70b-versatile',
                messages: [{ role: 'user', content: prompt }],
                temperature: 0.3,
                max_tokens: 1024
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const aiText = data.choices?.[0]?.message?.content?.trim();

        if (!aiText) throw new Error('Empty AI response');

        const suggestions = JSON.parse(aiText);

        let allocated = 0;
        document.querySelectorAll('.ai-reason-box').forEach(box => {
        box.style.display = 'none';
        });
        suggestions.forEach(sug => {
            const item = Array.from(document.querySelectorAll('.expense-item')).find(el =>
                el.querySelector('.expense-name').textContent.trim() === sug.accountName.trim()
            );
           if (item) {
        const input = item.querySelector('.expense-input');
        const current = parseFloat(input.value) || 0;
        input.value = (current + sug.suggestedAmount).toFixed(2);
        allocated += sug.suggestedAmount;

   
        const reasonBox = item.querySelector('.ai-reason-box');
        const reasonText = item.querySelector('.ai-reason-text');

        if (reasonBox && reasonText && sug.reason) {
            reasonText.textContent = sug.reason;
            reasonBox.style.display = 'block';
        }
    }
        });

        alert(`AI allocated ₱${allocated.toFixed(2)} successfully!\nReview and save.`);
        updateVerifyButton();
    } catch (err) {
        console.error('AI Allocation Error:', err);
        alert('Failed to allocate with AI.\nCheck internet, API key, or console for details.');
    } finally {
        const btn = document.getElementById('aiAllocateBtn');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Allocate with AI';
        }
    }
}


async function forecastNextBudget(hist) {
  

    const xs = tf.tensor2d(hist.map((val, idx) => [idx]), [hist.length, 1]); // Years as 0,1,2,...
    const ys = tf.tensor1d(hist);

    const model = tf.sequential();
    model.add(tf.layers.dense({units: 1, inputShape: [1]}));
    model.compile({loss: 'meanSquaredError', optimizer: 'sgd'});

    await model.fit(xs, ys, {epochs: 250});

    const nextIndex = tf.tensor2d([[hist.length]]);
    const pred = model.predict(nextIndex).dataSync()[0];

    return pred;
}


function updateVerifyButton() {
    const hasAllocation = Array.from(document.querySelectorAll('.expense-input'))
        .some(input => parseFloat(input.value) > 0);
    
    document.getElementById('verifyAllocationBtn').disabled = !hasAllocation;
}


function showVerificationStage() {
    const checkedItems = document.querySelectorAll('.expense-checkbox:checked');
    const verifiedList = document.getElementById('verifiedAccountsList');
    verifiedList.innerHTML = '';

    let total = 0;

    checkedItems.forEach(checkbox => {
        const item = checkbox.closest('.expense-item');
        const name = item.querySelector('.expense-name').textContent.trim();
        const input = item.querySelector('.expense-input');
        const amount = parseFloat(input.value) || 0;

        if (amount > 0) {
            total += amount;

            const reasonBox = item.querySelector('.ai-reason-box');
            const reasonHTML = reasonBox && reasonBox.style.display !== 'none' 
                ? reasonBox.outerHTML 
                : '<div class="ai-reason-box" style="display:block;"><strong>🤖 AI Reason:</strong><br><span class="ai-reason-text">Manually entered or adjusted amount.</span></div>';

            verifiedList.insertAdjacentHTML('beforeend', `
                <div class="verified-item">
                    <div>
                        <div class="account-name">${name}</div>
                        ${reasonHTML}
                    </div>
                    <div class="amount">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
            `);
        }
    });


    verifiedList.insertAdjacentHTML('beforeend', `
        <div style="text-align: right; font-size: 1.4em; font-weight: bold; color: #27ae60; margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 16px;">
            Total Allocated: ₱${total.toLocaleString('en-US', {minimumFractionDigits: 2})}
        </div>
    `);


    document.getElementById('expensesList').style.display = 'none';
    document.querySelector('.verify-btn').style.display = 'none';
    document.getElementById('verificationStage').style.display = 'block';


    document.getElementById('verificationStage').scrollIntoView({ behavior: 'smooth' });
}

function backToAllocation() {
    document.getElementById('expensesList').style.display = 'block';
    document.querySelector('.verify-btn').style.display = 'block';
    document.getElementById('verificationStage').style.display = 'none';
}
async function confirmAndSave() {
    const deptBudgetID = window.currentDeptBudgetID;
    const year = window.currentFiscalYear;

    if (!deptBudgetID) {
        alert("Error: Department ID not found. Please reopen the modal.");
        return;
    }

    const totalBudgetText = document.querySelector('.total').textContent;
    const totalBudget = parseFloat(totalBudgetText.replace(/[^0-9.-]+/g, '')) || 0;

    const allocations = [];

    document.querySelectorAll('.verified-item').forEach(item => {
        const name = item.querySelector('.account-name').textContent.trim();
        const amountText = item.querySelector('.amount').textContent;
        const amount = parseFloat(amountText.replace(/[^0-9.-]+/g, ''));

        if (amount <= 0) return;

        // Find original expense-item to get accountID
        const originalItem = Array.from(document.querySelectorAll('.expense-item'))
            .find(el => el.querySelector('.expense-name').textContent.trim() === name);

        if (!originalItem) return;

        const accountID = parseInt(originalItem.dataset.accountId);
        const percentage = totalBudget > 0 ? ((amount / totalBudget) * 100).toFixed(2) : 0;

        let reason = "Manually allocated by user.";
        
        const reasonBox = item.querySelector('.ai-reason-box');
        if (reasonBox && reasonBox.style.display !== 'none') {
            const reasonText = reasonBox.querySelector('.ai-reason-text');
            if (reasonText && reasonText.textContent.trim()) {
                reason = reasonText.textContent.trim();
            }
        }

        allocations.push({
            accountID: accountID,
            amount: amount,
            percentage: percentage,
            reason: reason   
        });
    });

    if (allocations.length === 0) {
        alert("No allocations to save!");
        return;
    }

    const payload = {
        deptBudgetID: deptBudgetID,
        year: year,
        totalBudget: totalBudget,
        allocations: allocations
    };

    try {
        const response = await fetch('savebudget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert(`🎉 SUCCESS!\n${result.message}\n\n${result.records_saved} records saved.`);
            document.getElementById('allocatemodal').classList.remove('active');
            document.body.style.overflow = '';
        } else {
            alert('Save failed: ' + result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Network error while saving. Check console.');
    }
}








document.getElementById('btnForecast').addEventListener('click', async () => {
  const btn = document.getElementById('btnForecast');
  const loading = document.getElementById('loading');
  const resultDiv = document.getElementById('result');

  btn.disabled = true;
  btn.textContent = 'Generating...';
  loading.classList.remove('hidden');
  resultDiv.classList.add('hidden');

  try {
 
    const res = await fetch('testing.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'forecast' })
    });
    const data = await res.json();

    if (!data.status) throw new Error('Data fetch failed');

    document.getElementById('total-cash').textContent = Number(data.total_cash).toLocaleString();


    const baselines = [];
    const cardsContainer = document.getElementById('forecast-cards');
    cardsContainer.innerHTML = '';

    for (const dept in data.dept_history) {
      const history = data.dept_history[dept].sort((a,b)=>a.year-b.year);
      let forecastAmount, method = 'Simple Rule', growthText = '';

      if (history.length >= 3) {
        forecastAmount = await tfPredictNext(history);
        method = 'TensorFlow.js';
      } else {
        const simple = data.simple_forecasts.find(f => f.department === dept);
        forecastAmount = simple?.simple_forecast || 0;
        growthText = simple?.suggested_growth || '';
      }

      baselines.push({
        department: dept,
        forecast: Math.round(forecastAmount),
        method
      });

 
      const last = history[history.length-1] || {amount:0, used:0};
      const card = document.createElement('div');
      card.className = 'bg-white border rounded-xl p-5 shadow-sm hover:shadow';
      card.innerHTML = `
        <h3 class="font-bold text-lg mb-3">${dept}</h3>
        <div class="space-y-2 text-sm">
          <p>Last known budget: ₱${Number(last.amount).toLocaleString()}</p>
          <p>Last used: ₱${Number(last.used).toLocaleString()}</p>
          <p class="text-indigo-600 font-bold">
            2027 Forecast: ₱${Number(forecastAmount).toLocaleString()}
            <span class="text-green-600 text-xs">(${growthText} via ${method})</span>
          </p>
        </div>
      `;
      cardsContainer.appendChild(card);
    }


    const groqRes = await fetch('testing.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'get_groq',
        baselines: JSON.stringify(baselines)
      })
    });
    const groqData = await groqRes.json();

    if (groqData.groq_ai) {
      renderAIAdvice(groqData.groq_ai);
    } else {
      document.getElementById('ai-content').innerHTML = '<p class="text-red-600">Groq AI response failed.</p>';
    }

    resultDiv.classList.remove('hidden');
  } catch (err) {
    alert('Error: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Generate 2027 Forecast & Recommendations';
    loading.classList.add('hidden');
  }
});

async function tfPredictNext(history) {
  const yearData = history.map(h => ({year: h.year, value: h.used || h.amount}));
  if (yearData.length < 3) return 0;

  const xs = tf.tensor2d(yearData.map(d => [d.year]), [yearData.length, 1]);
  const ys = tf.tensor2d(yearData.map(d => [d.value]), [yearData.length, 1]);

  const model = tf.sequential();
  model.add(tf.layers.dense({units: 1, inputShape: [1]}));
  model.compile({loss: 'meanSquaredError', optimizer: tf.train.sgd(0.01)});

  await model.fit(xs, ys, {epochs: 500, verbose: 0});

  const nextYear = Math.max(...yearData.map(d => d.year)) + 1;
  const prediction = model.predict(tf.tensor2d([[nextYear]], [1, 1]));
  const result = (await prediction.data())[0];

  xs.dispose(); ys.dispose(); model.dispose(); prediction.dispose();

  return result > 0 ? result : 0;
}


function renderAIAdvice(jsonString) {
  try {
    const data = JSON.parse(jsonString);
    const container = document.getElementById('ai-content');
    let html = '';

    if (data.recommendations?.length) {
      data.recommendations.forEach(rec => {
        const riskColor = {
          low: 'bg-green-100 text-green-800',
          medium: 'bg-amber-100 text-amber-800',
          high: 'bg-red-100 text-red-800'
        }[rec.risk?.toLowerCase()] || 'bg-gray-100';

        html += `
          <div class="mb-6 p-4 rounded-lg border ${riskColor}">
            <div class="flex justify-between items-center mb-2">
              <h4 class="font-bold text-lg">${rec.department}</h4>
              <span class="px-3 py-1 rounded-full text-sm font-medium">${rec.risk}</span>
            </div>
            <p class="text-2xl font-bold text-indigo-700 mb-2">
              ₱${Number(rec.suggested_amount_2027).toLocaleString()}
            </p>
            <p class="text-gray-700">${rec.reason}</p>
          </div>
        `;
      });
    }

    if (data.overall_advice) {
        const sulat = document.getElementById("sulat");
        sulat.textContent = data.overall_advice;
    }

    if (data.cash_warning) {
      html += `<p class="mt-4 text-red-600 font-medium">${data.cash_warning}</p>`;
    }

    container.innerHTML = html || '<p class="text-gray-500">No recommendations available.</p>';
  } catch (e) {
    document.getElementById('ai-content').innerHTML = '<p class="text-red-600">Invalid AI response.</p>';
    console.error(e);
  }
}