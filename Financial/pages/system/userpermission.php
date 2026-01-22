
<?php
require_once __DIR__ . '/../../utility/connection.php'; 
include_once('../../utility/head.php');

$error_message = '';
$success_message = '';

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = trim($_POST['new_username'] ?? '');
    $email = trim($_POST['new_email'] ?? '');
    $pass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $pass === '' || $confirm === '') {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($pass !== $confirm) {
        $error_message = "Passwords do not match.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM settings.users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error_message = "Username or email already exists.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO settings.users (username, password, email, role, created_at) VALUES (?, ?, ?, 'Admin', NOW())");
                if ($stmt->execute([$username, $hash, $email])) {
                    $success_message = "Sign up successful! You may now log in.";
                } else {
                    $error_message = "Unable to create account. Try again later.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log </title>
    <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 
  


<style>
  .page-container {
 
  padding: 1.5rem;
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 1.25rem;
}

.form-group {
  margin-bottom: 1rem;
}

.error-message {
  background-color: #fee2e2;
  color: #dc2626;
  padding: 0.75rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.success-message {
  background-color: #d1fae5;
  color: #065f46;
  padding: 0.75rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

input, select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}


.tabs .tab {
  background-color: transparent;
}

.tabs .tab:hover {
  background-color: #e5e7eb;
}

.tabs .tab.active {
  background-color: #e5e7eb;
  color: #2563eb;
  font-weight: 600;
}
</style>
<!-- ===================== ADMIN ACCOUNT CREATION ===================== -->
<div class="page-container rounded-xl p-6 shadow-lg mt-5">
  <h2 class="text-2xl font-semibold text-gray-800 mb-6">Create New Account</h2>
  <?php if ($error_message): ?>
    <div class="error-message bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error_message) ?></div>
  <?php endif; ?>
  <?php if ($success_message): ?>
    <div class="success-message bg-green-100 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($success_message) ?></div>
  <?php endif; ?>

  <form method="post" novalidate class="space-y-4">
    <div class="form-group">
      <label for="new_username" class="block text-sm font-medium text-gray-700">Username</label>
      <input type="text" id="new_username" name="new_username" placeholder="Enter username" required
             value="<?= htmlspecialchars($_POST['new_username'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="form-group">
      <label for="new_email" class="block text-sm font-medium text-gray-700">Email</label>
      <input type="email" id="new_email" name="new_email" placeholder="Enter email" required
             value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="form-group">
      <label for="new_password" class="block text-sm font-medium text-gray-700">Password</label>
      <input type="password" id="new_password" name="new_password" placeholder="Enter password" required
             class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="form-group">
      <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required
             class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="form-group">
      <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
      <select id="role" name="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="Admin">Admin</option>
        <option value="Manager">Manager</option>
        <option value="Staff">Staff</option>
        <option value="Finance Officer">Finance Officer</option>
      </select>
    </div>
    <button type="submit" name="create_account" class="mt-4 w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
      Create Account
    </button>
  </form>
</div>

<!-- ===================== USER PERMISSIONS (UNCHANGED) ===================== -->
<div class="page-container rounded-xl p-6 shadow-lg mt-5">
  <h1 class="page-title text-3xl font-semibold mb-6">User Permissions</h1>
  <div class="tabs flex items-center gap-3 mb-5 border-b-2 border-gray-200 pb-2 form-group">
    <button class="tab px-4 py-2 cursor-pointer font-medium rounded-t-lg transition-all duration-300 hover:bg-gray-200" data-tab="all">All Users</button>
    <button class="tab px-4 py-2 cursor-pointer font-medium rounded-t-lg transition-all duration-300 hover:bg-gray-200" data-tab="active">Active</button>
    <input type="text" id="search" placeholder="Search users..." class="ml-auto px-4 py-2 border-2 rounded-lg text-sm focus:border-blue-500 focus:outline-none transition-colors duration-300 form-group" />
  </div>

  <div class="table-wrapper overflow-x-auto">
    <table id="userTable" class="w-full border-collapse border-spacing-0">
      <thead>
        <tr>
          <th class="font-semibold uppercase text-xs">Username</th>
          <th class="font-semibold uppercase text-xs">Email</th>
          <th class="font-semibold uppercase text-xs">Role</th>
          <th class="font-semibold uppercase text-xs">Permissions</th>
          <th class="font-semibold uppercase text-xs">Action</th>
        </tr>
      </thead>
      <tbody id="userTableBody">
        <tr><td colspan="5" class="loading text-center text-gray-400 italic py-4">Loading users...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ===================== MODAL (UNCHANGED) ===================== -->
<div id="modal" class="modal fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
  <div class="card w-full max-w-md mx-4 rounded-xl shadow-2xl p-6 relative animate-fadeIn">
    <div class="flex justify-between items-center border-b border-gray-200 pb-3 mb-4">
      <h2 id="modalTitle" class="text-lg font-semibold text-blue-700">Edit Permissions</h2>
      <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
    </div>
    <div id="permissionList" class="space-y-3 max-h-72 overflow-y-auto pr-2"></div>
    <div class="mt-6">
      <button id="savePermissions"
        class="w-full py-3 bg-blue-700 text-white rounded-lg font-medium hover:bg-blue-800 transition duration-300">
        Save Changes
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("userTableBody");
  const searchInput = document.getElementById("search");
  const tabs = document.querySelectorAll(".tab");
  const modal = document.getElementById("modal");
  const permissionList = document.getElementById("permissionList");
  const saveBtn = document.getElementById("savePermissions");
  const closeModal = document.getElementById("closeModal");

  let currentUsers = [];
  let currentTab = "all";
  let currentUser = null;
  let permissionsMap = {};

  async function fetchPermissions() {
    const res = await fetch("../../crud/system/user.php", { method: "OPTIONS" });
    const data = await res.json();
    permissionsMap = data.permissions || {};
  }

  async function loadUsers() {
    tableBody.innerHTML = `<tr><td colspan="5" class="loading text-center text-gray-400 italic py-4">Loading...</td></tr>`;
    const res = await fetch(`../../crud/system/user.php?q=${searchInput.value.trim()}`);
    const data = await res.json();
    if (data.status !== "success") {
      tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-gray-400 italic py-4">${data.message}</td></tr>`;
      return;
    }
    currentUsers = data.data;
    renderTable();
  }

  function renderTable() {
    const filtered = currentUsers.filter(u => {
      if (currentTab === "active") return u.status == 1;
      if (currentTab === "archived") return u.status == 0;
      return true;
    });

    if (filtered.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-gray-400 italic py-4">No users found.</td></tr>`;
      return;
    }

    tableBody.innerHTML = filtered.map(u => `
      <tr>
        <td class="px-5 py-3">${u.username}</td>
        <td class="px-5 py-3">${u.email}</td>
        <td class="px-5 py-3">${u.role}</td>
        <td class="px-5 py-3">${Object.keys(u.permissions || {}).filter(k => u.permissions[k]).length}</td>
        <td class="px-5 py-3"><button class="manage-btn px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600" data-id="${u.id}">Manage</button></td>
      </tr>
    `).join("");

    document.querySelectorAll(".manage-btn").forEach(btn =>
      btn.addEventListener("click", () => openModal(btn.dataset.id))
    );
  }

  async function openModal(id) {
    currentUser = currentUsers.find(u => u.id == id);
    if (!currentUser) return;

    permissionList.innerHTML = Object.entries(permissionsMap)
      .map(([key, label]) => {
        const checked = currentUser.permissions?.[key] ? "checked" : "";
        return `
          <div class="permission-item flex justify-between items-center mb-4 pb-2 border-b border-dashed border-gray-200">
            <label class="text-base">${label}</label>
            <input type="checkbox" id="${key}" ${checked} class="w-5 h-5 cursor-pointer">
          </div>
        `;
      }).join("");

    modal.classList.remove("hidden");
  }

  closeModal.addEventListener("click", () => modal.classList.add("hidden"));

  saveBtn.addEventListener("click", async () => {
    if (!currentUser) return;
    const updatedPerms = {};
    Object.keys(permissionsMap).forEach(k => {
      updatedPerms[k] = document.getElementById(k).checked;
    });

    const res = await fetch("../../crud/system/user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: currentUser.id, permissions: updatedPerms })
    });

    const data = await res.json();
    if (data.status === "success") {
      alert("Permissions updated successfully");
      modal.classList.add("hidden");
      await loadUsers();
    } else {
      alert("Error: " + data.message);
    }
  });

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      tabs.forEach(t => t.classList.remove("bg-gray-200", "text-blue-700", "font-semibold"));
      tab.classList.add("bg-gray-200", "text-blue-700", "font-semibold");
      currentTab = tab.dataset.tab;
      renderTable();
    });
  });

  searchInput.addEventListener("keyup", () => loadUsers());
  fetchPermissions().then(loadUsers);

  window.addEventListener("click", (e) => {
    if (e.target === modal) modal.classList.add("hidden");
  });
});
</script>


</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>

</body>

</html>