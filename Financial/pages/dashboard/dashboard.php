<?php include_once('../../crud/system/dashboard.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approver Management</title>
   <?php include "../../static/head/header.php" ?>
<style>
 #darkOverlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65); 
    z-index: 40; 
    backdrop-filter: blur(3px);
    transition: opacity 0.3s ease;
    opacity: 0;
    pointer-events: none;
  }

  body.modal-open #darkOverlay {
    opacity: 1;
    pointer-events: auto;
  }
  .sidebar.modal-open #darkOverlay {
    opacity: 1;
    pointer-events: auto;
  }
  .cards 
  {
    background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border-radius:12px;
 
}

.cards h2
{
    font-weight:700;
    margin:0;
    font-size:14px;
    text-align:center;
}
</style>

</head>
<body>
    <?php include "../sidebar.php"; ?>


    <div class="w-full h-full space-y-8">
        <header>
            <h1 class="text-3xl font-bold text-var(--text-light)">Dashboard</h1>
           
        </header>

        <section>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <div class="bg-slate-900 p-6 rounded-xl shadow-lg border border-slate-800 transition-all duration-300 hover:scale-105 hover:border-blue-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-slate-400">Disbursement</h3>
                        <div class="p-2 rounded-full bg-blue-500/20 text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-down icon"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-var(--text-light) mb-1"><?php echo getTotalDisburseAmount($pdo); ?></p>
                    <p class="text-xs text-slate-500">Total OF Cash Release</p>
                </div>

                <div class="bg-slate-900 p-6 rounded-xl shadow-lg border border-slate-800 transition-all duration-300 hover:scale-105 hover:border-red-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-slate-400">Accounts Payable</h3>
                        <div class="p-2 rounded-full bg-red-500/20 text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text icon"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="21" y2="21"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-var(--text-light) mb-1"><?php echo getTotalOutstanding($pdo); ?></p>
                    <p class="text-xs text-slate-500">ToTal of Payment</p>
                </div>

                <div class="bg-slate-900 p-6 rounded-xl shadow-lg border border-slate-800 transition-all duration-300 hover:scale-105 hover:border-yellow-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-slate-400">Accounts Receivable</h3>
                        <div class="p-2 rounded-full bg-yellow-500/20 text-yellow-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-receipt icon"><path d="M4 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v20l-2-2-2 2-2-2-2 2-2-2-2 2-2-2-2 2-2-2-2 2-2-2-2 2V2z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-var(--text-light) mb-1"><?php echo getTotalPayment($pdo);?></p>
                    <p class="text-xs text-slate-500">Total OF Sales </p>
                </div>

                <div class="bg-slate-900 p-6 rounded-xl shadow-lg border border-slate-800 transition-all duration-300 hover:scale-105 hover:border-green-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-slate-400">Collection</h3>
                        <div class="p-2 rounded-full bg-green-500/20 text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-coins icon"><path d="M9.8 19.95 2.15 15.8a1 1 0 0 1 0-1.6L9.8 10.05a1 1 0 0 1 1.4.15L18.4 14.8a1 1 0 0 1 0 1.6l-7.25 4.05a1 1 0 0 1-1.4-.15z"/><path d="m15 10-8.6 4.86a1 1 0 0 0 0 1.76L15 21l8.6-4.86a1 1 0 0 0 0-1.76L15 10z"/><path d="m7 7 8.6 4.86a1 1 0 0 0 0 1.76L7 18l-8.6-4.86a1 1 0 0 0 0-1.76L7 7z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-var(--text-light) mb-1"><?php echo getFollowUp($pdo);?></p>
                    <p class="text-xs text-slate-500">Need To follow Up</p>
                </div>
                
                <div class="bg-slate-900 p-6 rounded-xl shadow-lg border border-slate-800 transition-all duration-300 hover:scale-105 hover:border-purple-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-slate-400">Budget Management</h3>
                        <div class="p-2 rounded-full bg-purple-500/20 text-purple-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pie-chart icon"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10Z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-var(--text-light) mb-1"><?php echo getUtilization($pdo);?></p>
                    <p class="text-xs text-slate-500">Percentage of Used Budget</p>
                </div>

                <div class="bg-slate-900 p-6 rounded-xl shadow-lg border border-slate-800 transition-all duration-300 hover:scale-105 hover:border-orange-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-slate-400">General Ledger</h3>
                        <div class="p-2 rounded-full bg-orange-500/20 text-orange-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-text icon"><path d="M2 11h20M12 2v20M2 15h20M2 19h20M2 7h20"/><path d="M2 3v18M22 3v18"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-var(--text-light) mb-1"><?php echo getTotalEntires($pdo);?> Entries</p>
                    <p class="text-xs text-slate-500">Total Entries</p>
                </div>
            </div>
        </section>



        <section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="lg:col-span-3">
                <div class="bg-slate-900 p-12 rounded-xl shadow-lg border border-slate-800 h-96 ">
                    <h2 class="text-xl font-semibold mb-2 text-var(--text-light) text-center">Revenue X Expenses</h2>
                    <canvas id="financialChart" class="w-full h-full"></canvas>
                </div>
            </div>
            <div class="bg-slate-900 p-5 rounded-xl shadow-lg border border-slate-800">
            <div class="cards">
            <h2>Today Remiders <small class="muted"></small></h2><div >
            <div class="list" id="reminderList" style="margin-top:16px"></div>
        </div>
            </div>
           
        </section>
           <?php include "../../contents/system/dashboard.php"; ?>

    </div>
</div>

<?php include "../system/terms.php"; ?>

<?php include "../../static/js/modal.php" ;?>
<script>
   
 
    function getCssVariable(name) {
        return getComputedStyle(document.body).getPropertyValue(name).trim();
    }

const ctx = document.getElementById('financialChart').getContext('2d');
const financialChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode($chartRevenue); ?>,
            borderColor: colors.revenueColor,
            tension: 0.3,
            fill: false,
        }, {
            label: 'Expenses',
            data: <?php echo json_encode($chartExpenses); ?>,
            borderColor: colors.expensesColor,
            tension: 0.3,
            fill: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: colors.text
                }
            }
        },
        scales: {
            x: {
                ticks: { color: colors.text },
                grid: { color: colors.grid }
            },
            y: {
                ticks: { color: colors.text },
                grid: { color: colors.grid }
            }
        }
    }
});

   
themeToggle.addEventListener('change', function() {
    document.body.classList.toggle('dark-mode', this.checked);

    isDarkMode = this.checked;
    colors = getChartColors(isDarkMode);

    financialChart.options.plugins.legend.labels.color = colors.textColor;
    financialChart.options.scales.x.ticks.color = colors.textColor;
    financialChart.options.scales.x.grid.color = colors.gridColor;
    financialChart.options.scales.y.ticks.color = colors.textColor;
    financialChart.options.scales.y.grid.color = colors.gridColor;

    financialChart.data.datasets[0].borderColor = colors.revenueColor;
    financialChart.data.datasets[1].borderColor = colors.expensesColor;

    financialChart.update();
});


    window.onload = createChart;
</script>




</body>
</html>