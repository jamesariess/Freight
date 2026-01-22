

<?php $baseURL = '../../pages/'; ?>

     <div class="sidebar" id="sidebar">
    <div class="logo">
<img src="../../image/logo.png" alt="SLATE Logo">
    </div>
    <div class="system-name">Financial</div>
    <a href="<?php echo $baseURL; ?>dashboard/dashboard.php" class="sidebar-item active">Dashboard</a>




  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-book"></i>
      <span>General Ledger</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>general ledger/chartofaccout.php" class="sidebar-item">Chart of Accounts</a>
      <a href="<?php echo $baseURL; ?>general ledger/trialbalnce.php" class="sidebar-item">Cash Management</a>

      <a href="<?php echo $baseURL; ?>general ledger/journalentries.php" class="sidebar-item">Journal Details</a>
      <a href="<?php echo $baseURL; ?>general ledger/general.php" class="sidebar-item">General Ledger</a>

      <a href="<?php echo $baseURL; ?>general ledger/ledgerbalances.php" class="sidebar-item">Account Insight</a>
    </div>
  </div>

  <!-- Budget Management -->
  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-chart-line"></i>
      <span>Budget Management</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>budget/budgetmonitoring.php" class="sidebar-item">Budget Monitoring</a>
      <a href="<?php echo $baseURL; ?>budget/budget.php" class="sidebar-item">Budget Planning</a>
      <a href="<?php echo $baseURL; ?>budget/budgetallocation.php" class="sidebar-item">Cost Allocation</a>
           <a href="<?php echo $baseURL; ?>budget/budgetadjustment.php" class="sidebar-item">Allocation Adjustments</a>
      <a href="<?php echo $baseURL; ?>budget/budgetplanning.php" class="sidebar-item">Budget Approval</a>
      
 
    </div>
  </div>

  <!-- Disbursement -->
  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-money-check-dollar"></i>
      <span>Disbursement</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>disbursement/approver.php" class="sidebar-item">Payment Release</a>
      <a href="<?php echo $baseURL; ?>disbursement/disbursement.php" class="sidebar-item">Request Reports</a>
  
    </div>
  </div>

  <!-- Collection -->
  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-hand-holding-dollar"></i>
      <span>Collection</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>collection/collectionplan.php" class="sidebar-item">Collection Plans</a>
      <a href="<?php echo $baseURL; ?>collection/reminders.php" class="sidebar-item">Follow-ups & Reminders</a>
      <a href="<?php echo $baseURL; ?>collection/invoce.php" class="sidebar-item">Invoice</a>
      <a href="<?php echo $baseURL; ?>collection/receipt.php" class="sidebar-item">Official Receipt</a>
      <a href="<?php echo $baseURL;?>collection/custumer.php" class="sidebar-item">Dispute / Adjustment Record</a>
      <a href="<?php echo $baseURL; ?>collection/adjustment.php" class="sidebar-item">Reminders Log</a>
     
    </div>
  </div>

  <!-- Accounts Payable -->
  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-file-invoice-dollar"></i>
      <span>Accounts Payable</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>ap/vendor.php" class="sidebar-item">Company Management</a>
      <a href="<?php echo $baseURL; ?>ap/bill.php" class="sidebar-item">Invoice Bill</a>
      <a href="<?php echo $baseURL;?>ap/ap_ment.php" class="sidebar-item">Payment</a>
             <!-- <a href="<?php echo $baseURL;?>ap/ap_adjustment.php" class="sidebar-item">Adjustment</a> -->
    <!-- <a href="#" class="sidebar-item">Approval Workflow</a>
    <a href="#" class="sidebar-item">Vendor Statements / Reconciliation</a>
        <a href="#" class="sidebar-item">Credit/Debit Notes</a> -->
    </div>
  </div>

  <!-- Accounts Receivable -->
  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-receipt"></i>
      <span>Accounts Receivable</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>ar/custumer.php" class="sidebar-item">Customer Details</a>
      <a href="<?php echo $baseURL; ?>ar/invoice.php" class="sidebar-item">Sales Invoice</a>
      <a href="<?php echo $baseURL;?>ar/payment.php" class="sidebar-item">Payment Recording</a>
      <!-- <a href="<?php echo $baseURL;?>ar/soa.php" class="sidebar-item">Statements Of Account</a> -->
        <!-- <a href="<?php echo $baseURL ;?>ar/adjustment.php" class="sidebar-item">Invoice Adjustments </a>
    <a href="#" class="sidebar-item">AR Aging & Reports</a>
    <a href="#" class="sidebar-item">Customer Statements / Reconciliation</a> -->
    </div>
  </div>

  <!-- Dummy Data -->
  <div class="dropdown">
    <a href="#" class="sidebar-item dropdown-toggle">
      <i class="fa-solid fa-database"></i>
      <span>Dummy Data</span>
      <span class="arrow">▶</span>
    </a>
    <div class="dropdown-menu">
      <a href="<?php echo $baseURL; ?>sample/custumer.php" class="sidebar-item">Invoice</a>
      <a href="<?php echo $baseURL; ?>sample/request.php" class="sidebar-item">Request</a>
    </div>
  </div>



<div class="bottom-links">
  
  <a href="<?php echo $baseURL; ?>system/userpermission.php" class="sidebar-item bottom-link">
    <i class="fa-solid fa-user-shield"></i>
    <span>User Permissions</span>
    <span></span>
  </a>

  <a href="<?php echo $baseURL; ?>system/auditlog.php" class="sidebar-item">
  <i class="fa-solid fa-clipboard-list"></i>
  <span>Audit Log</span>
  <span></span>
</a>

  <a href="<?php echo $baseURL; ?>system/archive.php" class="sidebar-item bottom-link">
    <i class="fa-solid fa-box-archive"></i>
    <span>Archive</span>
    <span></span>
  </a>
  <a href="../auth/logout.php" class="sidebar-item bottom-link logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    <span>Logout</span>
    <span></span>
  </a>
</div>

  </div>
      <div class="overlay" id="overlay"></div>
     <div class="content" id="mainContent">
<div class="header">
  <div class="hamburger" id="hamburger">☰</div>

  <div class="theme-toggle-container">
    <div class="toggle-wrapper">
      <label class="theme-toggle" id="themeToggleLabel">
        <input type="checkbox" id="themeToggle">
        <span class="toggle-slider">
          <i class="fa-solid fa-sun sun-icon"></i>
          <i class="fa-solid fa-moon moon-icon"></i>
        </span>
      </label>
    </div>
     <?php include "notification.php"; ?>

   
    <div class="icon-btn user-icon" title="Account">
      <i class="fa-solid fa-user"></i>
    </div>
  </div>
</div>

<div id="message" class="flex"></div>

<?php if (!empty($successMessage)): ?>
    <div id="successAlert" class="mt-4 w-full bg-green-100 border-l-4 border-green-500 p-4 rounded-md shadow-md flex justify-between items-center mb-4">
      <p class="text-green-600 font-medium text-lg px-4"><?php echo htmlspecialchars($successMessage); ?></p>
      <button onclick="document.getElementById('successAlert').style.display='none';" class="text-green-600 hover:text-green-800 text-xl p-2">&times;</button>
    </div>
    <script>
      setTimeout(() => document.getElementById('successAlert').style.display = 'none', 5000);
    </script>
  <?php endif; ?>

  <?php if (!empty($errorMessage)): ?>
    <div id="errorAlert" class="mt-4 w-full bg-red-100 border-l-4 border-red-500 p-4 rounded-md shadow-md flex justify-between items-center mb-4">
      <p class="text-red-600 font-medium text-lg px-4"><?php echo htmlspecialchars($errorMessage); ?></p>
      <button onclick="document.getElementById('errorAlert').style.display='none';" class="text-red-600 hover:text-red-800 text-xl p-2">&times;</button>
    </div>
    <script>
      setTimeout(() => document.getElementById('errorAlert').style.display = 'none', 5000);
    </script>
  <?php endif; ?>
    <br>

      
   