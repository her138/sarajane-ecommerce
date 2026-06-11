<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'includes/csrf.php';
require_once 'includes/rate_limit.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'contact')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $rateCheck = checkRateLimit('contact', $ip, 5, 60);
        if ($rateCheck !== true) {
            $error = $rateCheck;
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $error = 'Please fill in all fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $subject, $message]);

                $adminBody = "<h2>New Contact Message from SaraJane</h2>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
$adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'webklinic2024@gmail.com';
                $mailResult = sendEmail($adminEmail, "Contact Form: {$subject}", $adminBody);

                $userBody = "<h2>Thank you for contacting SaraJane</h2>
                    <p>Dear {$name},</p>
                    <p>We have received your message and will get back to you within 24 hours.</p>
                    <p>Here is a copy of your message:</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                    <p>Best regards,<br>The SaraJane Team</p>";
                sendEmail($email, "We received your message – SaraJane", $userBody);

                if ($mailResult === true) {
                    clearRateLimit('contact', $ip);
                    $success = 'Thank you for your message! We will get back to you soon.';
                } else {
                    error_log("Contact email failed: " . $mailResult);
                    $success = 'Thank you for your message! Our team will be in touch.';
                }
            }
        }
    }
}

// AJAX response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true, 'message' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => $error]);
    }
    exit;
}

// Redirect back
require_once __DIR__ . '/config/session.php';if ($success) {
    $_SESSION['contact_success'] = $success;
} else {
    $_SESSION['contact_error'] = $error;
}
header('Location: contact.php');
exit;
?>