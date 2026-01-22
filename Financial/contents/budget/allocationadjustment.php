
<div>
  <form method="POST" id="adjustForm" class=" border border-gray-200 rounded-2xl shadow-md p-8 space-y-6">
    <h2 class="text-2xl font-semibold  border-b border-gray-200 pb-3 mb-6">Budget Allocation Adjustment</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 form-group">
      <div>
        <label>Department</label>
        <select id="department" name="department" class="w-full p-3 border rounded-lg" required>
          <option value="">--SELECT DEPARTMENT--</option>
          <?php foreach($departments as $d): ?>
            <option value="<?= $d['Deptbudget'] ?>" data-total="<?= $d['Amount'] ?>"><?= $d['Name'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Year</label>
        <select id="year" name="year" class="w-full p-3 border rounded-lg" required>
          <option value="">--SELECT YEAR--</option>
        </select>
      </div>
    </div>

  
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6 form-group">
      <div class="p-6 border rounded-xl shadow-sm ">
        <h3 class="text-lg font-semibold mb-4">Adjust Allocation (can increase or decrease)</h3>
        <select id="title_increase" name="title_increase" class="w-full p-3 border rounded-lg" required>
          <option value="">--SELECT TITLE--</option>
        </select>
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div><label>Current %</label><input type="text" id="increase_current_percent" readonly class="w-full border p-2 rounded"></div>
          <div><label>Current Amount</label><input type="text" id="increase_current_amount" readonly class="w-full border p-2 rounded"></div>
        </div>
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div><label>New %</label><input type="number" id="new_percent_increase" name="new_percent_increase" min="0" max="100" step="0.01" required class="w-full border p-2 rounded"></div>
          <div><label>New Amount</label><input type="text" id="new_amount_increase" readonly class="w-full border p-2 rounded"></div>
        </div>
      </div>

      <div class="p-6 border rounded-xl shadow-sm ">
        <h3 class="text-lg font-semibold mb-4">Decrease Allocation (required for increase)</h3>
        <select id="title_decrease" name="title_decrease" class="w-full p-3 border rounded-lg">
          <option value="">--SELECT TITLE--</option>
        </select>
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div><label>Current %</label><input type="text" id="decrease_current_percent" readonly class="w-full border p-2 rounded"></div>
          <div><label>Current Amount</label><input type="text" id="decrease_current_amount" readonly class="w-full border p-2 rounded"></div>
        </div>
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div><label>New %</label><input type="number" id="new_percent_decrease" readonly class="w-full border p-2 rounded"></div>
          <div><label>New Amount</label><input type="text" id="new_amount_decrease" readonly class="w-full border p-2 rounded"></div>
        </div>
      </div>
    </div>

    <div class="form-group"><label>Reason</label><input type="text" name="reason" required class="w-full border p-3 rounded"></div>
    <div class="flex justify-end"><button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded shadow">Adjust Budget</button></div>
  </form>
</div>

<script>
const dept = document.getElementById('department');
const year = document.getElementById('year');
const inc = document.getElementById('title_increase');
const dec = document.getElementById('title_decrease');

const currIncP = document.getElementById('increase_current_percent');
const currDecP = document.getElementById('decrease_current_percent');
const newIncP = document.getElementById('new_percent_increase');
const newDecP = document.getElementById('new_percent_decrease');

const currIncA = document.getElementById('increase_current_amount');
const currDecA = document.getElementById('decrease_current_amount');
const newIncA = document.getElementById('new_amount_increase');
const newDecA = document.getElementById('new_amount_decrease');

let totalBudget = 0;
let allocationsData = [];


function fetchYears() {
    year.innerHTML = "<option value=''>--SELECT YEAR--</option>";
    inc.innerHTML = "<option value=''>--SELECT TITLE--</option>";
    dec.innerHTML = "<option value=''>--SELECT TITLE--</option>";
    clearIncreaseFields();
    clearDecreaseFields();

    const d = dept.value;
    if (!d) return;

    fetch(`?dept=${d}`)
        .then(res => res.json())
        .then(years => {
            years.forEach(y => {
                const o = document.createElement('option');
                o.value = y;
                o.text = y;
                year.appendChild(o);
            });
        });
}


function fetchTitles() {
    inc.innerHTML = "<option value=''>--SELECT TITLE--</option>";
    dec.innerHTML = "<option value=''>--SELECT TITLE--</option>";
    clearIncreaseFields();
    clearDecreaseFields();

    const d = dept.value;
    const y = year.value;
    totalBudget = parseFloat(dept.selectedOptions[0]?.dataset.total || 0);
    if (!d || !y) return;

    fetch(`../../crud/budget/adjustment.php?dept=${d}&year=${y}`)
        .then(res => res.json())
        .then(data => {
            allocationsData = data;
     
            data.forEach(item => {
                const o = document.createElement('option');
                o.value = item.allocationID;
                o.text = `${item.Title} (${item.Percentage}%)`;
                o.dataset.percentage = item.Percentage;
                o.dataset.amount = item.Amount;
                inc.appendChild(o);
            });
            rebuildDecreaseOptions();
        });
}

function rebuildDecreaseOptions() {
    const selectedInc = inc.value;
    const prevDec = dec.value;
    dec.innerHTML = "<option value=''>--SELECT TITLE--</option>";

    allocationsData.forEach(item => {
        if (String(item.allocationID) !== selectedInc) {
            const o = document.createElement('option');
            o.value = item.allocationID;
            o.text = `${item.Title} (${item.Percentage}%)`;
            o.dataset.percentage = item.Percentage;
            o.dataset.amount = item.Amount;
            dec.appendChild(o);
        }
    });


    if (prevDec && allocationsData.some(item => String(item.allocationID) === prevDec) && prevDec !== selectedInc) {
        dec.value = prevDec;
        updateDecreaseFields(prevDec);
    } else {
        dec.value = "";
        clearDecreaseFields();
    }
}

function updateIncreaseFields(selectedIncId) {
    const selectedItem = allocationsData.find(item => String(item.allocationID) === selectedIncId);
    if (selectedItem) {
        currIncP.value = selectedItem.Percentage;
        currIncA.value = selectedItem.Amount;
        newIncP.value = selectedItem.Percentage;
        newIncA.value = (selectedItem.Percentage / 100 * totalBudget).toFixed(2);
    }
}

function updateDecreaseFields(selectedDecId) {
    const selectedItem = allocationsData.find(item => String(item.allocationID) === selectedDecId);
    if (selectedItem) {
        currDecP.value = selectedItem.Percentage;
        currDecA.value = selectedItem.Amount;
        newDecP.value = selectedItem.Percentage;
        newDecA.value = (selectedItem.Percentage / 100 * totalBudget).toFixed(2);
    }
}

function clearIncreaseFields() {
    currIncP.value = "";
    currIncA.value = "";
    newIncP.value = "";
    newIncA.value = "";
}

function clearDecreaseFields() {
    currDecP.value = "";
    currDecA.value = "";
    newDecP.value = "";
    newDecA.value = "";
}


function handleNewPercentChange() {
    if (!inc.value || !dec.value) return;

    const currInc = parseFloat(currIncP.value) || 0;
    let newInc = parseFloat(newIncP.value) || currInc;
    newInc = Math.max(0, Math.min(100, newInc));
    newIncP.value = newInc.toFixed(2);
    newIncA.value = (newInc / 100 * totalBudget).toFixed(2);

    const currDec = parseFloat(currDecP.value) || 0;
    const isIncreasing = newInc > currInc;

    if (isIncreasing) {
        let delta = newInc - currInc;
        if (delta > currDec) {
            delta = currDec;
            newIncP.value = (currInc + delta).toFixed(2);
            newIncA.value = ((currInc + delta) / 100 * totalBudget).toFixed(2);
            newInc = parseFloat(newIncP.value);
        }
        const newDec = currDec - delta;
        newDecP.value = newDec.toFixed(2);
        newDecA.value = (newDec / 100 * totalBudget).toFixed(2);
    } else {
        newDecP.value = currDec.toFixed(2);
        newDecA.value = (currDec / 100 * totalBudget).toFixed(2);
    }
}


dept.addEventListener('change', () => {
    fetchYears();
    fetchTitles();
});
year.addEventListener('change', fetchTitles);

inc.addEventListener('change', () => {
    const s = inc.selectedOptions[0];
    if (!s) {
        clearIncreaseFields();
        return;
    }
    const selectedIncId = s.value;
    updateIncreaseFields(selectedIncId);
    rebuildDecreaseOptions();
    if (dec.value) handleNewPercentChange();
});

dec.addEventListener('change', () => {
    const s = dec.selectedOptions[0];
    if (!s) {
        clearDecreaseFields();
        return;
    }
    const selectedDecId = s.value;
    updateDecreaseFields(selectedDecId);
    if (inc.value) handleNewPercentChange();
});

newIncP.addEventListener('input', () => {
    const currInc = parseFloat(currIncP.value) || 0;
    let newInc = parseFloat(newIncP.value) || currInc;
    if (newInc > currInc && !dec.value) {
        alert('Please select a decrease title to fund the increase.');
        newIncP.value = currInc.toFixed(2);
        newIncA.value = (currInc / 100 * totalBudget).toFixed(2);
        clearDecreaseFields();
        return;
    }
    handleNewPercentChange();
});
</script>