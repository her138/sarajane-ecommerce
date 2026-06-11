<?php
// config/session.php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Secure session configuration
|--------------------------------------------------------------------------
| This file must be included before any HTML/output.
| Use this file everywhere instead of calling session_start() manually.
*/

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    }

    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }

    if (time() - $_SESSION['last_regeneration'] >= 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}