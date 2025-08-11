
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require __DIR__ . '/../config/supabase.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $response = $supabase->auth->signInWithPassword([
        'email' => $email,
        'password' => $password
    ]);

    if (!empty($response->user)) {
    $_SESSION['user'] = $response->user;
    header('Location: dashboard.php');
    exit;
} else {
    $error = $response->error->message ?? 'Login failed';
}

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <?php if (!empty($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
    <p><a href="index.php">Back</a></p>
</body>
</html>
