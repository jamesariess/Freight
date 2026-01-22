<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../static/css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="budget.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script> <!-- Add TF.js -->
</head>
<body>
    
<div>
<div class="container mx-auto">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">

    <div class="quick-stat-card purple border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-sack-dollar text-purple-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Total Budgets This Year</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="totalBudgets" class="text-2l font-bold text-purple-600 masked" data-value="">****</div>
        
      </div><span class="text-gray-500 text-sm">All</span>
    </div>


    <div class="quick-stat-card green border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Active</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="approvedBudgets" class="text-2l font-bold text-green-600 masked" data-value="">****</div>
     
      </div>   <span class="text-gray-500 text-sm">Budgets This year</span>
    </div>


    <div class="quick-stat-card red border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Cancelled</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="cancelledBudgets" class="text-2l font-bold text-red-600 masked" data-value="">****</div>
        
      </div><span class="text-gray-500 text-sm">Budgets This year</span>
    </div>


    <div class="quick-stat-card blue border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-times-circle text-blue-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">GL Cash</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="Cash" class="text-2l font-bold text-blue-600 masked" data-value="">****</div>
        
      </div><span class="text-gray-500 text-sm">Available</span>
    </div>


    <div class="quick-stat-card yellow border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Total Department</h3>
      </div>
      <div class="flex items-end justify-between">
        <div id="totalDepartments" class="text-3xl font-bold text-yellow-600" data-value="">0</div>
        
      </div><span class="text-gray-500 text-sm">Budgets</span>
    </div>
  </div>
</div>

<!-- AI -->
<div class="w-full mt-4 mb-6">
  <div class="bg-indigo-50 border border-indigo-200 shadow-sm rounded-lg p-4 flex items-center gap-4">
    <div class="bg-indigo-100 text-indigo-600 rounded-full p-2">
      <svg xmlns="http://www.w3.org/2000/svg" 
           class="h-6 w-6" fill="none" viewBox="0 0 24 24" 
           stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 
              10 10 0 000-20z" />
      </svg>
    </div>

    <div class="flex-1">
      <p class="text-gray-800 font-medium">AI Suggestion</p>
      <p class="text-gray-700 text-sm">
        Based on last year’s spending, you might want to allocate a higher budget for 
        <span class="font-semibold">Maintenance</span> this year to avoid unexpected costs.
      </p>
    </div>
  </div>
</div>




<div class="container">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between shadow-md rounded-2xl p-4 border border-gray-200 card">
        <div>
          <h2 id="sulat" class="text-2xl font-bold flex items-center gap-2">
                <i class="fas fa-bell text-indigo-500"></i>
                Budget Allocation Data| Allocate Funds
            </h2>
              <p class="mb-4">View and manage budget allocation for each Department</p>
        </div>
        <div class="mt-4 md:mt-0">
            <div class="flex gap-3 items-center">
                <div class="mb-6 form-group">
                </div>
               <button id="toggleFormBtn" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl shadow-md flex items-center gap-2 transition">
            <i class="fas fa-plus-circle"></i>
              Create a Budget
         </button>
            </div>
        </div>
         </div>

         <div class="mt-8 bg-white p-6 rounded-lg shadow-md border border-gray-200 card">
            



<div  class="grid grid-cols-3 gap-6">
  <div id="budgetCardsContainer" class="col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
</div>









<div class="grid grid-cols-1 bg-gray-100 p-4 rounded-lg">
          

<div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
 
  <div class="mb-6">
    <h5 class="text-lg font-semibold text-gray-900">Allocation Summary</h5>
    <p class="text-sm text-gray-500">
      Overview of current budget allocation and utilization
    </p>
  </div>


  <div class="grid grid-cols-2 gap-6 mb-6">
    <div>
      <p class="text-xs uppercase tracking-wide text-gray-400">Total Budget</p>
      <p id="totalBudgetss" class="text-xl font-semibold text-gray-900 masked" data-value="">0</p>
    </div>

    <div>
      <p class="text-xs uppercase tracking-wide text-gray-400">Allocated</p>
      <p id="allocatedBudgets" class="text-xl font-semibold text-blue-600 masked" data-value="">0</p>
    </div>

    <div>
      <p class="text-xs uppercase tracking-wide text-gray-400">Remaining</p>
      <p id="remainingBudgets" class="text-xl font-semibold text-green-600 masked" data-value="">0</p>
    </div>

    <div>
      <p class="text-xs uppercase tracking-wide text-gray-400">Utilization</p>
      <p id="utilizationPercentagess" class="text-xl font-semibold text-gray-900 ">0</p>
    </div>
  </div>


  <div id="percentages" class="mb-6">
  
  </div>


  <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-4 py-3">
    <span class="text-sm font-medium text-gray-700">Status</span>
    <span class="text-sm font-semibold text-green-600">Healthy</span>
  </div>
</div>


<div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm mt-6">
  <div class="mb-6">
    <h5 class="text-xl font-bold text-gray-900">Financial Year</h5>
    <p class="text-sm text-gray-500 mt-1">Overview of the years and their status</p>
  </div>

  <div id="financialYearsContainer" class="space-y-4">
  
    <div class="text-center py-8 text-gray-400">
      Loading financial years...
    </div>
  </div>
</div>
  </div>
</div>



 </div>
</div>
</div>
</div>
</div>



<div id="noBudgetModal" class="modal-overlay">
  <div class="modal-contents large-modal">
    <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>

    <h3 class="modal-heading text-center text-4xl font-bold text-gray-900">No Budget Allocated Yet</h3>
    <p class="modal-message text-xl text-gray-600 mt-6 leading-relaxed text-center">
      There are no budgets allocated for this department in the selected year.<br>
      Start by allocating a budget to manage expenses.
    </p>

    <button id="btnAllocateBudget"
            class="mt-12 bg-indigo-600 hover:bg-indigo-700 text-white text-xl font-semibold px-14 py-6 rounded-2xl shadow-xl hover:shadow-2xl transition transform hover:-translate-y-1">
      <svg class="inline w-7 h-7 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
      </svg>
      Allocate Budget
    </button>
  </div>
</div>

<div id="allocatemodal" class="modal-overlay">
  <div class="modal-allocate">
    <button class="close-btn" onclick="closeAllocationModal()">&times;</button>

    <div class="modal-header">
      <h1>Allocate Expenses</h1>
      <p id="allocationHeaderText">Add and manage expenses for Department (FY 2025)</p>
    </div>

    <div class="info-bar">
      <div class="info-item">
        <div class="info-icon"></div>
        <div>
          <div class="info-label">Department</div>
          <div id="departmentName" class="info-value">Loading...</div>
          <div class="info-manager">Manager: James Aries</div>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon"></div>
        <div>
          <div class="info-label">Fiscal Year</div>
          <div id="fiscalYear" class="info-value">FY 2025</div>
        </div>
      </div>
    </div>

    <div class="budget-card">
      <h2 class="budget-title">Department Name</h2>
      <p class="budget-subtitle">Fiscal Year 2025</p>
      <div class="check-icon">✓</div>

      <div class="budget-grid">
        <div class="budget-item">
          <h3>Total Budget</h3>
          <h4 class="total">₱0.00</h4>
        </div>
        <div class="budget-item">
          <h3>Allocated / Used</h3>
          <h4 class="used">₱0.00</h4>
        </div>
        <div class="budget-item">
          <h3>Remaining</h3>
          <h4 class="remaining">₱0.00</h4>
        </div>
      </div>

      <div class="utilization">
        <div class="utilization-label">
          <span>Budget Utilization</span>
          <span class="util-percent">0%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width: 0%;"></div>
        </div>
      </div>
    </div>

    <div class="expenses-section">
      <div class="expenses-header">
        <div>
          <div class="expenses-title">Unallocated Expenses</div>
          <div class="expenses-subtitle">0 expenses available for allocation</div>
        </div>
        <button class="select-all" id="aiAllocateBtn">Allocate with AI</button>

      </div>

      <div id="expensesList">
     
        <div class="text-center py-8 text-gray-500">
          Loading unallocated expenses...
        </div>
        
      </div>
    <button id="verifyAllocationBtn" class="verify-btn" onclick="showVerificationStage()" disabled>
    Verify Allocation
  </button>
    </div>

<div id="verificationStage" class="verification-stage" style="display: none; margin-top: 30px;">
 
  <div id="verifiedAccountsList" class="verified-list"></div>
  
  <div style="text-align: center; margin-top: 30px; display: flex; justify-content: center; gap: 20px;">
    <button onclick="backToAllocation()" class="back-btn">← Back to Edit</button>
    <button onclick="confirmAndSave()" class="confirm-btn">Confirm & Save</button>
  </div>
</div>
  </div>

</div>


<div id="budgetDetailsModal" class="modal-overlay">
  <div class="modal-budget large-modal">
                        <div id="budgetDetailsContainer" style="width: 100%;"></div>
                        <button onclick="closeBudgetDetailsModal()" 
                                style="position: absolute; top: 16px; right: 24px; background: none; border: none; font-size: 32px; color: #9ca3af; cursor: pointer;">
                            ×
                        </button>
            <div id="budgetDetails">

            </div>
       </div>
</div>



    <div id="budgetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center p-4">
        <div class="rounded-lg shadow-xl p-6 w-full max-w-lg">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-bold">Create New Budget</h3>
                <button id="closeModalBtn" type="button" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
            
            <form id="budgetForm" method="POST">
                <div class="mb-4 form-group">
                    <label class="block text-gray-700 font-semibold mb-2">Department Name</label>
                    <select id="deptNameSelect" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">

                    </select>
                    <input type="text" id="deptNameInput" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 hidden mt-2" placeholder="Enter Department Name">
                    <input type="hidden" id="realDeptName" name="deptName">
                    <button type="button" id="addNewDeptBtn" class="mt-2 text-indigo-600 hover:underline text-sm">➕ Add New</button>
                </div>

                <div class="mb-4 form-group">
                    <label for="budgetYear" class="block text-gray-700 font-semibold mb-2">Year</label>
                    <select id="budgetYear" name="budgetYear" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
          
                    </select>
                </div>
                
                <div class="mb-4 form-group">
                    <label for="budgetAmount" class="block text-gray-700 font-semibold mb-2">Budget Amount (₱)</label>
                    <input type="number" id="budgetAmount" name="budgetAmount" step="0.01" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required min="0">
                    <small id="maxAllowedMsg" class="text-gray-500 text-sm mt-1 hidden">Maximum allowed: ₱<span id="maxValue"></span></small>
                </div>

                <div class="mb-4 form-group">
                    <label class="block text-gray-700 font-semibold mb-2">Budget Details</label>
                    <textarea id="budgetDetailsInput" name="budgetDetails" rows="4" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter Budget Details"></textarea>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">Submit Budget</button>
                </div>
            </form>
        </div>
      
    </div>

<script src="buget.js?v=<?php echo time(); ?>"></script>
<script src="budgetwithai.js?v=<?php echo time(); ?>"></script>
</body>
</html>