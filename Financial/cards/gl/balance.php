<header class="mb-6 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
  <div>
    <h1 class="text-2xl font-semibold">Accounting Reports</h1>
    <p class="text-sm text-gray-500 mt-1">
      Period: <strong><?php echo safe($selectedPeriodLabel); ?></strong> 
      <?php if($selectedPeriodStatus) echo "· Status: <span class='text-sm font-medium'>".safe($selectedPeriodStatus)."</span>"; ?>
    </p>
  </div>

  <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto form-group">
    <!-- Period selector -->
    <form method="post" class="flex items-center gap-2 w-full sm:w-auto">
      <label class="text-sm text-gray-600 whitespace-nowrap">Period</label>
      <select name="period_id" onchange="this.form.submit()" 
        class="flex-1 sm:flex-none p-2 border border-gray-200 rounded-lg bg-white text-sm w-full sm:w-auto">
        <option value="">All Periods</option>
        <?php foreach ($periods as $p): 
            $label = $p['year'] . '-' . str_pad($p['month'],2,'0',STR_PAD_LEFT) . ' (' . $p['status'] . ')';
        ?>
          <option value="<?php echo (int)$p['period_id']; ?>" <?php echo ($selectedPeriodId && $selectedPeriodId == $p['period_id']) ? 'selected' : ''; ?>>
            <?php echo safe($label); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <!-- Month/Year selector + actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
      <form method="post" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full sm:w-auto" id="periodForm">
        <label class="text-sm text-gray-600 whitespace-nowrap">Select Month/Year</label>

        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
          <select name="month" class="p-2 border border-gray-200 rounded-lg bg-white text-sm w-full sm:w-auto" required>
            <option value="">Month</option>
            <?php
            $months = [];
            try {
                $monthStmt = $pdo->query("SELECT DISTINCT MONTH(date) as month FROM entries ORDER BY month");
                $months = $monthStmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($months as $m) {
                    $mName = date('F', mktime(0, 0, 0, $m, 1));
                    $selected = isset($_POST['month']) && $_POST['month'] == $m ? 'selected' : '';
                    echo "<option value='$m' $selected>$mName</option>";
                }
            } catch (Exception $e) {
                echo "<option value=''>Error loading months</option>";
            }
            ?>
          </select>

          <select name="year" class="p-2 border border-gray-200 rounded-lg bg-white text-sm w-full sm:w-auto" required>
            <option value="">Year</option>
            <?php
            $years = [];
            try {
                $yearStmt = $pdo->query("SELECT DISTINCT YEAR(date) as year FROM entries ORDER BY year DESC");
                $years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($years as $y) {
                    $selected = isset($_POST['year']) && $_POST['year'] == $y ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
            } catch (Exception $e) {
                echo "<option value=''>Error loading years</option>";
            }
            ?>
          </select>
        </div>

        <div class="flex gap-2 mt-2 sm:mt-0">
          <button type="submit" name="action" value="close" class="px-3 py-2 rounded-lg bg-red-600 text-white text-sm">Close Period</button>
          <button type="submit" name="action" value="reopen" class="px-3 py-2 rounded-lg bg-green-600 text-white text-sm">Reopen Period</button>
        </div>
      </form>

      <button onclick="window.location.href=window.location.pathname" class="px-3 py-2 rounded-lg border border-gray-200 shadow-sm text-sm w-full sm:w-auto">Reset</button>
    </div>
  </div>
</header>


<!-- Top summary cards -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
  <div class="quick-stat-card purple border-b-2 border-opacity-50">
    <div class="text-sm text-gray-500">Total Accounts</div>
    <div class="text-2xl font-semibold mt-1"><?php echo (int)$totalAccounts; ?></div>
  </div>
  <div class="quick-stat-card red border-b-2 border-opacity-50">
    <div class="text-sm text-gray-500">Total Debit</div>
    <div class="text-2xl font-semibold mt-1"><?php echo fmtMoney($totalDebitBalance); ?></div>
  </div>
  <div class="quick-stat-card green border-b-2 border-opacity-50">
    <div class="text-sm text-gray-500">Total Credit</div>
    <div class="text-2xl font-semibold mt-1"><?php echo fmtMoney($totalCreditBalance); ?></div>
  </div>
  <div class="quick-stat-card blue border-b-2 border-opacity-50">
    <div class="text-sm text-gray-500">Net (Debit - Credit)</div>
    <div class="text-2xl font-semibold mt-1"><?php echo fmtMoney($totalDebitBalance - $totalCreditBalance); ?></div>
  </div>
</section>