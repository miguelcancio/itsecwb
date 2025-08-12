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
    .btn.accent { background:#b29279; color:#fff; }
    .btn.accent:hover { filter: brightness(1.05); }
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

    /* Full-bleed promo band */
    .container > .full-bleed { margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw); width: 100vw; }
    .promo { background:#b29279; color:#fff; padding:120px 24px; margin-top:160px; }
    .promo p { margin:0; text-align:center; font-style:italic; font-size:26px; }

    /* Features/cards */
    .features { margin: 40px 0; }
    .features h2 { text-align:center; margin: 0 0 20px 0; font-size:24px; }
    .features-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:16px; }
    .feature-card { border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#fff; display:flex; flex-direction:column; }
    .feature-card img { width:100%; height:160px; object-fit:cover; display:block; }
    .feature-card .content { padding:12px; }
    .feature-card .title { margin:0 0 6px 0; font-weight:700; font-size:16px; color:#0f172a; }
    .feature-card .desc { margin:0; color:#334155; font-size:14px; line-height:1.4; }
    @media (max-width: 640px) { .features-grid { grid-template-columns: 1fr; } }

    /* Auth landing on index */
    .container > .auth-landing { margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw); width: 100vw; }
    .auth-grid { display:grid; grid-template-columns: 1.2fr 1fr; min-height: 520px; }
    .auth-hero { position:relative; background-image:url('/asset/welcome.jpg'); background-size:cover; background-position:center; }
    .auth-hero::after { content:""; position:absolute; inset:0; background:rgba(15,23,42,0.5); }
    .auth-hero .content { position:relative; z-index:1; color:#fff; padding:48px; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; gap:8px; }
    .auth-hero h1 { margin:0; font-size:36px; font-weight:800; }
    .auth-hero p { margin:0; font-size:16px; opacity:0.95; }
    .auth-panels { background:#5b7fa2; display:flex; align-items:center; justify-content:center; padding:24px; }
    .auth-cards { display:grid; grid-template-columns: 1fr; gap:16px; width:100%; max-width: 460px; }
    .auth-card, .auth-panel-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; }
    .auth-card h3, .auth-panel-card h3 { margin:0 0 8px 0; font-size:18px; }
    .form-field { margin:10px 0; }
    .form-field input[type="email"], .form-field input[type="password"], .form-field input[type="text"] { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; }
    .password-field { position:relative; }
    .password-toggle { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; font-size:16px; }
    .form-actions { margin-top:12px; display:flex; gap:8px; align-items:center; }
    .form-row { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .toggle-link { background:transparent; border:none; color:#2563eb; cursor:pointer; text-decoration:underline; padding:0; }
    @media (max-width: 900px) { .auth-grid { grid-template-columns: 1fr; } .auth-hero .content { align-items:center; text-align:center; } }
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


