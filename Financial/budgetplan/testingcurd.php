<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Budget Forecasting for 2027 - TF.js + Groq AI</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-6xl mx-auto p-6">

  <h1 class="text-3xl font-bold mb-2">Budget Forecasting for 2027</h1>
  <p class="text-gray-600 mb-6">
    Current total available cash: ₱<span id="total-cash" class="font-bold">Loading...</span><br>
    <span class="text-sm">TensorFlow.js (when ≥3 years data) + Groq AI refinement</span>
  </p>

  <button id="btnForecast"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium shadow-md transition">
    Generate 2027 Forecast & Recommendations
  </button>

  <div id="loading" class="hidden mt-6 text-indigo-600 font-medium">Calculating with TensorFlow.js & Groq AI...</div>

  <div id="result" class="mt-10 hidden space-y-10">

    <!-- Forecast Cards -->
    <div id="forecast-cards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

    <!-- Groq AI Recommendations -->
    <div class="bg-white border rounded-xl p-6 shadow-sm">
      <h2 class="text-xl font-semibold mb-4 text-indigo-700">Groq AI Strategic Recommendations for 2027</h2>
      <div id="ai-content" class="prose max-w-none"></div>
    </div>
  </div>
</div>

<script>

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

      // Render card
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

    // 3. Ask Groq for refinement
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

// ==================== TensorFlow.js Prediction ====================
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

// ==================== Render Groq AI Response ====================
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
      html += `
        <div class="mt-8 p-5 bg-indigo-50 border border-indigo-100 rounded-lg">
          <p class="font-medium text-indigo-800 mb-2">Overall Advice for 2027</p>
          <p class="text-gray-700">${data.overall_advice}</p>
        </div>
      `;
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
</script>
</body>
</html>