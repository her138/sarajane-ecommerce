// ==================== MAIN INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initNewsletter();
    initCategoryFilters();
    initCartCount();
    initAddToCartButtons();
    initProductFilterFromUrl();
    initRemoveFromCart();
    initRemoveFromWishlist();
    initScrollReviews();
    initQuantityStepper();
});

// ==================== MOBILE MENU & BOTTOM NAV ====================
function initMobileMenu() {
    const bottomNav = document.getElementById('bottomNav');
    if (bottomNav) {
        let lastScrollTop = 0;
        let ticking = false;
        const scrollThreshold = 6;
        let indicator = document.querySelector('.bottom-nav-indicator');
        if (!indicator && bottomNav) {
            indicator = document.createElement('div');
            indicator.className = 'bottom-nav-indicator';
            document.body.appendChild(indicator);
        }

        function handleScroll() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (Math.abs(scrollTop - lastScrollTop) < scrollThreshold) {
                ticking = false;
                return;
            }
            if (scrollTop < 10) {
                bottomNav.classList.remove('hide');
            } else if (scrollTop > lastScrollTop) {
                bottomNav.classList.add('hide');
            } else {
                bottomNav.classList.remove('hide');
                bottomNav.style.transition = 'transform 0.44s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.32s ease';
                setTimeout(() => { bottomNav.style.transition = ''; }, 500);
            }
            if (bottomNav.classList.contains('hide')) {
                indicator.classList.add('visible');
            } else {
                indicator.classList.remove('visible');
            }
            lastScrollTop = scrollTop;
            ticking = false;
        }

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(handleScroll);
                ticking = true;
            }
        });
    }

    // Hamburger menu drawer
    const menuBtn = document.getElementById('mobileMenuBtn');
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('drawerOverlay');
    const closeBtn = document.getElementById('drawerCloseBtn');

    function openDrawer() {
        drawer?.classList.add('open');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer?.classList.remove('open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuBtn) menuBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);
}

// ==================== NEWSLETTER ====================
function initNewsletter() {
    const form = document.getElementById('newsletterForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const emailInput = form.querySelector('input[type="email"]');
        const email = emailInput.value;
        const btn = form.querySelector('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const res = await fetch((window.SaraJane?.siteUrl || '') + 'ajax/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, csrf_token: window.SaraJane?.csrf?.newsletter || '' })
            });
            const data = await res.json();
            showNotification(data.message || (data.success ? 'Subscribed!' : 'Error'), data.success ? 'success' : 'error');
            if (data.success) emailInput.value = '';
        } catch (err) {
            showNotification('Something went wrong. Please try again.', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
}

// ==================== CATEGORY FILTERS (SHOP PAGE) ====================
function initCategoryFilters() {
    const filterContainer = document.querySelector('.category-filters');
    if (!filterContainer) return;
    const buttons = filterContainer.querySelectorAll('.filter-btn');
    const products = document.querySelectorAll('.product-item');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const category = btn.getAttribute('data-category');
            products.forEach(product => {
                const productCat = product.getAttribute('data-category');
                if (category === 'all' || productCat === category) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
    });
}

// ==================== CART COUNT ====================
function initCartCount() {
    updateCartCount();
}
async function updateCartCount() {
    try {
        const res = await fetch((window.SaraJane?.siteUrl || '') + 'ajax/get_cart_count.php');
        const data = await res.json();
        document.querySelectorAll('.cart-badge, .count').forEach(badge => {
            badge.textContent = data.count;
        });
    } catch (e) { console.warn(e); }
}

// ==================== UNIFIED ADD TO CART ====================
function initAddToCartButtons() {
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.removeEventListener('click', handleAddToCart);
        btn.addEventListener('click', handleAddToCart);
    });
}
async function handleAddToCart(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    const productId = btn.getAttribute('data-product-id');
    if (!productId) return;

    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('csrf_token', window.SaraJane?.csrf?.cart || '');
        const res = await fetch((window.SaraJane?.siteUrl || '') + 'ajax/add_to_cart.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            updateCartCount();
            showNotification('Product added to cart!', 'success');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.backgroundColor = '#28a745';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.backgroundColor = '';
                btn.disabled = false;
            }, 1500);
        } else {
            showNotification(data.message || 'Error', 'error');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    } catch (err) {
        showNotification('Network error', 'error');
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

// ==================== REMOVE FROM CART ====================
function initRemoveFromCart() {
    document.querySelectorAll('.cart-remove-btn').forEach(btn => {
        btn.removeEventListener('click', handleCartRemove);
        btn.addEventListener('click', handleCartRemove);
    });
}
async function handleCartRemove(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    const cartId = btn.getAttribute('data-cart-id');
    if (!cartId) return;

    if (!confirm('Remove this item from your cart?')) return;

    try {
        const formData = new FormData();
        formData.append('cart_id', cartId);
        formData.append('csrf_token', window.SaraJane?.csrf?.cart || '');
        const res = await fetch((window.SaraJane?.siteUrl || '') + 'ajax/remove_cart_item.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            const row = btn.closest('tr');
            if (row) row.remove();
            updateCartCount();
            updateCartTotals();
            showNotification('Item removed', 'success');
            if (document.querySelectorAll('.cart-table tbody tr').length === 0) {
                location.reload();
            }
        } else {
            showNotification(data.message || 'Failed to remove', 'error');
        }
    } catch (err) {
        showNotification('Network error', 'error');
    }
}

// ==================== QUANTITY STEPPER ====================
function initQuantityStepper() {
    document.querySelectorAll('.qty-plus, .qty-minus').forEach(btn => {
        btn.removeEventListener('click', handleStepper);
        btn.addEventListener('click', handleStepper);
    });
}

function handleStepper(e) {
    const btn = e.currentTarget;
    const cartId = btn.getAttribute('data-cart-id');
    const input = document.querySelector(`.cart-quantity-input[data-cart-id="${cartId}"]`);
    if (!input) return;
    let newVal = parseInt(input.value);
    if (btn.classList.contains('qty-plus')) {
        newVal = Math.min(parseInt(input.max) || 999, newVal + 1);
    } else if (btn.classList.contains('qty-minus')) {
        newVal = Math.max(0, newVal - 1);
    }
    input.value = newVal;
    updateCartItemQuantity(cartId, newVal);
}

async function updateCartItemQuantity(cartId, quantity) {
    try {
        const formData = new FormData();
        formData.append('update_item', '1');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);
        formData.append('csrf_token', window.SaraJane?.csrf?.cart || '');
        const res = await fetch((window.SaraJane?.siteUrl || '') + 'cart.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (res.ok) {
            updateCartCount();
            updateCartTotals();
            // Update row total
            const row = document.querySelector(`tr:has(.cart-quantity-input[data-cart-id="${cartId}"])`);
            if (row) {
                const price = parseFloat(row.querySelector('.item-price').innerText.replace('R', '').trim());
                const newTotal = price * quantity;
                row.querySelector('.item-total').innerText = 'R ' + newTotal.toFixed(2);
            }
        }
    } catch (err) {
        console.warn(err);
    }
}

// ==================== RECALCULATE CART TOTALS ====================
function updateCartTotals() {
    let subtotal = 0;
    document.querySelectorAll('.cart-table tbody tr').forEach(row => {
        const priceText = row.querySelector('.item-price').innerText.replace('R', '').trim();
        const price = parseFloat(priceText);
        const qty = parseInt(row.querySelector('.cart-quantity-input').value);
        subtotal += price * qty;
    });
    const tax = subtotal * 0.10;
    const total = subtotal + tax;
    document.querySelector('.subtotal-value').innerText = 'R ' + subtotal.toFixed(2);
    document.querySelector('.tax-value').innerText = 'R ' + tax.toFixed(2);
    document.querySelector('.total-value').innerText = 'R ' + total.toFixed(2);
}

// ==================== REMOVE FROM WISHLIST ====================
function initRemoveFromWishlist() {
    document.querySelectorAll('.remove-wishlist').forEach(btn => {
        btn.removeEventListener('click', handleWishlistRemove);
        btn.addEventListener('click', handleWishlistRemove);
    });
}
async function handleWishlistRemove(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    const productId = btn.getAttribute('data-product-id');
    if (!productId) return;

    if (!confirm('Remove this item from your wishlist?')) return;

    try {
        const res = await fetch((window.SaraJane?.siteUrl || '') + 'ajax/remove_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${encodeURIComponent(productId)}&csrf_token=${encodeURIComponent(window.SaraJane?.csrf?.wishlist || '')}`
        });
        const data = await res.json();
        if (data.success) {
            if (window.location.pathname.includes('wishlist.php')) {
                window.location.reload();
            } else {
                showNotification('Removed from wishlist', 'success');
                const card = btn.closest('.product-card');
                if (card) card.remove();
            }
        } else {
            showNotification(data.message || 'Failed to remove', 'error');
        }
    } catch (err) {
        showNotification('Network error', 'error');
    }
}

// ==================== PRODUCT FILTER FROM URL (shop.php) ====================
function initProductFilterFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category');
    if (category && document.querySelector('.category-filters')) {
        const targetBtn = document.querySelector(`.filter-btn[data-category="${category}"]`);
        if (targetBtn) targetBtn.click();
    }
}

// ==================== SCROLL-TRIGGERED REVIEWS ====================
function initScrollReviews() {
    const reviewsSection = document.querySelector('.scroll-reviews');
    if (!reviewsSection) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                document.querySelectorAll('.reveal-item').forEach(el => el.classList.add('in'));
                startQuoteRotation();
                observer.disconnect();
            }
        });
    }, { threshold: 0.12 });
    observer.observe(reviewsSection);

    // Quote rotation data
    const quotes = [
        { stars: 5, text: "I’ve struggled with breakage for years. SaraJane’s satin pillowcase transformed my hair. No more tangles, and my curls actually look defined in the morning!", name: "Thandi M.", city: "Johannesburg", verified: true, product: "Satin Pillowcase", avatar: "TM" },
        { stars: 5, text: "The Hypochlorous Acid Daily Spritz is a game changer. My scalp feels so much calmer, and the redness has faded.", name: "Michael v.d. Merwe", city: "Pretoria", verified: true, product: "Hypochlorous Spritz", avatar: "MV" },
        { stars: 5, text: "I was skeptical at first, but the detangling brush is magic. My daughter’s hair used to be a battle – now she actually enjoys brushing.", name: "Naledi K.", city: "Durban", verified: true, product: "Detangle Brush", avatar: "NK" },
        { stars: 5, text: "Living in Cape Town, the wind ruins my style. The satin swirl cap keeps my hair protected and frizz‑free overnight.", name: "Nabeelah P.", city: "Cape Town", verified: true, product: "Satin Swirl Cap", avatar: "NP" }
    ];
    let quoteIndex = 0;
    let progressInterval = null;
    const quoteText = document.querySelector('.hero-quote-text');
    const quoteStars = document.querySelector('.hero-quote-stars');
    const authorName = document.querySelector('.author-name');
    const authorCity = document.querySelector('.author-city');
    const authorAvatar = document.querySelector('.author-avatar');
    const verifiedBadge = document.querySelector('.verified-badge');
    const productTag = document.querySelector('.product-tag');
    const progressFill = document.querySelector('.progress-fill');
    const dotsContainer = document.querySelector('.quote-controls');

    function updateQuote(index, direction = 'next') {
        const q = quotes[index];
        if (!quoteText) return;
        quoteText.style.transition = 'opacity 0.3s, transform 0.3s';
        quoteText.style.opacity = '0';
        quoteText.style.transform = direction === 'next' ? 'translateY(-9px)' : 'translateY(9px)';
        setTimeout(() => {
            if (quoteStars) quoteStars.innerHTML = '★'.repeat(q.stars) + '☆'.repeat(5 - q.stars);
            if (quoteText) quoteText.innerText = q.text;
            if (authorName) authorName.innerText = q.name;
            if (authorCity) authorCity.innerText = q.city;
            if (authorAvatar) authorAvatar.innerText = q.avatar;
            if (verifiedBadge) verifiedBadge.innerHTML = q.verified ? '<i class="fas fa-check-circle"></i> Verified purchase' : '';
            if (productTag) productTag.innerText = q.product;
            if (quoteText) {
                quoteText.style.opacity = '1';
                quoteText.style.transform = direction === 'next' ? 'translateY(11px)' : 'translateY(-11px)';
                setTimeout(() => { if (quoteText) quoteText.style.transform = 'translateY(0)'; }, 50);
            }
        }, 300);
        const dots = document.querySelectorAll('.quote-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    function startQuoteRotation() {
        if (progressInterval) clearInterval(progressInterval);
        let progress = 0;
        if (progressFill) progressFill.style.width = '0%';
        progressInterval = setInterval(() => {
            progress += 2;
            if (progress >= 100) {
                progress = 0;
                const next = (quoteIndex + 1) % quotes.length;
                updateQuote(next, 'next');
                quoteIndex = next;
            }
            if (progressFill) progressFill.style.width = progress + '%';
        }, 100);
    }

    function moveToQuote(index) {
        if (index === quoteIndex) return;
        const direction = index > quoteIndex ? 'next' : 'prev';
        updateQuote(index, direction);
        quoteIndex = index;
        if (progressFill) progressFill.style.width = '0%';
        if (progressInterval) {
            clearInterval(progressInterval);
            startQuoteRotation();
        }
    }

    if (dotsContainer && quotes.length) {
        dotsContainer.innerHTML = '';
        quotes.forEach((_, idx) => {
            const dot = document.createElement('div');
            dot.classList.add('quote-dot');
            if (idx === 0) dot.classList.add('active');
            dot.addEventListener('click', () => moveToQuote(idx));
            dotsContainer.appendChild(dot);
        });
    }
    if (quotes.length && quoteStars && quoteText) {
        const first = quotes[0];
        quoteStars.innerHTML = '★'.repeat(first.stars) + '☆'.repeat(5 - first.stars);
        quoteText.innerText = first.text;
        if (authorName) authorName.innerText = first.name;
        if (authorCity) authorCity.innerText = first.city;
        if (authorAvatar) authorAvatar.innerText = first.avatar;
        if (verifiedBadge) verifiedBadge.innerHTML = first.verified ? '<i class="fas fa-check-circle"></i> Verified purchase' : '';
        if (productTag) productTag.innerText = first.product;
    }
}

// ==================== GLOBAL NOTIFICATION ====================
function showNotification(message, type = 'success') {
    const existing = document.querySelector('.custom-notification');
    if (existing) existing.remove();
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.textContent = message;
    notification.style.backgroundColor = type === 'error' ? '#dc3545' : '#5a3e5e';
    notification.style.color = 'white';
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}