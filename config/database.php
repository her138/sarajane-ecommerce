<?php
// config/database.php

// Load .env file if it exists (for local development)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv($line);
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'ecommerce_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$app_env = getenv('APP_ENV') ?: 'development';

// SSL options
$ca_path = getenv('DB_CA_PATH') ?: __DIR__ . '/../ca.pem';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 30,               // 30 seconds timeout for connection attempt
];

// Add SSL if CA certificate exists
if (file_exists($ca_path)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $ca_path;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

// Global PDO object
$pdo = null;
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";

function connectDB() {
    global $pdo, $dsn, $username, $password, $options, $app_env;
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        if ($app_env === 'production') {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection temporarily unavailable. Please try again later.");
        } else {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

// Initial connection
connectDB();

// Function to check if connection is alive and reconnect if needed
function ensureDBConnection() {
    global $pdo;
    try {
        // Simple ping: execute a lightweight query
        $pdo->query("SELECT 1");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'gone away') !== false || strpos($e->getMessage(), 'server has gone away') !== false) {
            // Connection lost, reconnect
            connectDB();
        } else {
            throw $e;
        }
    }
}

// Site constants (can also come from environment)
if (!defined('SITE_URL')) define('SITE_URL', getenv('SITE_URL') ?: '/');
if (!defined('SITE_NAME')) define('SITE_NAME', getenv('SITE_NAME') ?: 'SaraJane');
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'webklinic2024@gmail.com');
?>