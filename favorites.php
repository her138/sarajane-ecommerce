<?php
$pageTitle = "My Favorites - ShopNow";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Handle remove from favorites
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
}

// Fetch favorite products
$stmt = $pdo->prepare("
    SELECT p.*, f.id as favorite_id 
    FROM favorites f 
    JOIN products p ON f.product_id = p.id 
    WHERE f.user_id = ?
    ORDER BY f.added_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">My Favorites</h2>
        
        <?php if (empty($favorites)): ?>
            <div class="alert alert-info">
                You haven't added any products to favorites yet. 
                <a href="index.php">Browse products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($favorites as $product): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card product-card h-100">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-primary">$<?php echo number_format($product['price'], 2); ?></p>
                                
                                <div class="mt-auto">
                                    <div class="d-grid gap-2">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-outline-primary">View Details</a>
                                        
                                        <div class="btn-group" role="group">
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <button class="btn btn-success add-to-cart" 
                                                        data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="favorites.php?remove=<?php echo $product['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Remove from favorites?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>