<?php
require '../config/supabase.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $response = $supabase->auth->signInWithPassword([
        'email' => $email,
        'password' => $password
    ]);

    echo '<pre>';
    var_dump($response);
    echo '</pre>';

    // Check for error
    if (!empty($response->error)) {
        echo "Error: " . $response->error->message;
        exit;
    }

    // Check if login successful
    if (!empty($response->session) && !empty($response->user)) {
        $_SESSION['user'] = $response->user;
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Login failed!";
    }
}
?>

<form method="POST">
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Login</button>
</form>
