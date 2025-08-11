<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_role('admin');

$message = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $email = validate_email($_POST['email'] ?? '');
        $password = validate_password($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin','manager','customer'], true) ? $_POST['role'] : null;
        if ($email && $password && $role) {
            $u = create_user($email, $password, $role);
            $u ? $message = 'User created' : $error = 'Create failed';
        } else {
            $error = 'Invalid input';
        }
    } elseif ($action === 'role') {
        $userId = $_POST['user_id'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin','manager','customer'], true) ? $_POST['role'] : null;
        if ($userId && $role) {
            update_user_role($userId, $role) ? $message = 'Role updated' : $error = 'Update failed';
        } else { $error = 'Invalid input'; }
    } elseif ($action === 'disable') {
        $userId = $_POST['user_id'] ?? '';
        $disabled = ($_POST['disabled'] ?? '') === '1';
        if ($userId) {
            set_user_disabled($userId, $disabled) ? $message = 'User updated' : $error = 'Update failed';
        } else { $error = 'Invalid input'; }
    }
}

$users = list_users();
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Manage Users</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

    <h3>Create user</h3>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="action" value="create">
      <input type="email" name="email" placeholder="Email" required maxlength="254">
      <input type="password" name="password" placeholder="Temp password" required minlength="12" maxlength="128">
      <select name="role" required>
        <option value="manager">Manager</option>
        <option value="customer">Customer</option>
        <option value="admin">Administrator</option>
      </select>
      <button class="btn" type="submit">Create</button>
    </form>
  </div>

  <div class="card">
    <h3>Users</h3>
    <table>
      <thead><tr><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['role']); ?></td>
            <td><?php echo !empty($u['is_disabled']) ? 'Disabled' : 'Active'; ?></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="role">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">
                <select name="role">
                  <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                  <option value="manager" <?php echo $u['role']==='manager'?'selected':''; ?>>Manager</option>
                  <option value="customer" <?php echo $u['role']==='customer'?'selected':''; ?>>Customer</option>
                </select>
                <button class="btn secondary" type="submit">Change</button>
              </form>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="disable">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">
                <input type="hidden" name="disabled" value="<?php echo !empty($u['is_disabled']) ? '0' : '1'; ?>">
                <button class="btn" type="submit"><?php echo !empty($u['is_disabled']) ? 'Enable' : 'Disable'; ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


