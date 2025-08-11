<?php
// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Composer autoload
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file from project root (one level above config/)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Debug print to verify environment variables are loaded
var_dump(getenv('SUPABASE_URL'));
var_dump(getenv('SUPABASE_KEY'));

// Fetch environment variables with fallback to null
$supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
$supabaseKey = $_ENV['SUPABASE_KEY'] ?? null;


// Check if keys exist
if (!$supabaseUrl || !$supabaseKey) {
    die("Supabase URL or Key is missing from environment variables.");
}

// Now instantiate Supabase client
use Supabase\Client\Supabase;

$supabase = new Supabase($supabaseUrl, $supabaseKey);
