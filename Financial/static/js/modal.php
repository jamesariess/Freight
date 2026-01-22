


<script>

  const body = document.body;
const themeToggle = document.getElementById("themeToggle");


const savedTheme = localStorage.getItem("darkMode");

const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
if (savedTheme === "true" || (savedTheme === null && prefersDark)) {
  body.classList.add("dark-mode");
  themeToggle.checked = true;


} else {
  body.classList.remove("dark-mode");
  themeToggle.checked = false;
}


themeToggle.addEventListener("change", () => {
  const isDark = themeToggle.checked;
  body.classList.toggle("dark-mode", isDark);
  localStorage.setItem("darkMode", isDark);
  updateChartColors();
});
function changeLimit(val) {
    const url = new URL(location);
    url.searchParams.set('limit', val);
    url.searchParams.set('page', 1);
    location = url;
}
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const hamburger = document.getElementById('hamburger');
const overlay = document.getElementById('overlay');

// Sidebar toggle logic
hamburger.addEventListener('click', function() {
  if (window.innerWidth <= 992) {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  } else {
    // This is the key change for desktop
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded'); 
  }
});

// Close sidebar on overlay click
overlay.addEventListener('click', function() {
  sidebar.classList.remove('show');
  overlay.classList.remove('show');
});


    // Dropdown toggle logic
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            const parentDropdown = this.closest('.dropdown');
            parentDropdown.classList.toggle('active');
        });
    });
const notifButton = document.getElementById('notifButton');
const notifModal = document.getElementById('notificationModal');

notifButton.addEventListener('click', () => {
  notifModal.style.display = notifModal.style.display === 'block' ? 'none' : 'block';
});

// Close modal when clicking outside
document.addEventListener('click', (e) => {
  if (!notifButton.contains(e.target) && !notifModal.contains(e.target)) {
    notifModal.style.display = 'none';
  }
});

    
</script>

<link rel="stylesheet" href="../../static/css/loader.css">
<?php include_once '../loader.html'; ?>
<script src="../../static/js/loader.js"></script>
