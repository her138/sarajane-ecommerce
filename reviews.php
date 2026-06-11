<?php
$pageTitle = "Customer Reviews - SaraJane";
require_once 'includes/header.php';

// In a real implementation, you'd fetch from a `reviews` table
// For now, we'll display the same static reviews as on the homepage
$reviews = [
    ['name' => 'Muneiwa M.', 'date' => 'Oct 2025', 'rating' => 5, 'title' => 'Well protected parcel', 'text' => 'Good communication from the delivery team and parcel came in good condition.', 'category' => 'shipping'],
    ['name' => 'Wesley N.', 'date' => 'Aug 2025', 'rating' => 5, 'title' => 'All round amazing experience', 'text' => 'Superb service and quick delivery. Good quality products. 110% recommended 💯🙏🏽❤️', 'category' => 'service'],
    ['name' => 'Sune C.', 'date' => 'Sep 2025', 'rating' => 5, 'title' => 'Delivered on time', 'text' => "I'm very impressed with the quick delivery. Thank you so much.", 'category' => 'shipping'],
    ['name' => 'Tracy R.', 'date' => 'Oct 2025', 'rating' => 5, 'title' => 'Love the service', 'text' => 'Thanks for great service as usual. I also needed to exchange an item and that also went very smoothly.', 'category' => 'service'],
];

// You can add pagination later if needed
?>

<div class="reviews-page py-5">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h1 class="display-4 mb-3">Customer Reviews</h1>
                <p class="lead text-muted">See what our customers are saying about SaraJane</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($reviews as $review): ?>
                <div class="col-md-6">
                    <div class="testimonial-item h-100">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span><?php echo substr($review['name'], 0, 2); ?></span>
                                </div>
                                <div>
                                    <h5 class="reviewer-name mb-0"><?php echo $review['name']; ?></h5>
                                    <span class="review-date"><?php echo $review['date']; ?></span>
                                </div>
                            </div>
                            <div class="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="review-title"><?php echo $review['title']; ?></h6>
                        <p class="review-text"><?php echo $review['text']; ?></p>
                        <div class="review-footer">
                            <span class="review-category"><?php echo ucfirst($review['category']); ?> Review</span>
                            <div class="review-actions">
                                <button class="like-btn"><i class="far fa-thumbs-up"></i> 0</button>
                                <button class="comment-btn"><i class="far fa-comment"></i> 0</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="shop.php" class="btn btn-primary btn-lg">Shop Now</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>