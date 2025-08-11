<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Welcome, <strong><?= htmlspecialchars($_SESSION['user']['email']) ?></strong></p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
