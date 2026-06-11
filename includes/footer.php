</main> <!-- Close main-content -->

<!-- Footer – Purple Palette (Angular style) -->
<footer class="main-footer pt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="footer-title mb-4">SaraJane</h5>
                <p class="footer-text">Luxury hair care & accessories for everyday confidence. Nourish, protect & style your hair effortlessly.</p>
                <div class="social-icons mt-4">
                    <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="social-icon" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4">
                <h6 class="footer-subtitle mb-4">Categories</h6>
                <ul class="footer-links list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>category.php?cat=hair-care">Hair Care</a></li>
                    <li><a href="<?php echo SITE_URL; ?>category.php?cat=hair-accessories">Hair Accessories</a></li>
                    <li><a href="<?php echo SITE_URL; ?>category.php?cat=satin-range">Satin Range</a></li>
                    <li><a href="<?php echo SITE_URL; ?>shop.php">All Products</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 mb-4">
                <h6 class="footer-subtitle mb-4">Quick Links</h6>
                <ul class="footer-links list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
                    <li><a href="<?php echo SITE_URL; ?>faq.php">FAQ</a></li>
                    <li><a href="<?php echo SITE_URL; ?>shipping.php">Shipping & Returns</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="footer-subtitle mb-4">Join Our Newsletter</h6>
                <p class="footer-text small">Subscribe for exclusive offers, hair care tips & new product launches.</p>
                <form class="newsletter-form" id="newsletterForm">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email address" required>
                        <button class="btn" type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <!-- CSRF token will be injected via JavaScript -->
                </form>
                <div class="payment-methods mt-4">
                    <h6 class="footer-subtitle mb-3">We Accept</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <i class="fab fa-cc-visa payment-icon"></i>
                        <i class="fab fa-cc-mastercard payment-icon"></i>
                        <i class="fab fa-cc-amex payment-icon"></i>
                        <i class="fab fa-cc-paypal payment-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 small copyright-text">&copy; <?php echo date('Y'); ?> SaraJane. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?php echo SITE_URL; ?>privacy.php" class="small me-3">Privacy Policy</a>
                <a href="<?php echo SITE_URL; ?>terms.php" class="small">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo SITE_URL; ?>assets/js/script.js"></script>

<?php
// Generate CSRF token for newsletter subscription
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/csrf.php';
$newsletter_token = generateCSRFToken('newsletter');
?>

<script>
// Update cart count dynamically
function updateCartCount() {
    fetch('<?php echo SITE_URL; ?>ajax/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = data.count;
        })
        .catch(err => console.warn('Cart update failed:', err));
}

// Add to cart with animation (for .add-to-cart buttons)
document.querySelectorAll('.add-to-cart')?.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.dataset.productId;
        const quantity = this.dataset.quantity || 1;
        
        fetch('<?php echo SITE_URL; ?>ajax/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.cart-badge');
                if (badge) {
                    badge.classList.add('cart-badge-update');
                    setTimeout(() => badge.classList.remove('cart-badge-update'), 300);
                }
                updateCartCount();
                showNotification('Product added to cart!', 'success');
            } else {
                showNotification(data.message || 'Error adding to cart', 'error');
            }
        })
        .catch(() => showNotification('Network error. Please try again.', 'error'));
    });
});

// Global notification function
function showNotification(message, type) {
    const existing = document.querySelector('.custom-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.textContent = message;
    notification.style.backgroundColor = type === 'success' ? '#5a3e5e' : '#dc3545';
    notification.style.color = 'white';
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
}

// Newsletter subscription (AJAX) with CSRF
const newsletterForm = document.getElementById('newsletterForm');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const emailInput = newsletterForm.querySelector('input[type="email"]');
        const email = emailInput.value;
        const csrfToken = '<?php echo $newsletter_token; ?>';
        const btn = newsletterForm.querySelector('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const res = await fetch('<?php echo SITE_URL; ?>ajax/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, csrf_token: csrfToken })
            });
            const data = await res.json();
            showNotification(data.message || (data.success ? 'Subscribed!' : 'Error'), data.success ? 'success' : 'error');
            if (data.success) emailInput.value = '';
        } catch (err) {
            showNotification('Something went wrong. Please try again.', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
}
</script>

</body>
</html>