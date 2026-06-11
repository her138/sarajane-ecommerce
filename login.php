<?php
$pageTitle = "Login - SaraJane";

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/rate_limit.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken, 'login')) {
        unset($_SESSION['csrf_tokens']['login']);
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if ($username === '' || $password === '') {
            $error = 'Please enter your username/email and password.';
        } else {
            $rateCheck = checkRateLimit('login', $ip, 5, 15);

            if ($rateCheck !== true) {
                $error = $rateCheck;
            } else {
                $stmt = $pdo->prepare("
                    SELECT id, username, email, password, role, full_name, email_verified
                    FROM users
                    WHERE username = ? OR email = ?
                    LIMIT 1
                ");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    if (isset($user['email_verified']) && (int)$user['email_verified'] !== 1) {
                        $safeEmail = urlencode($user['email']);
                        $error = 'Please verify your email address before logging in. ';
                        $error .= '<a href="resend-verification.php?email=' . $safeEmail . '">Resend verification email</a>';
                    } else {
                        clearRateLimit('login', $ip);

                        session_regenerate_id(true);

                        $_SESSION['logged_in'] = true;
                        $_SESSION['is_logged_in'] = true;

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'] ?? 'user';
                        $_SESSION['full_name'] = $user['full_name'] ?? '';

                        $_SESSION['user'] = [
                            'id' => (int)$user['id'],
                            'username' => $user['username'],
                            'email' => $user['email'],
                            'role' => $user['role'] ?? 'user',
                            'full_name' => $user['full_name'] ?? ''
                        ];

                        $_SESSION['last_activity'] = time();

                        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                        unset($_SESSION['redirect_after_login']);

                        if (preg_match('/^https?:\/\//i', $redirect)) {
                            $redirect = 'index.php';
                        }

                        header('Location: ' . $redirect);
                        exit();
                    }
                } else {
                    recordRateLimitAttempt('login', $ip);
                    $error = 'Invalid username or password.';
                }
            }
        }
    }
}

$loginTokenField = csrf_field('login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fefaf5; }
        .login-container { max-width: 500px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .btn-primary { background: #5a3e5e; border: none; }
        .btn-primary:hover { background: #4a2e4e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Login to SaraJane</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?php echo $loginTokenField; ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>

                <div class="mb-3 text-end">
                    <a href="forgot-password.php" class="small">Forgot Password?</a>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <p class="mb-0">Don't have an account? <a href="register.php">Sign up here</a></p>
                <p class="mt-2"><a href="index.php">Return to store</a></p>
            </div>
        </div>
    </div>
</body>
</html>