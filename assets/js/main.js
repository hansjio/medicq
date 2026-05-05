/**
 * MEDICQ - Main JavaScript File
 */

// Global SITE_URL variable — set by PHP in footer.php before this script loads
const SITE_URL = window.SITE_URL || '';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdowns
    initDropdowns();
    
    // Initialize tabs
    initTabs();
    
    // Initialize modals
    initModals();
    
    // Initialize notifications
    loadNotifications();
    
    // Initialize flash messages auto-dismiss
    initFlashMessages();
});

/**
 * Dropdown functionality
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.notification-bell, .user-menu-toggle');
        
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });
                
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
    
    // Prevent dropdown menu clicks from closing
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

/**
 * Tab functionality
 */
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabGroup = this.closest('.tabs');
            const contentContainer = tabGroup ? tabGroup.nextElementSibling : null;
            const targetId = this.dataset.tab;
            
            // Update active button
            if (tabGroup) {
                tabGroup.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            }
            this.classList.add('active');
            
            // Update active content
            if (contentContainer) {
                contentContainer.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            }
        });
    });
}

/**
 * Modal functionality
 */
function initModals() {
    // Open modal buttons
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.dataset.modal;
            openModal(modalId);
        });
    });
    
    // Close modal buttons
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });
    
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Load notifications
 */
function loadNotifications() {
    const notificationList = document.getElementById('notification-list');
    if (!notificationList) return;
    
    fetch(SITE_URL + '/api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                notificationList.innerHTML = data.notifications.map(n => `
                    <div class="notification-item ${n.is_read ? '' : 'unread'}">
                        <div class="notification-icon ${getNotificationIconClass(n.type)}">
                            <i class="fas ${getNotificationIcon(n.type)}"></i>
                        </div>
                        <div class="notification-content">
                            <h4>${escapeHtml(n.title)}</h4>
                            <p>${escapeHtml(n.message)}</p>
                            <span class="notification-time">${formatTimeAgo(n.created_at)}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                notificationList.innerHTML = '<div style="padding: var(--spacing-6); text-align: center; color: var(--gray-500);">No notifications</div>';
            }
        })
        .catch(err => {
            notificationList.innerHTML = '<div style="padding: var(--spacing-4); text-align: center; color: var(--gray-500);">Failed to load notifications</div>';
        });
}

function getNotificationIcon(type) {
    const icons = {
        'confirmation': 'fa-check-circle',
        'reminder': 'fa-clock',
        'cancellation': 'fa-times-circle',
        'appointment': 'fa-calendar-check',
        'system': 'fa-info-circle'
    };
    return icons[type] || 'fa-bell';
}

function getNotificationIconClass(type) {
    const classes = {
        'confirmation': 'success',
        'reminder': 'warning',
        'cancellation': 'danger',
        'appointment': 'info',
        'system': 'info'
    };
    return classes[type] || 'info';
}

/**
 * Flash messages
 */
function initFlashMessages() {
    const alerts = document.querySelectorAll('.alert[data-dismiss]');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Utility: Format time ago
 */
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    
    return date.toLocaleDateString();
}

/**
 * Utility: Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Utility: Show loading state
 */
function showLoading(element) {
    element.disabled = true;
    element.dataset.originalText = element.innerHTML;
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
}

/**
 * Utility: Hide loading state
 */
function hideLoading(element) {
    element.disabled = false;
    element.innerHTML = element.dataset.originalText;
}

/**
 * Utility: Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;';
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Utility: Confirm dialog
 */
function confirmAction(message) {
    return new Promise((resolve) => {
        if (confirm(message)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

/**
 * Form validation
 */
function validateForm(formElement) {
    let isValid = true;
    const requiredFields = formElement.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        const errorElement = field.parentElement.querySelector('.form-error');
        
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            if (errorElement) {
                errorElement.textContent = 'This field is required';
                errorElement.style.display = 'block';
            }
        } else {
            field.classList.remove('is-invalid');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }
        
        // Email validation
        if (field.type === 'email' && field.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                isValid = false;
                field.classList.add('is-invalid');
                if (errorElement) {
                    errorElement.textContent = 'Please enter a valid email address';
                    errorElement.style.display = 'block';
                }
            }
        }
    });
    
    return isValid;
}