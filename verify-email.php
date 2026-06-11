<?php
require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid verification link.';
} else {
    $stmt = $pdo->prepare("SELECT id, email_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = 'Invalid or expired verification token.';
    } elseif ($user['email_verified'] == 1) {
        $success = 'Email already verified. You can login now.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        $success = 'Email verified successfully! You can now login.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification - SaraJane</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #fefaf5; } .container { max-width: 500px; margin: 80px auto; }</style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <a href="register.php" class="btn btn-primary">Register Again</a>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <a href="login.php" class="btn btn-primary">Login Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>