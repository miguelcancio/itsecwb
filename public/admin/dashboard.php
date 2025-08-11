<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_role('admin');
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Admin Dashboard</h2>
    <p>Manage users, rooms, and view logs.</p>
    <ul>
      <li><a class="btn" href="/admin/manage_users.php">Manage Users</a></li>
      <li><a class="btn" href="/admin/manage_rooms.php">Manage Rooms</a></li>
      <li><a class="btn secondary" href="/admin/view_logs.php">View Logs</a></li>
    </ul>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


