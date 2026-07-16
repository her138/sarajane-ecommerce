<?php
$pageTitle = "Manage Products - SaraJane";
require_once '../includes/header.php';
require_once '../includes/admin_check.php';
require_once '../includes/csrf.php';  // CSRF protection

// Generate CSRF token for add/edit form (single token per page load)
$csrf_token = generateCSRFToken('admin_product');

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/products/';

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        die('Unable to create the product upload directory.');
    }
}

if (!is_writable($upload_dir)) {
    die('The product upload directory is not writable.');
}

// Improved upload function with robust validation
function uploadProductImage($file, $existing_image = null) {
    global $upload_dir;
    
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return $existing_image;
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        $_SESSION['upload_error'] = 'Only JPG, PNG, WEBP, and GIF files are allowed (invalid extension).';
        return $existing_image;
    }
    
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        $mime_type = mime_content_type($file['tmp_name']);
    }
    
    if (!in_array($mime_type, $allowed_mimes)) {
        $_SESSION['upload_error'] = 'Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed (detected: ' . $mime_type . ').';
        return $existing_image;
    }
    
    if ($file['size'] > $max_size) {
        $_SESSION['upload_error'] = 'File size must be less than 5MB';
        return $existing_image;
    }
    
$filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
$upload_path = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    $_SESSION['upload_error'] = 'Failed to upload the product image. Check server folder permissions.';
    return $existing_image;
}

chmod($upload_path, 0644);

return 'uploads/products/' . $filename;
}

// Handle product deletion (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['delete_product']);
    $token_action = 'delete_product_' . $product_id;
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', $token_action)) {
        header('Location: products.php?error=Invalid security token');
        exit;
    }
    
    // Get product image to delete file
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product && !empty($product['image_url']) && strpos($product['image_url'], 'uploads/') === 0) {
        $file_path = __DIR__ . '/../' . ltrim($product['image_url'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    header('Location: products.php?success=Product deleted successfully');
    exit;
}

// Handle form submission for add/edit (with CSRF)
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'admin_product')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category'] ?? '');

$allowed_categories = [
    'Hair Care',
    'Hair Accessories',
    'Satin Range'
];

if (!in_array($category, $allowed_categories, true)) {
    $error = 'Please select a valid product category.';
}
        $stock_quantity = intval($_POST['stock_quantity']);
        $sku = trim($_POST['sku'] ?? '');
        $compare_price = !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null;
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $image_url = uploadProductImage($_FILES['image_file']);
            if (isset($_SESSION['upload_error'])) {
                $error = $_SESSION['upload_error'];
                unset($_SESSION['upload_error']);
            }
        } elseif (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
            $image_url = $_POST['existing_image'];
        } elseif (empty($image_url)) {
            $image_url = 'https://placehold.co/400x300?text=No+Image';
        }
        
        if (empty($error)) {
            if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
                // Update existing product
                $product_id = intval($_POST['product_id']);
                
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $old_product = $stmt->fetch();
                    if ($old_product && !empty($old_product['image_url']) && strpos($old_product['image_url'], 'uploads/') === 0) {
                        $old_file = __DIR__ . '/../' . ltrim($old_product['image_url'], '/');
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, compare_price = ?, 
                        category = ?, image_url = ?, stock_quantity = ?, sku = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $compare_price, $category, 
                               $image_url, $stock_quantity, $sku, $product_id]);
                $success = 'Product updated successfully!';
            } else {
                // Add new product
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, price, compare_price, category, 
                                         image_url, stock_quantity, sku) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $price, $compare_price, $category, 
                               $image_url, $stock_quantity, $sku]);
                $success = 'Product added successfully!';
            }
        }
    }
}

// Fetch products
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR sku LIKE ? OR category LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Check if we're in edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="admin-products-page py-4">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Manage Products</h1>
                <p class="text-muted">Add, edit, and manage your product catalog</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetProductForm()">
                <i class="fas fa-plus me-2"></i> Add New Product
            </button>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Search and Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Search</label>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Product name, SKU, or category..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px">Image</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th style="width: 120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No products found</p>
                                        <?php if ($search || $category_filter): ?>
                                            <button class="btn btn-sm btn-link mt-2" onclick="window.location.href='products.php'">
                                                Clear filters
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="align-middle">
                                        <?php if (!empty($product['image_url']) && strpos($product['image_url'], 'placeholder') === false): ?>
                                            <img src="<?php echo htmlspecialchars(
    rtrim(SITE_URL, '/') . '/' . ltrim($product['image_url'], '/'),
    ENT_QUOTES,
    'UTF-8'
); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="img-thumbnail"
                                                 style="width: 60px; height: 60px; object-fit: cover;"
                                                 onerror="this.onerror=null; this.src='https://placehold.co/60x60?text=No+Image';">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <p class="small text-muted mb-0">
                                                <?php echo strlen($product['description'] ?? '') > 80 ? 
                                                      substr($product['description'], 0, 80) . '...' : 
                                                      ($product['description'] ?? 'No description'); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <code><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <div>
                                            <strong class="text-primary">R <?php echo number_format($product['price'], 2); ?></strong>
                                            <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                                <br>
                                                <small class="text-muted text-decoration-line-through">
                                                    R <?php echo number_format($product['compare_price'], 2); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] < 10): ?>
                                            <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?> left</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo $product['stock_quantity']; ?> in stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    onclick='editProduct(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>)'
                                                    title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <!-- Delete form with unique CSRF token -->
                                            <form method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                                <input type="hidden" name="delete_product" value="<?php echo $product['id']; ?>">
                                                <?php 
                                                    // Generate a unique token for this specific delete action
                                                    $delete_token = generateCSRFToken('delete_product_' . $product['id']);
                                                ?>
                                                <input type="hidden" name="csrf_token" value="<?php echo $delete_token; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Product">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetProductForm()"></button>
            </div>
            <form method="POST" action="" id="productForm" enctype="multipart/form-data">
                <!-- CSRF token for add/edit -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="save_product" value="1">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="existing_image" id="existing_image">
                    
                    <div class="row">
                        <div class="col-md-7">
                            <h6 class="mb-3">Basic Information</h6>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label required">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                <small class="text-muted">Detailed product description, features, and benefits</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label required">Price (R) *</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="compare_price" class="form-label">Compare Price (R)</label>
                                    <input type="number" class="form-control" id="compare_price" name="compare_price" 
                                           step="0.01" min="0">
                                    <small class="text-muted">Original price for sale display</small>
                                </div>
                            </div>
                            
                            <div class="row">
<div class="col-md-6 mb-3">
    <label for="category" class="form-label required">Category *</label>
    <select class="form-select" id="category" name="category" required>
        <option value="">Select a category</option>
        <option value="Hair Care">Hair Care</option>
        <option value="Hair Accessories">Hair Accessories</option>
        <option value="Satin Range">Satin Range</option>
    </select>
</div>
                                <div class="col-md-6 mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           placeholder="Product code">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label required">Stock Quantity *</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <h6 class="mb-3">Product Image</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload Image</label>
                                <div id="imagePreview" class="image-preview mb-2" 
                                     style="width: 100%; height: 200px; border: 2px dashed #dee2e6; border-radius: 8px; 
                                            background-size: cover; background-position: center; background-repeat: no-repeat;">
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
                                    </div>
                                </div>
                                <input type="file" class="form-control" id="image_file" name="image_file" 
                                       accept="image/jpeg,image/png,image/webp,image/gif">
                                <small class="text-muted">Recommended: Square image (800x800px). Max 5MB. JPG, PNG, WEBP, or GIF.</small>
                            </div>
                            
                            <div class="alert alert-info small">
                                <i class="fas fa-info-circle me-2"></i>
                                Images will be uploaded to the server.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetProductForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveProductBtn">
                        <i class="fas fa-save me-2"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.required:after {
    content: " *";
    color: red;
}
.image-preview {
    cursor: pointer;
    transition: all 0.3s ease;
}
.image-preview:hover {
    border-color: var(--primary-color, #8b7355) !important;
    background-color: rgba(139, 115, 85, 0.05);
}
</style>

<script>
// Pass SITE_URL to JavaScript
var SITE_URL = '<?php echo SITE_URL; ?>';

function resetProductForm() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('existing_image').value = '';
    document.getElementById('productModalTitle').textContent = 'Add New Product';
    
    const preview = document.getElementById('imagePreview');
    preview.style.backgroundImage = '';
    preview.innerHTML = `
        <div class="d-flex align-items-center justify-content-center h-100">
            <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
        </div>
    `;
}

function editProduct(product) {
    resetProductForm();
    
    document.getElementById('product_id').value = product.id;
    document.getElementById('name').value = product.name;
    document.getElementById('description').value = product.description || '';
    document.getElementById('price').value = product.price;
    document.getElementById('compare_price').value = product.compare_price || '';
    document.getElementById('category').value = product.category;
    document.getElementById('sku').value = product.sku || '';
    document.getElementById('stock_quantity').value = product.stock_quantity;
    document.getElementById('productModalTitle').textContent = 'Edit Product';
    
    if (product.image_url && !product.image_url.includes('placeholder')) {
        const fullUrl = SITE_URL + product.image_url;
        const preview = document.getElementById('imagePreview');
        preview.style.backgroundImage = `url(${fullUrl})`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
        preview.innerHTML = '';
        document.getElementById('existing_image').value = product.image_url;
    }
    
    var modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

document.getElementById('image_file')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Only JPG, PNG, WEBP, and GIF files are allowed');
        this.value = '';
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        this.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('imagePreview');
        preview.style.backgroundImage = `url(${e.target.result})`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
        preview.innerHTML = '';
    };
    reader.readAsDataURL(file);
});

document.getElementById('productForm')?.addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const price = parseFloat(document.getElementById('price').value);
    const category = document.getElementById('category').value;
    const stock = parseInt(document.getElementById('stock_quantity').value);
    const imageFile = document.getElementById('image_file').files[0];
    const existingImage = document.getElementById('existing_image').value;
    
    if (!name) {
        e.preventDefault();
        alert('Please enter a product name');
        return;
    }
    
    if (isNaN(price) || price <= 0) {
        e.preventDefault();
        alert('Please enter a valid price');
        return;
    }
    
const allowedCategories = [
    'Hair Care',
    'Hair Accessories',
    'Satin Range'
];

if (!allowedCategories.includes(category)) {
    e.preventDefault();
    alert('Please select a valid category');
    return;
}
    
    if (isNaN(stock) || stock < 0) {
        e.preventDefault();
        alert('Please enter a valid stock quantity');
        return;
    }
    
    const isEdit = document.getElementById('product_id').value;
    if (!isEdit && !imageFile && !existingImage) {
        e.preventDefault();
        alert('Please upload a product image');
        return;
    }
    
    const saveBtn = document.getElementById('saveProductBtn');
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
    saveBtn.disabled = true;
});
</script>

<?php require_once '../includes/footer.php'; ?>