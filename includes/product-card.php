<?php
/**
 * Product Card – feelsilki style, WITH star ratings
 */
?>
<div class="product-card">
    <div class="product-card__figure">
        <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] <= 0): ?>
            <div class="badge-list">
                <span class="badge badge--sold-out">Sold Out</span>
            </div>
        <?php elseif (isset($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
            <div class="badge-list">
                <span class="badge badge--on-sale">Sale</span>
            </div>
        <?php endif; ?>

        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="product-card__media">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?php echo SITE_URL . htmlspecialchars($product['image_url']); ?>"
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="product-card__image"
                     loading="lazy"
                     onerror="this.src='https://placehold.co/600x600?text=No+Image'">
            <?php else: ?>
                <div class="product-image-placeholder">
                    <i class="fas fa-image"></i>
                </div>
            <?php endif; ?>
        </a>

        <!-- Plus button (Font Awesome) – appears on hover -->
        <button class="product-card__quick-add-button add-to-cart-btn"
                data-product-id="<?php echo $product['id']; ?>"
                aria-label="Add to cart">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <div class="product-card__info">
        <div class="product-title-section">
            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="product-title">
                <?php echo htmlspecialchars($product['name']); ?>
            </a>
            <div class="price-list">
                <span class="sale-price">
                    R <?php echo number_format($product['price'], 2); ?>
                </span>
                <?php if (isset($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                    <span class="compare-at-price">
                        R <?php echo number_format($product['compare_price'], 2); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Star ratings – restored -->
        <div class="rating-section">
            <span class="rating-badge">
                <span class="rating-badge__stars">
                    <?php
                    $rating = isset($product['rating']) ? floatval($product['rating']) : 4.8;
                    $fullStars = floor($rating);
                    $halfStar = ($rating - $fullStars) >= 0.5;
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
                </span>
                <span class="rating-text">(<?php echo number_format($rating, 1); ?>)</span>
            </span>
        </div>
    </div>
</div>