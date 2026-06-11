<?php
$pageTitle = "My Account - SaraJane";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
require_once 'includes/csrf.php';  // CSRF protection

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate CSRF token for profile update
$csrf_token = generateCSRFToken('account');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'account')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email already in use.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
                $_SESSION['email'] = $email;
                $_SESSION['full_name'] = $full_name;
                $message = 'Profile updated successfully.';
                
                // Password change (optional)
                if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
                    $current = $_POST['current_password'];
                    $new = $_POST['new_password'];
                    $confirm = $_POST['confirm_password'];
                    if (password_verify($current, $user['password'])) {
                        if ($new === $confirm && strlen($new) >= 6) {
                            $hashed = password_hash($new, PASSWORD_DEFAULT);
                            $pwdStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $pwdStmt->execute([$hashed, $user_id]);
                            $message .= ' Password updated.';
                        } else {
                            $error = 'New password must be at least 6 characters and match confirmation.';
                        }
                    } else {
                        $error = 'Current password is incorrect.';
                    }
                }
            }
        }
    }
}
?>

<div class="account-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="h2 mb-4">My Account</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="list-group">
                            <a href="account.php" class="list-group-item list-group-item-action active">Profile</a>
                            <a href="orders.php" class="list-group-item list-group-item-action">Order History</a>
                            <a href="wishlist.php" class="list-group-item list-group-item-action">Wishlist</a>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                        <small class="text-muted">Username cannot be changed.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                    </div>
                                    <hr class="my-4">
                                    <h5 class="mb-3">Change Password</h5>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" name="confirm_password">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>