<?php
$pageTitle = "Home";
require_once 'includes/header.php';

// Fetch best sellers (most added to cart)
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(c.id) as cart_count
    FROM products p
    LEFT JOIN cart c ON p.id = c.product_id
    GROUP BY p.id
    ORDER BY cart_count DESC
    LIMIT 6
");
$stmt->execute();
$bestSellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <!-- Hero Carousel Section -->
    <section class="hero-carousel">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
            <!-- Indicators -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3"></button>
            </div>
            
            <!-- Slides -->
            <div class="carousel-inner">
                <!-- Banner 1 -->
                <div class="carousel-item active">
                    <div class="banner-slide" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                        <div class="banner-content">
                            <h1 class="banner-title">Premium Hair Care Collection</h1>
                            <p class="banner-subtitle">Transform your hair with our professional-grade products</p>
                            <a href="shop.php" class="btn btn-horizon btn-lg banner-btn">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Banner 2 -->
                <div class="carousel-item">
                    <div class="banner-slide" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1583484963886-cfe2bff2945f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                        <div class="banner-content">
                            <h1 class="banner-title">New Arrivals</h1>
                            <p class="banner-subtitle">Discover our latest hair care innovations</p>
                            <a href="category.php?cat=new-arrivals" class="btn btn-horizon btn-lg banner-btn">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Banner 3 -->
                <div class="carousel-item">
                    <div class="banner-slide" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1556228578-9c360e1d8d34?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                        <div class="banner-content">
                            <h1 class="banner-title">Satin Collection</h1>
                            <p class="banner-subtitle">Protect your hair while you sleep</p>
                            <a href="category.php?cat=satin-range" class="btn btn-horizon btn-lg banner-btn">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Banner 4 -->
                <div class="carousel-item">
                    <div class="banner-slide" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1560343090-f0409e92791a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                        <div class="banner-content">
                            <h1 class="banner-title">Save 20% Today</h1>
                            <p class="banner-subtitle">Limited time offer on all premium products</p>
                            <a href="shop.php?discount=20" class="btn btn-horizon btn-lg banner-btn">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

<section class="category-highlights py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Shop by Category</h2>
                <p class="section-subtitle">Discover our curated collections</p>
            </div>
        </div>
        
        <div class="row g-4">
           <div class="col-lg-4">
    <div class="category-container">
        <img src="<?php echo SITE_URL; ?>assets/images/category/t.t-category.png" 
             alt="Hair Care" 
             class="category-bg-img">
        <div class="category-header">
            <h3>Hair Care</h3>
        </div>
        <div class="category-footer">
            <a href="category.php?cat=hair-care" class="category-shop-link">
                Shop Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <div class="category-container">
        <img src="<?php echo SITE_URL; ?>assets/images/category/s.b-category.png" 
             alt="Hair Accessories" 
             class="category-bg-img">
        <div class="category-header">
            <h3>Hair Accessories</h3>
        </div>
        <div class="category-footer">
            <a href="category.php?cat=hair-accessories" class="category-shop-link">
                Shop Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <div class="category-container">
        <img src="<?php echo SITE_URL; ?>assets/images/category/argan-category.png" 
             alt="Satin Range" 
             class="category-bg-img">
        <div class="category-header">
            <h3>Satin Range</h3>
        </div>
        <div class="category-footer">
            <a href="category.php?cat=satin-range" class="category-shop-link">
                Shop Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
        </div>
    </div>
</section>


<!-- Featured Products Section -->
<section class="featured-products py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Best Sellers</h2>
                <p class="section-subtitle">Most loved by our customers</p>
            </div>
        </div>

        <?php if (empty($bestSellers)): ?>
            <div class="text-center py-5">
                <p class="text-muted">No products yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <!-- Use the same grid class as shop page -->
            <div class="products-grid">
                <?php foreach ($bestSellers as $product): ?>
                    <?php include 'includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="shop.php" class="btn btn-outline-dark btn-lg">Shop All Products</a>
            </div>
        </div>
    </div>
</section>

    <!-- Brand Promise -->
    <section class="brand-promise py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Why Choose SaraJane</h2>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 text-center">
                    <div class="promise-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h4 class="promise-title">Quality Ingredients</h4>
                    <p class="promise-text">Only the finest, hair-friendly ingredients selected for optimal results.</p>
                </div>
                
                <div class="col-lg-4 text-center">
                    <div class="promise-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="promise-title">Hair-Safe Materials</h4>
                    <p class="promise-text">Accessories designed to protect your hair from breakage and damage.</p>
                </div>
                
                <div class="col-lg-4 text-center">
                    <div class="promise-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h4 class="promise-title">Fast & Secure Delivery</h4>
                    <p class="promise-text">Free shipping over $50. Discreet packaging and easy returns.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Add the USP Section -->
    <div id="shopify-section-template--20157118841031__fb36fc9a-b750-4219-8dd4-d985ffe0399d" class="shopify-section section section--home-usp">

    <div data-section-id="template--20157118841031__fb36fc9a-b750-4219-8dd4-d985ffe0399d" data-section-type="image-text" data-section-name="home-usp">
      <div class="section__inner page-width ">
        <div class="category_main" data-ga-tracking-config="
      {
        &quot;creative_action_registered&quot;: false,
        &quot;promotion_name&quot;: &quot;&quot;,
        &quot;promotion_id&quot;: &quot;&quot;,
        &quot;promotion_slot&quot;: &quot;Home USP&quot;,
        &quot;buttons&quot;: [
          
          {
            &quot;text&quot;: &quot;CTA1 | delivery &amp; payments&quot;,
            &quot;link&quot;: &quot;/pages/faq&quot;,
            &quot;action_registered&quot;: false
          }
          

                ,{
                  &quot;text&quot;: &quot;CTA3 | Free standard shipping over R500&quot;,
                  &quot;link&quot;: &quot;/pages/shipping-delivery&quot;,
                  &quot;action_registered&quot;: false
                },
            
                {
                  &quot;text&quot;: &quot;CTA4 | Sign up to our newsletter for exclusive deals&quot;,
                  &quot;link&quot;: &quot;/pages/sign-up-for-20-off&quot;,
                  &quot;action_registered&quot;: false
                },
            
                {
                  &quot;text&quot;: &quot;CTA5 | Give the gift of choice with a Lovisa Gift Card&quot;,
                  &quot;link&quot;: &quot;/products/digital-gift-card&quot;,
                  &quot;action_registered&quot;: false
                },
            
                {
                  &quot;text&quot;: &quot;CTA6 | Instant EFT Available&quot;,
                  &quot;link&quot;: &quot;#&quot;,
                  &quot;action_registered&quot;: false
                }
            
          
        ]
      }
    ">
    </div>

    <!-- Scroll-Triggered Reviews Section -->
<section class="scroll-reviews">
    <div class="container">
        <div class="reviews-header reveal-item stagger-1">
            <h2>Client stories</h2>
        </div>

        <!-- Hero Rotating Quote Card -->

       <div class="hero-quote-card reveal-item stagger-2">
    <!-- Author info now on top -->
    <div class="hero-quote-author">
        <div class="author-avatar">NK</div>
        <div class="author-info">
            <div class="author-name">Naledi K.</div>
            <div class="author-city">Durban</div>
        </div>
        <div class="verified-badge"><i class="fas fa-check-circle"></i> Verified purchase</div>
        <div class="product-tag">Detangle Brush</div>
    </div>
    <!-- Stars -->
    <div class="hero-quote-stars">★★★★★</div>
    <!-- Quote text -->
    <div class="hero-quote-text">Finally found a detangling brush that doesn't hurt.</div>
    <!-- Dot controls -->
    <div class="quote-controls"></div>
</div>

        <!-- Mini Review Cards Strip -->
        <div class="mini-reviews-strip reveal-item stagger-3">
            <div class="mini-reviews-track">
                <div class="mini-review-card">
                    <div class="stars">★★★★★</div>
                    <div class="quote">“Silki Curls Shampoo left my hair so soft without stripping it.”</div>
                    <div class="reviewer">Palesa M.</div>
                    <div class="location">Durban</div>
                    <div class="product">Hair Care</div>
                </div>
                <div class="mini-review-card">
                    <div class="stars">★★★★★</div>
                    <div class="quote">“Best satin scrunchies ever! No creases and super gentle.”</div>
                    <div class="reviewer">Candice L.</div>
                    <div class="location">Cape Town</div>
                    <div class="product">Satin Scrunchies</div>
                </div>
                <div class="mini-review-card">
                    <div class="stars">★★★★★</div>
                    <div class="quote">“The argan oil serum is a miracle for my split ends.”</div>
                    <div class="reviewer">Bongani N.</div>
                    <div class="location">Pretoria</div>
                    <div class="product">Argan Serum</div>
                </div>
                <div class="mini-review-card">
                    <div class="stars">★★★★★</div>
                    <div class="quote">“Finally found a detangling brush that doesn't hurt.”</div>
                    <div class="reviewer">Zanele K.</div>
                    <div class="location">Johannesburg</div>
                    <div class="product">Detangle Brush</div>
                </div>
                <div class="mini-review-card">
                    <div class="stars">★★★★★</div>
                    <div class="quote">“Satin bonnet keeps my twist-out fresh for days.”</div>
                    <div class="reviewer">Thabiso R.</div>
                    <div class="location">Soweto</div>
                    <div class="product">Satin Bonnet</div>
                </div>
            </div>
        </div>

        <!-- Read All Reviews Link -->
        <a href="reviews.php" class="read-all-link reveal-item stagger-7">
            <span>Read all reviews</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

    <!-- Add CSS for the enhanced review features -->
   

    <!-- Add JavaScript for review filtering -->
    <script>
    // Review filtering functionality
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get filter value
            const filterValue = this.getAttribute('data-filter');
            
            // Show all testimonials if "all" is selected
            if (filterValue === 'all') {
                document.querySelectorAll('.testimonial-item').forEach(item => {
                    item.style.display = 'block';
                });
            } else {
                // Hide all testimonials
                document.querySelectorAll('.testimonial-item').forEach(item => {
                    item.style.display = 'none';
                });
                
                // Show only testimonials with matching category
                document.querySelectorAll(`.testimonial-item[data-category="${filterValue}"]`).forEach(item => {
                    item.style.display = 'block';
                });
            }
        });
    });
    
    // Like button functionality
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const likeIcon = this.querySelector('i');
            
            if (likeIcon.classList.contains('far')) {
                likeIcon.classList.remove('far');
                likeIcon.classList.add('fas');
                let currentCount = parseInt(this.textContent);
                this.innerHTML = `<i class="fas fa-thumbs-up"></i> ${currentCount + 1}`;
            } else {
                likeIcon.classList.remove('fas');
                likeIcon.classList.add('far');
                let currentCount = parseInt(this.textContent);
                this.innerHTML = `<i class="far fa-thumbs-up"></i> ${currentCount - 1}`;
            }
        });
    });
    
    // Comment button functionality
    document.querySelectorAll('.comment-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Comment functionality coming soon!');
        });
    });
    
    </script>
       <!-- Instagram Section -->
    <section class="instagram-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Follow Our Journey</h2>
                    <p class="section-subtitle">@SaraJaneOfficial</p>
                </div>
            </div>
            
            <!-- Infinite Scroll Container -->
            <div class="instagram-scroll-container">
                <div class="instagram-scroll-track">
                    <!-- First set of posts -->
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <div class="instagram-post-item">
                            <div class="instagram-post">
                                <div class="post-image">
                                    <!-- Instagram-like color gradients -->
                                    <div class="post-gradient" style="background: <?php 
                                        $gradients = [
                                            'linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D)',
                                            'linear-gradient(45deg, #FFDC80, #FFDC80, #FCAF45, #F77737, #F56040, #E1306C)',
                                            'linear-gradient(45deg, #FFDC80, #FFDC80, #FFDC80, #FCAF45, #F77737, #F56040)',
                                            'linear-gradient(45deg, #833AB4, #C13584, #E1306C, #FD1D1D)',
                                            'linear-gradient(45deg, #405DE6, #5851DB, #833AB4)',
                                            'linear-gradient(45deg, #FFDC80, #FCAF45, #F77737)',
                                            'linear-gradient(45deg, #E1306C, #FD1D1D, #F77737)',
                                            'linear-gradient(45deg, #405DE6, #5851DB, #C13584)',
                                            'linear-gradient(45deg, #F56040, #F77737, #FCAF45)',
                                            'linear-gradient(45deg, #833AB4, #E1306C, #FD1D1D)',
                                            'linear-gradient(45deg, #FFDC80, #FCAF45, #E1306C)',
                                            'linear-gradient(45deg, #405DE6, #833AB4, #E1306C)'
                                        ];
                                        echo $gradients[$i-1];
                                    ?>;"></div>
                                    
                                    <!-- Hover overlay -->
                                    <div class="post-overlay">
                                        <div class="overlay-content">
                                            <div class="instagram-icons">
                                                <div class="icon-item">
                                                    <i class="fas fa-heart"></i>
                                                    <span class="icon-count"><?php echo rand(100, 999); ?></span>
                                                </div>
                                                <div class="icon-item">
                                                    <i class="fas fa-comment"></i>
                                                    <span class="icon-count"><?php echo rand(20, 199); ?></span>
                                                </div>
                                                <div class="icon-item">
                                                    <i class="fas fa-share"></i>
                                                </div>
                                                <div class="icon-item">
                                                    <i class="fas fa-bookmark"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Instagram logo -->
                                    <div class="instagram-logo">
                                        <i class="fab fa-instagram"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                    
                    <!-- Duplicate set for seamless scrolling -->
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <div class="instagram-post-item">
                            <div class="instagram-post">
                                <div class="post-image">
                                    <div class="post-gradient" style="background: <?php 
                                        $gradients = [
                                            'linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D)',
                                            'linear-gradient(45deg, #FFDC80, #FFDC80, #FCAF45, #F77737, #F56040, #E1306C)',
                                            'linear-gradient(45deg, #FFDC80, #FFDC80, #FFDC80, #FCAF45, #F77737, #F56040)',
                                            'linear-gradient(45deg, #833AB4, #C13584, #E1306C, #FD1D1D)',
                                            'linear-gradient(45deg, #405DE6, #5851DB, #833AB4)',
                                            'linear-gradient(45deg, #FFDC80, #FCAF45, #F77737)',
                                            'linear-gradient(45deg, #E1306C, #FD1D1D, #F77737)',
                                            'linear-gradient(45deg, #405DE6, #5851DB, #C13584)',
                                            'linear-gradient(45deg, #F56040, #F77737, #FCAF45)',
                                            'linear-gradient(45deg, #833AB4, #E1306C, #FD1D1D)',
                                            'linear-gradient(45deg, #FFDC80, #FCAF45, #E1306C)',
                                            'linear-gradient(45deg, #405DE6, #833AB4, #E1306C)'
                                        ];
                                        echo $gradients[$i-1];
                                    ?>;"></div>
                                    
                                    <div class="post-overlay">
                                        <div class="overlay-content">
                                            <div class="instagram-icons">
                                                <div class="icon-item">
                                                    <i class="fas fa-heart"></i>
                                                    <span class="icon-count"><?php echo rand(100, 999); ?></span>
                                                </div>
                                                <div class="icon-item">
                                                    <i class="fas fa-comment"></i>
                                                    <span class="icon-count"><?php echo rand(20, 199); ?></span>
                                                </div>
                                                <div class="icon-item">
                                                    <i class="fas fa-share"></i>
                                                </div>
                                                <div class="icon-item">
                                                    <i class="fas fa-bookmark"></i>
                                                </div>
                                            </div>
                                            <div class="post-caption">
                                                <p>Discover beautiful hair transformations 💫</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="instagram-logo">
                                        <i class="fab fa-instagram"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Add CSS for enhanced Instagram section -->
 

    <!-- Add JavaScript for enhanced interactivity -->
    <script>
    // Pause/Resume animation on button click
    const scrollTrack = document.querySelector('.instagram-scroll-track');
    const pauseBtn = document.getElementById('pauseScroll');
    const resumeBtn = document.getElementById('resumeScroll');
    
    if (pauseBtn && resumeBtn) {
        pauseBtn.addEventListener('click', () => {
            scrollTrack.style.animationPlayState = 'paused';
        });
        
        resumeBtn.addEventListener('click', () => {
            scrollTrack.style.animationPlayState = 'running';
        });
    }
    
    // Instagram post click functionality
    document.querySelectorAll('.instagram-post').forEach(post => {
        post.addEventListener('click', function() {
            const postNumber = Array.from(this.closest('.instagram-scroll-track').children).indexOf(this.closest('.instagram-post-item')) + 1;
            alert(`Opening Instagram post ${postNumber}. In production, this would open the actual Instagram post.`);
        });
    });
    
    // Instagram follow button click
    document.querySelector('.btn-instagram-follow')?.addEventListener('click', function(e) {
        e.preventDefault();
        // In production, this would open Instagram in a new tab
        window.open('https://www.instagram.com/SaraJaneOfficial', '_blank');
    });
    
    // Hover effects for icon items
    document.querySelectorAll('.icon-item').forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.2)';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    const instagramContainer = document.querySelector('.instagram-scroll-container');
    
    if (instagramContainer) {
        instagramContainer.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        instagramContainer.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
    }
    
    function handleSwipe() {
        if (touchEndX < touchStartX) {
            // Swipe left - resume animation
            scrollTrack.style.animationPlayState = 'running';
        }
        if (touchEndX > touchStartX) {
            // Swipe right - pause animation
            scrollTrack.style.animationPlayState = 'paused';
        }
    }
    </script>
    <!-- Add Custom CSS for the new design -->


    <!-- Add JavaScript for product quick add -->
    <script>
    // Handle quick add to cart buttons
    document.querySelectorAll('.product-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    updateCartCount();
                    
                    // Show notification
                    showNotification('Product added to cart!', 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            });
        });
    });
    
    // Carousel auto-play
    const heroCarousel = document.getElementById('heroCarousel');
    if (heroCarousel) {
        const carousel = new bootstrap.Carousel(heroCarousel, {
            interval: 3000,
            ride: 'carousel'
        });
    }
    </script>

<?php require_once 'includes/footer.php'; ?>