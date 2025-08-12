<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_role(['customer','manager','admin']);
include __DIR__ . '/../includes/header.php';
?>
  <section class="hero">
    <div class="hero-content">
      <h1>Book Your Dorm Room Easily</h1>
      <p class="subtitle">Secure and hassle-free dormitory reservations for students.</p>
    </div>
  </section>
  <div class="cta-center">
    <a class="btn btn-large" href="/customer/my_reservations.php">View My Reservations</a>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


