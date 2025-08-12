<?php declare(strict_types=1);
require_once __DIR__ . '/includes/validation.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>
  <?php if (empty($_SESSION['user'])): ?>
    <section class="auth-landing full-bleed">
      <div class="auth-grid">
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
                  <button class="toggle-link" type="button" onclick="showRegister()">Create account</button>
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
                  <button class="toggle-link" type="button" onclick="showLogin()">Back to login</button>
                </div>
              </form>
            </div>
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
    <div class="card">
      <h1>Welcome back</h1>
      <p>You are logged in as <?php echo htmlspecialchars($_SESSION['user']['email']); ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)</p>
    </div>
  <?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

