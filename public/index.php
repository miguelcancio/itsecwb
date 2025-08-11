<?php declare(strict_types=1); ?>
<?php include __DIR__ . '/includes/header.php'; ?>
  <div class="card">
    <h1>Welcome to Dorm Reservation</h1>
    <p>Reserve rooms securely with role-based access controls.</p>
    <?php if (empty($_SESSION['user'])): ?>
      <a class="btn" href="/login.php">Login</a>
      <a class="btn secondary" href="/register.php">Register</a>
    <?php else: ?>
      <p>You are logged in as <?php echo htmlspecialchars($_SESSION['user']['email']); ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)</p>
    <?php endif; ?>
  </div>
<?php include __DIR__ . '/includes/footer.php'; ?>

