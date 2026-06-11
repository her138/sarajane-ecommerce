<?php
$pageTitle = "Reset Password - SaraJane";
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'includes/csrf.php';
require_once 'includes/rate_limit.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'reset_password')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $rateCheck = checkRateLimit('reset_password', $ip, 5, 60);
        if ($rateCheck !== true) {
            $error = $rateCheck;
        } else {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt->execute([$hashed, $user['id']]);
                clearRateLimit('reset_password', $ip);
                $success = 'Your password has been reset successfully. You can now <a href="login.php">log in</a>.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fefaf5; }
        .reset-container { max-width: 500px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .btn-primary { background: #5a3e5e; border: none; }
        .btn-primary:hover { background: #4a2e4e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <h2 class="text-center mb-4">Set New Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (!$success && !$error): ?>
            <form method="POST" action="">
                <?php echo csrf_field('reset_password'); ?>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            <?php endif; ?>
            <?php if ($error && strpos($error, 'invalid') !== false): ?>
                <div class="text-center mt-3">
                    <a href="forgot-password.php">Request new reset link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>