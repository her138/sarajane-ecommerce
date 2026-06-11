<?php
$pageTitle = "Product Details - SaraJane";
require_once 'config/session.php';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo '<div class="container py-5"><div class="alert alert-danger">Product not found.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

// Build main image URL
$main_image_full = !empty($product['image_url']) && strpos($product['image_url'], 'placeholder') === false
    ? SITE_URL . $product['image_url']
    : 'https://placehold.co/600x600?text=No+Image';

// Gallery images (using main image for all)
$product_images = [
    ['id' => 1, 'url' => $main_image_full, 'alt' => htmlspecialchars($product['name']) . ' - Main View', 'active' => true],
    ['id' => 2, 'url' => $main_image_full, 'alt' => htmlspecialchars($product['name']) . ' - Detail View', 'active' => false],
    ['id' => 3, 'url' => $main_image_full, 'alt' => htmlspecialchars($product['name']) . ' - Packaging', 'active' => false],
    ['id' => 4, 'url' => $main_image_full, 'alt' => htmlspecialchars($product['name']) . ' - In Use', 'active' => false]
];

// Check if product is in favorites
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $checkStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$_SESSION['user_id'], $product_id]);
    $isFavorite = $checkStmt->fetch() !== false;
}

// ----- REVIEWS SYSTEM -----
// Fetch reviews for this product
$reviewStmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM product_reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([$product_id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE product_id = ? AND status = 'approved'");
$avgStmt->execute([$product_id]);
$ratingData = $avgStmt->fetch(PDO::FETCH_ASSOC);
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$reviewCount = $ratingData['review_count'] ?? 0;

// Check if user has already reviewed this product
$userReviewed = false;
if (isset($_SESSION['user_id'])) {
    $checkStmt = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $checkStmt->execute([$product_id, $_SESSION['user_id']]);
    $userReviewed = $checkStmt->fetch() !== false;
}

// Handle review submission
$review_error = '';
$review_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $review_error = 'Please select a valid rating.';
    } elseif (empty($comment)) {
        $review_error = 'Please enter your review comment.';
    } elseif ($userReviewed) {
        $review_error = 'You have already reviewed this product.';
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO product_reviews (product_id, user_id, rating, comment, status) 
            VALUES (?, ?, ?, ?, 'approved')
        ");
        $insertStmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment]);
        $review_success = 'Thank you for your review! It has been added.';
        // Refresh the page to show new review
        header("Location: product-detail.php?id=" . $product_id);
        exit;
    }
}
// ----- END REVIEWS SYSTEM -----

// Fetch related products
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$stmt->execute([$product['category'], $product_id]);
$relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="product-detail-page py-5">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>shop.php">Shop</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
        
        <div class="row g-5">
            <!-- Product Image Gallery -->
            <div class="col-lg-6">
                <div class="product-image-container">
                    <div class="main-image-wrapper">
                        <div class="main-image-container">
                            <?php foreach ($product_images as $image): ?>
                                <img src="<?php echo $image['url']; ?>" 
                                     class="main-product-image <?php echo $image['active'] ? 'active' : ''; ?>" 
                                     alt="<?php echo $image['alt']; ?>"
                                     data-image-id="<?php echo $image['id']; ?>"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='https://placehold.co/600x600?text=No+Image';">
                            <?php endforeach; ?>
                        </div>
                        <button class="image-nav-btn prev-btn" aria-label="Previous image"><i class="fas fa-chevron-left"></i></button>
                        <button class="image-nav-btn next-btn" aria-label="Next image"><i class="fas fa-chevron-right"></i></button>
                        <div class="image-zoom-container">
                            <button class="zoom-btn" id="zoomIn" aria-label="Zoom in"><i class="fas fa-search-plus"></i></button>
                            <button class="zoom-btn" id="zoomOut" aria-label="Zoom out"><i class="fas fa-search-minus"></i></button>
                        </div>
                    </div>
                    
                    <div class="thumbnail-gallery">
                        <button class="thumbnail-nav-btn thumb-prev"><i class="fas fa-chevron-left"></i></button>
                        <div class="thumbnail-scroll-container">
                            <?php foreach ($product_images as $image): ?>
                                <div class="thumbnail-item <?php echo $image['active'] ? 'active' : ''; ?>" data-image-id="<?php echo $image['id']; ?>" data-image-src="<?php echo $image['url']; ?>">
                                    <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" loading="lazy" onerror="this.src='https://placehold.co/85x85?text=No+Image';">
                                    <div class="thumbnail-overlay"><i class="fas fa-eye"></i></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="thumbnail-nav-btn thumb-next"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
            
            <!-- Product Details -->
            <div class="col-lg-6">
                <h1 class="product-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Rating Display (stars + average) -->
                <div class="product-rating mb-3">
                    <div class="stars" style="display: inline-block; color: #FFD700;">
                        <?php
                        $fullStars = floor($avgRating);
                        $halfStar = ($avgRating - $fullStars) >= 0.5;
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= $fullStars):
                                echo '<i class="fas fa-star"></i>';
                            elseif ($i == $fullStars + 1 && $halfStar):
                                echo '<i class="fas fa-star-half-alt"></i>';
                            else:
                                echo '<i class="far fa-star"></i>';
                            endif;
                        endfor;
                        ?>
                    </div>
                    <span class="rating-value ms-2"><?php echo $avgRating; ?> / 5 (<?php echo $reviewCount; ?> reviews)</span>
                </div>
                
                <div class="price-section mb-4">
                    <div class="price-amount">R <?php echo number_format($product['price'], 2); ?></div>
                    <?php if (isset($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                        <div class="compare-price text-muted mt-1">
                            <s>R <?php echo number_format($product['compare_price'], 2); ?></s>
                            <span class="badge bg-success ms-2">Save <?php echo round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-description mb-4">
                    <p class="description-text"><?php echo htmlspecialchars($product['description'] ?? 'Experience the luxury of premium hair care with our expertly formulated products.'); ?></p>
                </div>
                
                <div class="stock-status mb-4">
                    <?php if ($product['stock_quantity'] <= 0): ?>
                        <span class="badge bg-danger px-3 py-2 fs-6">SOLD OUT</span>
                    <?php elseif ($product['stock_quantity'] < 10): ?>
                        <span class="badge bg-warning px-3 py-2 fs-6">Only <?php echo $product['stock_quantity']; ?> left</span>
                    <?php else: ?>
                        <span class="badge bg-success px-3 py-2 fs-6">IN STOCK</span>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="action-buttons mb-5">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button class="btn btn-primary btn-lg btn-buy-now add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-bag me-2"></i> Add To Cart
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-primary btn-lg wishlist-btn <?php echo $isFavorite ? 'active' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-heart me-2"></i>
                            <span><?php echo $isFavorite ? 'REMOVE FROM WISHLIST' : 'ADD TO WISHLIST'; ?></span>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <a href="<?php echo SITE_URL; ?>login.php" class="alert-link">Sign in</a> to purchase and save to wishlist.
                    </div>
                <?php endif; ?>
                
                <div class="product-meta mb-4 pt-3 border-top">
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!empty($product['sku'])): ?>
                            <div><small class="text-muted">SKU:</small> <span><?php echo htmlspecialchars($product['sku']); ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($product['category'])): ?>
                            <div><small class="text-muted">Category:</small> <a href="<?php echo SITE_URL; ?>category.php?cat=<?php echo urlencode($product['category']); ?>"><?php echo htmlspecialchars($product['category']); ?></a></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="accordion product-accordion" id="productAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDetails">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetails" aria-expanded="true">PRODUCT DETAILS</button>
                        </h2>
                        <div id="collapseDetails" class="accordion-collapse collapse show" data-bs-parent="#productAccordion">
                            <div class="accordion-body"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Premium quality product designed for optimal results.')); ?></div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingHowTo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHowTo">HOW TO USE</button>
                        </h2>
                        <div id="collapseHowTo" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body">Apply to clean, damp hair. Distribute evenly. Style as desired. Use consistently for best results.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingShipping">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseShipping">SHIPPING & RETURNS</button>
                        </h2>
                        <div id="collapseShipping" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body">
                                <p><strong>Shipping:</strong> Free standard shipping over R550. Express available at checkout.</p>
                                <p><strong>Returns:</strong> 30-day return policy.</p>
                                <p><strong>Delivery:</strong> Standard 3-5 days | Express 1-2 days</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ==================== REVIEWS SECTION ==================== -->
                <div class="reviews-section mt-5 pt-3">
                    <h3 class="h5 mb-3">Customer Reviews</h3>
                    
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="reviewer-name">
                                        <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                        <span class="text-muted ms-2 small"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="review-stars" style="color: #FFD700;">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $review['rating']):
                                                echo '<i class="fas fa-star"></i>';
                                            else:
                                                echo '<i class="far fa-star"></i>';
                                            endif;
                                        endfor;
                                        ?>
                                    </div>
                                </div>
                                <p class="review-comment mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($userReviewed): ?>
                            <div class="alert alert-info mt-3">You have already reviewed this product. Thank you!</div>
                        <?php else: ?>
                            <div class="write-review mt-4">
                                <h4 class="h6 mb-3">Write a Review</h4>
                                <?php if ($review_error): ?>
                                    <div class="alert alert-danger"><?php echo $review_error; ?></div>
                                <?php endif; ?>
                                <?php if ($review_success): ?>
                                    <div class="alert alert-success"><?php echo $review_success; ?></div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Your Rating *</label>
                                        <div class="rating-input">
                                            <div class="stars" id="ratingStars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="far fa-star" data-rating="<?php echo $i; ?>" style="cursor: pointer; font-size: 1.5rem; margin-right: 5px; color: #FFD700;"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="rating" id="ratingValue" value="0" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="comment" class="form-label">Your Review *</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <a href="<?php echo SITE_URL; ?>login.php">Log in</a> to write a review.
                        </div>
                    <?php endif; ?>
                </div>
                <!-- ==================== END REVIEWS SECTION ==================== -->
            </div>
        </div>
        
        <?php if (!empty($relatedProducts)): ?>
            <div class="also-like-section mt-5 pt-5">
                <div class="section-header text-center mb-4">
                    <h2 class="section-title">You May Also Like</h2>
                    <p class="text-muted">Discover more products you might love</p>
                </div>
                <div class="row g-4">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <?php $product = $related; include 'includes/product-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> ADDING...';
            this.disabled = true;
            
            fetch('<?php echo SITE_URL; ?>ajax/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check me-2"></i> ADDED!';
                    this.classList.add('btn-success');
                    if (typeof updateCartCount === 'function') updateCartCount();
                    else {
                        fetch('<?php echo SITE_URL; ?>ajax/get_cart_count.php')
                            .then(res => res.json())
                            .then(cartData => {
                                const badge = document.querySelector('.cart-count .count');
                                if (badge) badge.textContent = cartData.count;
                            });
                    }
                    showNotification('Product added to cart!', 'success');
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('btn-success');
                        this.disabled = false;
                    }, 2000);
                } else {
                    this.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> ERROR';
                    showNotification(data.message || 'Error adding to cart', 'error');
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> ERROR';
                showNotification('Network error. Please try again.', 'error');
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            });
        });
    }
    
    // Wishlist toggle
    const wishlistBtn = document.querySelector('.wishlist-btn');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const isActive = this.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> ...';
            this.disabled = true;
            
            fetch('<?php echo SITE_URL; ?>ajax/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    const newText = this.classList.contains('active') ? 'REMOVE FROM WISHLIST' : 'ADD TO WISHLIST';
                    this.innerHTML = `<i class="fas fa-heart me-2"></i> ${newText}`;
                    showNotification(data.message, 'success');
                } else {
                    this.innerHTML = originalText;
                    showNotification(data.message || 'Error', 'error');
                }
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalText;
                showNotification('Network error. Please try again.', 'error');
                this.disabled = false;
            });
        });
    }
    
    // Notification helper
    function showNotification(message, type) {
        const existing = document.querySelector('.custom-notification');
        if (existing) existing.remove();
        const notif = document.createElement('div');
        notif.className = 'custom-notification';
        notif.textContent = message;
        notif.style.cssText = `position:fixed; top:80px; right:20px; background:${type === 'success' ? '#5a3e5e' : '#dc3545'}; color:white; padding:12px 24px; border-radius:8px; z-index:9999; box-shadow:0 2px 10px rgba(0,0,0,0.2);`;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 3000);
    }
    
    // Image gallery functionality
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const mainImages = document.querySelectorAll('.main-product-image');
    if (thumbnails.length) {
        thumbnails.forEach((thumb, idx) => {
            thumb.addEventListener('click', () => {
                mainImages.forEach(img => img.classList.remove('active'));
                mainImages[idx].classList.add('active');
                thumbnails.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    }
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    let currentIndex = 0;
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + mainImages.length) % mainImages.length;
            mainImages.forEach(img => img.classList.remove('active'));
            mainImages[currentIndex].classList.add('active');
            thumbnails.forEach(t => t.classList.remove('active'));
            thumbnails[currentIndex].classList.add('active');
        });
        nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % mainImages.length;
            mainImages.forEach(img => img.classList.remove('active'));
            mainImages[currentIndex].classList.add('active');
            thumbnails.forEach(t => t.classList.remove('active'));
            thumbnails[currentIndex].classList.add('active');
        });
    }
    
    // Star rating input interaction
    const stars = document.querySelectorAll('#ratingStars i');
    const ratingInput = document.getElementById('ratingValue');
    if (stars.length && ratingInput) {
        stars.forEach(star => {
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                resetStars();
                highlightStars(rating);
            });
            star.addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value);
                resetStars();
                highlightStars(currentRating);
            });
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                resetStars();
                highlightStars(rating);
            });
        });
        
        function resetStars() {
            stars.forEach(star => {
                star.classList.remove('fas');
                star.classList.add('far');
            });
        }
        
        function highlightStars(rating) {
            for (let i = 0; i < rating; i++) {
                stars[i].classList.remove('far');
                stars[i].classList.add('fas');
            }
        }
        
        // Set initial highlight if editing (not applicable for new review)
        if (ratingInput.value > 0) {
            highlightStars(parseInt(ratingInput.value));
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>