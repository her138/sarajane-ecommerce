<?php
$pageTitle = "Server Error - SaraJane";
require_once 'includes/header.php';
?>

<div class="error-page py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-8">
                <div class="error-content">
                    <div class="error-code mb-4">
                        <span class="display-1 fw-bold text-primary">500</span>
                    </div>
                    <h1 class="h2 mb-3">Something Went Wrong</h1>
                    <p class="text-muted mb-4">We're sorry, but there was an internal server error. Our team has been notified and is working to fix the issue.</p>
                    <div class="error-actions">
                        <a href="<?php echo SITE_URL; ?>index.php" class="btn btn-primary me-2">
                            <i class="fas fa-home me-2"></i> Go Home
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Go Back
                        </a>
                    </div>
                    <div class="mt-5">
                        <p class="text-muted">If the problem persists, please <a href="<?php echo SITE_URL; ?>contact.php">contact us</a>.</p>
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