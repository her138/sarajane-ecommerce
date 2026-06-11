<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';

// Custom error handlers (production only)
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error: $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        include __DIR__ . '/../500.php';
    } else {
        echo "<h1>500 Internal Server Error</h1><p>Please try again later.</p>";
    }
    exit;
}

function customExceptionHandler($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        include __DIR__ . '/../500.php';
    } else {
        echo "<h1>500 Internal Server Error</h1><p>Please try again later.</p>";
    }
    exit;
}

if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    set_error_handler("customErrorHandler");
    set_exception_handler("customExceptionHandler");
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Get cart count
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cartCount = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Header Cart Error: " . $e->getMessage());
    }
} elseif (isset($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= isset($pageTitle) ? "{$pageTitle} - SaraJane" : 'SaraJane - Luxury Hair Care & Accessories' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <?php if (isset($customCSS) && $customCSS): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/<?php echo $customCSS; ?>">
    <?php endif; ?>
</head>
<body>

<!-- ==================== DESKTOP HEADER ==================== -->
<header class="main-header">
    <div class="top-bar">
        <div class="container text-center">
            <small><i class="fas fa-truck"></i> Free shipping on orders over R550</small>
            <small class="ms-3"><i class="fas fa-undo-alt"></i> 30-Day Return Policy</small>
        </div>
    </div>
    <nav class="navbar">
        <div class="container">
            <!-- Logo (left) -->
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>index.php">SaraJane</a>
            
            <!-- Navigation links (centered) -->
            <div class="navbar-collapse" id="desktopNav">
                <ul class="navbar-nav">
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="<?php echo SITE_URL; ?>index.php">Home</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : '' ?>" href="<?php echo SITE_URL; ?>shop.php">Shop</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>" href="<?php echo SITE_URL; ?>about.php">About</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>" href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
                </ul>
            </div>
            
            <!-- Icons (right) -->
            <div class="navbar-icons">
                <a href="#" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search"></i></a>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-user"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>account.php">My Account</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>orders.php">My Orders</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>wishlist.php">Wishlist</a></li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/dashboard.php">Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>login.php">Login</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <a href="<?php echo SITE_URL; ?>cart.php" class="position-relative">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-badge" id="cartCountDesktop"><?= $cartCount ?></span>
                </a>
            </div>
        </div>
    </nav>
</header>

<!-- ==================== MOBILE HEADER & NAVIGATION ==================== -->
<div class="mobile-top-bar" id="mobileTopBar">
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    <a href="<?php echo SITE_URL; ?>index.php" class="mobile-logo">SaraJane</a>
    <div class="mobile-icons">
        <a href="#" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search"></i></a>
        <a href="<?php echo SITE_URL; ?>cart.php" class="position-relative">
            <i class="fas fa-shopping-bag"></i>
            <span class="cart-badge" id="mobileCartCount"><?= $cartCount ?></span>
        </a>
    </div>
</div>

<div class="bottom-nav" id="bottomNav">
    <ul>
        <li><a href="<?php echo SITE_URL; ?>index.php" class="bottom-nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i><span>HOME</span></a></li>
        <li><a href="<?php echo SITE_URL; ?>shop.php" class="bottom-nav-link <?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : '' ?>"><i class="fas fa-store"></i><span>SHOP</span></a></li>
        <li><a href="<?php echo SITE_URL; ?>about.php" class="bottom-nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>"><i class="fas fa-info-circle"></i><span>ABOUT</span></a></li>
        <li><a href="<?php echo SITE_URL; ?>contact.php" class="bottom-nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i><span>CONTACT</span></a></li>
    </ul>
</div>
<div class="bottom-nav-indicator"></div>

<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <button class="drawer-close" id="drawerCloseBtn"><i class="fas fa-times"></i></button>
    <ul class="drawer-menu">
        <li><a href="<?php echo SITE_URL; ?>index.php">Home</a></li>
        <li><a href="<?php echo SITE_URL; ?>shop.php">Shop</a></li>
        <li><a href="<?php echo SITE_URL; ?>about.php">About</a></li>
        <li><a href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?php echo SITE_URL; ?>account.php">My Account</a></li>
            <li><a href="<?php echo SITE_URL; ?>orders.php">Order History</a></li>
            <li><a href="<?php echo SITE_URL; ?>wishlist.php">Wishlist</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo SITE_URL; ?>admin/dashboard.php">Admin Dashboard</a></li>
            <?php endif; ?>
            <li><a href="<?php echo SITE_URL; ?>logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="<?php echo SITE_URL; ?>login.php">Login</a></li>
            <li><a href="<?php echo SITE_URL; ?>register.php">Register</a></li>
        <?php endif; ?>
    </ul>
    <div class="drawer-social">
        <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
        <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
        <a href="#" target="_blank"><i class="fab fa-whatsapp"></i></a>
        <a href="#" target="_blank"><i class="fab fa-tiktok"></i></a>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4">
                <form action="<?php echo SITE_URL; ?>search.php" method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" placeholder="Search for products..." autofocus>
                        <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<main class="main-content">