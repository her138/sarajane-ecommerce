<?php
$pageTitle = "Category";
require_once 'includes/header.php';

$category = isset($_GET['cat']) ? $_GET['cat'] : 'hair-care';

// Category titles and descriptions
$categories = [
    'hair-care' => ['name' => 'Hair Care', 'desc' => 'Nourish your hair from root to tip'],
    'hair-accessories' => ['name' => 'Hair Accessories', 'desc' => 'Style without damage'],
    'satin-range' => ['name' => 'Satin Range', 'desc' => 'Protect while you sleep']
];

if (!isset($categories[$category])) {
    header('Location: shop.php');
    exit();
}

$cat_info = $categories[$category];

// Fetch products for this category (match by category name)
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
$stmt->execute([$cat_info['name']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$actual_count = count($products);
?>

<!-- Category Header -->
<section class="shop-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($cat_info['name']); ?></h1>
        <p class="product-count"><?php echo htmlspecialchars($cat_info['desc']); ?> • <?php echo $actual_count; ?> products</p>
    </div>
</section>

<!-- Category Content -->
<section class="category-content py-5">
    <div class="container">
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-spa fa-3x text-muted mb-3"></i>
                <h4>No products in this category yet</h4>
                <p class="text-muted">Check back soon for new arrivals!</p>
                <a href="shop.php" class="btn btn-primary mt-3">Shop All Products</a>
            </div>
        <?php else: ?>
            <!-- Use same product grid as shop page -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php include 'includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>