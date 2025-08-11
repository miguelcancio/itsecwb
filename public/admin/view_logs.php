<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/logger.php';

$lines = [];
$path = app_log_path();
if (is_file($path)) {
    $content = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($content)) {
        // Show last 500 lines max
        $lines = array_slice($content, -500);
    }
}
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Application Logs</h2>
    <p>Read-only view for administrators.</p>
    <pre style="white-space: pre-wrap; word-wrap: break-word; max-height: 480px; overflow:auto; background:#0b1020; color:#d1d5db; padding:12px; border-radius:6px;">
<?php foreach ($lines as $line) { echo htmlspecialchars($line) . "\n"; } ?>
    </pre>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


