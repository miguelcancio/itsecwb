<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_role(['customer','manager','admin']);
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>My Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['email']); ?>.</p>
    <a class="btn" href="/customer/my_reservations.php">My Reservations</a>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


