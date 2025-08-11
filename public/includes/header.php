<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dorm Reservation</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin:0; }
    header, footer { background:#0f172a; color:#fff; padding:12px 16px; }
    nav a { color:#fff; margin-right:12px; text-decoration:none; }
    .container { max-width: 960px; margin: 16px auto; padding: 0 12px; }
    .card { border:1px solid #e5e7eb; border-radius:8px; padding:16px; margin:12px 0; }
    .btn { display:inline-block; background:#2563eb; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; }
    .btn.secondary { background:#64748b; }
    .error { color:#b91c1c; }
    .success { color:#166534; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; }
  </style>
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'"> 
</head>
<body>
<header>
  <nav>
    <a href="/index.php">Home</a>
    <?php if ($user): ?>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="/admin/dashboard.php">Admin Dashboard</a>
        <a href="/admin/manage_users.php">Manage Users</a>
        <a href="/admin/view_logs.php">Logs</a>
      <?php elseif ($user['role'] === 'manager'): ?>
        <a href="/manager/dashboard.php">Manager Dashboard</a>
        <a href="/manager/manage_reservations.php">Reservations</a>
      <?php elseif ($user['role'] === 'customer'): ?>
        <a href="/customer/dashboard.php">My Dashboard</a>
        <a href="/customer/my_reservations.php">My Reservations</a>
      <?php endif; ?>
      <a href="/<?php echo htmlspecialchars($user['role']); ?>/change_password.php">Change Password</a>
      <a href="/logout.php" class="btn secondary">Logout</a>
    <?php else: ?>
      <a href="/login.php">Login</a>
      <a href="/register.php" class="btn">Register</a>
    <?php endif; ?>
  </nav>
</header>
<div class="container">


