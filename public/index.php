<?php declare(strict_types=1);
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/logger.php';
ensure_session_started(); 

// If this request reached index.php for a non-root path, serve 404
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($__path !== '/' && $__path !== '/index.php') {
  http_response_code(404);
  log_event('not_found', 'Route not found', ['path' => $__path]);
  require __DIR__ . '/errors/404.php';
  exit;
}

// Get security status for display (only if email was provided in previous attempt)
$securityMessages = [];
$errorMessage = '';
$email = $_GET['email'] ?? '';

// Check for error messages from login process
if (isset($_GET['error'])) {
    $errorMessage = 'Invalid username and/or password';
}

if ($email && validate_email($email)) {
    $user = get_user_by_email($email);
    if ($user) {
        $failedAttempts = (int)($user['failed_attempts'] ?? 0);
        $isLocked = is_account_locked($user);
        
        if ($isLocked && !empty($user['locked_until'])) {
            $lockTime = strtotime($user['locked_until']);
            $remainingMinutes = max(0, ceil(($lockTime - time()) / 60));
            if ($remainingMinutes > 0) {
                $securityMessages[] = [
                    'type' => 'danger',
                    'message' => "🔒 Account locked for {$remainingMinutes} minutes",
                    'icon' => '🔒'
                ];
            }
        } elseif ($failedAttempts > 0) {
            $remainingAttempts = MAX_FAILED_ATTEMPTS - $failedAttempts;
            if ($remainingAttempts > 0) {
                $securityMessages[] = [
                    'type' => 'warning',
                    'message' => "{$remainingAttempts} login attempts remaining",
                    'icon' => '⚠'
                ];
            } else {
                $securityMessages[] = [
                    'type' => 'danger',
                    'message' => "🚫 Account locked due to too many failed attempts",
                    'icon' => '🚫'
                ];
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dorm Reservation</title>
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'">
  <style>
    html, body { height:100%; }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    *, *::before, *::after { box-sizing: border-box; }
    .auth-grid { display:grid; grid-template-columns: 1.2fr 1fr; min-height:100vh; }
    .auth-hero { position:relative; background-image:url('/asset/welcome.jpg'); background-size:cover; background-position:center; }
    .auth-hero::after { content:""; position:absolute; inset:0; background:rgba(15,23,42,0.5); }
    .auth-hero .content { position:relative; z-index:1; color:#fff; padding:48px; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; gap:8px; height:100%; }
    .auth-hero h1 { margin:0; font-size:42px; font-weight:800; }
    .auth-hero p { margin:0; font-size:18px; opacity:0.95; }
    .auth-panels { background:#5b7fa2; display:flex; align-items:center; justify-content:center; padding:24px; }
    .auth-panels, .auth-hero { min-height: 100vh; }
    .auth-cards { display:grid; grid-template-columns: 1fr; gap:16px; width:100%; max-width: 420px; }
    .auth-panel-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; overflow:hidden; width:100%; }
    .auth-panel-card h3 { margin:0 0 8px 0; font-size:18px; }
    
    /* Security Messages Styling */
    .security-messages { margin-bottom: 16px; }
    .security-message { 
      padding: 12px; 
      border-radius: 8px; 
      margin-bottom: 8px; 
      font-size: 14px; 
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .security-message.warning { 
      background-color: #fef3c7; 
      border: 1px solid #f59e0b; 
      color: #92400e; 
    }
    .security-message.danger { 
      background-color: #fee2e2; 
      border: 1px solid #ef4444; 
      color: #991b1b; 
    }
    .security-message .icon { font-size: 16px; }
    
    /* Error Message Styling */
    .error-message { 
      background-color: #fee2e2; 
      border: 1px solid #ef4444; 
      color: #991b1b; 
      padding: 12px; 
      border-radius: 8px; 
      margin-bottom: 16px; 
      font-size: 14px; 
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .form-field { margin:10px 0; }
    .form-field input[type="email"], .form-field input[type="password"], .form-field input[type="text"] { width:100%; max-width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; }
    .password-field { position:relative; }
    .password-toggle { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; font-size:16px; }
    .form-actions { margin-top:12px; display:flex; gap:8px; align-items:center; }
    .form-actions .btn-block { width:100%; margin-top:8px; }
    .form-row { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .btn { display:inline-block; background:#2563eb; color:#fff; padding:10px 14px; border-radius:8px; text-decoration:none; border:none; cursor:pointer; font-size:14px; transition:.2s ease all; }
    .btn:hover { filter:brightness(1.05); transform: translateY(-1px); }
    .btn.secondary { background:#64748b; color:#fff; }
    .btn.accent { background:#b29279; color:#fff; }
    
    .forgot-password {
      text-align: left;
      margin-top: 8px;
    }
    
    .forgot-password a {
      color: #991b1b;
      text-decoration: underline;
      font-size: 14px;
    }
    
    .forgot-password a:hover {
      color: #7f1d1d;
    }
    
    .create-account {
      text-align: center;
      margin-top: 16px;
    }
    
    .create-account a {
      color: #374151;
      text-decoration: underline;
      font-size: 14px;
    }
    
    .create-account a:hover {
      color: #1f2937;
    }
    
    @media (max-width: 900px) { .auth-grid { grid-template-columns: 1fr; } .auth-hero .content { align-items:center; text-align:center; } }
  </style>
</head>
<body>
<?php if (empty($_SESSION['user'])): ?>
  <section class="auth-grid">
    <div class="auth-hero">
      <div class="content">
        <h1>Welcome to Dorm Reservation</h1>
        <p>Secure and hassle-free dormitory reservations for students.</p>
      </div>
    </div>
    <div class="auth-panels">
      <div class="auth-cards">
         <div class="auth-panel-card" id="login-panel">
          <h3>Sign in</h3>
          
          <?php if ($errorMessage): ?>
          <div class="error-message">
            <span>❌</span>
            <span><?php echo htmlspecialchars($errorMessage); ?></span>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($securityMessages)): ?>
          <div class="security-messages">
            <?php foreach ($securityMessages as $message): ?>
            <div class="security-message <?php echo htmlspecialchars($message['type']); ?>">
              <span class="icon"><?php echo htmlspecialchars($message['icon']); ?></span>
              <span><?php echo htmlspecialchars($message['message']); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          
          <form method="post" action="/login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-field">
              <label>Email</label><br>
              <input type="email" name="email" required maxlength="254" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="form-field password-field">
              <label>Password</label><br>
              <input id="login-password" type="password" name="password" required minlength="12" maxlength="128" autocomplete="current-password">
              <button class="password-toggle" type="button" aria-label="Toggle password" onclick="(function(btn){var i=document.getElementById('login-password'); if(i.type==='password'){ i.type='text'; btn.textContent='🙈'; } else { i.type='password'; btn.textContent='👁'; }})(this)">👁</button>
            </div>
            <div class="form-row">
              <label><input type="checkbox" name="remember" value="1"> Remember me</label>
            </div>
            <div class="form-actions">
              <button class="btn accent" type="submit" style="width:100%">Sign in</button>
            </div>
            <div class="forgot-password">
              <a href="/reset_password.php">Forgot your password?</a>
            </div>
            <div class="create-account">
              <a href="/register.php">Create account</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
  <script></script>
<?php else: ?>
  <div style="display:flex; height:100vh; align-items:center; justify-content:center;">
    <div style="max-width:720px; padding:16px; text-align:center;">
      <h1>Welcome back</h1>
      <p>You are logged in as <?php echo htmlspecialchars($_SESSION['user']['email']); ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)</p>
    </div>
  </div>
<?php endif; ?>
</body>
</html>