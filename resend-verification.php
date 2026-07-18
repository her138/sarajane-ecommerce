<?php
$pageTitle = 'Resend Verification - SaraJane';

require_once 'config/database.php';
require_once 'config/email.php';
require_once 'config/session.php';
require_once 'includes/csrf.php';
require_once 'includes/rate_limit.php';

$message = '';
$messageType = 'danger';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'resend_verification')) {
        $message = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateKey = hash('sha256', $ipAddress . '|' . $email);
        $rateCheck = checkRateLimit('resend_verification', $rateKey, 3, 900);

        if ($rateCheck !== true) {
            $message = (string) $rateCheck;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $message = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'SELECT id, username, email_verified
                     FROM users
                     WHERE email = ?
                     LIMIT 1'
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Use a generic response to avoid revealing whether an email is registered.
                $genericSuccess = 'If an unverified account exists for that address, a new verification message has been sent to the configured test inbox.';

                if (!$user || (int) $user['email_verified'] === 1) {
                    $message = $genericSuccess;
                    $messageType = 'success';
                } else {
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET verification_token = ?, verification_token_expires_at = ?
                         WHERE id = ? AND email_verified = 0'
                    );
                    $stmt->execute([$token, $expiresAt, $user['id']]);

                    $verifyLink = rtrim(SITE_URL, '/') .
                        '/verify-email.php?token=' . urlencode($token);

                    $safeUsername = htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8');
                    $safeVerifyLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

                    $subject = 'Verify your email - SaraJane';
                    $body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#fefaf5;font-family:Arial,sans-serif;color:#333;">
    <div style="max-width:600px;margin:0 auto;padding:24px;">
        <div style="background:#5a3e5e;color:#fff;padding:24px;text-align:center;"><h2 style="margin:0;">Email Verification</h2></div>
        <div style="background:#fff;padding:24px;line-height:1.6;">
            <p>Hello {$safeUsername},</p>
            <p>Use the button below to verify your SaraJane account.</p>
            <p style="text-align:center;margin:28px 0;"><a href="{$safeVerifyLink}" style="display:inline-block;padding:12px 24px;background:#5a3e5e;color:#fff;text-decoration:none;border-radius:30px;">Verify Email</a></p>
            <p>This link expires in one hour and replaces any previous verification link.</p>
            <p>If you did not request this message, you can ignore it.</p>
        </div>
    </div>
</body>
</html>
HTML;

                    $plainText = "Verify your SaraJane account using this link:\n{$verifyLink}\n\nThis link expires in one hour.";
                    $mailResult = sendEmail($email, $subject, $body, $plainText);

                    if ($mailResult === true) {
                        clearRateLimit('resend_verification', $rateKey);
                        $message = $genericSuccess;
                        $messageType = 'success';
                    } else {
                        error_log('Resend verification failed for ' . $email . ': ' . (string) $mailResult);
                        $message = 'The verification message could not be sent. Please check the email configuration and try again later.';
                    }
                }
            } catch (Throwable $exception) {
                error_log('Resend verification error: ' . $exception->getMessage());
                $message = 'The verification request could not be completed. Please try again later.';
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
            <div class="card-body p-4">
                <h3 class="text-center mb-3">Resend Verification</h3>
                <p class="text-muted text-center">Enter the email address used for registration.</p>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" autocomplete="on">
                    <?php echo csrf_field('resend_verification'); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" maxlength="254" autocomplete="email" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Send New Verification Link</button>
                        <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>