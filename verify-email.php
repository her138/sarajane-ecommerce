<?php
$pageTitle = 'Email Verification - SaraJane';

require_once 'config/database.php';
require_once 'config/session.php';

$token = trim((string) ($_GET['token'] ?? ''));
$error = '';
$success = '';

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'Invalid verification link.';
} else {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT id, email_verified, verification_token_expires_at
             FROM users
             WHERE verification_token = ?
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $pdo->rollBack();
            $error = 'This verification link is invalid, expired or has already been used.';
        } elseif ((int) $user['email_verified'] === 1) {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET verification_token = NULL, verification_token_expires_at = NULL
                 WHERE id = ?'
            );
            $stmt->execute([$user['id']]);
            $pdo->commit();
            $success = 'Your email address is already verified. You can log in now.';
        } elseif (empty($user['verification_token_expires_at']) || strtotime((string) $user['verification_token_expires_at']) < time()) {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET verification_token = NULL, verification_token_expires_at = NULL
                 WHERE id = ?'
            );
            $stmt->execute([$user['id']]);
            $pdo->commit();
            $error = 'This verification link has expired. Request a new verification link.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET email_verified = 1,
                     verification_token = NULL,
                     verification_token_expires_at = NULL
                 WHERE id = ? AND email_verified = 0'
            );
            $stmt->execute([$user['id']]);
            $pdo->commit();

            if ($stmt->rowCount() === 1) {
                $success = 'Email verified successfully. You can now log in.';
            } else {
                $error = 'This account could not be verified. Please request a new verification link.';
            }
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Email verification error: ' . $exception->getMessage());
        $error = 'Email verification could not be completed. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fefaf5; }
        .verification-container { max-width: 500px; margin: 80px auto; }
        .btn-primary { background: #5a3e5e; border: none; }
        .btn-primary:hover { background: #4a2e4e; }
    </style>
</head>
<body>
    <div class="container verification-container">
        <div class="card shadow-sm">
            <div class="card-body p-4 text-center">
                <h3 class="mb-3">Email Verification</h3>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="d-grid gap-2">
                        <a href="resend-verification.php" class="btn btn-primary">Request New Link</a>
                        <a href="register.php" class="btn btn-outline-secondary">Register</a>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <a href="login.php" class="btn btn-primary">Login Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>