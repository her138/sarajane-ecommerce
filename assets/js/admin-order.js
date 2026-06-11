/**
 * Admin Orders JavaScript
 * Uses global adminCsrfToken declared in orders.php
 */

document.addEventListener('DOMContentLoaded', function() {
    initStatusDropdowns();
    initSendEmailButtons();
});

function initStatusDropdowns() {
    document.querySelectorAll('.status-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const newStatus = this.getAttribute('data-status');
            const dropdown = this.closest('.status-dropdown');
            const orderId = dropdown.getAttribute('data-order-id');
            
            if (!orderId || !newStatus) return;
            
            if (newStatus === 'cancelled' && !confirm('Cancel this order? Stock will be restored.')) {
                return;
            }
            
            const toggleBtn = dropdown.closest('.d-flex').querySelector('.dropdown-toggle');
            const originalHtml = toggleBtn ? toggleBtn.innerHTML : '';
            if (toggleBtn) {
                toggleBtn.disabled = true;
                toggleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('order_id', orderId);
            formData.append('status', newStatus);
            formData.append('csrf_token', adminCsrfToken);  // global variable
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the badge
                    const oldBadge = document.getElementById(`status-badge-${orderId}`);
                    if (oldBadge && data.badge_html) {
                        oldBadge.outerHTML = data.badge_html;
                    }
                    // Update the CSRF token for next request
                    if (data.new_csrf_token) {
                        window.adminCsrfToken = data.new_csrf_token;
                    }
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || 'Failed to update status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error: ' + error.message, 'error');
            })
            .finally(() => {
                if (toggleBtn) {
                    toggleBtn.disabled = false;
                    toggleBtn.innerHTML = originalHtml;
                }
                const bsDropdown = bootstrap.Dropdown.getInstance(toggleBtn);
                if (bsDropdown) bsDropdown.hide();
            });
        });
    });
}

function initSendEmailButtons() {
    document.querySelectorAll('.send-email-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.getAttribute('data-order-id');
            const email = this.getAttribute('data-email');
            
            if (!orderId) return;
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            // Use absolute path from site root
            fetch((window.SaraJane?.siteUrl || '../') + 'ajax/send-order-email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, email: email })
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification(`Email sent for order #${data.order_number}`, 'success');
                } else {
                    showNotification(data.message || 'Failed to send email', 'error');
                }
            })
            .catch(error => {
                console.error('Email error:', error);
                showNotification('Email failed: ' + error.message, 'error');
            })
            .finally(() => {
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    });
}

function showNotification(message, type = 'success') {
    const existing = document.querySelector('.custom-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed; top: 80px; right: 20px; padding: 12px 24px;
        border-radius: 8px; background-color: ${type === 'error' ? '#dc3545' : '#5a3e5e'};
        color: white; z-index: 9999; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}