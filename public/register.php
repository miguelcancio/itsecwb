<?php
session_start();
require __DIR__ . '/../config/supabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $response = $supabase->auth->signUp([
        'email' => $email,
        'password' => $password
    ]);

    if (!empty($response['user'])) {
        $_SESSION['user'] = $response['user'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $response['error']['message'] ?? 'Registration failed';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h1>Register</h1>
    <?php if (!empty($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Register</button>
    </form>
    <p><a href="index.php">Back</a></p>
</body>
</html>
