<div class="icon-btn notification-wrapper" id="notifButton" title="Notifications">
  <i class="fa-solid fa-bell"></i>
  <span class="badge" id="notifBadge" style="display:none;">0</span>
</div>


<div class="notification-modal" id="notificationModal">
  <div class="notif-header">
    <h3>Notifications</h3>
    <button id="clearNotif" class="clear-btn">Clear All</button>
  </div>
  <ul class="notif-list" id="notifList"></ul>
</div>

<script>
async function loadNotifications() {
  try {
    const res = await fetch('../../api/notif/fetch_notifications.php');
    const data = await res.json();
    const notifList = document.getElementById('notifList');
    const badge = document.getElementById('notifBadge');

    notifList.innerHTML = '';

    if (data.length === 0) {
      notifList.innerHTML = `<li class="notif-item"><p>No notifications.</p></li>`;
      badge.style.display = 'none';
      return;
    }

    const unread = data.filter(n => n.is_read == 0).length;
    badge.textContent = unread;
    badge.style.display = unread > 0 ? 'inline-block' : 'none';

    data.forEach(n => {
      const item = document.createElement('li');
      item.className = `notif-item ${n.is_read == 0 ? 'new' : ''}`;
      item.innerHTML = `
        <i class="fa-solid ${n.icon || 'fa-bell'}"></i>
        <div class="notif-text">
          <strong>${n.title}</strong>
          <p>${n.message}</p>
          <span>${new Date(n.created_at).toLocaleString()}</span>
        </div>
      `;
      notifList.appendChild(item);
    });
  } catch (err) {
    console.error('Error loading notifications:', err);
  }
}



notifButton.addEventListener('click', async (e) => {
  e.stopPropagation();
  notifModal.classList.toggle('show');
  await fetch('../../api/notif/read.php');
  loadNotifications();
});

// Click outside to close
document.addEventListener('click', (e) => {
  if (!notifModal.contains(e.target) && !notifButton.contains(e.target)) {
    notifModal.classList.remove('show');
  }
});

// Clear all
document.getElementById('clearNotif').addEventListener('click', async () => {
  await fetch('../../api/notif/read.php');
  loadNotifications();
});

// Auto-refresh
setInterval(loadNotifications, 30000);
loadNotifications();
</script>
