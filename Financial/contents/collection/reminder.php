

    <header class="card">
        <div class="brand">
            <div class="logos">RF</div>
            <div>
                <h1>Reminders & Follow-ups</h1>
                <p class="lead">Manage automated and manual reminder plans for invoices and collections.</p>
            </div>
            </div>
    </header>
    <br>

<div class="container pb-4">
  <div class=" grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

   
    <div onclick="totalReminders()" class="card quick-stat-card purple border-b-2 border-opacity-50">
      <div class="flex items-center mb-3" >
        <i class="fas fa-bell text-purple-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Total Reminders</h3>
      </div>
      <div class=" flex items-end justify-between">
        <div  class="text-3xl font-bold text-purple-600"><span id="totalRequest">0</span></div>
        <span class="text-gray-500 text-sm">All</span>
      </div>
    </div>

    <div onclick="totalPaid()" class="quick-stat-card card green border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Paid</h3>
      </div>
      <div class="flex items-end justify-between">
        <div  class="text-3xl font-bold text-green-600"><span id="totalAmountRelease">0</span></div>
        <span class="text-gray-500 text-sm">Settled</span>
      </div>
    </div>

 
    <div onclick="totalPending()" class="quick-stat-card red card border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Pendig</h3>
      </div>
      <div class="flex items-end justify-between">
        <div class="text-3xl font-bold text-red-600"><span id="rejectedRequest">0</span></div>
        <span class="text-gray-500 text-sm">Pending</span>
      </div>
    </div>

   
    <div onclick="totalFailed()" class="quick-stat-card yellow card border-b-2 border-opacity-50">
      <div class="flex items-center mb-3">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-2"></i>
        <h3 class="text-base font-semibold text-gray-800">Failed</h3>
      </div>
      <div class="flex items-end justify-between">
        <div  class="text-3xl font-bold text-yellow-600"><span id="Failed">0</span></div>
        <span class="text-gray-500 text-sm">Failed Reminder</span>
      </div>
    </div>

  </div>
</div>

    <div class="grids">
      <div class="left">
        <div class="card">
          <h2>Overview <small class="muted">(Today)</small></h2><div >
        <div class="list" id="reminderList" style="margin-top:16px"></div>
        </div>
    </div>


      <div class="card" style="margin-top:16px">
      <h2>Follow-up Queue <small class="muted">(Awaiting action)</small></h2>
       <div id="followup-list" class="list"></div>
      </div>
      </div> 

<aside class="side">
    <div class="card section">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <h2 style="margin:0;">Template Preview</h2>
            <div class="carousel-controls">
                <button class="btn ghost" id="prevPlan" title="Previous template">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span id="planCounter" class="muted" style="font-size:12px; margin:0 6px;">0 / 0</span>
                <button class="btn ghost" id="nextPlan" title="Next template">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>


        <div id="planBadge" class="pill" style="margin-bottom:8px; display:none;">
            <span id="planName"></span>
        </div>

      
        <div id="templateSubject" class="template" style="font-weight:600; margin-bottom:6px; white-space:pre-wrap;"></div>

        <div id="templateBody" class="template" style="max-height:180px; overflow:auto; white-space:pre-wrap;"></div>
    </div>
</aside>

    </div> 
    
    
    <div class="card" style="margin-top:16px">
          <h2>Sent Log <small class="muted">(Recent)</small></h2>
          <div class="log">
            <table>
              <thead><tr><th>Time</th><th>Type</th><th>To</th><th>Status</th></tr></thead>
              <tbody>
               
              </tbody>
            </table>
     </div>
    </div>
      
 

<div class="modal" id="modal">
  <div class="modal-card">
    <h3 id="modalTitle">Send Reminder</h3>
    <p class="muted" id="modalSub">Invoice: —</p>

    <div style="margin-top:12px">
      <label style="font-size:13px;display:block;margin-bottom:6px">
        Message preview
      </label>
      <div class="template" id="modalPreview"
           style="max-height:150px;overflow:auto">—</div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
      <button class="btn" onclick="closeModal()">Cancel</button>
      <button class="btn primary" onclick="sendNow()">Send Now</button>
    </div>
  </div>
</div>


<div class="cardmodal" id="cardmodal">
  <div class="cardmodal-card">
    <div class="hala">
      <div></div>
    <h3 id="cardmodalTitle">Loading...</h3>
    <button class="btn" onclick="closeModal()"><i class="fa-solid fa-x"></i></button>
</div>
    <div id="cardmodalContent">
      <table></table>
    </div>

    
  </div>
</div>


<script src="../../static/js/jsreminder.js?v=<?php echo time();?>"></script>

