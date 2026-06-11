<?php
$pageTitle = "Contact SaraJane";
require_once 'includes/header.php';

// Display success/error messages from send-contact.php
if (isset($_SESSION['contact_success'])) {
    echo '<div class="container" style="margin-top:20px;"><div class="alert alert-success">' . $_SESSION['contact_success'] . '</div></div>';
    unset($_SESSION['contact_success']);
}
if (isset($_SESSION['contact_error'])) {
    echo '<div class="container" style="margin-top:20px;"><div class="alert alert-danger">' . $_SESSION['contact_error'] . '</div></div>';
    unset($_SESSION['contact_error']);
}
?>

<div class="contact-container">
    <div class="contact-header">
        <h1>Connect With Us</h1>
    </div>

    <div class="contact-content">
        <!-- Left Side - Dark Purple background -->
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <div class="info-item">
                <span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                <p>hello@sarajane.com</p>
            </div>
            <div class="info-item">
                <span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                <p>+27 12 345 6789</p>
            </div>
            <div class="info-item">
                <span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                <p>Cape Town, South Africa</p>
            </div>

            <h3>Follow Us</h3>
            <div class="social-links">
                <a href="#" class="social-link"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg><span>Instagram</span></a>
                <a href="#" class="social-link"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg><span>WhatsApp</span></a>
                <a href="#" class="social-link"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/><path d="M16 9a5 5 0 0 1-5-5"/></svg><span>TikTok</span></a>
                <a href="#" class="social-link"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg><span>Facebook</span></a>
            </div>

            <div class="hours">
                <h3>Customer Care Hours</h3>
                <p>Monday - Friday: 9am - 6pm SAST<br>Saturday: 10am - 4pm SAST<br>Sunday: Closed</p>
            </div>
        </div>

        <!-- Right Side - Contact Form -->
        <div class="contact-form">
            <h2>Send a Message</h2>
            <form action="send-contact.php" method="POST" id="contactForm">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>