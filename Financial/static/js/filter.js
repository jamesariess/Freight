
function resetFilters() {
    const ids = ["myInput", "plantypefilter", "statusfilter", "minAmount", "maxAmount", "startDate", "endDate","crminAmount", "crmaxAmount"];

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (el.tagName === "SELECT") {
                el.value = "All";  
            } else {
                el.value = "";   
            }
        }
    });

    applyFilters();
}


let asc = true; 
function toggleSort() { const table = document.getElementById('employeesTable'); 
    const tbody = table.tBodies[0]; const rows = Array.from(tbody.rows); 
    rows.reverse().forEach(r => tbody.appendChild(r)); 
    asc = !asc;
     document.getElementById('sortBtn').textContent = asc ? '⇅ Sort Asc' : '⇵ Sort Desc'; }