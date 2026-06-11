<?php
// config/app.php - Application configuration

// Detect environment from environment variable (default: development)
$app_env = getenv('APP_ENV') ?: 'development';

// Set error reporting based on environment
if ($app_env === 'production') {
    // Production: log errors, don't display
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    // Development: show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

// Custom error handler that shows safe messages in production
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $app_env = getenv('APP_ENV') ?: 'development';
    if ($app_env === 'production') {
        error_log("Error: $errstr in $errfile on line $errline");
        // Don't show details to user
        return true;
    } else {
        // Show details in development
        echo "<div style='color:red'><strong>Error:</strong> $errstr in $errfile on line $errline</div>";
        return false;
    }
});

// Custom exception handler
set_exception_handler(function($exception) {
    $app_env = getenv('APP_ENV') ?: 'development';
    if ($app_env === 'production') {
        error_log("Uncaught Exception: " . $exception->getMessage());
        // Don't show details to user, redirect to a friendly error page
        header('HTTP/1.1 500 Internal Server Error');
        include __DIR__ . '/../500.php';
        exit;
    } else {
        echo "<div style='color:red'><strong>Exception:</strong> " . $exception->getMessage() . 
             " in " . $exception->getFile() . " on line " . $exception->getLine() . "</div>";
    }
});
?>