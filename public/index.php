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
    /* Enhanced Grid Layout */
    .auth-grid { 
      display:grid; 
      grid-template-columns: 1.2fr 1fr; 
      height:100vh; 
      overflow: hidden;
    }
    
    /* Enhanced Hero Section */
    .auth-hero { 
      position:relative; 
      background-image:url('/asset/welcome.jpg'); 
      background-size:cover; 
      background-position:center;
      background-repeat: no-repeat;
      overflow: hidden;
    }
    
    .auth-hero::after { 
      content:""; 
      position:absolute; 
      inset:0; 
      background: linear-gradient(135deg, rgba(15,23,42,0.7) 0%, rgba(91,127,162,0.6) 100%);
      backdrop-filter: blur(1px);
    }
    
    .auth-hero .content { 
      position:relative; 
      z-index:1; 
      color:#fff; 
      padding:48px; 
      display:flex; 
      flex-direction:column; 
      justify-content:center; 
      align-items:flex-start; 
      gap:16px; 
      height:100%;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .auth-hero h1 { 
      margin:0; 
      font-size:48px; 
      font-weight:800; 
      line-height: 1.1;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .auth-hero p { 
      margin:0; 
      font-size:20px; 
      font-weight:400;
      line-height: 1.5;
      opacity:0.95;
      max-width: 500px;
    }
    
    /* Enhanced Form Panels */
    .auth-panels { 
      background: linear-gradient(135deg, #5b7fa2 0%, #4a6b8a 100%);
      display:flex; 
      align-items:center; 
      justify-content:center; 
      padding:32px 24px;
      position: relative;
    }
    
    .auth-panels::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
      pointer-events: none;
    }
    .auth-cards { display:grid; grid-template-columns: 1fr; gap:16px; width:100%; max-width: 460px; }
    
    /* Enhanced Card Styling */
    .auth-panel-card { 
      background:#fff; 
      border:1px solid #e5e7eb; 
      border-radius:12px; 
      padding:24px; 
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    /* Enhanced Typography */
    .auth-panel-card h3 { 
      margin:0 0 24px 0; 
      font-size:24px; 
      font-weight:700; 
      color:#1f2937;
      text-align:center;
    }
    
    /* Enhanced Form Field Styling */
    .form-field { 
      margin:0 0 16px 0; 
    }
    
    .form-field label {
      display:block;
      margin-bottom:8px;
      font-weight:600;
      color:#374151;
      font-size:14px;
    }
    
    .form-field input[type="email"], 
    .form-field input[type="password"], 
    .form-field input[type="text"] { 
      width:100%; 
      padding:12px 16px; 
      border:2px solid #e5e7eb; 
      border-radius:8px; 
      font-size:16px; 
      transition: all 0.2s ease;
      box-sizing: border-box;
    }
    
    .form-field input[type="email"]:focus, 
    .form-field input[type="password"]:focus, 
    .form-field input[type="text"]:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    /* Enhanced Password Field */
    .password-field { 
      position:relative; 
    }
    
    .password-toggle { 
      position:absolute; 
      right:12px; 
      top:50%; 
      transform:translateY(-50%); 
      background:transparent; 
      border:none; 
      cursor:pointer; 
      font-size:18px;
      color: #6b7280;
      transition: color 0.2s ease;
    }
    
    .password-toggle:hover {
      color: #374151;
    }
    
    /* Enhanced Checkbox Styling */
    .form-row { 
      display:flex; 
      align-items:center; 
      gap:8px; 
      margin:16px 0;
    }
    
    .form-row input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #2563eb;
      cursor: pointer;
    }
    
    .form-row label {
      font-size: 14px;
      color: #6b7280;
      cursor: pointer;
      margin: 0;
    }
    
    /* Enhanced Button Styling */
    .form-actions { 
      margin:24px 0 16px 0; 
      display:flex; 
      flex-direction:column;
      gap:12px;
    }
    
    .form-actions .btn-block { 
      width:100%; 
    }
    
    .btn { 
      display:inline-block; 
      background:#2563eb; 
      color:#fff; 
      padding:14px 20px; 
      border-radius:8px; 
      text-decoration:none; 
      border:none; 
      cursor:pointer; 
      font-size:16px; 
      font-weight:600;
      transition: all 0.2s ease;
      width: 100%;
      box-sizing: border-box;
    }
    
    .btn:hover { 
      filter:brightness(1.05); 
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    
    .btn:active {
      transform: translateY(0);
    }
    
    .btn.secondary { 
      background:#64748b; 
      color:#fff; 
    }
    
    .btn.secondary:hover {
      box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
    }
    
    .btn.accent { 
      background:#b29279; 
      color:#fff; 
    }
    
    .btn.accent:hover {
      box-shadow: 0 4px 12px rgba(178, 146, 121, 0.3);
    }
    
    /* Enhanced Responsive Design */
    @media (max-width: 900px) { 
      .auth-grid { 
        grid-template-columns: 1fr; 
        height: auto;
        min-height: 100vh;
      } 
      
      .auth-hero { 
        min-height: 50vh;
        background-position: center 20%;
      }
      
      .auth-hero .content { 
        align-items:center; 
        text-align:center; 
        padding: 32px 24px;
        gap: 12px;
      }
      
      .auth-hero h1 {
        font-size: 36px;
      }
      
      .auth-hero p {
        font-size: 18px;
        max-width: 100%;
      }
      
      .auth-panels {
        padding: 24px 16px;
        min-height: 50vh;
      }
    }
    
    @media (max-width: 480px) {
      .auth-hero h1 {
        font-size: 28px;
      }
      
      .auth-hero p {
        font-size: 16px;
      }
      
      .auth-hero .content {
        padding: 24px 16px;
      }
      
      .auth-panels {
        padding: 16px 12px;
      }
    }
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
              <label for="login-email">Email</label>
              <input type="email" id="login-email" name="email" required maxlength="254" placeholder="Enter your email address">
            </div>
            <div class="form-field password-field">
              <label for="login-password">Password</label>
              <input id="login-password" type="password" name="password" required minlength="12" maxlength="128" autocomplete="current-password" placeholder="Enter your password">
              <button class="password-toggle" type="button" aria-label="Toggle password" onclick="(function(btn){var i=document.getElementById('login-password'); if(i.type==='password'){ i.type='text'; btn.textContent='🙈'; } else { i.type='password'; btn.textContent='👁'; }})(this)">👁</button>
            </div>
            <div class="form-row">
              <input type="checkbox" id="remember-me" name="remember" value="1">
              <label for="remember-me">Remember me</label>
            </div>
            <div class="form-actions">
              <button class="btn accent" type="submit">Sign in</button>
              <button class="btn secondary btn-block" type="button" onclick="showRegister()">Create account</button>
            </div>
          </form>
        </div>
        <div class="auth-panel-card" id="register-panel" style="display:none">
          <h3>Create account</h3>
          <form method="post" action="/register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-field">
              <label for="register-email">Email</label>
              <input type="email" id="register-email" name="email" required maxlength="254" placeholder="Enter your email address">
            </div>
            <div class="form-field password-field">
              <label for="register-password">Password</label>
              <input id="register-password" type="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password" placeholder="Create a strong password">
              <button class="password-toggle" type="button" aria-label="Toggle password" onclick="(function(btn){var i=document.getElementById('register-password'); if(i.type==='password'){ i.type='text'; btn.textContent='🙈'; } else { i.type='password'; btn.textContent='👁'; }})(this)">👁</button>
            </div>
            <div class="form-actions">
              <button class="btn" type="submit">Create account</button>
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

