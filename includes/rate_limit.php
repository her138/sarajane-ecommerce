<?php
/**
 * Rate Limiting Helper
 */
require_once __DIR__ . '/../config/database.php';

/**
 * Check rate limit
 *
 * @param string $action     e.g., 'login', 'password_reset', 'contact'
 * @param string $identifier e.g., IP address or email
 * @param int $maxAttempts
 * @param int $decayMinutes
 * @return bool|string True if allowed, error message if blocked
 */
function checkRateLimit($action, $identifier, $maxAttempts = 5, $decayMinutes = 15) {
    global $pdo;
    
    $cutoff = date('Y-m-d H:i:s', strtotime("-$decayMinutes minutes"));
    
    // Clean old entries
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE last_attempt < ?");
    $stmt->execute([$cutoff]);
    
    // Get current attempts
    $stmt = $pdo->prepare("SELECT attempts FROM rate_limits WHERE action = ? AND identifier = ?");
    $stmt->execute([$action, $identifier]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $attempts = $row ? $row['attempts'] : 0;
    
    if ($attempts >= $maxAttempts) {
        $wait = getWaitTime($action, $identifier);
        return "Too many attempts. Please try again after {$wait} minutes.";
    }
    
    return true;
}

/**
 * Record a failed attempt
 */
function recordRateLimitAttempt($action, $identifier) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (action, identifier, attempts, first_attempt, last_attempt)
        VALUES (?, ?, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
        attempts = attempts + 1,
        last_attempt = NOW()
    ");
    $stmt->execute([$action, $identifier]);
}

/**
 * Clear rate limit on success (e.g., after successful login)
 */
function clearRateLimit($action, $identifier) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE action = ? AND identifier = ?");
    $stmt->execute([$action, $identifier]);
}

/**
 * Get remaining wait time in minutes
 */
function getWaitTime($action, $identifier) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT last_attempt FROM rate_limits WHERE action = ? AND identifier = ?");
    $stmt->execute([$action, $identifier]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    $last = strtotime($row['last_attempt']);
    $expires = $last + (15 * 60); // 15 minutes
    $remaining = ceil(($expires - time()) / 60);
    return max(0, $remaining);
}
?>