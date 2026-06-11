<?php
$pageTitle = "About SaraJane - Hair Care & Accessories";
require_once 'includes/header.php';
?>

<div class="about-page py-5">
    <div class="container">
        <!-- Hero Section (simple, like feelsilki) -->
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h1 class="display-4 mb-3">Hair Care that Cares</h1>
                <p class="lead text-muted">Thoughtfully crafted for healthy, beautiful hair – every day.</p>
            </div>
        </div>

        <!-- Story Section (two columns, left image / right text) -->
        <div class="row align-items-center g-5 mb-5 py-4">
            <div class="col-md-6">
                <img src="https://placehold.co/600x400?text=SaraJane+Story" 
                     class="img-fluid rounded shadow-sm" 
                     alt="SaraJane founder">
            </div>
            <div class="col-md-6">
                <h2 class="mb-3">Our Story</h2>
                <p>SaraJane began with a simple belief: everyone deserves hair that feels as good as it looks. After years of struggling to find products that truly nourish without harsh chemicals, our founder set out to create a line that puts hair health first.</p>
                <p>Today, we’re proud to offer a curated collection of premium hair care and accessories, designed to protect, style, and celebrate every texture. Every product is tested, loved, and made with you in mind.</p>
                <p class="mt-3"><strong>Because your hair is your crown. Let’s make it shine.</strong></p>
            </div>
        </div>

        <!-- Values / Promise Section (three columns, like feelsilki) -->
        <div class="row text-center g-4 py-5">
            <div class="col-md-4">
                <div class="promise-icon mb-3">
                    <i class="fas fa-leaf fa-2x text-primary"></i>
                </div>
                <h3 class="h5">Quality Ingredients</h3>
                <p class="text-muted">Only the finest, hair-friendly ingredients selected for optimal results.</p>
            </div>
            <div class="col-md-4">
                <div class="promise-icon mb-3">
                    <i class="fas fa-shield-alt fa-2x text-primary"></i>
                </div>
                <h3 class="h5">Hair‑Safe Materials</h3>
                <p class="text-muted">Accessories designed to protect your hair from breakage and damage.</p>
            </div>
            <div class="col-md-4">
                <div class="promise-icon mb-3">
                    <i class="fas fa-shipping-fast fa-2x text-primary"></i>
                </div>
                <h3 class="h5">Fast & Secure Delivery</h3>
                <p class="text-muted">Free shipping over R550. Discreet packaging and easy returns.</p>
            </div>
        </div>

        <!-- Meet the Founder / Team (optional, similar to feelsilki's brand voice) -->
        <div class="row align-items-center g-5 py-5">
            <div class="col-md-6 order-md-2">
                <img src="https://placehold.co/600x400?text=Founder+Image" 
                     class="img-fluid rounded shadow-sm" 
                     alt="SaraJane founder">
            </div>
            <div class="col-md-6 order-md-1">
                <h2 class="mb-3">Meet Jane</h2>
                <p>Jane started SaraJane after years of searching for products that truly respected her hair. Frustrated by dryness, breakage, and unreliable ingredients, she decided to create her own.</p>
                <p>What began as a personal journey is now a brand trusted by thousands. Jane still tests every formula herself and personally approves every accessory design. Her mission is simple: to help you love your hair.</p>
                <div class="mt-4">
                    <a href="shop.php" class="btn btn-outline-primary">Shop our collection →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.about-page .promise-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    background: rgba(139, 115, 85, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}
.about-page .promise-icon:hover {
    background: var(--primary-color);
    color: white;
    transform: scale(1.05);
}
</style>

<?php require_once 'includes/footer.php'; ?>