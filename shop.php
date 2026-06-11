<?php
$pageTitle = "Shop All Products";
require_once 'includes/header.php';

// Get filters from URL with sanitization
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_min = isset($_GET['price_min']) ? max(0, floatval($_GET['price_min'])) : 0;
$price_max = isset($_GET['price_max']) ? max(1, floatval($_GET['price_max'])) : 1000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'popular';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ensure price_min <= price_max
if ($price_min > $price_max) {
    $temp = $price_min;
    $price_min = $price_max;
    $price_max = $temp;
}

// Build query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " AND price BETWEEN ? AND ?";
$params[] = $price_min;
$params[] = $price_max;

// Sorting
switch($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'newest':
        $query .= " ORDER BY created_at DESC";
        break;
    default: // popular
        $query .= " ORDER BY (SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE product_id = products.id) DESC, created_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$product_count = count($products);

// Get categories for filter
$categoryStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Get min and max prices for range
$priceStmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
$priceRange = $priceStmt->fetch(PDO::FETCH_ASSOC);
$global_min_price = floor($priceRange['min_price'] ?? 0);
$global_max_price = ceil($priceRange['max_price'] ?? 1000);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Shop</li>
            <?php if(!empty($category)): ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category); ?></li>
            <?php endif; ?>
        </ol>
    </div>
</nav>

<!-- Shop Header -->
<section class="shop-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="shop-title">Shop All Products</h1>
                <p class="product-count"><?php echo $product_count; ?> product<?php echo $product_count !== 1 ? 's' : ''; ?> found</p>
            </div>
            <div class="col-md-6">
                <div class="shop-search">
                    <form method="GET" action="shop.php" class="search-form" id="searchForm">
                        <?php if(!empty($category)): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <input type="hidden" name="price_min" value="<?php echo $price_min; ?>">
                        <input type="hidden" name="price_max" value="<?php echo $price_max; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shop Content -->
<section class="shop-content py-5">
    <div class="container">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <div class="filter-header">
                        <h5 class="filter-title">Filters</h5>
                        <a href="shop.php" class="clear-filters">
                            <i class="fas fa-times me-1"></i> Clear All
                        </a>
                    </div>
                    
                    <form method="GET" action="shop.php" class="filter-form" id="filterForm">
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <h6 class="filter-group-title">Category</h6>
                            <div class="category-filters">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" 
                                           id="cat_all" value="" <?php echo empty($category) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat_all">
                                        All Products
                                        <span class="category-count"><?php echo $product_count; ?></span>
                                    </label>
                                </div>
                                <?php foreach($categories as $cat): 
                                    // Count products in category
                                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
                                    $countStmt->execute([$cat]);
                                    $catCount = $countStmt->fetchColumn();
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="category" 
                                               id="cat_<?php echo urlencode($cat); ?>" 
                                               value="<?php echo htmlspecialchars($cat); ?>"
                                               <?php echo $category == $cat ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cat_<?php echo urlencode($cat); ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                            <span class="category-count"><?php echo $catCount; ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="filter-group">
                            <h6 class="filter-group-title">Price Range (R)</h6>
                            <div class="price-range">
                                <div class="price-inputs row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">R</span>
                                            <input type="number" class="form-control" name="price_min" 
                                                   id="price_min" value="<?php echo $price_min; ?>" 
                                                   min="<?php echo $global_min_price; ?>" 
                                                   max="<?php echo $global_max_price; ?>" step="10">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">R</span>
                                            <input type="number" class="form-control" name="price_max" 
                                                   id="price_max" value="<?php echo $price_max; ?>" 
                                                   min="<?php echo $global_min_price; ?>" 
                                                   max="<?php echo $global_max_price; ?>" step="10">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="price-slider-container">
                                    <div class="price-slider"></div>
                                    <div class="price-slider-fill" id="price-slider-fill"></div>
                                    <input type="range" class="price-range-input" id="price-min-slider" 
                                           min="<?php echo $global_min_price; ?>" 
                                           max="<?php echo $global_max_price; ?>" 
                                           value="<?php echo $price_min; ?>" step="10">
                                    <input type="range" class="price-range-input" id="price-max-slider" 
                                           min="<?php echo $global_min_price; ?>" 
                                           max="<?php echo $global_max_price; ?>" 
                                           value="<?php echo $price_max; ?>" step="10">
                                </div>
                            </div>
                        </div>

                        <!-- Sort By -->
                        <div class="filter-group">
                            <h6 class="filter-group-title">Sort By</h6>
                            <select class="sort-select" name="sort" id="sortSelect">
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                            </select>
                        </div>

                        <!-- Hidden search field to preserve search term -->
                        <?php if(!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-100 mt-3" id="applyFilters">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="col-lg-9">
                <?php if(empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>No products found</h4>
                        <p class="text-muted">Try adjusting your search or filters to find what you're looking for.</p>
                        <a href="shop.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i> Reset All Filters
                        </a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <div class="row g-4" id="productGrid">
                            <?php foreach($products as $index => $product): ?>
                                <div class="col-xl-3 col-lg-4 col-md-6 product-item" data-index="<?php echo $index; ?>">
                                    <?php include 'includes/product-card.php'; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Load More Button -->
                        <?php if($product_count > 12): ?>
                            <div class="text-center mt-5">
                                <button class="btn-load-more" id="loadMoreBtn" data-page="1" data-total="<?php echo $product_count; ?>">
                                    <i class="fas fa-spinner me-2 d-none" id="loadMoreSpinner"></i>
                                    <span id="loadMoreText">Load More Products</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>