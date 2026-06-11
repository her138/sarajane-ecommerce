<?php
$pageTitle = "Page Not Found - SaraJane";
require_once 'includes/header.php';
?>

<div class="error-page py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-8">
                <div class="error-content">
                    <div class="error-code mb-4">
                        <span class="display-1 fw-bold text-primary">404</span>
                    </div>
                    <h1 class="h2 mb-3">Oops! Page Not Found</h1>
                    <p class="text-muted mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
                    <div class="error-actions">
                        <a href="<?php echo SITE_URL; ?>index.php" class="btn btn-primary me-2">
                            <i class="fas fa-home me-2"></i> Go Home
                        </a>
                        <a href="<?php echo SITE_URL; ?>shop.php" class="btn btn-outline-secondary">
                            <i class="fas fa-shopping-bag me-2"></i> Browse Products
                        </a>
                    </div>
                    <div class="mt-5">
                        <form action="<?php echo SITE_URL; ?>search.php" method="GET" class="search-form">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" placeholder="Search for products...">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
}
.error-code span {
    font-size: 6rem;
    font-weight: 600;
    color: var(--primary-color);
    text-shadow: 2px 2px 4px rgba(0,0,0,0.05);
}
@media (max-width: 768px) {
    .error-code span {
        font-size: 4rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>