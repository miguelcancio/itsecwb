<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

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

    /* Login Notification */
    .login-notification {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        padding: 20px 24px;
        border-radius: 16px;
        margin: 20px auto;
        max-width: 900px;
        width: calc(100% - 32px);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
        position: relative;
        overflow: visible;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }
    
    .login-notification::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #60a5fa, #3b82f6, #1d4ed8);
        border-radius: 16px 16px 0 0;
    }
    
    .login-notification .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .login-notification .notification-title {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .login-notification .notification-title::before {
        content: '🔐';
        font-size: 24px;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    }
    
    .login-notification .dismiss-btn {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.2s ease;
        line-height: 1;
        min-width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .login-notification .dismiss-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .login-notification .notification-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 16px;
    }
    
    .login-notification .info-item {
        background: rgba(255, 255, 255, 0.12);
        padding: 16px;
        border-radius: 12px;
        border-left: 4px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(10px);
        transition: all 0.2s ease;
    }
    
    .login-notification .info-item:hover {
        background: rgba(255, 255, 255, 0.16);
        transform: translateY(-1px);
    }
    
    .login-notification .info-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.85;
        margin-bottom: 6px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .login-notification .info-value {
        font-size: 15px;
        font-weight: 600;
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Courier New', monospace;
        color: #ffffff;
        word-break: break-all;
        line-height: 1.4;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .login-notification .warning {
        background: rgba(245, 158, 11, 0.15);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-left: 4px solid #f59e0b;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        color: #ffffff;
        font-weight: 500;
    }
    
    .login-notification .warning::before {
        content: '⚠️';
        font-size: 16px;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    }
    
    .login-notification .warning strong {
        color: #fbbf24;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .login-notification {
            margin: 16px;
            padding: 18px 20px;
            border-radius: 14px;
        }
        
        .login-notification .notification-content {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .login-notification .notification-title {
            font-size: 18px;
        }
        
        .login-notification .info-item {
            padding: 14px;
        }
    }
    
    @media (max-width: 480px) {
        .login-notification {
            margin: 12px;
            padding: 16px 18px;
            border-radius: 12px;
        }
        
        .login-notification .notification-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .login-notification .dismiss-btn {
            align-self: flex-end;
        }
    }

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
    .promo { background:#b29279; color:#fff; padding:120px 24px; margin-top:12px; }
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
    <?php if ($user): ?>
      <?php if ($user['role'] === 'customer'): ?>
        <a href="/customer/dashboard.php">Home</a>
      <?php endif; ?>
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

<?php
// Display login notification if requested
if ($user && isset($_SESSION['show_login_info']) && $_SESSION['show_login_info'] === true) {
    $lastLoginAt = $user['last_login_at'] ?? null;
    $lastLoginIp = $user['last_login_ip'] ?? null;
    $currentIp = get_client_ip();
    
    // Always show the notification when requested, even if database values are null
    // Use session data which we know is correct from the login process
    if ($lastLoginAt && $lastLoginIp) {
        $lastLoginTime = new DateTime($lastLoginAt);
        $lastLoginTime->setTimezone(new DateTimeZone('Asia/Manila'));
        $formattedTime = $lastLoginTime->format('F j, Y \a\t g:i A T');
        
        $ipDiffers = $lastLoginIp !== $currentIp;
        ?>
        <div class="login-notification" id="loginNotification">
            <div class="notification-header">
                <h3 class="notification-title">Welcome Back!</h3>
                <button class="dismiss-btn" onclick="dismissLoginNotification()" title="Dismiss notification">×</button>
            </div>
            <div class="notification-content">
                <div class="info-item">
                    <div class="info-label">Last Login Time</div>
                    <div class="info-value"><?php echo htmlspecialchars($formattedTime); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Login IP Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($lastLoginIp); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current IP Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentIp); ?></div>
                </div>
            </div>
            <?php if ($ipDiffers): ?>
                <div class="warning">
                    <strong>Security Notice:</strong> Your current IP address differs from your last login location.
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        function dismissLoginNotification() {
            const notification = document.getElementById('loginNotification');
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
            
            // Send AJAX request to clear the session flag
            fetch('/clear_login_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'dismiss' })
            }).catch(console.error);
        }
        </script>
        <?php
        // Clear the session flag after displaying
        unset($_SESSION['show_login_info']);
    } else {
        // If database values are null, show a simplified notification with current info
        ?>
        <div class="login-notification" id="loginNotification">
            <div class="notification-header">
                <h3 class="notification-title">Welcome Back!</h3>
                <button class="dismiss-btn" onclick="dismissLoginNotification()" title="Dismiss notification">×</button>
            </div>
            <div class="notification-content">
                <div class="info-item">
                    <div class="info-label">Current IP Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentIp); ?></div>
                </div>
                                 <div class="info-item">
                     <div class="info-label">Login Time</div>
                     <div class="info-value"><?php 
                         $currentTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
                         echo htmlspecialchars($currentTime->format('F j, Y \a\t g:i A T')); 
                     ?></div>
                 </div>
            </div>
        </div>
        
        <script>
        function dismissLoginNotification() {
            const notification = document.getElementById('loginNotification');
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
            
            // Send AJAX request to clear the session flag
            fetch('/clear_login_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'dismiss' })
            }).catch(console.error);
        }
        </script>
        <?php
        // Clear the session flag after displaying
        unset($_SESSION['show_login_info']);
    }
}
?>

<div class="container">


