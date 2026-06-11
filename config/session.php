<?php
// config/session.php

// Security settings for sessions
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        // Leave domain unset so sessions work on localhost, subfolders, and hosts with ports.
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), // Auto-detect HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else {
        $interval = 60 * 30; // 30 minutes
        if (time() - $_SESSION['last_regeneration'] >= $interval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}
?>