<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use Supabase\Client\Supabase;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new Supabase($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$response = $supabase->auth->signInWithPassword([
    'email' => 'your_test_email@example.com',
    'password' => 'your_test_password',
]);

echo '<pre>';
var_dump($response);
echo '</pre>';
