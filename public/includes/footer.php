<?php declare(strict_types=1); ?>
</div>
<footer>
  <small>&copy; <?php echo date('Y'); ?> Dorm Reservation</small>
  <?php if (!empty($_SESSION['user']['last_login_at'])): ?>
    <small style="float:right">Last login: <?php echo htmlspecialchars($_SESSION['user']['last_login_at']); ?> from <?php echo htmlspecialchars($_SESSION['user']['last_login_ip'] ?? ''); ?></small>
  <?php endif; ?>
</footer>
</body>
</html>


