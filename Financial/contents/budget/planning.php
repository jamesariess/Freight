<div  class="mb-4">
    <div class=" rounded-2xl shadow-xl p-6  table-section">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold  flex items-center">
                <i class="fas fa-check-circle text-indigo-500 mr-2"></i>
                Approval Section
            </h2>
        </div>
        
        
        <div id="approvalCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </div>
</div>



<!-- View Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     onclick="outsideClick(event, 'detailsModal')">

  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative"
       onclick="event.stopPropagation()">

    <div class="flex items-center justify-between mb-6 border-b pb-3">
      <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-info-circle text-blue-600"></i>
        Budget Request Details
      </h2>
      <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
              onclick="closeModals()">&times;</button>
    </div>
    <div id="modalContent" class="grid grid-cols-2 gap-x-6 gap-y-3 text-gray-700 text-sm">
 
    </div>


    <div id="modalButtons" class="mt-6 flex justify-end">
    </div>
  </div>
</div>
