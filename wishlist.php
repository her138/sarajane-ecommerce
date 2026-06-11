<?php
$pageTitle = "My Wishlist - SaraJane";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';
require_once 'includes/csrf.php'; // for token generation

$user_id = $_SESSION['user_id'];

// Fetch wishlist items
$stmt = $pdo->prepare("
    SELECT p.*, f.id as favorite_id 
    FROM favorites f 
    JOIN products p ON f.product_id = p.id 
    WHERE f.user_id = ? 
    ORDER BY f.added_at DESC
");
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate CSRF token for wishlist actions
$csrf_token = generateCSRFToken('wishlist');
?>
<div class="wishlist-page py-5">
    <div class="container">
        <h1 class="h2 mb-4">My Wishlist</h1>
        
        <?php if (empty($wishlist)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                <p class="mb-0">Your wishlist is empty.</p>
                <a href="shop.php" class="btn btn-primary mt-3">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row g-4" id="wishlistGrid">
                <?php foreach ($wishlist as $product): ?>
                    <div class="col-md-4 col-lg-3 wishlist-item" data-product-id="<?= $product['id'] ?>">
                        <div class="product-card">
                            <div class="product-card__figure">
                                <a href="product-detail.php?id=<?= $product['id'] ?>">
                                    <img src="<?= SITE_URL . htmlspecialchars($product['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         class="product-card__image"
                                         loading="lazy"
                                         onerror="this.src='https://placehold.co/300x375?text=No+Image'">
                                </a>
                                <button class="product-card__quick-add-button remove-wishlist" 
                                        data-product-id="<?= $product['id'] ?>"
                                        data-csrf-token="<?= $csrf_token ?>"
                                        title="Remove from wishlist">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="product-card__info">
                                <a href="product-detail.php?id=<?= $product['id'] ?>" class="product-title">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                                <div class="price-list">
                                    <span class="sale-price">R <?= number_format($product['price'], 2) ?></span>
                                </div>
                                <button class="btn btn-primary btn-sm w-100 add-to-cart-wishlist mt-2"
                                        data-product-id="<?= $product['id'] ?>"
                                        data-csrf-token="<?= $csrf_token ?>">
                                    <i class="fas fa-shopping-bag me-1"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Global CSRF token for wishlist actions
const wishlistCsrfToken = '<?= $csrf_token ?>';

// Remove from wishlist with CSRF
document.querySelectorAll('.remove-wishlist').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const productId = this.dataset.productId;
        const token = this.dataset.csrfToken;
        
        if (!confirm('Remove this item from your wishlist?')) return;
        
        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('csrf_token', token);
            
            const res = await fetch('ajax/remove_wishlist.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                // Remove the item from DOM
                const item = document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
                if (item) item.remove();
                showNotification('Removed from wishlist', 'success');
                // If no items left, reload to show empty message
                if (document.querySelectorAll('.wishlist-item').length === 0) {
                    location.reload();
                }
            } else {
                showNotification(data.message || 'Failed to remove', 'error');
            }
        } catch (err) {
            showNotification('Network error', 'error');
        }
    });
});

// Add to cart from wishlist with CSRF
document.querySelectorAll('.add-to-cart-wishlist').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const productId = this.dataset.productId;
        const token = this.dataset.csrfToken;
        const originalHTML = this.innerHTML;
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        this.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('csrf_token', token);
            
            const res = await fetch('ajax/add_to_cart.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                if (typeof updateCartCount === 'function') updateCartCount();
                showNotification('Product added to cart!', 'success');
                this.innerHTML = '<i class="fas fa-check"></i> Added';
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                }, 1500);
            } else {
                showNotification(data.message || 'Error adding to cart', 'error');
                this.innerHTML = originalHTML;
                this.disabled = false;
            }
        } catch (err) {
            showNotification('Network error', 'error');
            this.innerHTML = originalHTML;
            this.disabled = false;
        }
    });
});

// Notification helper (if not already defined)
function showNotification(message, type = 'success') {
    const existing = document.querySelector('.custom-notification');
    if (existing) existing.remove();
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.textContent = message;
    notification.style.backgroundColor = type === 'error' ? '#dc3545' : '#5a3e5e';
    notification.style.color = 'white';
    notification.style.position = 'fixed';
    notification.style.top = '80px';
    notification.style.right = '20px';
    notification.style.padding = '12px 24px';
    notification.style.borderRadius = '8px';
    notification.style.zIndex = '9999';
    notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>