<?php declare(strict_types=1);
require_once __DIR__ . '/includes/validation.php'; ?>
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
    .auth-grid { display:grid; grid-template-columns: 1.2fr 1fr; height:100vh; }
    .auth-hero { position:relative; background-image:url('/asset/welcome.jpg'); background-size:cover; background-position:center; }
    .auth-hero::after { content:""; position:absolute; inset:0; background:rgba(15,23,42,0.5); }
    .auth-hero .content { position:relative; z-index:1; color:#fff; padding:48px; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; gap:8px; height:100%; }
    .auth-hero h1 { margin:0; font-size:42px; font-weight:800; }
    .auth-hero p { margin:0; font-size:18px; opacity:0.95; }
    .auth-panels { background:#5b7fa2; display:flex; align-items:center; justify-content:center; padding:24px; }
    .auth-cards { display:grid; grid-template-columns: 1fr; gap:16px; width:100%; max-width: 460px; }
    .auth-panel-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; }
    .auth-panel-card h3 { margin:0 0 8px 0; font-size:18px; }
    .form-field { margin:10px 0; }
    .form-field input[type="email"], .form-field input[type="password"], .form-field input[type="text"] { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; }
    .password-field { position:relative; }
    .password-toggle { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; font-size:16px; }
    .form-actions { margin-top:12px; display:flex; gap:8px; align-items:center; }
    .form-actions .btn-block { width:100%; margin-top:8px; }
    .form-row { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .btn { display:inline-block; background:#2563eb; color:#fff; padding:10px 14px; border-radius:8px; text-decoration:none; border:none; cursor:pointer; font-size:14px; transition:.2s ease all; }
    .btn:hover { filter:brightness(1.05); transform: translateY(-1px); }
    .btn.secondary { background:#64748b; color:#fff; }
    .btn.accent { background:#b29279; color:#fff; }
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
          <form method="post" action="/login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-field">
              <label>Email</label><br>
              <input type="email" name="email" required maxlength="254">
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
              <button class="btn accent" type="submit">Sign in</button>
            </div>
            <div>
              <button class="btn secondary btn-block" type="button" onclick="showRegister()">Create account</button>
            </div>
          </form>
        </div>
        <div class="auth-panel-card" id="register-panel" style="display:none">
          <h3>Create account</h3>
          <form method="post" action="/register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-field">
              <label>Email</label><br>
              <input type="email" name="email" required maxlength="254">
            </div>
            <div class="form-field password-field">
              <label>Password</label><br>
              <input id="register-password" type="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password">
              <button class="password-toggle" type="button" aria-label="Toggle password" onclick="(function(btn){var i=document.getElementById('register-password'); if(i.type==='password'){ i.type='text'; btn.textContent='🙈'; } else { i.type='password'; btn.textContent='👁'; }})(this)">👁</button>
            </div>
            <div class="form-actions">
              <button class="btn" type="submit">Create account</button>
            </div>
            <div>
              <button class="btn secondary btn-block" type="button" onclick="showLogin()">Back to login</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
  <script>
    function showRegister(){
      var l=document.getElementById('login-panel');
      var r=document.getElementById('register-panel');
      if(l&&r){ l.style.display='none'; r.style.display='block'; }
    }
    function showLogin(){
      var l=document.getElementById('login-panel');
      var r=document.getElementById('register-panel');
      if(l&&r){ r.style.display='none'; l.style.display='block'; }
    }
  </script>
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