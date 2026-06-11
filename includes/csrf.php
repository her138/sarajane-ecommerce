<?php
// includes/csrf.php

require_once __DIR__ . '/../config/session.php';

function generateCSRFToken($action = 'default') {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    if (
        isset($_SESSION['csrf_tokens'][$action]['token']) &&
        isset($_SESSION['csrf_tokens'][$action]['expires']) &&
        $_SESSION['csrf_tokens'][$action]['expires'] >= time()
    ) {
        return $_SESSION['csrf_tokens'][$action]['token'];
    }

    $token = bin2hex(random_bytes(32));

    $_SESSION['csrf_tokens'][$action] = [
        'token' => $token,
        'expires' => time() + 3600
    ];

    return $token;
}

function verifyCSRFToken($token, $action = 'default') {
    if (empty($token)) {
        return false;
    }

    if (!isset($_SESSION['csrf_tokens'][$action])) {
        return false;
    }

    $stored = $_SESSION['csrf_tokens'][$action];

    if (!isset($stored['token'], $stored['expires'])) {
        return false;
    }

    if ($stored['expires'] < time()) {
        unset($_SESSION['csrf_tokens'][$action]);
        return false;
    }

    return hash_equals($stored['token'], $token);
}

function csrf_field($action = 'default') {
    $token = generateCSRFToken($action);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
?>