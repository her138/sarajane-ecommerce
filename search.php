<?php
$pageTitle = "Search Results - SaraJane";
require_once 'includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];
$product_count = 0;

if (!empty($query)) {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE name LIKE ? OR description LIKE ? OR category LIKE ?
        ORDER BY created_at DESC
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $product_count = count($products);
}
?>

<div class="search-page py-5">
    <div class="container">
        <h1 class="h2 mb-4">Search Results</h1>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="search.php" method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($query); ?>" autofocus>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (empty($query)): ?>
            <div class="alert alert-info">Please enter a search term.</div>
        <?php elseif ($product_count === 0): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>No products found for "<?php echo htmlspecialchars($query); ?>"</h4>
                <p class="text-muted">Try different keywords or browse our categories.</p>
                <a href="shop.php" class="btn btn-primary">Browse All Products</a>
            </div>
        <?php else: ?>
            <p class="text-muted mb-4">Found <?php echo $product_count; ?> product(s) matching "<?php echo htmlspecialchars($query); ?>"</p>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-lg-3">
                        <?php include 'includes/product-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>