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
    /* Layout */
    html, body { height: 100%; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin:0; display:flex; flex-direction:column; min-height:100vh; }
    header, footer { background:#0f172a; color:#fff; padding:12px 16px; }
    footer { margin-top:auto; }
    nav a { color:#fff; margin-right:12px; text-decoration:none; }
    .nav-inner { display:flex; align-items:center; gap:16px; }
    .nav-spacer { flex:1 1 auto; }
    .brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:#fff; }
    .brand img { height:32px; width:auto; display:block; }
    .container { max-width: 960px; margin: 16px auto; padding: 0 12px; flex: 1 0 auto; }

    /* UI */
    .card { border:1px solid #e5e7eb; border-radius:8px; padding:16px; margin:12px 0; }
    .btn { display:inline-block; background:#2563eb; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; transition: all .2s ease; }
    .btn:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .btn.secondary { background:#64748b; }
    .btn.btn-large { padding:12px 18px; font-size:16px; font-weight:600; border-radius:8px; background:#22c55e; }
    .btn.btn-large:hover { filter: brightness(1.07); }
    .error { color:#b91c1c; }
    .success { color:#166534; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; }

    /* Hero used on customer dashboard */
    .hero { position:relative; background-image: url('/asset/dorm1.jpg'); background-size: cover; background-position: center; border-radius:0; min-height: 360px; display:flex; align-items:center; padding:64px 24px; overflow:hidden; }
    /* Make hero full-bleed within a contained layout */
    .container > .hero { margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw); width: 100vw; }
    .hero::after { content:""; position:absolute; inset:0; background: rgba(15, 23, 42, 0.55); }
    .hero .hero-content { position:relative; z-index:1; color:#fff; max-width: 820px; margin: 0 auto; text-align:center; }
    .hero h1 { margin:0 0 12px 0; font-size:40px; font-weight:800; color:#fff; }
    .hero .subtitle { margin:0; font-size:18px; font-style: italic; color:#fff; opacity:0.98; }

    .cta-center { text-align:center; margin: 20px 0 0 0; }
  </style>
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'"> 
</head>
<body>
<header>
  <nav class="nav-inner">
    <a class="brand" href="/index.php" aria-label="Home">
      <img src="/asset/logo.png" alt="Dorm Reservation Logo" />
    </a>
    <div class="nav-spacer"></div>
    <?php if ($user && ($user['role'] ?? null) === 'customer'): ?>
      <a href="/customer/dashboard.php">Home</a>
    <?php else: ?>
      <a href="/index.php">Home</a>
    <?php endif; ?>
    <?php if ($user): ?>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="/admin/dashboard.php">Admin Dashboard</a>
        <a href="/admin/manage_users.php">Manage Users</a>
        <a href="/manager/manage_reservations.php">Reservations</a>
        <a href="/admin/view_logs.php">Logs</a>
      <?php elseif ($user['role'] === 'manager'): ?>
        <a href="/manager/dashboard.php">Manager Dashboard</a>
        <a href="/manager/manage_reservations.php">Reservations</a>
      <?php elseif ($user['role'] === 'customer'): ?>
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


