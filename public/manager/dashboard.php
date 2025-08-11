<?php
declare(strict_types=1);
require __DIR__ . '/../includes/role_check.php';
require_role(['manager','admin']);
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Manager Dashboard</h2>
    <p>Manage customer reservations.</p>
    <a class="btn" href="/manager/manage_reservations.php">Manage Reservations</a>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


