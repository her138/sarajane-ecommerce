<?php
/**
 * CSRF Protection Helper
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate and store a CSRF token
 */
function generateCSRFToken($action = 'default') {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$action] = [
        'token' => $token,
        'expires' => time() + 3600 // 1 hour
    ];
    return $token;
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token, $action = 'default') {
    if (!isset($_SESSION['csrf_tokens'][$action])) {
        return false;
    }
    $stored = $_SESSION['csrf_tokens'][$action];
    if ($stored['expires'] < time()) {
        unset($_SESSION['csrf_tokens'][$action]);
        return false;
    }
    if (hash_equals($stored['token'], $token)) {
        // Token used – delete it (one-time use)
        unset($_SESSION['csrf_tokens'][$action]);
        return true;
    }
    return false;
}

/**
 * Output hidden CSRF field
 */
function csrf_field($action = 'default') {
    $token = generateCSRFToken($action);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>